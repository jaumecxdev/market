<?php

namespace App\Libraries;


use App\Address;
use App\Buyer;
use App\Country;
use App\Currency;
use App\MarketCarrier;
use App\Order;
use App\OrderComment;
use App\OrderItem;
use App\Shop;
use App\ShopProduct;
use App\Status;
use App\Traits\HelperTrait;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;


class MagentoWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    private $client = null;
    private $refresh_token_steps = 3;

    private $shipping_time = '2-5';
    private $paypal_fee = 0.029;
    private $paypal_fee_addon = 0.35;

    const DEFAULT_CONFIG = [
        // MarketWS
        'header' => null,
        'header_rows' => 1,
        'order_status_ignored' => ['CancelPending'],
        'errors_ignored' => ['21915465', '21920270', '21919189', '21917091'],
        'publish_packs' => [
            'enabled' => true,
            'values' => [2, 10, 50]
        ],
        'functions' => [
            'getBrands'             => false,
            'getCategories'         => true,
            'getAttributes'         => true,
            'getItemRowProduct'     => true,
            'getItemRowOffer'       => true,
            'getItemRowPromo'       => true,
            'getFeed'               => true,
            'getJobs'               => true,
            'getOrders'             => true,
            'getGroups'             => false,
            'getCarriers'           => true,
            'getOrderComments'      => false,
            'postNewProduct'        => true,
            'postUpdatedProduct'    => true,
            'postPriceProduct'      => true,
            'postNewProducts'       => true,
            'postUpdatedProducts'   => true,
            'postPricesStocks'      => true,
            'synchronize'           => true,
            'postGroups'            => false,
            'removeProduct'         => true,
            'postOrderTrackings'    => true,
            'postOrderComment'      => false,
        ],
        'locale' => 'es_ES',
    ];


   

   

    public function __construct(Shop $shop)
    {
        parent::__construct($shop);
        /* $this->storage_dir .= $shop->market->code.'/';
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir); */

        // BANKIA
        // title sería el título que quieres poner al envío (pj Correos Express)
        // carrier_code sería la agencia de transporte que utilizáis (correos express en minúsculas)

        //$this->shipping_time = $shop->shipping;

        //$this->client = new Client(['base_uri' => 'http://localhost:8080/mage/']);   //$shop->endpoint]);

        // DEMO LOCAL DATA
        //$this->shop->client_id = '';
        //$this->shop->client_secret = '';      
        //$this->shop->endpoint = 'http://localhost:8080/mage/';

        $this->client = new Client(['base_uri' => $this->shop->endpoint]);
    }


    public function getToken()
    {
        try {
            if ($this->refresh_token_steps) {
                $this->refresh_token_steps--;

                $response = $this->client->post('rest/V1/integration/admin/token', [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        //'Authorization' => 'Bearer ' .$this->shop->token,
                    ],
                    'json' => [
                        'username'  => $this->shop->client_id,
                        'password'  => $this->shop->client_secret,
                    ],
                ]);

                if ($response->getStatusCode() == '200') {
                    $contents = $response->getBody()->getContents();

                    Storage::append($this->storage_dir. 'tokens/' .date('Y-m-d'). '.json', $contents);
                    $json_res = json_decode($contents);

                    $this->shop->token = $json_res;
                    $this->shop->save();

                    return $json_res;
                }
            }
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_tokens.json', $th->getMessage());
        }

        return null;
    }



    /************** PRIVATE FUNCTIONS - TOKEN ***************/



    /************** PRIVATE FUNCTIONS - ORDERS ***************/


    private function firstOrCreateAddress($country_id, $customer_email, $json_address)
    {
        return Address::firstOrCreate([
            'country_id'            => $country_id,
            'market_id'             => $this->market->id,
            'name'                  => $json_address->firstname. ' ' .$json_address->lastname,
            'phone'                 => $json_address->telephone ?? null,
            'marketBuyerId'         => $customer_email,
            'address1'              => $json_address->street[0] ?? null,
        ],[
            'address2'              => $json_address->street[1] ?? null,
            'address3'              => $json_address->street[3] ?? null,
            'zipcode'               => $json_address->postcode ?? null,
            'city'                  => $json_address->city ?? null,
            'state'                 => $json_address->region ?? null,
        ]);
    }


    private function updateOrCreateOrder($item)
    {
        try {
            Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_order_'.$item->increment_id.'.json', json_encode($item));

            $country = null;
            $billing_address = null;
            if ($json_billing_address = $item->billing_address) {

                $country = Country::firstOrCreate([
                    'code'  => $json_billing_address->country_id,
                ],[
                    'name'  => $json_billing_address->country_id,
                ]);

                $billing_address = $this->firstOrCreateAddress($country->id ?? null, $item->customer_email, $json_billing_address);
            }

            $shipping_address = null;
            $json_shipping_address = $item->extension_attributes->shipping_assignments[0]->shipping->address ?? null;
            if (isset($json_shipping_address))
                $shipping_address = $this->firstOrCreateAddress($country->id ?? null, $item->customer_email, $json_shipping_address);

            $buyer = Buyer::firstOrCreate([
                'market_id'             => $this->market->id,
                'name'                  => $item->customer_firstname. ' ' .$item->customer_lastname,
                'marketBuyerId'         => null,
                'company_name'          => $json_billing_address->company ?? $json_shipping_address->company ?? null,
            ],[
                'email'                 => $item->customer_email ?? null,
                'phone'                 => $billing_address->phone ?? $shipping_address->phone ?? null,
                'shipping_address_id'   => $shipping_address->id ?? null,
                'billing_address_id'    => $billing_address->id ?? null,
            ]);

            // 'supplier_id', 'market_id', 'name', 'supplierStatusName', 'marketStatusName', 'type'
            $status = Status::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketStatusName'      => $item->status,   // 'processing';  ->state: 'complete'
                'type'                  => 'order',
            ],[
                'name'                  => $item->status,
            ]);

            $currency = null;
            $currency_code = $item->order_currency_code ?? $item->store_currency_code ?? $item->base_currency_code ?? $item->global_currency_code ?? null;
            if (isset($currency_code))
                $currency = Currency::firstOrCreate([
                    'code'      => $currency_code,
                ],[
                    'name'      => $currency_code,
                ]);

            $order = $this->shop->orders()->where('marketOrderId', $item->increment_id)->first();
            $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $order = Order::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'marketOrderId'         => $item->entity_id,        // increment_id ?? $item->protect_code,
            ],[
                'buyer_id'              => $buyer->id ?? null,
                'shipping_address_id'   => $shipping_address->id ?? null,
                'billing_address_id'    => $billing_address->id ?? null,
                'currency_id'           => $currency->id ?? null,
                'status_id'             => $status->id ?? null,
                'type_id'               => null,
                'SellerId'              => $item->customer_email,
                'SellerOrderId'         => null,
                'info'                  => isset($item->payment->additional_information[0]) ? mb_substr($item->payment->additional_information[0], 0, 255) : null,
                'price'                 => $item->grand_total,
                'tax'                   => $item->tax_amount,
                'shipping_price'        => $item->shipping_incl_tax,
                'shipping_tax'          => $item->shipping_tax_amount,
                'notified'              => $notified,
                'notified_updated'      => $notified_updated,
            ]);

            $order->created_at = $item->created_at;
            $order->updated_at = $item->updated_at;
            $order->save();

            // Order Items
            foreach ($item->items as $json_item) {
                // ITEM SKU de MPe: $json_item->sku: "iPhone Xs Max 64GB-Gris espacial-1"
                // ITEM SKU de Bankia: $json_item->product_id: 4
                $shop_product = $this->shop->shop_products()
                    ->whereMpsSku($json_item->sku)
                    ->first();

                /* $price = $json_item->price_incl_tax ?? $json_item->base_price_incl_tax ?? $json_item->price ?? 0;
                $bfit = $mp_bfit = 0;
                if ($shop_product) {
                    $bfit = $this->getBfit($price,
                        $shop_product->param_fee,
                        $shop_product->param_bfit_min,
                        $shop_product->tax);

                    $mp_bfit = $this->getMarketBfit($price,
                        $shop_product->param_mp_fee,
                        $shop_product->param_mp_fee_addon,
                        $shop_product->tax);
                } */

                $order_item = $order->updateOrCreateOrderItem(
                    $json_item->item_id,
                    $json_item->sku,
                    $shop_product->marketProductSku ?? null,
                    $json_item->name,
                    $json_item->qty_ordered,
                    $json_item->price_incl_tax ?? $json_item->base_price_incl_tax ?? $json_item->price ?? 0,
                    $json_item->base_tax_amount ?? $json_item->tax_amount ?? 0,
                    ($json_item->free_shipping == 0) ? 0 : 0,
                    ($json_item->free_shipping == 0) ? 0 : null,
                    $json_item->product_type ?? null);

                /* $order_item = OrderItem::updateOrCreate([
                    'order_id'          => $order->id ?? null,
                    'product_id'        => $shop_product->product->id ?? null,
                    'marketOrderId'     => $json_item->order_id,
                    'marketItemId'      => $json_item->item_id,
                ],[
                    'marketProductSku'  => $shop_product->marketProductSku,
                    'currency_id'       => $currency->id ?? null,
                    'MpsSku'            => $json_item->sku,
                    'name'              => $json_item->name,
                    'info'              => $json_item->product_type ?? null,
                    'quantity'          => $json_item->qty_ordered,
                    'price'             => $price,
                    'tax'               => $json_item->base_tax_amount ?? $json_item->tax_amount ?? 0,
                    'shipping_price'    => ($json_item->free_shipping == 0) ? 0 : null,
                    'shipping_tax'      => ($json_item->free_shipping == 0) ? 0 : null,

                    'cost'              => $shop_product->getCost() ?? 0,
                    'bfit'              => $bfit,
                    'mp_bfit'           => $mp_bfit,
                ]); */
            }

            return $order;
        }
        catch (Throwable $th) {

            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_updateOrCreateOrder.json', $th->getMessage());
        }

        return null;
    }


    private function getV1Orders()
    {
        try {
            $query = [
                /* 'searchCriteria[filter_groups][0][filters][0][field]'           => 'state',
                'searchCriteria[filter_groups][0][filters][0][value]'           => 'pending',
                'searchCriteria[filter_groups][0][filters][0][condition_type]'  => 'eq', */
                //'proveedor'                                                     => 'VDSHOP',

                'searchCriteria'    => 'All',
                //'searchCriteria'    => 'proveedor='.$this->shop->marketSellerId,
                'proveedor'         => $this->shop->marketSellerId,
            ];
            $response = $this->client->get('rest/V1/orders', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                    //'Content-Type'  => 'application/json',
                ],
                'query' => $query,
            ]);

            Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_StatusCode.txt', $response->getStatusCode());
            Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_StatusCode.txt', json_encode($response->getHeaders()));

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_getV1Orders.json', $contents);
                $json_res = json_decode($contents);

                // success
                if ($json_res->total_count != 0) {          // 548
                    $count = 0;
                    //$items = null;
                    // $items_array = json_decode($json_res->items, true);
                    //$items_array = array_reverse($json_res->items, true);
                    foreach ($json_res->items as $item) {

                        //$items[] = $item;
                        $this->updateOrCreateOrder($item);
                        //$count++;
                        //if ($count > 10) break;
                    }
                }



                return $json_res->total_count;
            }

            return $response->getStatusCode();
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getV1Orders.json', json_encode([$this->refresh_token_steps, $th->getMessage()]));
            // 401 Unauthorized -> GetToken
            if ($th->getCode() == '401') {
                $token = $this->getToken();
                if (isset($token)) return $this->getV1Orders();
            }
            else
                return $th->getMessage();
        }
    }


    /************** PRIVATE FUNCTIONS - ORDERS SHIPMENTS ***************/


    private function postMagentoTrackings(Order $order, $shipment_data)
    {
        // CREAR CAMPS marketShipId && marketTrackId
        try {
            $order_items = (isset($shipment_data['order_item_id'])) ?
                (new Collection( [OrderItem::find($shipment_data['order_item_id'])] )) :
                $order->order_items;

            $json_items = null;
            foreach($order_items as $order_item) {
                $json_items[] = [
                    'order_item_id'     => $order_item->marketItemId,
                    'qty'               => $order_item->quantity,
                ];
            }

            $json_tracks = null;
            $market_carrier = MarketCarrier::find($shipment_data['market_carrier_id']);
            for ($i=0; $i<$order_items->count(); $i++) {
                $json_tracks[] = [
                    'track_number'     => $shipment_data['tracking'],
                    'title'            => $market_carrier->name,        // BANKIA: Correos Express
                    'carrier_code'     => $market_carrier->code,        // BANKIA: correos express
                ];
            }

            /* $order->marketOrderId = '1';
            $json_items[] = [
                'order_item_id'     => 1,
                'qty'               => 1,
            ]; */




            // ADD SHIPMENT TRACK TO ORDER ITEM
            // post('rest/V1/order/' .$order->marketOrderId. '/ship',
            $response = $this->client->post('rest/V1/order/' .$order->marketOrderId. '/ship', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'json' => [
                    'items'     => $json_items,
                    'tracks'    => $json_tracks
                ],
            ]);

            // DELETE TRACK_ID
            // /V1/shipment/track/:id" method="DELETE">
            /* $response = $this->client->delete('rest/V1/shipment/track/1', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
            ]);
            return $json_res;   // true
            */



            // GET SHIPPMENTS
            /* $query = [
                'searchCriteria'    => 'All',
            ];
            $response = $this->client->get('rest/V1/shipments', [
                'headers' => [
                    //'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => $query,
            ]); */

            // GET SHIPMENT DATA
            /* $query = [
                'searchCriteria'    => 'All',
            ];
            $response = $this->client->get('rest/V1/shipment/4', [
                'headers' => [
                    //'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                //'query' => $query,
            ]); */
            // RETURNS: tracks[] = "track_number":"12345","title":"Free Shipping","carrier_code":"freeshipping_freeshipping"
            // $json_res->tracks[0]->track_number
            // $json_res->tracks[0]->title
            // $json_res->tracks[0]->carrier_code



            // UPDATE TRACK SHIPMENT
            /* $response = $this->client->post('rest/V1/shipment/track', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'json' => [
                    'entity'     => [
                        'order_id'         => $order_item->marketItemId,
                        "parent_id"         => 4,   // shipment_id
                        'qty'              => $order_item->quantity,
                        'track_number'     => $shipment_data['tracking'],
                        'title'            => 'Free Shipping',
                        'carrier_code'     => 'freeshipping_freeshipping',
                    ]
                ],
            ]);
 */

            // GET STORES
            /* $response = $this->client->get('rest/V1/store/storeViews', [
                'headers' => [
                    //'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                //'query' => $query,
            ]); */


            // GET STORE CONFIG
            /* $query = [
                'storeCodes'    => ['default'],
            ];
            $response = $this->client->get('rest/V1/store/storeConfigs', [
                'headers' => [
                    //'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => $query,
            ]); */


            // GET SALES RULES
            /* $query = [
                'searchCriteria'    => 'All',
            ];
            $response = $this->client->get('rest/V1/salesRules/search', [
                'headers' => [
                    //'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => $query,
            ]);
 */

            // SEARCH CARTS
            /* $query = [
                'searchCriteria'    => 'All',
            ];
            $response = $this->client->get('rest/V1/carts/search', [
                'headers' => [
                    //'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => $query,
            ]); */

            // Create CART: 3 && GET SHIPPPING METHODS
            /* $response = $this->client->post('rest/V1/carts/mine', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'json' => [
                    'customer_id'     => 1,
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'postV1OrderShip/' .date('Y-m-d'). '.json', $contents);
                $json_res = json_decode($contents);

                $card_id = $json_res;

                $query = [
                    'cart_id'    => $card_id,      //3,
                ];
                $response = $this->client->get('rest/V1/carts/mine/shipping-methods', [
                    'headers' => [
                        //'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' .$this->shop->token,
                    ],
                    'query' => $query,
                ]);
            } */





            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'postV1OrderShip/' .date('Y-m-d'). '.json', $contents);
                $json_res = json_decode($contents);

                $order->SellerOrderId = $json_res;      // shipment_id
                $order->save();

                return true;   // trackId -> CREAR CAMP marketTrackId
            }
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_postV1OrderShip.json', $th->getMessage());
            // 401 Unauthorized -> GetToken
            if ($th->getCode() == '401') {
                $token = $this->getToken();
                if (isset($token)) return $this->postMagentoTrackings($order, $shipment_data);
            }
            else
                return $th->getMessage();
        }

        return null;
    }


    /************** PRIVATE FUNCTIONS - ORDERS COMMENTS ***************/


    private function updateOrCreateOrderComment($order, $item)
    {
        try {
            Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_order_comment_'.$item->entity_id.'.json', json_encode($item));

            $order_comment = OrderComment::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'order_id'              => $order->id,
                'comment'               => $item->comment,
            ],[
                //'created_at'            => Carbon::createFromFormat('Y-m-d H:i:s', $item->created_at)->format('Y-m-d H:i:s'),
            ]);

            $order_comment->created_at = $item->created_at;
            $order_comment->updated_at = $item->created_at;
            $order_comment->save();

            return $order_comment;
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_updateOrCreateOrderComment.json', $th->getMessage());
        }

        return null;
    }


    private function getV1OrdersComments($order)
    {
        try {
            $query = [
                /* 'searchCriteria[filter_groups][0][filters][0][field]'           => 'state',
                'searchCriteria[filter_groups][0][filters][0][value]'           => 'pending',
                'searchCriteria[filter_groups][0][filters][0][condition_type]'  => 'eq', */
                //'proveedor'                                                     => 'VDSHOP',

                //'searchCriteria'    => 'All',
                //'searchCriteria'    => 'proveedor='.$this->shop->marketSellerId,
            ];
            $response = $this->client->get('rest/V1/orders/' .$order->marketOrderId. '/comments', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                    //'Content-Type'  => 'application/json',
                ],
                'query' => $query,
            ]);

            Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_StatusCode.txt', $response->getStatusCode());
            Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_StatusCode.txt', json_encode($response->getHeaders()));

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_getV1OrdersComments.json', $contents);
                $json_res = json_decode($contents);

                // success
                if ($json_res->total_count != 0) {          // 548
                    foreach ($json_res->items as $item) {
                        $this->updateOrCreateOrderComment($order, $item);
                    }
                }

                return $json_res->total_count;
            }

            return $response->getStatusCode();
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getV1OrdersComments.json', json_encode([$this->refresh_token_steps, $th->getMessage()]));
            // 401 Unauthorized -> GetToken
            if ($th->getCode() == '401') {
                $token = $this->getToken();
                if (isset($token)) return $this->getV1OrdersComments($order);
            }
            else
                return $th->getMessage();
        }
    }


    private function postV1OrdersComments($order, $comment_data)
    {
        try {
            $response = $this->client->post('rest/V1/orders/' .$order->marketOrderId. '/comments', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'json' => [
                    'statusHistory'     => [
                        'comment'   => $comment_data['comment'],
                        'status'    => 'processing',    //$comment_data['status'],
                        'is_customer_notified'  => 1,   //$comment_data['notify'],      // 1 | 0
                        'is_visible_on_front'  => 1,    //$comment_data['visible'],      // 1 | 0
                    ],
                ],
            ]);

            if ($response->getStatusCode() == '200')
                return true;
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_postV1OrdersComments.json', $th->getMessage());
            //Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getV1Orders.json', json_encode([$this->refresh_token_steps, $th->getMessage()]));
            // 401 Unauthorized -> GetToken
            if ($th->getCode() == '401') {
                $token = $this->getToken();
                if (isset($token)) return $this->postV1OrdersComments($order, $comment_data);
            }
            else
                return $th->getMessage();

        }

        return null;
    }


    /************** PRIVATE FUNCTIONS - CARRIERS ***************/


    private function getMagentoCarriers()
    {
        try {
            $query = [
                'cardId'    => 1,
            ];
            $response = $this->client->get('rest/V1/carts/mine/payment-methods', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                    //'Content-Type'  => 'application/json',
                ],
                'query' => $query,
            ]);



            /* if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'postV1OrderShip/' .date('Y-m-d'). '.json', $contents);
                $json_res = json_decode($contents);

                $order->SellerOrderId = $json_res;      // shipment_id
                $order->save();

                return $json_res;
            } */
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_postV1OrderShip.json', $th->getMessage());
             // 401 Unauthorized -> GetToken
            if ($th->getCode() == '401') {
                $token = $this->getToken();
                if (isset($token)) return $this->getMagentoCarriers();
            }
        }

        return null;
    }


    /************** PRIVATE FUNCTIONS - BUILDERS ***************/



    /************** PRIVATE FUNCTIONS - POSTS ***************/



    /************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getBrands($id_min = null)
    {
        return false;
    }


    public function getCategories($marketCategoryId = null)
    {
        return false;
    }


    public function getAttributes(Collection $market_categories)
    {
        return false;
    }


    public function getFeed(ShopProduct $shop_product)
    {
        return false;
    }


    public function getJobs()
    {
        return false;
    }


    public function getOrders()
    {
        return $this->getV1Orders();
    }


    public function getGroups()
    {
        return false;
    }


    public function getCarriers()
    {
        $res = $this->getMagentoCarriers();
        return $res;

        /* if ($res->result_list) {

            foreach ($res->result_list->aeop_logistics_service_result as $logictic) {
                MarketCarrier::updateOrCreate([
                    'market_id'     => $this->market->id,
                    'code'          => $logictic->service_name,
                ], [
                    'name'          => $logictic->display_name,
                    'url'           => null,
                ]);
            }

            return true;
        }

        Storage::put($this->storage_dir. 'errors/' .date('Y-m-d'). '_getCarriers.json', $res);
        return (isset($res->error_response)) ? $res->error_response : false; */
    }


    public function getOrderComments(Order $order)
    {
        return $this->getV1OrdersComments($order);
    }


    /************ PUBLIC FUNCTIONS - POSTS *******************/


    public function postNewProduct(ShopProduct $shop_product)
    {
        return false;
    }


    public function postUpdatedProduct(ShopProduct $shop_product)
    {
        return false;
    }


    public function postPriceProduct(ShopProduct $shop_product)
    {
        return false;
    }


    public function postNewProducts($shop_products = null)
    {
        return false;
    }


    public function postUpdatedProducts($shop_products = null)
    {
        return false;
    }


    public function postPricesStocks($shop_products = null)
    {
        return false;
    }


    public function postGroups($shop_products = null)
    {
        return false;
    }


    public function removeProduct($marketProductSku = null)
    {
        return false;
    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        return $this->postMagentoTrackings($order, $shipment_data);
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return $this->postV1OrdersComments($order, $comment_data);
    }


    public function synchronize()
    {
        return null;
    }


    public function removeWithoutStock()
    {
        return null;
    }



    /************* REQUEST FUNCTIONS *********************/


    public function getProduct($marketProductSku)
    {
        return false;
    }


    public function getAllProducts()
    {
        return false;
    }


    private function getCSVProducts($download_link)
    {
        return false;
    }




}
