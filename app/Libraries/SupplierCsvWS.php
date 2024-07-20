<?php

namespace App\Libraries;

use App\Brand;
use App\Currency;
use App\Product;
use App\Status;
use App\Supplier;
use App\SupplierCategory;
use App\Type;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\Mpe as FacadesMpe;
use Throwable;


/***************************
 *
 *
 *
 *
 *      LLIBRERIA OBSOLETA
 *      REVISAR DE DALT A BAIX ABANS D'UTILITZAR
 *
 *      PER IMPORTAR CSV | XLSX -> SUPPLIERFILEWS o LLIBRERIES IMPORT
 *
 *
 *
 *
 */


class SupplierCsvWS extends SupplierWS
{
    protected $url_csv = null;
    protected $disk = null;
    protected $filename = null;
    protected $delimiter = ';';

    protected $images_import_type = null;
    protected $images_folder = null;
    protected $images_folder_field = null;

    protected $conditions = null;
    //protected $category_supplier_id = null;
    //protected $getImageByEan = null;


    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
    }



    /************** FAKE DESCENDENT FUNCTIONS                       ***************/
    /************** IMPLEMENTED IN DESCENDENT CLASS: IOUTLETWEBWS   ***************/


    /************** PRIVATE FUNCTIONS ***************/


    private function getCsvFile()
    {
        try {
            if ($this->supplier->type_import == 'file') {
                $client = new Client();
                $response = $client->request('GET', $this->url_csv .$this->filename);
                // $res->getHeader('content-type')[0]; // 'text/csv'
                if ($response->getStatusCode() == '200') {
                    $contents = $response->getBody()->getContents();
                    Storage::put($this->storage_dir. $this->filename, $contents);
                    return true;
                }
            }
            elseif ($this->supplier->type_import == 'ftp') {
                $contents = Storage::disk($this->disk)->get($this->filename);
                Storage::put($this->storage_dir. $this->filename, $contents);
                return true;
            }

        } catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getCsvFile.json',
                json_encode([$th->getMessage(), $this->supplier->toArray()]));
        }

        return false;
    }


    private function getCsv()
    {
        return (storage_path('app/' .$this->storage_dir . $this->filename));
    }


    private function getFieldConditioneds($row, $field_conditions)
    {
        $fields = [
            'primary_key'       => $this->supplier->primary_key_field != null ? $row[$this->supplier->primary_key_field] : null,

            'parent'            => $this->supplier->parent_field != null ? $row[$this->supplier->parent_field] : null,
            'brand'             => $this->supplier->brand_field != null ? $row[$this->supplier->brand_field] : null,
            'category'          => $this->supplier->category_field != null ? $row[$this->supplier->category_field] : null,
            'category_id'       => $this->supplier->category_id_field != null ? $row[$this->supplier->category_id_field] : null,
            'type'              => $this->supplier->type_field != null ? $row[$this->supplier->type_field] : null,
            'status'            => $this->supplier->status_field != null ? $row[$this->supplier->status_field] : null,
            'currency'          => $this->supplier->currency_field != null ? $row[$this->supplier->currency_field] : null,

            'ready'             => $this->supplier->ready_field != null ? $row[$this->supplier->ready_field] : 1,
            'name'              => $this->supplier->name_field != null ? FacadesMpe::getString($row[$this->supplier->name_field]) : null,
            'keywords'          => $this->supplier->keys_field != null ? FacadesMpe::getString($row[$this->supplier->keys_field]) : null,
            'pn'                => $this->supplier->pn_field != null ? $row[$this->supplier->pn_field] : null,
            'ean'               => $this->supplier->ean_field != null ? $row[$this->supplier->ean_field] : null,
            'upc'               => $this->supplier->upc_field != null ? $row[$this->supplier->upc_field] : null,
            'isbn'              => $this->supplier->isbn_field != null ? $row[$this->supplier->isbn_field] : null,
            'shortdesc'         => $this->supplier->short_field != null ? FacadesMpe::getText($row[$this->supplier->short_field]) : null,
            'longdesc'          => $this->supplier->long_field != null ? FacadesMpe::getText($row[$this->supplier->long_field]) : null,

            'supplierSku'       => $this->supplier->supplierSku_field != null ? strval($row[$this->supplier->supplierSku_field]) : null,
            'model'             => $this->supplier->model_field != null ? $row[$this->supplier->model_field] : null,
            'cost'              => $this->supplier->cost_field != null ? FacadesMpe::roundFloatEsToEn($row[$this->supplier->cost_field]) : 0,
            'canon'             => $this->supplier->canon_field != null ? FacadesMpe::roundFloatEsToEn($row[$this->supplier->canon_field]) : 0,
            'rappel'            => $this->supplier->rappel_field != null ? FacadesMpe::roundFloatEsToEn($row[$this->supplier->rappel_field]) : 0,
            'ports'             => $this->supplier->ports_field != null ? FacadesMpe::roundFloatEsToEn($row[$this->supplier->ports_field]) : 0,
            'tax'               => $this->supplier->tax_field != null ? FacadesMpe::roundFloatEsToEn($row[$this->supplier->tax_field]) : null,
            'stock'             => $this->supplier->stock_field != null ? intval($row[$this->supplier->stock_field]) : 0,
            'size'              => $this->supplier->size_field != null ? $row[$this->supplier->size_field] : null,
            'color'             => $this->supplier->color_field != null ? $row[$this->supplier->color_field] : null,
            'material'          => $this->supplier->material_field != null ? $row[$this->supplier->material_field] : null,
            'style'             => $this->supplier->style_field != null ? $row[$this->supplier->style_field] : null,
            'gender'            => $this->supplier->gender_field != null ? $row[$this->supplier->gender_field] : null,
            'sku_src'           => $this->supplier->sku_src_field != null ? $row[$this->supplier->sku_src_field] : null,

            'images'            => ($this->supplier->images_field != null && $row[$this->supplier->images_field] != '') ?
                explode(',', $row[$this->supplier->images_field]) : null,
            'extra'             => $this->supplier->extra_field != null ? $row[$this->supplier->extra_field] : null,
        ];

        // Field Conditions
        foreach ($field_conditions as $field_condition) {
            if ($field_condition['function'] == 'substr') {
                $fields[$field_condition['name']] = substr($fields[$field_condition['name']],0,
                    strpos($fields[$field_condition['name']], $field_condition['param']));
            }
            elseif ($field_condition['function'] == 'fixed') {
                $fields[$field_condition['name']] = $field_condition['param'];
            }
            elseif ($field_condition['function'] == 'change') {
                if ($fields[$field_condition['name']] == $field_condition['value'])
                    $fields[$field_condition['name']] = $field_condition['param'];
            }
        }

        return $fields;
    }


    private function getFilters($supplier_filter)
    {
        $filters = [];
        if ($supplier_filter->brand_name && $this->supplier->brand_field != null) {
            $filters[] = [
                'field'         => 'brand',
                'operator'      => '==',
                'value'         => $supplier_filter->brand_name,
            ];
        }
        if ($supplier_filter->category_name) {
            if ($this->supplier->category_field != null) {
                    $filters[] = [
                    'field'         => 'category',
                    'operator'      => '==',
                    'value'         => $supplier_filter->category_name,
                ];
            }
            elseif ($this->supplier->category_id_field != null) {
                    $filters[] = [
                    'field'         => 'category_id',
                    'operator'      => '==',
                    'value'         => $supplier_filter->category_name,
                ];
            }
        }

        if ($supplier_filter->type_name && $this->supplier->type_field != null) {
            $filters[] = [
                'field'         => 'type',
                'operator'      => '==',
                'value'         => $supplier_filter->type_name,
            ];
        }
        if ($supplier_filter->status_name && $this->supplier->status_field != null) {
            $filters[] = [
                'field'         => 'status',
                'operator'      => '==',
                'value'         => $supplier_filter->status_name,
            ];
        }
        if ($supplier_filter->name && $this->supplier->name_field != null) {
            $filters[] = [
                'field'         => 'name',
                'operator'      => 'like',
                'value'         => $supplier_filter->name,
            ];
        }
        if ($supplier_filter->model && $this->supplier->model_field != null) {
            $filters[] = [
                'field'         => 'model',
                'operator'      => '==',
                'value'         => $supplier_filter->model,
            ];
        }
        if ($supplier_filter->supplierSku && $this->supplier->supplierSku_field != null) {
            $filters[] = [
                'field'         => 'supplierSku',
                'operator'      => '==',
                'value'         => $supplier_filter->supplierSku,
            ];
        }
        if ($supplier_filter->pn && $this->supplier->pn_field != null) {
            $filters[] = [
                'field'         => 'pn',
                'operator'      => '==',
                'value'         => $supplier_filter->pn,
            ];
        }
        if ($supplier_filter->ean && $this->supplier->ean_field != null) {
            $filters[] = [
                'field'         => 'ean',
                'operator'      => '==',
                'value'         => $supplier_filter->ean,
            ];
        }
        if ($supplier_filter->upc && $this->supplier->upc_field != null) {
            $filters[] = [
                'field'         => 'upc',
                'operator'      => '==',
                'value'         => $supplier_filter->upc,
            ];
        }
        if ($supplier_filter->isbn && $this->supplier->isbn_field != null) {
            $filters[] = [
                'field'         => 'isbn',
                'operator'      => '==',
                'value'         => $supplier_filter->isbn,
            ];
        }
        if ($supplier_filter->cost_min && $this->supplier->cost_field != null) {
            $filters[] = [
                'field'         => 'cost',
                'operator'      => '>=',
                'value'         => $supplier_filter->cost_min,
            ];
        }
        if ($supplier_filter->cost_max && $this->supplier->cost_field != null) {
            $filters[] = [
                'field'         => 'cost',
                'operator'      => '<=',
                'value'         => $supplier_filter->cost_max,
            ];
        }
        if ($supplier_filter->stock_min && $this->supplier->stock_field != null) {
            $filters[] = [
                'field'         => 'stock',
                'operator'      => '>=',
                'value'         => $supplier_filter->stock_min,
            ];
        }
        if ($supplier_filter->stock_max && $this->supplier->stock_field != null) {
            $filters[] = [
                'field'         => 'stock',
                'operator'      => '<=',
                'value'         => $supplier_filter->stock_max,
            ];
        }
        if ($supplier_filter->field_string && $supplier_filter->field_name != null) {
            $filters[] = [
                'field'         => 'extra',
                'operator'      => '==',
                'value'         => $supplier_filter->field_string,
            ];
        }
        if ($supplier_filter->field_integer && $supplier_filter->field_name != null) {
            $filters[] = [
                'field'         => 'extra',
                'operator'      => '==',
                'value'         => $supplier_filter->field_integer,
            ];
        }
        if ($supplier_filter->field_float && $supplier_filter->field_name != null) {
            $filters[] = [
                'field'         => 'extra',
                'operator'      => '==',
                'value'         => $supplier_filter->field_float,
            ];
        }
        if ($supplier_filter->limit_products) {
            $filters[] = ['limit' => $supplier_filter->limit_products];
        }

        return $filters;
    }


    private function filterAllow(&$count, $filters, $fields)
    {
        $operationAllowed = true;
        foreach ($filters as $filter) {
            if (isset($filter['limit'])) $operationAllowed = ($filter['limit'] < $count);
            elseif ($filter['operator'] == '==') $operationAllowed = ($fields[$filter['field']] == $filter['value']);
            elseif ($filter['operator'] == '>=') $operationAllowed = ($fields[$filter['field']] >= $filter['value']);
            elseif ($filter['operator'] == '<=') $operationAllowed = ($fields[$filter['field']] <= $filter['value']);
            // like
            else $operationAllowed = (strstr($fields[$filter['field']], $filter['value']));

            if (!$operationAllowed) break;
        }

        return $operationAllowed;
    }


    private function createProduct($fields)
    {
        // If product don't have category
        if (!$fields['category'] && !$fields['category_id']) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_NO_PRODUCT_CATEGORY.json', json_encode($fields));
            return null;
        }

        $product = null;
        try {

            $supplier_category = SupplierCategory::firstOrCreate([
                'supplier_id'       => $this->category_supplier_id ?? $this->supplier->id,
                'name'              => $fields['category'] ?? $fields['category_id'],
                'supplierCategoryId'=> $fields['category_id'] ?? null,
            ],[]);

            // If NO Mapping Category, return NULL & NO create Product
            if (!$supplier_category->category_id) {
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_NO_CATEGORY_MAPPING.json', json_encode($supplier_category));
                return null;
            }

            if ($fields['parent'])
                $parent = Product::firstOrCreate([
                    'name' => $fields['parent']
                ], []);

            if ($fields['brand'])
                $brand = Brand::firstOrCreate([
                    'name' => $fields['brand']
                ], []);

            if ($fields['type'])
                $type = Type::firstOrCreate(
                    [
                        'name'  => $fields['type'],
                        'type'  => 'product'
                    ],
                    []
                );

            if ($fields['status'])
                $status = Status::firstOrCreate(
                    [
                        'name'  => $fields['status'],
                        'type'  => 'product'
                    ],
                    []
                );

            if ($fields['currency'])
                $currency = Currency::firstOrCreate(
                    ['code'  => $fields['currency']],
                    ['name'  => $fields['currency']]
                );

            $brand_id = $fields['brand'] ? $brand->id : null;
            //$supplier_params = $this->supplier->getSupplierProductParams($fields['supplierSku'], $brand_id, $supplier_category->category_id);

            $product = Product::updateOrCreate(
                [
                    'supplier_id'   => $this->supplier->id,
                    'supplierSku'   => $fields['supplierSku'],
                    'pn'            => $fields['pn'],
                    'ean'           => $fields['ean'],
                    'upc'           => $fields['upc'],
                    'isbn'          => $fields['isbn'],
                    'gtin'          => null,
                    //'name'          => $fields['name'],
                ],
                [
                    'parent_id'     => $fields['parent'] ? $parent->id : null,
                    'brand_id'      => $brand_id,
                    'supplier_category_id'   => $supplier_category->id,
                    'category_id'   => $supplier_category->category_id,
                    'type_id'       => $fields['type'] ? $type->id : null,
                    'status_id'     => $fields['status'] ? $status->id : null,
                    'currency_id'   => $fields['currency'] ? $currency->id : $this->currency_id_eur,

                    'name'          => $fields['name'],
                    'keywords'      => $fields['keywords'],
                    'shortdesc'     => $fields['shortdesc'],
                    'longdesc'      => $fields['longdesc'] ?? $fields['name'],
                    'weight'        => null,
                    'length'        => null,
                    'width'         => null,
                    'height'        => null,

                    //'ready'         => $fields['ready'],
                    'model'         => $fields['model'],
                    // supplier_params 042021
                    'cost'          => FacadesMpe::roundFloat($fields['cost']),
                    //'cost'          => $this->getCost($fields, $supplier_params),
                    'tax'           => $fields['tax'] ?? $this->tax_max,
                    'stock'         => $fields['stock'],

                    'size'          => $fields['size'],
                    'color'         => $fields['color'],
                    'material'      => $fields['material'],
                    'style'         => $fields['style'],
                    'gender'        => $fields['gender'],

                    'fix_text'      => 0,
                ]
            );

            // Update Category Canon (ONLY Blanes)
            if ($fields['canon'] > 0 && isset($product->category_id))
                $product->category->firstOrCreateCanon($fields['canon'], 'es');

            // Sku Image
            if ($fields['sku_src']) {
                $product->sku_src = $product->updateOrCreateExternalImage($fields['sku_src'], 0);
                $product->save();
            }

            // Images
            if (isset($fields['images']) && count($fields['images']) > 0) {
                // By FTP || URL
                // FTP GMZ: 'Imágenes/'.$supplierSku
                $images_data = ($this->images_import_type == 'ftp') ? $this->images_folder.$fields['images'][0] : $fields['images'];
                $product->updateOrCreateExternalImages($images_data, $this->disk);
            }

            // Get image from MPS Products
            if (!isset($fields['sku_src']) && !isset($fields['images'])) {
                $product->getMPEProductImages();


                    /* $image = $this->getImageByEan($product->ean);
                    if (isset($image))
                        $product->updateOrCreateExternalImage($image, 0);
                }*/
            }

            $product->logPrice(true);

        } catch (\Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_NO_PRODUCT_CREATE.json',
                json_encode(array_merge(['error' => $th->__toString()], $fields)));
        }

        return $product;
    }


    private function updateFullProduct(Product $product, $fields)
    {
        //$supplier_params = $this->supplier->getSupplierProductParams($product->supplierSku, $product->brand_id, $product->category_id);
        $product->update([
            'name'          => $fields['name'],
            'keywords'      => $fields['keywords'],
            'pn'            => $fields['pn'] ?? $product->pn,
            'ean'           => $fields['ean'] ?? $product->ean,
            'upc'           => $fields['upc'],
            'isbn'          => $fields['isbn'],
            'gtin'          => null,
            'shortdesc'     => $fields['shortdesc'],
            'longdesc'      => $fields['longdesc'] ?? $fields['name'],
            'weight'        => null,
            'length'        => null,
            'width'         => null,
            'height'        => null,

            //'ready'         => $fields['ready'],
            'model'         => $fields['model'],
            // supplier_params 042021
            'cost'          => FacadesMpe::roundFloat($fields['cost']),
            //'cost'          => $this->getCost($fields, $supplier_params),
            'tax'           => $fields['tax'] ?? $this->tax_max,
            'stock'         => $fields['stock'],

            'size'          => $fields['size'],
            'color'         => $fields['color'],
            'material'      => $fields['material'],
            'style'         => $fields['style'],
            'gender'        => $fields['gender'],
        ]);

        $product->deleteAllImages();

        // Sku Image
        if ($fields['sku_src']) {
            $product->sku_src = $product->updateOrCreateExternalImage($fields['sku_src'], 0);
            $product->save();
        }

        // Images
        if (isset($fields['images']) && count($fields['images']) > 0) {
            $images_data = ($this->images_import_type == 'ftp') ? $this->images_folder.$fields['images'][0] : $fields['images'];
            $product->updateOrCreateExternalImages($images_data, $this->disk);
        }

        // Get images from MPS Products
        if (!isset($fields['sku_src']) && !isset($fields['images'])) {
            $product->getMPEProductImages();


               /*  $image = $this->getImageByEan($product->ean);
                if (isset($image))
                    $product->updateOrCreateExternalImage($image, 0);
            } */
        }

        $product->logPrice(false);

        return $product;
    }


    private function updateProduct(Product $product, $fields)
    {
        //$supplier_params = $this->supplier->getSupplierProductParams($product->supplierSku, $product->brand_id, $product->category_id);

        $product->update([
            //'ready'     => $fields['ready'],
            // supplier_params 042021
            'cost'          => FacadesMpe::roundFloat($fields['cost']),
            //'cost'      => $this->getCost($fields, $supplier_params),
            'tax'       => $fields['tax'] ?? $this->tax_max,
            'stock'     => $fields['stock'],
        ]);

        $product->logPrice(false);

        if (!$product->images()->count()) {
            // Get images from MPS Products
            if (!isset($fields['sku_src']) && !isset($fields['images'])) {
                $product->getMPEProductImages();

                   /*  $image = $this->getImageByEan($product->ean);
                    if (isset($image))
                        $product->updateOrCreateExternalImage($image, 0);
                } */
            }
        }

        return $product;
    }


    private function getFilterProducts($supplier_filter, $conditions)
    {
        $processed_ids = [];
        $res = [
            'updateds'      => 0,
            'news'          => 0,
            'no_category'   => [],
        ];

        $res['starts_at'] = date('Y-m-d_H-i-s');
        $filters = $supplier_filter ? $this->getFilters($supplier_filter) : [];
        $count = 0;
        $rows = [];
        $path = $this->getCsv();
        if (!$csv_array = file($path)) {
            Storage::put($this->storage_dir. 'errors/' .date('Y-m-d_H-i-s'). '_getFilterProducts.json', json_encode(['path not found' => $path]));
            return 0;
        }
        // remove first line (headers)
        if ($conditions['headers'])
            $csv_array = array_slice($csv_array, 1);

        //$attributes_updates = $this->getAttributesUpdates();

        foreach ($csv_array as $line) {

            $row = str_getcsv($line, $this->delimiter);
            $fields = $this->getFieldConditioneds($row, $conditions['fields']);
            $operationAllowed = $this->filterAllow($count, $filters, $fields);

            if ($operationAllowed) {
                $count++;
                $product = $this->supplier->products  //Product::where('supplier_id', $this->supplier->id)
                    ->where('supplierSku', $fields['supplierSku'])
                    /* ->where('pn', $fields['pn']) */
                    ->first();

                // Update cost & stock
                if (isset($product)) {
                    if ($product->ready) {
                        $updated = $this->updateProduct($product, $fields);
                        if (!isset($updated->id)) $res['no_category'][$updated] = 'No updated mapping';
                            else {
                                $res['updateds']++;
                                $processed_ids[] = $updated->id;
                            }
                    }
                }
                // New product
                else {
                    $created = $this->createProduct($fields);
                    if (!isset($created->id)) $res['no_category'][$created] = 'No created mapping';
                        else {
                            $res['news']++;
                            $processed_ids[] = $created->id;
                        }
                }
            }
        }

        // Resets OLD NOT FOUND product stocks of current supplier
        if (count( $processed_ids))
            $this->supplier->products()->whereNotIn('id', $processed_ids)->update(['stock' => 0]);

        Storage::append($this->storage_dir. 'products/' .date('Y-m-d'). '.json', json_encode($rows));

        $res['ends_at'] = date('Y-m-d_H-i-s');
        return $res;
    }


    private function getConditionedProducts($conditions)
    {
        $res = [];
        $supplier_filters = $this->supplier->supplier_filters;
        if (!$supplier_filters->count()) {
            $res = $this->getFilterProducts(null, $conditions);
        }
        else {
            foreach ($supplier_filters as $supplier_filter) {
                $res[] = $this->getFilterProducts($supplier_filter, $conditions);
            }
        }

        return $res;
    }


    private function getConditionedProduct(Product $product, $conditions)
    {
        $path = $this->getCsv();
        if (!$csv_array = file($path)) {
            Storage::put($this->storage_dir. 'errors/' .date('Y-m-d_H-i-s'). '_getSupplierProduct.json',
                json_encode(['path not found' => $path]));

            return false;
        }
        // remove first line (headers)
        if ($conditions['headers'])
            $csv_array = array_slice($csv_array, 1);

        foreach ($csv_array as $line) {

            $row = str_getcsv($line, $this->delimiter);
            $fields = $this->getFieldConditioneds($row, $conditions['fields']);
            if ($fields['supplierSku'] == $product->supplierSku) {
                $this->updateFullProduct($product, $fields);

                return $product;
            }

        }

        return null;
    }


    /************** PUBLIC FUNCTIONS ***************/


    public function getProduct(Product $product)
    {
        $this->getCsvFile();
        $product = $this->getConditionedProduct($product, $this->conditions);

        return $product;
    }


    public function getProducts()
    {
        $this->getCsvFile();
        // Save New products BY Idiomund categories
        $count = $this->getConditionedProducts($this->conditions);

        return $count;
    }


    public function getPricesStocks()
    {
        return null;
    }

}
