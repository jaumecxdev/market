<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Brand
 *
 * @property int $id
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierFilter[] $supplier_filters
 * @property-read int|null $supplier_filters_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Brand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Brand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Brand query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Brand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Brand whereName($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketParam[] $market_params
 * @property-read int|null $market_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierParam[] $supplier_params
 * @property-read int|null $supplier_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\FeeParam[] $fee_params
 * @property-read int|null $fee_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopParam[] $shop_params
 * @property-read int|null $shop_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketBrand[] $market_brands
 * @property-read int|null $market_brands_count
 * @method static Builder|Brand filter($params)
 */
class Brand extends Model
{
    protected $table = 'brands';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];


    // MANY

    public function market_params()
    {
        return $this->hasMany('App\MarketParam');
    }

    public function products()
    {
        return $this->hasMany('App\Product');
    }

    public function shop_params()
    {
        return $this->hasMany('App\ShopParam');
    }

    public function supplier_filters()
    {
        return $this->hasMany('App\SupplierFilter');
    }

    public function supplier_params()
    {
        return $this->hasMany('App\SupplierParam');
    }


    // MANY TO MANY

    public function market_brands()
    {
        return $this->belongsToMany('App\MarketBrand');
    }


    public function market_brand($market_id = null)
    {
        return $this->belongsToMany('App\MarketBrand')->where('market_id', $market_id);
    }



    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        $query->select('brands.*');

        if (!isset($params['brand_name'])) $params['brand_id'] = null;
        if ( isset($params['brand_id']) && $params['brand_id'] != null) {
            $query->where('brand.id', $params['brand_id']);
        }

        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }

}
