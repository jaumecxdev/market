<?php

namespace App\Libraries;


use App\Address;
use App\Buyer;
use App\Country;
use App\Currency;
use App\MarketBrand;
use App\Order;
use App\Shop;
use App\ShopJob;
use App\ShopProduct;
use App\Status;
use App\Traits\HelperTrait;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\Mpe as FacadesMpe;
use Throwable;


class WishWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    private $client = null;
    private $paypal_fee = 0.029;
    private $paypal_fee_addon = 0.35;

    private $shipping_time = '2-3';
    private $shipping = 3.99;
    private $country_shipping_prices = [
        'AD'    => 3.99,   // Andorra
        'PT'    => 3.99,   // Portugal
        'ES'    => 3.99,
    ];


    const DEFAULT_CONFIG = [
        // MarketWS
        'header' => null,
        'header_rows' => 1,
        'order_status_ignored' => ['SHIPPED'],
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



    public function __construct(Shop $shop)
    {
        parent::__construct($shop);

        if (isset($this->config)) {
            if (isset($this->config->shipping)) {
                $this->shipping = $this->config->shipping;
            }
            if (isset($this->config->country_shipping_prices)) {
                $this->country_shipping_prices = json_decode($this->config->country_shipping_prices, true);
            }
        }

        $this->shipping_time = $shop->shipping ?? $this->shipping_time;

        $this->client = new Client(['base_uri' => $shop->endpoint]);
        if (isset($this->shop->token) && !$this->testToken()) {
            if ($this->refreshToken()) $this->shop = $this->shop->refresh();
            else return null;
        }
    }


    public function authorize()
    {
        try {
            if (!isset($this->shop->client_id) || !isset($this->shop->client_secret)  || !isset($this->shop->redirect_url) || isset($this->shop->token))
                dd('Faltan datos en esta tienda: [client_id, client_secret, redirect_url] o token NO Nulo.');

            // Authorize App & get Authorization Code via Redirect URI
            // RETURN TO REDIRECT URI: 
            return redirect()->to($this->shop->endpoint. 'v3/oauth/authorize?client_id=' .$this->shop->client_id);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $this);
        }
    }


    /************** PRIVATE FUNCTIONS - TOKEN ***************/


    private function testToken()
    {
        // TEST token -> FAILS -> REFRESH
        // api/v3/oauth/test -> RESPONSE: $json_res->data->merchant_id
        try {
            $response = $this->client->get('api/v3/oauth/test', ['query' => [
                'access_token' => $this->shop->token,
            ]
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_test.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0)
                    if (isset($json_res->data->merchant_id))
                        return true;
            }

            Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_test_errors.json', $response->getBody()->getContents());
            return false;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function refreshToken()
    {
        // Refresh access_token every 30 days
        try {
            $response = $this->client->get('api/v3/oauth/refresh_token', ['query' => [
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
                    $this->shop->marketSellerId = $json_res->data->merchant_id;
                    $this->shop->save();

                    return true;
                }
            }

            Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_refresh_errors.json', $response->getBody()->getContents());
            return false;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    /************** PRIVATE FUNCTIONS - ORDERS ***************/


    private function updateOrCreateOrder($csv_row_array)
    {
        try {
            Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_orders.json', json_encode($csv_row_array));

            $country = Country::firstOrCreate([
                'code'  => $csv_row_array[39],
            ],[
                'name'  => $csv_row_array[36]
            ]);

            $address = Address::firstOrCreate([
                'country_id'            => $country->id,
                'market_id'             => $this->market->id,
                'name'                  => $csv_row_array[28],
                'phone'                 => $csv_row_array[38],
                'zipcode'               => $csv_row_array[35],
            ],[
                'marketBuyerId'         => null,
                'address1'              => $csv_row_array[31],
                'address2'              => $csv_row_array[32],
                'city'                  => $csv_row_array[33],
                'state'                 => $csv_row_array[34],
            ]);

            $buyer = Buyer::firstOrCreate([
                'market_id'             => $this->market->id,
                'name'                  => $csv_row_array[28],
                'phone'                 => $csv_row_array[38],
            ],[
                'marketBuyerId'         => null,
                'shipping_address_id'   => $address->id ?? null,
                'billing_address_id'    => null,
            ]);

            $status = Status::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketStatusName'      => $csv_row_array[3],
                'type'                  => 'order',
            ],[
                'name'                  => $csv_row_array[3],
            ]);

            $currency = Currency::firstOrCreate([
                'code'      => $csv_row_array[11],
            ],[
                'name'      => $csv_row_array[11],
            ]);

            $price = floatval(str_replace('€', '', $csv_row_array[12]));
            $price_cost = floatval(str_replace('€', '', $csv_row_array[13]));
            $shipping_price = floatval(str_replace('€', '', $csv_row_array[14]));

            $order = Order::whereMarketId($this->market->id)->whereShopId($this->shop->id)->where('marketOrderId', $csv_row_array[1])->first();
            $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $order = Order::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'marketOrderId'         => $csv_row_array[1],
            ],[
                'buyer_id'              => $buyer->id ?? null,
                'shipping_address_id'   => $address->id ?? null,
                'billing_address_id'    => null,
                'currency_id'           => $currency->id ?? null,
                'status_id'             => $status->id ?? null,
                'type_id'               => null,
                'SellerId'              => null,
                'SellerOrderId'         => null,
                'info'                  => '',
                'price'                 => $price,
                'tax'                   => 0,
                'shipping_price'        => $shipping_price,
                'shipping_tax'          => 0,
                'notified'              => $notified,
                'notified_updated'      => $notified_updated,
            ]);

            // 2020-05-13T06:50:51
            $order->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s', $csv_row_array[0])->addHours(1)->format('Y-m-d H:i:s');
            // 2020-05-13T06:50:51 - Last Updated ? Order Or Buyer
            $order->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s', $csv_row_array[37])->addHours(1)->format('Y-m-d H:i:s');
            $order->save();

            $order_item = $order->updateOrCreateOrderItem(
                $csv_row_array[2],
                $csv_row_array[4],
                $csv_row_array[6],
                $csv_row_array[5],
                intval($csv_row_array[16]),
                $price,
                0,
                $shipping_price,
                0,
                null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $csv_row_array);
        }
    }


    private function getCSVOrders($download_link)
    {
        // https://sweeper-sandbox-merchant-export.s3-us-west-1.amazonaws.com/5ebb98b72f2475004d1b15fe-5ebf7594c893542cd8679dee-2020-05-16-05%3A09%3A40.csv
        // ?Signature=DL7eFoSNBKxldmSBe0c%2F4SHbJak%3D&Expires=1589865456&AWSAccessKeyId=AKIAJFT6XO7RY2S4TSRQ
        try {
            $client = new Client();
            $response = $client->get($download_link);

            // header['Content-Type'] => ['application/octet-stream']
            if ($response->getStatusCode() == '200') {
                $filename = date('Y-m-d_H-i-s').'.csv';
                $body = $response->getBody();
                Storage::put($this->storage_dir. 'orders_download/'. $filename, $body);

                $path = storage_path('app/' .$this->storage_dir. 'orders_download/'. $filename);
                $row = 1;
                if (($handle = fopen($path, 'r')) !== FALSE) {
                    while (($csv_row_array = fgetcsv($handle, $length = 0, $delimiter = ",", $enclosure = '"', $escape = "\\")) !== FALSE) {
                        // remove first row (headers)
                        if ($row > 1) {
                            $this->updateOrCreateOrder($csv_row_array);

                            /* $num = count($csv_file_array);
                            echo "<p> $num fields in line $row: <br /></p>\n";
                            for ($c=0; $c < $num; $c++) {
                                echo $csv_file_array[$c] . "<br />\n";
                            } */
                        }

                        $row++;
                    }
                    fclose($handle);
                }
                else {
                    $res = ['path not found' => $path];
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_orders_csv.json', json_encode($res));
                    return $res;
                }

                return $row;
            }
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $download_link);
        }
    }


    private function getOrderDownloadJob(ShopJob $shop_job)
    {
        try {
            $job_result['jobId'] = $shop_job->jobId;
            $response = $this->client->post('api/v2/order/get-download-job-status', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'form_params' => [
                    'job_id' => $shop_job->jobId,
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_download.json', $contents);
                $json_res = json_decode($contents);

                // success
                if ($json_res->code == 0) {
                    $job_result['data'] = $json_res->data;
                    if ($json_res->data->status == 'FINISHED') {
                        // Update Job
                        $shop_job->total_count = $json_res->data->total_count;
                        $shop_job->success_count = $json_res->data->processed_count;
                        $shop_job->save();

                        $count = 0;
                        if (isset($json_res->data->download_link))
                            $count = $this->getCSVOrders($json_res->data->download_link);

                        $job_result['count'] = $count;

                        return $job_result;
                    }
                }
            }

            $job_result['response'] = $response;
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_orders_download.json', json_encode($response));

            return $job_result;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_job);
        }
    }


    private function getOrderJobs()
    {
        try {
            $jobs_result = [];
            $shop_jobs = $this->shop->shop_jobs()
                ->whereNull('total_count')
                ->whereOperation('order')
                ->get();

            $jobs_result['jobs_count'] = $shop_jobs->count();
            foreach ($shop_jobs as $shop_job)  {

                $job_result = $this->getOrderDownloadJob($shop_job);
                $jobs_result['jobs'][] = $job_result;
            }

            return $jobs_result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function startOrderDownloadJob()
    {
        try {
            $response = $this->client->post('api/v2/order/create-download-job', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                //'form_params' => '',
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_create.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {
                    ShopJob::create([
                        'shop_id'   => $this->shop->id,
                        'jobId'     => $json_res->data->job_id,
                        'operation' => 'order',
                    ]);
                    return $json_res->data->job_id;
                }
            }

            $contents = $response->getBody()->getContents();
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_orders_create.json', $contents);
            return $contents;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    /************** PRIVATE FUNCTIONS - BUILDERS ***************/


    private function buildPricesStocksFeed(ShopProduct $shop_product)
    {
        try {
            $shop_product->setPriceStock();

            $item = null;
            $item['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
            $item['inventory'] = $shop_product->enabled ? $shop_product->stock : 0;
            $item['price'] = $shop_product->price;              // price: deprecated
            $item['localized_price'] = $shop_product->price;
            $item['localized_currency_code'] = 'EUR';
            $item['enabled'] = ($shop_product->stock == 0 || !$shop_product->enabled) ? 'False' : 'True';
            $item['shipping_time'] = $this->shipping_time;

            return $item;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildMarketBrandId($brand_name)
    {
        try {
            if (strtoupper(substr($brand_name, 0, 2)) == 'HP') $brand_name = 'Hewlett-Packard';
            if ($brand_name == 'Dell technologies') $brand_name = 'Dell';

            return $this->market->market_brands()->whereName($brand_name)->value('marketBrandId');

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $brand_name);
        }
    }


    private function buildProductFeed(ShopProduct $shop_product)
    {
        try {
            $market_brand_id = $this->buildMarketBrandId($shop_product->product->brand->name);
            if (!$market_brand_id) return 'NO BRAND';

            $title = FacadesMpe::buildString($shop_product->buildTitle());
            $description = FacadesMpe::buildText($shop_product->buildDescription4Mobile());
            $images = array_slice($shop_product->product->public_url_images->toArray(), 0, 20);
            $shop_product->setPriceStock();
            $tags = implode(',', array_slice(explode(' ', str_replace(',', '',$shop_product->product->color.','.$title)), 0, 10));

            $item = null;
            // marketProductSku for Update
            if ($shop_product->isUpgradeable())
                $item['id'] = $shop_product->marketProductSku;
            /* if (isset($shop_product->marketProductSku) &&
                !empty($shop_product->marketProductSku) &&
                ($shop_product->marketProductSku != 'ERROR')) {
                $item['id'] = $shop_product->marketProductSku;
            } */
            //$item['category_id'] = $market_category->marketCategoryId;
            $item['name'] = $title;
            $item['description'] = $description;
            $item['tags'] = $tags;
            $item['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
            $item['inventory'] = $shop_product->enabled ? $shop_product->stock : 0;
            $item['price'] = $shop_product->price;              // price: deprecated
            $item['localized_price'] = $shop_product->price;
            $item['localized_currency_code'] = 'EUR';

            $item['shipping_time'] = $this->shipping_time;
            $item['shipping'] = $this->shipping;                  // shipping: deprecated
            $item['localized_shipping'] = $this->shipping;
            $item['country_shipping_prices'] = json_encode($this->country_shipping_prices);

            $item['main_image'] = $images[0];
            $item['requested_product_brand_id'] = $market_brand_id;
            // localized_default_shipping_price
            // default_shipping_price

            if ($shop_product->product->upc) $item['upc'] = $shop_product->product->upc;
            elseif ($shop_product->product->ean) $item['upc'] = substr($shop_product->product->ean, 1, strlen($shop_product->product->ean));

            array_shift($images);
            if (count($images))
                $item['extra_images'] = implode('|', $images);

            if ($shop_product->product->size) $item['size'] = $shop_product->product->size;
            if ($shop_product->product->color) $item['color'] = $shop_product->product->color;
            if ($shop_product->product->weight) $item['weight'] = $shop_product->product->weight;
            if ($shop_product->product->length) $item['length'] = $shop_product->product->length;
            if ($shop_product->product->width) $item['width'] = $shop_product->product->width;
            if ($shop_product->product->height) $item['height'] = $shop_product->product->height;

            return $item;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildVariantFeed(ShopProduct $shop_product)
    {
        try {
            $shop_product->setPriceStock();
            $images = $shop_product->product->public_url_images->toArray();

            $item = null;
            if ($shop_product->is_sku_child && $shop_product->product->parent_id) {
                //$parent = Product::find($shop_product->product->parent_id);
                $item['parent_sku'] = $shop_product->product->getMPSSku();
            }
            $item['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
            $item['inventory'] = $shop_product->enabled ? $shop_product->stock : 0;
            $item['price'] = $shop_product->price;              // price: deprecated
            $item['localized_price'] = $shop_product->price;
            $item['localized_currency_code'] = 'EUR';
            $item['enabled'] = $shop_product->enabled ? 'True' : 'False';
            $item['shipping_time'] = $this->shipping_time;
            $item['main_image'] = $images[0];

            if ($shop_product->product->size) $item['size'] = $shop_product->product->size;
            if ($shop_product->product->color) $item['color'] = $shop_product->product->color;
            if ($shop_product->product->weight) $item['weight'] = $shop_product->product->weight;
            if ($shop_product->product->length) $item['length'] = $shop_product->product->length;
            if ($shop_product->product->width) $item['width'] = $shop_product->product->width;
            if ($shop_product->product->height) $item['height'] = $shop_product->product->height;

            return $item;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    /************** PRIVATE FUNCTIONS - POSTS ***************/


    private function postPriceStock(ShopProduct $shop_product)
    {
        try {
            if ($item = $this->buildPricesStocksFeed($shop_product)) {
                try {
                    $response = $this->client->post('api/v2/variant/update', [
                        'headers' => [
                            'Authorization' => 'Bearer ' .$this->shop->token,
                        ],
                        'form_params' => $item,
                    ]);

                    if ($response->getStatusCode() == '200') {
                        $contents = $response->getBody()->getContents();
                        Storage::append($this->storage_dir. 'product/' .date('Y-m-d'). '_postPriceStock.json', $contents);
                        $json_res = json_decode($contents);
                        // success
                        if ($json_res->code == 0)
                            return [
                                'id'                => $shop_product->id,
                                'product_id'        => $shop_product->product_id,
                                'marketProductSku'  => $shop_product->marketProductSku
                            ];
                    }

                    $contents = $response->getBody()->getContents();
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_postPriceStock.json', $contents);
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_postPriceStock.json', json_encode($item));
                    return ['CONTENTS:' => $contents, 'ITEM:' => $item];
                }
                catch (Throwable $th) {
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_postPriceStock.json', json_encode($th->getMessage()));
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_postPriceStock.json', json_encode($item));
                    return ['ERROR:' => $th->getMessage(), 'ITEM:' => $item];
                }
            }

            return false;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function postPricesStocksPayload(Collection $shop_products)
    {
        try {
            $count = 0;
            $products_result = null;
            $products_result['count'] = $shop_products->count();
            foreach ($shop_products as $shop_product)
                if ($products_result['updates'][] = $this->postPriceStock($shop_product))
                    $count++;

            $products_result['price_stock'] = $count;
            Storage::append($this->storage_dir. 'payloads/' .date('Y-m-d'). '_prices_stocks.json', json_encode($products_result));

            unset($products_result['updates']);
            return $products_result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function postVariant(ShopProduct $shop_product, $feed_type = 'add')
    {
        try {
            if ($item = $this->buildVariantFeed($shop_product)) {
                $response = $this->client->post('api/v2/variant/'.$feed_type, [
                    'headers' => [
                        'Authorization' => 'Bearer ' .$this->shop->token,
                    ],
                    'form_params' => $item,
                ]);

                if ($response->getStatusCode() == '200') {
                    $contents = $response->getBody()->getContents();
                    Storage::append($this->storage_dir. 'product/' .date('Y-m-d'). '_variant_'.$feed_type.'.json', $contents);
                    $json_res = json_decode($contents);
                    // success
                    if ($json_res->code == 0)
                        if ($feed_type == 'update') {
                            return [
                                'id'                => $shop_product->id,
                                'product_id'        => $shop_product->product_id,
                                'marketProductSku'  => $shop_product->marketProductSku
                            ];
                        }
                    elseif (isset($json_res->data->Variant->id)) {
                        $shop_product->marketProductSku = $json_res->data->Variant->id;
                        $shop_product->save();
                        return [
                            'id'                => $shop_product->id,
                            'product_id'        => $shop_product->product_id,
                            'marketProductSku'  => $shop_product->marketProductSku
                        ];
                    }
                }

                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_variant_'.$feed_type.'.json', $contents);
                return $contents;
            }

            return $item;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_product, $feed_type]);
        }
    }


    private function postProduct(ShopProduct $shop_product, $feed_type = 'add')
    {
        try {
            $products_result = null;
            $item = $this->buildProductFeed($shop_product);

            if ($item) {
                if ($item == 'NO BRAND') {
                    $shop_product->marketProductSku = 'NO BRAND';
                    $shop_product->save();
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_product_'.$feed_type.'_NO_BRAND_'.
                        $shop_product->product->brand->name.'.json', json_encode($shop_product->toArray()));
                    return 'NO BRAND';
                }

                try {
                    $response = $this->client->post('api/v2/product/'.$feed_type, [
                        'headers' => [
                            'Authorization' => 'Bearer ' .$this->shop->token,
                        ],
                        'form_params' => $item,
                    ]);

                    if ($response->getStatusCode() == '200') {
                        $contents = $response->getBody()->getContents();
                        Storage::append($this->storage_dir. 'product/' .date('Y-m-d'). '_product_'.$feed_type.'.json', $contents);



                        $json_res = json_decode($contents);
                        // success
                        if ($json_res->code == 0)
                            if ($feed_type == 'update') {
                                return [
                                    'id'                => $shop_product->id,
                                    'product_id'        => $shop_product->product_id,
                                    'marketProductSku'  => $shop_product->marketProductSku
                                ];
                            }
                            elseif (isset($json_res->data->Product->id)) {
                                $shop_product->marketProductSku = $json_res->data->Product->id;
                                $shop_product->save();
                                //$shop_product->toArray();
                                $products_result['parent'] = [
                                    'id'                => $shop_product->id,
                                    'product_id'        => $shop_product->product_id,
                                    'marketProductSku'  => $shop_product->marketProductSku
                                ];

                                // Post Variants
                                if (count($shop_product->product->childs)) {
                                    foreach ($shop_product->product->childs as $child) {
                                        $child_shop_product = $this->postVariant($child->shop_product($this->shop->id)->first(), $feed_type);
                                        $products_result['childs'][] = $child_shop_product;
                                    }
                                }

                                return $products_result;
                            }

                        return $json_res;
                    }

                    $contents = $response->getBody()->getContents();
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_product_'.$feed_type.'.json', $contents);

                    return $contents;
                }
                catch (Throwable $th) {

                    $shop_product->marketProductSku = 'ERROR';
                    $shop_product->save();
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_product_'.$feed_type.'.json', json_encode($th->getMessage()));



                    return $th;
                }
            }

            return $item;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$shop_product, $feed_type]);
        }
    }


    private function postPayload(Collection $shop_products, $feed_type = 'add')
    {
        try {
            $count = 0;
            $products_result = null;
            $products_result['count'] = $shop_products->count();

            foreach ($shop_products as $shop_product) {
                $result = $this->postProduct($shop_product, $feed_type);
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


    private function removeOneProduct(ShopProduct $shop_product)
    {
        try {
            $marketProductSku = $shop_product->marketProductSku;
            $response = $this->client->post('api/v2/product/remove', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'form_params' => [
                    'id'    => $marketProductSku,
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'product/' .date('Y-m-d'). '_' .$marketProductSku. '_remove.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {
                    $shop_product->deleteSecure();

                    return $marketProductSku;
                }

            }

            $contents = $response->getBody()->getContents();
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$marketProductSku. '_remove', $contents);
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$marketProductSku. '_remove', json_encode($marketProductSku));
            return ['CONTENTS ERROR:' => $contents, 'marketProductSku:' => $marketProductSku];
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function deleteOnlineProducts(array $delete_list)
    {
        try {
            foreach ($delete_list as $marketProductSku) {
                $response = $this->client->post('api/v2/product/remove', [
                    'headers' => [
                        'Authorization' => 'Bearer ' .$this->shop->token,
                    ],
                    'form_params' => [
                        'id'    => $marketProductSku,
                    ],
                ]);
            }

            return true;

        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $delete_list);
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


    /************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getBrands($id_min = null)
    {
        try {
            $query = [
                'sort_by'   => 'id.asc',
                'limit'     => 500,
            ];
            if (isset($id_min)) $query['id_min'] = $id_min;
            $response = $this->client->get('api/v3/brands', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => $query,
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'requests/' .date('Y-m-d'). '_brands.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {
                    $last_id = null;
                    $count = 0;
                    foreach ($json_res->data as $wish_brand) {
                        MarketBrand::updateOrCreate(
                            [
                                'market_id'             => $this->market->id,
                                'marketBrandId'         => $wish_brand->id,
                            ],
                            [
                                'name'                  => $wish_brand->name,
                            ]
                        );

                        $last_id = $wish_brand->id;
                        $count++;
                    }
                    if ($count >= 500)
                        $this->getBrands($last_id);
                }
            }

            return 'Marcas guardadas en la BBDD market_brands y en mp/wish/requests/brands.json';
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $id_min);
        }
    }


    public function getCategories($marketCategoryId = null)
    {
        return 'Wish no tiene categorías.';
    }


    public function getAttributes(Collection $market_categories)
    {
        return 'Wish no tiene atributos.';
    }


    public function getFeed(ShopProduct $shop_product)
    {
        try {
            $item = $this->buildProductFeed($shop_product);

            return $item;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function getJobs()
    {
        return 'Wish no tiene Jobs.';
    }


    public function getOrders()
    {
        try {
            $res = null;
            $res['get_order_jobs'] = $this->getOrderJobs();
            $res['start_order_jobs'] = $this->startOrderDownloadJob();

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function getGroups()
    {
        return 'Wish no tiene grupos de categorías.';
    }


    public function getCarriers()
    {
        return 'Wish no tiene transportistas.';
    }


    public function getOrderComments(Order $order)
    {
        return 'Wish no tiene comentarios de pedidos.';
    }


    /************ PUBLIC FUNCTIONS - POSTS *******************/


    public function postNewProduct(ShopProduct $shop_product)
    {
        try {
            $shop_products = new Collection([$shop_product]);
            return $this->postNewProducts($shop_products);
            //return $this->postProduct($shop_product, 'add');

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function postUpdatedProduct(ShopProduct $shop_product)
    {
        try {
            $shop_products = new Collection([$shop_product]);
            return $this->postUpdatedProducts($shop_products);
            //return $this->postProduct($shop_product, 'update');

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function postPriceProduct(ShopProduct $shop_product)
    {
        try {
            $shop_products = new Collection([$shop_product]);
            return $this->postPricesStocks($shop_products);
            //return $this->postPriceStock($shop_product);

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


    public function postUpdatedProducts($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            return $this->postPayload($shop_products, 'update');

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

            return $this->postPricesStocksPayload($shop_products);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postGroups($shop_products = null)
    {
        return 'Wish no tiene grupos de categorías.';
    }


    public function removeProduct($marketProductSku = null)
    {
        try {
            /* $shop_products = $this->shop->shop_products()->where('marketProductSku', 'ERROR')->get();
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
            /*  $shop_products = $this->shop->shop_products()->get();
                $res = null;
                $count = 0;
                foreach ($shop_products as $shop_product) {

                    if (in_array($shop_product->product->category_id,
                        [3126, 2860, 2838, 5244, 2839, 4236, 3155, 4334, 3153, 2912, 2878, 5247, 3211,
                        4356, 2596, 2907, 3152, 3152, 2965, 2505, 2516, 3159, 2825, 2877, 5249, 3097, 3139, 2879, 2574, 4069, 4405])) {
                            $res[] = $this->removeOneProduct($shop_product);

                            $count++;
                        }
                }
                */
            }

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketProductSku);
        }

    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        return 'Wish no tiene trackings de pedidos.';
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return 'Wish no tiene comentarios de pedidos.';
    }


    public function synchronize()
    {
        return 'Wish no tiene sincronismo online offline.';
    }


    public function removeWithoutStock()
    {
        return 'Wish no tiene eliminación en grupo.';
    }



    /************* REQUEST FUNCTIONS *********************/


    public function getProduct($marketProductSku)
    {
        try {
            $response = $this->client->get('api/v2/product', [
                /*'query' => [
                    'access_token' => $this->shop->token,
                    'id' => $marketProductSku,
                ]*/
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
                    dd($json_res->data->Product, $json_res);
            }

        }
        catch (Throwable $th) {
            dd($th);
        }
    }


    public function getAllProducts()
    {
        /*
        $shop_products = $this->shop->shop_products()->get();
        $res = null;
        foreach ($shop_products as $shop_product) {
            //$res[$shop_product->product->category_id] = $shop_product->product->category->name;
            $res[] = $shop_product->product->category_id;
        }
        foreach ($res as $category_id) {
            $this->shop->shop_filters()->updateOrCreate(
                [
                    'supplier_id'   => 1,   // idiomund
                    'status_id'     => 1,
                    'stock_min'     => 5,
                    'category_id'   => $category_id,
                ],[]);
            $this->shop->shop_filters()->updateOrCreate(
                [
                    'supplier_id'   => 8,   // idiomund others
                    'status_id'     => 1,
                    'stock_min'     => 5,
                    'category_id'   => $category_id,
                ],[]);
            dd($category_id);
        }
        foreach ($res as $category_id) {
            ShopFilter::updateOrCreate(
                [
                    'shop_id'       => 3,
                    'supplier_id'   => 1,   // idiomund
                    'status_id'     => 1,
                    'stock_min'     => 5,
                    'cost_max'      => 400,
                    'category_id'   => $category_id,
                ],[]);
            ShopFilter::updateOrCreate(
                [
                    'shop_id'       => 3,
                    'supplier_id'   => 8,   // idiomund others
                    'status_id'     => 1,
                    'stock_min'     => 5,
                    'cost_max'      => 400,
                    'category_id'   => $category_id,
                ],[]);
            //dd($category_id);
        }
        dd($res);
        */




        try {
            // FIRST
            $response = $this->client->get('api/v2/product/create-download-job', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => [
                    'show_rejected' => true
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'requests/' .date('Y-m-d'). '_products.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {
                    ShopJob::create([
                        'shop_id'   => $this->shop->id,
                        'jobId'     => $json_res->data->job_id,
                            'operation' => 'product',
                    ]);
                    dd('In 1 minute -> GetAllProducts JOB', $json_res->data->job_id, $json_res->data);
                }
            }

            dd($response);


            // SECOND
            //dd($this->getAllProductsJob());
        }
        catch (Throwable $th) {
            dd($th);
        }
    }


    private function getCSVProducts($download_link)
    {
        try {
            $client = new Client();
            $response = $client->get($download_link);

            // header['Content-Type'] => ['application/octet-stream']
            if ($response->getStatusCode() == '200') {
                $filename = date('Y-m-d_H-i-s').'.csv';
                $body = $response->getBody();
                Storage::put($this->storage_dir. 'products_download/'. $filename, $body);

                $path = storage_path('app/' .$this->storage_dir. 'products_download/'. $filename);
                $row = 1;
                if (($handle = fopen($path, 'r')) !== FALSE) {

                    //$delete_list = [];

                    $count =0;
                    $res = null;
                    while (($csv_row_array = fgetcsv($handle, $length = 0, $delimiter = ",", $enclosure = '"', $escape = "\\")) !== FALSE) {

                        // remove first row (headers)
                        if ($row > 1 && isset($csv_row_array[0]) && isset($csv_row_array[5])) {
                            //dd($row, $csv_row_array, $csv_row_array[0], $csv_row_array[5]);
                            //$product_id = $this->getIdFromMPSSku($csv_row_array[5]);

                            // REMOVE ONLINE PRODUCT
                            //$delete_list[] = $csv_row_array[0];

                            $shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $csv_row_array[5]);
                            if ( isset($shop_product) && !$shop_product->isUpgradeable()) {
                                $shop_product->marketProductSku = $csv_row_array[0];
                                $shop_product->save();
                                //dd($row, $csv_row_array, $shop_product);
                                $count++;
                            }
                        }
                        else
                            $res[] = [$row, $csv_row_array];

                        $row++;
                    }
                    fclose($handle);


                    //$this->deleteOnlineProducts($delete_list);

                    dd($row, $count, $res);
                }
                else {
                    $res = ['path not found' => $path];
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_products_csv.json', json_encode($res));
                    return $res;
                }

                return $row;
            }
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_products_csv.json', json_encode($th->getMessage()));
            return $th;
        }
    }



    public function getAllProductsJob()
    {
        $jobs_result = null;
        $shop_jobs = $this->shop->shop_jobs()
            ->whereNull('total_count')
            ->whereOperation('product')
            ->get();

        $jobs_result['jobs'] = $shop_jobs->count();
        foreach ($shop_jobs as $shop_job) {

            $response = $this->client->post('api/v2/product/get-download-job-status', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'form_params' => [
                    'job_id' => $shop_job->jobId,
                ]
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'requests/' .date('Y-m-d'). '_products_jobs.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0) {
                    $jobs_result[$shop_job->jobId]['data'] = $json_res->data;

                    if ($json_res->data->status == 'FINISHED') {
                        // Update Job
                        $shop_job->total_count = $json_res->data->total_count;
                        $shop_job->success_count = $json_res->data->processed_count;
                        $shop_job->save();

                        $count = $this->getCSVProducts($json_res->data->download_link);
                        $jobs_result['count'] = $count;
                    }
                }
            }
            else
                $jobs_result['responses'][] = $response;
        }

        dd($jobs_result);
    }


    public function removeAllProducts()
    {
        $shop_products = $this->getShopProducts4Update();
        if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

        $count = 0;
        $products_result = [];
        $products_result['count'] = $shop_products->count();
        foreach ($shop_products as $shop_product) {
            if ($products_result['removes'][] = $this->removeOneProduct($shop_product))
                $count++;
        }

        $products_result['removes_count'] = $count;
        Storage::append($this->storage_dir. 'products/' .date('Y-m-d'). '_REMOVES_ALL.json', json_encode($products_result));

        return $products_result;
    }


    public function getCurrencies()
    {
        try {
            $response = $this->client->get('api/v3/currencies', [
                'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ],
                'query' => [],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'requests/' .date('Y-m-d'). '_currencies.json', $contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0)
                    dd($json_res);
            }

            dd('Error opteniendo Currencies');
        }
        catch (Throwable $th) {
            dd($th);
        }
    }





}
