<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Group
 *
 * @property int $id
 * @property int|null $market_id
 * @property string|null $name
 * @property string|null $marketGroupId
 * @property string|null $marketGroupParentId
 * @property int|null $shop_id
 * @property-read \App\Market|null $market
 * @property-read \App\Shop|null $shop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopGroup[] $shop_groups
 * @property-read int|null $shop_groups_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Group query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Group whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Group whereMarketGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Group whereMarketGroupParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Group whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Group whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Group whereShopId($value)
 * @mixin \Eloquent
 */
class Group extends Model
{
    protected $table = 'groups';

    public $timestamps = false;

    protected $fillable = [
        'market_id', 'shop_id', 'name', 'marketGroupId', 'marketGroupParentId'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }


    // MANY

    public function shop_groups()
    {
        return $this->hasMany('App\ShopGroup');
    }

}
