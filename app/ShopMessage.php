<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\ShopMessage
 *
 * @property int $id
 * @property int|null $market_id
 * @property int|null $shop_id
 * @property int|null $order_id
 * @property int|null $type_id
 * @property string|null $messageId
 * @property string|null $subject
 * @property string $message
 * @property string|null $seller
 * @property string|null $buyer
 * @property string|null $attachments
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Market|null $market
 * @property-read \App\Order|null $order
 * @property-read \App\Shop|null $shop
 * @property-read \App\Type|null $type
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereBuyer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereSeller($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopMessage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ShopMessage extends Model
{
    protected $table = 'shop_messages';

    protected $fillable = [
        'market_id', 'shop_id', 'order_id', 'type_id',
        'messageId', 'subject', 'message', 'seller', 'buyer', 'attachments'
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

    public function type()
    {
        return $this->belongsTo('App\Type');
    }




}
