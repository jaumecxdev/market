<?php

namespace App;

use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


/**
 * App\Price
 *
 * @property int $id
 * @property int|null $product_id
 * @property string|null $name
 * @property float $cost
 * @property float $price
 * @property int $stock
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $shop_id
 * @property-read \App\Product|null $product
 * @property-read \App\Shop|null $shop
 * @property-read \App\ShopProduct|null $shop_product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereShopProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property float $fee_mps
 * @property float $fee_mp
 * @property float $benefit_mps
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price filter($params)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereBenefitMps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereFeeMp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereFeeMps($value)
 * @property float $supplier_fee
 * @property float $supplier_bfit
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereMpFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereMpFeeAddon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereSupplierBfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereSupplierFee($value)
 * @property float $mp_bfit
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereMpBfit($value)
 * @property float $bfit
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Price whereBfit($value)
 * @property int|null $shop_product_id
 * @property string|null $marketProductSku
 * @method static Builder|Price whereMarketProductSku($value)
 * @property float $mps_bfit
 * @method static Builder|Price whereMpsBfit($value)
 */
class Price extends Model
{
    use HelperTrait;

    protected $table = 'prices';

    protected $fillable = [
        'product_id', 'shop_product_id', 'shop_id', 'marketProductSku', 'name', 'cost', 'price', 'stock',
        'bfit', 'mps_bfit', 'mp_bfit'
    ];


    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    public function shop_product()
    {
        return $this->belongsTo('App\ShopProduct');
    }

    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }


    // CUSTOM HELPERS

    public function getFirstImageFullUrl()
    {
        // ONLY WORKS with Product::filter($params)
        return Storage::url('img/' .$this->product_id. '/' .$this->image_src);
    }

    public function setBfitAttribute($value)
    {
        $this->attributes['bfit'] = number_format((float)round($value, 2), 2, '.', '');
    }

    public function setMpsBfitAttribute($value)
    {
        $this->attributes['mps_bfit'] = number_format((float)round($value, 2), 2, '.', '');
    }

    public function setMpBfitAttribute($value)
    {
        $this->attributes['mp_bfit'] = number_format((float)round($value, 2), 2, '.', '');
    }


    static function reset($days)
    {
        try {
            $deletedRows = Price::where('created_at', '<', now()->addDays(-$days)->format('Y-m-d H:i:s'))->delete();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return$deletedRows;
    }




    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        $query->select('prices.*',
            'prices.name as action_name',
            'products.ready as product_ready',
            'products.supplier_id as supplier_id',
            'products.brand_id as brand_id',
            'products.category_id as category_id',
            'products.supplierSku as supplierSku',
            'products.pn as pn',
            'products.ean as ean',
            'products.upc as upc',
            'products.isbn as isbn',
            'products.name as product_name',
            'shops.name as shop_name',
            'markets.name as market_name',
            'markets.product_url as market_product_url',
            'suppliers.name as supplier_name',
            'brands.name as brand_name',
            'categories.name as category_name',
            'images.src as image_src'
        )
            ->leftJoin('products', 'prices.product_id', '=', 'products.id')
            //->leftJoin('shop_products', 'prices.shop_product_id', '=', 'shop_products.id')
            ->leftJoin('shops', 'prices.shop_id', '=', 'shops.id')
            ->leftJoin('markets', 'markets.id', '=', 'shops.market_id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('images', 'images.id', '=', DB::raw('(SELECT id FROM images WHERE images.product_id = products.id LIMIT 1)'))
            ->groupBy('prices.id')
            ->groupBy('images.id');

        if ( isset($params['marketProductSku']) && $params['marketProductSku'] != null) {
            if ($params['marketProductSku'] == 'null') $params['marketProductSku'] = null;
            $query->where('marketProductSku', $params['marketProductSku']);
        }

        if ( isset($params['MPSSku']) && $params['MPSSku'] != null) {
            if ($shop_product = $this->shop->shop_products()->where('mps_sku', $params['MPSSku'])->first())
                $query->where('prices.product_id', $shop_product->product_id);
            /* else
                $query->where('prices.product_id', $this->getIdFromMPSSku($params['MPSSku'])); */
        }

        if (!isset($params['category_name'])) $params['category_id'] = null;
        if ( isset($params['category_id']) && $params['category_id'] != null) {
            $query->where('products.category_id', '=', $params['category_id']);
        }

        if (!isset($params['brand_name'])) $params['brand_id'] = null;
        if ( isset($params['brand_id']) && $params['brand_id'] != null && isset($params['brand_name'])) {
            $query->where('products.brand_id', '=', $params['brand_id']);
        }

        if ( isset($params['supplier_id']) && $params['supplier_id'] != null) {
            $query->where('products.supplier_id', $params['supplier_id']);
        }

        if ( isset($params['cost_min']) && $params['cost_min'] != null) {
            $query-> where('prices.cost', '>=', floatval($params['cost_min']));
        }
        if ( isset($params['cost_max']) && $params['cost_max'] != null) {
            $query-> where('prices.cost', '<=', floatval($params['cost_max']));
        }

        if ( isset($params['price_min']) && $params['price_min'] != null) {
            $query-> where('prices.price', '>=', floatval($params['price_min']));
        }
        if ( isset($params['price_max']) && $params['price_max'] != null) {
            $query-> where('prices.price', '<=', floatval($params['price_max']));
        }

        if ( isset($params['stock_min']) && $params['stock_min'] != null) {
            $query-> where('prices.stock', '>=', intval($params['stock_min']));
        }
        if ( isset($params['stock_max']) && $params['stock_max'] != null) {
            $query-> where('prices.stock', '<=', intval($params['stock_max']));
        }

        if ( isset($params['action_name']) && $params['action_name'] != null) {
            $query-> where('prices.name', $params['action_name']);
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

        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }


}
