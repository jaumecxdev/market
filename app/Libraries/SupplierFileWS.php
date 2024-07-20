<?php

namespace App\Libraries;


use App\Brand;
use App\Currency;
use App\Product;
use App\Status;
use App\Supplier;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\Mpe as FacadesMpe;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Throwable;

class SupplierFileWS extends SupplierWS
{
    use HelperTrait;

    protected $client = null;
    protected $parses = [];
    protected $currency_code = 'EUR';
    protected $currency_id = 1;             // EUR Euro

    protected $file_type = null;            // xml, json, csv
    protected $file_cdata = false;          // true | false
    protected $file_url = null;             // https://www...
    protected $file_child = null;           // null | <<child_name>>
    protected $file_name = null;            // null | <<file_name>>
    protected $file_disk = null;
    protected $file_delimiter = null;       // ',' | ';' | "\t"         If $file_type == csv
    protected $header_rows = null;

    protected $images_type = null;          // array | string
    protected $images_child = null;         // null | <<images_child_name>>
    protected $images_delimiter = null;     // ',' | ';' | "\t"         If $images_type == string

    protected $subcategory = null;          // null | <<subcategory_name>>

    protected $longdesc_type = null;        // 'html' | text
    protected $longdesc_extra = null;

    protected $status_id = null;
    protected $statuses = [
        'Nuevo'             => 1,
        'Usado'             => 2,
        'Remanufacturado'   => 3,
    ];


