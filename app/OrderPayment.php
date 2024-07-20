<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\OrderPayment
 *
 * @property int $id
 * @property int|null $order_id
 * @property int|null $order_item_id
 * @property int|null $currency_id
 * @property int $fixed
 * @property float $cost
 * @property float $price
 * @property float $shipping_price
 * @property float $tax
 * @property float $bfit
 * @property float $mps_bfit
 * @property float $mp_bfit
 * @property int $charget
 * @property string|null $invoice
 * @property \Illuminate\Support\Carbon|null $payment_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Currency|null $currency
 * @property-read \App\Order|null $order
 * @property-read \App\OrderItem|null $order_item
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment filter($params)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereBfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereCharget($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereFixed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereInvoice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereMpBfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereMpsBfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereOrderItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment wherePaymentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereShippingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $invoice_mpe
 * @property float $invoice_mpe_price
 * @property \Illuminate\Support\Carbon|null $invoice_mpe_created_at
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereInvoiceMpe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereInvoiceMpeCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderPayment whereInvoiceMpePrice($value)
 */
class OrderPayment extends Model
{
    protected $table = 'order_payments';

    protected $dates = [
        'payment_at', 'invoice_mpe_created_at'
    ];

    protected $fillable = [
        'order_id', 'order_item_id', /* 'currency_id', */
        'fixed', 'cost', 'price', 'shipping_price', /* 'tax', */ 'bfit', 'mps_bfit ', 'mp_bfit',
        'charget', 'invoice', 'payment_at',
        'invoice_mpe', 'invoice_mpe_price', 'invoice_mpe_created_at'
    ];

    protected $status_id_ignoreds = [
        4,              // Pendiente de pago
        //15,             // Cerrado
        61, 68, 69,     // Cancelado
        32, 52, 64,     // Rechazado
        46, 70,         // Pago Cancelado
        41,             // Verificación de fraude negativa
    ];


    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    public function order_item()
    {
        return $this->belongsTo('App\OrderItem');
    }

    /* public function currency()
    {
        return $this->belongsTo('App\Currency');
    } */


    // SCOPES


    public function scopeFilter($query, $params)
    {
        $query->select('order_payments.*',
            /* 'markets.name as market_name', */
            'markets.order_url as market_order_url',
            /* 'shops.name as shop_name', */
            'orders.marketOrderId as marketOrderId',
            'order_items.marketItemId as marketItemId',
            'orders.created_at as order_created_at',
            'statuses.name as order_status_name',
            'currencies.code as currency_code',
            'buyers.name as buyer_name',
            DB::raw("CONCAT('(', markets.name, ') ', shops.name) AS market_shop_name")
        )
            ->leftJoin('orders', 'order_payments.order_id', '=', 'orders.id')
            ->leftJoin('order_items', 'order_payments.order_item_id', '=', 'order_items.id')
            ->leftJoin('markets', 'orders.market_id', '=', 'markets.id')
            ->leftJoin('shops', 'orders.shop_id', '=', 'shops.id')
            ->leftJoin('statuses', 'orders.status_id', '=', 'statuses.id')
            ->leftJoin('buyers', 'orders.buyer_id', '=', 'buyers.id')
            ->leftJoin('currencies', 'orders.currency_id', '=', 'currencies.id')

            ->whereNotIn('orders.status_id', $this->status_id_ignoreds);

            /* 'order_id', 'order_item_id', 'currency_id', 'buyer_id',
        'fixed', 'cost', 'price', 'shipping_price', 'tax', 'bfit', 'mps_bfit ', 'mp_bfit',
        'charget', 'invoice', 'payment_at',
        'invoice_mpe', 'invoice_mpe_price', 'invoice_mpe_created_at' */

        if ( isset($params['invoice']) && $params['invoice'] != null) {
            $query->where('order_payments.invoice', 'LIKE', '%' . $params['invoice'] . '%');
        }

        if ( isset($params['invoice_mpe']) && $params['invoice_mpe'] != null) {
            $query->where('order_payments.invoice_mpe', 'LIKE', '%' . $params['invoice_mpe'] . '%');
        }

        if ( isset($params['marketOrderId']) && $params['marketOrderId'] != null) {
            $query->where('orders.marketOrderId', 'LIKE', '%' . $params['marketOrderId'] . '%');
        }

        if ( isset($params['shop_id']) && $params['shop_id'] != null) {
            $query->where('orders.shop_id', $params['shop_id']);
        }

        if ( isset($params['status_id']) && $params['status_id'] != null) {
            $query->where('orders.status_id', $params['status_id']);
        }

        if (!isset($params['buyer_name'])) $params['buyer_id'] = null;
        if ( isset($params['buyer_id']) && $params['buyer_id'] != null) {
            $query->where('orders.buyer_id', $params['buyer_id']);
        }

        if ( isset($params['order_created_at_min']) && $params['order_created_at_min'] != null) {
            $query->whereDate('orders.created_at', '>=', $params['order_created_at_min']);
        }

        if ( isset($params['order_created_at_max']) && $params['order_created_at_max'] != null) {
            $query->whereDate('orders.created_at', '<=', $params['order_created_at_max']);
        }

        if ( isset($params['invoice_mpe_created_at_min']) && $params['invoice_mpe_created_at_min'] != null) {
            $query->whereDate('order_payments.invoice_mpe_created_at', '>=', $params['invoice_mpe_created_at_min']);
        }

        if ( isset($params['invoice_mpe_created_at_max']) && $params['invoice_mpe_created_at_max'] != null) {
            $query->whereDate('order_payments.invoice_mpe_created_at', '<=', $params['invoice_mpe_created_at_max']);
        }

        if (isset($params['charget'])) {
            $query->where('order_payments.charget', '=', 1);
        }

        if ( isset($params['payment_at_min']) && $params['payment_at_min'] != null) {
            $query->whereDate('order_payments.payment_at', '>=', $params['payment_at_min']);
        }

        if ( isset($params['payment_at_max']) && $params['payment_at_max'] != null) {
            $query->whereDate('order_payments.payment_at', '<=', $params['payment_at_max']);
        }



        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }



}
