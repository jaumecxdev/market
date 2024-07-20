<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Type
 *
 * @property int $id
 * @property int|null $market_id
 * @property string $name
 * @property string|null $code
 * @property string|null $type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Attribute[] $attributes
 * @property-read int|null $attributes_count
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketAttribute[] $market_attributes
 * @property-read int|null $market_attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopFilter[] $shop_filters
 * @property-read int|null $shop_filters_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierFilter[] $supplier_filters
 * @property-read int|null $supplier_filters_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Type newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Type newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Type query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Type whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Type whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Type whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Type whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Type whereType($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopMessage[] $shop_messages
 * @property-read int|null $shop_messages_count
 */
class Type extends Model
{
    protected $table = 'types';

    public $timestamps = false;

    protected $fillable = [
        'market_id', 'name', 'code', 'type'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }


    // MANY

    public function attributes()
    {
        return $this->hasMany('App\Attribute');
    }

    public function market_attributes()
    {
        return $this->hasMany('App\MarketAttribute');
    }

    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    public function products()
    {
        return $this->hasMany('App\Product');
    }

    public function shop_filters()
    {
        return $this->hasMany('App\ShopFilter');
    }

    public function shop_messages()
    {
        return $this->hasMany('App\ShopMessage');
    }

    public function supplier_filters()
    {
        return $this->hasMany('App\SupplierFilter');
    }

}
