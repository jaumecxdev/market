<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\MarketCarrier
 *
 * @property int $id
 * @property int|null $market_id
 * @property string|null $name
 * @property string|null $code
 * @property string|null $url
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderShipment[] $order_shipments
 * @property-read int|null $shipments_count
 * @method static \Illuminate\Database\Eloquent\Builder|MarketCarrier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MarketCarrier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MarketCarrier query()
 * @method static \Illuminate\Database\Eloquent\Builder|MarketCarrier whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketCarrier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketCarrier whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketCarrier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketCarrier whereUrl($value)
 * @mixin \Eloquent
 * @property-read int|null $order_shipments_count
 */
class MarketCarrier extends Model
{
    protected $table = 'market_carriers';

    public $timestamps = false;

    protected $fillable = [
        'market_id', 'name', 'code', 'url'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }


    // MANY

    public function order_shipments()
    {
        return $this->hasMany('App\OrderShipment');
    }

}
