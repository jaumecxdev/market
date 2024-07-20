<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\OrderShipment
 *
 * @property int $id
 * @property int|null $market_id
 * @property int|null $shop_id
 * @property int|null $market_carrier_id
 * @property int|null $order_id
 * @property int|null $order_item_id
 * @property int $full
 * @property int $quantity
 * @property string|null $tracking
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Market|null $market
 * @property-read \App\MarketCarrier|null $market_carrier
 * @property-read \App\Order|null $order
 * @property-read \App\OrderItem|null $order_item
 * @property-read \App\Shop|null $shop
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereFull($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereMarketCarrierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereTracking($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $desc
 * @method static \Illuminate\Database\Eloquent\Builder|OrderShipment whereDesc($value)
 */
class OrderShipment extends Model
{
    protected $table = 'order_shipments';

    protected $fillable = [
        'market_id', 'shop_id', 'market_carrier_id', 'order_id', 'order_item_id', 'full', 'quantity', 'tracking', 'desc'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }

    public function market_carrier()
    {
        return $this->belongsTo('App\MarketCarrier');
    }

    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    public function order_item()
    {
        return $this->belongsTo('App\OrderItem');
    }


}
