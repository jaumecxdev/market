<?php

namespace App\Libraries;


use App\Brand;
use App\Product;
use App\Status;
use App\Supplier;
use App\SupplierCategory;
use App\SupplierParam;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\Mpe as FacadesMpe;
use Google_Client;
use Google_Service_Sheets;
use Illuminate\Support\Collection;
use Throwable;


/***************************
 *
 *
 *
 *
 *      LLIBRERIA OBSOLETA
 *      REVISAR DE DALT A BAIX ABANS D'UTILITZAR
 *
 *
 *
 *
 *
 */


class SupplierGoogleWS extends SupplierWS
{
    protected $currency_id = 1;             // EUR Euro

    private $client = null;

    protected $ranges;
    protected $ranges_update;
    protected $spreadsheetId;
    protected $parses;

    protected $subcategory = null;          // null | <<subcategory_name>>


    public function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
    }


    function getClient()
    {
        $client = new Google_Client();

        $config = [
            "type"              => "service_account",
            "project_id"        => "",
            "private_key_id"    => "",
            "private_key"       => "",
            "client_email"      => "",
            "client_id"         => "",
            "auth_uri"          => "https://accounts.google.com/o/oauth2/auth",
            "token_uri"         => "https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url"   => "https://www.googleapis.com/oauth2/v1/certs",
            "client_x509_cert_url"          => ""
        ];

        $client->setAuthConfig($config);
        $client->setApplicationName('MPeSpecialist');
        $client->setAccessType('offline');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);       // SPREADSHEETS, SPREADSHEETS_READONLY

        return $client;
    }


    /************** PRIVATE FUNCTIONS ***************/


    private function filterSupplierProducts(Collection $productsCollect, $only_prices_stocks = false)
    {
        $productsCollectResult = collect();
        $supplier_filters = $this->supplier->supplier_filters;
        $productsCollect = $productsCollect->where($this->supplier->stock_field, '>', 0);

        if (!$supplier_filters->count())
            return $productsCollect;

        $filter_fields = $this->supplier->getFilterFields();
        foreach ($supplier_filters as $supplier_filter) {

            $productsCollectFilter = $productsCollect;
            foreach ($filter_fields as $filter_field) {

                if ((!$only_prices_stocks || $only_prices_stocks == $filter_field['only_prices_stocks']) &&
                    $supplier_filter->{$filter_field['filter_name']} &&
                    isset($this->supplier->{$filter_field['field_name']})) {

                        $supplier_filter_name = $supplier_filter->{$filter_field['filter_name']};
                        if ($filter_field['field_name'] == 'category_id_field') $supplier_filter_name = intval($supplier_filter_name);
                        elseif ($filter_field['field_name'] == 'name_field') $supplier_filter_name = '%'.$supplier_filter_name.'%';

                        if ($filter_field['field_name'] == 'limit_products')
                            $productsCollectFilter = $productsCollectFilter->take($supplier_filter->limit_products);
                        elseif ($filter_field['filter_name'] == 'field_name')
                            $productsCollectFilter = $productsCollectFilter->where(
                                $supplier_filter_name,
                                $supplier_filter->field_operator,
                                $supplier_filter->field_string ?? $supplier_filter->field_integer ?? $supplier_filter->field_float
                        );
                        else
                            $productsCollectFilter = $productsCollectFilter->where(
                                $this->supplier->{$filter_field['field_name']},
                                $filter_field['operator'],
                                $supplier_filter_name
                            );
                    }
            }

            $productsCollectResult = $productsCollectResult->union($productsCollectFilter);
        }

        return $productsCollectResult;
    }


    private function getGoogleSheet()
    {
        $this->client = $this->getClient();
        $service = new Google_Service_Sheets($this->client);
        $optParams = [
            'ranges'            => $this->ranges,
            'majorDimension'    => 'ROWS'
        ];

        $response = $service->spreadsheets_values->batchGet($this->spreadsheetId, $optParams);
        $valueRanges = $response->getValueRanges();
        $supplierProducts = $valueRanges[0]->values;

        // remove first line (header)
        if ($this->parses['header']) unset($supplierProducts[0]);
        $productsCollect = collect($supplierProducts);

        return $productsCollect->keyBy($this->supplier->supplierSku_field);
    }


    private function getSupplierCategory($supplier_product)
    {
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

        return SupplierCategory::firstOrCreate([
            'supplier_id'       => $this->supplier->id,
            'supplierCategoryId'=> mb_substr($category_id, 0, 64),        // $supplier_product[$this->supplier->category_id_field] ?? $supplier_product[$this->supplier->category_field],
        ],[
            'name'              => mb_substr($category_name, 0, 255),      //$supplier_product[$this->supplier->category_field] ?? $supplier_product[$this->supplier->category_id_field],
        ]);
    }


    private function getBrand($supplier_product)
    {
        return Brand::firstOrCreate([
            'name' => $supplier_product[$this->supplier->brand_field]
        ], []);
    }


    private function getUpdateProductFields($supplier_product)
    {
        try {
            // supplier_params 042021
            $cost = FacadesMpe::roundFloat($supplier_product[$this->supplier->cost_field] ?? 0);
            $canon  = $supplier_product[$this->supplier->canon_field] ?? 0;
            $rappel = $supplier_product[$this->supplier->rappel_field] ?? 0;
            $ports  = $supplier_product[$this->supplier->ports_field] ?? 0;
            if ($canon || $rappel || $ports) {

                if (isset($this->supplier->category_id_field) || isset($this->supplier->category_field))
                    $supplier_category = $this->getSupplierCategory($supplier_product);

                if (isset($this->supplier->brand_field))
                    $brand = $this->getBrand($supplier_product);

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


            /* $supplier_params = $this->supplier->getSupplierProductParams($product->supplierSku, $product->brand_id, $product->category_id);
            $cost = FacadesMpe::getCost([
                    'cost'      => $supplier_product[$this->supplier->cost_field] ?? 0,
                    'canon'     => $supplier_product[$this->supplier->canon_field] ?? 0,
                    'rappel'    => $supplier_product[$this->supplier->rappel_field] ?? 0,
                    'ports'     => $supplier_product[$this->supplier->ports_field] ?? 0,
                ],
                $supplier_params
            ); */

            return [
                'cost'          => $cost,
                'tax'           => $supplier_product[$this->supplier->tax_field] ?? $this->tax_max,      // 21,
                'stock'         => $supplier_product[$this->supplier->stock_field] ?? 0,
                'currency_id'   => $supplier_product[$this->supplier->currency_field] ?? $this->currency_id,
            ];

        } catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getUpdateProductFields.json',
                json_encode([$th->getMessage(), $supplier_product]));

            return null;
        }
    }


    private function getCreateProductFields($supplier_product)
    {
        try {
            if (isset($this->supplier->category_id_field) || isset($this->supplier->category_field))
                $supplier_category = $this->getSupplierCategory($supplier_product);

            // If NO Mapping Category, return NULL & NO create Product
            if (!$supplier_category || !$supplier_category->category_id) {
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_NO_CATEGORY_MAPPING.json', json_encode($supplier_category));
                return $supplier_category->supplierCategoryId;
            }

            // BRAND
            if (isset($this->supplier->brand_field))
                $brand = $this->getBrand($supplier_product);

            // STATUS
            if (isset($supplier_product[$this->supplier->status_field]))
                $status = Status::firstOrCreate(
                    [
                        'name'  => $supplier_product[$this->supplier->status_field],
                        'type'  => 'product'
                    ],
                    []
                );

            // OTHER FIELDS
            $name = FacadesMpe::getString($supplier_product[$this->supplier->name_field]);
            $description = FacadesMpe::getText($supplier_product[$this->supplier->long_field]);
            $description_short = $this->supplier->short_field ? FacadesMpe::getText($supplier_product[$this->supplier->short_field]) : '';
            $keywords = $this->supplier->keys_field ? FacadesMpe::getText($supplier_product[$this->supplier->keys_field]) : '';

            return [
                'brand_id'      => $brand->id ?? null,
                'supplier_category_id'   => $supplier_category->id ?? null,
                'category_id'   => $supplier_category->category_id ?? null,
                'status_id'     => $status->id ?? 1,        // NEW
                'name'          => $name,
                'shortdesc'     => $description_short,
                'longdesc'      => $description,
                'keywords'      => $keywords,
                'parent_id'     => $supplier_product[$this->supplier->parent_field] ?? null,

                'supplierSku'   => $supplier_product[$this->supplier->supplierSku_field] ?? '',
                'pn'            => $supplier_product[$this->supplier->pn_field] ?? '',
                'ean'           => $supplier_product[$this->supplier->ean_field] ?? '',
                'upc'           => $supplier_product[$this->supplier->upc_field] ?? '',
                'isbn'          => $supplier_product[$this->supplier->isbn_field] ?? '',
                'gtin'          => null,
                'model'         => $supplier_product[$this->supplier->model_field] ?? '',

                'weight'        => $supplier_product[$this->supplier->weight_field] ?? '',
                'length'        => $supplier_product[$this->supplier->length_field] ?? '',
                'width'         => $supplier_product[$this->supplier->width_field] ?? '',
                'height'        => $supplier_product[$this->supplier->height_field] ?? '',
                'size'          => $supplier_product[$this->supplier->size_field] ?? '',
                'color'         => $supplier_product[$this->supplier->color_field] ?? '',
                'material'      => $supplier_product[$this->supplier->material_field] ?? '',
                'style'         => $supplier_product[$this->supplier->style_field] ?? '',
                'gender'        => $supplier_product[$this->supplier->gender_field] ?? '',
            ];

        } catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getCreateProductFields.json',
                json_encode([$th->getMessage(), $supplier_product]));

            return null;
        }
    }


    private function createProductImages(Product $product, $supplier_product)
    {
        try {
            $res_img = null;
            if (isset($this->supplier->sku_src_field) && $supplier_product[$this->supplier->sku_src_field] != '')
                $res_img = $product->updateOrCreateExternalImage($supplier_product[$this->supplier->sku_src_field], 0);

            if (isset($this->supplier->images_field) && $supplier_product[$this->supplier->images_field] != '') {
                $images = explode(',', $supplier_product[$this->supplier->images_field]);

                foreach ($images as $image) {
                    $res_img = $product->updateOrCreateExternalImage(trim($image));
                }
            }

            if (!isset($res_img))
                $product->getMPEProductImages();

        } catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_createProductImages.json',
                json_encode([$th->getMessage(), $supplier_product]));
        }
    }


    private function createProduct($supplier_product)
    {
        try {
            $create_fields = $this->getCreateProductFields($supplier_product);
            if (!isset($create_fields) || !is_array($create_fields)) return $create_fields;

            $product = Product::updateOrCreate(
                [
                    'supplier_id'   => $this->supplier->id,
                    'supplierSku'   => $create_fields['supplierSku'],
                    'pn'            => $create_fields['pn'],
                    'ean'           => $create_fields['ean'],
                    'upc'           => $create_fields['upc'],
                    'isbn'          => $create_fields['isbn'],
                    'gtin'          => $create_fields['gtin'],
                ],
                [
                    //'ready'         => 1,
                    'fix_text'      => 0,
                    'type_id'       => null,

                    'brand_id'      => $create_fields['brand_id'],
                    'supplier_category_id'   => $create_fields['supplier_category_id'],
                    'category_id'   => $create_fields['category_id'],
                    'status_id'     => $create_fields['status_id'],
                    'name'          => $create_fields['name'],
                    'shortdesc'     => $create_fields['shortdesc'],
                    'longdesc'      => $create_fields['longdesc'],
                    'keywords'      => $create_fields['keywords'],
                    'parent_id'     => $create_fields['parent_id'],

                    'model'         => $create_fields['model'],
                    'weight'        => $create_fields['weight'],
                    'length'        => $create_fields['length'],
                    'width'         => $create_fields['width'],
                    'height'        => $create_fields['height'],
                    'size'          => $create_fields['size'],
                    'color'         => $create_fields['color'],
                    'material'      => $create_fields['material'],
                    'style'         => $create_fields['style'],
                    'gender'        => $create_fields['gender'],
                ]
            );

            // PRICES
            $update_fields = $this->getUpdateProductFields($supplier_product);
            $product->update([
                'cost'          => $update_fields['cost'],
                'tax'           => $update_fields['tax'],
                'stock'         => $update_fields['stock'],
                'currency_id'   => $update_fields['currency_id'],
            ]);

            // IMAGES
            $this->createProductImages($product, $supplier_product);
            $product->logPrice(true);

            return $product;

        } catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d_H-i'). '_createProduct.json', json_encode([$th->getMessage(), $supplier_product]));
            return null;
        }
    }


    private function updateProduct(Product $product, $supplier_product, $full_update_old_products = false)
    {
        try {
            if (!$supplier_product) return null;

            $update_fields = $this->getUpdateProductFields($supplier_product);
            if ($product->cost != $update_fields['cost'] ||
                $product->stock != $update_fields['stock'])
                    $product->update([
                        'cost'          => $update_fields['cost'],
                        'tax'           => $update_fields['tax'],
                        'stock'         => $update_fields['stock'],
                        'currency_id'   => $update_fields['currency_id'],
                    ]);

            if ($full_update_old_products) {

                $create_fields = $this->getCreateProductFields($supplier_product);
                if (!isset($create_fields) || !is_array($create_fields)) return $create_fields;

                $product->update([
                    'brand_id'      => $create_fields['brand_id'],
                    'supplier_category_id'   => $create_fields['supplier_category_id'],
                    'category_id'   => $create_fields['category_id'],
                    'status_id'     => $create_fields['status_id'],
                    'name'          => $create_fields['name'],
                    'shortdesc'     => $create_fields['shortdesc'],
                    'longdesc'      => $create_fields['longdesc'],
                    'keywords'      => $create_fields['keywords'],

                    'supplierSku'   => $create_fields['supplierSku'],
                    'pn'            => $create_fields['pn'],
                    'ean'           => $create_fields['ean'],
                    'upc'           => $create_fields['upc'],
                    'isbn'          => $create_fields['isbn'],
                    'gtin'          => $create_fields['gtin'],
                    'model'         => $create_fields['model'],

                    'weight'        => $create_fields['weight'],
                    'length'        => $create_fields['length'],
                    'width'         => $create_fields['width'],
                    'height'        => $create_fields['height'],
                    'size'          => $create_fields['size'],
                    'color'         => $create_fields['color'],
                    'material'      => $create_fields['material'],
                    'style'         => $create_fields['style'],
                    'gender'        => $create_fields['gender'],
                ]);

                // IMAGES
                $product->deleteAllImages();
                $this->createProductImages($product, $supplier_product);
            }

            if ($product->wasChanged())
                $product->logPrice(false);

            return $product;

        } catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d_H-i'). '_updateProduct.json', json_encode([$th->getMessage(), $supplier_product]));
            return null;
        }
    }


    private function updateOrCreateProducts(Collection $productsCollect, $create_new_products = false, $full_update_old_products = false)
    {
        try {
            $processed_ids = [];
            $res = [
                'updateds'      => 0,
                'news'          => 0,
                'no_category'   => [],
            ];

            $res['starts_at'] = date('Y-m-d_H-i-s');
            if ($productsCollect->count()) {

                foreach ($productsCollect as $supplier_product) {

                    $product = $this->supplier->products()
                        ->where('supplierSku', $supplier_product[$this->supplier->supplierSku_field])
                        ->first();

                    // Update cost & stock
                    if (isset($product)) {

                        if ($product->ready) {
                            $updated = $this->updateProduct($product, $supplier_product, $full_update_old_products);
                            if (!isset($updated->id)) $res['no_category'][$updated] = 'No updated mapping';
                            else {
                                $res['updateds']++;
                                $processed_ids[] = $updated->id;
                            }
                        }
                    }
                    // Create new product
                    elseif ($create_new_products) {
                        $created = $this->createProduct($supplier_product);
                        if (!isset($created->id)) $res['no_category'][$created] = 'No created mapping';
                        else {
                            $res['news']++;
                            $processed_ids[] = $created->id;
                        }
                    }
                }

                // Resets OLD NOT FOUND product stocks of current supplier
                if (count($processed_ids))
                    $this->supplier->products()->whereNotIn('id', $processed_ids)->update(['stock' => 0]);
            }

            $res['ends_at'] = date('Y-m-d_H-i-s');
            return $res;

        } catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d_H-i'). '_updateOrCreateProducts.json', json_encode([$th->getMessage(), $supplier_product]));
            return $th->getMessage();
        }
    }



    /************** PUBLIC FUNCTIONS ***************/


    public function getProduct(Product $product)
    {
        $productsCollect = $this->filterSupplierProducts($this->getGoogleSheet());
        $supplier_product = $productsCollect->firstWhere($this->supplier->supplierSku_field, $product->supplierSku);

        return $this->updateProduct($product, $supplier_product, true);
    }


    public function getProducts()
    {
        $productsCollect = $this->filterSupplierProducts($this->getGoogleSheet());

        return $this->updateOrCreateProducts($productsCollect, true, false);
    }


    public function getPricesStocks()
    {
        $productsCollect = $this->filterSupplierProducts($this->getGoogleSheet(), true);

        return $this->updateOrCreateProducts($productsCollect, false, false);
    }


}
