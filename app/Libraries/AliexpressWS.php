<?php

namespace App\Libraries;

require_once("Aliexpress/TopSdk.php");

use AliexpressFreightRedefiningListfreighttemplateRequest;
use AliexpressSolutionBatchProductDeleteRequest;
use AliexpressSolutionOrderFulfillRequest;
use AliexpressLogisticsRedefiningListlogisticsserviceRequest;
use AliexpressPostproductRedefiningOfflineaeproductRequest;
use AliexpressPostproductRedefiningOnlineaeproductRequest;
use AliexpressPostproductRedefiningQuerypromisetemplatebyidRequest;
use AliexpressPostproductRedefiningSetgroupsRequest;
use AliexpressProductProductgroupsGetRequest;
use AliexpressSolutionFeedQueryRequest;
use AliexpressSolutionFeedSubmitRequest;
use AliexpressSolutionMerchantProfileGetRequest;
use AliexpressSolutionOrderGetRequest;
use AliexpressSolutionOrderInfoGetRequest;
use AliexpressSolutionProductInfoGetRequest;
use AliexpressSolutionProductListGetRequest;
use AliexpressSolutionProductSchemaGetRequest;
use AliexpressSolutionSellerCategoryTreeQueryRequest;
use App\Address;
use App\AttributeMarketAttribute;
use App\Buyer;
use App\Category;
use App\Country;
use App\Currency;
use App\Dictionary;
use App\Group;
use App\MarketAttribute;
use App\MarketCarrier;
use App\MarketCategory;
use App\Order;
use App\Product;
use App\Property;
use App\PropertyValue;
use App\RootCategory;
use App\Shop;
use App\ShopFilter;
use App\ShopJob;
use App\ShopProduct;
use App\Status;
use App\Traits\HelperTrait;
use App\Type;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ItemListQuery;
use SingleItemRequestDto;
use Throwable;


class AliexpressWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    private $sessionKey = null;
    private $client = null;
    private $locale = 'es_ES';
    private $product_group_id = false;
    private $group_id = false;


    const PAGINATE = 900;       // max: 2000

    const NO_AUTH_BRANDS = ['Nintendo', 'Oral-B', 'LEGO'];

    const DEFAULT_CONFIG = [
        // MarketWS
        'header' => [
            "category_id",
            "sku_code",
            "inventory",
            "price",
            "discount_price",
            "ean",
            "brand",
            "locale",
            "shipping_preparation_time",
            "shipping_template_id",
            "service_template_id",
            "product_units_type",
            "inventory_deduction_strategy",
            "product_group_id",
            "group_id",
            "title",
            "description",
            "image1",
            "image2",
            "image3",
            "image4",
            "image5"
        ],
        'header_rows' => 1,
        'order_status_ignored' => [
            'PLACE_ORDER_SUCCESS', 'WAIT_BUYER_ACCEPT_GOODS', 'WAIT_SELLER_EXAMINE_MONEY',
            'RISK_CONTROL', 'FUND_PROCESSING', 'WAIT_SELLER_EXAMINE', 'FINISH', 'COMPLETED'
        ],
        'errors_ignored' => [
            'F00-10-10000-001',         // system error, please try again later
            'F00-00-10009-002',
            'F00-00-10014-017',         // Downstream failed for price updating. Please try again later.
            'F00-00-10014-018',         // API_EXCEPTION:07009999|SYSTEM:Unexcepted exception: \nerror message : [HSF-Provider-11.21.111.99] Error log: Provider's HSF thread pool is full.",
            'F00-00-10020-004',         // 系统错误,请稍后重试 - Error del sistema, intente nuevamente más tarde
            '13250015',                 // 250015:System Error. Please try again later or contact customer service
            '-99999',                   // HSFTimeOutException-FutureTimeout ERR-CODE: [HSF-0002]
            '250004',                   // Translation service call failed，please try to submit again.
            '7250004',                  // Translation service call failed，please try to submit again.
            'SYS_ERROR',                // \u7cfb\u7edf\u9519\u8bef,\u8bf7\u7a0d\u540e\u91cd\u8bd5\     System error, please try again later.
            '10006008',                 // API_EXCEPTION:01202016|Photobank:upload_error https:\/\/app.mpespecialist.com\/storage\/img\/47273\/5.jpg
            'PRODUCT_IN_LOCK_MODE',     // Product is punished and locked
            'API_CALL_UIC_TENANT_ERROR_0001',    // Hi ha producte local i online i coincideixen mps_sku i marketProductSku. API_CALL_UIC_TENANT_ERROR_0001:API_CALL_UIC_TENANT_ERROR_0001:UicTenantClient getCanonicalSellerTenantInfo Failed SellerId=972585204
            'API_CALL_UIC_TAG_ERROR_0005',       // Hi ha producte local i online i coincideixen mps_sku i marketProductSku. API_CALL_UIC_TAG_ERROR_0005:API_CALL_UIC_TAG_ERROR_0005:UserDataTagFacade getUserTag Failed globalUserId=972585204 tagCode=seller_tenant_info reason=java.util.concurrent.TimeoutException: Waited -66492 nanoseconds (plus 66492 nanoseconds delay) for com.taobao.hsf.util.concurrent.DefaultListenableFuture@46b5bad1[status=PENDING]
            'THD_IC_F_IC_INFRA_SELLER_005'       // Hi ha producte local i online i coincideixen mps_sku i marketProductSku. Query seller tag failed,msg:{\"displayMessage\":\"UserBoolTagFacade hasUserTag Failed globalUserId=[972585204] tagCodes=[blockPublishEdit, 47177] reason=java.util.concurrent.TimeoutException
        ],
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
        'supplier_shippings'   => null,

        // ALIEXPRESS
        'product_group_id'  => false,
        'group_id'          => false,
    ];


    public function __construct(Shop $shop)
    {
        parent::__construct($shop);

        $this->config = json_decode($shop->config);
        if (isset($this->config)) {
            if (isset($this->config->locale))
                $this->locale = $this->config->locale;
            if (isset($this->config->product_group_id))
                $this->product_group_id = $this->config->product_group_id;
            if (isset($this->config->group_id))
                $this->group_id = $this->config->group_id;
        }

        $this->client = new \TopClient();
        $this->client->appkey = $shop->client_id;
        $this->client->secretKey = $shop->client_secret;
        $this->sessionKey = $shop->token;
    }


    public function authorize()
    {
        try {
            if (!isset($this->shop->client_id) || !isset($this->shop->client_secret) || !isset($this->shop->redirect_url) || isset($this->shop->token))
                dd('Faltan datos en esta tienda: [client_id, client_secret, redirect_url] o token NO Nulo.');

            // https://oauth.aliexpress.com/authorize?response_type=code&client_id=23075594&redirect_uri=http://www.oauth.net/2/&state=1212&view=web&sp=ae
            $authorize_url = $this->shop->endpoint. 'authorize?sp=ae&response_type=code&client_id=' .$this->shop->client_id.
                '&redirect_uri='.$this->shop->redirect_url;

            return redirect()->to($authorize_url);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    /************** PRIVATE FUNCTIONS ***************/


    public function getItemRowPromo(ShopProduct $shop_product, $extra_data)
    {
        try {
            $shop_product->setPriceStock();

            $name = FacadesMpe::buildString($shop_product->buildTitle(), 128);

            $item_row['Product ID'] = strval($shop_product->marketProductSku);
            $item_row['Product Title'] = $name;
            $item_row['Discount'] = $extra_data['discount'];
            $item_row['Mobile Discount'] = $extra_data['mobile'];
            $item_row['Target People'] = $extra_data['target'];
            $item_row['Extra Discount'] = $extra_data['extra'];
            $item_row['Limit Buy Per Customer'] = $extra_data['limit'];

            return $item_row;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_product, $extra_data]);
        }
    }


    /************** PRIVATE FUNCTIONS - SAVES & UPDATES ***************/


    private function saveMarketCategoryAttributes($market_category_id, $type_name, $attributes)
    {
        try {
            $type = Type::firstOrCreate(
                [
                    'market_id' => $this->market->id,
                    'name' => $type_name,
                    'type' => 'market_attribute',
                ],
                []
            );

            foreach ($attributes as $attribute) {

                $market_attribute = MarketAttribute::firstOrCreate(
                    [
                        'market_id'             => $this->market->id,
                        'market_category_id'    => $market_category_id,
                        'type_id'               => $type->id,
                        'name'                  => $attribute->title,   // Brand Name, Model Number, Weight, Color, Processor Model, Plugs Type, ...
                        'code'                  => null,
                    ],
                    [
                        'datatype'              => $attribute->type,     // object | string | array | numeric
                        'required'              => false,
                    ]
                );

                foreach ($attribute->properties as $property) {

                    if ($property->title != 'customValue') {
                        $attribute_property = Property::firstOrCreate(
                            [
                                'market_attribute_id'   => $market_attribute->id,
                                'name'                  => $property->title,   // value | customValue | unit
                            ],
                            [
                                'datatype'              => $property->type,     // string | array
                                'required'              => in_array($property->title, $attribute->required),

                                // If NO Custom, 1st search in oneOf -> THEN -> If custom_value: value=4, customValue={product_attribute->value}
                                'custom'                => (isset($property->oneOf) || isset($property->items->oneOf)) ? false : true,
                                'custom_value'          => (isset($attribute->if) && isset($attribute->if->properties->{$property->title})) ?
                                    $attribute->if->properties->{$property->title}->const : null,       // 4
                                'custom_value_field'    => (isset($attribute->if) && isset($attribute->if->properties->{$property->title})) ?
                                    'customValue' : null,           // CustomValue
                            ]
                        );

                        // STRING: ONE VALUE ON OF
                        if (isset($property->oneOf)) {
                            foreach ($property->oneOf as $oneof) {

                                // id, property_id, name, value
                                PropertyValue::firstOrCreate(
                                    [
                                        'property_id'   => $attribute_property->id,
                                        'name'          => $oneof->title,
                                        'value'         => $oneof->const,
                                    ],
                                    []
                                );
                            }
                        }

                        // ARRAY: Operating System | Feature | Port
                        if (isset($property->items->oneOf)) {
                            foreach ($property->items->oneOf as $oneof) {

                                PropertyValue::firstOrCreate(
                                    [
                                        'property_id'   => $attribute_property->id,
                                        'name'          => $oneof->title,
                                        'value'         => $oneof->const,
                                    ],
                                    []
                                );
                            }
                        }
                    }
                }
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$market_category_id, $type_name, $attributes]);
        }
    }


    private function firstOrCreateAddress($buyer_login_id, $receipt_address)
    {
        try {
            $country = Country::firstOrCreate([
                'code'      => $receipt_address->country,
            ],[]);

            return Address::updateOrCreate([
                'country_id'            => $country->id,
                'market_id'             => $this->market->id,
                'marketBuyerId'         => $buyer_login_id,
            ],[
                'name'                  => $receipt_address->contact_person,
                'address1'              => $receipt_address->detail_address,
                'address2'              => $receipt_address->address2 ?? null,
                'city'                  => $receipt_address->city,
                'state'                 => $receipt_address->province,
                'zipcode'               => $receipt_address->zip,
                'phone'                 => $receipt_address->mobile_no,
            ]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$buyer_login_id, $receipt_address]);
        }
    }


    private function updateOrCreateOrder($order_dto)
    {
        try {
            $order_info = $this->getOrderInfoRequest($order_dto->order_id);
            Storage::append($this->shop_dir. 'orders/' .date('Y-m-d'). '_updateOrCreateOrder.json', json_encode($order_info));

            /* "code": "15",
            "msg": "Remote service error",
            "sub_code": "isp.50001",
            "sub_msg": "服务器错误",    Error del servidor */
            if ($order_info->code == '15') return null;

            if ($order_info->code != '15' && (!$order_info->result || !$order_info->result->data)) {
                return $this->nullAndStorage(__METHOD__, ['!$order_info->result || !$order_info->result->data', $order_dto, $order_info]);
            }

            if ($order_info->result && $order_info->result->data->receipt_address)
                $address = $this->firstOrCreateAddress($order_dto->buyer_login_id, $order_info->result->data->receipt_address);

            $buyer = Buyer::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketBuyerId'         => $order_dto->buyer_login_id,
            ],[
                // ES shopper OR Real name
                'name'                  => (isset($order_info) && isset($order_info->result)) ?
                    $order_info->result->data->buyer_signer_fullname : $order_dto->buyer_signer_fullname,
                'shipping_address_id'   => isset($address) ? $address->id : null,
                'billing_address_id'    => null,
                'phone'                 => isset($address) ? $address->phone : null,
                /*'email'                 => null,
                'company_name'          => null,
                'tax_region'            => null,
                'tax_name'              => null,
                'tax_value'             => null,*/
            ]);


            // order_info -> result -> data -> "loan_status": "loan_ok" && "order_status": "FINISH",
            // marketStatusName == Completed
            $marketStatusName = $order_dto->order_status;
            if ($marketStatusName == 'FINISH' && $order_info->result->data && $order_info->result->data->loan_status == 'loan_ok')
                $marketStatusName = 'COMPLETED';

            $status = Status::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketStatusName'      => $marketStatusName,
                'type'                  => 'order',
            ],[
                'name'                  => $marketStatusName,
            ]);

            $currency = Currency::firstOrCreate([
                'code'             => $order_dto->product_list->order_product_dto->product_unit_price->currency_code,
            ],[]);

            $order = $this->shop->orders()->where('marketOrderId', $order_dto->order_id)->first();
            $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;

            $price = (float)$order_info->result->data->init_oder_amount->amount ?? 0;
            $shipping_price = (float)$order_info->result->data->logistics_amount->amount ?? 0;
            $info = mb_substr($order_info->result->data->memo ?? '', 0, 255);

            $order = Order::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'marketOrderId'         => $order_dto->order_id,
            ],[
                'buyer_id'              => isset($buyer) ? $buyer->id : null,
                'shipping_address_id'   => isset($address) ? $address->id : null,
                'billing_address_id'    => null,
                'currency_id'           => $currency->id,
                'status_id'             => $status->id,
                'type_id'               => null,
                'SellerId'              => $order_dto->seller_login_id,
                'SellerOrderId'         => null,
                'info'                  => $info,   // BUYER message
                'price'                 => $price,
                'tax'                   => 0,
                'shipping_price'        => $shipping_price,
                'shipping_tax'          => 0,       // mp_shipping_fee: isset($order_info) ? (floatval($order_info->result->data->logisitcs_escrow_fee_rate)*100) : 0,
                'notified'              => $notified,
                'notified_updated'      => $notified_updated,
            ]);

            $order->created_at = Carbon::createFromFormat('Y-m-d H:i:s', $order_dto->gmt_create)->addHours(9)->format('Y-m-d H:i:s');
            $order->updated_at = Carbon::createFromFormat('Y-m-d H:i:s', $order_dto->gmt_update)->addHours(9)->format('Y-m-d H:i:s');
            $order->save();

            $order_items_count = 0;
            foreach ($order_dto->product_list->order_product_dto as $order_product_dto) {

                $MpsSku = (string)$order_product_dto->sku_code;
                $marketProductSku = (string)$order_product_dto->product_id;
                $product_name = (string)$order_product_dto->product_name;
                $quantity = (integer)$order_product_dto->product_count;

                $mp_fee = $order_info->result->data->child_order_list->global_aeop_tp_child_order_dto->escrow_fee_rate ?? 0;
                $mp_fee *= 100;
                // Cerrado
                if ($order->status_id == 15) $mp_fee = 0;

                $price = (float)$order_product_dto->total_product_amount->amount;
                $shipping_price = ($order_items_count == 0) ? $shipping_price : 0;
                $mp_shipping_bfit = ($shipping_price != 0) ? FacadesMpe::getMarketBfit($shipping_price, $mp_fee, 0, 0) : 0;

                $order_item = $order->updateOrCreateOrderItem(
                    $order_product_dto->child_id,
                    $MpsSku,
                    $marketProductSku,
                    $product_name,
                    $quantity,
                    $price,
                    0,
                    $shipping_price,
                    0,
                    'Issue: ' .$order_product_dto->issue_status,
                    ['mp_fee' => $mp_fee, 'mp_shipping_bfit' => $mp_shipping_bfit]
                );

                $order_items_count++;
            }
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$order_dto, $order_info ?? null]);
        }
    }


    private function updateOrCreateOrders($response)
    {
        try {
            $count_orders = 0;
            if ((isset($response->result)) && (isset($response->result->target_list))) {
                foreach ($response->result->target_list->order_dto as $order_dto) {
                    $this->updateOrCreateOrder($order_dto);
                    $count_orders++;
                }
            }

            return $count_orders;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $response);
        }
    }


    private function deleteOnlineProducts(array $delete_list)
    {
        try {
            $res = [];
            $chunks = array_chunk($delete_list, 99);
            foreach ($chunks as $marketProductSku_chunk) {
                $req = new AliexpressSolutionBatchProductDeleteRequest();
                $req->setProductIds(implode(',', $marketProductSku_chunk));

                $res[] = $this->client->execute($req, $this->sessionKey);
            }

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$delete_list, $res ?? null]);
        }
    }


    /* private function deleteStorage4Remove()
    {
        try {
            $collection_4_remove = $this->shop->getStorage4Remove();
            if (isset($collection_4_remove) && $collection_4_remove->count()) {
                return $this->deleteOnlineProducts($collection_4_remove->pluck('marketProductSku')->toArray());
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$collection_4_remove ?? null, $this]);
        }
    } */


    private function setAEOfflineProducts($marketProductSkus)
    {
        try {
            $marketProductSkus = array_chunk($marketProductSkus, 49);
            //foreach ($marketProductSkus as $marketProductSku) {
                $req = new AliexpressPostproductRedefiningOfflineaeproductRequest();
                $req->setProductIds(implode(';', $marketProductSkus[0]));
                $res = $this->client->execute($req, $this->sessionKey);
            //}

            return $res;
            //return $res->result->success;        // $res->result->success == 'true'

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$marketProductSkus, $res ?? null]);
        }
    }


    private function setAEOnlineProducts($marketProductSkus)
    {
        try {
            $res = [];
            $chunks = array_chunk($marketProductSkus, 49);
            foreach ($chunks as $chunk) {
                $req = new AliexpressPostproductRedefiningOnlineaeproductRequest();
                $req->setProductIds(implode(';', $chunk));
                $res[] = $this->client->execute($req, $this->sessionKey);
            }

            return $res;
            //return $res->result->success;        // $res->result->success == 'true'

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$marketProductSkus, $res ?? null]);
        }
    }


    /************** PRIVATE FUNCTIONS - GETTERS ***************/


    private function getCategoryTree($marketCategoryId)
    {
        try {
            $req = new AliexpressSolutionSellerCategoryTreeQueryRequest();
            $req->setCategoryId($marketCategoryId);
            $req->setFilterNoPermission('true');
            $category_tree = $this->client->execute($req, $this->sessionKey);
            Storage::put($this->shop_dir . 'categories/' . date('y-m-d') . '_' . $marketCategoryId . '.json', json_encode($category_tree));

            return $category_tree;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $marketCategoryId);
        }
    }


    // TODO: Make only one request. Similar to Mirakl same function
    private function getAllCategoriesRequest($marketCategoryId, $tree_parent_category = '', $root_category_id = null)
    {
        try {
            $marketCategoryId = intval($marketCategoryId);
            $req = new AliexpressSolutionSellerCategoryTreeQueryRequest();
            $req->setCategoryId($marketCategoryId);
            $req->setFilterNoPermission('true');
            $sellerCategory =  $this->client->execute($req, $this->sessionKey);
            Storage::put($this->shop_dir. 'categories/' .date('y-m-d'). '_' .$marketCategoryId. '.json', json_encode($sellerCategory));

            if (isset($sellerCategory->children_category_list)) {

                if (!isset($root_category_id)) {
                    $root_category = $this->market->root_categories()->where('marketCategoryId', $marketCategoryId)->first();
                    if (isset($root_category)) {
                        $root_category_id = $root_category->id;
                        $tree_parent_category = $root_category->name;
                    }
                }

                // For all subcategories of $marketCategoryId
                foreach ($sellerCategory->children_category_list->category_info as $category_info) {

                    // CATEGORIA ES: 7 - Informática y oficina
                    // CATEGORIA ES: 21 - Material escolar y de oficina
                    // CATEGORIA ES: 44 - Electrónica
                    // CATEGORIA ES: 509 - Teléfonos y comunicación

                    $json = json_decode($category_info->multi_language_names, true);
                    if ((isset($json['es'])) || (isset($json['en']))) {

                        if ($category_info->level == '1') {

                            $tree_parent_category = isset($json['es']) ? $json['es'] : $json['en'];
                            $root_category = RootCategory::firstOrCreate([
                                'market_id'         => $this->market->id,
                                'name'              => $tree_parent_category,
                                'marketCategoryId'  => $category_info->children_category_id,
                            ],[]);

                            $rootCategoriesId = RootCategory::where('market_id', $this->market->id)->pluck('marketCategoryId')->all();
                            $rootCategoriesId[] = null;

                            //if (in_array($category_info->children_category_id, [null, '7', '21', '44', '509', '18'])) {
                            if (in_array($category_info->children_category_id, $rootCategoriesId)) {
                                $this->getAllCategoriesRequest($category_info->children_category_id, $tree_parent_category, $root_category->id);
                            }
                        }
                        // Others levels. SAVED LEVELS
                        else {
                            if ($category_info->is_leaf_category == 'true') {

                                MarketCategory::updateOrCreate(
                                    [
                                        'market_id'         => $this->market->id,
                                        'marketCategoryId'  => strval($category_info->children_category_id),
                                    ],
                                    [
                                        'name'              => isset($json['es']) ? $json['es'] : $json['en'],
                                        'parent_id'         => null,
                                        'path'              => $tree_parent_category,
                                        'root_category_id'  => $root_category_id,
                                    ]
                                );
                            }
                            else {
                                $next_parent_category = $tree_parent_category . (isset($json['es']) ? (" / " .$json['es']) : (" / " .$json['en']));
                                $this->getAllCategoriesRequest($category_info->children_category_id, $next_parent_category, $root_category_id);
                            }
                        }
                    }
                }
            }

            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$marketCategoryId, $tree_parent_category, $root_category_id]);
        }
    }


    private function getProductSchemaRequest($marketCategoryId)
    {
        try {
            $req = new AliexpressSolutionProductSchemaGetRequest();
            $req->setAliexpressCategoryId($marketCategoryId);

            $res = $this->client->execute($req, $this->sessionKey);
            Storage::put($this->shop_dir. 'schemas/' . $marketCategoryId . '.json', $res->result->schema);

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $marketCategoryId);
        }
    }


    private function getMarketCategoryAttributes(MarketCategory $market_category)
    {
        try {
            $res = $this->getProductSchemaRequest($market_category->marketCategoryId);
            $schema = json_decode($res->result->schema);
            if (!isset($res->code) && $res->result->success == 'true') {

                $this->saveMarketCategoryAttributes($market_category->id, 'type_category', $schema->properties->category_attributes->properties);
                $this->saveMarketCategoryAttributes($market_category->id, 'type_sku', $schema->properties->sku_info_list->items->properties->sku_attributes->properties);
            }
            else
                $this->nullAndStorage(__METHOD__, [$market_category, $schema]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $market_category);
        }
    }


    private function getAEProductList($current_page, $page_size, $product_status_type, $gmt_modified_start = null)
    {
        try {
            // excepted_product_ids: [32962333569,32813963253]
            // gmt_modified_start: 2012-01-01 12:13:14
            $req = new AliexpressSolutionProductListGetRequest();
            $aeop_a_e_product_list_query = new ItemListQuery();
            $aeop_a_e_product_list_query->current_page = $current_page;
            $aeop_a_e_product_list_query->page_size = $page_size;

            // onSelling, offline, auditing, editingRequired
            $aeop_a_e_product_list_query->product_status_type = $product_status_type;
            //$aeop_a_e_product_list_query->have_national_quote = 'n';
            if (isset($gmt_modified_start)) $aeop_a_e_product_list_query->gmt_modified_start = $gmt_modified_start;

            $req->setAeopAEProductListQuery(json_encode($aeop_a_e_product_list_query));

            return $this->client->execute($req, $this->sessionKey);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$current_page, $page_size, $product_status_type, $gmt_modified_start]);
        }
    }


    private function getAllProductList($gmt_modified_start = null, $product_status_type = 'onSelling')
    {
        try {
            // onSelling; offline; auditing; and editingRequired
            $products = [];
            $current_page = 1;
            $page_size = 99;
            $resp = $this->getAEProductList($current_page, $page_size, $product_status_type, $gmt_modified_start);
            if ($resp->result->success == 'true' && $resp->result->product_count != '0') {
                $pages = intval($resp->result->total_page);

                foreach ($resp->result->aeop_a_e_product_display_d_t_o_list->item_display_dto as $item_display_dto)
                    $products[] = $item_display_dto;

                while ($current_page < $pages) {
                    $current_page++;
                    $resp = $this->getAEProductList($current_page, $page_size, $product_status_type, $gmt_modified_start);
                    if ($resp->result->success == 'true') {
                        foreach ($resp->result->aeop_a_e_product_display_d_t_o_list->item_display_dto as $item_display_dto)
                            $products[] = $item_display_dto;
                    }
                }
            }

            Storage::put($this->shop_dir. 'products/'.date('Y-m-d_H-i-s'). '_getAllProductList.json', json_encode($products));
            return $products;

        } catch (Throwable $th) {
            return $this->nullAndStorage(__METHOD__, [$gmt_modified_start, $product_status_type]);
        }
    }


    private function getAliexpressProduct($marketProductSku)
    {
        try {
            $req = new AliexpressSolutionProductInfoGetRequest();
            $req->setProductId($marketProductSku);

            $response =  $this->client->execute($req, $this->sessionKey);
            Storage::put($this->shop_dir. 'products/' .$marketProductSku. '.json', json_encode($response));
            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $marketProductSku);
        }
    }


    private function feedQueryRequest($jobId)
    {
        try {
            $req = new AliexpressSolutionFeedQueryRequest();
            $req->setJobId($jobId);

            return $this->client->execute($req, $this->sessionKey);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $jobId);
        }
    }


    public function removePropertyValue($marketCategoryId, $market_attribute_name, $property_value_value)
    {
        // "F00-00-10020-002"   #/category_attributes/Brand Name/value: #: 0 subschemas matched instead of one

        // market_id: 2                                 Aliexpress
        // $marketCategoryId: 702                       Ordenadores portatiles
        // $market_attribute_name: Screen Size
        // property_value: 200005075

        $market_id = 2; // Aliexpress
        try {
            if ($market_category = MarketCategory::whereMarketId($market_id)->where('marketCategoryId', $marketCategoryId)->first()) {
                if ($market_attribute = MarketAttribute::whereMarketCategoryId($market_category->id)->whereName($market_attribute_name)->first()) {
                    if ($property = Property::whereMarketAttributeId($market_attribute->id)->first()) {
                        if ($property_value_value == '4') {
                            //$this->nullAndStorage(__METHOD__, ['PROPERTY CUSTOM VALUE NULL', $marketCategoryId, $market_attribute_name, $property_value_value, $property]);
                            $property->custom_value = null;
                            $property->custom = false;
                            $property->save();
                            return true;
                        }
                        if ($property_value = PropertyValue::whereValue($property_value_value)->wherePropertyId($property->id)->first()) {
                            //$this->nullAndStorage(__METHOD__, ['REMOVED PROPERTY VALUE', $marketCategoryId, $market_attribute_name, $property_value_value, $property_value]);
                            $property_value->delete();
                            return true;
                        }
                    }
                }
            }

            $this->nullAndStorage(__METHOD__, ['NOT FOUND OR NOT REMOVED PROPERTY VALUE', $marketCategoryId, $market_attribute_name, $property_value_value]);

        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, [$marketCategoryId, $market_attribute_name, $property_value_value]);
        }
    }


    private function getShopJobRequest(ShopJob $shop_job)
    {
        try {
            $shop_job_result = [];
            $jobId = intval($shop_job->jobId);
            $shop_job_result[$jobId]['OPERATION'] = $shop_job->operation;

            $job_result = [];
            //$ae_errors_result = [];
            //$delete_create_list = [];
            $delete_local_list = [];
            $response = $this->feedQueryRequest($jobId);

            if (isset($response->job_id)) {

                $shop_job->total_count = $shop_job_result[$jobId]['TOTAL_COUNT'] = strval($response->total_item_count);
                $shop_job->success_count = $shop_job_result[$jobId]['SUCCESS_COUNT'] = strval($response->success_item_count);
                $shop_job->save();

                if (isset($response->result_list) && isset($response->result_list->single_item_response_dto))
                    foreach ($response->result_list->single_item_response_dto as $response_dto) {

                        $mps_sku = strval($response_dto->item_content_id);
                        //$product_id = FacadesMpe::getIdFromMPSSku($mps_sku);
                        $item_result = json_decode($response_dto->item_execution_result);
                        $marketProductSku = $item_result->productId ?? null;
                        $success = $item_result->success ?? null;

                        // Update marketProductSku in ShopProducts
                        if ($success == true && isset($marketProductSku)) {

                            $this->shop->shop_products()
                                ->where('mps_sku', $mps_sku)
                                ->whereNull('marketProductSku')
                                ->update(['marketProductSku' => $marketProductSku]);
                        }
                        // CHANGED PRODUCT SUPPLIER -> CHANGE SKU MPS ON ALIEXPRESS (OLD Product is More expensive than NEW PRODUCT)
                        // PRODUCT_PRICES_UPDATE: ErrorCode:F00-00-10014-015 -- sku_code in multiple_sku_update_list must already existed in this Aliexpress product, please check",
                        // PRODUCT_STOCKS_UPDATE: "ErrorCode:F00-00-10007-024 -- None of the sku belongs to the specific product. product_id:1005001320354447",

                        // Coincideixen marketProductSku i mps_sku PERO es fan PRODUCT_PRICES_UPDATE i PRODUCT_STOCKS_UPDATE al mateix temps
                        // sku_code in multiple_sku_update_list must already existed in this Aliexpress product, please check":"F00-00-10014-015"

                        elseif (isset($item_result->errorCode)) {

                            $schema = $item_result->integrationErrorCode->integrationRequestParameter->schemaPostRequest ?? json_encode($item_result);

                            $errorMessage = explode(PHP_EOL, $item_result->errorMessage);
                            if (!in_array($item_result->errorCode, $this->errors_ignored)) {

                                // Remove local only
                                // F00-00-10001-002: The product you would like to operate does not exist
                                if ($item_result->errorCode == "F00-00-10001-002") {
                                    $delete_local_list[] = $marketProductSku;       //$item_result->productId ?? null;       // marketProductSku

                                    $this->nullAndStorage(__METHOD__, ['F00-00-10001-002', $marketProductSku]);

                                }
                                // Remove Property Value: #/category_attributes/Brand Name/value: #: 0 subschemas matched instead of one
                                elseif ($item_result->errorCode == "F00-00-10020-002") {
                                    $schema_json = json_decode($schema, true);
                                    $msg = $errorMessage[0];

                                    // #/product_group_id: #: 0 subschemas matched instead of one
                                    if (mb_strpos($msg, 'group_id')) {
                                        $this->nullAndStorage(__METHOD__.'_product_group_id', [
                                            'shop_code' => $this->shop->code,
                                            'jobId' => $shop_job->jobId,
                                            'marketProductSku' => $marketProductSku ?? null,
                                            'error_code' => $item_result->errorCode,
                                            'error_message' => $errorMessage,
                                            'schema' => $schema_json
                                        ]);
                                    }
                                    else {
                                        // #/category_attributes/Brand Name/value: #: 0 subschemas matched instead of one
                                        // #/title_multi_language_list/0/title: expected maxLength: 128, actual: 130
                                        $market_attribute_name = mb_substr($msg, 22, strpos($msg, 'value:')-23);
                                        $marketCategoryId = $schema_json['category_id'];
                                        if ($property_value_value = $schema_json['category_attributes'][$market_attribute_name]['value'])
                                            $job_result['DELETED PROPERTY VALUES F00-00-10020-002'][$marketCategoryId][$market_attribute_name][$property_value_value][] = $this->removePropertyValue($marketCategoryId, $market_attribute_name, $property_value_value);
                                        else
                                            $job_result['DELETED PROPERTY VALUES F00-00-10020-002'][$marketCategoryId][] = $msg;
                                    }
                                }
                                // The attribute you input does not exist under the category, please check.Attribute key:Screen Size; Category:200001086
                                elseif ($item_result->errorCode == "F00-00-10003-005") {
                                    $schema_json = json_decode($schema, true);
                                    // #/category_attributes/Brand Name/value: #: 0 subschemas matched instead of one
                                    $msg = $errorMessage[0];
                                    $market_attribute_name = mb_substr($msg, 86, strpos($msg, 'Category:')-88);
                                    $marketCategoryId = $schema_json['category_id'];
                                    if ($property_value_value = $schema_json['category_attributes'][$market_attribute_name]['value'])
                                        $job_result['DELETED PROPERTY VALUES F00-00-10003-005'][$marketCategoryId][$market_attribute_name][$property_value_value][] = $this->removePropertyValue($marketCategoryId, $market_attribute_name, $property_value_value);
                                    else
                                        $job_result['DELETED PROPERTY VALUES F00-00-10003-005'][$marketCategoryId][] = $msg;
                                }
                                // "CHK_CATEGORY_ID_NO_PERMISSION:This is a restricted category. Please attain the authorization before publishing."
                                // Avisa d'aquest error però publica igualment
                                elseif (in_array($item_result->errorCode, ['707001032', 'CHK_CATEGORY_ID_NO_PERMISSION'])) {
                                    if (isset($marketProductSku) &&
                                        $shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first()) {

                                        if ($shop_product->market_category_id)
                                            $job_result['CHK_CATEGORY_ID_NO_PERMISSION'][$shop_product->market_category->name][] = $marketProductSku;
                                        else
                                            $job_result['CHK_CATEGORY_ID_NO_PERMISSION']['No Category Found'][] = $marketProductSku;
                                    }
                                    else
                                        $job_result['CHK_CATEGORY_ID_NO_PERMISSION']['No Product Found'][] = $marketProductSku;

                                }
                                elseif (!in_array($item_result->errorCode, ["F00-00-10007-024", "PRODUCT_IN_AUDIT_PENDING"]))
                                    $this->nullAndStorage(__METHOD__.'_SKU_NOT_FOUND', [
                                        'shop_code' => $this->shop->code,
                                        'jobId' => $shop_job->jobId,
                                        'marketProductSku' => $marketProductSku ?? null,
                                        'error_code' => $item_result->errorCode,
                                        'error_message' => $errorMessage,
                                        'schema' => json_decode($schema)
                                    ]);

                                // Existeix marketProductSku: 1005001398266600 Online i Local, pero NO tenen el mateix mps_sku
                                // "F00-00-10007-024","None of the sku belongs to the specific product. product_id:1005001398266600":
                                // ELIMINAR Online i PUJAR el Local
                                /* if ($item_result->errorCode == "F00-00-10007-024") {
                                    $delete_create_list[] = $marketProductSku;
                                } */


                                // F00-00-10003-005
                                // The attribute you input does not exist under the category, please check.Attribute key:Screen Size; Category:7080401
                                // F00-00-10020-002
                                // #/category_attributes/Brand Name/value: #: 0 subschemas matched instead of one
                                elseif ($item_result->errorCode != "PRODUCT_IN_AUDIT_PENDING") {
                                    //$job_result['ERRORS'][$item_result->errorCode][] = [$mps_sku, $errorMessage[0]];
                                    $job_result['ERRORS'][$item_result->errorCode][$errorMessage[0]][] = $mps_sku;
                                }
                            }
                            else {
                                $job_result['AE_SYSTEM_ERRORS'][$item_result->errorCode] = isset($job_result['AE_SYSTEM_ERRORS'][$item_result->errorCode]) ?
                                    $job_result['AE_SYSTEM_ERRORS'][$item_result->errorCode]++ : 1;

                                if ($item_result->errorCode == 'F00-00-10020-004') {
                                    if ($schema_json = json_decode($schema, true)) {
                                        $job_result['ERRORS'][$item_result->errorCode][] = $schema_json['sku_info_list'][0]['sku_code'];
                                    }
                                }
                                // Product is punished and locked
                                elseif ($item_result->errorCode == 'PRODUCT_IN_LOCK_MODE') {
                                    if (!$marketProductSku && $schema_json = json_decode($schema, true)) {
                                        $marketProductSku = $schema_json['productId'] ?? null;
                                    }
                                    if ($marketProductSku) {
                                        if ($shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first()) {
                                            $shop_product->marketProductSku = 'NO AUTH';
                                            $shop_product->save();
                                        }

                                        //$job_result['DELETE_ONLINE'][] = $marketProductSku;
                                    }

                                    $this->nullAndStorage(__METHOD__.'_PRODUCT_IN_LOCK_MODE', [
                                        'shop_code' => $this->shop->code,
                                        'jobId' => $shop_job->jobId,
                                        'marketProductSku' => $marketProductSku ?? null,
                                        'error_code' => $item_result->errorCode,
                                        'error_message' => $errorMessage,
                                        'schema' => json_decode($schema)
                                    ]);
                                }

                                Storage::append($this->shop_dir. 'errors/' .date('Y-m-d'). '_ERRORS_IGNORE.json',
                                    json_encode([$shop_job, $item_result->errorCode, $item_result, $schema]));
                            }
                        }
                        else {
                            $job_result['UNKNOWN'][] = [$mps_sku, $marketProductSku, $success, $response_dto];
                        }
                }
            }

            /* if (count($delete_create_list)) {

                $job_result['MP_SKU_DELETEDS'] = $delete_create_list;
                $this->deleteOnlineProducts($delete_create_list);
                $shop_products = $this->shop->shop_products()->whereIn('marketProductSku', $delete_create_list);
                //$this->shop->shop_products()->whereIn('marketProductSku', $delete_create_list)->update(['marketProductSku' => null]);
                $shop_products_to_nullable = $this->shop->shop_products()->whereIn('marketProductSku', $delete_create_list)->get();
                foreach ($shop_products_to_nullable as $shop_product_to_nullable) {
                    $shop_product_to_nullable->marketProductSku = null;
                    $shop_product_to_nullable->save();
                }
                $shop_products = $shop_products->get();
                $job_result['MP_SKU_CREATEDS_RESULT'] = $this->postNewProducts($shop_products);
            } */

            if (count($delete_local_list)) {

                $this->nullAndStorage(__METHOD__, ['delete_local_list', $delete_local_list]);

                foreach($delete_local_list as $marketProductSku) {
                    if ($marketProductSku && $shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku))
                        $shop_product->deleteSecure();
                }
            }

            if (isset($job_result['DELETE_ONLINE']) && count($job_result['DELETE_ONLINE'])) {

                $this->nullAndStorage(__METHOD__, ['DELETE_ONLINE', $job_result['DELETE_ONLINE']]);

                foreach($job_result['DELETE_ONLINE'] as $marketProductSku) {
                    $job_result['POST_DELETES_MSG'] = $this->deleteOnlineProducts($job_result['DELETE_ONLINE']);
                }
            }

            Storage::append($this->shop_dir. 'getJob/' .date('Y-m-d'). '_' .$jobId. '.json', json_encode($job_result));
            $shop_job_result[$jobId]['RESULT'] = $job_result;

            return $shop_job_result;

        } catch (Throwable $th) {
            //dd($th, $shop_job, $response, $shop_job_result, $schema_json, $market_attribute_name, $msg);

            return $this->msgWithErrors($th, __METHOD__, ['error_line' => $th->getLine(), $ms ?? null, $shop_job, $response, $shop_job_result]);
        }
    }


    private function getOrderListRequest($page = 1)
    {
        try {
            $req = new AliexpressSolutionOrderGetRequest();
            $param0 = new \OrderQuery();
            $param0->page_size = 50;
            $param0->current_page = $page;

            $param0->create_date_start = now()->addDays(-100)->toDateTimeString();
            //$param0->modified_date_start = now()->addDays(-10)->toDateTimeString();

            $param0->order_status_list = [];
            $param0->order_status_list[] = 'PLACE_ORDER_SUCCESS';
            $param0->order_status_list[] = 'IN_CANCEL';
            $param0->order_status_list[] = 'WAIT_SELLER_SEND_GOODS';
            $param0->order_status_list[] = 'SELLER_PART_SEND_GOODS';
            $param0->order_status_list[] = 'WAIT_BUYER_ACCEPT_GOODS';
            $param0->order_status_list[] = 'FUND_PROCESSING';
            $param0->order_status_list[] = 'IN_ISSUE';
            $param0->order_status_list[] = 'IN_FROZEN';
            $param0->order_status_list[] = 'WAIT_SELLER_EXAMINE_MONEY';
            $param0->order_status_list[] = 'RISK_CONTROL';
            $param0->order_status_list[] = 'FINISH';
            $req->setParam0(json_encode($param0));

            $response = $this->client->execute($req, $this->sessionKey);
            Storage::append($this->shop_dir. 'orders/' .date('Y-m-d'). '_orders_list.json', json_encode($response));

            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $page);
        }
    }


    private function getOrdersPages($response)
    {
        try {
            if ($response->result)
                return strval($response->result->total_page);

            // "code":"15","msg":"Remote service error","sub_code":"F00-10-10000-001","sub_msg":"system error, please try again later",
            //$this->nullAndStorage(__METHOD__, $response);

            return 0;

        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, $response);
            // $th->getMessage(): "Undefined property: ResultSet::$result"
            // $response->code: 0
            // $response->msg: Could not resolve host: gw.api.taobao.com
            return 0;
        }
    }


    private function getOrderInfoRequest($order_id)
    {
        try {
            $req = new AliexpressSolutionOrderInfoGetRequest();
            $param1 = new \OrderDetailQuery();
            $param1->ext_info_bit_flag = "11111";
            $param1->order_id = strval($order_id);
            $req->setParam1(json_encode($param1));

            return $this->client->execute($req, $this->sessionKey);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $order_id);
        }
    }


    private function getAEGroups()
    {
        try {
            $req = new AliexpressProductProductgroupsGetRequest();

            return $this->client->execute($req, $this->sessionKey);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getGroupsRequest()
    {
        try {
            $resp = $this->getAEGroups();

            $json = json_encode($resp, true);
            Storage::put($this->shop_dir. 'groups/' .date('Y-m-d'). '_' .$this->shop->id. '.json', $json);

            if (isset($resp->result) && !empty($resp->result)) {
                foreach ($resp->result->target_list->aeop_ae_product_tree_group as $parent) {

                    Group::updateOrCreate(
                        [
                            'market_id'             => $this->market->id,
                            'marketGroupId'         => $parent->group_id
                        ],
                        [
                            'shop_id'               => $this->shop->id,
                            'name'                  => $parent->group_name,
                            'marketGroupParentId'   => null,
                        ]
                    );

                    if ($parent->child_group_list) {
                        foreach ($parent->child_group_list->aeop_ae_product_child_group as $child) {

                            Group::updateOrCreate(
                                [
                                    'market_id'             => $this->market->id,
                                    'marketGroupId'         => $child->group_id
                                ],
                                [
                                    'shop_id'               => $this->shop->id,
                                    'name'                  => $child->group_name,
                                    'marketGroupParentId'   => $parent->group_id,
                                ]
                            );
                        }
                    }
                }

                return 'Grupos descargados correctamente';
            }
            else if ($resp->error_response) {
                return $this->msgAndStorage(__METHOD__, $resp->error_response, [$this->shop, $resp]);
            }

            return 'No se han encontrado grupos en esta Tienda';

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    public function getAECarriers()
    {
        try {
            $req = new AliexpressLogisticsRedefiningListlogisticsserviceRequest();
            $resp = $this->client->execute($req, $this->sessionKey);
            Storage::put($this->shop_dir. 'carriers/' .date('Y-m-d'). '_getAECarriers.json', json_encode($resp));

            return $resp;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $resp ?? null);
        }
    }


    private function getAEMessages()
    {
        /* try {
             $req = new AliexpressSolutionIssuePartnerRmaStateUpdateRequest();
            $rma_state_update = new RmaStateUpdateRequest();
            $req->setRmaStateUpdateRequest($rma_state_update);

            // arguments:param_message_faq_query
            $req = new AliexpressMessageFaqListRequest();
            $req->setParamMessageFaqQuery('contact');

            $req = new AliexpressIssueIssuelistGetRequest();
            $query_dto = new IssueApiListQueryDto();
            //$query_dto->buyer_login_id="ro1332405501rqcs";
            $query_dto->current_page="1";
            //$query_dto->issue_status="processing";
            //$query_dto->order_no="8124890287608049";
            $query_dto->page_size="10";
            $req->setQueryDto(json_encode($query_dto));

            $req = new Alibaba

            $resp = $this->client->execute($req, $this->sessionKey);



            Storage::put($this->shop_dir. 'messages/' .date('Y-m-d'). '_getAeMessages.json', json_encode($resp));

            return $resp;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        } */
    }



    /************** PRIVATE FUNCTIONS - BUILDERS ***************/


    private function buildPropertyFeed(AttributeMarketAttribute $attribute_market_attribute,
                                       Property $property,
                                       $value)
    {
        try {
            $property_feed = null;
            //$value = $product_attribute->value;
            if ($attribute_market_attribute->if_exists) {
                if ($value == $attribute_market_attribute->if_exists)
                    $property_feed[$property->name] = $attribute_market_attribute->if_exists_value;
            }
            else {
                // Model Number
                if ($property->custom) {
                    // 'Color'.'sku image url' -> equal
                    // 'Weight '.'value' -> strpos (Remove Kg)
                    $property_feed[$property->name] = ($attribute_market_attribute->mapping == 'equal') ?
                    mb_substr($value, 0, 70) :
                    preg_replace($attribute_market_attribute->pattern, "", $value);
                }
                // oneOf
                else {

                    $property_feed_value = $this->attribute_match(
                        $attribute_market_attribute->pattern,
                        $attribute_market_attribute->mapping,
                        $value,
                        $property->property_values);

                    if ($property_feed_value) {
                        $property_feed[$property->name] = $property_feed_value;
                    }
                }
            }

            // CustomValue
            if (!$property_feed && $property->custom_value) {
                $property_feed = [
                    $property->name                 => $property->custom_value,     // 'value' => '4',
                    $property->custom_value_field   => $value     // 'CustomValue' => Attribute VOX
                ];
            }

            return $property_feed;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$attribute_market_attribute, $property, $value]);
        }
    }


    private function buildPropertyFeedByFixed(AttributeMarketAttribute $attribute_market_attribute,
                                              Property $property)
    {
        try {
            $property_feed = null;

            $property_feed[$property->name] = ($property->datatype == 'string') ?
                $attribute_market_attribute->fixed_value : [$attribute_market_attribute->fixed_value];

            return $property_feed;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$attribute_market_attribute, $property]);
        }
    }


    private function buildPropertyFeedByField(AttributeMarketAttribute $attribute_market_attribute,
                                              Property $property,
                                              $field_value)
    {
        try {
            $property_feed = null;
            $value = is_object($field_value) ? $field_value->name : $field_value;
            if ($attribute_market_attribute->market_attribute->name == 'Item Condition')
                $value = ($value == 'Nuevo') ? 'New' : (($value == 'Remanufacturado') ? 'Refurbished' : 'Used');

            $property_feed = $this->buildPropertyFeed($attribute_market_attribute, $property, $value);

            return $property_feed;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$attribute_market_attribute, $property, $field_value]);
        }
    }



    private function buildPropertyFeedByAttribute(AttributeMarketAttribute $attribute_market_attribute,
                                                  Property $property,
                                                  Product $product)
    {
        try {
            $property_feed = null;

            $attribute = $attribute_market_attribute->attribute;
            $product_attributes = $product->product_attributes->where('attribute_id', $attribute->id);

            if ($property->datatype == 'string') {
                foreach ($product_attributes as $product_attribute) {
                    $property_feed = $this->buildPropertyFeed($attribute_market_attribute, $property, $product_attribute->value);
                    if ($property_feed) break;
                }
            }
            else if ($property->datatype == 'array') {
                $property_feed_values = null;
                foreach ($product_attributes as $product_attribute) {
                    $property_feed = $this->buildPropertyFeed($attribute_market_attribute, $property, $product_attribute->value);
                    //if ($property_feed) $attribute_feed[$property->name][] = $property_feed[$property->name];
                    if ($property_feed) $property_feed_values[] = $property_feed[$property->name];
                }
                if ($property_feed_values) $property_feed[$property->name] = $property_feed_values;
            }

            return $property_feed;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$attribute_market_attribute, $property, $product]);
        }
    }


    private function attributeFeedIsRight($attribute_feed, $property_requireds)
    {
        try {
            // When finish Mapping Market_Attributes, CHECK if ALL REQUIRED VALUES isn't NULL (Weight: value & unit)
            if (isset($attribute_feed)) {
                foreach ($property_requireds as $key => $value) {
                    // $property_requireds[$property->name] = 1;
                    if (!isset($attribute_feed[$key]))
                        return false;
                }

                // Exists ALL Required Properties
                return true;
            }

            return false;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$attribute_feed, $property_requireds]);
        }
    }


    private function buildItemAttributesFeed($type_name, ShopProduct $shop_product, Product $product)
    {
        try {
            $attributes_feed = [];
            $market_attributes = $shop_product->market_category->market_attributes($type_name)->get();
            foreach ($market_attributes as $market_attribute) {

                $attribute_feed = null;
                // $market_attribute->name == 'Brand Name'
                $property_requireds = null;
                // Market_attributes <-> Properties mapped

                foreach ($market_attribute->attribute_market_attributes as $attribute_market_attribute) {

                    $property_feed = null;
                    $property = $attribute_market_attribute->property;
                    if ($property->required) $property_requireds[$property->name] = 1;

                    // PROPERTY FIXED
                    if ($attribute_market_attribute->fixed && $attribute_market_attribute->fixed_value)
                        $property_feed = $this->buildPropertyFeedByFixed($attribute_market_attribute, $property);
                    // PRODUCT FIELD
                    elseif ($product_field = $attribute_market_attribute->field)
                        $property_feed = $this->buildPropertyFeedByField($attribute_market_attribute, $property, $product->{$product_field});
                    // PRODUCT ATTRIBUTE
                    elseif ($attribute_market_attribute->attribute_id)
                        $property_feed = $this->buildPropertyFeedByAttribute($attribute_market_attribute, $property, $product);

                    if (isset($property_feed)) {
                        foreach ($property_feed as $key => $value)
                            if ($property->datatype == 'array') $attribute_feed[$key][] = current($value);
                            else $attribute_feed[$key] = $value;
                    }
                }

                if ($this->attributeFeedIsRight($attribute_feed, $property_requireds))
                    $attributes_feed[$market_attribute->name] = $attribute_feed;    // $attributes_feed['Color'] = ['value' => 'Negro']
            }

            // Add (Ships from SPAIN) SKU attribute to all products of all categories
            // TODO: This attribute depends of Shop, NO Marketplace. Add Shop param to determine 'Ships From'
            if ($type_name == 'type_sku') {
                $attributes_feed['Ships From'] = [
                    'value' => '201336104',
                ];
            }
            // Type Category
            else {
                if (!isset($attributes_feed['Brand Name']))
                    $attributes_feed['Brand Name']['value'] = '203062806';       // ESNone	203062806
            }

            return $attributes_feed;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$type_name, $shop_product, $product]);
        }
    }


    private function buildItemSKUAttributesFeed(ShopProduct $shop_product, Product $product = null, $product_ids_today_orders)
    {
        try {
            $shop_product->setPriceStock(null, false, $product_ids_today_orders);
            $sku_info = [
                'sku_code'  => mb_substr($shop_product->mps_sku, 0, 50),
                'inventory' => $shop_product->enabled ? $shop_product->stock : 0,
                'price'     => $shop_product->price,
            ];

            // eq() equals, ne() not equals, gt() greater than, gte() greater than or equals, lt() less than, lte() less than or equals
            if ($shop_product->param_discount_price != 0 && $shop_product->param_starts_at && $shop_product->param_ends_at &&
                $shop_product->param_starts_at->lte(now()) && $shop_product->param_ends_at->gte(now())) {

                $sku_info['discount_price'] = $shop_product->param_discount_price;
            }

            $sku_attributes_feed = $this->buildItemAttributesFeed('type_sku', $shop_product, $shop_product->product);
            if (count($sku_attributes_feed))
                $sku_info['sku_attributes'] = $sku_attributes_feed;

            return $sku_info;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_product, $product]);
        }
    }


    private function buildItemFeed(ShopProduct $shop_product, $product_ids_today_orders)
    {
        try {
            if (!$shop_product->enabled) return null;
            if (!$shop_product->product->images->count()) return null;

            $market_category = $shop_product->market_category;
            if (!$market_category) return null;

            $title = FacadesMpe::buildString($shop_product->buildTitle(), 128);
            if (!isset($title) || $title == '') return null;

            $description = FacadesMpe::buildText($shop_product->buildDescription4Html());
            if (!isset($description) || $description == '') $description = $title;

            $images = $shop_product->product->getAllUrlImages(6)->toArray();

            $item_content = [];
            if ($shop_product->isUpgradeable()) {
                $item_content['aliexpress_product_id'] = $shop_product->marketProductSku;
                if ($this->group_id) $item_content['group_id'] = $this->group_id;
                if ($this->product_group_id) $item_content['product_group_id'] = $this->product_group_id;
                else {
                    $marketGroupId = $shop_product->shop->shop_groups()->where('market_category_id', $market_category->id)
                        ->leftjoin('groups', 'shop_groups.group_id', '=', 'groups.id')->value('marketGroupId');
                    if ($marketGroupId) $item_content['product_group_id'] = $marketGroupId;
                }
            }
            $item_content['category_id'] = $market_category->marketCategoryId;
            $item_content['title_multi_language_list'] = [
                ['locale' => 'es_ES', 'title' => $title]
            ];
            $item_content['description_multi_language_list'] = [
                [
                    'locale'        => 'es_ES',
                    'module_list'   => [
                        [
                            'type'  => 'html',
                            'html'  => ['content'   => $description]
                        ]
                    ]
                ]
            ];
            $item_content['locale'] = 'es_ES';
            $item_content['product_units_type'] = '100000015';
            $item_content['image_url_list'] = $images;      //explode(',', $images);

            $item_content['inventory_deduction_strategy'] = 'place_order_withhold';
            $item_content['package_weight'] = 1;
            $item_content['package_length'] = 1;
            $item_content['package_height'] = 1;
            $item_content['package_width'] = 1;

            $item_content['shipping_preparation_time'] = $this->shop->preparation;

            // Politica envíos
            if ($this->supplier_shippings)
                $item_content['shipping_template_id'] = $this->supplier_shippings[$shop_product->product->supplier_id] ?? $this->supplier_shippings[0] ?? '1019415074';
            else
                $item_content['shipping_template_id'] = $this->shop->shipping;

            $item_content['service_template_id'] = $this->shop->return;             // Politica devoluciones

            $item_content['user_defined_attribute_list'] = [];
            if ($shop_product->product->pn)
                $item_content['user_defined_attribute_list'][] =
                    [
                        'attribute_name'  => 'Part Number',
                        'attribute_value' => $shop_product->product->pn ?? $shop_product->product->model,
                    ];
            if ($shop_product->product->ean)
                $item_content['user_defined_attribute_list'][] =
                    [
                        'attribute_name'  => 'EAN13',
                        'attribute_value' => $shop_product->product->ean,
                    ];
            if ($shop_product->product->upc)
                $item_content['user_defined_attribute_list'][] =
                    [
                        'attribute_name'  => 'UPC',
                        'attribute_value' => $shop_product->product->upc,
                    ];
            if ($shop_product->product->isbn)
                $item_content['user_defined_attribute_list'][] =
                    [
                        'attribute_name'  => 'ISBN',
                        'attribute_value' => $shop_product->product->isbn,
                    ];

            // category attributes
            $category_attributes_feed = $this->buildItemAttributesFeed('type_category', $shop_product, $shop_product->product);
            if (count($category_attributes_feed))
                $item_content['category_attributes'] = $category_attributes_feed;

            // sku attributes

            // Build PARENT SKU Attributes
            $item_content['sku_info_list'][0] = $this->buildItemSKUAttributesFeed($shop_product, null, $product_ids_today_orders);
            if (isset($this->publish_packs) && $this->publish_packs->enabled) {
                // Afegir packs ofertes, tants com values, si hi ha stock suficient
                //$item_content['sku_info_list'][0] = $this->buildItemSKUAttributesFeed($shop_product);
            }

            $attributes = isset($shop_product->attributes) ? json_decode($shop_product->attributes, true) : [];
            foreach($attributes as $attribute_code => $attribute_value) {
                if ($attribute_code == 'Brand Name')
                    $item_content['category_attributes']['Brand Name']['value'] = $attribute_value;
            }

            // If this PARENT have any SKU child
            if ($shop_product->product->childs->count()) {

                foreach ($shop_product->product->childs as $child) {
                    // Build CHILD SKU Attributes
                    $child_sku_attributes = $this->buildItemSKUAttributesFeed($shop_product, $child, $product_ids_today_orders);
                    // Ships From
                    if (count($child_sku_attributes['sku_attributes']) > 1)
                        $item_content['sku_info_list'][] = $child_sku_attributes;
                }

                // Children’s sku attributes are not allowed -> Upgrade TITLE Parent with ALL Child Attributes
                if (count($item_content['sku_info_list']) == 1) {

                    foreach ($shop_product->product->childs as $child) {
                        // 'size', 'color', 'material', 'style', 'gender'
                        $sku_fields = $child->getSkuFields();
                        foreach ($sku_fields as $key => $value) {
                                $item_content['title_multi_language_list'][0]['title'] .= ' '.$value;
                        }
                    }
                }
            }

            $json_item_content = json_encode($item_content);
            $item_list = new SingleItemRequestDto();
            $mps_sku = mb_substr($shop_product->mps_sku, 0, 50);
            $item_list->item_content_id = $mps_sku; //$shop_product->getMPSSku(50);
            $item_list->item_content = $json_item_content;

            $shop_product->mps_sku = $mps_sku;
            $shop_product->save();

            return $item_list;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildItemSKUPriceFeed(ShopProduct $shop_product, $product_ids_today_orders)
    {
        try {
            $shop_product->setPriceStock(null, false, $product_ids_today_orders);
            //if (!$shop_product->wasChanged()) return null;

            $sku_info = [
                'sku_code'  => mb_substr($shop_product->mps_sku, 0, 50),        //$shop_product->getMPSSku(50),
                'price'     => $shop_product->price,
            ];

            // eq() equals, ne() not equals, gt() greater than, gte() greater than or equals, lt() less than, lte() less than or equals
            if ($shop_product->param_discount_price != 0 && $shop_product->param_starts_at && $shop_product->param_ends_at &&
                $shop_product->param_starts_at->lte(now()) && $shop_product->param_ends_at->gte(now())) {

                $sku_info['discount_price'] = $shop_product->param_discount_price;
            }

            return $sku_info;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildItemSKUStockFeed(ShopProduct $shop_product)
    {
        try {
            //$shop_product->setPriceStock();
            $sku_info = [
                'sku_code'  => mb_substr($shop_product->mps_sku, 0, 50),        //$shop_product->getMPSSku(50),
                'inventory' => $shop_product->enabled ? $shop_product->stock : 0,
            ];

            return $sku_info;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildItemPricesFeed(ShopProduct $shop_product, $product_ids_today_orders)
    {
        try {
            $item_content = [];
            if ($shop_product->isUpgradeable())
                $item_content['aliexpress_product_id'] = $shop_product->marketProductSku;

            // Build PARENT SKU Price
            if (!$item_content['multiple_sku_update_list'][0] = $this->buildItemSKUPriceFeed($shop_product, $product_ids_today_orders))
                return null;

            // If this PARENT have any SKU child
            /* If ($shop_product->product->childs_count) {
                foreach ($shop_product->product->childs as $child) {
                    // Build CHILD SKU Price
                    $item_content['multiple_sku_update_list'][] = $this->buildItemSKUPriceFeed($child->shop_product($this->shop->id));
                }
            } */

            if ($json_item_content = json_encode($item_content)) {
                $item_list = new \SingleItemRequestDto();
                $item_list->item_content_id = mb_substr($shop_product->mps_sku, 0, 50);     //$shop_product->getMPSSku(50);
                $item_list->item_content = $json_item_content;

                return $item_list;
            }

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildItemStocksFeed(ShopProduct $shop_product)
    {
        try {
            $item_content = [];
            if ($shop_product->isUpgradeable())
                $item_content['aliexpress_product_id'] = $shop_product->marketProductSku;

            // Build PARENT SKU Stock
            $item_content['multiple_sku_update_list'][0] = $this->buildItemSKUStockFeed($shop_product);

            // If this PARENT have any SKU child
            /* If ($shop_product->product->childs_count) {
                foreach ($shop_product->product->childs as $child) {
                    // Build CHILD SKU Stock
                    $item_content['multiple_sku_update_list'][] = $this->buildItemSKUStockFeed($child->shop_product($this->shop->id));
                }
            } */

            $json_item_content = json_encode($item_content);

            if ($json_item_content) {
                $item_list = new \SingleItemRequestDto();
                $item_list->item_content_id = mb_substr($shop_product->mps_sku, 0, 50);     //$shop_product->getMPSSku(50);
                $item_list->item_content = $json_item_content;

                return $item_list;
            }

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    /************** PRIVATE FUNCTIONS - POSTS ***************/


    private function postProducts($item_list, $feed_type)
    {
        try {
            $req = new AliexpressSolutionFeedSubmitRequest();
            $req->setOperationType($feed_type);

            // Test ITEM_CONTENT Null
            //$json_item_list = $this->cleanNullsJsonEncodeds($item_list);
            $json_item_list = json_encode($item_list);
            $req->setItemList($json_item_list);
            $response = $this->client->execute($req, $this->sessionKey);

            if ($response && isset($response->job_id)) {
                ShopJob::create([
                    'shop_id'   => $this->shop->id,
                    'jobId'     => $response->job_id,
                    'operation' => $feed_type,
                ]);

                return $response->job_id;
            }

            return $this->msgAndStorage(__METHOD__, $response->msg, [$this->shop->code, $feed_type, $response]);    //, $item_list]);
            // $response->code: 0,
		    // $response->msg: "Could not resolve host: gw.api.taobao.com"

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$this->shop->code, $item_list, $feed_type]);
        }
    }


    private function postPayload(Collection $shop_products, $job_type = 'PRODUCT_CREATE')       // PRODUCT_CREATE, PRODUCT_FULL_UPDATE
    {
        try {
            //$count = 0;
            $products_result = [];
            $products_result['count'] = $shop_products->count();
            $chunks = $shop_products->chunk(AliexpressWS::PAGINATE);
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            foreach ($chunks as $chunk) {

                $item_list = [];
                foreach ($chunk as $shop_product) {
                    if ($shop_product->product->images->count() && $item = $this->buildItemFeed($shop_product, $product_ids_today_orders))
                        $item_list[] = $item;
                }

                if (count($item_list))
                    $products_result['jobs'][] = $this->postProducts($item_list, $job_type);
            }

            /* foreach ($shop_products as $shop_product) {

                if ($shop_product->product->images->count()) {

                    if ($item = $this->buildItemFeed($shop_product)) {
                        $count++;
                        $item_list[] = $item;
                    }
                }

                if ($count == AliexpressWS::PAGINATE) {
                    $products_result['jobs'][] = $this->postProducts($item_list, $job_type);
                    $item_list = [];
                    $count = 0;
                }
            }

            if ($count > 0)
                $products_result['jobs'][] = $this->postProducts($item_list, $job_type); */

            return $products_result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_products, $job_type]);
        }
    }


    private function postPricesStocksPayload(Collection $shop_products)
    {
        try {
            $count = 0;
            $count_create = 0;
            $products_result = [];

            $marketProductSku_list_delete = [];
            $item_list_create = [];
            $item_list_prices = [];
            $item_list_stocks = [];
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            $products_result['count'] = $shop_products->count();
            foreach ($shop_products as $shop_product) {

                // DELETE & 'PRODUCT_CREATE'
                /* if (isset($shop_product->last_product_id)) {

                    if ($item = $this->buildItemFeed($shop_product)) {
                        $count_create++;
                        $item_list_create[] = $item;
                    }
                }
                // 'PRODUCT_PRICES_UPDATE' & 'PRODUCT_STOCKS_UPDATE'
                else { */
                    $item_price = $this->buildItemPricesFeed($shop_product, $product_ids_today_orders);
                    $item_stock = $this->buildItemStocksFeed($shop_product);
                    if (isset($item_price) && isset($item_stock)) {
                        $count++;
                        $item_list_prices[] = $item_price;
                        $item_list_stocks[] = $item_stock;
                    }
                //}

                // DELETE & CREATE PRODUCTS
                /* if ($count_create == AliexpressWS::PAGINATE) {
                    $this->deleteOnlineProducts($marketProductSku_list_delete);
                    $marketProductSku_list_delete = [];
                    $products_result['jobs']['PRODUCT_CREATE'][] = $this->postProducts($item_list_create, 'PRODUCT_CREATE');
                    $item_list_create = [];
                    $count_create = 0;
                } */

                // UPDATE PRICES & STOCKS
                if ($count == AliexpressWS::PAGINATE) {
                    $products_result['jobs']['PRODUCT_PRICES_UPDATE'][] = $this->postProducts($item_list_prices, 'PRODUCT_PRICES_UPDATE');
                    $products_result['jobs']['PRODUCT_STOCKS_UPDATE'][] = $this->postProducts($item_list_stocks, 'PRODUCT_STOCKS_UPDATE');
                    $item_list_prices = [];
                    $item_list_stocks = [];
                    $count = 0;
                }
            }

            // UPDATE PRICES & STOCKS
            if ($count > 0) {
                $products_result['jobs']['PRODUCT_PRICES_UPDATE'][] = $this->postProducts($item_list_prices, 'PRODUCT_PRICES_UPDATE');
                $products_result['jobs']['PRODUCT_STOCKS_UPDATE'][] = $this->postProducts($item_list_stocks, 'PRODUCT_STOCKS_UPDATE');
            }

            return $products_result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products);
        }
    }


    private function postGroupRequest($marketProductSku, $marketGroupId)
    {
        try {
            $req = new AliexpressPostproductRedefiningSetgroupsRequest();
            $req->setProductId($marketProductSku);
            $req->setGroupIds($marketGroupId);

            return $this->client->execute($req, $this->sessionKey);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$marketProductSku, $marketGroupId]);
        }
    }


    private function postProductToGroup(ShopProduct $shop_product)
    {
        try {
            // MPe Shop FAKE
            if ($this->shop->id == 1)
                $marketGroupId = '518650487';   // Home
            else {
                $market_category_id = $shop_product->market_category_id;
                $marketGroupId = $this->shop->shop_groups()->where('market_category_id', $market_category_id)
                    ->leftjoin('groups', 'shop_groups.group_id', '=', 'groups.id')->value('marketGroupId');
            }

            if ($marketGroupId) {
                $response = $this->postGroupRequest($shop_product->marketProductSku, $marketGroupId);
                if ($response->result->success == 'true') {
                    $shop_product->set_group = 1;
                    $shop_product->save();
                }

                return $response;
            }

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function postAETrackings(Order $order, $shipment_data)
    {
        try {
            $market_carrier = MarketCarrier::find($shipment_data['market_carrier_id']);
            $type = isset($shipment_data['full']) ? 'all' : 'part';

            $req = new AliexpressSolutionOrderFulfillRequest();
            $req->setServiceName($market_carrier->code);
            $req->setTrackingWebsite($market_carrier->url);
            $req->setOutRef($order->marketOrderId);
            $req->setSendType($type);
            $req->setDescription($shipment_data['desc']);
            $req->setLogisticsNo($shipment_data['tracking']);

            return $this->client->execute($req, $this->sessionKey);
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$order, $shipment_data]);
        }
    }


    /************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getBrands()
    {
        return 'Este Marketplace no requiere mapping de Marcas.';
    }


    public function getCategories($marketCategoryId = null)
    {
        try {
            $marketCategoryId = $marketCategoryId ?? 0;

            return $this->getAllCategoriesRequest($marketCategoryId);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketCategoryId);
        }
    }


    public function getAttributes(Collection $market_categories)
    {
        try {
            foreach ($market_categories as $market_category) {
                $this->getMarketCategoryAttributes($market_category);
            }

            return true;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $market_categories);
        }
    }


    public function getItemRowProduct(ShopProduct $shop_product)
    {
        try {
            $shop_product->setPriceStock();

            $market_category = $shop_product->market_category;
            $name = FacadesMpe::buildString($shop_product->buildTitle(), 128);
            $description = FacadesMpe::buildText($shop_product->product->buildDescriptionLong4Excel());
            $keywords = $shop_product->product->buildKeywords($name, 90);
            $images = $shop_product->product->getAllUrlImages(6)->toArray();

            $item_row = [];
            $item_row['category_id'] = $market_category->marketCategoryId;
            $item_row['sku_code'] = mb_substr($shop_product->mps_sku, 0, 50);       //$shop_product->getMPSSku(50);
            $item_row['inventory'] = $shop_product->enabled ? $shop_product->stock : 0;
            $item_row['price'] = $shop_product->price;

            // eq() equals, ne() not equals, gt() greater than, gte() greater than or equals, lt() less than, lte() less than or equals
            if ($shop_product->param_discount_price != 0 && $shop_product->param_starts_at && $shop_product->param_ends_at &&
                $shop_product->param_starts_at->lte(now()) && $shop_product->param_ends_at->gte(now())) {

                $item_row['discount_price'] = $shop_product->param_discount_price;
            }

            $item_row['ean'] = $shop_product->product->ean;
            $item_row['brand'] = $shop_product->product->brand->name;

            $item_row['locale'] = $this->locale;
            $item_row['shipping_preparation_time'] = $this->shop->preparation;

            // Politica envíos
            if ($this->supplier_shippings)
                $item_content['shipping_template_id'] = $this->supplier_shippings[$shop_product->product->supplier_id] ?? $this->supplier_shippings[0];
            else
                $item_content['shipping_template_id'] = $this->shop->shipping;

            $item_row['service_template_id'] = $this->shop->return;

            $item_row['product_units_type'] = '100000015';
            $item_row['inventory_deduction_strategy'] = 'place_order_withhold';

            $item_row['title'] = $name;
            $item_row['description'] = $description;
            $item_row['image1'] = $images[0];
            $item_row['image2'] = $images[1] ?? '';
            $item_row['image3'] = $images[2] ?? '';
            $item_row['image4'] = $images[3] ?? '';
            $item_row['image5'] = $images[4] ?? '';

            if ($this->group_id) $item_row['group_id'] = $this->group_id;
            if ($this->product_group_id) $item_row['product_group_id'] = $this->product_group_id;
            else {
                $marketGroupId = $shop_product->shop->shop_groups()->where('market_category_id', $market_category->id)
                    ->leftjoin('groups', 'shop_groups.group_id', '=', 'groups.id')->value('marketGroupId');
                if ($marketGroupId) $item_row['product_group_id'] = $marketGroupId;
            }

            return $item_row;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function getFeed(ShopProduct $shop_product)
    {
        try {
            $item = $this->buildItemFeed($shop_product, null);

            dd([$item->item_content_id, json_decode($item->item_content)]);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function getJobs()
    {
        try {
            $jobs_result = [];
            $shop_jobs = $this->shop->shop_jobs()->whereNull('total_count')->get();
            $jobs_result['jobs'] = $shop_jobs->count();
            foreach ($shop_jobs as $shop_job)
                $jobs_result[] = $this->getShopJobRequest($shop_job);

            return $jobs_result;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function getOrders()
    {
        try {
            $count_orders = 0;
            $page = 0;
            do {
                $page++;
                $response = $this->getOrderListRequest($page);
                if (isset($response->error_response)) {
                    return $this->nullAndStorage(__METHOD__, $response);
                }
                else {
                    $pages = $this->getOrdersPages($response);
                    $count_orders = $this->updateOrCreateOrders($response);
                }

            } while ($page < $pages);

            return $count_orders;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function getGroups()
    {
        return $this->getGroupsRequest();
    }


    public function getCarriers()
    {
        try {
            $res = $this->getAECarriers();
            if ($res->result_list) {

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

            return $this->msgAndStorage(__METHOD__, $res->error_response ?? null, $res);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function getOrderComments(Order $order)
    {
        return false;
    }


    public function getMessages()
    {
        //$res = $this->getAEMessages();
        //return $res;
        return false;
    }


    public function getPayments()
    {
        return "Aliexpress importa los pagos manualmente. ".
            "Ir a Aliexpress | Pedidos | Alipay | Español | Actividad de la cuenta y descargar, por meses, todas las operaciones a FACTURAS\2021\MARKETPLACES\MPE\MAITE_DETALL_PAGAMENTS".
            "Seguidamente, ir a MPe | Utilidades | Import Aliexpress | Import Payments e importar todas las descargas.";
    }


    /************ PUBLIC FUNCTIONS - POSTERS *******************/


    public function postNewProduct(ShopProduct $shop_product)
    {
        try {
            $shop_products = new Collection([$shop_product]);
            return $this->postNewProducts($shop_products);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function postUpdatedProduct(ShopProduct $shop_product)
    {
        try {
            $shop_products = new Collection([$shop_product]);
            return $this->postUpdatedProducts($shop_products);
        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function postPriceProduct(ShopProduct $shop_product)
    {
        try {
            $shop_products = new Collection([$shop_product]);
            return $this->postPricesStocks($shop_products);
        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function postNewProducts($shop_products = null)
    {
        try {
            if ($this->shop->shop_jobs()->whereOperation('PRODUCT_CREATE')->whereNull('total_count')->exists())
                return null;

            $shop_products_brands = ShopProduct::select('shop_products.*')
                ->leftJoin('products', 'products.id', '=', 'shop_products.product_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->where('shop_id', 1)
                ->whereIn('brands.name', self::NO_AUTH_BRANDS)
                ->get();

            foreach ($shop_products_brands as $shop_product) {
                //$shop_product->deleteSecure();
                $shop_product->marketProductSku = 'NO AUTH';
                $shop_product->save();
            }

            $shop_products = $this->getShopProducts4Create($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos nuevos en esta Tienda';

            return $this->postPayload($shop_products, 'PRODUCT_CREATE');

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postUpdatedProducts($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            return $this->postPayload($shop_products, 'PRODUCT_FULL_UPDATE');

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postPricesStocks($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            return $this->postPricesStocksPayload($shop_products);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postGroups($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            $count = 0;
            $shop_products = $shop_products->where('set_group', 0)->all();
            foreach ($shop_products as $shop_product) {
                if (!$shop_product->set_group) {
                    if ($this->postProductToGroup($shop_product)) $count++;
                }
            }

            return $count;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function removeProduct($marketProductSku = null)
    {
        try {
            if (isset($marketProductSku)) {
                $res = $this->deleteOnlineProducts([$marketProductSku]);
                if ($shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first())
                    $shop_product->deleteSecure();

                return $res;
            }
            else {
                return 'Entra el marketProductSku';

                // REMOVE SHOP_PRODUCTS CODITIONAL
                $shop_products = $this->getShopProducts4Update(null);
                if (!$shop_products->count()) {
                    return 'No se han encontrado productos para eliminar en esta Tienda';
                }

                $result['count'] = 0;
                $result['count_local'] = 0;
                $result['marketProductSkus'] = [];
                foreach ($shop_products as $shop_product) {

                    // REMOVE SHOP_PRODUCTS BY MARKET_CATEGORY_ID
                    if ($shop_product->stock == 0 ||
                        $shop_product->market_category_id == 323 || $shop_product->market_category_id == 293 ||
                        $shop_product->market_category_id == 638 || $shop_product->market_category_id == 227 ||
                        $shop_product->market_category_id == 230 || $shop_product->market_category_id == 370 ||
                        $shop_product->market_category_id == 357 || $shop_product->market_category_id == 83 ||
                        $shop_product->market_category_id == 520 || $shop_product->market_category_id == 5510 ||
                        $shop_product->market_category_id == 325 || $shop_product->market_category_id == 30 ||
                        $shop_product->market_category_id == 342 || $shop_product->market_category_id == 546 ||
                        $shop_product->market_category_id == 326 || $shop_product->market_category_id == 366 ||
                        $shop_product->market_category_id == 284 || $shop_product->market_category_id == 408 ||
                        $shop_product->market_category_id == 247 || $shop_product->market_category_id == 246 ||
                        $shop_product->market_category_id == 558 || $shop_product->market_category_id == 252 ||
                        $shop_product->market_category_id == 380 || $shop_product->market_category_id == 503 ||
                        $shop_product->market_category_id == 258 || $shop_product->market_category_id == 5488 ||
                        $shop_product->market_category_id == 267 || $shop_product->market_category_id == 6446 ||
                        $shop_product->market_category_id == 27 || $shop_product->market_category_id == 385 ||
                        $shop_product->market_category_id == 307 || $shop_product->market_category_id == 283 ||
                        $shop_product->market_category_id == 275 || $shop_product->market_category_id == 6650 ||
                        $shop_product->market_category_id == 523 || $shop_product->market_category_id == 5816 ||
                        $shop_product->market_category_id == 257 || $shop_product->market_category_id == 639 ||
                        $shop_product->market_category_id == 233 || $shop_product->market_category_id == 554 ||
                        $shop_product->market_category_id == 564 || $shop_product->market_category_id == 337 ||
                        $shop_product->market_category_id == 286 || $shop_product->market_category_id == 595 ||
                        $shop_product->market_category_id == 602) {

                        $result['marketProductSkus'] = $shop_product->marketProductSku;
                        $result['count']++;
                    }
                }

                if (count($result['marketProductSkus']) && $this->setAEOfflineProducts($result['marketProductSkus']) == 'true') {
                    foreach ($result['marketProductSkus'] as $marketProductSku) {
                        $shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first();
                        $shop_product->deleteSecure();
                        $result['count_local']++;
                    }
                }

                return $result;
            }

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketProductSku);
        }
    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        try {
            $res = $this->postAETrackings($order, $shipment_data);

            if (isset($res->result))
                return true;

            return $this->msgAndStorage(__METHOD__, $res->error_response ?? null, [$order, $shipment_data, $res]);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$order, $shipment_data]);
        }
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return false;
    }


    public function synchronize()
    {
        try {
            // onSelling; offline; auditing; and editingRequired
            $res = [];

            $res['jobs'] = $this->getJobs();

            // NO APROBADOS -> NO AUTH + DELETE ONLINE
            $offers_no_aproveds = $this->getAllProductList(null, 'editingRequired');
            $no_auths = [];
            foreach ($offers_no_aproveds as $offer) {

                $marketProductSku = strval($offer->product_id);
                $res['DELETE_ONLINE'][] = $marketProductSku;
                if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku)) {
                    $shop_product->marketProductSku = 'NO AUTH';
                    $shop_product->save();
                    $no_auths[] = [$shop_product->product_id, $shop_product->ean];
                }
            }

            $res['NO AUTH editingRequired'] = $no_auths;

            // onSelling && auditing
            $offers = $this->getAllProductList(null, 'onSelling');
            $offers_pending = $this->getAllProductList(null, 'auditing');
            $offers = array_merge($offers, $offers_pending);

            $marketProductSkus = [];
            foreach ($offers as $offer) {

                $marketProductSku = strval($offer->product_id);
                $marketProductSkus[] = $marketProductSku;
                if (!$shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku)) {

                    $res['DELETE_ONLINE'][] = $marketProductSku;
                }
            }

            // NO AUTH -> (Local BUT NOT Online)
            // Stock == 0 -> Delete Local + Online
            $nulls = [];
            if (count($marketProductSkus)) {
                $shop_products = $this->getShopProducts4Update();
                foreach ($shop_products as $shop_product) {
                    if (!in_array($shop_product->marketProductSku, $marketProductSkus)) {

                        $nulls[] = $shop_product->marketProductSku;
                        $shop_product->marketProductSku = null; //'NO AUTH';
                        $shop_product->save();
                    }
                    elseif ($shop_product->stock == 0) {
                        $res['DELETE_ONLINE'][] = $shop_product->marketProductSku;
                        $shop_product->deleteSecure();
                    }
                }
            }

            $res['NO AUTH NO Online'] = $nulls;

            // REMOVE ONLINE OFFERS THAT NOT IN SERVER
            if (isset($res['DELETE_ONLINE']))
                $res['POST_DELETES_MSG'] = $this->deleteOnlineProducts($res['DELETE_ONLINE']);

            // REMOVE WITHOUT STOCK
            foreach ($this->shop->shop_products as $shop_product)
                if ($shop_product->stock == 0 && !$shop_product->isUpgradeable() && $shop_product->marketProductSku != 'NO AUTH')
                    $shop_product->deleteSecure();

            return $res;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $res);
        }
    }


    public function syncCategories()
    {
        $changes = [];
        try {
            $market_params = $this->market->market_params;
            $mp_offers = $this->getAllProductList(null, 'onSelling');
            foreach ($mp_offers as $offer) {

                $mp_product = $this->getAliexpressProduct($offer->product_id);
                if (isset($mp_product->result) && isset($mp_product->result->category_id)) {

                    if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $offer->product_id)) {
                        $new_category_code = $mp_product->result->category_id;
                        $shop_product_category_code = $shop_product->market_category->marketCategoryId;

                        if ($new_category_code != $shop_product_category_code) {
                            $shop_product->longdesc = utf8_encode($shop_product->longdesc);
                            $changes['CATEGORY CHANGES'][$new_category_code][] = [
                                'old_code' => $shop_product_category_code,
                                'mp_sku' => $shop_product->marketProductSku,
                                'shop_product' => [
                                    'id'                    => $shop_product->id,
                                    'product_id'            => $shop_product->product_id,
                                    'market_code'           => $shop_product->market->code,
                                    'shop_code'             => $shop_product->shop->code,
                                    'market_category_id'    => $shop_product->market_category_id,
                                    'market_category_name'  => $shop_product->market_category->name,
                                ],
                            ];

                            if ($new_market_category = $this->market->market_categories()->firstWhere('marketCategoryId', $new_category_code)) {

                                /* $shop_product->market_category_id = $new_market_category->id;
                                $shop_product->save(); */

                                $old_mp_fee = $shop_product->param_mp_fee;
                                $shop_product->setMarketParams($market_params);
                                if ($old_mp_fee != $shop_product->param_mp_fee)
                                    $changes['MP FEE CHANGES'][] = [
                                        'mp_sku' => $shop_product->marketProductSku,
                                        'old_mp_fee' => $old_mp_fee,
                                        'new_mp_fee' => $shop_product->param_mp_fee
                                    ];
                            }
                            else
                                $changes['NO MARKET_CATEGORIES FOUND'][] = $new_category_code;
                        }
                    }
                    else
                        $changes['NOT FOUND'][] = $offer;
                }
            }

            Storage::append($this->shop_dir. 'categories/' .date('Y-m-d'). '_syncCategories.txt', json_encode($changes));
            return $changes;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $mp_offers ?? null);
        }
    }


    public function removeWithoutStock()
    {
        $res = [];
        try {
            foreach ($this->shop->shop_products as $shop_product) {
                $shop_product->setPriceStock();
                if ($shop_product->stock == 0) {
                    if (!$shop_product->isUpgradeable())
                        $shop_product->deleteSecure();
                    else {
                        if ($shop_product->deleteSecure())
                            $res['DELETE_ONLINE'][] = $shop_product->marketProductSku;
                    }
                }
            }

            // REMOVE ONLINE OFFERS
            if (isset($res['DELETE_ONLINE'])) {
                $res['POST_DELETES'] = $this->deleteOnlineProducts($res['DELETE_ONLINE']);
            }

            return $res;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $res);
        }
    }


    /************* UTIL FUNCTIONS *********************/


    public function extractCompetitorPrice($stringPrice)
    {
        try {
            $stringPrice = str_replace('€', '', $stringPrice);

            return FacadesMpe::roundFloatEsToEn($stringPrice);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $stringPrice);
        }
    }


    public function getScrapeField()
    {
        return 'products.pn';
    }



    /************* REQUEST FUNCTIONS *********************/


    public function getProduct($marketProductSku)
    {
        $mp_product = $this->getAliexpressProduct($marketProductSku);
        dd($mp_product);

        $shop_job = ShopJob::where('jobId', '200000553400565204')->first();
        dd($this->getShopJobRequest($shop_job));


        //$this->removeProduct($marketProductSku);
        /* if (isset($mp_product->result) && isset($mp_product->result->category_id)) {
        } */

    }


    public function getAllProducts()
    {
        $res = null;
        $offline_count = 0;
        $offline_products = [];
        // onSelling; offline; auditing; and editingRequired
        $products_list = $this->getAllProductList(null, 'onSelling');
        foreach ($products_list as $offer) {
            dd($offer, $products_list);
            /* $offline_count++;
            if ($marketProductSku = strval($offer->product_id)) {
                $offline_products[] = $marketProductSku;
                if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku))
                    $shop_product->deleteSecure();
            } */

        }

        dd($products_list);

        // PUT OFFLINE -> ONLINE
        /* if (count($offline_products))
            $res = $this->setAEOnlineProducts($offline_products); */

        // REMOVE OFFLINE PRODUCTS FROM ALIEXPRESS
        // dd($offline_count, $offline_products);
        if (count($offline_products))
            $res = $this->deleteOnlineProducts($offline_products);


        dd($offline_products, $res);




        /* $changes = $this->syncCategories();
        dd($changes); */

        // onSelling; offline; auditing; and editingRequired
        // auditing: Pendiente de aprovación
        $products_list = $this->getAllProductList(null, 'onSelling');
        dd($products_list);




        // Delete ALL Offline products
        /* $list = [];
        foreach($products_list as $product) {

            $list[] = strval($product->product_id);
            if (count($list) == 100) {
                $req = new AliexpressSolutionBatchProductDeleteRequest();
                $req->setProductIds(implode(',', $list));
                $this->client->execute($req, $this->sessionKey);
                $list = [];
            }
        }

        if (count($list) > 0) {
            $req = new AliexpressSolutionBatchProductDeleteRequest();
            $req->setProductIds(implode(',', $list));
            $this->client->execute($req, $this->sessionKey);
        }
        dd($products_list); */




        $delete_list = '';
        $delete_count = 0;
        $all_products = [
            'total'             => 0,
            'local_and_shop'    => [],
            'only_shop'         => [],
            'stock_shop'        => [],
            'deleted'           => [],
        ];
        $current_page = 1;
        $page_size = 99;
        $product_status_type = 'onSelling';
        $response = $this->getAEProductList($current_page, $page_size, $product_status_type);

        if ($response->result->success == 'true') {
            $all_products['total'] = intval($response->result->product_count);
            $pages = intval($response->result->total_page);
            foreach ($response->result->aeop_a_e_product_display_d_t_o_list->item_display_dto as $item_dto) {
                $product_id = strval($item_dto->product_id);
                $product_max_price = strval($item_dto->product_max_price);
                $subject = strval($item_dto->subject);
                $shop_products = $this->shop->shop_products()->where('marketProductSku', $product_id);
                if ($shop_products->count()) {
                    $shop_product = $shop_products->first();
                    $all_products['local_and_shop'][] = [$product_id, $product_max_price == $shop_product->price, $subject];
                } else {
                    $all_products['only_shop'][] = [$product_id, $product_max_price, $subject];
                    /*$response_product = $this->getProductInfo($product_id);
                    if (($response_product->result) && ($response_product->result->aeop_ae_product_s_k_us)) {
                        $stock = intval($response_product->result->aeop_ae_product_s_k_us->global_aeop_ae_product_sku->ipm_sku_stock);
                        $sku_code = strval($response_product->result->aeop_ae_product_s_k_us->global_aeop_ae_product_sku->sku_code);
                        if ($response_product->result->aeop_ae_product_s_k_us->global_aeop_ae_product_sku->sku_stock == 'true')
                            $all_products['stock_shop'][] = [$product_id, $product_max_price, $subject, $sku_code, $stock];
                        else {
                            $all_products['only_shop'][] = [$product_id, $product_max_price, $subject];
                            if ($delete_list)  $delete_list .= ',' .$product_id;
                            else $delete_list = $product_id;
                            $delete_count++;
                            if ($delete_count == 20) {
                                $response_delete = $this->deleteProduct($delete_list);
                                $all_products['deleted'][] = $response_delete;
                                Storage::append($this->shop_dir. 'products/' .date('Y-m-d_H-i'). '_get_all_products_DELETE.json', json_encode($all_products['deleted']));
                                $delete_list = '';
                                $delete_count = 0;
                            }
                        }
                    }*/
                }
            }
            /*if ($delete_list) {
                $response_delete = $this->deleteProduct($delete_list);
                $delete_list = '';
                $all_products['deleted'][] = $response_delete;
                Storage::append($this->shop_dir. 'products/' .date('Y-m-d_H-i'). '_get_all_products_DELETE.json', json_encode($all_products['deleted']));
            }*/

            for ($current_page = 2; $current_page < $pages; $current_page++) {

                $response = $this->getAEProductList($current_page, $page_size, $product_status_type);
                if ($response->result->success == 'true') {
                    foreach ($response->result->aeop_a_e_product_display_d_t_o_list->item_display_dto as $item_dto) {
                        $product_id = strval($item_dto->product_id);
                        $product_max_price = strval($item_dto->product_max_price);
                        $subject = strval($item_dto->subject);
                        $shop_products = $this->shop->shop_products()->where('marketProductSku', $product_id);
                        if ($shop_products->count()) {
                            $shop_product = $shop_products->first();
                            $all_products['local_and_shop'][] = [$product_id, $product_max_price == $shop_product->price, $subject];
                        } else {
                            $all_products['only_shop'][] = [$product_id, $product_max_price, $subject];
                            /*if ($delete_list)  $delete_list .= ',' .$product_id;
                            else $delete_list = $product_id;
                            $delete_count++;*/
                            /*if ($delete_count == 20) {
                                $response_delete = $this->deleteProduct($delete_list);
                                $all_products['deleted'][] = $response_delete;
                                Storage::append($this->shop_dir. 'products/' .date('Y-m-d_H-i'). '_get_all_products_DELETE.json', json_encode($all_products['deleted']));
                                $delete_list = '';
                                $delete_count = 0;
                            }*/
                        }
                    }
                }

                /*if ($delete_list) {
                    $response_delete = $this->deleteProduct($delete_list);
                    $delete_list = '';
                    $all_products['deleted'][] = $response_delete;
                    Storage::append($this->shop_dir. 'products/' .date('Y-m-d_H-i'). '_get_all_products_DELETE.json', json_encode($all_products['deleted']));
                }*/
            }
        }

        Storage::put($this->shop_dir. 'products/' .date('Y-m-d_H-i'). '_get_all_products.json', json_encode($all_products));

        return $all_products;
    }


    public function removeAllProducts()
    {
        // onSelling; offline; auditing; and editingRequired
        // auditing: Pendiente de aprovación
        $responses = $this->getAllProductList(null, 'offline');

        $remove_items = [];
        $no_product = null;
        foreach ($responses as $offer) {

            $marketProductSku = strval($offer->product_id);
            $remove_items[] = $marketProductSku;

            $shop_product = $this->shop->shop_products()
                ->where('marketProductSku', $marketProductSku)
                ->first();

            if ($shop_product)
                $shop_product->deleteSecure();
            else
                $no_product[] = $offer;
        }

        if (count($remove_items)) {
            $res = $this->deleteOnlineProducts($remove_items);
        }

        if ($this->shop->shop_products->count()) {
            $shop_products = $this->shop->shop_products;
            foreach ($shop_products as $shop_product) {
                $shop_product->deleteSecure();
            }
        }

        dd($responses, $no_product, $remove_items, $res ?? null, $shop_products ?? null);
    }



    public function getShopConfig($supplier_id = null)
    {
        Storage::makeDirectory($this->shop_dir. 'shop_params');

        $merchantProfile = $this->getMerchantProfile();
        $shippingTemplate = $this->getShippingTemplate();
        $serviceTemplate = $this->getServiceTemplate();
        $groups = $this->getAEGroups();
        $brandsList = isset($supplier_id) ? $this->getBrandsList($supplier_id) : [];

        dd($merchantProfile, $shippingTemplate, $serviceTemplate, $groups, $brandsList);
    }


    public function setDefaultShopFilters()
    {
        $res = [];

        // No Scooters, No patinetes, No monpatines, ...


        // INTEGRAR ONLY MPE FILES
        // 13-30	Esprinet:	1%
        // 14-27	Desyman:	Mateixos preus. Alguna vegada inclus a favor de MPe
        // 36		Dmi:		Idiomund no integra DMI
        // 38-23	Megasur:	Mateixos preus

        // INTEGRAR IDIOMUND + MPE
        // 10-29	Vinzeo: 	4.5% - 6%
        // 8-31	    Ingram:		Molta diferència
        // 11-35	Techdata:	Molta diferència    MPE NO INTEGRA

        // 35 Techdata NO ACTUALITZA
        // SPEEDLER: NO INTEGRAR ENLLOC
        $shop_id = $this->shop->id;
        $status_id = 1;     // nuevo
        $cost_min = 30;
        $cost_max = 1000;
        $stock_min = 5;
        $supplier_ids = [1, 8, 10, 11, 13, 14, 16, 24, 27, 29, 30, 31, 36];   // 1 Blanes, 14-27 desyman, 13-30 Esprinet, // 35 Techdata NO ACTUALITZA
        $own_suppliers = [22, 23, 26, 37];        // 22 Depau, 23 Megasur, 26 SCE, 37 Aseuropa, 39 Infortisa
        $supplier_ids = array_merge($supplier_ids, $own_suppliers);

        //$supplier_id = Supplier::whereCode('mcr')->pluck('id')->first();
        $categories_id_1000 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            // Tabletas gráficas
            ->whereNotIn('categories.name', ['Cartuchos de tinta y tóner', 'Accesorios de bricolaje', 'Productos del hogar',
                'Accesorios informáticos', 'Cables', 'Audio',
                'Soportes para pantallas de proyección', 'Artículos de oficina', 'Sistemas operativos', 'Accesorios de telefonía',
                'Accesorios para cámaras de vigilancia','Accesorios y piezas para cámaras','Accesorios y piezas para televisores',
                'Amplificadores de señal','Baterías para teléfonos inalámbricos','Cables de almacenamiento y transmisión de datos',
                'Cajas registradoras','Calendarios, organizadores y agendas','Campanas de cocina','Consumibles para impresoras',
                'Desinfectantes domésticos','Dispositivos firewall y seguridad de red','Frigoríficos',
                'Impresoras de Tinta Sólida','Lámparas','Lavadoras','Lavavajillas','Placas de cocina','Plotters','Procesadores de señales',
                'Puentes y enrutadores','Secadoras','Servidores informáticos','Sistemas de aire acondicionado',
                'Software corporativo y de productividad','Software de seguridad y antivirus','Ventiladores de techo','Tóners',
                'Sondas y visores','Receptores náuticos de audio y vídeo',
                'Conmutadores KVM', 'Lectores de códigos de barras','Repetidores y transceptores','Etiquetas y etiquetas adhesivas',
                'Purificadores de aire', 'Software de videojuegos', 'Adaptadores','Cables de alimentación','Accesorios para piscinas y jacuzzis',
                'Dispositivos biométricos'])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $categories_id_500 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->whereIn('categories.name', ['Monitores de ordenador','Paneles táctiles','Pantallas para proyección','Altavoces',
                /*'Altavoces de repuesto para portátiles' ,'Discos duros',*/'Discos Duros Externos','Discos duros SSD',
                /* 'Fuentes de alimentación para ordenadores','Memoria RAM','Placas madre',
                'Piezas de refrigeración del sistema y ventiladores para ordenadores', */'Altavoces de repuesto para tablets',
                'Altavoces de repuesto para TV y barras de sonido'/* ,'Cajas de ordenadores y servidores','Componentes para ordenadores',
                'Concentradores y conmutadores Switches Hubs','Conmutadores KVM','Dispositivos de almacenamiento' */])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $categories_id_400 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->where('categories.name', 'LIKE', '%'.'Accesorios'.'%')
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $categories_id_300 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->whereIn('categories.name', ['Impresoras de Tickets','Impresoras Fotográficas','Impresoras Inyección de Tinta','Impresoras Láser',
                'Impresoras Multifuncionales','Impresoras, fotocopiadoras y faxes'/* ,'Supresores de sobretensión y regletas SAI UPS',
                'Puentes y enrutadores','Redes','Adaptadores de Power over Ethernet POE',
                'Enrutadores inalámbricos','Enrutadores y puertas de enlace VoIP','Adaptadores y tarjetas de red','Escáneres',
                'Puntos de acceso inalámbrico, Amplificadores y Repetidores de Red','Impresoras Matriciales',
                'Kits de mantenimiento de impresoras','Kits de tambores de impresoras','Adaptadores USB',
                'Adaptadores y tarjetas de interfaz de red','Adaptadores y tarjetas sintonizadoras de televisión',
                'Cargadores y adaptadores de alimentación' */])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $categories_id_100 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->whereIn('categories.name', ['Hornos','Frigoríficos'])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        $categories_stock_20 = Category::select('categories.id', 'categories.name')
            ->join('products', 'products.category_id', '=', 'categories.id')
            ->whereIn('categories.name', ['Tarjetas de vídeo'])
            ->whereIn('products.supplier_id',$supplier_ids)
            ->where('products.cost', '>=', $cost_min)
            ->where('products.cost', '<=', $cost_max)
            ->where('products.stock', '>=', $stock_min)
            ->groupBy('categories.id')
            ->orderBy('categories.name')
            //->get();
            ->pluck('categories.id')
            ->all();

        //dd($categories_id_100, $categories_id_300, $categories_id_500, $categories_id_1000);

        $filter_groups = [
            [
                'cost_max'      => 1000,
                'category_ids'  => $categories_id_1000
            ],
            [
                'cost_max'      => 500,
                'category_ids'  => $categories_id_500
            ],
            [
                'cost_max'      => 400,
                'category_ids'  => $categories_id_400
            ],
            [
                'cost_max'      => 300,
                'category_ids'  => $categories_id_300
            ],
            [
                'cost_max'      => 100,
                'category_ids'  => $categories_id_100
            ],
        ];

        foreach ($supplier_ids as $supplier_id) {

            foreach ($filter_groups as $filter_group) {

                $cost_max = $filter_group['cost_max'];
                $category_ids = $filter_group['category_ids'];

                //dd($cost_max, $category_ids, $filter_group, $supplier_id);

                foreach ($category_ids as $category_id) {
                    if (Product::whereCategoryId($category_id)
                        ->whereSupplierId($supplier_id)
                        ->whereNotNull('name')
                        ->where('cost', '>=', $cost_min)
                        ->where('cost', '<=', $cost_max)
                        ->where('stock', '>=', $stock_min)
                        ->count()) {

                        ShopFilter::updateOrCreate([
                            'shop_id'       => $shop_id,
                            'supplier_id'   => $supplier_id,
                            'category_id'   => $category_id
                        ],[
                            'stock_min'     => $stock_min,
                            'cost_min'      => $cost_min,
                            'cost_max'      => $cost_max,
                            'status_id'     => 1,
                        ]);
                    }
                }
            }
        }


        // Tarjetas de vídeo
        foreach ($supplier_ids as $supplier_id) {

            $cost_max = 300;
            $category_ids = $categories_stock_20;
            $stock_min = 20;

            foreach ($category_ids as $category_id) {
                if (Product::whereCategoryId($category_id)
                    ->whereSupplierId($supplier_id)
                    ->whereNotNull('name')
                    ->where('cost', '>=', $cost_min)
                    ->where('cost', '<=', $cost_max)
                    ->where('stock', '>=', $stock_min)
                    ->count()) {

                    ShopFilter::updateOrCreate([
                        'shop_id'       => $shop_id,
                        'supplier_id'   => $supplier_id,
                        'category_id'   => $category_id
                    ],[
                        'stock_min'     => $stock_min,
                        'cost_min'      => $cost_min,
                        'cost_max'      => $cost_max,
                        'status_id'     => 1,
                    ]);
                }
            }
        }

        dd('FI');

        return redirect()->route('shops.shop_filters.index', [$this->shop])->with('status', 'Filtros creados correctamente.');
    }


    public function getJob($jobId, $operation = null)
    {
        $res = $this->feedQueryRequest($jobId);
        dd($jobId, $res);
    }


    public function getAllLocalProducts()
    {
        dd('getAllLocalProducts');
        $local_connection = DB::connection('mysql_oldlocal');
        $local_query = $local_connection->table('products');
        $local_products = $local_query->get();
        Storage::put($this->shop_dir. 'local.json', json_encode($local_products));
        dd($local_products);

        /*foreach ($local_products as $local_product) {
            $product_id = $local_product->ae_product_id;
            $sku_code = $local_product->sku;

            if ( ($product = Product::where('supplierSku', $sku_code)->first()) &&
                ($shop_product = $this->shop->shop_products()->where('product_id', $product->id)->first()) ) {
                    $shop_product->marketProductSku = $product_id;
                    $shop_product->save();
            }
        }*/

    }


    public function getMarketCategoryAttributesRequest($market_category)
    {
        $res = $this->getProductSchemaRequest($market_category->marketCategoryId);
        $schema = json_decode($res->result->schema);

        return [$res, $schema];
    }


    public function SetAllSkuProducts()
    {
        $json_products = json_decode(file_get_contents(storage_path('app/' .$this->shop_dir. 'local.json')), false);
        dd($json_products);

        foreach ($json_products as $json_product) {
            $product_id = $json_product->ae_product_id;
            $sku_code = $json_product->sku;

            if ( ($product = Product::where('supplierSku', $sku_code)->first()) &&
                ($shop_product = $this->shop->shop_products()->where('product_id', $product->id)->first()) ) {
                    if ($shop_product->marketProductSku == 'ERROR') {
                        $shop_product->marketProductSku = $product_id;
                        $shop_product->save();
                    }
            }
        }

    }


    public function getCategorySchema($marketCategoryId)
    {
        $schema = $this->getProductSchemaRequest($marketCategoryId);
        dd($schema);
    }


    public function getCategoryTreeRequest($marketCategoryId)
    {
        $category_tree = $this->getCategoryTree($marketCategoryId);
        dd($category_tree);
    }


    public function requestTranslateColors()
    {
        // COLOR CATEGORIES TRANSLATE EN TO ES, FOR ALIEXPRESS MARKET
        $log = [];
        $no_log = [];

        $market_attributes = $this->market->market_attributes()
            ->where('name', 'Color')
            ->get();

        foreach ($market_attributes as $market_attribute) {
            foreach ($market_attribute->properties as $property) {
                foreach ($property->property_values as $property_value) {

                    if ($property_value->name != null) {
                        $es = Dictionary::where('en', 'like', $property_value->name)->value('es');
                        if ($es) {
                            $property_value->name = $es;
                            $property_value->save();
                        }
                        if ($es) $log[] = [$es, $property_value->name];
                        else $no_log[] = [$es, $property_value->name];
                    }

                }
            }
        }
        dd($log, $no_log);
    }


    public function requestGetOrder($marketOrderId)
    {
        $order_info = $this->getOrderInfoRequest($marketOrderId);

        dd($order_info);
    }


    private function getMerchantProfile()
    {
        $req = new AliexpressSolutionMerchantProfileGetRequest();
        $response = $this->client->execute($req, $this->sessionKey);

        Storage::put($this->shop_dir. 'shop_params/get_merchant_profile.json', json_encode($response));
        return $response;
    }


    public function getShippingTemplate()
    {
        $req = new AliexpressFreightRedefiningListfreighttemplateRequest();
        $response = $this->client->execute($req, $this->sessionKey);

        Storage::put($this->shop_dir. 'shop_params/get_shipping_template.json', json_encode($response));
        return $response;
    }


    public function getServiceTemplate()
    {
        $req = new AliexpressPostproductRedefiningQuerypromisetemplatebyidRequest();
        $req->setTemplateId("-1");
        $response = $this->client->execute($req, $this->sessionKey);

        Storage::put($this->shop_dir. 'shop_params/get_service_template.json', json_encode($response));
        return $response;
    }


    private function getBrandsList($supplier_id)
    {
        $brands = Product::select('brands.name')
            ->whereSupplierId($supplier_id)
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->groupBy('brands.name')
            ->get();

        return $brands->pluck('name')->all();
    }

}