    public function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
    }



    /************** PRIVATE FUNCTIONS ***************/


    private function parse($supplier_product)
    {
        try {
            // Util form XML docs. [] -> ''
            foreach($supplier_product as $key => $field_value)
                if (is_array($field_value) && $field_value == []) $supplier_product[$key] = '';

            // Mandatory modifications: name, longdesc, shortdesc, keywords
            $supplier_product[$this->supplier->name_field] = FacadesMpe::getString($supplier_product[$this->supplier->name_field]);
            $supplier_product[$this->supplier->long_field] = FacadesMpe::getText($supplier_product[$this->supplier->long_field]);
            if (isset($this->longdesc_extra)) {
                $longdesc_extra = FacadesMpe::getText($supplier_product[$this->longdesc_extra]);
                if ($this->longdesc_type == 'html') $supplier_product[$this->supplier->long_field] .= '<br><br>' .$longdesc_extra;
                else $supplier_product[$this->supplier->long_field] .= '\n\n' .$longdesc_extra;
            }

            if ($this->supplier->short_field)
                $supplier_product[$this->supplier->short_field] = FacadesMpe::getText($supplier_product[$this->supplier->short_field]);
            if ($this->supplier->keys_field)
                $supplier_product[$this->supplier->keys_field] = FacadesMpe::getText($supplier_product[$this->supplier->keys_field]);

            // Only Parse modifications
            if (!isset($this->parses['fields'])) return $supplier_product;
            foreach ($this->parses['fields'] as $key => $field) {

                if ($field['function'] == 'substr') {
                    $supplier_product[$field['name']] = substr($supplier_product[$field['name']], 0, strpos($supplier_product[$field['name']], $field['param']));
                }
                elseif ($field['function'] == 'fixed') {
                    $supplier_product[$field['name']] = $field['param'];
                }
                elseif ($field['function'] == 'change' && $supplier_product[$field['name']] == $field['value']) {
                    $supplier_product[$field['name']] = $field['param'];
                }
                elseif ($field['function'] == 'explode' && isset($supplier_product[$field['name']])) {
                    if (trim($supplier_product[$field['name']]) == '')
                        $supplier_product[$field['name']] = null;
                    else
                        $supplier_product[$field['name']] = array_map('trim', explode($field['param'], $supplier_product[$field['name']]));
                }
            }

            return $supplier_product;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function getProductsCollect()
    {
        try {
            $contents = null;
            if ($this->file_name) {
                $contents = Storage::get($this->storage_dir.$this->file_name);
            }
            else {
                $this->client = new Client();
                $response = $this->client->get($this->file_url, [
                    'headers' => [
                        'Content-Type'  => 'text/xml',
                    ],
                ]);

                if ($response->getStatusCode() == '200')
                    $contents = $response->getBody()->getContents();
            }

            if (isset($contents)) {
                Storage::put($this->storage_dir. 'products/' .date('Y-m-d_H'). '.xml', $contents);

                if ($this->file_type == 'xml') {
                    if ($this->file_cdata)
                        $contents = preg_replace('~\s*(<([^-->]*)>[^<]*<!--\2-->|<[^>]*>)\s*~','$1', $contents);
                    $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA);
                    $json = json_encode($xml);
                }
                elseif ($this->file_type == 'json') {
                    $json = json_encode($contents);
                }
                elseif (in_array($this->file_type, ['xlsx', 'csv'])) {

                    $inputFileName = storage_path('app/'.$this->storage_dir.$this->file_name);


                    /* $inputFileType = IOFactory::identify($inputFileName);   // 'Xlsx'
                    $reader = IOFactory::createReader($inputFileType);
                    $spreadsheet = $reader->load($inputFileName);
                    $sheet = $spreadsheet->getSheet(0);

                    $array = $sheet->toArray(null, true, true, true);
                    if (isset($this->header_rows) && count($array)) {
                        for($i=0; $i<$this->header_rows; $i++)
                            array_shift($array);
                    } */

                    $array = FacadesMpeImport::getFileRows($inputFileName, $this->header_rows ?? 0);
                }

                if (isset($json)) $array = json_decode($json, TRUE);
                $productsCollect = (isset($this->file_child)) ? collect($array[(string)$this->file_child]) : collect($array);

                return $productsCollect
                    ->keyBy((string)$this->supplier->supplierSku_field)
                    ->transform(function ($supplier_product, $key) {
                        return $this->parse($supplier_product);
                    });
            }
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$response ?? null, $this->supplier]);
        }
    }


    /* private function filterSupplierProducts(Collection $productsCollect, $only_prices_stocks = false)
    {
        try {
            $productsCollectResult = collect();
            $supplier_filters = $this->supplier->supplier_filters;
            $productsCollect = $productsCollect->where($this->supplier->stock_field, '>', 0);

            if (!$supplier_filters->count())
                return $this->nullAndStorage(__METHOD__, [$productsCollect, $only_prices_stocks]);

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

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$productsCollect, $only_prices_stocks]);
        }
    } */


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
            return Brand::firstOrCreate([
                'name' => $supplier_product[$this->supplier->brand_field]
            ], []);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function getUpdateProductFields($supplier_product)
    {
        try {
            // BRAND
            if (isset($this->supplier->brand_field))
                $brand = $this->getBrand($supplier_product);

            // STATUS
            if (isset($this->status_id))
                $status_id = $this->status_id;
            elseif (isset($supplier_product[$this->supplier->status_field])) {
                if (isset($this->statuses[$supplier_product[$this->supplier->status_field]]))
                    $status_id = $this->statuses[$supplier_product[$this->supplier->status_field]];
                else {
                    $status = Status::firstWhere('name', ucfirst(strtolower($supplier_product[$this->supplier->status_field])));
                    $status_id = (isset($status)) ? $status->id : 1;     // Nuevo
                }
            }

            // CATEGORY
            if (isset($this->supplier->category_id_field) || isset($this->supplier->category_field)) {
                $supplier_category = $this->getSupplierCategory($supplier_product);
                if (!isset($supplier_category))
                    return $this->nullAndStorage(__METHOD__, ['No supplier_category', $supplier_product]);
                elseif (in_array($supplier_category->supplierCategoryId, $this->rejected_categories))
                    return null;
            }

            // CURRENCY
            if (isset($supplier_product[$this->supplier->currency_field]))
                $currency = Currency::firstOrCreate([
                    'code'  => $supplier_product[$this->supplier->currency_field],
                ],[]);

            // CANON
            $canon  = isset($this->supplier->canon_field) ? $supplier_product[$this->supplier->canon_field] : 0;

            // SUPPLIER PARAMS
            $cost = isset($this->supplier->cost_field) ? FacadesMpe::roundFloat($supplier_product[$this->supplier->cost_field]) : 0;
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

            [$pn, $ean] = FacadesMpe::getPnEan(
                $supplier_product[$this->supplier->pn_field] ?? null,
                $supplier_product[$this->supplier->ean_field] ?? null
            );

            return [
                'cost'                  => $cost,
                'tax'                   => isset($this->supplier->tax_field) ? $supplier_product[$this->supplier->tax_field] : $this->tax_max,      // 21,
                'stock'                 => isset($this->supplier->stock_field) ? ($supplier_product[$this->supplier->stock_field] ?? 0) : 0,
                'currency_id'           => $currency->id ?? 1,
                'supplierSku'           => $supplier_product[$this->supplier->supplierSku_field] ?? null,
                'supplier_category_id'  => $supplier_category->id ?? null,
                'category_id'           => $supplier_category->category_id ?? null,

                'brand_id'              => $brand->id ?? null,
                'pn'                    => $pn,
                'ean'                   => $ean,
                'status_id'             => $status_id ?? null,
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$supplier_product, $this->supplier]);
        }
    }


    private function getCreateProductFields($supplier_product)
    {
        try {
            if (isset($this->supplier->name_field))
                $name = FacadesMpe::getString($supplier_product[$this->supplier->name_field]);

            if (isset($supplier_product[$this->supplier->long_field]))
                $longdesc = FacadesMpe::getText($supplier_product[$this->supplier->long_field]);

            if (isset($supplier_product[$this->supplier->short_field]))
                $shortdesc = FacadesMpe::getText($supplier_product[$this->supplier->short_field]);

            if (isset($supplier_product[$this->supplier->keys_field]))
                $keywords = FacadesMpe::getText($supplier_product[$this->supplier->keys_field]);

            if (isset($supplier_product[$this->supplier->color_field]))
                $color = is_array($supplier_product[$this->supplier->color_field]) ?
                implode(',', $supplier_product[$this->supplier->color_field]) : $supplier_product[$this->supplier->color_field];

            return [
                'name'          => $name ?? '',
                'shortdesc'     => $shortdesc ?? null,
                'longdesc'      => $longdesc ?? null,
                'keywords'      => $keywords ?? null,
                'parent_id'     => $supplier_product[$this->supplier->parent_field] ?? null,

                'upc'           => $supplier_product[$this->supplier->upc_field] ?? null,
                'isbn'          => $supplier_product[$this->supplier->isbn_field] ?? null,
                'gtin'          => null,
                'model'         => $supplier_product[$this->supplier->model_field] ?? null,

                'weight'        => $supplier_product[$this->supplier->weight_field] ?? null,
                'length'        => $supplier_product[$this->supplier->length_field] ?? null,
                'width'         => $supplier_product[$this->supplier->width_field] ?? null,
                'height'        => $supplier_product[$this->supplier->height_field] ?? null,
                'size'          => $supplier_product[$this->supplier->size_field] ?? null,
                'color'         => $supplier_product[$this->supplier->color_field] ?? null,
                'material'      => $supplier_product[$this->supplier->material_field] ?? null,
                'style'         => $supplier_product[$this->supplier->style_field] ?? null,
                'gender'        => $supplier_product[$this->supplier->gender_field] ?? null,
            ];

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function createProductImages(Product $product, $supplier_product)
    {
        try {
            $res_img = null;
            if (isset($this->supplier->sku_src_field) && $supplier_product[$this->supplier->sku_src_field] != '')
                $res_img = $product->updateOrCreateExternalImage($supplier_product[$this->supplier->sku_src_field], 0);

            if (isset($this->supplier->images_field) && $supplier_product[$this->supplier->images_field] != '') {

                // string
                if ($this->images_type == 'string')
                    $images = explode(',', $supplier_product[$this->supplier->images_field]);
                // array
                else
                    $images = isset($this->images_child) ?
                        $supplier_product[$this->supplier->images_field][$this->images_child] :
                        $supplier_product[$this->supplier->images_field];

                if (is_string($images)) $images = [$images];

                if ($images)
                    foreach ($images as $image)
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
                return $this->msgAndStorage(__METHOD__, 'Error de producto de proveedor.', ['Error de producto de proveedor.', $this->supplier->code, $supplier_product, $update_fields]);

            $create_fields = $this->getCreateProductFields($supplier_product);
            if (!isset($create_fields) || !is_array($create_fields))
                return $this->msgAndStorage(__METHOD__, 'Error: '.$create_fields, ['Error', $this->supplier->code, $supplier_product, $create_fields]);

            // Fields are Arrays -> String
            foreach ($update_fields as $key => $update_field)
                if (is_array($update_fields[$key])) $update_fields[$key] = array_values($update_fields[$key])[0];

            foreach ($create_fields as $key => $update_field)
                if (is_array($create_fields[$key])) $create_fields[$key] = array_values($create_fields[$key])[0];

            if ($product = $this->supplier->updateOrCreateProduct(
                $update_fields['pn'], $update_fields['ean'], $create_fields['upc'], $create_fields['isbn'], $create_fields['gtin'],
                $update_fields['supplierSku'], $update_fields['brand_id'], $update_fields['supplier_category_id'],
                $update_fields['category_id'], $update_fields['status_id'], $update_fields['currency_id'],
                $create_fields['name'], $create_fields['longdesc'], $update_fields['cost'], $update_fields['tax'], $update_fields['stock'],
                $create_fields['weight'], $create_fields['length'], $create_fields['width'], $create_fields['height'],
                $create_fields['parent_id'], null, null, $create_fields['model'], $create_fields['keywords'], $create_fields['shortdesc'],
                $create_fields['size'], $create_fields['color'], $create_fields['material'], $create_fields['style'], $create_fields['gender'])) {

                // IMAGES
                $this->createProductImages($product, $supplier_product);
                $product->logPrice(true);

                // model, size, color, material, style, gender, weight, length, width, height
                if (isset($this->parses['variants']) && !empty($this->parses['variants'])) {
                    foreach ($this->parses['variants'] as $variant_field) {
                        if (isset($create_fields[$variant_field])) {
                            foreach ($create_fields[$variant_field] as $variant_field_value) {
                                $variant_product = Product::updateOrCreate(
                                    [
                                        'supplier_id'   => $this->supplier->id,
                                        'parent_id'     => $product->id,
                                        $variant_field  => $variant_field_value
                                    ],
                                    [
                                        //'ready'         => 1,
                                        'cost'          => $update_fields['cost'],
                                        'tax'           => $update_fields['tax'],
                                        'stock'         => $update_fields['stock'],
                                        'currency_id'   => $update_fields['currency_id'],
                                    ]
                                );
                            }
                        }
                    }
                }

                return $product;
            }

            return $this->msgAndStorage(__METHOD__,
                'Error creando actualizando producto de proveedor ID: '.$supplier_product[$this->supplier->supplierSku_field] ?? null,
                ['Error creando actualizando producto de proveedor', $this->supplier->code, $supplier_product, $update_fields]);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$supplier_product, $update_fields, $this]);
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
                $product->status_id != $update_fields['status_id'])

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

            if ($full_update_old_products) {

                $create_fields = $this->getCreateProductFields($supplier_product);
                if (!isset($create_fields) || !is_array($create_fields))
                    return $this->msgAndStorage(__METHOD__, 'Error: '.$create_fields, [$product, $supplier_product, $full_update_old_products]);

                // Fields are Arrays -> String
                foreach ($update_fields as $key => $update_field)
                    if (is_array($update_fields[$key])) $update_fields[$key] = array_values($update_fields[$key])[0];

                foreach ($create_fields as $key => $update_field)
                    if (is_array($create_fields[$key])) $create_fields[$key] = array_values($create_fields[$key])[0];

                if ($product = $this->supplier->updateOrCreateProduct(
                    $update_fields['pn'], $update_fields['ean'], $create_fields['upc'], $create_fields['isbn'], $create_fields['gtin'],
                    $update_fields['supplierSku'], $update_fields['brand_id'], $update_fields['supplier_category_id'],
                    $update_fields['category_id'], $update_fields['status_id'], $update_fields['currency_id'],
                    $create_fields['name'], $create_fields['longdesc'], $update_fields['cost'], $update_fields['tax'], $update_fields['stock'],
                    $create_fields['weight'], $create_fields['length'], $create_fields['width'], $create_fields['height'],
                    $create_fields['parent_id'], null, null, $create_fields['model'], $create_fields['keywords'], $create_fields['shortdesc'],
                    $create_fields['size'], $create_fields['color'], $create_fields['material'], $create_fields['style'], $create_fields['gender'])) {


                    // IMAGES
                    $product->deleteAllImages();
                    $this->createProductImages($product, $supplier_product);

                    // model, size, color, material, style, gender, weight, length, width, height
                    if (isset($this->parses['variants'])) {
                        foreach ($this->parses['variants'] as $variant_field) {
                            if (isset($create_fields[$variant_field])) {
                                foreach ($create_fields[$variant_field] as $variant_field_value) {
                                    $variant_product = Product::updateOrCreate(
                                        [
                                            'supplier_id'   => $this->supplier->id,
                                            'parent_id'     => $product->id,
                                            $variant_field  => $variant_field_value
                                        ],
                                        [
                                            //'ready'         => 1,
                                            'cost'          => $update_fields['cost'],
                                            'tax'           => $update_fields['tax'],
                                            'stock'         => $update_fields['stock'],
                                            'currency_id'   => $update_fields['currency_id'],
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }
                else
                    return $this->msgAndStorage(__METHOD__,
                        'Error creando actualizando producto ID: '.$product->id,
                        [$product, $supplier_product, $update_fields, $full_update_old_products]);
                }

            if ($product->wasChanged())
                $product->logPrice(false);

            return $product;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$product, $supplier_product, $full_update_old_products]);
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

                    if ($update_fields = $this->getUpdateProductFields($supplier_product)) {

                        $product = $this->supplier->getSimilarProduct($update_fields['status_id'],
                            $update_fields['brand_id'],
                            $update_fields['pn'],
                            $update_fields['ean']);

                        // Update cost & stock
                        if (isset($product)) {

                            if ($product->ready) {
                                $updated = $this->updateProduct($product, $supplier_product, $update_fields, $full_update_old_products);
                                if (!isset($updated->id)) $res['no_updated'][] = $updated;
                                else {
                                    $res['updateds']++;
                                    $processed_ids[] = $updated->id;
                                }
                            }
                        }
                        // Create new product
                        elseif ($create_new_products) {
                            $created = $this->createProduct($supplier_product, $update_fields);
                            if (!isset($created->id)) $res['no_created'][] = $created;
                            else {
                                $res['news']++;
                                $processed_ids[] = $created->id;
                            }
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
            return $this->msgWithErrors($th, __METHOD__, [$productsCollect, $create_new_products, $full_update_old_products]);
        }
    }



    /************** PUBLIC FUNCTIONS ***************/


    public function getProduct(Product $product)
    {
        if ($productsCollect = $this->getProductsCollect()) {
            if ($supplier_product = $productsCollect->firstWhere($this->supplier->ean_field, $product->ean) ??
                $productsCollect->where($this->supplier->brand_field, $product->brand_name)->where($this->supplier->pn_field, $product->pn) ??
                $productsCollect->firstWhere($this->supplier->supplierSku_field, $product->supplierSku)) {

                    if ($update_fields = $this->getUpdateProductFields($supplier_product))
                        return $this->updateProduct($product, $supplier_product, $update_fields, false);
                }
        }

        return 'Error obteniendo el producto.';
    }


    public function getProducts()
    {
        if ($productsCollect = $this->supplier->filterProducts($this->getProductsCollect()))
            return $this->updateOrCreateProducts($productsCollect, true, false);

        return 'Error obteniendo los productos.';
    }


    public function getPricesStocks()
    {
        if ($productsCollect = $this->getProductsCollect())
            return $this->updateOrCreateProducts($productsCollect, false, false);

        return 'Error actualizando los productos.';
    }


}
