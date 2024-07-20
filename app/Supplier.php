<?php

namespace App;

use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * App\Supplier
 *
 * @property int $id
 * @property string|null $code
 * @property string $name
 * @property string|null $type_import
 * @property string|null $primary_key_field
 * @property string|null $brand_field
 * @property string|null $category_field
 * @property string|null $type_field
 * @property string|null $status_field
 * @property string|null $name_field
 * @property string|null $model_field
 * @property string|null $supplierSku_field
 * @property string|null $pn_field
 * @property string|null $ean_field
 * @property string|null $upc_field
 * @property string|null $isbn_field
 * @property string|null $cost_field
 * @property string|null $stock_field
 * @property string|null $ready_field
 * @property string|null $keys_field
 * @property string|null $short_field
 * @property string|null $long_field
 * @property string|null $images_field
 * @property string|null $extra_field
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopFilter[] $shop_filters
 * @property-read int|null $shop_filters_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Status[] $statuses
 * @property-read int|null $statuses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierFilter[] $supplier_filters
 * @property-read int|null $supplier_filters_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereBrandField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereCategoryField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereCostField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereEanField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereExtraField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereImagesField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereIsbnField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereKeysField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereLongField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereModelField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereNameField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier wherePnField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier wherePrimaryKeyField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereReadyField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereShortField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereStatusField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereStockField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereSupplierSkuField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereTypeField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereTypeImport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereUpcField($value)
 * @mixin \Eloquent
 * @property string|null $ws
 * @property string|null $parent_field
 * @property string|null $currency_field
 * @property string|null $tax_field
 * @property string|null $size_field
 * @property string|null $color_field
 * @property string|null $material_field
 * @property string|null $style_field
 * @property string|null $gender_field
 * @property string|null $sku_src_field
 * @property string|null $weight_field
 * @property string|null $length_field
 * @property string|null $width_field
 * @property string|null $height_field
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereColorField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereCurrencyField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereGenderField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereHeightField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereLengthField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereMaterialField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereParentField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereSizeField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereSkuSrcField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereStyleField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereTaxField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereWeightField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereWidthField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereWs($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierParam[] $supplier_params
 * @property-read int|null $supplier_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Receiver[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Receiver[] $receivers
 * @property-read int|null $receivers_count
 * @property string|null $category_id_field
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereCategoryIdField($value)
 * @property string|null $canon_field
 * @property string|null $rappel_field
 * @property string|null $ports_field
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereCanonField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier wherePortsField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Supplier whereRappelField($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\FeeParam[] $fee_params
 * @property-read int|null $fee_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopParam[] $shop_params
 * @property-read int|null $shop_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierBrand[] $supplier_brands
 * @property-read int|null $supplier_brands_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierCategory[] $supplier_categories
 * @property-read int|null $supplier_categories_count
 * @property mixed|null $config
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereConfig($value)
 * @property-read mixed $storage_dir
 * @property string|null $locale
 * @method static \Illuminate\Database\Eloquent\Builder|Supplier whereLocale($value)
 */
class Supplier extends Model
{
    use HelperTrait;

    protected $table = 'suppliers';

    public $timestamps = false;

    protected $fillable = [
        'code', 'name', 'locale', 'type_import', 'ws', 'primary_key_field', 'brand_field', 'category_field', 'category_id_field',
        'type_field', 'status_field', 'currency_field', 'parent_field',
        'ready_field', 'name_field', 'keys_field', 'pn_field', 'ean_field', 'upc_field', 'isbn_field', 'short_field', 'long_field',
        'supplierSku_field', 'model_field', 'cost_field', 'canon_field', 'rappel_field', 'ports_field', 'tax_field',
        'stock_field', 'size_field', 'color_field', 'material_field', 'style_field', 'gender_field', 'sku_src_field',
        'weight_field', 'length_field', 'width_field', 'height_field',
        //'field_name', 'field_string_value', 'field_integer_value', 'field_float_value',
        'images_field', 'extra_field',
        'config'
    ];

    // MANY

    public function products()
    {
        return $this->hasMany('App\Product');
    }

    public function receivers()
    {
        return $this->hasMany('App\Receiver');
    }

    public function shop_filters()
    {
        return $this->hasMany('App\ShopFilter');
    }

    public function shop_params()
    {
        return $this->hasMany('App\ShopParam');
    }

    public function statuses()
    {
        return $this->hasMany('App\Status');
    }

    public function supplier_brands()
    {
        return $this->hasMany('App\SupplierBrand');
    }

    public function supplier_categories()
    {
        return $this->hasMany('App\SupplierCategory');
    }

    public function supplier_filters()
    {
        return $this->hasMany('App\SupplierFilter');
    }

    public function supplier_params()
    {
        return $this->hasMany('App\SupplierParam');
    }



    // CUSTOM


    public function getStorageDirAttribute()
    {
        return 'supplier/' .$this->code.'/';
    }


    public function getSimilarProduct($status_id, $brand_id, $pn, $ean)
    {
        try {
            if (isset($ean) && $ean != '')
                return $this->products()
                    ->where('status_id', $status_id)
                    ->where('ean', $ean)
                    //->where('ready', 1)
                    ->first();
            elseif (isset($pn) && $pn != '' && isset($brand_id))
                return $this->products()
                    ->where('status_id', $status_id)
                    ->where('pn', $pn)
                    ->where('brand_id', $brand_id)
                    //->where('ready', 1)
                    ->first();

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$status_id, $brand_id, $pn, $ean, $this]);
        }
    }


    public function updateOrCreateProduct($pn, $ean, $upc = null, $isbn = null, $gtin = null, $supplierSku,
        $brand_id, $supplier_category_id, $category_id, $status_id, $currency_id,
        $name, $longdesc,
        $cost, $tax, $stock,
        $weight = null, $length = null, $width = null, $height = null,
        $parent_id = null, $type_id = null, $provider_id = null,
        $model = null, $keywords = null, $shortdesc = null,
        $size = null, $color = null, $material = null, $style = null, $gender = null)
    {
        try {
            if ($ean == '') $ean = null;
            if ($pn == '') $pn = null;
            //$ean = $ean ?? FacadesMpe::getMPSEan($brand_id, $pn);

            return Product::updateOrCreate(
                [
                    'supplier_id'           => $this->id,
                    'pn'                    => $pn,
                    'ean'                   => $ean,
                    'upc'                   => $upc,
                    'isbn'                  => $isbn,
                    'gtin'                  => $gtin,
                ],
                [
                    //'ready'                 => 1,
                    'fix_text'              => 0,
                    'supplierSku'           => $supplierSku,
                    'parent_id'             => $parent_id,
                    'brand_id'              => $brand_id,
                    'supplier_category_id'  => $supplier_category_id,
                    'category_id'           => $category_id,
                    'type_id'               => $type_id,
                    'status_id'             => $status_id,
                    'currency_id'           => $currency_id,
                    'provider_id'           => $provider_id,

                    'name'                  => $name,
                    'keywords'              => $keywords,
                    'shortdesc'             => $shortdesc,
                    'longdesc'              => $longdesc,
                    'weight'                => $weight,
                    'length'                => $length,
                    'width'                 => $width,
                    'height'                => $height,

                    'model'                 => $model,
                    'cost'                  => $cost,
                    'tax'                   => $tax,
                    'stock'                 => $stock,

                    'size'                  => $size,
                    'color'                 => $color,
                    'material'              => $material,
                    'style'                 => $style,
                    'gender'                => $gender,
                ]
            );

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$pn, $ean, $upc, $isbn, $gtin, $supplierSku,
                $brand_id, $supplier_category_id, $category_id, $status_id, $currency_id,
                $name, $longdesc,
                $cost, $tax, $stock,
                $weight, $length, $width, $height,
                $parent_id, $type_id, $provider_id,
                $model, $keywords, $shortdesc,
                $size, $color, $material, $style, $gender]);
        }
    }


    public function firstOrCreateParam($brand_id, $category_id, $rappel = 0, $ports = 0)
    {
        try {
            return SupplierParam::firstOrCreate([
                'supplier_id'   => $this->id,
                'brand_id'      => $brand_id,
                'category_id'   => $category_id,
            ],[
                'rappel'        => $rappel,
                'ports'         => $ports,
            ]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$brand_id, $category_id, $rappel, $ports, $this]);
        }
    }


    public function getSupplierProductParams($supplierSku, $brand_id, $category_id)
    {
        try {
            /* $param_canon = $this->getSupplierProductParam($supplierSku, $brand_id, $category_id, 'canon'); */
            $param_rappel = $this->getSupplierProductParam($supplierSku, $brand_id, $category_id, 'rappel');
            $param_ports = $this->getSupplierProductParam($supplierSku, $brand_id, $category_id, 'ports');

            return [
                /* 'canon'    => $param_canon->canon ?? 0, */
                'rappel'    => $param_rappel->rappel ?? 0,
                'ports'    => $param_ports->ports ?? 0,
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$supplierSku, $brand_id, $category_id, $this]);
        }
    }


    private function getSupplierProductParam($supplierSku, $brand_id, $category_id, $field)
    {
        try {
            // 1.- supplierSku
            // 2.- brand_id && category_id
            // 3.- brand_id
            // 4.- category_id
            // 5.- GENERAL supplier_id: ALL null

            $supplier_params = $this->supplier_params->where($field, '!=' , 0);

            // PRODUCT_SUPPLIER_SKU
            $supplier_param = $supplier_params
                ->firstWhere('supplierSku', $supplierSku);

            // BRAND_ID & CATEGORY_ID
            if (!isset($supplier_param))
                $supplier_param = $supplier_params
                    ->where('brand_id', $brand_id)
                    ->firstWhere('category_id', $category_id);

            // BRAND_ID
            if (!isset($supplier_param))
                $supplier_param = $supplier_params
                    ->firstWhere('brand_id', $brand_id);

            // CATEGORY_ID
            if (!isset($supplier_param))
                $supplier_param = $supplier_params
                    ->firstWhere('category_id', $category_id);

            // GENERAL: SUPPLIER_ID
            if (!isset($supplier_param))
                $supplier_param = $supplier_params
                    ->whereNull('supplierSku')
                    ->whereNull('brand_id')
                    ->whereNull('category_id')
                    ->first();

            return $supplier_param;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$supplierSku, $brand_id, $category_id, $field, $this]);
        }
    }


    public function getFilterFields()
    {
        // NOT included IN Filters:
        // 'currency_field', 'parent_field', 'ready_field', 'keys_field', 'short_field', 'long_field',
        // 'rappel_field', 'ports_field', 'tax_field',
        // 'size_field', 'color_field', 'material_field', 'style_field', 'gender_field', 'sku_src_field',
        // 'weight_field', 'length_field', 'width_field', 'height_field',
        // 'images_field', 'extra_field'

        // filter_name -> SupplierFilter
        /* 'supplier_id', 'status_name', 'brand_name', 'category_name', // NOT USE: 'type_name',
        'supplierSku', 'name', 'pn', 'ean', 'upc', 'isbn', 'model',
        'cost_min', 'cost_max', 'stock_min', 'stock_max',
        'field_name', 'field_operator', 'field_string', 'field_integer', 'field_float',
        'limit_products' */

        // field_name -> Supplier
        /* 'code', 'name', 'type_import', 'ws', 'primary_key_field', 'brand_field', 'category_field', 'category_id_field',
        'type_field', 'status_field', 'currency_field', 'parent_field',
        'ready_field', 'name_field', 'keys_field', 'pn_field', 'ean_field', 'upc_field', 'isbn_field', 'short_field', 'long_field',
        'supplierSku_field', 'model_field', 'cost_field', 'canon_field', 'rappel_field', 'ports_field', 'tax_field',
        'stock_field', 'size_field', 'color_field', 'material_field', 'style_field', 'gender_field', 'sku_src_field',
        'weight_field', 'length_field', 'width_field', 'height_field',
        //'field_name', 'field_string_value', 'field_integer_value', 'field_float_value',
        'images_field', 'extra_field',
        'config' */

        return [
            [
                'filter_name'           => 'brand_name',
                'field_name'            => 'brand_field',
                'operator'              => '=',
                'only_prices_stocks'    => false,
            ],
            [
                'filter_name'           => 'category_name',
                'field_name'            => 'category_field',
                'operator'              => '=',
                'only_prices_stocks'    => false,
            ],
            [
                'filter_name'           => 'category_name',
                'field_name'            => 'category_id_field',
                'operator'              => '=',
                'only_prices_stocks'    => false,
            ],
            [
                'filter_name'           => 'type_name',
                'field_name'            => 'type_field',
                'operator'              => '=',
                'only_prices_stocks'    => false,
            ],
            [
                'filter_name'           => 'status_name',
                'field_name'            => 'status_field',
                'operator'              => '=',
                'only_prices_stocks'    => false,
            ],
            [
                'filter_name'           => 'name',
                'field_name'            => 'name_field',
                'operator'              => 'like',
                'only_prices_stocks'    => false,
            ],
            [
                'filter_name'           => 'field_name',
                'field_name'            => null,
                'operator'              => null,
                'only_prices_stocks'    => false,
            ],

            [
                'filter_name'           => 'supplierSku',
                'field_name'            => 'supplierSku_field',
                'operator'              => '=',
                'only_prices_stocks'    => true,
            ],
            [
                'filter_name'           => 'model',
                'field_name'            => 'model_field',
                'operator'              => '=',
                'only_prices_stocks'    => true,
            ],
            [
                'filter_name'           => 'pn',
                'field_name'            => 'pn_field',
                'operator'              => '=',
                'only_prices_stocks'    => true,
            ],
            [
                'filter_name'           => 'ean',
                'field_name'            => 'ean_field',
                'operator'              => '=',
                'only_prices_stocks'    => true,
            ],
            [
                'filter_name'           => 'upc',
                'field_name'            => 'upc_field',
                'operator'              => '=',
                'only_prices_stocks'    => true,
            ],
            [
                'filter_name'           => 'isbn',
                'field_name'            => 'isbn_field',
                'operator'              => '=',
                'only_prices_stocks'    => true,
            ],

            [
                'filter_name'           => 'cost_min',
                'field_name'            => 'cost_field',
                'operator'              => '>=',
                'only_prices_stocks'    => true,
            ],
            [
                'filter_name'           => 'cost_max',
                'field_name'            => 'cost_field',
                'operator'              => '<=',
                'only_prices_stocks'    => true,
            ],
            [
                'filter_name'           => 'stock_min',
                'field_name'            => 'stock_field',
                'operator'              => '>=',
                'only_prices_stocks'    => true,
            ],
            [
                'filter_name'           => 'stock_max',
                'field_name'            => 'stock_field',
                'operator'              => '<=',
                'only_prices_stocks'    => true,
            ],

            [
                'filter_name'           => null,
                'field_name'            => 'limit_products',
                'operator'              => null,
                'only_prices_stocks'    => true,
            ],
        ];
    }


    public function filterProducts(Collection $productsCollect)
    {
        try {
            if (!isset($productsCollect))
                return $this->nullAndStorage(__METHOD__, $productsCollect);

            $productsCollectResult = collect();
            $supplier_filters = $this->supplier_filters;
            //$productsCollect = $productsCollect->where($this->stock_field, '>', 0);

            if (!$supplier_filters->count())
                return $this->nullAndStorage(__METHOD__, $productsCollect);

            $filter_fields = $this->getFilterFields();
            foreach ($supplier_filters as $supplier_filter) {

                $productsCollectFilter = $productsCollect;
                foreach ($filter_fields as $filter_field) {

                    if ($supplier_filter->{$filter_field['filter_name']} && isset($this->{$filter_field['field_name']})) {

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
                                    $this->{$filter_field['field_name']},
                                    $filter_field['operator'],
                                    $supplier_filter_name
                                );
                    }
                }

                $productsCollectResult = $productsCollectResult->union($productsCollectFilter);
            }

            Storage::append($this->storage_dir.'import/'.date('Y-m-d_H-i').'_products_filtered.json', $productsCollectResult->toJson());

            return $productsCollectResult;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$productsCollect, $this]);
        }
    }



    public function syncParams()
    {
        try {
            $shop_ids = $this->shop_filters()->groupBy('shop_id')->pluck('shop_id');
            foreach ($shop_ids as $shop_id) {
                foreach ($this->supplier_params as $supplier_param) {

                    ShopParam::updateOrCreate([
                        'shop_id'       => $shop_id,
                        'supplier_id'   => $this->id,
                        'brand_id'      => $supplier_param->brand_id,
                        'category_id'   => $supplier_param->category_id,

                        'product_id'    => $supplier_param->product_id,
                        'supplierSku'   => $supplier_param->supplierSku,
                        'pn'            => $supplier_param->pn,
                        'ean'           => $supplier_param->ean,
                        'upc'           => $supplier_param->upc,
                        'isbn'          => $supplier_param->isbn,
                        'gtin'          => $supplier_param->gtin,

                        'cost_min'      => $supplier_param->cost_min,
                        'cost_max'      => $supplier_param->cost_max,
                        'starts_at'     => $supplier_param->starts_at,
                        'ends_at'       => $supplier_param->ends_at,
                    ],[
                        /* 'canon'         => $supplier_param->canon, */
                        'rappel'        => $supplier_param->rappel,
                        'ports'         => $supplier_param->ports,

                        'price'         => $supplier_param->price,
                        'discount_price'=> $supplier_param->discount_price,
                        'stock'         => $supplier_param->stock
                    ]);
                }
            }

            return 'Parámetros del proveedor '.$this->name.' añadidos a '.count($shop_ids).' Tiendas.';

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $this);
        }
    }

}
