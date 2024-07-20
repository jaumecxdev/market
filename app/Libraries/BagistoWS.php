<?php

namespace App\Libraries;


use App\MarketCategory;
use App\Order;
use App\RootCategory;
use App\Shop;
use App\ShopProduct;
use App\Traits\HelperTrait;
use Facades\App\Facades\Mpe as FacadesMpe;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;


class BagistoWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    protected $client = null;
    protected $token = null;


    protected $apiKey = null;
    protected $debug = false;
    protected $webService = null;

    protected $shops = [1];
    protected $languages = [1];
    protected $id_shop_default = 1;
    protected $rate_standard_id = 1;        // id_tax_rules_group
    protected $rate_reduced_id = 2;
    protected $rate_super_reduced_id = 3;
    protected $standard_rate = 21;          // iva standard
    protected $locale = 'ca';


    const DEFAULT_CONFIG = [];



    function __construct(Shop $shop)
    {
        try {
            parent::__construct($shop);

            $this->client = new Client(['base_uri' => $shop->endpoint]);
            $this->token = $shop->token;    // '1234567890';
            $this->locale = $shop->country;

            /* $token = $this->bagistoLoginToken();
            if (!$token) return 'Error Login Token'; */

            //$this->token = '';
            //$this->token = $token;

            /* $guard = $this->bagistoLoginToken();
            if (!$guard) return 'Error Customer Guard'; */

            /* $bagisto_customer = $this->bagistoGetCustomer();
            if (!$bagisto_customer) return 'Error Get Customer'; */

            //$bagisto_products = $this->bagistoGetAllProducts();

            //$bagisto_product_info = $this->bagistoGetProductAditionalInfo(1);

            // $categories = $this->bagistoGetCategories();






        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }

    }


    /************** PRIVATE FUNCTIONS BAGISTO ***************/


    /* private function bagistoLoginToken()
    {
        try {
            // Login Token Auth Guards
            $response = $this->client->post('api/customer/login', [
                'headers' => [
                    'Accept'        => 'application/json'
                ],
                'form_params' => [
                    'token'         => 'true',
                    'email'         => 'customer@example.com',
                    'password'      => 'customer'
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'login/' .date('Y-m-d'). '_token.json', $contents);
                $json_res = json_decode($contents);

                if (isset($json_res->message) && $json_res->message == 'Logged in successfully.')
                    if (isset($json_res->token))
                        return $json_res->token;
            }

            return $this->nullAndStorage(__METHOD__, ['Login Token Auth Guards', $response]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    private function bagistoLogin()
    {
        try {
            // Login Customer Guard
            $response = $this->client->post('api/customer/login', [
                'headers' => [
                    'Accept'        => 'application/json'
                ],
                'form_params' => [
                    'email'         => 'customer@example.com',
                    'password'      => 'customer'
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'login/' .date('Y-m-d'). '_guard.json', $contents);
                $json_res = json_decode($contents);

                if (isset($json_res->message) && $json_res->message == 'Logged in successfully.')
                    if (isset($json_res->token) && $json_res->token == true)
                        return $json_res->token;
            }

            return $this->nullAndStorage(__METHOD__, ['Login Customer Guard', $response]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    private function bagistoGetCustomer()
    {
        try {
            // Get customer
            $response = $this->client->get('api/customer/get', [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' .$this->token,
                ],
                'query' => [
                    'token'         => 'true',
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'customer/' .date('Y-m-d'). '_get.json', $contents);
                $json_res = json_decode($contents);

                // success
                if (isset($json_res->data)) {
                    // id, email, first_name, name, gender, date_of_birth, phone, status,
                    // group[id, name, created_at, updated_at], created_at, updated_at
                    return $json_res->data;
                }
            }

            return $this->nullAndStorage(__METHOD__, ['Get Customer', $response]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    private function bagistoGetAllProducts($page = 1)
    {
        try {
            $response = $this->client->get('api/products', [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' .$this->token,
                ],
                'query' => [
                    'page'         => $page,
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'products/' .date('Y-m-d'). '_page_'.$page.'.json', $contents);
                $json_res = json_decode($contents);

                // success
                if (isset($json_res->data)) {

                    $bagisto_products = $json_res->data;
                    if ($page < $json_res->meta->total) {
                        $bagisto_products = array_merge($bagisto_products, $this->bagistoGetAllProducts(++$page));
                    }
                    // id, email, first_name, name, gender, date_of_birth, phone, status,
                    // group[id, name, created_at, updated_at], created_at, updated_at
                    return $bagisto_products;
                }
            }

            return $this->nullAndStorage(__METHOD__, ['Get All Products', $page, $response]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$page, $this]);
        }
    }


    private function bagistoGetProductAditionalInfo($product_id = 1)
    {
        try {
            $response = $this->client->get('api/product-additional-information/'.$product_id, [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' .$this->token,
                ],
                'query' => [
                    'page'         => $page,
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'products/' .date('Y-m-d'). '_info_'.$product_id.'.json', $contents);
                $json_res = json_decode($contents);

                // success
                if (isset($json_res->data)) {

                    $bagisto_product_info = $json_res->data;
                    foreach ($bagisto_product_info as $bagisto_info) {
                        $code = $bagisto_info->code;        // brand
                        $value = $bagisto_info->value;      // Iiyama
                    }

                    return $bagisto_product_info;
                }
            }

            return $this->nullAndStorage(__METHOD__, ['Get Product Additional Info', $product_id, $response]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product_id, $this]);
        }
    }
 */



    private function bagistoGetCategories()
    {
        try {
            $response = $this->client->get('apiadmin/categories', [
                'headers' => [
                    'Accept'        => 'application/json',
                ],
                'query' => [
                    'token'         => $this->token,
                    'limit'         => 100,
                    /* 'sort'          => 'slug',
                    'order'         => 'asc', */
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'categories/' .date('Y-m-d'). '.json', $contents);
                $json_res = json_decode($contents);

                // success
                if (isset($json_res->data))
                    return $json_res->data;
            }

            return $this->nullAndStorage(__METHOD__, ['Get Categories', $response]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    private function bagistoGetProducts($limit = 100, $page = 1, $sku = null)
    {
        try {
            $query = [
                'token'         => $this->token,
                'limit'         => $limit,
                'page'          => $page
            ];

            if (isset($sku)) $query['sku'] = $sku;

            $response = $this->client->get('apiadmin/products', [
                'headers' => [
                    'Accept'        => 'application/json',
                ],
                'query' => $query,
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'products/' .date('Y-m-d'). '_products.json', $contents);
                $json_res = json_decode($contents);

                // success
                if (isset($json_res->data))
                    return $json_res->data;
            }

            return $this->nullAndStorage(__METHOD__, ['Get Products', $response]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    private function bagistoGetProduct($marketProductSku)
    {
        try {
            $response = $this->client->get('apiadmin/products/'.$marketProductSku, [
                'headers' => [
                    'Accept'        => 'application/json',
                ],
                'query' => [
                    'token'         => $this->token,
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'products/' .date('Y-m-d'). '_'.$marketProductSku.'.json', $contents);
                $json_res = json_decode($contents);

                // success
                if (isset($json_res->data)) {

                    return $json_res->data;
                }
            }

            return $this->nullAndStorage(__METHOD__, ['Get Product', $response]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    private function bagistoPostNewProduct($item)
    {
        try {
            $item['token'] = $this->token;

            $response = $this->client->post('apiadmin/products', [
                'headers' => [
                    'Accept'        => 'application/json',
                ],
                'form_params' => $item,
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'products/' .date('Y-m-d'). '.json', $contents);
                $json_res = json_decode($contents);

                // success
                if (isset($json_res->data) && isset($json_res->data->product))
                    return $json_res->data->product->id;
            }

            return $this->nullAndStorage(__METHOD__, ['Post Product', $this->shop->name, $response]);

        } catch (Throwable $th) {
            // Already Exists ?
            if ($th->getCode() == 400 && $bagisto_product = $this->bagistoGetProducts(100, 1, $item['sku'])) {
                if ($shop_product = $this->shop->shop_products()->where('mps_sku', $item['sku'])->first()) {
                    $shop_product->marketProductSku = $bagisto_product->id;
                    $shop_product->save();

                    return $bagisto_product->id;
                }
            }
            else
                return $this->nullWithErrors($th, __METHOD__, [$this->shop->name, $item, $this, $response ?? null]);
        }
    }


    private function bagistoPostPriceStockProduct($item)
    {
        try {
            $item['token'] = $this->token;

            $response = $this->client->post('apiadmin/products/prices', [
                'headers' => [
                    'Accept'        => 'application/json',
                ],
                'form_params' => $item,
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'products/' .date('Y-m-d'). '_price.json', $contents);
                $json_res = json_decode($contents);

                // success
                if (isset($json_res->data) && isset($json_res->data->product)) {
                    return $json_res->data->product->id;
                }

                return $json_res->message;
            }

            return $this->nullAndStorage(__METHOD__, ['Post Price Stock Product', $this->shop->name, $response, $item]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$this->shop->name, $item, $this]);
        }
    }


    private function bagistoRemoveProduct($marketProductSku)
    {
        try {
            $item['token'] = $this->token;

            $response = $this->client->delete('apiadmin/products/'.$marketProductSku, [
                'headers' => [
                    'Accept'        => 'application/json',
                ],
                'form_params' => $item,
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'products/' .date('Y-m-d'). '_removed.json', $contents);
                $json_res = json_decode($contents);

                // success
                if (isset($json_res->data) && isset($json_res->data->product))
                    return $json_res->data->product->id;
            }

            return $this->nullAndStorage(__METHOD__, ['Remove Product', $response]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }



/************** PRIVATE FUNCTIONS - ORDERS ***************/



/************** PRIVATE FUNCTIONS - BUILDERS ***************/


    private function buildBagistoMpsSku($mps_sku)
    {
        try {
            $mps_sku = $this->changeAccents($mps_sku);
            $mps_sku = str_replace(['=', '¦', 'ª', '®', '™', '\\', ';', '&amp;', '#039'], [''], $mps_sku);
            $mps_sku = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '', $mps_sku);
            $mps_sku = str_replace('_', '-', $mps_sku);
            //$mpsSku = preg_replace('/[^A-Za-z0-9\-\.\"\_\/ ]/', '', $mpsSku);

            return trim(mb_strtolower($mps_sku));

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $mps_sku);
        }
    }


    private function buildProductFeed(ShopProduct $shop_product, $product_ids_today_orders)
    {
        try {
            // sku, name, ean, pn, cost, price, stock, weight, brand|brand_id, category|category_id, shortdesc, longdesc, images,
            // color, size
            $market_category = $shop_product->market_category;
            if (!$market_category) return $this->nullAndStorage(__METHOD__, ['NO Market Category', $shop_product->id]);

            $shop_product->setPriceStock(null, false, $product_ids_today_orders);

            $item = [];
            if ($shop_product->isUpgradeable()) $item['id'] = $shop_product->marketProductSku;
            $item['product_locale'] = $this->locale;
            $mps_sku = $this->buildBagistoMpsSku($shop_product->mps_sku);
            $shop_product->mps_sku = $mps_sku;
            $shop_product->save();
            $item['sku'] = $mps_sku;      //$shop_product->product_id;
            $item['name'] = FacadesMpe::buildString($shop_product->buildTitle());
            $item['ean'] = $shop_product->ean ?? $shop_product->pn ?? null;
            $item['tax'] = $shop_product->product->tax;
            $item['cost'] = $shop_product->getCost();
            $item['price'] = $shop_product->price;
            $item['stock'] = $shop_product->stock;
            $item['brand'] = $shop_product->product->brand->name ?? null;
            $item['category_id'] = $market_category->marketCategoryId;
            $item['longdesc'] = FacadesMpe::buildText($shop_product->buildDescription4Mobile());

            $images = array_slice($shop_product->product->public_url_images->toArray(), 0, 8);
            if ($images && count($images))
                $item['images'] = implode(',', $images);

            if ($shop_product->product->size) $item['size'] = $shop_product->product->size;
            if ($shop_product->product->color) $item['color'] = $shop_product->product->color;
            if ($shop_product->product->weight) $item['weight'] = $shop_product->product->weight;

            // Local Test Purposes
            if (config('app.env', 'production') == 'local')
                $item['images'] = str_replace('https', 'http', $item['images']);


            $item['name_1'] = $shop_product->buildTitle();
            $item['name_2'] = FacadesMpe::buildString($shop_product->product->name);
            $item['name_3'] = $shop_product->product->name;

            return $item;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildPricesStocksFeed(ShopProduct $shop_product, $product_ids_today_orders)
    {
        try {
            $shop_product->setPriceStock(null, false, $product_ids_today_orders);

            $item = [];
            $mps_sku = $this->buildBagistoMpsSku($shop_product->mps_sku);
            $shop_product->mps_sku = $mps_sku;
            $shop_product->save();

            $item['id'] = $shop_product->marketProductSku;
            $item['sku'] = $mps_sku;        // $this->buildBagistoMpsSku($shop_product->mps_sku);       //$shop_product->product_id;
            $item['ean'] = $shop_product->ean ?? $shop_product->pn ?? null;
            //$item['pn'] = $shop_product->pn ?? null;
            $item['tax'] = $shop_product->product->tax;
            $item['cost'] = $shop_product->getCost();
            $item['price'] = $shop_product->price;
            $item['stock'] = $shop_product->stock;
            $item['enabled'] = ($shop_product->stock == 0 || !$shop_product->enabled) ? 0 : 1;

            $item['name'] = FacadesMpe::buildString($shop_product->buildTitle());
            $item['longdesc'] = FacadesMpe::buildText($shop_product->buildDescription4Mobile());

            return $item;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


/************** PRIVATE FUNCTIONS - POSTS ***************/


    private function postProduct(ShopProduct $shop_product, $feed_type = 'add', $product_ids_today_orders)
    {
        try {
            if ($item = $this->buildProductFeed($shop_product, $product_ids_today_orders)) {
                if ($marketProductSku = $this->bagistoPostNewProduct($item)) {
                    $shop_product->marketProductSku = $marketProductSku;
                    $shop_product->save();

                    return $marketProductSku;
                }
            }

            // Already Exists ?
            if ($bagisto_product = $this->bagistoGetProducts(100, 1, $shop_product->mps_sku)) {
                $shop_product->marketProductSku = $bagisto_product->id;
                $shop_product->save();
            }
            else
                return $this->msgAndStorage(__METHOD__, 'Error. Product ID: '.$shop_product->product_id, [$shop_product, $feed_type]);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$shop_product, $feed_type]);
        }
    }


    private function postPayload(Collection $shop_products, $feed_type = 'add')
    {
        try {
            $count = 0;
            $products_result = [];
            $products_result['count'] = $shop_products->count();
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            foreach ($shop_products as $shop_product) {
                $result = $this->postProduct($shop_product, $feed_type, $product_ids_today_orders);
                $products_result['products'][] = $result;
                $count++;
            }

            $products_result[$feed_type] = $count;
            Storage::append($this->storage_dir. 'payloads/' .date('Y-m-d'). '_'.$feed_type.'.json', json_encode($products_result));

            return $products_result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_products, $feed_type]);
        }
    }


    private function postPriceStock(ShopProduct $shop_product, $product_ids_today_orders)
    {
        try {
            if ($item = $this->buildPricesStocksFeed($shop_product, $product_ids_today_orders))
                if ($marketProductSku = $this->bagistoPostPriceStockProduct($item))
                    return $marketProductSku;

            return $this->msgAndStorage(__METHOD__, 'Error. Product ID: '.$shop_product->product_id, [$this->shop->name, $marketProductSku, $shop_product]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$this->shop->name, $shop_product]);
        }
    }


    private function postPricesStocksPayload(Collection $shop_products)
    {
        try {
            $count = 0;
            $products_result = null;
            $products_result['count'] = $shop_products->count();
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            foreach ($shop_products as $shop_product) {
                $result = $this->postPriceStock($shop_product, $product_ids_today_orders);
                if (!is_numeric($result)) $products_result['updates'][$shop_product->marketProductSku] = $result;
                $count++;
            }

            $products_result['price_stock'] = $count;
            Storage::append($this->storage_dir. 'payloads/' .date('Y-m-d'). '_prices_stocks.json', json_encode($products_result));

            //unset($products_result['updates']);
            return $products_result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }



/************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getCategoryPath(MarketCategory $parent)
    {
        try {
            $path = $parent->name;
            $parent = $parent->parent ?? null;
            while ($parent) {
                $path = $parent->name.' / '.$path;
                $parent = $parent->parent ?? null;
            }

            return $path;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $parent);
        }
    }


    public function getCategories($marketCategoryId = null)
    {
        try {
            if ($bagisto_categories = $this->bagistoGetCategories()) {

                $root_category = RootCategory::firstOrCreate([
                    'market_id'       => $this->market->id,
                    'name'            => 'Root',
                    'marketCategoryId'=> 1,
                ],[]);

                foreach ($bagisto_categories as $bagisto_category) {

                    if ($bagisto_category->id != 1) {

                        $path = null;
                        if ($parent = $this->shop->market->market_categories()->firstWhere('marketCategoryId', $bagisto_category->parent_id))
                            $path = $this->getCategoryPath($parent);

                        //$path = $parent ? ($parent->parent_id ? $parent->parent->name.' / '.$parent->name : $parent->name) : null;
                        MarketCategory::updateOrCreate([
                            'market_id'         => $this->market->id,
                            'marketCategoryId'  => $bagisto_category->id,
                        ],[
                            'root_category_id'  => $parent ? $parent->root_category_id : $root_category->id,
                            'parent_id'         => $parent ? $parent->id : null,
                            'name'              => $bagisto_category->name ?? 'Null',
                            'path'              => $path
                        ]);
                    }



                }
            }

            return 'Categorías descargadas correctamente. '.count($bagisto_categories);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketCategoryId);
        }
    }


    public function getFeed(ShopProduct $shop_product)
    {
        try {
            return $this->buildProductFeed($shop_product, null);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function getBrands()
    {
        return 'No code.';
    }


    public function getJobs()
    {
        return 'No code.';
    }


    public function getGroups()
    {
        return 'No code.';
    }


    public function getAttributes(Collection $market_categories)
    {
        return 'No code.';
    }


    public function getOrders()
    {
        return 'No code.';
    }


    public function getCarriers()
    {
        return 'No code.';
    }


    public function getOrderComments(Order $order)
    {
        return 'No code.';
    }


/************** PUBLIC FUNCTIONS - POSTTERS ***************/


    public function postNewProduct(ShopProduct $shop_product)
    {
        try {
            $shop_products = new Collection([$shop_product]);

            return $this->postNewProducts($shop_products);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function postNewProducts($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Create($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos nuevos en esta Tienda';

            return $this->postPayload($shop_products, 'add');

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
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


    public function postPricesStocks($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            //$this->deleteStorage4Remove();

            return $this->postPricesStocksPayload($shop_products);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function removeProduct($marketProductSku = null)
    {
        try {
            if (isset($marketProductSku))
                if ($marketProductSku = $this->bagistoRemoveProduct($marketProductSku)) {
                    if ($shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first())
                        return $shop_product->deleteSecure();
                    else
                        return 'Solo eliminado en el Marketplace, no de la lista.';
                }

                return $this->msgAndStorage(__METHOD__, 'No se ha podido eliminar o no se ha encontrado.', $marketProductSku);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketProductSku);
        }

    }


    public function postUpdatedProduct(ShopProduct $shop_product)
    {
        return 'No code.';
    }


    public function postUpdatedProducts($shop_products = null)
    {
        return 'No code.';
    }


    public function postGroups($shop_products = null)
    {
        return 'No code.';
    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        return 'No code.';
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return 'No code.';
    }


    public function synchronize()
    {
        try {
            $res = [];
            $page = 1;
            $bagisto_products = [];
            do {
                $b_p = $this->bagistoGetProducts(100, $page);
                //$bagisto_products = array_merge($bagisto_products, $b_p);
                $bagisto_products += $b_p;

                $page++;

            } while ($page < 1000 && isset($b_p) && !empty($b_p));

            Storage::append('test_bagisto.json', json_encode([$page, $res, $bagisto_products]));

            $res['FOUND_OK'] = 0;
            foreach ($bagisto_products as $bagisto_product) {
                if ($shop_product = $this->shop->shop_products()->where('marketProductSku', $bagisto_product->id)->first()) {
                    $res['FOUND_OK']++;
                }
                else {
                    $product_id = substr($bagisto_product->sku, 0, strpos($bagisto_product->sku, '-'));
                    if ($shop_product = $this->shop->shop_products()->where('product_id', $product_id)->first()) {
                        if (!isset($shop_product->marketProductSku)) {
                            if ($this->buildBagistoMpsSku($shop_product->mps_sku) != $bagisto_product->sku) $res['DIFERENT_MPS_SKU'][] = [$shop_product->mps_sku, $bagisto_product->sku];
                            else {
                                $res['NO_ISSET_MPSKU'][] = $bagisto_product->sku;
                                $shop_product->marketProductSku = $bagisto_product->id;
                                $shop_product->save();
                            }
                        }
                        else $res['DIFERENT_MPSKU'][] = $bagisto_product->sku;
                    }
                    else
                        $res['NOT_FOUND_MPSKU'][] = $bagisto_product->sku;
                }
            }

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$page, $res, $bagisto_products]);
        }
    }


    public function removeWithoutStock()
    {
        return 'No code.';
    }

    public function setDefaultShopFilters()
    {
        // Develop in descendant
    }



    /************* REQUEST FUNCTIONS *********************/



    public function getProduct($marketProductSku)
    {
        dd($this->bagistoGetProduct($marketProductSku));
    }


    public function getAllProducts()
    {
        try {
            $shop_products = $this->shop->shop_products;
            foreach ($this->shop->shop_products as $shop_product) {
                if ($this->removeProduct($shop_product->marketProductSku) === true) $res[] = $shop_product->marketProductSku;
                else $errors[] = [$shop_product->marketProductSku, $shop_product->mps_sku];
            }

            dd($errors, $res);



            $res = [];
            $errors = [];
            $page = 1;
            do {
                $bagisto_products = $this->bagistoGetProducts(100, $page);
                foreach ($bagisto_products as $bagisto_product) {

                    if ($this->removeProduct($bagisto_product->id) === true) $res[] = $bagisto_product->id;
                    else $errors[] = [$bagisto_product->id, $bagisto_product->sku];
                }

                $page++;

            } while ($page < 1000 && isset($bagisto_products) && !empty($bagisto_products));

            dd($page, $errors, $res);

        } catch (Throwable $th) {
            dd($th);
        }
    }

}
