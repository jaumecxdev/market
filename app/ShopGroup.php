<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ShopGroup
 *
 * @property int $id
 * @property int|null $shop_id
 * @property int|null $group_id
 * @property int|null $market_category_id
 * @property-read \App\Group|null $group
 * @property-read \App\MarketCategory|null $market_category
 * @property-read \App\Shop|null $shop
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopGroup whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopGroup whereMarketCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopGroup whereShopId($value)
 * @mixin \Eloquent
 */
class ShopGroup extends Model
{
    protected $table = 'shop_groups';

    public $timestamps = false;

    protected $fillable = [
        'shop_id', 'group_id', 'market_category_id'
    ];


    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }

    public function group()
    {
        return $this->belongsTo('App\Group');
    }

    public function market_category()
    {
        return $this->belongsTo('App\MarketCategory');
    }

}
