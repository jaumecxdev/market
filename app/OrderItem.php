<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\OrderItem
 *
 * @property int $id
 * @property int|null $order_id
 * @property int|null $shop_product_id
 * @property int|null $currency_id
 * @property string|null $MpsSku
 * @property string|null $marketOrderId
 * @property string|null $marketItemId
 * @property string|null $name
 * @property string|null $info
 * @property int $quantity
 * @property float $price
 * @property float $tax
 * @property float $shipping_price
 * @property float $shipping_tax
 * @property-read \App\Currency|null $currency
 * @property-read \App\Order|null $order
 * @property-read \App\ShopProduct|null $shop_product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereMarketItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereMarketOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereMpsSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereShippingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereShippingTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereShopProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereTax($value)
 * @mixin \Eloquent
 * @property float $fee_mps
 * @property float $fee_mp
 * @property float $benefit_mps
 * @property float $cost
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereBenefitMps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereFeeMp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereFeeMps($value)
 * @property float $supplier_fee
 * @property float $supplier_bfit
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereMpFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereMpFeeAddon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereSupplierBfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereSupplierFee($value)
 * @property float $mp_bfit
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereMpBfit($value)
 * @property float $bfit
 * @method static \Illuminate\Database\Eloquent\Builder|\App\OrderItem whereBfit($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderShipment[] $order_shipments
 * @property-read int|null $shipments_count
 * @property-read int|null $order_shipments_count
 * @property int|null $product_id
 * @property string|null $marketProductSku
 * @property-read \App\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereMarketProductSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereProductId($value)
 * @property float $mps_bfit
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereMpsBfit($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderPayment[] $order_payments
 * @property-read int|null $order_payments_count
 */
class OrderItem extends Model
{
    protected $table = 'order_items';

    public $timestamps = false;

    protected $fillable = [
        'order_id', 'product_id', 'shop_product_id', 'currency_id', 'marketOrderId', 'marketItemId',
        'MpsSku', 'marketProductSku', 'name', 'info', 'quantity', 'cost', 'price', 'tax', 'shipping_price', 'shipping_tax',
        'bfit', 'mps_bfit', 'mp_bfit'
    ];


    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    public function shop_product()
    {
        return $this->belongsTo('App\ShopProduct');
    }

    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    public function currency()
    {
        return $this->belongsTo('App\Currency');
    }


    // MANY

    public function order_payments()
    {
        return $this->hasMany('App\OrderPayment');
    }

    public function order_shipments()
    {
        return $this->hasMany('App\OrderShipment');
    }



    // CUSTOM


    public function setCostAttribute($value)
    {
        $this->attributes['cost'] = number_format((float)round($value, 2), 2, '.', '');
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = number_format((float)round($value, 2), 2, '.', '');
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

}
