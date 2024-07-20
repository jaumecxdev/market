<?php

namespace App\Libraries;


use App\Brand;
use App\Traits\HelperTrait;
use App\Product;
use App\Supplier;
use App\SupplierCategory;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\Mpe as FacadesMpe;

use PrestaShopWebservice;
use PrestaShopWebserviceException;
use Throwable;


class SupplierPrestaWS extends SupplierWS
{
    use HelperTrait;

    protected $apiUrl = null;
    protected $apiKey = null;
    protected $debug = false;
    protected $webService = null;
    protected $fixed_brand = null;          // All products -> Same Brand

    protected $shops = [1];
    protected $languages = [1];
    protected $language = null;
    protected $id_shop_default = 1;
    protected $rate_standard_id = 1;        // id_tax_rules_group
    protected $rate_reduced_id = 2;
    protected $rate_super_reduced_id = 3;
    protected $standard_rate = 21;          // iva standard

    protected $category_names_excluded = ['Home'];      // Not used, ONLY: $rejected_categories
    protected $currency_id = 1;     // EUR Euro
    protected $presta_statuses = [
        'new'           => 1,       // Nuevo
        'used'          => 2,       // Usado
        'refurbished'   => 3,       // Remanufacturado
    ];

    protected $presta_products = null;      // ps_product
    protected $presta_products_shop = null; // ps_product_shop
    protected $presta_product_langs = null; // ps_product_lang
    protected $presta_stocks = null;        // ps_stock_available
    protected $presta_categories = null;    // ps_category
    protected $presta_images = null;        // ps_image
    protected $presta_image_shops = null;   // ps_image_shop

    protected $has_product_options = 0;
    protected $product_options = null;

    protected $stock_min = 0;



