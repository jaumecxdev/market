<?php

namespace App\Libraries;


use App\Address;
use App\Buyer;
use App\Country;
use App\Currency;
use App\MarketBrand;
use App\Order;
use App\Product;
use App\Shop;
use App\ShopJob;
use App\ShopProduct;
use App\Status;
use App\Traits\HelperTrait;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;



/**
 * Class FruugoWS                   FruugoWS Web Service
 */
class FruugoWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    private $client = null;
    private $shipping_time = '2-5';
    private $paypal_fee = 0.029;
    private $paypal_fee_addon = 0.35;

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


    // Base: MPS: 5 - MP: 6
    public function __construct(Shop $shop)     //, $fee_mps = 5, $fee_mp = 18, $iva = 21)
    {
        parent::__construct($shop);     //, $fee_mps, $fee_mp, $iva);
        /* $this->storage_dir .= $shop->market->code.'/';
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir); */
        $this->shipping_time = $shop->shipping;

        $this->client = new Client(['base_uri' => $shop->endpoint]);
        /* if (isset($this->shop->token) && !$this->testToken()) {
            if ($this->refreshToken()) $this->shop = $this->shop->refresh();
            else return null;
        } */
    }


    public function authorize()
    {
        if (!isset($this->shop->client_id) || !isset($this->shop->client_secret)  || !isset($this->shop->redirect_url) || isset($this->shop->token))
            dd('Faltan datos en esta tienda: [client_id, client_secret, redirect_url] o token NO Nulo.');

        // Authorize App & get Authorization Code via Redirect URI
        // RETURN TO REDIRECT URI: https://app.mpespecialist.com/oauth/wish?code=6430c1eb040f45f38e6a55e7f8fe2feb
        return redirect()->to($this->shop->endpoint. 'v3/oauth/authorize?client_id=' .$this->shop->client_id);
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
            Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_test_errors.json', json_encode($th->getMessage()));
            return false;
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
            Storage::append($this->storage_dir. 'oauth/' .date('Y-m-d'). '_refresh_errors.json', json_encode($th->getMessage()));
            return false;
        }
    }


    /************** PRIVATE FUNCTIONS - ORDERS ***************/


    private function updateOrCreateOrder($csv_row_array)
    {
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
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_orders_csv.json', json_encode($th->getMessage()));
            return $th;
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
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_orders_download.json', json_encode($th->getMessage()));
            return json_encode($th->getMessage());
        }
    }


    private function getOrderJobs()
    {
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
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_orders_create.json', json_encode($th->getMessage()));
            return json_encode($th->getMessage());
        }
    }


    /************** PRIVATE FUNCTIONS - BUILDERS ***************/


    private function buildPricesStocksFeed(ShopProduct $shop_product)
    {
        $shop_product->setPriceStock();

        $shop_product->stock = 0;
        $shop_product->save();

        $item = null;
        $item['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
        $item['inventory'] = $shop_product->stock;
        $item['price'] = $shop_product->price;              // price: deprecated
        $item['localized_price'] = $shop_product->price;
        $item['localized_currency_code'] = 'EUR';
        $item['enabled'] = 'False';  // 'True'
        $item['shipping_time'] = $this->shipping_time;

        return $item;
    }


    private function buildWishTitle(Product $product)
    {
        return mb_substr($product->buildTitle(), 0, 255);
    }


    private function buildMarketBrandId($brand_name)
    {
        if (strtoupper(substr($brand_name, 0, 2)) == 'HP') $brand_name = 'Hewlett-Packard';
        if ($brand_name == 'Dell technologies') $brand_name = 'Dell';

        return $this->market->market_brands()->whereName($brand_name)->value('marketBrandId');
    }


    private function buildProductFeed(ShopProduct $shop_product)
    {
        $market_brand_id = $this->buildMarketBrandId($shop_product->product->brand->name);
        if (!$market_brand_id) return 'NO BRAND';

        $title = $this->buildWishTitle($shop_product->product);
        $description = $shop_product->product->buildDescription4Mobile();
        $images = array_slice($shop_product->product->public_url_images->toArray(), 0, 20);
        $shop_product->setPriceStock();
        $tags = implode(',', array_slice(explode(' ', str_replace(',', '',$shop_product->product->color.','.$title)), 0, 10));

        $item = null;
        // marketProductSku for Update
        if (isset($shop_product->marketProductSku) &&
            !empty($shop_product->marketProductSku) &&
            ($shop_product->marketProductSku != 'ERROR')) {
            $item['id'] = $shop_product->marketProductSku;
        }
        //$item['category_id'] = $market_category->marketCategoryId;
        $item['name'] = $title;
        $item['description'] = $description;
        $item['tags'] = $tags;
        $item['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
        $item['inventory'] = $shop_product->stock;
        $item['price'] = $shop_product->price;              // price: deprecated
        $item['localized_price'] = $shop_product->price;
        $item['shipping'] = 0;                              // shipping: deprecated
        $item['localized_shipping'] = 0;
        $item['localized_currency_code'] = 'EUR';
        $item['country_shipping_prices'] = json_encode([
            'AD'    => 0,   // Andorra
            'PT'    => 0,   // Portugal
            'ES'    => 0,
        ]);
        $item['shipping_time'] = $this->shipping_time;
        $item['main_image'] = $images[0];

        $item['requested_product_brand_id'] = $market_brand_id;

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
    }


    private function buildVariantFeed(ShopProduct $shop_product)
    {
        $shop_product->setPriceStock();
        $images = $shop_product->product->public_url_images->toArray();

        $item = null;
        if ($shop_product->is_sku_child && $shop_product->product->parent_id) {
            //$parent = Product::find($shop_product->product->parent_id);
            $item['parent_sku'] = $shop_product->product->getMPSSku();
        }
        $item['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
        $item['inventory'] = $shop_product->stock;
        $item['price'] = $shop_product->price;              // price: deprecated
        $item['localized_price'] = $shop_product->price;
        $item['localized_currency_code'] = 'EUR';
        $item['enabled'] = 'True';
        $item['shipping_time'] = $this->shipping_time;
        $item['main_image'] = $images[0];

        if ($shop_product->product->size) $item['size'] = $shop_product->product->size;
        if ($shop_product->product->color) $item['color'] = $shop_product->product->color;
        if ($shop_product->product->weight) $item['weight'] = $shop_product->product->weight;
        if ($shop_product->product->length) $item['length'] = $shop_product->product->length;
        if ($shop_product->product->width) $item['width'] = $shop_product->product->width;
        if ($shop_product->product->height) $item['height'] = $shop_product->product->height;

        return $item;
    }


    /************** PRIVATE FUNCTIONS - POSTS ***************/


    private function postPriceStock(ShopProduct $shop_product)
    {
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
                    Storage::append($this->storage_dir. 'product/' .date('Y-m-d'). '_variant_price_stock.json', $contents);
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
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_variant_price_stock.json', $contents);
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_variant_price_stock.json', json_encode($item));
                return ['CONTENTS:' => $contents, 'ITEM:' => $item];
            }
            catch (Throwable $th) {
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_variant_price_stock.json', json_encode($th->getMessage()));
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_variant_price_stock.json', json_encode($item));
                return ['ERROR:' => $th, 'ITEM:' => $item];
            }
        }

        return false;
    }


    private function postPricesStocksPayload(Collection $shop_products)
    {
        $count = 0;
        $products_result = null;
        $products_result['count'] = $shop_products->count();
        foreach ($shop_products as $shop_product)
            if ($products_result['updates'][] = $this->postPriceStock($shop_product))
                $count++;

        $products_result['price_stock'] = $count;
        Storage::append($this->storage_dir. 'payloads/' .date('Y-m-d'). '_prices_stocks.json', json_encode($products_result));

        return $products_result;
    }


    private function postVariant(ShopProduct $shop_product, $feed_type = 'add')
    {
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
    }


    private function postProduct(ShopProduct $shop_product, $feed_type = 'add')
    {
        $products_result = null;
        $item = $this->buildProductFeed($shop_product);

        //dd($item, $shop_product, $feed_type);

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

                    //dd('json_res', $json_res);

                    return $json_res;
                }

                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_product_'.$feed_type.'.json', $contents);

                //dd('contents', $contents);

                return $contents;
            }
            catch (Throwable $th) {

                //dd($shop_product, $feed_type, $th);

                $shop_product->marketProductSku = 'ERROR';
                $shop_product->save();
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_product_'.$feed_type.'.json', json_encode($th->getMessage()));



                return $th;
            }
        }

        return $item;
    }


    private function postPayload(Collection $shop_products, $feed_type = 'add')
    {
        $count = 0;
        $products_result = null;
        $products_result['count'] = $shop_products->count();

        foreach ($shop_products as $shop_product)
            $result = $this->postProduct($shop_product, $feed_type);
            $products_result['products'][] = $result;
            $count++;

            /* if ($result && $result != 'NO BRAND') {
                $products_result['products'][] = $result;
                $count++;
            } */

        $products_result[$feed_type] = $count;
        Storage::append($this->storage_dir. 'payloads/' .date('Y-m-d'). '_'.$feed_type.'.json', json_encode($products_result));

        return $products_result;
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
                    $shop_product->delete();

                    return $marketProductSku;
                }

            }

            $contents = $response->getBody()->getContents();
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$marketProductSku. '_remove', $contents);
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$marketProductSku. '_remove', json_encode($marketProductSku));
            return ['CONTENTS ERROR:' => $contents, 'marketProductSku:' => $marketProductSku];
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$marketProductSku. '_remove', json_encode($th->getMessage()));
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$marketProductSku. '_remove', json_encode($marketProductSku));
            return ['ERROR:' => $th, 'marketProductSku:' => $marketProductSku];
        }
    }


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
            return $th->getMessage();
        }
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
        $item = $this->buildProductFeed($shop_product);

        return $item;
    }


    public function getJobs()
    {
        return false;
    }


    public function getOrders()
    {
        $res = null;
        $res['get_order_jobs'] = $this->getOrderJobs();
        $res['start_order_jobs'] = $this->startOrderDownloadJob();

        return $res;
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
        $shop_products = new Collection([$shop_product]);
        return $this->postNewProducts($shop_products);
        //return $this->postProduct($shop_product, 'add');
    }


    public function postUpdatedProduct(ShopProduct $shop_product)
    {
        $shop_products = new Collection([$shop_product]);
        return $this->postUpdatedProducts($shop_products);
        //return $this->postProduct($shop_product, 'update');
    }


    public function postPriceProduct(ShopProduct $shop_product)
    {
        $shop_products = new Collection([$shop_product]);
        return $this->postPricesStocks($shop_products);
        //return $this->postPriceStock($shop_product);
    }


    public function postNewProducts($shop_products = null)
    {
        $shop_products = $this->getShopProducts4Create($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos nuevos en esta Tienda';

        return $this->postPayload($shop_products, 'add');
    }


    public function postUpdatedProducts($shop_products = null)
    {
        $shop_products = $this->getShopProducts4Update($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

        return $this->postPayload($shop_products, 'update');
    }


    public function postPricesStocks($shop_products = null)
    {
        $shop_products = $this->getShopProducts4Update($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

        return $this->postPricesStocksPayload($shop_products);
    }


    public function postGroups($shop_products = null)
    {
        return false;
    }


    public function removeProduct($marketProductSku = null)
    {
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
                    dd($json_res->data->Product);
            }


        }
        catch (Throwable $th) {
            dd($th);
        }
    }


    public function getAllProducts()
    {
        //https://fruugo.atlassian.net/wiki/spaces/RR/pages/2133524481/v2+Download+XML+Specification

        /* public  class Item
        {
            public int fruugoProductId { get; set; }//
            public int fruugoSkuId { get; set; }
            public int  quantity { get; set; }

        } */

        try {
            /* OK ORDERS
            // orders/download/v2
            // <?xml version="1.0" encoding="UTF-8" standalone="yes"
            // o:orders merchantId="12934" xmlns:o="https://www.fruugo.com/orders/schema"
            $response = $this->client->get('orders/download/v2', [
                'auth' => [
                    'regalasexo@gmail.com',
                    'bsAM6d8Z'
                ],
                'query' => [
                    'from' => date('Y-m-d'),
                    'to' => date('Y-m-d')
                ],
            ]);
            */

            // OK get stocks
            // <?xml version="1.0" encoding="UTF-8"
            // <skus />
            /* $response = $this->client->get('stockstatus-api', [
                'auth' => [
                    'regalasexo@gmail.com',
                    'bsAM6d8Z'
                ],
            ]);
            */

            /*$response = $this->client->post('api/v2/product/get-download-job-status', [
                'auth' => [
                    'regalasexo@gmail.com',
                    'bsAM6d8Z'
                ],
                'form_params' => [
                    'job_id' => $shop_job->jobId,
                ]
            ]);

            // dd($response);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::put($this->storage_dir. 'requests/' .date('Y-m-d_H_i_s'). '_test.json', $contents);
                 if ($this->file_cdata)
                    $contents = preg_replace('~\s*(<([^-->]*)>[^<]*<!--\2-->|<[^>]*>)\s*~','$1', $contents);
                $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA);
                $json = json_encode($xml);

                $json_res = json_decode($contents);
                dd($json_res, $contents);

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

            dd($response);*/
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

                    $count =0;
                    $res = null;
                    while (($csv_row_array = fgetcsv($handle, $length = 0, $delimiter = ",", $enclosure = '"', $escape = "\\")) !== FALSE) {

                        // remove first row (headers)
                        if ($row > 1 && isset($csv_row_array[0]) && isset($csv_row_array[5])) {
                            //dd($row, $csv_row_array, $csv_row_array[0], $csv_row_array[5]);
                            //$product_id = $this->getIdFromMPSSku($csv_row_array[5]);
                            $shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $csv_row_array[5]);
                            if ( isset($shop_product) &&
                                (!isset($shop_product->marketProductSku) || $shop_product->marketProductSku == 'ERROR' ||
                                $shop_product->marketProductSku = 'NO BRAND') ) {
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
