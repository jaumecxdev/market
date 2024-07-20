<?php

namespace App;

use App\Traits\HelperTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * App\Shop
 *
 * @property int $id
 * @property int|null $market_id
 * @property string|null $code
 * @property string $name
 * @property string|null $marketShopId
 * @property string|null $marketSellerId
 * @property string|null $country
 * @property string|null $site
 * @property string|null $endpoint
 * @property string|null $app_name
 * @property string|null $app_version
 * @property string|null $client_id
 * @property string|null $client_secret
 * @property string|null $dev_id
 * @property string|null $token
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Group[] $groups
 * @property-read int|null $groups_count
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopFilter[] $shop_filters
 * @property-read int|null $shop_filters_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopGroup[] $shop_groups
 * @property-read int|null $shop_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopJob[] $shop_jobs
 * @property-read int|null $shop_jobs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopProduct[] $shop_products
 * @property-read int|null $shop_products_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereAppName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereAppVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereDevId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereEndpoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereMarketSellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereMarketShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereSite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereToken($value)
 * @mixin \Eloquent
 * @property string|null $store_url
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereStoreUrl($value)
 * @property string|null $refresh
 * @property string|null $redirect_url
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereRefresh($value)
 * @property string|null $payment
 * @property string|null $preparation
 * @property string|null $shipping
 * @property string|null $return
 * @property string|null $channel
 * @property string|null $header_url
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\FeeParam[] $fee_params
 * @property-read int|null $fee_params_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereHeaderUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop wherePayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop wherePreparation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereReturn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Shop whereShipping($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopParam[] $shop_params
 * @property-read int|null $shop_params_count
 * @property int $enabled
 * @property-read Collection|\App\ProviderUpdate[] $provider_updates
 * @property-read int|null $provider_updates_count
 * @method static EloquentBuilder|Shop whereEnabled($value)
 * @property-read Collection|\App\OrderShipment[] $order_shipments
 * @property-read int|null $shipments_count
 * @property-read Collection|\App\OrderComment[] $order_comments
 * @property-read int|null $order_comments_count
 * @property-read int|null $order_shipments_count
 * @property mixed|null $config
 * @method static EloquentBuilder|Shop whereConfig($value)
 * @property-read mixed $shop_dir
 * @property-read mixed $storage_dir
 * @property-read Collection|\App\ShopMessage[] $shop_messages
 * @property-read int|null $shop_messages_count
 * @method static EloquentBuilder|Shop filter($params)
 * @property string|null $dev_secret
 * @method static EloquentBuilder|Shop whereDevSecret($value)
 * @property string|null $locale
 * @method static EloquentBuilder|Shop whereLocale($value)
 */
class Shop extends Model
{
    use HelperTrait;

    protected $table = 'shops';

    public $timestamps = false;

    protected $fillable = [
        'market_id', 'code', 'name', 'locale', 'enabled', 'store_url', 'header_url', 'redirect_url', 'marketShopId', 'marketSellerId',
        'country', 'site', 'endpoint', 'app_name', 'app_version',
        'client_id', 'client_secret', 'dev_id', 'dev_secret', 'token', 'refresh',
        'preparation', 'shipping', 'return', 'payment', 'channel',
        'config'
    ];

    public function market()
    {
        return $this->belongsTo('App\Market');
    }


    // MANY

    public function groups()
    {
        return $this->hasMany('App\Group');
    }

    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    public function provider_updates()
    {
        return $this->hasMany('App\ProviderUpdate');
    }

    public function order_comments()
    {
        return $this->hasMany('App\OrderComment');
    }

    public function order_shipments()
    {
        return $this->hasMany('App\OrderShipment');
    }

    public function shop_filters()
    {
        return $this->hasMany('App\ShopFilter');
    }

    public function shop_groups()
    {
        return $this->hasMany('App\ShopGroup');
    }

    public function shop_jobs()
    {
        return $this->hasMany('App\ShopJob');
    }

    public function shop_messages()
    {
        return $this->hasMany('App\ShopMessage');
    }

    public function shop_params()
    {
        return $this->hasMany('App\ShopParam');
    }

    public function shop_products()
    {
        return $this->hasMany('App\ShopProduct');
    }



    // CUSTOM

    public function getStorageDir()
    {
        return 'mp/' .$this->market->code.'/';
    }


    public function getStorageDirAttribute()
    {
        return 'mp/' .$this->market->code.'/';
    }


    public function getShopDir()
    {
        return 'shops/' .$this->market->code.'_'.$this->code. '/';
    }


    public function getShopDirAttribute()
    {
        return 'shops/' .$this->market->code.'_'.$this->code. '/';
    }


    public function storage4Remove(array $remove_online_ids)
    {
        try {
            Storage::put($this->shop_dir.'/remove_online/'.date('Y-m-d_H-i-s').'_shop_products.json', json_encode($remove_online_ids));
            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $remove_online_ids);
        }
    }


    public function getStorage4Remove()
    {
        try {
            $filenames = Storage::files($this->shop_dir.'/remove_online');

            $products_4_remove = [];
            if (!empty($filenames)) {
                foreach ($filenames as $filename) {
                    $contents = Storage::get($filename);
                    $products_4_remove = array_merge($products_4_remove, json_decode($contents, true));
                    Storage::delete($filename);
                }
            }

            return new SupportCollection($products_4_remove);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$this, $filenames ?? null]);
        }
    }


    public function getProductsFilters($shop_filters = null)
    {
        try {
            $query = null;
            if (!$shop_filters) $shop_filters = $this->shop_filters;

            // Illuminate\Database\Eloquent\Builder
            if (!$shop_filters->count()) return null;
            foreach ($shop_filters as $shop_filter) {

                //if ($shop_filter->supplier_id != 42) continue;


                $new_query = Product::query();
                if ($shop_filter->product_id) {
                    $new_query->where('id', $shop_filter->product_id);
                }
                if ($shop_filter->supplier_brand_id) {
                    $new_query->where('supplier_brand_id', $shop_filter->supplier_brand_id);
                }
                if ($shop_filter->brand_id) {
                    $new_query->where('brand_id', $shop_filter->brand_id);
                }
                if ($shop_filter->supplier_category_id) {
                    $new_query->where('supplier_category_id', $shop_filter->supplier_category_id);
                }
                if ($shop_filter->category_id) {
                    $new_query->where('category_id', $shop_filter->category_id);
                }
                if ($shop_filter->supplier_id) {
                    $new_query->where('supplier_id', $shop_filter->supplier_id);
                }
                if ($shop_filter->type_id) {
                    $new_query->where('type_id', $shop_filter->type_id);
                }
                if ($shop_filter->status_id) {
                    $new_query->where('status_id', $shop_filter->status_id);
                }
                if ($shop_filter->cost_min) {
                    $new_query->where('cost', '>=', $shop_filter->cost_min);
                }
                if ($shop_filter->cost_max) {
                    $new_query->where('cost', '<=', $shop_filter->cost_max);
                }
                if ($shop_filter->stock_min) {
                    $new_query->where('stock', '>=', $shop_filter->stock_min);
                }
                if ($shop_filter->stock_max) {
                    $new_query->where('stock', '<=', $shop_filter->stock_max);
                }

                if ($shop_filter->limit_products) $new_query->take($shop_filter->limit_products);

                $query = isset($query) ? $query->union($new_query) : $new_query;
            }

            // Only READY products
            return $query->where('ready', 1);       // NO FUNCIONA??? ->where('stock', '>', 0);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function getFieldsShopParamsQueries()
    {
        try {
            //'fee', 'bfit_min', 'mps_fee', 'price', 'stock', 'stock_min', 'stock_max',
            // 'rappel', 'ports', 'discount_price', 'starts_at', 'ends_at'
            $fields_shop_params_queries = [];
            foreach (ShopParam::VALUE_FIELDS as $shop_product_param_field) {
                $fields_shop_params_queries[$shop_product_param_field] = $this->getFieldParamsQuery($shop_product_param_field);
            }

            return $fields_shop_params_queries;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function getFieldParamsQuery($field)
    {
        // FIELDS: 'rappel' 'ports' 'fee' 'bfit_min' 'mps_fee' 'price' 'stock' 'stock_min' 'stock_max';

        // 1.- 'product_id'
        // 2.- (supplier_id || seller_id) && status_id && (supplierSku,  'pn', 'ean', 'upc')
        // 3.- (supplier_id || seller_id) && (supplierSku,  'pn', 'ean', 'upc')
        // 4.- (supplier_id || seller_id) && status_id && brand_id && category_id
        // 5.- (supplier_id || seller_id) && status_id && brand_id
        // 6.- (supplier_id || seller_id) && status_id && category_id
        // 7.- (supplier_id || seller_id) && status_id
        // 8.- (supplier_id || seller_id) && brand_id && category_id
        // 9.- (supplier_id || seller_id) && brand_id
        // 10.- (supplier_id || seller_id) && category_id
        // 11.- GENERAL ALL null

        try {

            $now = Carbon::now()->addHour()->format('Y-m-d').' 00:00:00';

            $shop_params_query = $this->shop_params()
                ->where($field, '!=' , 0)
                ->where(function (EloquentBuilder $query) use ($now) {

                    return $query->where(function (EloquentBuilder $subquery) use ($now) {
                        return $subquery->whereNull('starts_at')
                                    ->orWhere('starts_at', '<=', $now);
                    })->orWhere(function (EloquentBuilder $subquery) use ($now) {
                        return $subquery->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $now);
                    });

                })
                //->orderBy('cost_min', 'asc')
                //->orderBy('cost_max', 'asc')

                ->orderBy('product_id', 'desc')
                ->orderBy('supplierSku', 'desc')
                ->orderBy('ean', 'desc')
                ->orderBy('pn', 'desc')
                ->orderBy('upc', 'desc')
                ->orderBy('supplier_id', 'desc')

                ->orderBy('supplier_brand_id', 'desc')
                ->orderBy('brand_id', 'desc')
                ->orderBy('supplier_category_id', 'desc')
                ->orderBy('category_id', 'desc')
                ->orderBy('market_category_id', 'desc')
                ->orderBy('root_category_id', 'desc');

                /* ->orderBy('product_id', 'desc')
                ->orderBy('supplierSku', 'desc')
                ->orderBy('ean', 'desc')
                ->orderBy('pn', 'desc')
                ->orderBy('upc', 'desc')
                ->orderBy('supplier_id', 'desc')

                ->orderBy('brand_id', 'desc')
                ->orderBy('category_id', 'desc')

                ->orderBy('market_category_id', 'desc')
                ->orderBy('root_category_id', 'desc')

                ->orderBy('starts_at', 'desc')
                ->orderBy('ends_at', 'desc'); */


                // 1.- 'product_id'
                // 2.- supplier_id && (supplierSku, 'ean', 'pn', 'upc')
                // 3.- supplier_id && brand_id && category_id
                // 4.- supplier_id && brand_id
                // 5.- supplier_id && category_id
                // 6.- GENERAL ALL null

            if (!$shop_params_query->count())
                return null;

            return $shop_params_query;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$field, $this]);
        }
    }


    public function getSimilarShopProduct($status_id, $brand_id, $pn, $ean)
    {
        try {
            $similar_shop_product = null;
            if (isset($ean) && $ean != '')
                $similar_shop_product = $this->shop_products()->select('shop_products.*')
                    ->leftJoin('products', 'shop_products.product_id', '=', 'products.id')
                    ->where('products.status_id', $status_id)
                    ->where('products.ean', $ean)
                    ->first();

            if (!$similar_shop_product && isset($pn) && $pn != '' && isset($brand_id))
                $similar_shop_product = $this->shop_products()->select('shop_products.*')
                    ->leftJoin('products', 'shop_products.product_id', '=', 'products.id')
                    ->where('products.status_id', $status_id)
                    ->where('products.pn', $pn)
                    ->where('products.brand_id', $brand_id)
                    ->first();

            return $similar_shop_product;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$status_id, $brand_id, $pn, $ean]);
        }
    }


    public function importFilteredProducts(EloquentBuilder $query_products)
    {
        try {
            if (!isset($query_products) || !$query_products->count()) return null;
            $products = $query_products->get();

            $suppliers_id = Auth::user()->getSuppliersId();

            $remove_online_ids = [];
            $res = [
                'Total de productos'                => $products->count(),
                'Creados o Actualizados'            => 0,
                'Cambio de proveedor'               => 0,
                'Rechazados por precio'             => 0,
                'Productos sin EAN13'               => 0,
                'Productos sin Título'              => 0,
                'Productos sin atributos'           => 0,
                'Productos sin imagen'              => 0,
                'Productos sin stock'               => 0,
                'Proveedor no permitido'            => 0,
                'Productos desabilitados'           => []
            ];

            $fields_shop_params_queries = $this->getFieldsShopParamsQueries();
            $market_params = $this->market->market_params;

            $processed_ids = [];
            foreach ($products as $product) {

                $market_category_id = null;
                if (isset($product->category_id)) {
                    $market_category_id = $product->category->market_category($this->market_id)->first()->id ?? null;

                    // Search & Create NEW Relation: Category <-> MarketCategory
                    if (!isset($market_category_id)) {
                        $market_category_shop_products = $this->shop_products()
                            ->select('shop_products.market_category_id', DB::raw('count(market_category_id) as count'))
                            ->leftjoin('products', 'products.id', '=', 'shop_products.product_id')
                            ->where('products.category_id', $product->category_id)
                            ->whereNotNull('shop_products.market_category_id')
                            ->groupBy('market_category_id')
                            ->orderBy('count', 'desc')
                            ->get();

                        if ($market_category_shop_products->count()) {
                            $market_category_shop_product = $market_category_shop_products->first();
                            $product->category->market_categories()->attach($market_category_shop_product->market_category_id);
                            $res['Nuevos Mappings de Categorias'][] = [$product->category->name, $market_category_shop_product->market_category->name];
                        }
                        //else continue;

                    }
                    //else continue;
                }

                if (!$product->ready)
                    $res['Productos desabilitados'][] = $product->id;
                elseif ($product->stock == 0)
                    $res['Productos sin stock']++;
                elseif ($this->market->market_category_required && !isset($product->category_id))
                    $res['Productos sin categoría'][] = $product->id;
                elseif ($this->market->market_category_required && !isset($market_category_id))
                    $res['Categorias sin mapear'][$product->category->id] = [$product->category->code, $product->category->name];
                elseif ($this->market->ean_required && !$product->ean && !in_array($product->supplier->code, ['davedans', 'jamonrey']))
                    $res['Productos sin EAN13']++;
                elseif ($this->market->pn_required && !$product->pn)
                    $res['Productos sin PN'][] = $product->id;
                elseif ($this->market->name_required && !$product->name)
                    $res['Productos sin Título']++;
                elseif ($this->market->attributes_required && !$product->provider_id)
                    $res['Productos sin atributos']++;
                elseif ($this->market->images_required && !$product->images()->count())
                    $res['Productos sin imagen']++;
                elseif (isset($suppliers_id) && !in_array($product->supplier_id, $suppliers_id))
                    $res['Proveedor no permitido']++;
                else {
                    $similar_shop_product = $this->getSimilarShopProduct($product->status_id, $product->brand_id, $product->pn, $product->ean);

                    // If it's NEW or the product_id already exists
                    if (!$similar_shop_product || $similar_shop_product->product_id == $product->id) {

                        $shop_product = $this->updateOrCreateShopProduct($product, $market_category_id);
                        $shop_product->setCanon($this->locale ?? 'es');
                        $shop_product->setShopParams($fields_shop_params_queries);
                        $shop_product->setMarketParams($market_params);

                        $res['Creados o Actualizados']++;
                        $processed_ids[] = $product->id;
                    }
                    // If there are TWO equals PN
                    // evaluate the cost, and make the product_id change if necessary.
                    elseif ($similar_shop_product->cost > $product->cost || ($similar_shop_product->stock == 0 && $product->stock > 0)) {

                        if (!$exists_shop_product = $this->shop_products()->firstWhere('product_id', $product->id)) {

                            // When change Product_id -> New Product
                            //$similar_shop_product->marketProductSku = null;
                            //$similar_shop_product->last_product_id = $similar_shop_product->product_id;
                            $similar_shop_product->product_id = $product->id;       // Update NEW Product_id
                            $similar_shop_product->enabled = true;
                            $similar_shop_product->market_category_id = $similar_shop_product->market_category_id ?? $market_category_id;
                            $similar_shop_product->currency_id = $product->currency_id;
                            $similar_shop_product->is_sku_child = isset($product->parent_id) ? true : false;
                            $similar_shop_product->cost = $product->cost;
                            $similar_shop_product->tax = $product->tax;
                            $similar_shop_product->stock = $product->stock;
                            $similar_shop_product->save();

                            $similar_shop_product->setCanon($this->locale ?? 'es');
                            $similar_shop_product->setShopParams($fields_shop_params_queries);
                            $similar_shop_product->setMarketParams($market_params);

                            $res['Cambio de proveedor']++;
                            $processed_ids[] = $product->id;
                        }
                    }
                    // There is another Product with better cost
                    else {
                        $res['Rechazados por precio']++;
                        continue;
                    }
                }
            }

            // DISABLE OLD Shop Products NOT imported NOW
            if (count($processed_ids))
                $res['Sin filtro'] = $this->shop_products()->whereNotIn('shop_products.product_id', $processed_ids)->update(['enabled' => false, 'stock' => 0]);

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$this, $query_products->get()]);
        }
    }


    public function updateOrCreateSaasShopProduct($product, $market_category_id = null, $mps_sku_field)
    {
        try {
            if (!isset($market_category_id) && isset($product->category_id)) $market_category_id = $product->category->market_category($this->market_id)->first()->id ?? null;
            $shop_product = ShopProduct::updateOrCreate(
                [
                    'market_id'             => $this->market->id,
                    'shop_id'               => $this->id,
                    'product_id'            => $product->id,
                ],
                [
                    'enabled'               => true,
                    'market_category_id'    => $market_category_id,
                    'currency_id'           => $product->currency_id,
                    'provider_id'           => $product->provider_id,
                    'provider_category_id'  => $product->provider_category_id,
                    'is_sku_child'          => isset($product->parent_id) ? true : false,
                    'cost'                  => $product->cost,
                    'tax'                   => $product->tax,
                    'stock'                 => $product->stock,
                ]
            );

            if ($mps_sku_field == 'id_pn_ean') $mps_sku = $product->getMPSSku();
            elseif ($mps_sku_field == 'ean') $mps_sku = $product->ean;
            elseif ($mps_sku_field == 'pn') $mps_sku = $product->pn;
            else $mps_sku = $product->supplierSku;  // 'sku_prov'
            $shop_product->mps_sku = $shop_product->mps_sku ?? $mps_sku;
            $shop_product->save();

            return $shop_product;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product, $market_category_id, $this]);
        }
    }


    public function getSaasSimilarShopProduct($status_id, $supplier_brand_id, $pn, $ean)
    {
        try {
            $similar_shop_product = null;
            if (isset($ean) && $ean != '')
                $similar_shop_product = $this->shop_products()->select('shop_products.*')
                    ->leftJoin('products', 'shop_products.product_id', '=', 'products.id')
                    ->where('products.status_id', $status_id)
                    ->where('products.ean', $ean)
                    ->first();

            if (!$similar_shop_product && isset($pn) && $pn != '' && isset($supplier_brand_id))
                $similar_shop_product = $this->shop_products()->select('shop_products.*')
                    ->leftJoin('products', 'shop_products.product_id', '=', 'products.id')
                    ->where('products.status_id', $status_id)
                    ->where('products.pn', $pn)
                    ->where('products.supplier_brand_id', $supplier_brand_id)
                    ->first();

            return $similar_shop_product;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$status_id, $supplier_brand_id, $pn, $ean]);
        }
    }


    public function importSaasFilteredProducts(EloquentBuilder $query_products)
    {
        try {
            if (!isset($query_products) || !$query_products->count()) return null;
            $products = $query_products->get();

            $suppliers_id = Auth::user()->getSuppliersId();

            $remove_online_ids = [];
            $res = [
                'Total de productos'                => $products->count(),
                'Creados o Actualizados'            => 0,
                'Cambio de proveedor'               => 0,
                'Rechazados por precio'             => 0,
                'Productos sin EAN13'               => 0,
                'Productos sin Título'              => 0,
                'Productos sin atributos'           => 0,
                'Productos sin imagen'              => 0,
                'Productos sin stock'               => 0,
                'Proveedor no permitido'            => 0,
                'Productos desabilitados'           => []
            ];

            $fields_shop_params_queries = $this->getFieldsShopParamsQueries();
            $market_params = $this->market->market_params;

            // ['id_pn_ean', 'ean', 'pn', 'sku_prov'],
            $mps_sku_field = 'id_pn_ean';
            if ($config_json = json_decode($this->config)) {
                if (isset($config_json->sku_type)) {
                    $mps_sku_field = $config_json->sku_type;
                }
            }

            $processed_ids = [];
            foreach ($products as $product) {

                $market_category_id = null;
                if (isset($product->supplier_category_id)) {
                    $market_category_id = $product->supplier_category->market_categories()->wherePivot('market_id', $this->market_id)->first()->id ?? null;

                    // Search & Create NEW Relation: SupplierCategory <-> MarketCategory
                    if (!isset($market_category_id)) {
                        $market_category_shop_products = $this->shop_products()
                            ->select('shop_products.market_category_id', DB::raw('count(market_category_id) as count'))
                            ->leftjoin('products', 'products.id', '=', 'shop_products.product_id')
                            ->where('products.supplier_category_id', $product->supplier_category_id)
                            ->whereNotNull('shop_products.market_category_id')
                            ->groupBy('market_category_id')
                            ->orderBy('count', 'desc')
                            ->get();

                        if ($market_category_shop_products->count()) {
                            $market_category_shop_product = $market_category_shop_products->first();
                            $product->supplier_category->market_categories()->attach($market_category_shop_product->market_category_id,
                            [
                                'supplier_id' => $product->supplier_id,
                                'market_id' => $this->market_id
                            ]);

                            $res['Nuevos Mappings de Categorias'][] = [$product->supplier_category->name, $market_category_shop_product->market_category->name];
                        }
                        //else continue;
                    }
                    //else continue;
                }

                if (!$product->ready)
                    $res['Productos desabilitados'][] = $product->id;
                elseif ($product->stock == 0)
                    $res['Productos sin stock']++;
                elseif ($this->market->market_category_required && !isset($product->supplier_category_id))
                    $res['Productos sin categoría'][] = $product->id;
                elseif ($this->market->market_category_required && !isset($market_category_id))
                    $res['Categorias sin mapear'][$product->supplier_category->id] = [$product->supplier_category->supplierCategoryId, $product->supplier_category->name];
                elseif ($this->market->ean_required && !$product->ean && !in_array($product->supplier->code, ['davedans', 'jamonrey']))
                    $res['Productos sin EAN13']++;
                elseif ($this->market->pn_required && !$product->pn)
                    $res['Productos sin PN'][] = $product->id;
                elseif ($this->market->name_required && !$product->name)
                    $res['Productos sin Título']++;
                elseif ($this->market->attributes_required && !$product->provider_id)
                    $res['Productos sin atributos']++;
                elseif ($this->market->images_required && !$product->images()->count())
                    $res['Productos sin imagen']++;
                elseif (isset($suppliers_id) && !in_array($product->supplier_id, $suppliers_id))
                    $res['Proveedor no permitido']++;
                else {
                    $similar_shop_product = $this->getSaasSimilarShopProduct($product->status_id, $product->brand_id, $product->pn, $product->ean);

                    // If it's NEW or the product_id already exists
                    if (!$similar_shop_product || $similar_shop_product->product_id == $product->id) {

                        $shop_product = $this->updateOrCreateSaasShopProduct($product, $market_category_id, $mps_sku_field);
                        $shop_product->setCanon($this->locale ?? 'es');
                        $shop_product->setShopParams($fields_shop_params_queries);
                        $shop_product->setMarketParams($market_params);

                        $res['Creados o Actualizados']++;
                        $processed_ids[] = $product->id;
                    }
                    // If there are TWO equals PN
                    // evaluate the cost, and make the product_id change if necessary.
                    elseif ($similar_shop_product->cost > $product->cost || ($similar_shop_product->stock == 0 && $product->stock > 0)) {

                        if (!$exists_shop_product = $this->shop_products()->firstWhere('product_id', $product->id)) {

                            // When change Product_id -> New Product
                            //$similar_shop_product->marketProductSku = null;
                            //$similar_shop_product->last_product_id = $similar_shop_product->product_id;
                            $similar_shop_product->product_id = $product->id;       // Update NEW Product_id
                            $similar_shop_product->enabled = true;
                            $similar_shop_product->market_category_id = $similar_shop_product->market_category_id ?? $market_category_id;
                            $similar_shop_product->currency_id = $product->currency_id;
                            $similar_shop_product->is_sku_child = isset($product->parent_id) ? true : false;
                            $similar_shop_product->cost = $product->cost;
                            $similar_shop_product->tax = $product->tax;
                            $similar_shop_product->stock = $product->stock;
                            $similar_shop_product->save();

                            $similar_shop_product->setCanon($this->locale ?? 'es');
                            $similar_shop_product->setShopParams($fields_shop_params_queries);
                            $similar_shop_product->setMarketParams($market_params);

                            $res['Cambio de proveedor']++;
                            $processed_ids[] = $product->id;
                        }
                    }
                    // There is another Product with better cost
                    else {
                        $res['Rechazados por precio']++;
                        continue;
                    }
                }
            }

            // DISABLE OLD Shop Products NOT imported NOW
            if (count($processed_ids))
                $res['Sin filtro'] = $this->shop_products()->whereNotIn('shop_products.product_id', $processed_ids)->update(['enabled' => false, 'stock' => 0]);

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$this, $query_products->get()]);
        }
    }


    public function getPropertyValues($market_category_name, $market_attribute_name)
    {
        try {
            if (!isset($market_category_name)) return 'Es necesario entrar market_category_name y market_attribute_name';
            $market_attribute_name = $market_attribute_name ?? 'Brand Name';

            $market_category = MarketCategory::whereMarketId($this->market_id)
                ->whereName($market_category_name)->first();        //find(233);
            $market_attribute = MarketAttribute::whereMarketId($this->market_id)
                ->whereMarketCategoryId($market_category->id)
                ->whereName($market_attribute_name)->first();

            // Get Online
            /* if ($ws = MarketWS::getMarketWS($this))
                $ws->getAttributes($market_category); */

            $property = Property::whereMarketAttributeId($market_attribute->id)->first();
            $property_values = PropertyValue::wherePropertyId($property->id)->get();

            return [
                'market_category_id'    => $market_category->id,
                'market_category_name'  => $market_category->name,
                'market_attribute_id'   => $market_attribute->id,
                'market_attribute_name' => $market_attribute->name,
                'property_id'           => $property->id,
                'property_name'         => $property->name,
                'property_values'       => json_encode($property_values)
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$market_category_name, $market_attribute_name, $this]);
        }
    }


    public function setShopParamCategories()
    {
        try {
            $market_category_ids = $this->shop_products()
                ->select('shop_products.market_category_id', 'market_categories.id', 'market_categories.name')
                ->join('market_categories', 'market_categories.id', '=', 'shop_products.market_category_id')
                ->groupBy('market_category_id')
                ->orderBy('market_categories.name')
                ->pluck('market_categories.id', 'market_categories.name')
                ->all();

            // Add MarketCategories TO Default ShopParams
            foreach ($market_category_ids as $market_category_id) {
                ShopParam::firstOrCreate([
                    'shop_id'           => $this->id,
                    'market_category_id'=> $market_category_id,
                ],[
                ]);
            }

            return redirect()->route('shops.shop_params.index', [$this])->with('status', 'Categorías añadidas correctamente.');

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function syncParams()
    {
        try {
            $fields_shop_params_queries = $this->getFieldsShopParamsQueries();
            foreach ($this->shop_products as $shop_product) {
                $shop_product->setCanon($this->locale ?? 'es');
                $shop_product->setShopParams($fields_shop_params_queries);
            }

            return 'Parámetros de la tienda '.$this->name.' sincronizados con  '.$this->shop_products->count().' productos.';

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $this);
        }
    }


    public function scopeFilter($query, $params)
    {
        try {
            /* 'market_id', 'code', 'name', 'enabled', 'store_url', 'header_url', 'redirect_url', 'marketShopId', 'marketSellerId',
            'country', 'site', 'endpoint', 'app_name', 'app_version',
            'client_id', 'client_secret', 'dev_id', 'token', 'refresh',
            'preparation', 'shipping', 'return', 'payment', 'channel',
            'config' */

            $query->select('shops.*',
                'markets.name as market_name',
                'markets.order_url as market_order_url',
                DB::raw("CONCAT('(', markets.name, ') ', shops.name) AS market_shop_name")
            )
            ->leftJoin('markets', 'shops.market_id', '=', 'markets.id');

            /* if ( isset($params['marketOrderId']) && $params['marketOrderId'] != null) {
                $query->where('orders.marketOrderId', 'LIKE', '%' . $params['marketOrderId'] . '%');
            } */

            if ( isset($params['market_id']) && $params['market_id'] != null) {
                $query->where('market_id', $params['market_id']);
            }

            // ORDER BY
            if ( isset($params['order_by']) && $params['order_by'] != null) {
                $query->orderBy($params['order_by'], $params['order']);
            }

            return $query;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$query, $params, $this]);
        }
    }



}
