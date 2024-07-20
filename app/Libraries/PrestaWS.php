<?php

namespace App\Libraries;


use App\Address;
use App\Buyer;
use App\Country;
use App\Currency;
use App\MarketCategory;
use App\Order;
use App\RootCategory;
use App\Shop;
use App\ShopProduct;
use App\Status;
use App\Traits\HelperTrait;
use App\MarketBrand;
use App\Models\PrestashopCategory;
use App\Models\PrestashopProduct;
use App\Models\PrestashopProductShop;
use App\Models\PrestashopProductLang;
use App\Models\PrestashopStockAvailable;
use App\Models\PrestashopImageShop;
use Facades\App\Facades\Mpe as FacadesMpe;
use CURLFile;
use DOMDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PrestaShopWebservice;
use PrestaShopWebserviceException;
use SimpleXMLElement;
use Throwable;


class PrestaWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    public $apiUrl = null;
    public $apiKey = null;
    public $debug = false;
    public $webService = null;

    public $shops = [1];
    public $languages = [1];
    public $id_shop_default = 1;
    public $rate_standard_id = 1;        // id_tax_rules_group
    public $rate_reduced_id = 2;
    public $rate_super_reduced_id = 3;
    public $standard_rate = 21;          // iva standard

    public $presta_products = null;      // ps_product
    public $presta_products_shop = null; // ps_product_shop
    public $presta_product_langs = null; // ps_product_lang
    public $presta_stocks = null;        // ps_stock_available
    public $presta_categories = null;    // ps_category
    public $presta_images = null;        // ps_image
    public $presta_image_shops = null;   // ps_image_shop

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
        'default_logo' => null,
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



    function __construct(Shop $shop)
    {
        // TEST API
        // http://www.pceducacion.com/api + TOKEN

        parent::__construct($shop);

        $this->apiUrl = $shop->endpoint;
        $this->apiKey = $shop->token;

        $this->webService = new PrestaShopWebservice($this->apiUrl, $this->apiKey, $this->debug);

        // Models
        $this->presta_products = new PrestashopProduct();
        $this->presta_products->setConnection('prestashop_'.$this->market->code);
        $this->presta_products_shop = new PrestashopProductShop();
        $this->presta_products_shop->setConnection('prestashop_'.$this->market->code);
        $this->presta_product_langs = new PrestashopProductLang();
        $this->presta_product_langs->setConnection('prestashop_'.$this->market->code);
        $this->presta_stocks = new PrestashopStockAvailable();
        $this->presta_stocks->setConnection('prestashop_'.$this->market->code);
        $this->presta_categories = new PrestashopCategory();
        $this->presta_categories->setConnection('prestashop_'.$this->market->code);
        $this->presta_image_shops = new PrestashopImageShop();
        $this->presta_image_shops->setConnection('prestashop_'.$this->market->code);
    }


    /************** PRIVATE FUNCTIONS PRESTA ***************/


    private function getPrestaCategories($marketCategoryId)
    {
        try {
            $opt['resource'] = 'categories';
            $opt['display'] = 'full';       //"[id, id_parent, is_root_category, level_depth, name]";
            $xml = $this->webService->get($opt);
            $presta_categories = $xml->children()->children();

            Storage::put($this->storage_dir. 'categories/all.xml', $xml->asXML());
            return $presta_categories;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $marketCategoryId);
        }
    }


    private function getPrestaProducts()
    {
        try {
            $opt['resource'] = 'products';
            $opt['display'] = 'full';       //"[id,active,available_for_order,name]";
            //$opt['filter[active]'] = 1;

            $xml = $this->webService->get($opt);
            return $xml->children()->children();
        }
        catch (PrestaShopWebserviceException $e) {
            return $this->msgWithErrors($e, __METHOD__, null);
        }
    }


    private function getPrestaProductSchema()
    {
        $opt = null;
        try {
            $xml_string = null;
            $schema_product_path = $this->storage_dir. 'schemas/product.xml';
            if (Storage::exists($schema_product_path)) {
                $xml_string = Storage::get($schema_product_path);
                $xml = new SimpleXMLElement($xml_string);
            }
            else {
                // Get Products Schema Online
                /* $opt['schema'] = 'synopsis'; */
                $opt['resource'] = 'products';
                $opt['schema'] = 'blank';
                $xml = $this->webService->get($opt);
                Storage::put($schema_product_path, $xml->asXML());
            }

            $item = $xml->children()->children();
            unset($item->id);
            unset($item->position_in_category);
            unset($item->manufacturer_name);
            unset($item->id_default_combination);
            //unset($item->associations);
            unset($item->quantity);
            unset($item->position);
            unset($item->id_shop_default);
            unset($item->date_add);
            unset($item->date_upd);
            unset($item->associations->combinations);
            unset($item->associations->product_options_values);
            unset($item->associations->product_features);
            unset($item->associations->stock_availables->stock_available->id_product_attribute);

            return $xml->children();

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, null);
        }
    }


    private function postPrestaProductImage($marketProductSku, $image_path)
    {
        try {
            $key = $this->apiKey;
            $presta_url = $this->apiUrl. '/api/images/products/' .$marketProductSku. '/';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);    // 1
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
            curl_setopt($ch, CURLOPT_URL, $presta_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_USERPWD, $key.':');

            $args['image'] = new CurlFile($image_path);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $args);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);      //, CURLINFO_HTTP_CODE);
            $err     = curl_errno( $ch );
            $errmsg  = curl_error( $ch );
            curl_close($ch);

            if (200 == $httpCode) {
                $xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA);
                //return strval($xml->image->id);
                return $xml;
            }

            return $this->nullAndStorage(__METHOD__, [$marketProductSku, $image_path, $result, $httpCode, $errmsg, $err, $args]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$marketProductSku, $image_path]);
        }
    }


    private function postPrestaProducts(Collection $shop_products)
    {
        try {
            $dom = new DOMDocument('1.0', 'utf-8'); // DOMDocument to store products
            $node = $dom->createElement("prestashop");
            $dom->appendChild($node);
            $xml_product = $this->getPrestaProductSchema();
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            foreach($shop_products as $shop_product) {
                if ($shop_product->price < 0) {
                    $this->nullAndStorage(__METHOD__.'_PRICE_NEGATIVE', $shop_product);
                    continue;
                }

                $item_product = $this->buildItemProduct($shop_product, $xml_product, $product_ids_today_orders);
                $dom_import = dom_import_simplexml($item_product);
                $dom->documentElement->appendChild($dom->importNode($dom_import, true));
            }

            $opt['resource'] = 'products';
            $opt['postXml'] = $dom->saveXML();
            Storage::put($this->storage_dir. 'products/' .date('Y-m-d_H-i'). '_pre_new.xml', $dom->saveXML());
            $xml = $this->webService->add($opt);
            Storage::append($this->storage_dir. 'products/' .date('Y-m-d_H-i'). '_new.xml', $xml->asXML());

            return $xml;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $shop_products);
        }
    }


    /************** PRIVATE FUNCTIONS ***************/


    private function getAllCategoriesRequest($marketCategoryId)
    {
        try {
            $cats = [];
            if ($presta_categories = $this->getPrestaCategories($marketCategoryId)) {

                foreach ($presta_categories as $presta_category) {

                    $marketCategoryId = (string)$presta_category->id;
                    $categoryIdParent = (string)$presta_category->id_parent;
                    $category_name = (string)$presta_category->name->language;  // Get First language

                    // Home
                    if ((int)$presta_category->is_root_category) {

                        $root_category = RootCategory::firstOrCreate([
                            'market_id'         => $this->market->id,
                            'name'              => $category_name,
                            'marketCategoryId'  => $marketCategoryId,
                        ],[]);

                        $cats['cats'][$marketCategoryId] = [
                            'id'                => $root_category->id,
                            'next_path'         => $category_name,
                            'root_category_id'  => $root_category->id,
                        ];
                    }
                    // NO save Root [id": "1", "id_parent": "0", "level_depth": "0", "is_root_category": "0"]
                    elseif ((int)$presta_category->level_depth > 1) {

                        $root_category_id = $cats['cats'][$categoryIdParent]['root_category_id'] ?? null;
                        $path = $cats['cats'][$categoryIdParent]['next_path'] ?? null;
                        $parent_category_id = null;
                        if (isset($cats['cats'][$categoryIdParent]) &&
                            $cats['cats'][$categoryIdParent]['id'] != $cats['cats'][$categoryIdParent]['root_category_id']) {

                            $parent_category_id = $cats['cats'][$categoryIdParent]['id'] ?? null;
                        }

                        // If the parent category HAS NOT yet been created, try TWICE
                        if (!isset($root_category_id)) {
                            $cats['missing_parents'][] = $presta_category;
                        }
                        else {
                            $market_category = MarketCategory::updateOrCreate(
                                [
                                    'market_id'         => $this->market->id,
                                    'marketCategoryId'  => $marketCategoryId,
                                ],
                                [
                                    'name'              => $category_name,
                                    'path'              => $path,
                                    'root_category_id'  => $root_category_id,
                                    'parent_id'         => $parent_category_id,
                                ]
                            );

                            $cats['cats'][$marketCategoryId] = [
                                'id'                => $market_category->id,
                                'next_path'         => $path. ' / ' .$category_name,
                                'root_category_id'  => $root_category_id,
                            ];
                        }
                    }
                }
            }

            return $cats;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $marketCategoryId);
        }
    }



    /************** PRIVATE FUNCTIONS - ORDERS & BUYERS & ADDRESSES ***************/


    private function getCountry($id_country)
    {
        try {
            if (!$id_country) return null;

            $opt = null;
            $opt['resource'] = 'countries/'.$id_country;
            $xml = $this->webService->get($opt);
            if (isset($xml->country)) {
                $country = null;
                $country = Country::firstOrCreate([
                    'code'      => (string)$xml->country->iso_code,
                ],[
                    'name'      => (string)$xml->country->name->language,   // Get First language
                ]);

                return $country;
            }

            return null;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $id_country);
        }
    }


    private function getState($id_state)
    {
        try {
            if (!$id_state) return null;

            $opt = null;
            $opt['resource'] = 'states/'.$id_state;
            $xml = $this->webService->get($opt);
            if (isset($xml->state))
                return (string)$xml->state->name;

            return null;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $id_state);
        }
    }


    private function getOrderState($current_state)
    {
        try {
            $opt = null;
            $opt['resource'] = 'order_states/'.$current_state;
            $xml = $this->webService->get($opt);
            if (isset($xml->order_state)) {
                $status = null;
                $status = Status::firstOrCreate([
                    'market_id'             => $this->market->id,
                    'marketStatusName'      => (string)$xml->order_state->name->language,   // Get First language
                    'type'                  => 'order',
                ],[
                    'name'                  => (string)$xml->order_state->name->language,   // Get First language
                ]);

                return $status;
            }

            return null;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $current_state);
        }
    }


    private function getCurrency($id_currency)
    {
        try {
            if (!$id_currency) return null;

            $opt = null;
            $opt['resource'] = 'currencies/'.$id_currency;
            $xml = $this->webService->get($opt);
            if (isset($xml->currency)) {
                $currency = null;
                $currency = Currency::firstOrCreate([
                    'code'                  => (string)$xml->currency->iso_code,
                ],[
                    'name'                  => (string)$xml->currency->name,
                ]);

                return $currency;
            }

            return null;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $id_currency);
        }
    }


    private function firstOrCreateAddress(SimpleXMLElement $order, $type = 'shipping_address')
    {
        try {
            $opt = null;
            $id_address = ($type == 'shipping_address') ? (string)$order->id_address_delivery : (string)$order->id_address_invoice;
            $opt['resource'] = 'addresses/'.$id_address;
            $xml = $this->webService->get($opt);
            if (isset($xml->address)) {
                Storage::put($this->storage_dir. 'addresses/' .date('Y-m-d '). '_'.$id_address.'.xml', $xml->asXML());
                $presta_address = $xml->address;

                $country = $this->getCountry((string)$presta_address->id_country);
                $state = $this->getState((string)$presta_address->id_state);
                $name = (string)$presta_address->alias ?? '';
                if (isset($presta_address->company)) $name = '(' .(string)$presta_address->company. ') ' .$name;
                $phone = !empty($presta_address->phone_mobile) ? (string)$presta_address->phone_mobile :
                    (!empty($presta_address->phone) ? (string)$presta_address->phone : null);

                $address = Address::updateOrCreate([
                    'country_id'            => $country->id ?? null,
                    'market_id'             => $this->market->id,
                    'name'                  => $name,
                ],[
                    'marketBuyerId'         => (string)$presta_address->id_customer ?? null,
                    'address1'              => (string)$presta_address->address1 ?? null,
                    'address2'              => (string)$presta_address->address2 ?? null,
                    'city'                  => (string)$presta_address->city ?? null,
                    'state'                 => $state,
                    'zipcode'               => (string)$presta_address->postcode ?? null,
                    'phone'                 => $phone,
                ]);

                return $address;
            }

            return null;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, [$order, $type]);
        }
    }


    private function getCustomer($id_customer)
    {
        try {
            $opt = null;
            $opt['resource'] = 'customers/'.$id_customer;
            $xml = $this->webService->get($opt);
            if (isset($xml->customer))
                return $xml->customer;

            return null;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $id_customer);
        }
    }


    private function updateOrCreateOrder(SimpleXMLElement $presta_order)
    {
        try {
            $shipping_address = $this->firstOrCreateAddress($presta_order, 'shipping_address');
            $billing_address = $this->firstOrCreateAddress($presta_order, 'billing_address');

            $presta_customer = $this->getCustomer((string)$presta_order->id_customer);
            $buyer = Buyer::updateOrCreate([
                'market_id'             => $this->market->id,
                'marketBuyerId'         => (string)$presta_order->id_customer,
            ],[
                // ES shopper OR Real name
                'name'                  => $billing_address->name ?? null,
                'shipping_address_id'   => $shipping_address->id ?? null,
                'billing_address_id'    => $billing_address->id ?? null,
                'email'                 => isset($presta_customer) ? (string)$presta_customer->email : null,
                'phone'                 => $shipping_address->phone ?? null,
                'company_name'          => isset($presta_customer) ? (string)$presta_customer->company : null,
                'tax_region'            => null,
                'tax_name'              => null,
                'tax_value'             => null,
            ]);

            $status = $this->getOrderState((string)$presta_order->current_state);
            $currency = $this->getCurrency((string)$presta_order->id_currency);

            $order = Order::whereMarketId($this->market->id)->whereShopId($this->shop->id)->where('marketOrderId', $presta_order->id)->first();
            $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $order = Order::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'marketOrderId'         => (string)$presta_order->id,
            ],[
                'buyer_id'              => $buyer->id ?? null,
                'shipping_address_id'   => $shipping_address->id ?? null,
                'billing_address_id'    => $billing_address->id ?? null,
                'currency_id'           => $currency->id ?? null,
                'status_id'             => $status->id ?? null,
                'type_id'               => null,
                'SellerId'              => (string)$presta_order->id_customer,
                'SellerOrderId'         => (string)$presta_order->id,
                'info'                  => (string)$presta_order->payment,
                'price'                 => (float)$presta_order->total_paid,
                'tax'                   => 0,
                'shipping_price'        => (float)$presta_order->total_shipping,
                'shipping_tax'          => 0,
                'notified'              => $notified,
                'notified_updated'      => $notified_updated,
            ]);

            $order->created_at = Carbon::createFromTimeString((string)$presta_order->date_add)->format('Y-m-d H:i:s');
            $order->updated_at = Carbon::createFromTimeString((string)$presta_order->date_upd)->format('Y-m-d H:i:s');
            $order->save();

            foreach ($presta_order->associations->order_rows->order_row as $order_row) {

                /*$shop_product = $this->shop->shop_products()
                    ->where('marketProductSku', (string)$order_row->product_id)
                    ->first();

                $price = floatval($order->total_paid);
                $bfit = $this->getBfit($price,
                    $shop_product->param_fee ?? 0,
                    $shop_product->param_bfit_min ?? 0,
                    $shop_product->tax ?? 21); */

                $order_item = $order->updateOrCreateOrderItem(
                    (string)$order_row->id,
                    null,
                    (string)$order_row->product_id,
                    (string)$order_row->product_name,
                    (int)$order_row->product_quantity,
                    (float)$order_row->unit_price_tax_incl,
                    0,
                    0,
                    0,
                    null);

                /* $order_item = OrderItem::updateOrCreate([
                    'order_id'          => $order->id,
                    'product_id'        => $shop_product->product->id ?? null,
                    'marketOrderId'     => $order->marketOrderId,
                    'marketItemId'      => (string)$order_row->id,
                ],[
                    'marketProductSku'  => $shop_product->marketProductSku ?? null,
                    'currency_id'       => $currency->id,
                    'MpsSku'            => null,
                    'name'              => (string)$order_row->product_name,
                    'info'              => '',
                    'quantity'          => (int)$order_row->product_quantity,
                    'price'             => (float)$order_row->unit_price_tax_incl,
                    'tax'               => 0,
                    'shipping_price'    => 0,
                    'shipping_tax'      => 0,

                    'cost'              => $shop_product->getCost() ?? 0,
                    'bfit'              => $bfit,
                    'mp_bfit'           => 0,
                ]); */
            }

            return $order->id;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $presta_customer);
        }
    }


    private function getOrdersRequest()
    {
        try {
            $opt = null;
            $res = null;
            $opt['resource'] = 'orders';
            $opt['display'] = 'full';
            $opt['limit'] = '0,20';
            //$opt['filter[id]'] = $marketProductSku;
            $xml = $this->webService->get($opt);
            $orders = $xml->orders->children();
            Storage::put($this->storage_dir. 'orders/' .date('Y-m-d '). '.xml', $xml->asXML());

            if (isset($orders))
                foreach($orders as $order) {
                    $res[] = $this->updateOrCreateOrder($order);
                }

            return $res;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, null);
        }
    }


    /************** PRIVATE FUNCTIONS - PRODUCT & IMAGES HELPERS ***************/


    private function getMarketBrandId($brand_name)
    {
        try {
            /* $marketBrandId = null;
            if (isset($product->brand_id) && $market_brand = $product->brand->market_brand($this->market->id)->first())
                $marketBrandId = $market_brand->marketBrandId;

            return $marketBrandId; */

            try {
               /*  if (strtoupper(substr($brand_name, 0, 2)) == 'HP') $brand_name = 'Hewlett-Packard';
                if ($brand_name == 'Dell technologies') $brand_name = 'Dell'; */

                if (!isset($brand_name)) return null;

                return $this->market->market_brands()->whereName($brand_name)->value('marketBrandId');

            } catch (Throwable $th) {
                return $this->nullWithErrors($th, __METHOD__, $brand_name);
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $brand_name);
        }
    }


    private function buildPrestaTitle($title)
    {
        try {
            $title = $this->changeAccents($title);
            //$title = str_replace(['¦', 'ª', '®', '™', '\\', ';', '&amp;', '#039', '(', ')'], [''], $title);

            $title = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '', $title);
            $title = preg_replace('/[^A-Za-z0-9\-\.\"\_\/ ]/', '', $title);

            return trim(mb_substr(ucwords(mb_strtolower($title)), 0, 126));

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $title);
        }
    }


    private function buildPrestaDescription($desc)
    {
        try {
            $desc = $this->changeAccents($desc);
            $desc = str_replace(['¦', 'ª','®','™', '\\', ';', '&amp;', '#039'], ['-', 'a','','', '', ' -', '', ''], $desc);

            //$desc = preg_replace('/^[^<>;=#{}]*$/u', '', $desc);
            //$desc = preg_replace('/[^a-zA-Z0-9\s\'\:\/\-\pL]/u', '', $desc);
            //$desc = preg_match('/^[^<>;=#{}]*$/u', $desc);

            /* $desc = str_replace(array('[\', \']'), '', $desc);
            $desc = preg_replace('/\[.*\]/U', '', $desc);
            $desc = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', ' ', $desc);
            $desc = htmlentities($desc, ENT_COMPAT, 'utf-8');
            $desc = preg_replace('/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);/i', '\\1', $desc );
            $desc = preg_replace(array('/[^a-z0-9]/i', '/[-]+/') , ' ', $desc);
            return $desc; */

            $desc = preg_replace('/[^A-Za-z0-9\-\.\"\_\/ ]/', '', $desc);
            $desc = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '', $desc);

            return $desc;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $desc);
        }
    }


    private function buildPrestaMpsSku($mpsSku)
    {
        try {
            $mpsSku = $this->changeAccents($mpsSku);
            $mpsSku = str_replace(['=', '¦', 'ª', '®', '™', '\\', ';', '&amp;', '#039'], [''], $mpsSku);
            $mpsSku = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '', $mpsSku);
            //$mpsSku = preg_replace('/[^A-Za-z0-9\-\.\"\_\/ ]/', '', $mpsSku);

            return trim(mb_substr(ucwords(mb_strtolower($mpsSku)), 0, 32));

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $mpsSku);
        }
    }


    private function buildPrestaReference($pn)
    {
        try {
            // Expected format: /^[^<>;={}]*$/u
            $pn = $this->changeAccents($pn);
            $pn = str_replace(['=', '¦', 'ª', '®', '™', '\\', ';', '&amp;', '#039'], ['', '-', 'a', '', '', '', ' -', '', ''], $pn);
            //$mpsSku = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '', $mpsSku);
            //$mpsSku = preg_replace('/[^A-Za-z0-9\-\.\"\_\/ ]/', '', $mpsSku);

            return trim($pn);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $pn);
        }
    }


    private function buildItemProduct(ShopProduct $shop_product, SimpleXMLElement $xml_product, $product_ids_today_orders)
    {
        try {
            $shop_product->setPriceStock(null, false, $product_ids_today_orders);
            $market_category = $shop_product->market_category;
            $marketBrandId = $this->getMarketBrandId($shop_product->product->brand->name ?? null);

            $item_product = $xml_product;
            $item = $item_product->children();

            // Constants
            $item->type = 'simple';
            $item->condition = 'new';
            $item->show_price = '1';
            $item->visibility = 'both';
            $item->indexed = '1';
            $item->id_default_combination = '0';
            $item->new = '0';
            $item->minimal_quantity = '1';

            // Shops. Necessary ?
            /* $item->id_shop = strval($this->id_shop_default);
            $item->id_shop_default = strval($this->id_shop_default);
            $item->id_shop_group = 1; */

            // Prices & Taxes
            $item->price = round( $shop_product->price / (1 + $this->standard_rate/100), 4, PHP_ROUND_HALF_UP);
            $item->wholesale_price = $shop_product->getCost();
            $item->id_tax_rules_group = strval($this->rate_standard_id);
            //$item->on_sale = 1;     // En Oferta

            // Boleans
            $item->state = ($shop_product->stock > 0 && $shop_product->enabled) ? 1 : 0;
            $item->active = ($shop_product->stock > 0 && $shop_product->enabled) ? 1 : 0;
            $item->available_for_order = ($shop_product->stock > 0 && $shop_product->enabled) ? 1 : 0;

            // Manufacturer & Category
            $item->id_supplier = '44';      // MP-eSpecialist
            $item->id_manufacturer = $marketBrandId;
            $item->id_category_default = $market_category->marketCategoryId ?? '';
            $item->associations->categories->category->id = $market_category->marketCategoryId ?? '';

            $mps_sku = $this->buildPrestaMpsSku($shop_product->mps_sku);   //$shop_product->getMPSSku());
            $item->supplier_reference = $mps_sku;
            if (isset($shop_product->product->pn)) $item->reference = $this->buildPrestaReference($shop_product->product->pn);
            if (isset($shop_product->product->ean) && is_numeric($shop_product->product->ean)) $item->ean13 = $shop_product->product->ean;
            if (isset($shop_product->product->isbn)) $item->isbn = $shop_product->product->isbn;
            if (isset($shop_product->product->upc)) $item->upc = $shop_product->product->upc;
            if (isset($shop_product->product->weight)) $item->weight = $shop_product->product->weight;

            //  Titles & descriptions
            $name = $this->buildPrestaTitle(FacadesMpe::buildString($shop_product->buildTitle()));
            $description = FacadesMpe::buildText($shop_product->product->buildDescriptionLong4Excel());
            $description = $this->buildPrestaDescription($description);
            $link_rewrite = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $name));
            $link_rewrite = mb_substr(str_replace('--', '', $link_rewrite), 0, 128);
            for ($i=0; $i<count($this->languages); $i++) {
                $item->name->language[$i] = $name;
                $item->description->language[$i] = $description;
                $item->link_rewrite->language[$i] = $link_rewrite;
            }

            $shop_product->mps_sku = $mps_sku;
            $shop_product->save();

            return $item_product;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_product, $xml_product]);
        }
    }


    private function changeProductImages(ShopProduct $shop_product)
    {
        try {
            $result = null;
            $opt = null;
            // Get current Product Images
            $opt['resource'] = 'images/products/'.$shop_product->marketProductSku;
            $opt['display'] = 'all';    //"[id,id_manufacturer,id_supplier,id_category_default,id_default_image,id_tax_rules_group,type,id_shop_default]";
            //$opt['filter[id]'] = $marketProductSku;
            $images = $this->webService->get($opt);

            $image_ids = null;
            foreach($images->image->declination as $image) {
                $image_ids[] = strval($image['id']);
            }

            // Remove Multishops Product images
            $this->presta_image_shops->where('id_product', $shop_product->marketProductSku)->delete();
            // Remove current Product Images
            if (isset($image_ids))
                foreach($image_ids as $image_id) {
                    $opt['resource'] = 'images/products/'.$shop_product->marketProductSku;
                    $opt['id'] = $image_id;
                    $this->webService->delete($opt);
                }

            // Post new Product Images
            $id_images = [];
            if ($shop_product->product->images->count()) {
                $images = $shop_product->product->storage_path_images->toArray();
                if (!empty($images))
                    foreach ($images as $image)
                        $id_images[] = $this->postPrestaProductImage($shop_product->marketProductSku, $image);
            }
            elseif (isset($this->default_logo)) {
                $image = storage_path('app/'.$shop_product->shop->shop_dir.$this->default_logo);
                $id_images[] = $this->postPrestaProductImage($shop_product->marketProductSku, $image);
            }

            // Multishops ?
            if (count($this->shops) > 1 && count($id_images))
            foreach ($this->shops as $id_shop) {
                if ($id_shop == 1) continue;
                $res2 = $this->addImageShop($shop_product->marketProductSku, $id_shop, $id_images);
            }

            return $result;

        } catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $shop_product);
        }
    }


    private function enableDisableCategories(Collection $shop_products)
    {
        try {
            $count = 0;
            if ($shop_products->count() > 1) {
                $shop_products_groups = $shop_products->where('stock', '>', 0)->groupBy('market_category_id');
                $enable_categories = ['1', '2'];    // Root, Home
                foreach ($shop_products_groups as $shop_products_group) {
                    $shop_products_category = $shop_products_group->first();
                    if (isset($shop_products_category) && isset($shop_products_category->market_category_id)) {
                        $enable_categories[] = $shop_products_category->market_category->marketCategoryId;
                        $count++;
                    }
                }

                // HACK PCEDUCACION: (326) COLEGIO GAUDEM
                $enable_categories[] = 326;
                $this->updatePSCategory($enable_categories);

                return $count;
            }

            return 0;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products);
        }
    }


    private function removeOneProduct($shop_product)
    {
        try {
            $marketProductSku = $shop_product->marketProductSku;

            // Remove ShopProduct
            $shop_product->delete();

            // Remove Multishops Product Data
            $this->presta_image_shops->where('id_product', $marketProductSku)->delete();
            $this->presta_products_shop->where('id_product', $marketProductSku)->delete();

            // Remove Product
            $opt['resource'] = 'products';
            $opt['id'] = $marketProductSku;
            $this->webService->delete($opt);

            return $marketProductSku;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product->toArray());
            // Here we are dealing with errors
            //$trace = $th->getTrace();
            //return ['ERROR:' => $th, 'shop_product:' => $shop_product];
        }
    }


    private function removeProductsBySku($marketProductSkus)
    {
        try {
            $res = [];
            $count = 0;
            // Remove Multishops Product Data
            $this->presta_image_shops->where('id_product', $marketProductSkus)->delete();
            $this->presta_products_shop->where('id_product', $marketProductSkus)->delete();

            $this->nullAndStorage(__METHOD__, ['TEST PrestaWS removeProductsBySku', $marketProductSkus]);

            // Remove products
            foreach ($marketProductSkus as $marketProductSku) {
                $opt['resource'] = 'products';
                $opt['id'] = $marketProductSku;
                $res = $this->webService->delete($opt);
                $count++;
            }

            return [$count, $res];
        }
        catch (PrestaShopWebserviceException $e) {
            return $this->nullWithErrors($e, __METHOD__, $marketProductSkus);
        }
    }


    /* private function deleteStorage4Remove()
    {
        try {
            $collection_4_remove = $this->shop->getStorage4Remove();

            $this->nullAndStorage(__METHOD__, ['TEST PrestaWS deleteStorage4Remove', $collection_4_remove]);

            if (isset($collection_4_remove) && $collection_4_remove->count()) {
                return $this->removeProductsBySku($collection_4_remove->pluck('marketProductSku')->toArray());
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$collection_4_remove ?? null, $this]);
        }
    }
 */

    private function addImageShop($id_product, $id_shop, $id_images)
    {
        try {
            $count = 0;
            foreach ($id_images as $id_image) {
                $cover = ($count == 0) ? 1 : null;
                $this->updateOrCreatePSImageShop($id_product, $id_image, $id_shop, $cover);
                $count++;
            }

            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$id_product, $id_shop, $id_images]);
        }
    }


    private function addProductShop($id_product, $id_shop)
    {
        try {
            $item_xml = $this->webService->get(['resource' => 'products', 'id' => $id_product]);

            $item = $item_xml->children()->children();
            unset($item->position_in_category);
            unset($item->manufacturer_name);
            unset($item->id_default_combination);
            unset($item->quantity);
            unset($item->position);
            unset($item->id_shop_default);
            unset($item->date_add);
            unset($item->date_upd);
            unset($item->associations->combinations);
            unset($item->associations->product_options_values);
            unset($item->associations->product_features);

            // Add product Shop
            $updated_item = $this->webService->edit([
                'resource'  => 'products',
                'id'        => $id_product,
                'putXml'    => $item_xml->asXML(),
                'id_shop'   => $id_shop
            ]);

            // Add images shop
            if (isset($item->associations->images->image)) {
                $id_images = null;
                foreach ($item->associations->images->image as $image) {
                    $id_images[] = strval($image->id);
                }

                $this->addImageShop($id_product, $id_shop, $id_images);
            }

            return $id_product;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$id_product, $id_shop]);
        }
    }


    /************** PRIVATE FUNCTIONS - PRESTA MODELS ***************/


    private function updatePSProductAll(ShopProduct $shop_product)
    {
        try {
            $market_category = $shop_product->market_category;
            $marketBrandId = $this->getMarketBrandId($shop_product->product->brand->name ?? null);

            $presta_product = $this->presta_products->where('id_product', $shop_product->marketProductSku)->first();
            if (isset($presta_product)) {
                $presta_product->quantity = $shop_product->enabled ? strval($shop_product->stock) : '0';
                $presta_product->price = round($shop_product->price / (1 + $this->standard_rate/100), 4, PHP_ROUND_HALF_UP);
                $presta_product->wholesale_price = $shop_product->getCost();
                $presta_product->id_tax_rules_group = strval($this->rate_standard_id);

                $presta_product->state = 1;
                $presta_product->active = ($shop_product->stock > 0 && $shop_product->enabled) ? 1 : 0;
                $presta_product->on_sale = ($shop_product->stock > 0 && $shop_product->enabled) ? $presta_product->on_sale : 0;  // En Oferta

                $presta_product->id_supplier = '44';      // MP-eSpecialist
                $presta_product->id_manufacturer = $marketBrandId;
                $presta_product->id_category_default = $market_category->marketCategoryId ?? '';
                $mps_sku = $this->buildPrestaMpsSku($shop_product->mps_sku);   //$shop_product->getMPSSku());
                $presta_product->supplier_reference = $mps_sku;
                if (isset($shop_product->product->weight)) {
                    $presta_product->weight = $shop_product->product->weight;
                }
                if (isset($shop_product->product->pn)) {
                    $presta_product->reference = $this->buildPrestaReference($shop_product->product->pn);
                }
                if (isset($shop_product->product->ean)) {
                    $presta_product->ean13 = $shop_product->product->ean;
                }
                if (isset($shop_product->product->isbn)) {
                    $presta_product->isbn = $shop_product->product->isbn;
                }
                if (isset($shop_product->product->upc)) {
                    $presta_product->upc = $shop_product->product->upc;
                }
                $presta_product->available_for_order = '1';
                $presta_product->condition = 'new';
                $presta_product->show_price = '1';
                $presta_product->visibility = 'both';
                $presta_product->indexed = '1';
                $presta_product->minimal_quantity = '1';

                $presta_product->save();

                $shop_product->mps_sku = $mps_sku;
                $shop_product->save();

                return true;
            }
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function updatePSProductPriceStock(ShopProduct $shop_product)
    {
        try {
            $presta_product = $this->presta_products->where('id_product', $shop_product->marketProductSku)->first();
            if (isset($presta_product)) {
                $presta_product->quantity = $shop_product->enabled ? strval($shop_product->stock) : 0;
                $presta_product->price = round($shop_product->price / (1 + $this->standard_rate/100), 4, PHP_ROUND_HALF_UP);
                $presta_product->wholesale_price = $shop_product->getCost();
                $presta_product->id_tax_rules_group = strval($this->rate_standard_id);
                $presta_product->active = ($shop_product->stock > 0 && $shop_product->enabled) ? 1 : 0;
                $presta_product->on_sale = ($shop_product->stock > 0 && $shop_product->enabled) ? $presta_product->on_sale : 0;  // En Oferta
                $presta_product->save();
                return true;
            }

            return 'Presta Product NOT Found.';
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function updatePSProductStock($marketProductSku, $stock)
    {
        try {
            $presta_product = $this->presta_products->where('id_product', $marketProductSku)->first();
            if (isset($presta_product)) {
                $presta_product->quantity = strval($stock);
                $presta_product->save();
                return true;
            }

            return 'Presta Product NOT Found.';
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$marketProductSku, $stock]);
        }
    }


    private function updatePSProductShop(ShopProduct $shop_product)
    {
        try {
            $presta_product_shops = $this->presta_products_shop->where('id_product', $shop_product->marketProductSku)->get();
            foreach ($presta_product_shops as $presta_product_shop) {
                $presta_product_shop->price = round( $shop_product->price / (1 + $this->standard_rate/100), 4, PHP_ROUND_HALF_UP);
                $presta_product_shop->wholesale_price = $shop_product->getCost();
                $presta_product_shop->id_tax_rules_group = strval($this->rate_standard_id);
                $presta_product_shop->active = ($shop_product->stock > 0 && $shop_product->enabled) ? 1 : 0;
                $presta_product_shop->on_sale = ($shop_product->stock > 0 && $shop_product->enabled) ? $presta_product_shop->on_sale : 0; // En Oferta
                $presta_product_shop->save();
            }

            return true;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function updatePSProductLang(ShopProduct $shop_product)
    {
        try {
            $presta_product_langs = $this->presta_product_langs->where('id_product', $shop_product->marketProductSku)->get();
            foreach($presta_product_langs as $presta_product_lang) {

                $name = $this->buildPrestaTitle(FacadesMpe::buildString($shop_product->buildTitle()));
                $link_rewrite = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $name));
                $link_rewrite = mb_substr(str_replace('--', '', $link_rewrite), 0, 128);

                $presta_product_lang->name = $name;
                $presta_product_lang->link_rewrite = $link_rewrite;

                $description = FacadesMpe::buildText($shop_product->buildDescription4Mobile());
                $presta_product_lang->description = $this->buildPrestaDescription($description);

                $presta_product_lang->save();
            }
            return true;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function updatePSStockAvailable($marketProductSku, $stock)
    {
        try {
            $presta_stock = $this->presta_stocks->where('id_product', $marketProductSku)->first();
            if (isset($presta_stock)) {
                $presta_stock->quantity = strval($stock);
                $presta_stock->save();
                return true;
            }
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$marketProductSku, $stock]);
        }
    }


    private function updatePSCategory(&$enable_categories)
    {
        try {
            foreach ($this->presta_categories->get() as $presta_category) {
                if (in_array($presta_category->id_category, $enable_categories)) $presta_category->active = 1;
                else $presta_category->active = 0;

                $presta_category->save();
            }
            return true;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $enable_categories);
        }
    }


    private function updateOrCreatePSImageShop($id_product, $id_image, $id_shop, $cover)
    {
        try {
            $presta_image_shop = $this->presta_image_shops->updateOrCreate([
                'id_product'    => $id_product,
                'id_image'    => $id_image,
                'id_shop'    => $id_shop,
                'cover'    => $cover,
            ],[]);

            return true;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$id_product, $id_image, $id_shop, $cover]);
        }
    }


    /************** PRIVATE FUNCTIONS PRODUCTS ***************/


    private function postProducts(Collection $shop_products)
    {
        try {
            $res = [];
            $res['Productos nuevos'] = 0;
            if ($xml = $this->postPrestaProducts($shop_products))
                foreach($xml->children() as $new_item) {

                    //$product_id = $this->getIdFromMPSSku((string)$new_item->supplier_reference);
                    $shop_product = $this->shop->shop_products()->firstWhere('mps_sku', (string)$new_item->supplier_reference);

                    if (isset($shop_product)) {

                        //dd('app/'.$shop_product->shop->shop_dir.$this->default_logo);

                        // Save marketProductSku
                        $marketProductSku = (string)$new_item->id;
                        $shop_product->marketProductSku = $marketProductSku;
                        $shop_product->save();

                        // Update Stock
                        $this->updatePSProductStock($marketProductSku, $shop_product->enabled ? $shop_product->stock : 0);
                        $this->updatePSStockAvailable($marketProductSku, $shop_product->enabled ? $shop_product->stock : 0);

                        // Add images
                        if ($shop_product->product->images->count()) {
                            $images = $shop_product->product->storage_path_images->toArray();
                            foreach ($images as $image)
                                $this->postPrestaProductImage($marketProductSku, $image);
                        }
                        elseif (isset($this->default_logo)) {
                            $image = storage_path('app/'.$shop_product->shop->shop_dir.$this->default_logo);
                            $this->postPrestaProductImage($marketProductSku, $image);
                        }

                        // Multishop ps ?
                        if (count($this->shops) > 1)
                            foreach ($this->shops as $id_shop) {
                                if ($id_shop == 1) continue;
                                $this->addProductShop((string)$new_item->id, $id_shop);
                            }

                        //$result['$product_id'][] = $shop_product->marketProductSku;
                        $res['Productos nuevos']++;
                    }
                    else
                        $res[(string)$new_item->supplier_reference] = 'NOT FOUND IN RESPONSE';


                    //dd($new_item, $xml, (string)$new_item->supplier_reference, $shop_product, $this->shops, $this->default_logo, $res);
                }

            // Enable | Disable presta categories
            $res['categorias habilitadas'] = $this->enableDisableCategories($shop_products);

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    private function postPrestaUpdateds(Collection $shop_products, $product_ids_today_orders)
    {
        try {
            $res = null;
            foreach($shop_products as $shop_product) {

                $shop_product->setPriceStock(null, false, $product_ids_today_orders);
                $res[$shop_product->product_id][] = $this->changeProductImages($shop_product);
                $res[$shop_product->product_id][] = $this->updatePSProductAll($shop_product);   // OK
                $res[$shop_product->product_id][] = $this->updatePSProductShop($shop_product);  // OK
                $res[$shop_product->product_id][] = $this->updatePSStockAvailable($shop_product->marketProductSku,
                    $shop_product->enabled ? $shop_product->stock : 0); // OK
                $res[$shop_product->product_id][] = $this->updatePSProductLang($shop_product);  // OK
            }

            // Enable | Disable presta categories
            $res['categorias habilitadas'] = $this->enableDisableCategories($shop_products);

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products);
        }
    }


    private function postPrestaPricesStocks(Collection $shop_products)
    {
        try {
            $res = [];
            $res['Productos actualizados'] = 0;
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            foreach($shop_products as $shop_product) {

                $shop_product->setPriceStock(null, false, $product_ids_today_orders);
                $this->updatePSProductPriceStock($shop_product);
                $this->updatePSProductShop($shop_product);
                $this->updatePSStockAvailable($shop_product->marketProductSku,
                    $shop_product->enabled ? $shop_product->stock : 0);
                $res['Productos actualizados']++;
            }

            // Enable | Disable presta categories
            $res['Categorias habilitadas'] = $this->enableDisableCategories($shop_products);

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products);
        }
    }


    /************** public FUNCTIONS - GETTERS ***************/


    public function getBrands()
    {
        $brands = [];
        try {
            $opt['resource'] = 'manufacturers';
            $opt['display'] = "[id,name]";
            $xml = $this->webService->get($opt);
            $manufacturers = $xml->children()->children();

            Storage::put($this->storage_dir. 'manufacturers/all.xml', $xml->asXML());

            foreach ($manufacturers as $node) {

                $marketBrandId = strval($node->id);
                $name = strval($node->name);
                $market_brand = MarketBrand::updateOrCreate(
                    [
                        'market_id'         => $this->market->id,
                        'marketBrandId'     => $marketBrandId,
                    ],
                    [
                        'name'              => $name,
                    ]
                );

                $brands[] = $market_brand;
            }

            return true;

        } catch (PrestaShopWebserviceException $e) {
            return $this->msgWithErrors($e, __METHOD__, null);
        }
    }


    public function getCategories($marketCategoryId = null)
    {
        return $this->getAllCategoriesRequest($marketCategoryId);
    }


    public function getAttributes(Collection $market_categories)
    {
        return 'Prestashop no tiene atributos.';
    }


    public function getFeed(ShopProduct $shop_product)
    {
        $xml_product = $this->getPrestaProductSchema();
        $item_product = $this->buildItemProduct($shop_product, $xml_product, null);

        //return $item_product->children();
        dd($item_product->children());
    }


    public function getJobs()
    {
        return 'Prestashop no tiene Jobs.';
    }


    public function getOrders()
    {
        return $this->getOrdersRequest();
    }


    public function getGroups()
    {
        return 'Prestashop no tiene grupos de categorías.';
    }


    public function getCarriers()
    {
        return 'Prestashop no tiene transportistas.';
    }


    public function getOrderComments(Order $order)
    {
        return 'Prestashop no tiene comentarios de pedidos.';
    }


    /************ public FUNCTIONS - POSTS *******************/


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
            $shop_products = $this->getShopProducts4Create($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos nuevos en esta Tienda';

            $res = [];
            $chunks = $shop_products->chunk(100);
            foreach ($chunks as $chunk)
                $res[] = $this->postProducts($chunk);

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postUpdatedProducts($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            $res = [];
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            if ($shop_products->count() > 500) {
                $chunks = $shop_products->chunk(500);
                foreach ($chunks as $chunk) {
                    $res[] = $this->postPrestaUpdateds($chunk, $product_ids_today_orders);
                }

                return $res;
            }

            return $this->postPrestaUpdateds($shop_products, $product_ids_today_orders);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postPricesStocks($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            //$this->deleteStorage4Remove();

            return $this->postPrestaPricesStocks($shop_products);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postGroups($shop_products = null)
    {
        return 'Prestashop no tiene grupos de categorías.';
    }


    public function removeProduct($marketProductSku = null)
    {
        try {
            if (isset($marketProductSku)) {
                $shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first();
                if ($shop_product) {
                    return $this->removeOneProduct($shop_product);
                }
                else
                    return $this->removeProductsBySku([$marketProductSku]);
            }
            else {
                /* $shop_filters = $this->shop->shop_filters()->pluck('product_id')->all();
                $shop_products = $this->shop->shop_products()->whereNotIn('product_id', $shop_filters)->get();
                $res = null;
                $count = 0;
                foreach ($shop_products as $shop_product) {
                    $res[] = $this->removeOneProduct($shop_product);
                    $count++;
                } */

            }

            return ['ERROR REMOVING:' => $marketProductSku];

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketProductSku);
        }
    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        return 'Prestashop no tiene trackings.';
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return 'Prestashop no tiene comentarios de pedidos.';
    }


    public function synchronize()
    {
        try {
            /* $res = [];
            $shop_products_marketProductSku = $this->shop->shop_products()->where('stock', 0)
                ->whereNotNull('marketProductSku')
                ->where('market_category_id', '<>', 47452)
                ->pluck('marketProductSku')
                ->toArray();

            $chunks = array_chunk($shop_products_marketProductSku, 100);
            foreach ($chunks as $marketProductSku_chunk) {
                $this->removeProductsBySku($marketProductSku_chunk);
            }

            $shop_products = $this->shop->shop_products()->where('stock', 0)
                ->whereNotNull('marketProductSku')
                ->where('market_category_id', '<>', 47452)
                ->get();

            foreach ($shop_products as $shop_product) {
                $shop_product->deleteSecure();
            }


           */





            $offers = $this->getPrestaProducts();
            foreach ($offers as $offer) {

                $marketProductSku = (string)$offer->id;
                //$res['ONLINE_OFFERS'][] = $marketProductSku;
                if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku)) {
                    if ($shop_product->mps_sku != $offer->supplier_reference) {
                        $shop_product->mps_sku = $offer->supplier_reference;
                        $shop_product->save();
                    }
                }



                /* if (!isset($shop_product)) {

                    $mps_sku = (string)$offer->supplier_reference;
                    //$product_id = FacadesMpe::getIdFromMPSSku($mps_sku);
                    $shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $mps_sku);
                    if (isset($shop_product)) {
                        $res['NEW_SKUS'][$mps_sku][] = $shop_product;
                        $shop_product->marketProductSku = $marketProductSku;
                        $shop_product->enabled = true;
                        $shop_product->save();
                    } else {

                        // DELETE ONLINE
                        $res['DELETE_ONLINE'][] = $marketProductSku;
                    }
                }
                elseif (!$shop_product->enabled) {
                    $res['ENABLED'][] = $marketProductSku;
                    $shop_product->enabled = true;
                    $shop_product->save();
                }
                elseif ($shop_product->stock == 0) {
                    $shop_product->setPriceStock();
                    if ($shop_product->stock == 0) {

                        // DELETE ONLINE
                        $res['DELETE_ONLINE'][] = $marketProductSku;
                        $shop_product->deleteSecure();
                    }
                } */
            }


            // RESETS SERVER OFFERS THAT NOT EXIST IN ONLINE
            /* if (isset($res['ONLINE_OFFERS'])) {
                $shop_products_marketProductSku_list = $this->getShopProducts4Update()->pluck('marketProductSku');
                $res['RESETS'] = $shop_products_marketProductSku_list->diff($res['ONLINE_OFFERS']);
                foreach ($res['RESETS'] as $marketProductSku) {
                    $shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku);
                    if (isset($shop_product)) {
                        $shop_product->marketProductSku = null;
                        $shop_product->save();
                    }
                }
            }

            // REMOVE DUPLICATEDS
            if (isset($res['NEW_SKUS'])) {
                foreach ($res['NEW_SKUS'] as $mps_sku => $shop_products) {
                    if (count($shop_products) > 1)
                        for($i=0; $i<count($shop_products)-1; $i++)
                            $res['DELETE_ONLINE'][] = $shop_products[$i]->marketProductSku;
                }
            }

            // REMOVE ONLINE OFFERS THAT NOT IN SERVER
            if (isset($res['DELETE_ONLINE'])) {
                $res['POST_DELETES'] = $this->removeProductsBySku($res['DELETE_ONLINE']);
            }

            return $res;*/

            return $offers;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function removeWithoutStock()
    {
        return 'Prestashop no tiene eliminación en bloque.';
    }


    /************* REQUEST FUNCTIONS *********************/


    public function getProduct($marketProductSku)
    {
        try
        {
            $opt['resource'] = 'products';
            $opt['id'] = $marketProductSku;
            $xml = $this->webService->get($opt);
            $product = $xml->children()->children();
            Storage::put($this->storage_dir. 'products/'.$marketProductSku.'.xml', $xml->asXML());

            dd($product);

        }
        catch (PrestaShopWebserviceException $e) {
            return $this->msgWithErrors($e, __METHOD__, $marketProductSku);
        }
    }


    public function getAllProducts()
    {
        try
        {
            $resources = $this->getPrestaProducts();
            dd($resources);


            // UPLOAD IMAGES TO THEHPSHOP ONLINE
            /* $res = null;
            $count = 0;
            foreach($resources as $resource) {

                //if (isset($resource->ean13) && $resource->ean13 != ''
                if (isset($resource->reference) && $resource->reference != ''
                    && $resource->active == '1' &&
                    (!isset($resource->id_default_image) || $resource->id_default_image == '')) {

                        $product = Product::wherePn($resource->reference)->whereIn('brand_id', [1,3,5,22,43,51,78])->first();
                        $res[] = $product->id;

                        //dd($resource,$product);

                        $id_images = null;
                        $images = $product->storage_path_images->toArray();
                        if (!empty($images)) {
                            foreach ($images as $image)
                                $id_images[] = $this->postPrestaProductImage($resource->id, $image);

                            $count++;
                        }


                    //dd($resource, $product, $images, $id_images);
                }
            } */
            //dd($count, $res);


            /* // CREATE LOCAL SHOPPRODUCTS FROM PRESTA
            foreach($resources as $resource) {
                if (isset($resource->ean13) && $resource->ean13 != '') {
                    $product = Product::whereEan($resource->ean13)->first();
                    if (isset($product)) {
                        ShopProduct::updateOrCreate([
                            'market_id'         => $this->market->id,
                            'shop_id'           => $this->shop->id,
                            'marketProductSku'  => $resource->id,
                            'product_id'        => $product->id,
                        ],[]);

                        //dd($resource, $resources);
                    }
                }
            }
            dd($resources); */


            // REMOVE IN-ACTIVE PRODUCTS, ONLINE & LOCAL
            /* foreach($resources as $resource) {
                if ($resource->active == '0')
                    $res[] = $this->removeProductsBySku([strval($resource->id)]);
                //dd($resource, strval($resource->id), $res);
            } */


            // REMOVE ONLINE PRODUCTS THAT NO EXIST IN MPE
            /* $res = [];
            $count = 0;
            $marketProductSkus = [];
            foreach($resources as $resource) {
                $shop_product = $this->shop->shop_products()->where('marketProductSku', $resource->id)->first();
                if (!isset($shop_product) && strval($resource->id) != '') { //&& substr($resource->date_add, 0, 10) < '2020-08-01') {   // 1116, 1155, 3344
                    $marketProductSkus[] = strval($resource->id);
                    $count++;
                }
            }
            dd($count, $marketProductSkus, $resource);
            $res[] = $this->removeProductsBySku([$marketProductSkus]); */


            // REMOVE ALL ONLINE & OFFLINE PRODUCTS
            foreach ($this->shop->shop_products as $shop_product) {
                $shop_product->deleteSecure();
            }

            $res = [];
            $count = 0;
            $marketProductSkus = [];
            foreach($resources as $resource) {
                $marketProductSkus[] = strval($resource->id);
                $count++;

                /* $shop_product = $this->shop->shop_products()->where('marketProductSku', $resource->id)->first();
                if (!isset($shop_product) && strval($resource->id) != '') { //&& substr($resource->date_add, 0, 10) < '2020-08-01') {   // 1116, 1155, 3344
                    $marketProductSkus[] = strval($resource->id);
                    $count++;
                } */
            }
            //dd($count, $marketProductSkus, $resource);
            $res[] = $this->removeProductsBySku($marketProductSkus);
            dd($count, $marketProductSkus, $resources);


        }
        catch (PrestaShopWebserviceException $e) {
            return $this->msgWithErrors($e, __METHOD__, null);
        }
    }



    public function getImages($marketProductSku = null)
    {

        try {
            //$webService = new PrestaShopWebservice($this->apiUrl, $this->apiKey, $this->debug);
            $opt['resource'] = 'products';
            //if (isset($marketProductSku)) $opt['resource'] .= '/'.$marketProductSku;
            //$opt['display'] = "[id,name,id_category_default,id_default_image]";
            $opt['display'] = "[id,id_default_image,active]";
            //$opt['filter[id]'] = "1115";
            $xml = $this->webService->get($opt);
            $images = $xml->children()->children();

            Storage::put($this->storage_dir. 'images/all.xml', $xml->asXML());
            dd($images);

            $res = [];
            $count = 0;
            foreach ($images as $image) {
                // quantity
                if ($image->active == '1' && (!isset($image->id_default_image) || $image->id_default_image == '')) {
                    $res[] = $image;
                    $count++;
                }

                if ($count > 5) break;
            }
            dd($res, $images, $xml);      //), $images->product->id_default_image, strval($images->product->id_default_image));

        } catch (PrestaShopWebserviceException $e)
        {
            dd($e, $opt);
        }
    }


    private function postCategoryActived($marketCategoryId, $name, $actived)
    {
        $result = null;
        try {
            $xml = $this->getCategorySchema();
            if ($xml == 'Bad HTTP response')
                return 'Fallo en la conexion al Prestashop';

            $item = $xml->children()->children();
            $item->id = $marketCategoryId;
            //$item->name->language[0][0] = $name;
            $item->active = $actived;

            $opt['resource'] = 'categories';
            $opt['id'] = intval($marketCategoryId);
            $opt['putXml'] = $xml->asXML();

            $xml = $this->webService->edit($opt);
            $item_updated = $xml->product->children();
            Storage::put($this->storage_dir. 'categories/'.$item_updated->id.'_actived.xml', $xml->asXML());

            dd($item, $xml);

        } catch (PrestaShopWebserviceException $e)
        {
            dd($e, $xml);
        }

        return $result;
    }


    public function getCategorySchema()
    {
        try {
            dd($this->presta_products->get());
        }
        catch (Throwable $th) {
            dd($th);
        }


        $opt = null;
        try {
            $schema_category_path = $this->storage_dir. 'schemas/category.xml';
            /* if (Storage::exists($schema_category_path)) {
                $xml_string = Storage::get($schema_category_path);
                return new SimpleXMLElement($xml_string);
            } */

            // Get Products Schema Online
            $opt['resource'] = 'categories';
            $opt['schema'] = 'blank';
            $xml = $this->webService->get($opt);

            $category_schema = $xml->category;
            unset($category_schema->id_parent);
            unset($category_schema->id_shop_default);
            unset($category_schema->is_root_category);
            unset($category_schema->position);
            unset($category_schema->date_add);
            unset($category_schema->date_upd);
            unset($category_schema->name);
            unset($category_schema->link_rewrite);
            unset($category_schema->description);
            unset($category_schema->meta_title);
            unset($category_schema->meta_description);
            unset($category_schema->meta_keywords);
            unset($category_schema->associations);

            //<id/>
            //<active/>

            Storage::put($schema_category_path, $xml->asXML());

            dd($xml);

            return $xml;

        } catch (PrestaShopWebserviceException $e)
        {
            dd($e);
        }
    }



    public function getConfiguration()
    {
        $opt = null;
        try {
            //$opt['resource'] = 'configurations';
            //$opt['resource'] = 'countries';
            //$opt['resource'] = 'languages';
            //$opt['resource'] = 'shop_groups';
            //$opt['resource'] = 'shop_urls';
            $opt['resource'] = 'shops';

            $opt['display'] = 'full';       //"[id_shop_group, id_shop, name, value]";
            $xml = $this->webService->get($opt);

            Storage::put($this->storage_dir. 'config/config.xml', $xml->asXML());
            $res = $xml->children()->children();

            // CONFIGURATION
            // array of SimpleXMLExlement (id_shop_group, id_shop, name, value): UDG
            // PS_LANG_DEFAULT - 5    Català
            // PS_CURRENCY_DEFAULT - 1  Euro
            // PS_COUNTRY_DEFAULT - 8   Espanya
            // PS_TAX - 1   // IVA ES 21%
            // PS_SHOP_ENABLE - 1
            // PS_LOCALE_LANGUAGE - es
            // PS_LOCALE_COUNTRY - es
            // PS_SHOP_DEFAULT - 1
            // PS_SHOP_DOMAIN - udg.test:8080
            // PS_SHOP_DOMAIN_SSL - udg.test:8080
            // PS_SHOP_NAME - Ofertes Estudiants UdG
            // PS_SHOP_EMAIL - botiga@udg.cat
            // PS_LOGO - thehpshop-logo-1550252806.jpg
            // PS_DETECT_LANG - 0
            // PS_DETECT_COUNTRY - 1
            // PS_SHOP_COUNTRY_ID - 6
            // PS_SHOP_COUNTRY - España
            // VOX66_USER - 
            // VOX66_PASSWORD - 
            // REDSYS_***

            // COUNTRIES
            // id: 6
            // id_zone": "1"
            // id_currency": "0"
            // call_prefix": "34"
            // iso_code": "ES"
            // active": "1"
            // contains_states": "1"
            // need_identification_number": "1"
            // need_zip_code": "1"
            // zip_code_format": "NNNNN"
            // display_tax_label": "1"
            // name->language[0 => "España", 1 => "Espanya"]
            /* foreach($res as $r) {
                if ($r->iso_code == 'ES') dd(strval($r->name->language[0]), $r, $res);
            } */

            // LANGUAGES (A HP NOMÉS SURT 1 LANGUGE)
            // id": "1"
            // name": "Español (Spanish)"
            // iso_code": "es"
            // locale": "es-ES"
            // language_code": "es"
            // active": "1"
            //
            // id": "5"
            // name": "Català (Catalan)"
            // iso_code": "ca"
            // locale": "ca-ES"
            // language_code": "ca-es"

            // SHOP_GROUPS
            // shop_group": SimpleXMLElement {#2092 ▼
            //      id": "1"
            //      name": "Default"
            //      share_customer": "0"
            //      share_order": "0"
            //      share_stock": "0"
            //      active": "1"
            //      deleted": "0"

            // SHOP_URLS
            // shop_url": SimpleXMLElement {#2092 ▼
            //        id": "1"
            //        id_shop": "1"
            //        active": "1"
            //        main": "1"
            //        domain": "udg.test:8080"
            //        domain_ssl": "udg.test:8080"
            //
            //        id": "2"
            //        id_shop": "2"
            //        domain": "udg.test:8080"
            //        domain_ssl": "udg.test:8080"
            //        physical_uri": "/"
            //        virtual_uri": "udg2/"

            // SHOPS
            // "shop": SimpleXMLElement {#2092 ▼
            //        id": "1"
            //        id_shop_group": "1"
            //        id_category": "2"
            //        active": "1"
            //        deleted": "0"
            //        name": "udg"
            //        theme_name": "AngarTheme"
            //
            //        id": "2"
            //        id_shop_group": "1"
            //        id_category": "2"
            //        name": "UdG2"
            //        theme_name": "AngarTheme"



            dd($res);

        } catch (PrestaShopWebserviceException $e)
        {
            dd($e);
        }
    }


}