    function __construct(Supplier $supplier)
    {
        try {
            parent::__construct($supplier);

            $this->webService = new PrestaShopWebservice($this->apiUrl, $this->apiKey, $this->debug);
            $this->file_child = 'product';
            $this->rejected_categories = [2];


            /* try {
                // Talla, Color
                $opt['resource'] = 'product_features';
                $opt['display'] = "full";
                //$opt['language'] = $this->language;
                //$opt['filter[active]'] = 1;

                $xml = $this->webService->get($opt);



                return $xml->children()->children();
            }
            catch (PrestaShopWebserviceException $e) {

                return $this->nullWithErrors($e, __METHOD__, null);
            }
            catch (Throwable $th) {

                return $this->nullWithErrors($th, __METHOD__, null);
            }
 */



        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $supplier);
        }
    }


    /************** PRIVATE PRESTA FUNCTIONS ***************/


    private function getPrestaLanguages()
    {
        try {
            $opt['resource'] = 'languages';
            $opt['display'] = 'full';
            //$opt['filter[active]'] = 1;

            $xml = $this->webService->get($opt);

            return $xml->children()->children();
        }
        catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, null);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getPrestaProductOptions()
    {
        try {
            // Talla, Color
            $opt['resource'] = 'product_options';
            $opt['display'] = "[id,name, public_name]";
            $opt['language'] = $this->language;
            //$opt['filter[active]'] = 1;

            $xml = $this->webService->get($opt);

            return $xml->children()->children();
        }
        catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, null);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getPrestaOptionValues()
    {
        // id, id_attribute_group, name
        try {
            $opt['resource'] = 'product_option_values';
            $opt['display'] = 'full';       //"[id,active,available_for_order,name]";
            $opt['language'] = $this->language;
            //$opt['filter[active]'] = 1;

            $xml = $this->webService->get($opt);

            return $xml->children()->children();
        }
        catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, null);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getOptionValuesCollect()
    {
        try {
            $xml = $this->getPrestaOptionValues();
            if (isset($xml)) {
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                $optionValuesCollect = collect($array['product_option_value']);

                return $optionValuesCollect->keyBy('id');
            }

            return $this->nullAndStorage(__METHOD__, $xml);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getPrestaProductCombinations()
    {
        try {
            $opt['resource'] = 'combinations';
            $opt['display'] = 'full';       //"[id,active,available_for_order,name]";
            //$opt['filter[active]'] = 1;

            $xml = $this->webService->get($opt);

            /* $items = [];
            foreach ($xml->children()->children() as $item) {
                $items[(int)$item->id_product][] = $item;
            }

            return $items; */

            return $xml->children()->children();
        }
        catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, null);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getProductCombinationsCollect()
    {
        try {
            $xml = $this->getPrestaProductCombinations();
            if (isset($xml)) {
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                $productCombinationsCollect = collect($array['combination']);

                return $productCombinationsCollect->groupBy('id_product');
            }

            return $this->nullAndStorage(__METHOD__, $xml);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getPrestaCategories($id_category_default = null)
    {
        try {
            $opt['resource'] = 'categories';
            $opt['language'] = $this->language;
            if (isset($id_category_default)) $opt['filter[id]'] = $id_category_default;
            else $opt['display'] = 'full';       //"[id, id_parent, is_root_category, level_depth, name]";

            $xml = $this->webService->get($opt);
            $presta_categories = $xml->children()->children();

            Storage::put($this->storage_dir. 'categories/getPrestaCategories.xml', $xml->asXML());
            return $presta_categories;

        }
        catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, null);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getCategoriesCollect($id_category_default = null)
    {
        try {
            $xml = $this->getPrestaCategories($id_category_default);
            if (isset($xml)) {
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                $prestaCollect = collect($array['category']);

                return $prestaCollect->keyBy('id');
            }

            return $this->nullAndStorage(__METHOD__, $xml);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getPrestaStocks($id_stock_available = null)
    {
        try {
            $opt['resource'] = 'stock_availables';
            if (isset($id_stock_available)) $opt['filter[id]'] = $id_stock_available;
            else $opt['display'] = 'full';       //"[id,active,available_for_order,name]";

            $xml = $this->webService->get($opt);

            return $xml->children()->children();
        }
        catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, null);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getStocksCollect($id_stock_available = null)
    {
        try {
            $xml = $this->getPrestaStocks($id_stock_available);
            if (isset($xml)) {
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);
                $stocksCollect = collect($array['stock_available']);

                return $stocksCollect->keyBy('id_product');
            }

            return $this->nullAndStorage(__METHOD__, $xml);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getPrestaProductImages($supplier_product)
    {
        try {
        // http://thehpshop.test:8080/42611-thickbox_default/tablet-lenovo-.jpg
            $product_images = [];

            if (isset($supplier_product['associations']['images']['image'])) {

                foreach ($supplier_product['associations']['images']['image'] as $image) {

                    $image_id = $image['id'] ?? $image;
                    // thickbox_default
                    $link = $supplier_product['link_rewrite']['language'];
                    $link = is_array($link) ? $link[0] : $link;
                    $product_images[] = $this->apiUrl. '/' .$image_id. '-thickbox_default/' .$link. '.jpg';
                    // large_default
                    //$product_images[] = $this->apiUrl. '/' .$image_id. '-large_default/' .$supplier_product['link_rewrite']['language']. '.jpg';
                }
            }

            return $product_images;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function getPrestaProducts($supplierSku = null)
    {
        try {
            $opt['resource'] = 'products';
            $opt['filter[active]'] = 1;
            $opt['language'] = $this->language;
            if (isset($supplierSku)) $opt['resource'] .= '/'.$supplierSku;
            else $opt['display'] = 'full';       //"[id,active,available_for_order,name]";

            $xml = $this->webService->get($opt);

            return $xml->children()->children();
        }
        catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, null);
        }
    }


    /************** PRIVATE FUNCTIONS ***************/


   /*  private function getProductArray($supplierSku)
    {
        try {
            $xml = $this->getPrestaProducts($supplierSku);
            if (isset($xml) && isset($xml->id)) {
                $json = json_encode($xml);
                $array = json_decode($json, TRUE);

                $product_stock_available = $this->getPrestaStocks($array['associations']['stock_availables']['stock_available']['id']);
                $product_category_default = $this->getPrestaCategories($array['id_category_default']);
                if (isset($product_stock_available) && isset($product_category_default)) {
                    $quantity = (integer)$product_stock_available->stock_available->quantity;

                    $category_name = (string)$product_category_default->category->name->language;
                    $array['quantity'] = $quantity;
                    $array['category_name'] = $category_name;

                    return $array;
                }
            }

            return $xml;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $supplierSku);
        }
    }

 */

    private function getProductCombinations($productCombinationsCollect, $optionValuesCollect)
    {
        try {
            $combinations = [];
            //$productCombinations = $productCombinationsCollect[$id_product];
            foreach ($productCombinationsCollect as $productCombination) {

                $combination = [];
                $combination['reference'] = $productCombination['reference'];
                $combination['price'] = (float)$productCombination['price'];
                $combination['quantity'] = (int)$productCombination['quantity'];

                foreach ($productCombination['associations']['product_option_values']['product_option_value'] as $product_option_value) {
                    $optionValueId = (int)$product_option_value['id'];
                    $optionValue = (string)$optionValuesCollect[$optionValueId]['name']['language'];
                    $id_attribute_group = (int)$optionValuesCollect[$optionValueId]['id_attribute_group'];
                    $product_option = $this->product_options[$id_attribute_group];

                    $combination[$product_option] = $optionValue;
                }

                $combinations[] = $combination;
            }

            return $combinations;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getProductsCollect($supplierSku = null)
    {
        try {
            $xml = $this->getPrestaProducts($supplierSku);
            if (isset($xml)) {
                $json = json_encode($xml);
                Storage::put($this->storage_dir. 'products/' .date('Y-m-d_H-i'). '_getProductsCollect.json', $json);

                $array = json_decode($json, TRUE);
                if (isset($supplierSku))
                    $productsCollect = collect([$array]);
                else
                    $productsCollect = (isset($this->file_child) && isset($array[$this->file_child])) ? collect($array[$this->file_child]) : collect($array);

                $stocksCollect = $this->getStocksCollect();
                $categoriesCollect = $this->getCategoriesCollect();
                if (!isset($stocksCollect) || !isset($categoriesCollect))
                    return $this->msgAndStorage(__METHOD__, 'Error obteniendo las collecciones de stocks y categorías.', null);

                if ($this->has_product_options) {
                    $productCombinationsCollect = $this->getProductCombinationsCollect();
                    $optionValuesCollect = $this->getOptionValuesCollect();
                }

                $productsCollect = $productsCollect->map(function ($item, $key)
                    use ($stocksCollect, $categoriesCollect, $productCombinationsCollect, $optionValuesCollect) {

                    if (!in_array($item['id_category_default'], [$this->rejected_categories])) {
                        $category_name = $categoriesCollect[$item['id_category_default']]['name']['language'] ?? '';
                        $category_name = is_array($category_name) ? $category_name[0] : $category_name;
                        $quantity = $stocksCollect[$item['id']]['quantity'] ?? 0;
                        $item['quantity'] = $quantity;
                        $item['category_name'] = $category_name;

                        if ($this->has_product_options && isset($productCombinationsCollect[$item['id']]))
                            $item['combinations'] = $this->getProductCombinations($productCombinationsCollect[$item['id']], $optionValuesCollect);

                        return $item;
                    }
                });

                Storage::put($this->storage_dir. 'products/' .date('Y-m-d_H-i'). '_getProductsCollect.json', json_encode($productsCollect->keyBy($this->supplier->supplierSku_field)->toArray()));
                Storage::put($this->storage_dir. 'stocks/' .date('Y-m-d_H-i'). '_getProductsCollect.json', json_encode($stocksCollect->toArray()));
                Storage::put($this->storage_dir. 'categories/' .date('Y-m-d_H-i'). '_getProductsCollect.json', json_encode($categoriesCollect->toArray()));

                return $productsCollect->keyBy($this->supplier->supplierSku_field);
            }

            return $this->nullAndStorage(__METHOD__, $xml);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplierSku);
        }
    }


    private function getSupplierCategory($supplier_product)
    {
        try {
            $category_id = isset($supplier_product[$this->supplier->category_id_field]) ?
                $supplier_product[$this->supplier->category_id_field] :
                ((isset($this->subcategory) && is_string($supplier_product[$this->subcategory])) ?
                    ($supplier_product[$this->subcategory]. ' / ' .$supplier_product[$this->supplier->category_field]) :
                    $supplier_product[$this->supplier->category_field]);

            $category_name = isset($supplier_product[$this->supplier->category_field]) ?
                ((isset($this->subcategory) && is_string($supplier_product[$this->subcategory])) ?
                    ($supplier_product[$this->subcategory]. ' / ' .$supplier_product[$this->supplier->category_field]) :
                    $supplier_product[$this->supplier->category_field]) :
                $supplier_product[$this->supplier->category_id_field];

            $category_name = is_array($category_name) ? $category_name[0] : $category_name;

            return SupplierCategory::firstOrCreate([
                'supplier_id'       => $this->supplier->id,
                'supplierCategoryId'=> mb_substr($category_id, 0, 64),        // $supplier_product[$this->supplier->category_id_field] ?? $supplier_product[$this->supplier->category_field],
            ],[
                'name'              => mb_substr($category_name, 0, 255),      //$supplier_product[$this->supplier->category_field] ?? $supplier_product[$this->supplier->category_id_field],
            ]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function getBrand($supplier_product)
    {
        try {
            if (isset($this->fixed_brand))
                $brand_name = $this->fixed_brand;
            elseif (!is_array($supplier_product[$this->supplier->brand_field]))
                $brand_name = $supplier_product[$this->supplier->brand_field];
            else
                $brand_name = 'Desconocido';

            return Brand::firstOrCreate([
                'name' => $brand_name
            ], []);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function getUpdateProductFields($supplier_product, $combination = null)
    {
        try {
            // BRAND
            $brand = $this->getBrand($supplier_product);

            // STATUS
            if (isset($supplier_product[$this->supplier->status_field]))
                $status_id = $this->presta_statuses[$supplier_product[$this->supplier->status_field]] ?? 1;

            // CATEGORY
            if (isset($this->supplier->category_id_field) || isset($this->supplier->category_field)) {
                $supplier_category = $this->getSupplierCategory($supplier_product);
                if (!isset($supplier_category))
                    return $this->nullAndStorage(__METHOD__, $supplier_product);
                elseif (in_array($supplier_category->supplierCategoryId, $this->rejected_categories)) {
                    return null;
                }
            }

            // CANON
            $canon  = isset($this->supplier->canon_field) ? $supplier_product[$this->supplier->canon_field] : 0;

            // SUPPLIER PARAMS
            $rappel = isset($this->supplier->rappel_field) ? $supplier_product[$this->supplier->rappel_field] : 0;
            $ports  = isset($this->supplier->ports_field) ? $supplier_product[$this->supplier->ports_field] : 0;
            if ($canon !=0 || $rappel !=0 || $ports !=0) {

                if (isset($supplier_category->category_id) || isset($brand)) {

                    if ($canon > 0)
                        $supplier_category->category->firstOrCreateCanon($canon, 'es');

                    $this->supplier->firstOrCreateParam(
                        $brand->id ?? null,
                        $supplier_category->category_id ?? null,
                        $rappel,
                        $ports);
                }
            }

            $supplierSku = $supplier_product[$this->supplier->supplierSku_field] ?? null;       // id

            [$pn, $ean] = FacadesMpe::getPnEan(
                $supplier_product[$this->supplier->pn_field] ?? null,       // reference
                $supplier_product[$this->supplier->ean_field] ?? null       // ena13
            );

            if (isset($combination)) {
                $parent_reference = $supplier_product['reference'];
                $pn = $combination['reference'];

                $cost = $combination['price'];
                $stock = $combination['quantity'];

                $size = isset($combination['size']) ? $combination['size'] : null;
                $color = isset($combination['color']) ? $combination['color'] : null;
                $material = isset($combination['material']) ? $combination['material'] : null;
                $style = isset($combination['style']) ? $combination['style'] : null;
                $gender = isset($combination['gender']) ? $combination['gender'] : null;
            }
            else {
                $parent_reference = null;

                $cost = isset($this->supplier->cost_field) ? FacadesMpe::roundFloat($supplier_product[$this->supplier->cost_field]) : 0;
                $stock = isset($this->supplier->stock_field) ? $supplier_product[$this->supplier->stock_field] : $this->stock_min;
                $size = $supplier_product[$this->supplier->size_field] ?? null;
                $color = $supplier_product[$this->supplier->color_field] ?? null;
                $material = $supplier_product[$this->supplier->material_field] ?? null;
                $style = $supplier_product[$this->supplier->style_field] ?? null;
                $gender = $supplier_product[$this->supplier->gender_field] ?? null;
            }

            // FAKE DAVEDANS
            if ($this->supplier->code == 'davedans') $stock = 100;

            return [
                'parent_reference'      => $parent_reference,
                'cost'                  => $cost,
                'stock'                 => $stock,
                'size'                  => $size,
                'color'                 => $color,
                'material'              => $material,
                'style'                 => $style,
                'gender'                => $gender,

                'tax'                   => isset($this->supplier->tax_field) ? $supplier_product[$this->supplier->tax_field] : $this->tax_max,      // 21,
                'currency_id'           => $this->currency_id,

                'supplierSku'           => $supplierSku,
                'supplier_category_id'  => $supplier_category->id ?? null,
                'category_id'           => $supplier_category->category_id ?? null,
                'brand_id'              => $brand->id ?? null,
                'pn'                    => $pn,
                'ean'                   => $ean,
                'status_id'             => $status_id ?? null,
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function getCreateProductFields($supplier_product)
    {
        try {
            $name = $supplier_product[$this->supplier->name_field]['language'];
            $name = is_array($name) ? $name[0] : $name;
            $name = FacadesMpe::getString($name);

            $longdesc = $supplier_product[$this->supplier->long_field]['language'];
            if (isset($longdesc['@attributes']) || isset($longdesc[0]['@attributes'])) $longdesc = null;
            else {
                $longdesc = is_array($longdesc) ? $longdesc[0] : $longdesc;
                $longdesc = FacadesMpe::getText($longdesc);
            }

            if (isset($this->longdesc_extra)) {
                $longdesc_extra = $supplier_product[$this->longdesc_extra]['language'];
                if (isset($longdesc_extra['@attributes']) ||isset($longdesc_extra[0]['@attributes'])) $longdesc_extra = null;
                else {
                    $longdesc_extra = is_array($longdesc_extra) ? $longdesc_extra[0] : $longdesc_extra;
                    $longdesc_extra = FacadesMpe::getText($longdesc_extra);
                }
                if ($this->longdesc_type == 'html') $longdesc .= '<br><br>' .$longdesc_extra;
                else $longdesc .= '\n\n' .$longdesc_extra;
            }

            $shortdesc = $supplier_product[$this->supplier->short_field]['language'];
            if (isset($shortdesc['@attributes']) || isset($shortdesc[0]['@attributes'])) $shortdesc = null;
            else {
                $shortdesc = null;
                if ($this->supplier->short_field) {
                    $shortdesc = is_array($shortdesc) ? $shortdesc[0] : $shortdesc;
                    $shortdesc = FacadesMpe::getText($shortdesc);
                }
            }

            $keywords = $supplier_product[$this->supplier->keys_field]['language'];
            if (isset($keywords['@attributes']) || isset($keywords[0]['@attributes'])) $keywords = null;
            else {
                $keywords = null;
                if ($this->supplier->keys_field) {
                    $keywords = is_array($keywords) ? $keywords[0] : $keywords;
                    $keywords = FacadesMpe::getText($keywords);
                }
            }

            $upc = $supplier_product[$this->supplier->upc_field] ?? null;
            $upc = (is_array($upc)) ? null : $upc;
            $isbn = $supplier_product[$this->supplier->isbn_field] ?? null;
            $isbn = (is_array($isbn)) ? null : $isbn;

            return [
                'name'          => $name,
                'shortdesc'     => $shortdesc,
                'longdesc'      => $longdesc,
                'keywords'      => $keywords,

                'upc'           => $upc,
                'isbn'          => $isbn,
                'gtin'          => null,
                'model'         => $supplier_product[$this->supplier->model_field] ?? null,

                'weight'        => $supplier_product[$this->supplier->weight_field] ?? null,
                'length'        => $supplier_product[$this->supplier->length_field] ?? null,
                'width'         => $supplier_product[$this->supplier->width_field] ?? null,
                'height'        => $supplier_product[$this->supplier->height_field] ?? null,
                /* 'size'          => $supplier_product[$this->supplier->size_field] ?? null,
                'color'         => $supplier_product[$this->supplier->color_field] ?? null,
                'material'      => $supplier_product[$this->supplier->material_field] ?? null,
                'style'         => $supplier_product[$this->supplier->style_field] ?? null,
                'gender'        => $supplier_product[$this->supplier->gender_field] ?? null, */
            ];

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function createProductImages(Product $product, $supplier_product)
    {
        try {
            $res_img = null;
            $product_images = $this->getPrestaProductImages($supplier_product);

            if (isset($product_images) && !empty($product_images))
                foreach ($product_images as $image) {
                    $res_img = $product->updateOrCreateExternalImage(trim($image));
                }

            if (!isset($res_img))
                $product->getMPEProductImages();

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product, $supplier_product]);
        }
    }


    private function createProduct($supplier_product, array $update_fields)
    {
        try {
            if (!$supplier_product || !is_array($supplier_product))
                return $this->msgAndStorage(__METHOD__, 'Error de producto de proveedor.', [$supplier_product, $update_fields]);

            $create_fields = $this->getCreateProductFields($supplier_product);
            if (!isset($create_fields) || !is_array($create_fields))
                return $this->msgAndStorage(__METHOD__, 'Error: '.$create_fields, $supplier_product);

            $parent_id = null;
            if (isset($update_fields['parent_reference']) && $product = $this->supplier->products()->where('pn', $update_fields['parent_reference'])->first())
                $parent_id = $product->id;

            if ($product = $this->supplier->updateOrCreateProduct(
                $update_fields['pn'], $update_fields['ean'], $create_fields['upc'], $create_fields['isbn'], $create_fields['gtin'],
                $update_fields['supplierSku'], $update_fields['brand_id'], $update_fields['supplier_category_id'],
                $update_fields['category_id'], $update_fields['status_id'], $update_fields['currency_id'],
                $create_fields['name'], $create_fields['longdesc'], $update_fields['cost'], $update_fields['tax'], $update_fields['stock'],
                $create_fields['weight'], $create_fields['length'], $create_fields['width'], $create_fields['height'],
                $parent_id, null, null, $create_fields['model'], $create_fields['keywords'], $create_fields['shortdesc'],
                $update_fields['size'], $update_fields['color'], $update_fields['material'], $update_fields['style'], $update_fields['gender'])) {

                // IMAGES
                if (!isset($parent_id))
                    $this->createProductImages($product, $supplier_product);

                //$product->logPrice(true);

                return $product;
            }

            return $this->msgAndStorage(__METHOD__,
                'Error creando actualizando producto de proveedor ID: '.$supplier_product[$this->supplier->sku_src_field],
                [$supplier_product, $update_fields]);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function updateProduct(Product $product, $supplier_product, array $update_fields, $full_update_old_products = false)
    {
        try {
            if (!$supplier_product || !is_array($supplier_product))
                return $this->msgAndStorage(__METHOD__, 'Error de producto de proveedor.', [$product, $supplier_product, $full_update_old_products]);

            if ($product->cost != $update_fields['cost'] ||
                $product->stock != $update_fields['stock'] ||
                $product->supplier_category_id != $update_fields['supplier_category_id'] ||
                $product->category_id != $update_fields['category_id'] ||
                $product->status_id != $update_fields['status_id']) {

                $product->updateCostStock(
                    $update_fields['supplierSku'],
                    $update_fields['cost'],
                    $update_fields['tax'],
                    $update_fields['currency_id'],
                    $update_fields['stock'],
                    $update_fields['brand_id'],
                    $update_fields['supplier_category_id'],
                    $update_fields['category_id'],
                    $update_fields['status_id']
                );
            }

            // IMAGES
            if (!isset($parent_id) && $product->images->count() == 0) {
                //$product->deleteAllImages();
                $this->createProductImages($product, $supplier_product);
            }


            if ($full_update_old_products) {

                $create_fields = $this->getCreateProductFields($supplier_product);
                if (!isset($create_fields) || !is_array($create_fields))
                    return $this->msgAndStorage(__METHOD__, 'Error: '.$create_fields, [$product, $supplier_product, $full_update_old_products]);

                $parent_id = null;
                if (isset($update_fields['parent_reference']) && $product = $this->supplier->products()->where('pn', $update_fields['parent_reference'])->first())
                    $parent_id = $product->id;

                if ($product = $this->supplier->updateOrCreateProduct(
                    $update_fields['pn'], $update_fields['ean'], $create_fields['upc'], $create_fields['isbn'], $create_fields['gtin'],
                    $update_fields['supplierSku'], $update_fields['brand_id'], $update_fields['supplier_category_id'],
                    $update_fields['category_id'], $update_fields['status_id'], $update_fields['currency_id'],
                    $create_fields['name'], $create_fields['longdesc'], $update_fields['cost'], $update_fields['tax'], $update_fields['stock'],
                    $create_fields['weight'], $create_fields['length'], $create_fields['width'], $create_fields['height'],
                    $parent_id, null, null, $create_fields['model'], $create_fields['keywords'], $create_fields['shortdesc'],
                    $update_fields['size'], $update_fields['color'], $update_fields['material'], $update_fields['style'], $update_fields['gender'])) {

                    // IMAGES
                    if (!isset($parent_id)) {
                        $product->deleteAllImages();
                        $this->createProductImages($product, $supplier_product);
                    }
                }
                else
                    return $this->msgAndStorage(__METHOD__,
                        'Error creando actualizando producto ID: '.$product->id,
                        [$product, $supplier_product, $update_fields, $full_update_old_products]);
            }

            /* if ($product->wasChanged())
                $product->logPrice(false); */

            return $product;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$product, $supplier_product, $full_update_old_products]);
        }
    }


    private function updateOrCreateProduct($supplier_product, $update_fields, $create_new_products, $full_update_old_products)
    {
        try {
            $product = $this->supplier->products()->where('pn', $update_fields['pn'])->first();

            // Update cost & stock
            if (isset($product)) {

                if ($product->ready) {
                    $updated = $this->updateProduct($product, $supplier_product, $update_fields, $full_update_old_products);
                    if (isset($updated->id)) return $updated->id;
                }
            }
            // Create new product
            elseif ($create_new_products) {
                $created = $this->createProduct($supplier_product, $update_fields);
                if (isset($created->id)) return $created->id;
            }

            return $this->nullAndStorage(__METHOD__, [$product ?? null, $supplier_product, $update_fields, $create_new_products, $full_update_old_products]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product ?? null, $supplier_product, $update_fields, $create_new_products, $full_update_old_products]);
        }
    }


    private function updateOrCreateProductCombinations($supplier_product, $create_new_products, $full_update_old_products)
    {
        try {
            $processed_ids = [];
            // HACK: When XML -> JSON -> Collection EMPTY FIELDS are EMPTY ARRAYS [] NOT EMPTY STRINGS ''
            foreach($supplier_product as $key => $value)
                if ($value == []) $supplier_product[$key] = '';

            // Create Or Update Parent Products
            if ($update_fields = $this->getUpdateProductFields($supplier_product)) {
                if ($product_id = $this->updateOrCreateProduct($supplier_product, $update_fields, $create_new_products, $full_update_old_products))
                    $processed_ids[] = $product_id;
            }

            // Create Or Update Combination Products
            if ($this->has_product_options && isset($supplier_product['combinations'])) {
                foreach ($supplier_product['combinations'] as $combination) {

                    if ($update_fields = $this->getUpdateProductFields($supplier_product, $combination)) {
                        if ($product_id = $this->updateOrCreateProduct($supplier_product, $update_fields, $create_new_products, $full_update_old_products))
                            $processed_ids[] = $product_id;
                    }
                }
            }

            return $processed_ids;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$supplier_product, $update_fields, $create_new_products, $full_update_old_products]);
        }
    }


    private function updateOrCreateProducts(SupportCollection $productsCollect, $create_new_products = false, $full_update_old_products = false)
    {
        try {
            $processed_ids = [];
            $res = [];
            $res['starts_at'] = date('Y-m-d_H-i-s');
            if ($productsCollect->count()) {

                foreach ($productsCollect as $supplier_product) {

                    $partial_ids = $this->updateOrCreateProductCombinations($supplier_product, $create_new_products, $full_update_old_products);
                    if (isset($partial_ids) && count($partial_ids))
                        $processed_ids = array_merge($processed_ids, $partial_ids);
                        //$processed_ids = array_merge($processed_ids, $partial_ids);

                    // HACK: When XML -> JSON -> Collection EMPTY FIELDS are EMPTY ARRAYS [] NOT EMPTY STRINGS ''
                    /* foreach($supplier_product as $key => $value)
                        if ($value == []) $supplier_product[$key] = '';

                    // Create Or Update Parent Products
                    if ($update_fields = $this->getUpdateProductFields($supplier_product)) {
                        if ($product_id = $this->updateOrCreateProduct($supplier_product, $update_fields, $create_new_products, $full_update_old_products))
                            $processed_ids[] = $product_id;
                    }

                    // Create Or Update Combination Products
                    if ($this->has_product_options && isset($supplier_product['combinations'])) {
                        foreach ($supplier_product['combinations'] as $combination) {

                            if ($update_fields = $this->getUpdateProductFields($supplier_product, $combination)) {
                                if ($product_id = $this->updateOrCreateProduct($supplier_product, $update_fields, $create_new_products, $full_update_old_products))
                                    $processed_ids[] = $product_id;
                            }
                        }
                    } */
                }

                // Resets OLD NOT FOUND product stocks of current supplier
                if (count($processed_ids))
                    $this->supplier->products()->whereNotIn('id', $processed_ids)->update(['stock' => 0]);
            }

            $res['processeds'] = count($processed_ids);
            $res['ends_at'] = date('Y-m-d_H-i-s');

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$productsCollect, $create_new_products, $full_update_old_products]);
        }
    }


    /************** PUBLIC SUPPLIER FUNCTIONS */


    public function getProduct(Product $product)
    {
        /* if ($supplier_product = $this->getProductArray($product->supplierSku))
            if ($update_fields = $this->getUpdateProductFields($supplier_product))
                return $this->updateProduct($product, $supplier_product, $update_fields, false); */

        try {
            if ($productsCollect = $this->getProductsCollect($product->supplierSku)) {
                // HACK: When XML -> JSON -> Collection EMPTY FIELDS are EMPTY ARRAYS [] NOT EMPTY STRINGS ''
                $supplier_product = $productsCollect->first();

                $partial_ids = $this->updateOrCreateProductCombinations($supplier_product, false, true);

                return $product;
            }

            return 'Error obteniendo el producto.';

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function getProducts()
    {
        try {
            if ($productsCollect = $this->getProductsCollect()) {

                // FALTA CREAR UN CUSTOM FILTER PRODUCTS PER PRESTASHOP QUE FILTRI PRODUCTS AMB COMBINATIONS
                if ($productsCollect = $this->supplier->filterProducts($productsCollect)) {
                    return $this->updateOrCreateProducts($productsCollect, true, false);
                }
            }

            return 'Error obteniendo los productos.';

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function getPricesStocks()
    {
        try {
            if ($productsCollect = $this->getProductsCollect())
                return $this->updateOrCreateProducts($productsCollect, false, false);

            return 'Error actualizando los productos.';

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }




}
