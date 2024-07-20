<?php

namespace App;

use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\Promo
 *
 * @property int $id
 * @property int|null $market_id
 * @property int|null $shop_id
 * @property int|null $supplier_id
 * @property int|null $product_id
 * @property int|null $shop_product_id
 * @property string|null $name
 * @property float|null $price
 * @property \Illuminate\Support\Carbon|null $begins_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property float|null $discount
 * @property-read mixed $offer_price
 * @property-read \App\Market|null $market
 * @property-read \App\Product|null $product
 * @property-read \App\Shop|null $shop
 * @property-read \App\ShopProduct|null $shop_product
 * @property-read \App\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo filter($params)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereBeginsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereShopProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Promo whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $marketProductSku
 * @method static Builder|Promo whereMarketProductSku($value)
 */
class Promo extends Model
{
    use HelperTrait;

    protected $table = 'promos';

    protected $dates = ['begins_at', 'ends_at'];

    protected $fillable = [
        'market_id', 'shop_id', 'supplier_id', 'product_id', 'marketProductSku', 'shop_product_id',
        'name', 'price', 'discount', 'begins_at', 'ends_at'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    public function shop_product()
    {
        return $this->belongsTo('App\ShopProduct');
    }


    // CUSTOM

    public function getOfferPriceAttribute()
    {
        if ($this->discount && $this->product) {
            $shop_product = $this->shop->shop_products()->whereProductId($this->product->id)->first();
            $res = round($shop_product->price - ($shop_product->price * $this->discount / 100), 2);
            return number_format((float)$res, 2, '.', '');
        }

        return number_format((float)round($this->price, 2), 2, '.', '');
    }


    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        $query->select('promos.*',
            'markets.name as market_name',
            'markets.product_url as market_product_url',
            'shops.name as shop_name',
            'suppliers.name as supplier_name',
            'products.name as product_name',
            'products.supplierSku as supplierSku',
            'products.pn as pn',
            'products.ean as ean',
            'products.upc as upc',
            'products.isbn as isbn',
            'products.cost as cost',
            'products.stock as stock',
            //'shop_products.price as shop_price',
            'images.src as image_src'
            // 'img/' .$this->id. '/' .$this->image_src
            //DB::raw("CONCAT('img/', promos.product_id, '/', images.src) AS first_image_url")
            //DB::raw("CONCAT('img/', promos.product_id, '/') AS first_image_url")
        )
            ->leftJoin('markets', 'promos.market_id', '=', 'markets.id')
            ->leftJoin('shops', 'promos.shop_id', '=', 'shops.id')
            ->leftJoin('suppliers', 'promos.supplier_id', '=', 'suppliers.id')
            ->leftJoin('products', 'promos.product_id', '=', 'products.id')
            //->leftJoin('shop_products', 'promos.shop_product_id', '=', 'shop_products.id')
            ->leftJoin('images', 'images.id', '=', DB::raw('(SELECT id FROM images WHERE images.product_id = products.id LIMIT 1)'));

        if ( isset($params['market_id']) && $params['market_id'] != null) {
            $query->where('promos.market_id', $params['market_id']);
        }

        if ( isset($params['shop_id']) && $params['shop_id'] != null) {
            $query->where('promos.shop_id', $params['shop_id']);
        }

        if ( isset($params['supplier_id']) && $params['supplier_id'] != null) {
            $query->where('promos.supplier_id', $params['supplier_id']);
        }

        if ( isset($params['name']) && $params['name'] != null) {
            $query->where('promos.name', 'LIKE', '%' .$params['name']. '%');
        }


        if ( isset($params['cost_min']) && $params['cost_min'] != null) {
            $query-> where('products.cost', '>=', floatval($params['cost_min']));
        }
        if ( isset($params['cost_max']) && $params['cost_max'] != null) {
            $query-> where('products.cost', '<=', floatval($params['cost_max']));
        }

        if ( isset($params['price_min']) && $params['price_min'] != null) {
            $query-> where('promos.price', '>=', floatval($params['price_min']));
        }
        if ( isset($params['price_max']) && $params['price_max'] != null) {
            $query-> where('promos.price', '<=', floatval($params['price_max']));
        }

        if ( isset($params['stock_min']) && $params['stock_min'] != null) {
            $query-> where('products.stock', '>=', intval($params['stock_min']));
        }
        if ( isset($params['stock_max']) && $params['stock_max'] != null) {
            $query-> where('products.stock', '<=', intval($params['stock_max']));
        }

        if ( isset($params['supplierSku']) && $params['supplierSku'] != null) {
            $query->where('products.supplierSku', $params['supplierSku']);
        }

        if (!isset($params['item_reference'])) $params['product_id'] = null;
        if (isset($params['item_reference']) && $params['item_reference'] != null) {
            if ($params['item_select'] == 'pn')
                $query->where('products.pn', $params['item_reference']);
            elseif ($params['item_select'] == 'ean')
                $query->where('products.ean', $params['item_reference']);
            elseif ($params['item_select'] == 'upc')
                $query->where('products.upc', $params['item_reference']);
            elseif ($params['item_select'] == 'isbn')
                $query->where('products.isbn', $params['item_reference']);
            elseif ($params['item_select'] == 'gtin')
                $query->where('products.gtin', $params['item_reference']);
            elseif ($params['item_select'] == 'name')
                if (isset($params['product_id']) && $params['product_id'] != null)
                    $query->where('products.id', $params['product_id']);
                else
                    $query->where('products.name', 'LIKE', '%' .$params['item_reference']. '%');
        }

        if ( isset($params['supplierSku']) && $params['supplierSku'] != null) {
            $query->where('products.supplierSku', $params['supplierSku']);
        }

        if ( isset($params['MPSSku']) && $params['MPSSku'] != null) {
            if ($shop_product = $this->shop->shop_products()->where('mps_sku', $params['MPSSku'])->first())
                $query->where('promos.product_id', $shop_product->product_id);
            /* else
                $query->where('promos.product_id', $this->getIdFromMPSSku($params['MPSSku'])); */
        }

        if ( isset($params['marketProductSku']) && $params['marketProductSku'] != null) {
            if ($params['marketProductSku'] == 'null') $params['marketProductSku'] = null;
            $query->where('promos.marketProductSku', $params['marketProductSku']);
        }


        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }





}
