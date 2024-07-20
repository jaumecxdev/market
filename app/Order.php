<?php

namespace App;

use App\Models\Guest\GuestService;
use App\Traits\HelperTrait;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * App\Order
 *
 * @property int $id
 * @property int|null $market_id
 * @property int|null $shop_id
 * @property int|null $buyer_id
 * @property int|null $shipping_address_id
 * @property int|null $billing_address_id
 * @property int|null $currency_id
 * @property int|null $status_id
 * @property int|null $type_id
 * @property string|null $marketOrderId
 * @property string|null $SellerId
 * @property string|null $SellerOrderId
 * @property string|null $info
 * @property float $price
 * @property float $tax
 * @property float $shipping_price
 * @property float $shipping_tax
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Address|null $billing_address
 * @property-read \App\Buyer|null $buyer
 * @property-read \App\Currency|null $currency
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $order_items
 * @property-read int|null $order_items_count
 * @property-read \App\Address|null $shipping_address
 * @property-read \App\Shop|null $shop
 * @property-read \App\Status|null $status
 * @property-read \App\Type|null $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order filter($params)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereBillingAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereBuyerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereMarketOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereSellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereSellerOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereShippingAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereShippingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereShippingTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $notified
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereNotified($value)
 * @property int $notified_updated
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Order whereNotifiedUpdated($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderShipment[] $order_shipments
 * @property-read int|null $shipments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderComment[] $order_comments
 * @property-read int|null $order_comments_count
 * @property-read int|null $order_shipments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopMessage[] $shop_messages
 * @property-read int|null $shop_messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderPayment[] $order_payments
 * @property-read int|null $order_payments_count
 */
class Order extends Model
{
    use HelperTrait;

    protected $table = 'orders';

    protected $fillable = [
        'market_id', 'shop_id', 'buyer_id', 'shipping_address_id', 'billing_address_id', 'currency_id', 'status_id', 'type_id', 'marketOrderId',
        'SellerId', 'SellerOrderId', 'info', 'price', 'tax', 'shipping_price', 'shipping_tax',
        'notified', 'notified_updated'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }

    public function buyer()
    {
        return $this->belongsTo('App\Buyer');
    }

    public function shipping_address()
    {
        return $this->belongsTo('App\Address');
    }

    public function billing_address()
    {
        return $this->belongsTo('App\Address');
    }

    public function currency()
    {
        return $this->belongsTo('App\Currency');
    }

    public function status()
    {
        return $this->belongsTo('App\Status');
    }

    public function type()
    {
        return $this->belongsTo('App\Type');
    }



    // MANY

    public function order_items()
    {
        return $this->hasMany('App\OrderItem');
    }

    public function order_comments()
    {
        return $this->hasMany('App\OrderComment');
    }

    public function order_payments()
    {
        return $this->hasMany('App\OrderPayment');
    }

    public function order_shipments()
    {
        return $this->hasMany('App\OrderShipment');
    }

    public function shop_messages()
    {
        return $this->hasMany('App\ShopMessage');
    }



    // CUSTOM

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = number_format((float)round($value, 2), 2, '.', '');
    }


    public function updateOrCreateOrderItem($marketItemId, $MpsSku, $marketProductSku, $product_name, $quantity,
        $price, $tax, $shipping_price, $shipping_tax, $info, $extra = [])
    {
        try {
            if ($shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first())
                $product = $shop_product->product;
            elseif ($shop_product = $this->shop->shop_products()->where('mps_sku', $MpsSku)->first())
                $product = $shop_product->product;
            else
                $product = Product::find(FacadesMpe::getIdFromMPSSku($MpsSku));

            // UPDATE ORDER_ITEM
            if ($marketItemId && $order_item = $this->order_items()->where('marketItemId', $marketItemId)->first()) {

                $order_item->info = $info;
                //if ($quantity && !$order_item->quantity) $order_item->quantity = $quantity;
                $order_item->quantity = $quantity;
                //if ($price && !$order_item->price) $order_item->price = $price;
                $order_item->price = $price;
                //if ($tax && !$order_item->tax) $order_item->tax = $tax;
                $order_item->tax = $tax;
                //if ($shipping_price && !$order_item->shipping_price) $order_item->shipping_price = $shipping_price;
                $order_item->shipping_price = $shipping_price;
                //if ($shipping_tax && !$order_item->shipping_tax) $order_item->shipping_tax = $shipping_tax;
                $order_item->shipping_tax = $shipping_tax;

                $param_fee = $shop_product->param_fee ?? 0;
                $param_mps_fee = $shop_product->param_mps_fee ?? 0;
                $param_bfit_min = $shop_product->param_bfit_min ?? 0;
                $param_mp_bfit_min = $shop_product->param_mp_bfit_min ?? 0;

                $mp_fee = $extra['mp_fee'] ?? $shop_product->param_mp_fee ?? 0;
                $mp_fee_addon = $extra['mp_fee_addon'] ?? $shop_product->param_mp_fee_addon ?? 0;
                $param_mp_lot = $shop_product->param_mp_lot ?? 0;
                $param_mp_lot_fee = $shop_product->param_mp_lot_fee ?? 0;

                $bfit = $shop_product->bfit ??
                    FacadesMpe::getClientBfitV2($price, $param_fee, $param_bfit_min, 0, 0, $tax);
                $mps_bfit = $shop_product->mps_bfit ??
                    FacadesMpe::getMpsBfitV2($price+$shipping_price, $param_fee, $param_mps_fee, $param_bfit_min, 0, 0);
                $mp_bfit = $extra['mp_bfit'] ?? $shop_product->mp_bfit ??
                    FacadesMpe::getMarketBfitV2($price, $mp_fee, $mp_fee_addon, $param_mp_bfit_min, $param_mp_lot, $param_mp_lot_fee);

                if (isset($extra['mp_shipping_bfit'])) $mp_bfit += $extra['mp_shipping_bfit'];

                if (!$order_item->bfit) $order_item->bfit = $bfit;
                if (!$order_item->mps_bfit) $order_item->mps_bfit = $mps_bfit;
                if (!$order_item->mp_bfit) $order_item->mp_bfit = $mp_bfit;

                $order_item->save();
            }
            // CREATE NEW ORDER_ITEM
            else {
                // Get mp_fee_real
                $cost = $product->cost ?? 0;
                $param_fee = $shop_product->param_fee ?? 0;
                $param_mps_fee = $shop_product->param_mps_fee ?? 0;
                $param_bfit_min = $shop_product->param_bfit_min ?? 0;
                $param_mp_bfit_min = $shop_product->param_mp_bfit_min ?? 0;

                $tax = $tax ?? $product->tax ?? 21;
                $shipping_tax = $shipping_tax ?? $product->tax ?? 21;

                $mp_fee = $extra['mp_fee'] ?? $shop_product->param_mp_fee ?? 0;
                $mp_fee_addon = $extra['mp_fee_addon'] ?? $shop_product->param_mp_fee_addon ?? 0;
                $param_mp_lot = $shop_product->param_mp_lot ?? 0;
                $param_mp_lot_fee = $shop_product->param_mp_lot_fee ?? 0;

                $bfit = $shop_product->bfit ??
                    FacadesMpe::getClientBfitV2($price, $param_fee, $param_bfit_min, 0, 0, $tax);
                $mps_bfit = $shop_product->mps_bfit ??
                    FacadesMpe::getMpsBfitV2($price+$shipping_price, $param_fee, $param_mps_fee, $param_bfit_min, 0, 0);
                $mp_bfit = $extra['mp_bfit'] ?? $shop_product->mp_bfit ??
                    FacadesMpe::getMarketBfitV2($price, $mp_fee, $mp_fee_addon, $param_mp_bfit_min, $param_mp_lot, $param_mp_lot_fee);

                if (isset($extra['mp_shipping_bfit'])) $mp_bfit += $extra['mp_shipping_bfit'];

                $order_item = OrderItem::updateOrCreate([
                    'order_id'          => $this->id,
                    'marketOrderId'     => $this->marketOrderId,
                    'marketItemId'      => $marketItemId,
                ],[
                    'product_id'        => $product->id ?? null,
                    'marketProductSku'  => $marketProductSku,
                    'currency_id'       => $this->currency_id,
                    'MpsSku'            => $MpsSku,
                    'name'              => $product_name,
                    'info'              => $info,
                    'quantity'          => $quantity,
                    'price'             => $price,
                    'tax'               => $tax,
                    'shipping_price'    => $shipping_price,
                    'shipping_tax'      => $shipping_tax,

                    'cost'              => $cost,
                    'bfit'              => $bfit,
                    'mps_bfit'          => $mps_bfit,
                    'mp_bfit'           => $mp_bfit,
                ]);

                // NEVER CHANGE ORDER ITEM SUPPLIER -> NO change Product_id || NO Change MpsSku || NO Change marketProductSku???
                //$order_item->product_id = $order_item->product_id ?? $product->id ?? null;
                //$order_item->MpsSku = $order_item->MpsSku ?? $MpsSku;
                //$order_item->marketProductSku = $order_item->marketProductSku ?? $marketProductSku;
                //$order_item->save();
            }

            return $order_item;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$marketItemId, $MpsSku, $marketProductSku, $product_name, $quantity,
                $price, $tax, $shipping_price, $shipping_tax, $info, $extra]);
        }
    }


    public function getTrackUrl($supplier_id)
    {
        $guest_service = GuestService::firstWhere('supplier_id', $supplier_id);
        $token = $guest_service ? $guest_service->token : '';
        return route('guest_service.order.track', [$this, 'token' => $token]);
    }


    public function sendNotifications($notified_type)
    {
        $count = 0;
        Log::channel('commands')->info('send:notifications - Order: ' .$this->toJson());
        $shop_name = '(' .$this->market->name. ') ' .$this->shop->name;

        $supplier_items = [];
        foreach ($this->order_items as $order_item) {
            if ($supplier = $order_item->product->supplier ?? null)
                $supplier_items[$supplier->id] = isset($supplier_items[$supplier->id]) ?
                    $supplier_items[$supplier->id]->push($order_item) :
                    collect([$order_item]);
        }

        // SEND NOTIFICATIONS TO SUPPLIERS
        if (count($supplier_items)) {

            foreach ($supplier_items as $supplier_id => $order_items) {

                $supplier = Supplier::find($supplier_id);
                foreach ($supplier->receivers as $receiver) {
                    if ($receiver->is_notificable()) {
                        $receiver->notified_type = $notified_type;
                        Log::channel('commands')->info('send:notifications - Receiver: ' .$receiver->toJson());
                        Log::channel('commands')->info('send:notifications - Shop: '.json_encode([$shop_name => $this->marketOrderId]));
                        $notification_class = 'App\Notifications\\' .$receiver->class;
                        if ($instance = new $notification_class($this, $order_items, $this->getTrackUrl($supplier->id))) {
                            $receiver->notify($instance);
                            $count++;
                        }
                    }
                }
            }
        }

        if ($admin_receivers = Receiver::whereNull('supplier_id')->get()) {
            foreach ($admin_receivers as $receiver) {
                if ($receiver->is_notificable()) {
                    $receiver->notified_type = $notified_type;
                    Log::channel('commands')->info('send:notifications - Admin Receiver: ' .$receiver->toJson());
                    Log::channel('commands')->info('send:notifications - Shop: '.json_encode([$shop_name => $this->marketOrderId]));
                    $notification_class = 'App\Notifications\\' .$receiver->class;
                    if ($instance = new $notification_class($this, $this->order_items, $this->getTrackUrl(null))) {
                        $receiver->notify($instance);
                        $count++;
                    }
                }
            }
        }

        $this->notified = true;
        $this->notified_updated = true;
        $this->save();

        return $count;
    }


    static function getProductIdsTodayOrders()
    {
        try {
            return OrderItem::select('order_items.product_id')  //, DB::raw('count(*) as count'))
                ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereDate('orders.created_at', '=', now())
                ->groupBy('order_items.product_id')
                ->get()->pluck('product_id')->toArray();

        } catch (Throwable $th) {
            Storage::append('errors/'.date('Y-m-d_H').'_getProductIdsTodayOrders.json',
                json_encode([$th->getMessage(), $th->getCode(), $th->getTrace()]));
            return [];
        }
    }


    static function getProductIdTodayOrdersCount($product_id)
    {
        try {
            return OrderItem::select('order_items.product_id')  //, DB::raw('count(*) as count'))
                ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereDate('orders.created_at', '=', now())
                ->where('order_items.product_id', $product_id)
                ->count();

        } catch (Throwable $th) {
            Storage::append('errors/'.date('Y-m-d_H').'_getProductIdTodayOrdersCount.json',
                json_encode([$th->getMessage(), $th->getCode(), $product_id, $th->getTrace()]));
            return [];
        }
    }



    // SCOPES


    public function scopeFilter($query, $params)
    {
        $query->select('orders.*',
            'markets.name as market_name',
            'markets.order_url as market_order_url',
            'shops.name as shop_name',
            'buyers.name as buyer_name',
            'statuses.name as status_name',
            'currencies.code as currency_code',
            DB::raw("CONCAT('(', markets.name, ') ', shops.name) AS market_shop_name"),
            DB::raw("CONVERT(orders.updated_at, DATE) AS updated_at_date")
        )
            ->leftJoin('markets', 'orders.market_id', '=', 'markets.id')
            ->leftJoin('shops', 'orders.shop_id', '=', 'shops.id')
            ->leftJoin('buyers', 'orders.buyer_id', '=', 'buyers.id')
            ->leftJoin('statuses', 'orders.status_id', '=', 'statuses.id')
            ->leftJoin('currencies', 'orders.currency_id', '=', 'currencies.id');

        if ( isset($params['marketOrderId']) && $params['marketOrderId'] != null) {
            $query->where('marketOrderId', 'LIKE', '%' . $params['marketOrderId'] . '%');
        }

        if ( isset($params['shop_id']) && $params['shop_id'] != null) {
            $query->where('shop_id', $params['shop_id']);
        }

        if ( isset($params['shops_id']) && $params['shops_id'] != null) {
            $query->whereIn('shop_id', $params['shops_id']);
        }

        if ( isset($params['status_id']) && $params['status_id'] != null) {
            $query->where('status_id', $params['status_id']);
        }

        if ( isset($params['buyer_name']) && $params['buyer_name'] != null) {
            $query->where('buyers.name', 'LIKE', '%' . $params['buyer_name'] . '%');
        }

        if ( isset($params['updated_at']) && $params['updated_at'] != null) {
            $query->whereDate('updated_at', $params['updated_at_operator'], $params['updated_at']);
        }

        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }


}
