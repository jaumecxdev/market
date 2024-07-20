<?php

namespace App\Libraries;


use App\Address;
use App\AttributeMarketAttribute;
use App\Buyer;
use App\Country;
use App\Currency;
use App\MarketAttribute;
use App\MarketCategory;
use App\Order;
use App\Product;
use App\Property;
use App\PropertyValue;
use App\RootCategory;
use App\Shop;
use App\ShopProduct;
use App\Status;
use App\Traits\HelperTrait;
use App\Type;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;


use function GuzzleHttp\json_encode;

/**
 * Class JoomWS                   JoomWS Web Service
 */
class JoomWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    private $client = null;
    private $currency_code = 'EUR';


    // JOOM: Orders must be fulfilled within 1-2 days. But if they remain unfulfilled past 8 days, they will be canceled.
    //private $shipping_time = '2-8';

    const DEFAULT_CONFIG = [
        // MarketWS
        'header' => null,
        'header_rows' => 1,
        'order_status_ignored' => [],
        'errors_ignored' => [],
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


    // Base: MPS: 5 - MP: 6
    public function __construct(Shop $shop)
    {
        parent::__construct($shop);
        /* $this->storage_dir .= $shop->market->code.'/';
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir); */
        //$this->shipping_time = $shop->shipping;
        $this->client = new Client(['base_uri' => $shop->endpoint]);
        $this->currency_code = $this->shop->country;
    }


    public function authorize()
    {
        if (!isset($this->shop->client_id) || !isset($this->shop->client_secret)  || !isset($this->shop->redirect_url) || isset($this->shop->token))
            dd('Faltan datos en esta tienda: [client_id, client_secret, redirect_url] o token NO Nulo.');

        // Authorize App & get Authorization Code via Redirect URI
        // RETURN TO REDIRECT URI: https://app.mpespecialist.com/oauth/joom?code=6430c1eb040f45f38e6a55e7f8fe2feb
        return redirect()->to($this->shop->endpoint. 'v2/oauth/authorize?client_id=' .$this->shop->client_id);
    }


    /************** PRIVATE FUNCTIONS ***************/


    /* private function testToken()
    {
        // TEST token -> FAILS -> REFRESH
        try {
            $response = $this->client->get('v2/auth_test', [
                'query' => [
                    'access_token' => $this->shop->token,
                ]
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_test.json', $contents);
                $json_res = json_decode($contents);
                // success: $json_res->data: 'Welcome!'
                if ($json_res->code == 0 && $json_res->data == 'Welcome!')
                    return true;
            }

            Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_test_errors.json', $response->getBody()->getContents());
            return false;
        }
        catch (Throwable $th) {
            // 401 Unauthorized -> Refresh Token
            if ($th->getCode() == '401' && $this->refreshToken())
                $this->getColorsRequest();

            Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_test_errors.json', json_encode($th->getMessage()));
            return false;
        }
    } */


    public function refreshToken()
    {
        /* curl --request POST \
            --url https://api-merchant.joom.com/api/v2/oauth/refresh_token \
            --header 'content-type: application/x-www-form-urlencoded' \
            --data client_id= \
            --data client_secret= \
            --data grant_type=refresh_token \
            --data refresh_token= */

        // Refresh access_token every 30 days
        try {
            $response = $this->client->post('v2/oauth/refresh_token', [
                'headers' => [
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'client_id' => $this->shop->client_id,
                    'client_secret' => $this->shop->client_secret,
                    'refresh_token' => $this->shop->refresh,
                    'grant_type' => 'refresh_token',
                ]
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_refresh.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {
                    $this->shop->token = $json_res->data->access_token;
                    $this->shop->refresh = $json_res->data->refresh_token;
                    // $this->shop->marketSellerId = $json_res->data->merchant_user_id;
                    $this->shop->save();

                    return true;
                }
            }

            Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_refresh_errors.json', $response->getBody()->getContents());
            return false;
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_refresh_errors.json', json_encode($th->getMessage()));
            return false;
        }
    }


    /************** PRIVATE FUNCTIONS - SAVES & UPDATES ***************/


    private function updateOrCreateOrder($item)
    {
        Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_orders.json', json_encode($item));

        $country = Country::firstOrCreate([
            'name'  => $item->shippingAddress->country,
        ],[
            'code'  => $item->shippingAddress->country,
        ]);

        $address = Address::firstOrCreate([
            'country_id'            => $country->id,
            'market_id'             => $this->market->id,
            'name'                  => $item->shippingAddress->name ?? null,
            'phone'                 => $item->shippingAddress->phoneNumber ?? null,
            'marketBuyerId'         => $item->customerId,
        ],[
            'zipcode'               => $item->shippingAddress->zipCode ?? '',
            'address1'              => $item->shippingAddress->streetAddress1 ?? '',
            'address2'              => $item->shippingAddress->streetAddress2 ?? '',
            'city'                  => $item->shippingAddress->city ?? '',
            'state'                 => $item->shippingAddress->state ?? '',
        ]);

        $buyer = Buyer::firstOrCreate([
            'market_id'             => $this->market->id,
            'name'                  => $item->shippingAddress->name ?? '',
            'marketBuyerId'         => $item->customerId ?? '',
        ],[
            'phone'                 => $item->shippingAddress->phoneNumber,
            'email'                 => $item->shippingAddress->email ?? null,
            'shipping_address_id'   => $address->id ?? null,
            'billing_address_id'    => null,
        ]);

        $status = Status::firstOrCreate([
            'market_id'             => $this->market->id,
            'marketStatusName'      => $item->status,
            'type'                  => 'order',
        ],[
            'name'                  => $item->status,
        ]);

        $currency = Currency::firstOrCreate([
            'code'      => $this->currency_code,
        ],[
            'name'      => $this->currency_code,
        ]);

        $price = floatval($item->priceInfo->orderPrice);
        $price_cost = floatval($item->priceInfo->origAmount);
        $shipping_price = floatval($item->priceInfo->shippingPrice);

        $order = Order::whereMarketId($this->market->id)->whereShopId($this->shop->id)->where('marketOrderId', $item->id)->first();
        $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
        $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
        $order = Order::updateOrCreate([
            'market_id'             => $this->market->id,
            'shop_id'               => $this->shop->id,
            'marketOrderId'         => $item->id,
        ],[
            'buyer_id'              => $buyer->id ?? null,
            'shipping_address_id'   => $address->id ?? null,
            'billing_address_id'    => null,
            'currency_id'           => $currency->id ?? null,
            'status_id'             => $status->id ?? null,
            'type_id'               => null,
            'SellerId'              => $item->customerId,
            'SellerOrderId'         => $item->transactionId,
            'info'                  => '',
            'price'                 => $price,
            'tax'                   => 0,
            'shipping_price'        => $shipping_price,
            'shipping_tax'          => 0,
            'notified'              => $notified,
            'notified_updated'      => $notified_updated,
        ]);

        // 2006-01-02T15:04:05Z
        $order->created_at = Carbon::createFromFormat('Y-m-d\TH:i:sZ', $item->orderTimestamp)->addHours(1)->format('Y-m-d H:i:s');
        $order->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:sZ', $item->orderTimestamp)->addHours(1)->format('Y-m-d H:i:s');
        $order->save();

        /* $shop_product = $this->shop->shop_products()
            ->where('marketProductSku', $item->product->id)
            ->first();

        $mp_bfit_real = floatval($item->priceInfo->commissionAmount);
        $bfit = $this->getBfit($price,
            $shop_product->param_fee ?? 0,
            $shop_product->param_bfit_min ?? 0,
            $shop_product->tax ?? 21);
 */
        $order_item = $order->updateOrCreateOrderItem(
            $item->product->id,
            $item->product->variant->sku,
            $item->product->id,
            $item->product->name,
            $item->quantity,
            $price,
            0,
            $shipping_price,
            0,
            null);

        /* $order_item = OrderItem::updateOrCreate([
            'order_id'          => $order->id ?? null,
            'product_id'        => $shop_product->product->id ?? null,
            'marketOrderId'     => $item->id,
            'marketItemId'      => $item->product->id,
        ],[
            'marketProductSku'  => $shop_product->marketProductSku,
            'currency_id'       => $currency->id ?? null,
            'MpsSku'            => $item->product->variant->sku,
            'name'              => $item->product->name,
            'info'              => null,
            'quantity'          => $item->quantity,
            'price'             => $price,
            'tax'               => 0,
            'shipping_price'    => $shipping_price,
            'shipping_tax'      => 0,

            'cost'              => $shop_product->getCost() ?? 0,
            'bfit'              => $bfit,
            'mp_bfit'           => $mp_bfit_real,
        ]); */

        // CONTROL_RATE

        /* $mp_bfit = $this->getMarketBfit($price,
            $shop_product->param_mp_fee ?? 0,
            $shop_product->param_mp_fee_addon ?? 0,
            $shop_product->tax ?? 21);

        $control_rate_info = [
            'OK'                        => ($mp_bfit != $mp_bfit_real) ? 'MP_BFIT_LOCAL_DIFERENT_REAL' : 'OK',
            'mp_fee'                    => $shop_product->param_mp_fee,
            'mp_fee_real'               => ($mp_bfit_real / $price) * 100,
            'mp_bfit'                   => $mp_bfit,
            'mp_bfit_real'              => $mp_bfit_real,
            'order_id'                  => $order->marketOrderId,
            'price'                     => $order->price,
        ];

        Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_CONTROL_RATE.json', json_encode($control_rate_info)); */
    }


    /************** PRIVATE FUNCTIONS - GETTERS ***************/


    private function getOrdersRequest()
    {
        try {
            $response = $this->client->get('v3/orders/multi', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => [
                    'limit'     => 10,
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_getOrdersRequest.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0 && isset($json_res->data->items)) {
                    foreach($json_res->data->items as $item)
                        $this->updateOrCreateOrder($item);
                }

                Storage::append($this->storage_dir. 'attributes/' .date('Y-m-d'). '_getOrdersRequest.json', json_encode($json_res));
                return $json_res;
            }

            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getOrdersRequest.json', json_encode($response->getHeaders()));
            return $response;
        }
        catch (Throwable $th) {
            // 401 Unauthorized -> Refresh Token
            if ($th->getCode() == '401' && $this->refreshToken())
                $this->getOrdersRequest();

            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getOrdersRequest.text', $th->getMessage());
            return $th->getMessage();
        }
    }


    private function getRootCategoryByParent($parentId, &$mp_category_ids)
    {
        $count = 0;     // Secure NO infinite loop
        $root_category_id = (isset($mp_category_ids[$parentId]) && !isset($mp_category_ids[$parentId]['parentId'])) ?
            $mp_category_ids[$parentId]['id'] :
            null;

        while (isset($mp_category_ids[$parentId]) && $root_category_id == null && $count < 10) {
            $parentId = $mp_category_ids[$parentId]['parentId'];
            $root_category_id = (isset($mp_category_ids[$parentId]) && !isset($mp_category_ids[$parentId]['parentId'])) ?
                $mp_category_ids[$parentId]['id'] :
                null;

            $count++;
        }

        return $root_category_id;
    }


    private function getCategoriesRequest($marketCategoryId = null)
    {
        // ROOT Category : NO isset ParentId
        try {
            $response = $this->client->get('v3/products/categories', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'categories/' .date('Y-m-d'). '_all.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {

                    $mp_category_ids = [];
                    foreach ($json_res->data->categories as $mp_category) {
                        if (!isset($mp_category->parentId)) {
                            $root_category = RootCategory::firstOrCreate([
                                'market_id'         => $this->market->id,
                                'name'              => $mp_category->name,
                                'marketCategoryId'  => $mp_category->id,
                            ],[]);
                            $mp_category_ids[$mp_category->id] = [
                                'id' => $root_category->id,
                                'parentId' => null,     // root category
                            ];

                        }
                        else {
                            $market_category = MarketCategory::updateOrCreate(
                                [
                                    'market_id'         => $this->market->id,
                                    'marketCategoryId'  => $mp_category->id,
                                ],
                                [
                                    'name'              => $mp_category->name,
                                    'path'              => $mp_category->path,
                                ]
                            );

                            $mp_category_ids[$mp_category->id] = [
                                'id' => $market_category->id,
                                'parentId' => $mp_category->parentId,       // NO root category
                            ];
                        }
                    }

                    $market_categories = $this->market->market_categories;
                    foreach($market_categories as $market_category) {
                        if (isset($mp_category_ids[$market_category->marketCategoryId])) {
                            $parentId = $mp_category_ids[$market_category->marketCategoryId]['parentId'];
                            if (!isset($market_category->parent_id) && isset($mp_category_ids[$parentId]))
                                $market_category->parent_id = $mp_category_ids[$parentId]['id'];
                            if (!isset($market_category->root_category_id))
                                $market_category->root_category_id = $this->getRootCategoryByParent($parentId, $mp_category_ids);

                            $market_category->save();
                        }
                    }

                    return $mp_category_ids;
                }

                Storage::append($this->storage_dir. 'categories/' .date('Y-m-d'). '_getCategoriesRequest.json', $json_res);
                return $json_res;
            }

            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getCategoriesRequest.json', json_encode($response->getHeaders()));
            return $response;
        }
        catch (Throwable $th) {
            // 401 Unauthorized -> Refresh Token
            if ($th->getCode() == '401' && $this->refreshToken())
                $this->getCategoriesRequest($marketCategoryId);

            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getCategoriesRequest.text', $th->getMessage());
            return $th->getMessage();
        }
    }


    private function getColorsRequest()
    {
        try {
            $response = $this->client->get('v3/products/colors', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'attributes/' .date('Y-m-d'). '_colors.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {
                    $type = Type::firstOrCreate(
                        [
                            'market_id' => $this->market->id,
                            'name' => 'type_sku',
                            'type' => 'market_attribute',
                        ],
                        []
                    );

                    $market_attribute = MarketAttribute::firstOrCreate(
                        [
                            'market_id' => $this->market->id,
                            'market_category_id' => null,       // All categories
                            'type_id' => $type->id,
                            'name' => 'colors',
                            'code' => null,
                        ],
                        [
                            'datatype' => 'string',
                            'required' => false,
                        ]
                    );

                    $attribute_property = Property::firstOrCreate(
                        [
                            'market_attribute_id' => $market_attribute->id,
                            'name' => null,
                        ],
                        [
                            'datatype' => 'string',
                            'required' => false,
                            'custom' => false,          // Can separate colors by: &, and, ','
                            'custom_value' => null,
                            'custom_value_field' => null,
                        ]
                    );

                    foreach ($json_res->data->colors as $color) {
                        PropertyValue::firstOrCreate(
                            [
                                'property_id' => $attribute_property->id,
                                'name' => null,
                                'value' => $color->name,
                            ],
                            []
                        );
                    }

                }

                Storage::append($this->storage_dir. 'attributes/' .date('Y-m-d'). '_getColorsRequest.json', json_encode($json_res));
                return $json_res;
            }

            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getColorsRequest.json', json_encode($response->getHeaders()));
            return $response;
        }
        catch (Throwable $th) {
            // 401 Unauthorized -> Refresh Token
            if ($th->getCode() == '401' && $this->refreshToken())
                $this->getColorsRequest();

            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getColorsRequest.text', $th->getMessage());
            return $th->getMessage();
        }
    }


    /************** PRIVATE FUNCTIONS - BUILDERS ***************/


    private function buildJoomTitle(Product $product)
    {
        $title = str_replace(['ª','®','™'], ['a','',''], $product->buildTitle());
        // ,6 GHz 8ª generación de procesadores Intel® Core™ i5 i5-8265U
        // mb_detect_encoding($product->buildTitle(), 'UTF-8', true);
        return mb_substr(ucwords(mb_strtolower($title)), 0, 150);
    }


    private function buildJoomDescription(Product $product)
    {
        return ucwords(mb_strtolower($product->buildDescription4Mobile()));
    }


    private function buildPropertyFeedByAttribute(AttributeMarketAttribute $attribute_market_attribute,
                                                  Property $property,
                                                  Product $product)
    {
        $property_feed = null;
        $attribute = $attribute_market_attribute->attribute;
        $product_attributes = $product->product_attributes->where('attribute_id', $attribute->id);

        if ($property->datatype == 'string') {
            foreach ($product_attributes as $product_attribute) {
                $property_feed = $product_attribute->value;
                if ($property_feed) break;
            }
        }

        return $property_feed;
    }


    private function buildItemSpecificsFeed($type_name, ShopProduct $shop_product)
    {
        $item_specifics = null;
        $market_attributes = $shop_product->market_category->market_attributes($type_name)->get();
        foreach ($market_attributes as $market_attribute) {

            $property_feed = null;
            foreach ($market_attribute->attribute_market_attributes as $attribute_market_attribute) {
                $property = $attribute_market_attribute->property;

                if ($attribute_market_attribute->attribute_id)
                    $property_feed = $this->buildPropertyFeedByAttribute($attribute_market_attribute, $property, $shop_product->product);
            }

            if ($property_feed) {
                $item_specifics[$market_attribute->name] = $property_feed;
            }
        }

        return $item_specifics;
    }


    private function buildBaseFeed(ShopProduct $shop_product)
    {
        $item = null;
        // marketProductSku for Update
        if (isset($shop_product->marketProductSku) &&
            !empty($shop_product->marketProductSku) &&
            ($shop_product->marketProductSku != 'ERROR') &&
            ($shop_product->marketProductSku != 'NO BRAND')) {
            $item['id'] = $shop_product->marketProductSku;
        }

        $item['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
        $item['enabled'] = true;

        return $item;
    }


    private function buildPricesStocksVariantFeed(ShopProduct $shop_product)
    {
        $shop_product->setPriceStock();

        $variant = [
            'sku'           => $shop_product->mps_sku,      //$shop_product->getMPSSku(),
            'enabled'       => true,
            'inventory'     => 0,       // $shop_product->stock,
            'price'         => strval($shop_product->price),
            'shippingPrice' => strval(0),
            'currency'      => $this->currency_code,
        ];

        return $variant;
    }


    private function buildProductVariantFeed(ShopProduct $shop_product, $feed_type = 'product', $colors = null)
    {
        $variant = $this->buildPricesStocksVariantFeed($shop_product);
        if ($feed_type == 'pricestock') return $variant;

        $images = $shop_product->product->public_url_images->toArray();
        $variant['mainImage'] = $images[0];

        if ($shop_product->product->size) $variant['size'] = $shop_product->product->size;
        if ($shop_product->product->weight) $variant['shippingWeight'] = $shop_product->product->weight;
        if ($shop_product->product->length) $variant['shippingLength'] = $shop_product->product->length;
        if ($shop_product->product->width) $variant['shippingWidth'] = $shop_product->product->width;
        if ($shop_product->product->height) $variant['shippingHeight'] = $shop_product->product->height;

        if (isset($colors) && $shop_product->product->color) {
            $pattern = '/[^a-zA-Z]/';
            $mapping = 'strpos';
            $property_feed_value = $this->attribute_match($pattern, $mapping, $shop_product->product->color, $colors);
            if ($property_feed_value) $variant['colors'] = $shop_product->product->color;
        }

        // size (Memory Cards, USB Flash Drives)
        $item_specifics = $this->buildItemSpecificsFeed('type_sku', $shop_product);
        if (!empty($item_specifics)) $variant = array_merge($variant, $item_specifics);

        return $variant;
    }


    private function buildProductVariantsFeed(ShopProduct $shop_product, $feed_type = 'product')
    {
        $colors = null;
        if ($feed_type == 'product') {
            $market_attribute_colors = $this->market->market_attributes()->whereName('colors')->first();
            $attribute_property_colors = Property::whereMarketAttributeId($market_attribute_colors->id)->first();
            $colors = PropertyValue::wherePropertyId($attribute_property_colors->id)->get();
        }

        $variants = null;
        $variants[] = $this->buildProductVariantFeed($shop_product, $feed_type, $colors);

        $childs = $shop_product->product->childs;
        if (count($childs))
            foreach ($childs as $child) {
                $variants[] = $this->buildProductVariantFeed($child->shop_product($this->shop->id)->first(), $feed_type, $colors);
            }

        return $variants;
    }


    private function buildMarketBrandId($brand_name)
    {
        if (strtoupper(substr($brand_name, 0, 2)) == 'HP') $brand_name = 'HP';
        if ($brand_name == 'Dell technologies') $brand_name = 'Dell';
        //'Thomson computing'

        return $brand_name;
    }


    private function buildProductFeed(ShopProduct $shop_product, $feed_type = 'product')
    {
        $item = $this->buildBaseFeed($shop_product);

        if ($feed_type == 'product') {
            $title = $this->buildJoomTitle($shop_product->product);
            $tags = explode(' ', str_replace(',', '',$shop_product->product->color.' '.$title));
            $description = $this->buildJoomDescription($shop_product->product);
            $images = array_slice($shop_product->product->public_url_images->toArray(), 0, 20);
            $market_category = $shop_product->market_category;
            if (!$title || !$description || empty($images) || !isset($market_category))
                return null;

            $item['storeId'] = $this->shop->marketSellerId;
            $item['name'] = $title;
            $item['description'] = $description;
            $item['gtin'] = $shop_product->product->ean ?? $shop_product->product->upc ?? $shop_product->product->isbn ?? $shop_product->product->gtin ?? $shop_product->product->pn;
            $item['tags'] = $tags;
            $item['brand'] = $this->buildMarketBrandId($shop_product->product->brand->name);
            $item['mainImage'] = $images[0];
            array_shift($images);
            if (count($images))
                $item['extra_images'] = implode('|', $images);

            $item['categoryId'] = $market_category->marketCategoryId;
        }

        $item['variants'] = $this->buildProductVariantsFeed($shop_product, $feed_type);
        //$item['attributes'] = []

        return $item;
    }



    /************** PRIVATE FUNCTIONS - POSTS ***************/


    private function postProduct(ShopProduct $shop_product, $feed_type = 'product', $post_type = 'create')
    {
        // form_params || multipart
        $result = null;
        $item = $this->buildProductFeed($shop_product, $feed_type);

        if ($item) {
            try {
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' .$this->shop->token,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $item,
                ];
                if ($post_type == 'update')
                    $options['query'] = ['id' => $shop_product->marketProductSku];

                $response = $this->client->post('v3/products/'.$post_type, $options);

                if ($response->getStatusCode() == '200') {
                    $contents = $response->getBody()->getContents();
                    Storage::append($this->storage_dir. 'product/' .date('Y-m-d'). '_' .$feed_type. '_'.$post_type.'.json', $contents);
                    $json_res = json_decode($contents);
                    // success
                    if ($json_res->code == 0)
                        if ($post_type == 'update') {
                            return [
                                'id'                => $shop_product->id,
                                'product_id'        => $shop_product->product_id,
                                'marketProductSku'  => $shop_product->marketProductSku
                            ];
                        }
                        elseif (isset($json_res->data->id)) {

                            $marketProductSku = $json_res->data->id;
                            if (isset($json_res->data->review) && isset($json_res->data->review->infractions)) {
                                foreach($json_res->data->review->infractions as $infraction) {
                                    // "description": "product is associated with nonexistent brand",
                                    // "description": "brand is not authorized for merchant",
                                    if ($infraction->code == 'J1131' || $infraction->code == 'J1132') {
                                        $marketProductSku = 'NO BRAND';
                                        break;
                                    }
                                }
                            }

                            $shop_product->marketProductSku = $marketProductSku;
                            $shop_product->save();

                            $result['product'] = [
                                'id'                => $shop_product->id,
                                'product_id'        => $shop_product->product_id,
                                'marketProductSku'  => $shop_product->marketProductSku
                            ];

                            if (isset($json_res->data->variants)) {
                                foreach ($json_res->data->variants as $variant) {
                                    //$product_id = $this->getIdFromMPSSku($variant->sku);
                                    $variant_shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $variant->sku);
                                    if ($variant_shop_product) {
                                        // ALL VARIANTS HAVE SAME PRODUCT_ID. variant_id != product_id
                                        // For Update, is necessary PRODUCT_ID & SKU
                                        $variant_shop_product->marketProductSku = $shop_product->marketProductSku;
                                        //$variant_shop_product->marketProductSku = $variant->id;   //$variant->Variant->product_id; $json_res->data->Product->id;
                                        $variant_shop_product->save();
                                        $result['product']['variants'] = [
                                            'id'                => $variant_shop_product->id,
                                            'product_id'        => $variant_shop_product->product_id,
                                            'marketProductSku'  => $variant_shop_product->marketProductSku
                                        ];
                                    }
                                }
                            }

                            return $result;
                        }

                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$feed_type. '_'.$post_type.'.json', json_encode($json_res));
                    return $json_res;
                }

                //$contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$feed_type. '_'.$post_type.'.json', json_encode($response->getHeaders()));
                return $response->getHeaders();
            }
            catch (Throwable $th) {
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$feed_type. '_'.$post_type.'.json', json_encode($th->getMessage()));
                // 401 Unauthorized -> Refresh Token
                if ($th->getCode() == '401' && $this->refreshToken())
                    $this->postProduct($shop_product, $feed_type, $post_type);

                return $th->getMessage();
            }
        }

        return $item;
    }


    private function removeOneProduct(ShopProduct $shop_product)
    {
        try {
            $marketProductSku = $shop_product->marketProductSku;
            $response = $this->client->post('v3/products/remove', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                    'Content-Type'  => 'application/json',
                ],
                'query' => [
                    'id' => $marketProductSku,
                ]
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'requests/' .date('Y-m-d'). '_product.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {
                    $shop_product->delete();

                    return $marketProductSku;
                }

            }

            return false;
        }
        catch (Throwable $th) {
            // 401 Unauthorized -> Refresh Token
            if ($th->getCode() == '401' && $this->refreshToken())
                $this->removeProduct($marketProductSku);

            return [$marketProductSku, $th];
        }
    }


    private function removeOneProductBySku($marketProductSku)
    {
        try {
            $response = $this->client->post('v3/products/remove', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                    'Content-Type'  => 'application/json',
                ],
                'query' => [
                    'id' => $marketProductSku,
                ]
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'requests/' .date('Y-m-d'). '_product.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0)
                    return $marketProductSku;
            }

            return false;
        }
        catch (Throwable $th) {
            // 401 Unauthorized -> Refresh Token
            if ($th->getCode() == '401' && $this->refreshToken())
                $this->removeProduct($marketProductSku);

            return [$marketProductSku, $th];
        }
    }


    /************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getBrands()
    {
        return null;
    }

    public function getCategories($marketCategoryId = null)
    {
        return $this->getCategoriesRequest($marketCategoryId);
    }


    public function getAttributes(Collection $market_categories)
    {
        // Categorias con Variant SIZE obligatorio
        // Memory Cards: 1473502935933050402-246-2-118-3698857822
        // USB Flash Drivers: 1473502935938677404-248-2-118-2064455579
        return ['No hay atributos por categorias, sino un solo atributo Colors para todas las categorías. Ejecutar el Request de Joom, getColors.'];
    }


    public function getFeed(ShopProduct $shop_product)
    {
        $item = $this->buildProductFeed($shop_product);

        return $item;
    }


    public function getJobs()
    {
        return false;
    }


    public function getOrders()
    {
        return $this->getOrdersRequest();
    }


    public function getGroups()
    {
        return false;
    }


    public function getCarriers()
    {
        return false;
    }


    public function getOrderComments(Order $order)
    {
        return false;
    }


    /************ PUBLIC FUNCTIONS - POSTS *******************/


    public function postNewProduct(ShopProduct $shop_product)
    {
        return $this->postProduct($shop_product, 'product', 'create');
    }


    public function postUpdatedProduct(ShopProduct $shop_product)
    {
        return $this->postProduct($shop_product, 'product', 'update');
    }


    public function postPriceProduct(ShopProduct $shop_product)
    {
        return $this->postProduct($shop_product, 'pricestock', 'update');
    }


    public function postNewProducts($shop_products = null)
    {
        $shop_products = $this->getShopProducts4Create($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos nuevos en esta Tienda';

        $res = null;
        foreach($shop_products as $shop_product) {
            $res[] = $this->postProduct($shop_product, 'product', 'create');
        }

        return $res;
    }


    public function postUpdatedProducts($shop_products = null)
    {
        $shop_products = $this->getShopProducts4Update($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

        $res = null;
        foreach($shop_products as $shop_product) {
            $res[] = $this->postProduct($shop_product, 'product', 'update');
        }

        return $res;
    }


    public function postPricesStocks($shop_products = null)
    {
        $shop_products = $this->getShopProducts4Update($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

        $res = null;
        foreach($shop_products as $shop_product) {
            $res[] = $this->postProduct($shop_product, 'pricestock', 'update');
        }

        return $res;
    }


    public function postGroups($shop_products = null)
    {
        return false;
    }


    public function removeProduct($marketProductSku = null)
    {
        /* $shop_products = $this->shop->shop_products()->where('marketProductSku', 'NO BRAND')->get();
        $res = null;
        foreach ($shop_products as $shop_product) {
            $shop_product->delete();
        }
        */

        if (isset($marketProductSku)) {
            $shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first();
            if ($shop_product) {
                return $this->removeOneProduct($shop_product);
            }
        }
        else {
            /* $shop_products = $this->shop->shop_products()->whereMarketCategoryId(16514)->get();
            $res = null;
            foreach ($shop_products as $shop_product) {
                $res[] = $this->removeOneProduct($shop_product);
            }
            */
        }





    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        return false;
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return false;
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
        try {
            $response = $this->client->get('v3/products', [

                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => [
                    'id' => $marketProductSku,
                ]
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'requests/' .date('Y-m-d'). '_product.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0)
                    dd($json_res);
            }

            dd($response);
        }
        catch (Throwable $th) {
            // 401 Unauthorized -> Refresh Token
            if ($th->getCode() == '401' && $this->refreshToken())
                $this->getProduct($marketProductSku);

            dd($th);
        }
    }


    public function getAllProducts($next_page = null)
    {
        // GroupBy: NO BRAND
        /* $shop_products = $this->shop->shop_products()->where('marketProductSku', 'NO BRAND')->get();
        $res = null;
        $count = 0;
        foreach ($shop_products as $shop_product) {
            $res[$shop_product->product->brand->name] = isset($res[$shop_product->product->brand->name]) ?
            $res[$shop_product->product->brand->name]++ :
            $res[$shop_product->product->brand->name] = 0;

            $count++;
        }
        dd($count, $res); */


        /* $shop_products = $this->shop->shop_products()->select('market_category_id')->groupBy('market_category_id')->get();
        $res = null;
        foreach ($shop_products as $shop_product) {
            $res[$shop_product->market_category_id] = $shop_product->market_category->name;
        }
        dd($res);
 */



        try {
            //$response = $this->client->get('v3/products/multi?after=1-gaNyYXeRuDVmMjA1NDZhODk5M2I3MDEwNmE2MGFkOA', [
            $response = $this->client->get('v2/product/multi-get', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => [
                    'limit' => 500,
                    'start' => 2500,
                    //'updatedFrom' => '2020-08-04',
                ]
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'requests/' .date('Y-m-d'). '_products.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {
                    //dd($json_res);

                    $count = 0;
                    $res = null;
                    $res2 = null;

                    /*
                    // API V3 -> NO funciona el paging
                    // SAVE LOCAL SHOP PRODUCTS WHERE MARKETPRODUCTSKU IS NULL
                    foreach($json_res->data->items as $item) {
                        $product_id = $this->getIdFromMPSSku($item->sku);
                        $shop_product = $this->shop->shop_products()->whereProductId($product_id)->first();
                        if (isset($shop_product) && !isset($shop_product->marketProductSku)) {
                            $shop_product->marketProductSku = $item->id;
                            $shop_product->save();
                            //dd($item, $shop_product);
                            $count++;
                        }
                    } */

                    // API V2
                    foreach($json_res->data as $data) {
                        //dd($data, $data->Product, $data->Product->id, $data->Product->parent_sku);

                        if (isset($data->Product)) {
                            //$product_id = $this->getIdFromMPSSku($data->Product->parent_sku);
                            $shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $data->Product->parent_sku);
                            // SAVE LOCAL SHOP PRODUCTS WHERE MARKETPRODUCTSKU IS NULL
                            if (isset($shop_product) && $shop_product->marketProductSku == 'NO BRAND') {
                                $shop_product->marketProductSku = $data->Product->id;
                                $shop_product->save();
                                //dd($data->Product, $shop_product);
                                $count++;
                            }

                            // REMOVE JOOM ONLINE PRODUCTS WHERE MARKETPRODUCTSKU IS NO NULL & NO LOCAL FOUND
                            if (!isset($shop_product)) {
                                //$count++;
                                $res[] = $data->Product;
                                $res2[] = $this->removeOneProductBySku($data->Product->id);
                            }
                        }
                    }


                    dd('FI FOREACH', $count, $res, $res2, $json_res);
                }
            }

            dd($response);
        }
        catch (Throwable $th) {
            // 401 Unauthorized -> Refresh Token
            if ($th->getCode() == '401' && $this->refreshToken())
                $this->getAllProducts();

            dd($th);
        }
    }


    public function getColors()
    {
        dd($this->getColorsRequest());
    }





}
