<?php

namespace App\Http\Controllers;

use App\Libraries\MarketWS;
use App\MarketAttribute;
use App\MarketCategory;
use App\MarketParam;
use App\Notifications\MailOrderNotification;
use App\Order;
use App\OrderItem;
use App\Product;
use App\Property;
use App\PropertyValue;
use App\ProviderAttribute;
use App\ProviderCategory;
use App\ProviderProductAttribute;
use App\Receiver;
use App\Shop;
use App\ShopFilter;
use App\ShopParam;
use App\ShopProduct;
use App\Supplier;
use App\SupplierCategory;
use App\SupplierParam;
use App\Traits\HelperTrait;
use App\User;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Throwable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Facades\App\Facades\Mpe as FacadesMpe;
use Facades\App\Facades\MpeExcel as FacadesMpeExcel;
use Illuminate\Database\Eloquent\Collection;

//use Google_Service_Customsearch_Search;

class TestController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }


    private function cleanStr($string) {
        // Replaces all spaces with hyphens.
        $string = str_replace(' ', '-', $string);

        // Removes special chars.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        // Replaces multiple hyphens with single one.
        $string = preg_replace('/-+/', '-', $string);

        return $string;
    }


    private function stripAccents($stripAccents){
        return strtr($stripAccents,'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ','aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    }



    private function createSectorList()
    {
        // CREA LLISTA DE TOTS ELS SECTORS DEL CSV
        $sectors = [];
        $source_path = storage_path('app/imports/BBDDSPAIN 2020 COL3.csv');
        $csv_array = file($source_path);
        foreach ($csv_array as $line) {

            $row = str_getcsv($line);
            if (!isset($row[0]) || !isset($row[1]) || !isset($row[2]))
                Storage::append('imports/actividades/_ERROR.json', json_encode($row));
            else {
                $sector = $this->stripAccents($row[1]);
                if (!isset($sectors[$sector])) $sectors[$sector] = 1;
                else $sectors[$sector]++;
            }
        }

        arsort($sectors);
        $target_path = storage_path('app/imports/sectors.csv');
        $fp = fopen($target_path, 'a');
        foreach ($sectors as $key => $value) {
            fputcsv($fp, [$key, $value]);
        }
        fclose($fp);

        return storage_path('app/imports/sectors.csv');
    }



    private function createCSVxMailjet($filename)
    {
        // CREA CSV LLISTA DE EMAILS SEGONS SECTORS
        $source_path = storage_path('app/imports/BBDDSPAIN 2020 COL3.csv');
        $target_path = storage_path('app/imports/' .$filename);
        $fp = fopen($target_path, 'a');

        $csv_array = file($source_path);
        foreach ($csv_array as $line) {

            $row = str_getcsv($line);
            if (!isset($row[0]) || !isset($row[1]) || !isset($row[2]))
                Storage::append('imports/actividades/_ERROR.json', json_encode($row));
            else {
                $sector = $this->cleanStr($this->stripAccents($row[1]));
                if (strpos($sector, 'BARES') !== false ||
                    strpos($sector, 'BAR RESTAURANTE') !== false ||
                    strpos($sector, 'CAFE BAR') !== false ||
                    strpos($sector, 'BAR DE COPAS') !== false ||
                    strpos($sector, 'BAR CERVECERIA') !== false ||
                    $sector == 'BAR') {

                        //Log::channel('commands')->info('companies:import - Nuevo sector: '.$sector);
                        //$target_path = storage_path('app/imports/actividades/'.$sector.'.csv');

                        $name = str_replace(',', ' ', trim($row[0]));
                        $email = trim($row[2]);
                        fputcsv($fp, [$email, $name]);

                }
            }
        }

        fclose($fp);

        return storage_path('app/imports/' .$filename);
    }


    private function makeCSV()
    {
        //$products = Product::whereIn('supplier_id', [1,8,10,11,12,13,14])->where('stock', '>', 0)->get();
        //$products = Product::whereIn('supplier_id', [1])->where('stock', '>', 0)->get();
        $products = Product::whereIn('supplier_id', [1,8])->where('stock', '>', 5)->get();
        $storage_path = storage_path('app/mp/csv/idiomund.csv');
        $delimiter = ",";

        $fp = fopen($storage_path, 'w');
        $columns = ['id', 'brand', 'category_id', 'category', 'pn', 'ean', 'cost', 'stock', 'name', /* 'longdesc', */ 'images'];
        fputcsv($fp, $columns, $delimiter);

        $count = 0;
        foreach ($products as $product) {
            if ($product->ean) {
                $item = [
                    $product->id,
                    $product->brand->name,
                    $product->category_id,
                    $product->category->name,
                    $product->pn,
                    $product->ean,
                    $product->cost / 0.93,
                    $product->stock,
                    mb_substr(ucwords(mb_strtolower(str_replace(['ª','®','™'], ['a','',''], $product->buildTitle()))), 0, 150),
                    //ucwords(mb_strtolower($product->buildDescription4Mobile())),
                    $product->getAllUrlImages()
                ];

                fputcsv($fp, array_values($item), $delimiter);
                $count++;
            }
        }
        fclose($fp);

        dd($count);
    }


    private function testEmail()
    {
        /* $data = [];
        Mail::send('emails.test', $data, function ($message) {
            $message->from('order@mpespecialist.com', 'John Doe');
            $message->sender('order@mpespecialist.com', 'John Doe');
            $message->to('marketplacespecialists@gmail.com', 'John Doe');
            // $message->cc('john@johndoe.com', 'John Doe');
            //$message->bcc('john@johndoe.com', 'John Doe');
            //$message->replyTo('john@johndoe.com', 'John Doe');
            $message->subject('Subject');
            $message->priority(3);
            //$message->attach('pathToFile');
        });
        dd($data); */

        try {

            // Render, NO send mail
            // Funciona AMB i SENSE render()
            //return (new TestMailable('Some data for mail'))->render();


            //$order = Order::firstWhere('marketOrderId', '23');
            //$order_item = OrderItem::firstWhere('marketOrderId', '34350695-A');
            //$order_items = $order->order_items;
            //$token = '12345';
            //$url = route('guest_service.order.track', [$order, $order_item, 'token' => $token]);

            $order = Order::find(635);
            $receiver = Receiver::find(2);
            if ($instance = new MailOrderNotification($order, $order->order_items, $order->getTrackUrl(null))) {
                $res = $receiver->notify($instance);
            }

            dd($res, $instance ?? null, $receiver, $order);

        } catch (Throwable $th) {
            dd($th);
        }
    }


    // supplier_params 042021
    private function createIdiomundSupplierParams()
    {
        /* $supplier = Supplier::find(1);
        $canons = [
            9       => 5.45,    // Portátiles
            10      => 5.45,    // Sobremesa
            37      => 3.15,    // Tablets
            64      => 5.25,    // Impresoras Multifuncionales
            65      => 5.25,    // Impresoras Láser                 Canon nuevo
            66      => 5.25,    // Impresoras Inyección de Tinta    Canon nuevo
            67      => 5.25,    // Impresoras Matriciales           Canon nuevo
            68      => 5.25,    // Plotters                         Canon nuevo
            73      => 5.25,    // Impresoras Fotográficas          Canon nuevo
            74      => 5.25,    // Otras Impresoras                 Canon nuevo
            76      => 5.25,    // Impresoras de Tinta Sólida       Canon nuevo
            88      => 1.10,    // Telefonia
            122     => 5.25,    // Impresoras de Etiquetas y Tickets   Canon nuevo
            165     => 0.24,    // Tarjetas de memoria
            172     => 5.45,    // Disco duro interno
            173     => 6.45,    // Discos Duros Externos
            195     => 5.45,    // SSD
            197     => 0.24,    // Memoria USB
        ];

        foreach ($canons as $supplierCategoryId => $canon) {
            if ($supplier_category = SupplierCategory::whereSupplierId($supplier->id)
                ->where('supplierCategoryId', $supplierCategoryId)
                ->first()) {

                if ($supplier_category->category_id) {

                    SupplierParam::updateOrCreate([
                        'supplier_id'   => $supplier->id,
                        //'brand_id'      => null,
                        'category_id'   => $supplier_category->category_id,
                    ],[
                        'canon'     => $canon,
                        //'rappel'    => $rappel,
                        //'ports'     => $ports,
                    ]);
                }
            }
        } */
    }


    private function getGlobomatikProducts()
    {
        try {
            // STOCK
            // http://multimedia.globomatik.net/csv/import.php?username=36343&password=01802156&filter=stockprecio
            // PRODUCTS
            // http://multimedia.globomatik.net/csv/import.php?username=36343&password=01802156&mode=all&type=default
            $client = new Client(['base_uri' => 'http://multimedia.globomatik.net/']);
            $response = $client->get('csv/import.php', [
                /* 'headers' => [
                    'Authorization' => 'Bearer ' .$this->shop->token,
                ], */
                'query' => [
                    'username'      => '36343',
                    'password'      => '01802156',
                    'mode'          => 'all',
                    'type'          => 'default',
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append('supplier/globomatik/' .date('Y-m-d'). '_products.csv', $contents);
                dd($contents);
                $json_res = json_decode($contents);
                // success
                if ($json_res->code == 0 && isset($json_res->data->items)) {
                    foreach($json_res->data->items as $item)
                        $this->updateOrCreateOrder($item);
                }

                Storage::append($this->storage_dir. 'attributes/' .date('Y-m-d'). '_getOrdersRequest.json', json_encode($json_res));
                return $json_res;
            }

            dd($response);

            /* Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getOrdersRequest.json', json_encode($response->getHeaders()));
            return $response; */
        }
        catch (Throwable $th) {
            dd($th);

            // 401 Unauthorized -> Refresh Token
            if ($th->getCode() == '401' && $this->refreshToken())
                $this->getOrdersRequest();

            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_getOrdersRequest.text', $th->getMessage());
            return $th->getMessage();
        }
    }


    private function writeRowToExcel($row_index, $row, $sheet)
    {
        $i = 1;
        foreach ($row as $value) {
            try {
                $sheet->setCellValueExplicitByColumnAndRow($i, $row_index, $value, DataType::TYPE_STRING);
                $i++;
            } catch(Throwable  $th) {
                dd($i, $row_index, $value, $th);
            }
        }
    }


    private function productsDownload()
    {
        // SELECT *  FROM `products` WHERE `supplier_id` IN (1,8,10,11,12,13,14,16) AND `cost` > 300 AND `ean` IS NOT NULL ORDER BY `type_id`  DESC

        $products = Product::whereIn('supplier_id', [1,8,10,11,12,13,14,16])->where('cost', '>', 300)->whereNotNull('ean')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row_count = 1;
        foreach ($products->sortBy('category_id') as $product) {
            //if ($shop_product->product->images->count()) {
                $this->writeRowToExcel($row_count, [
                        $product->category->name ?? null,
                        $product->ean,
                        $product->brand->name ?? null,
                        $product->pn,
                        $product->name
                    ], $sheet);
                $row_count++;
            //}
        }

        $new_filename = date('Y-m-d').'_TEST.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/'.$new_filename));

        header('Cache-Control: max-age=0');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.urlencode($new_filename).'"');
        $writer->save('php://output');
        exit();
    }


    // dd($this->creteNewSeller('Jordi Mancebon', 'silempresas@grupo-sil.com', 'Bl@nes21'))
    private function createNewSeller($name, $email, $password)
    {
        $user = User::create([
            'name'      => $name,
            'email'     => $email,
            'password'  => Hash::make($password),
        ]);

        $user->assignRole('seller');
        $user->givePermissionTo('shops.*.9,11,12');

        return $user;
    }


    private function createPermissionAssing()
    {
        $permission = Permission::create(['name' => 'suppliers.*.1,8,10,11,13,14']);
        $user = User::find(4);      // Albert
        $user->givePermissionTo($permission);
        $user = User::find(8);      // Jordi Mancebon Silempresas
        $user->givePermissionTo($permission);

        return $permission;

        /* $suppliers_id = $request->user()->getSuppliersId();
            $shops_id = $request->user()->getShopsId();
            $markets_id = $request->user()->getMarketsId(); */
    }


    function getProductId($item)
    {
        return substr($item, 11);       // removes: 'public/img/'
    }

    private function removeOldProductImages()
    {
        try {
            $img_dirs = Storage::directories('public/img/');
            $products_id = array_map(array($this, 'getProductId'), $img_dirs);
            foreach ($products_id as $product_id) {
                if (!$product = Product::find($product_id)) {
                    // Remove Images WithOut Product
                    Storage::deleteDirectory('public/img/'.$product_id);
                }
            }
            dd($products_id);

        } catch (Throwable $th) {
            dd($th);
        }
    }


    private function removeShopProductsWithoutStock()
    {
        // Tables with: shop_product_id
        // order_items (not in use), prices, promos
        $shops = Shop::whereEnabled(1)->get();

        $status_response = [];
        foreach ($shops as $shop) {
            if ($ws = MarketWS::getMarketWS($shop)) {
                $response = $ws->removeWithoutStock();
                $status_response[] = $response;
            }
        }
        dd($status_response);
    }


    private function removeProductsWithoutStock()
    {
        // Tables with: product_id
        // shop_products, product_product, images, shop_filters, product_attributes, order_items,
        // prices, promos, shop_params
        $products = Product::whereStock(0)->orWhere('ready', 0)->get();
        foreach ($products as $product) {
            if (!$product->shop_products->count() &&
            !$product->shop_filters->count() &&
            !$product->order_items->count() &&
            !$product->promos->count() &&
            !$product->shop_params->count()) {

                if ($product->childs->count()) $product->childs()->delete();
                if ($product->images->count()) $product->images()->delete();
                if ($product->product_attributes->count()) $product->product_attributes()->delete();
                if ($product->prices->count()) $product->prices()->delete();
            }
        }

    }


    private function removePrestaeduWithoutStock()
    {
        // Tables with: product_id
        // shop_products, product_product, images, shop_filters, product_attributes, order_items,
        // prices, promos, shop_params
        $removed_local_online = [];
        $removed_local = [];
        $shop = Shop::firstWhere('code', 'pcedu');
        if ($ws = MarketWS::getMarketWS($shop)) {
            $shop_products = $shop->shop_products()->whereStock(0)->get();
            //dd($shop_products);

            foreach ($shop_products as $shop_product) {
                if ($shop_product->isUpgradeable()) {
                    //dd('isUpgradeable', $shop_product);
                    $removed_local_online[] = $ws->removeProduct($shop_product->marketProductSku);
                    //$removed_local_online[] = $shop_product->product_id;

                    //dd('isUpgradeable', $removed_local_online, $shop_product);
                }
                else {
                    //dd('NO isUpgradeable', $shop_product);
                    $shop_product->deleteSecure();
                    $removed_local[] = $shop_product->product_id;
                }
            }
        }

        dd($removed_local_online, $removed_local);
    }


    public function compareSupplierProddducts()
    {
        $products = Product::select('products.ean')->whereSupplierId(16)->orWhere('supplier_id', 26)->orWhere('supplier_id', 24)
            ->orWhere('supplier_id', 25)->orWhere('supplier_id', 27)->groupBy('products.ean')->havingRaw('count(*) > 1')->get();

        $costs = [];
        foreach ($products as $product) {

            if ($product->ean != '000000000000000') {
                $products_ean = Product::select('products.*')
                    ->where(function (Builder $query) {

                        return $query->whereSupplierId(16)
                            ->orWhere('supplier_id', 26)
                            ->orWhere('supplier_id', 24)
                            ->orWhere('supplier_id', 25)
                            ->orWhere('supplier_id', 27);

                    })
                    ->where('ean', $product->ean)
                    ->get();

                if ($products_ean->count() > 1) {
                    foreach ($products_ean as $product_ean) {
                        $costs[$product->ean][] = [$product_ean->supplier->code, $product_ean->cost];
                    }

                }
                //dd($product->ean, $products_ean);
            }

        }

        dd($costs);

        dd($products);
    }


    public function order_items_products()
    {
        $order_items = OrderItem::
            leftJoin('products', 'products.id', '=', 'order_items.product_id')->leftJoin('suppliers', 'suppliers.id', '=', 'products.supplier_id')
                ->where('order_items.name', 'like', '%apple%')->where('suppliers.id', '=', 16)->get();

        dd($order_items);
    }


    public function cleanProductsWrongData()
    {
        try {
            $res = [];
            // EAN & PN EQUALS & LENGTH == 13
            $products = Product::whereNotNull('ean')->whereRaw('ean = pn')
                ->whereRaw('LENGTH(ean) = 13')
                ->get();

            $res['equals_pn_ean_13'] = [];
            $res['equals_pn_ean_13']['pn_null'] = [];
            $res['equals_pn_ean_13']['ean_null'] = [];
            foreach ($products as $product) {
                // is EAN
                if (is_numeric($product->ean)) {
                    $product->pn = null;
                    $product->save();
                    $res['equals_pn_ean_13']['pn_null'][] = [$products->id, $product->ean];
                }
                // is PN
                else {
                    $product->ean = null;
                    $product->save();
                    $res['equals_pn_ean_13']['ean_null'][] = [$products->id, $product->pn];
                }
            }


            // EAN & PN EQUALS
            $products = Product::whereNotNull('ean')->whereRaw('ean = pn')
                    ->get();

            $res['equals_pn_ean'] = [];
            $res['equals_pn_ean']['ean_add_0'] = [];
            $res['equals_pn_ean']['ean_null'] = [];
            foreach ($products as $product) {
                // is EAN
                if (is_numeric($product->ean) && strlen(trim($product->ean)) == 12) {
                    $product->ean = '0'.$product->ean;
                    $product->save();
                    $res['equals_pn_ean']['ean_add_0'][] = [$products->id, $product->ean];
                }
                // is PN
                else {
                    $product->ean = null;
                    $product->save();
                    $res['equals_pn_ean']['ean_null'][] = [$products->id, $product->pn];
                }
            }


            // LENGTH EAN == 25 -> EAN12 + EAN13 & NUMERIC
            $products = Product::whereNotNull('ean')->whereRaw('LENGTH(ean) = 25')
                    ->get();

            $res['ean_25'] = [];
            foreach ($products as $product) {
                if (is_numeric($product->ean)) {
                    $product->ean = '0'.mb_substr($product->ean, 0, 12);
                    $product->save();
                    $res['ean_25'][] = [$products->id, $product->ean];
                }
            }


            // LENGTH EAN == 12 & NUMERIC
            $products = Product::whereNotNull('ean')->whereRaw('LENGTH(ean) = 12')
                    ->get();

            $res['ean_12'] = [];
            foreach ($products as $product) {
                if (is_numeric($product->ean)) {
                    $product->ean = '0'.$product->ean;
                    $product->save();
                    $res['ean_12'][] = [$products->id, $product->ean];
                }
            }


            // EAN == 000000000000000
            $products = Product::whereEan('000000000000000')
                ->get();

            $res['ean_000000000000000'] = [];
            foreach ($products as $product) {
                $product->ean = null;
                $product->save();
                $res['ean_000000000000000'][] = [$products->id, $product->pn];
            }


            // Wrong EAN
            $products = Product::whereNull('pn')->whereNotNull('ean')->whereRaw('LENGTH(ean) <> 13')
                ->get();

            $res['wrong_ean'] = [];
            foreach ($products as $product) {
                $res['wrong_ean'][] = [$products->id, $product->ean];
            }


            // PN is EAN 13
            $products = Product::whereNull('ean')->whereNotNull('pn')->whereRaw('LENGTH(pn) = 13')
                ->get();

            $res['pn_is_ean_13'] = [];
            foreach ($products as $product) {
                if (is_numeric($product->pn)) {
                    $res['pn_is_ean_13'][] = $product->pn;
                }
            }


            // PN is EAN 12
            $products = Product::whereNull('ean')->whereNotNull('pn')->whereRaw('LENGTH(pn) = 12')
                ->get();

            $res['pn_is_ean_12'] = [];
            foreach ($products as $product) {
                if (is_numeric($product->pn)) {
                    $res['pn_is_ean_12'][] = $product->pn;
                }
            }



            $this->nullAndStorage(__METHOD__, $res);

        } catch (Throwable $th) {
            dd($th, $res);
            return $this->nullWithErrors($th, __METHOD__, $res);
        }

    }


    public function compareSupplierProducts($supplier_1_id, $supplier_2_id)
    {
        $res = [];
        $products = Product::whereSupplierId($supplier_1_id)->get();
        foreach ($products as $product) {
            if ($d_product = Product::whereSupplierId($supplier_2_id)->whereEan($product->ean)->first())
                if ($d_product->cost > $product->cost)
                    $res[] = [$d_product->id, $d_product->cost, $product->cost];
        }

        dd($res);
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


    public function getMPPrice($cost, $client_fee, $mps_fee, $mp_fee, $mp_fee_addon, $iva = 21)
    {
        //$mp_fee += $mps_fee;
        $price = round(
            (1 + $iva/100) * ($cost + $mp_fee_addon) /
            (1 - $client_fee/100 - $mp_fee/100 - $mp_fee/100 * $iva/100 - $mps_fee/100 - $mps_fee/100 * $iva/100),
            2
        );

        return $price;
    }

    public function getMpsBfit($price, $mps_fee)
    {
        return round($mps_fee/100 * $price, 2);
    }


    public function copyShopFilters($source, $target)
    {
        $new_shop = Shop::find($target);
        $new_shop->shop_filters()->delete();

        $shop = Shop::find($source);
        foreach ($shop->shop_filters as $shop_filter) {
            ShopFilter::updateOrCreate([
                'shop_id'       => $new_shop->id,
                'category_id'   => $shop_filter->category_id,
                'supplier_id'   => $shop_filter->supplier_id,
                'cost_min'      => $shop_filter->cost_min,
                'stock_min'     => $shop_filter->stock_min,
            ],[

            ]);
        }
    }


    public function getAttributesAndProperties($market_id, $marketCategoryId, $market_attribute_name, $property_value)
    {
        // market_id: 2                                 Aliexpress
        // $marketCategoryId: 702                       Ordenadores portatiles
        // $market_attribute_name: Screen Size
        // property_value: 200005075
        try {
            $market_category = MarketCategory::whereMarketId($market_id)->where('marketCategoryId', $marketCategoryId)->first();
            $market_attribute = MarketAttribute::whereMarketCategoryId($market_category->id)->whereName($market_attribute_name)->first();
            $properties = Property::whereMarketAttributeId($market_attribute->id)->get();
            $property_values = [];
            foreach ($properties as $property) {
                $property_values[] = PropertyValue::whereValue($property_value)->wherePropertyId($property->id)->first();
            }

            dd($property_values, $properties, $market_attribute, $market_category);

        } catch (Throwable $th) {
            dd($th, $market_id, $marketCategoryId, $market_attribute_name);
        }
    }


    public function shopFiltersClone($source_shop_id, $target_shop_id, $supplier_id)
    {
        try {
            $res = [];
            $shop_filters = ShopFilter::whereShopId($source_shop_id)->whereSupplierId($supplier_id)->get();
            foreach ($shop_filters as $shop_filter) {
                $shop_filter_new = $shop_filter->replicate();
                $shop_filter_new->shop_id = $target_shop_id;
                $shop_filter_new->save();
                $res[] = $shop_filter_new;
            }

            return $res;

        } catch (Throwable $th) {
            dd($th);
        }
    }

    public function shopParamsClone($source_shop_id, $target_shop_id, $supplier_id)
    {
        try {
            $res = [];
            $shop_params = ShopParam::whereShopId($source_shop_id)->whereSupplierId($supplier_id)->get();
            foreach ($shop_params as $shop_param) {
                $shop_param_new = $shop_param->replicate();
                $shop_param_new->shop_id = $target_shop_id;
                $shop_param_new->save();
                $res[] = $shop_param_new;
            }

            return $res;

        } catch (Throwable $th) {
            dd($th);
        }
    }


    private function cleanShopProducts()
    {
        // REMOVE "NO PRODUCT" && STOCK = 0
        // REMOVE NULL && STOCK = 0

        $count = 0;
        $shops = Shop::whereEnabled(1)->get();
        foreach ($shops as $shop) {

            $shop_products = $shop->shop_products()->whereNull('marketProductSku')->whereStock(0)->get();
            foreach ($shop_products as $shop_product) {
                $shop_product->deleteSecure();

                $count++;
            }

            $shop_products = $shop->shop_products()->where('marketProductSku', 'NO PRODUCT')->whereStock(0)->get();
            foreach ($shop_products as $shop_product) {
                $shop_product->deleteSecure();

                $count++;
            }

            /* $shop_products = $shop->shop_products()->where('marketProductSku', 'NO AUTH')->whereStock(0)->get();
            foreach ($shop_products as $shop_product) {
                $shop_product->deleteSecure();

                $count = 0;
            } */
        }
        dd($count);
    }


    private function getRepricedShopproducts()
    {
        $shop_products_200 = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('stock', '>', 0)
            ->where('cost', '<=', 199.99)->where('price', '>', 0)->count();
        $shop_products_200_ = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->whereRepriced(1)
            ->where('stock', '>', 0)->where('cost', '<=', 199.99)->where('price', '>', 0)->count();

        $shop_products_400 = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('stock', '>', 0)
            ->where('cost', '<=', 399.99)->where('cost', '>=', 200)->where('price', '>', 0)->count();
        $shop_products_400_ = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->whereRepriced(1)
            ->where('stock', '>', 0)->where('cost', '<=', 399.99)->where('cost', '>=', 200)->where('price', '>', 0)->count();

        $shop_products_600 = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('stock', '>', 0)
            ->where('cost', '<=', 599.99)->where('cost', '>=', 400)->where('price', '>', 0)->count();
        $shop_products_600_ = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->whereRepriced(1)
            ->where('stock', '>', 0)->where('cost', '<=', 599.99)->where('cost', '>=', 400)->where('price', '>', 0)->count();

        $shop_products_800 = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('stock', '>', 0)
            ->where('cost', '<=', 799.99)->where('cost', '>=', 600)->where('price', '>', 0)->count();
        $shop_products_800_ = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->whereRepriced(1)
            ->where('stock', '>', 0)->where('cost', '<=', 799.99)->where('cost', '>=', 600)->where('price', '>', 0)->count();

        $shop_products_1000 = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('stock', '>', 0)
            ->where('cost', '<=', 1000)->where('cost', '>=', 800)->where('price', '>', 0)->count();
        $shop_products_1000_ = ShopProduct::whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->whereRepriced(1)
            ->where('stock', '>', 0)->where('cost', '<=', 1000)->where('cost', '>=', 800)->where('price', '>', 0)->count();

        $shop_products_amazon = ShopProduct::where('shop_products.shop_id', 20)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_pccompo = ShopProduct::where('shop_products.shop_id', 16)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_worten = ShopProduct::where('shop_products.shop_id', 13)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_carrefour = ShopProduct::where('shop_products.shop_id', 15)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();


        $shop_products_aseuropa = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 37)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_depau = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 22)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_desyman = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 27)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_dmi = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 36)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_esprinet = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 30)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_globo = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 24)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_blanes = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 1)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_iingram = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 8)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_itech = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 11)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_ivinzeo = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 10)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_idesyman = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 14)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_iesprinet = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 13)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_mcr = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 16)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_megasur = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 23)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_sce = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 26)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_ingram = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 31)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        // NO ACTUALITZA ELS PREUS!!!
        $shop_products_tech = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 35)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        $shop_products_vinzeo = ShopProduct::leftJoin('products', 'products.id', '=', 'shop_products.product_id')->where('products.supplier_id', 29)
            ->whereRepriced(1)->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)->where('shop_products.stock', '>', 0)->count();

        dd($shop_products_200_/$shop_products_200, $shop_products_200_,
            $shop_products_400_/$shop_products_400, $shop_products_400_,
            $shop_products_600_/$shop_products_600, $shop_products_600_,
            $shop_products_800_/$shop_products_800, $shop_products_800_,
            $shop_products_1000_/$shop_products_1000, $shop_products_1000_,
            'AMAZON', $shop_products_amazon,
            'PCCOMPO', $shop_products_pccompo,
            'WORTEN', $shop_products_worten,
            'CARREFOUR', $shop_products_carrefour,
            '----------',
            'ASEUROPA', $shop_products_aseuropa,
            'DEPAU', $shop_products_depau,
            'DESYMAN', $shop_products_desyman,
            'DMI', $shop_products_dmi,
            'ESPRINET', $shop_products_esprinet,
            'GLOBO', $shop_products_globo,
            'BLANES', $shop_products_blanes,
            'IINGRAM', $shop_products_iingram,
            'ITECH', $shop_products_itech,
            'IVINZEO', $shop_products_ivinzeo,
            'IDESYMAN', $shop_products_idesyman,
            'IESPRIENT', $shop_products_iesprinet,
            'MCR', $shop_products_mcr,
            'MEGASUR', $shop_products_megasur,
            'SCE', $shop_products_sce,
            'INGRAM', $shop_products_ingram,
            //'TECH', $shop_products_tech,
            'VINZEO', $shop_products_vinzeo
        );

    }


    private function getDirSizeRecursive($dir)
    {
        try {
            $res = [];
            $file_size = 0;
            foreach( File::files($dir) as $file)
            {
                //$res['files'][] = $file->getFilename();
                $file_size += $file->getSize();
            }

            $res['size'] = number_format($file_size / 1048576, 2);

            $subdirs = File::directories($dir);
            //dd($dir, $subdirs);

            /* if ($dir == '../storage\app\imports\MAYORISTAS DISTRIBUIDORES FABRICANTES\actividades')
                dd($dir, $subdirs, $res); */

            if (count($subdirs)) {
                foreach ($subdirs as $subdir) {
                    if ($subdir != '../storage\app\public\img')
                        if ($res_subdir = $this->getDirSizeRecursive($subdir))
                            $res[$subdir] = $res_subdir;
                }

                /* if ($dir == '../storage\app\imports\MAYORISTAS DISTRIBUIDORES FABRICANTES\actividades')
                    dd($dir, $subdirs, $res); */
            }
           /*  elseif (floatval($res['size']) < 10) {
                $res = null;
            } */

            if (count($res) == 1 && floatval($res['size']) < 10)
                $res = null;

            return $res;

        } catch (Throwable $th) {
            dd($th);
        }
    }


    public function index()
    {
        try {
            $res = [];
            dd('Hello');

            $products = Product::select('products.*')
                    ->leftJoin('provider_sheets', 'provider_sheets.ean', '=', 'products.ean')
                    ->whereNull('provider_sheets.ean')
                    ->whereIn('products.supplier_id', [1])      //,8,10,11,13,14,38])     //  16,22,23,24,26,27,28,29])
                    ->whereNotNull('products.ean')
                    ->whereNull('products.provider_id')
                    ->where('products.stock', '>', 0)

                    ->where('products.pn', 'B1400CEAE-EK2167R')

                    ->orderBy('products.id', 'desc')
                    ->take(1000)
                    ->get();

            dd($products);


            // remove all shop products
            $shop_products = ShopProduct::all();
            foreach ($shop_products as $shop_product) {
                $shop_product->deleteSecure();
            }

            // remove all products NO Idiomund
            $products = Product::whereNotIn('supplier_id', [1,2,4,5,7,8,10,11,13,14,25,34,38])->get();
            foreach ($products as $product) {
                $product->deleteSecure();
            }

            dd(ShopProduct::count(), Product::count());


            /* $shop_products = ShopProduct::whereShopId(29)->get();
            dd($shop_products);

            foreach ($shop_products as $shop_product) {
                $shop_product->deleteSecure();
            }

            $products = Product::whereIn('supplier_id', [6, 7, 18, 21, 28, 35, 39, 41])->get();

            foreach ($products as $product) {
                if (!$product->deleteSecure()) {
                    $res[$product->supplier_id][] = [
                            $product->id,
                            $product->shop_products->count(),
                            $product->order_items->count(),
                            $product->shop_filters->count(),
                            $product->shop_params->count(),
                            $product->lasteds_shop_products->count()
                    ];
                }
            }

            dd($res, $products);
 */
        } catch (Throwable $th) {
            dd($th, $product ?? null);
        }
    }





}
