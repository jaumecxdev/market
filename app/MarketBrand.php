<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\MarketBrand
 *
 * @property int $id
 * @property int|null $market_id
 * @property string $name
 * @property string|null $marketBrandId
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Brand[] $brands
 * @property-read int|null $brands_count
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopFilter[] $shop_filters
 * @property-read int|null $shop_filters_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopProduct[] $shop_products
 * @property-read int|null $shop_products_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketBrand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketBrand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketBrand query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketBrand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketBrand whereMarketBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketBrand whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketBrand whereName($value)
 * @mixin \Eloquent
 */
class MarketBrand extends Model
{
    protected $table = 'market_brands';

    public $timestamps = false;

    protected $fillable = [
        'market_id', 'name', 'marketBrandId'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }


    // MANY

    public function shop_filters()
    {
        return $this->hasMany('App\ShopFilter');
    }

    public function shop_products()
    {
        return $this->hasMany('App\ShopProduct');
    }


    // MANY TO MANY

    public function brands()
    {
        return $this->belongsToMany('App\Brand');
    }


}
