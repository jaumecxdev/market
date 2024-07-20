<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\OrderComment
 *
 * @property-read \App\Market $market
 * @property-read \App\Order $order
 * @property-read \App\Shop $shop
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment query()
 * @mixin \Eloquent
 * @property int $id
 * @property int|null $market_id
 * @property int|null $shop_id
 * @property int|null $order_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereUpdatedAt($value)
 */
class OrderComment extends Model
{
    protected $table = 'order_comments';

    protected $fillable = [
        'market_id', 'shop_id', 'order_id', 'comment'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }

    public function order()
    {
        return $this->belongsTo('App\Order');
    }


}
