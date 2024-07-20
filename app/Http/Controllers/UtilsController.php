<?php

namespace App\Http\Controllers;

use App\Facades\MpeImport;
use App\Market;
use App\OrderItem;
use App\Product;
use App\Shop;
use App\ShopFilter;
use App\ShopProduct;
use App\Status;
use App\Supplier;
use Facades\App\Facades\Mpe as FacadesMpe;
use Facades\App\Facades\Vox66Api as FacadesVox66Api;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;


class UtilsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $imports = MpeImport::getImportsLibraries();
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();

        return view('utils.index', compact('imports', 'shops'));
    }


    /*** MATCH EANS & IMAGES FILE ***/


    public function matchEans()
    {
        $count = 0;
        $eans = [];
        $products = Product::where('stock', '>', 0)->whereNull('ean')
            ->where('created_at', '>', now()->addDays(-15)->format('Y-m-d H:i:s'))
            ->get();

        foreach ($products as $product) {

            if (isset($product->pn) && isset($product->brand_id)) {
                $ean_product = Product::wherePn($product->pn)->whereBrandId($product->brand_id)->whereNotNull('ean')->first();
                if (isset($ean_product)) {

                    $product->ean = $ean_product->ean;
                    $product->save();

                    $eans[] = $ean_product->ean;
                    $count++;
                }
            }
        }


        return ([$count, $eans]);
    }


    public function matchImages()
    {
        // REMOVE LAST PRODUCT IMAGES
        /* $products_to_remove_images = Product::where('id', '>=', 32828)->get();
        foreach ($products_to_remove_images as $product_to_remove_images) {
            $product_to_remove_images->deleteAllImages();
        }


        $products_to_remove_images = Product::where('id', '<', 32828)->whereHas('images')->get();
        foreach ($products_to_remove_images as $product_to_remove_images) {
            $product_to_remove_images->deleteAllImages();
        }

       */

        $res = [];
        $products_without_images = Product::where('stock', '>', 0)->doesntHave('images')
            ->where('created_at', '>', now()->addDays(-15)->format('Y-m-d H:i:s'))
            ->get();


        foreach ($products_without_images as $product_without_images) {

            $product_with_images = $product_without_images->getMPESimilarProductWithImages();


            /* $product_with_images = Product::whereHas('images')
                ->where(function ($query) use ($product_without_images) {
                    $query->where(function ($q) use ($product_without_images) {
                        $q->whereNotNull('pn');
                        $q->where('pn', $product_without_images->pn);
                        $q->where('brand_id', $product_without_images->brand_id);
                    });
                    $query->orWhere(function ($q) use ($product_without_images) {
                        $q->whereNotNull('ean');
                        $q->where('ean', $product_without_images->ean);
                    });
                })
                ->first(); */

            if (isset($product_with_images)) {
                $res[] = [
                    $product_without_images->copyImages($product_with_images),
                    $product_without_images->id,
                    $product_with_images->id,
                ];
            }
        }


        return $res;
    }



    /*** GENERATE FILE ***/


    public function selectFileType()
    {
        $processTypes = [
            'aliexpress_scrape',
            'pns_all_scrape',
            'pns_ean_scrape',
            'worten_img_scrape',
            'pccompo_img_scrape',
        ];

        return view('utils.generatefile', compact('processTypes'));
    }


    public function generateFile(Request $request)
    {
        if (!$process_Type = $request->input('process_type'))
            return redirect()->back()->withErrors('Falta escoger el tipo de fichero.');

        $res = 'NO se ha podido generar el fichero.';
        try {
            if ($process_Type == 'aliexpress_scrape')
                $res = $this->generateFileAliexpresscrape();
            elseif ($process_Type == 'pns_all_scrape')
                $res = $this->generateFilePNxALLScrape();
            elseif ($process_Type == 'pns_ean_scrape')
                $res = $this->generateFilePNxEANScrape();
            elseif ($process_Type == 'worten_img_scrape')
                $res = $this->generateFileWortenIMGScrape();
            elseif ($process_Type == 'pccompo_img_scrape')
                $res = $this->generateFilePCCompoIMGScrape();
        }
        catch (Throwable $th) {
            dd($request, $th);
            //Storage::append('error/storeImageUrl_' .date('y-m-d_H'). '.json', json_encode($e));
            return ($res. ' '.$th->getMessage());
        }

        return redirect()->route('utils')->with('status', $res);
    }


    private function generateFileAliexpresscrape()
    {
        $fileName = 'ERROR';

        try {

            $shop_products = ShopProduct::whereShopId(1)
                // Test Local
                //->where('cost', '>=', 100)
                //->where('cost', '<=', 105)

                //->where('stock', '>=', 20)
                //->where('price', '>=', 100)
                //->where('price', '<=', 150)
                ->where('stock', '>=', 0)
                ->whereNotNull('marketProductSku')
                ->get();

            $words = [];
            foreach ($shop_products as $shop_product) {
                $words[] = [$shop_product->product->pn, $shop_product->product->brand->name];   //, $shop_product->price];
                //dd($shop_product, $words);
            }

            $fileName = 'utils/words_ali.json';
            Storage::put($fileName, json_encode($words));

            return ($fileName. ' - /pupp node words_ali.js - '. strval($shop_products->count()));
        }
        catch (Throwable $th) {
            dd($th);
            return null;
        }
    }


    private function generateFilePNxALLScrape()
    {
        $pns = [];
        $fileName = 'ERROR';

        try {
            $count_files = 0;
            $products = Product::where('stock', '>', 0)->whereNotNull('pn')
                ->where('created_at', '>', now()->addDays(-10)->format('Y-m-d'))
                //->where('created_at', '>=', now()->addDays(-15)->format('Y-m-d H:i:s'))
                //->where('created_at', '<', now()->addDays(-2)->format('Y-m-d H:i:s'))
                //->where('cost', '>', 70)
                ->where('category_id', '<>', 5247)  // Sistemas Operativos
                ->where('category_id', '<>', 5255)  // Software de diseño gráfico
                ->where('category_id', '<>', 5264)  // Software de seguridad y antivirus
                ->where('category_id', '<>', 5244)  // Software informático
                ->where(function ($query) {
                    $query->whereNull('ean');
                    $query->orDoesntHave('images');
                    })
                ->get();

            foreach ($products as $product) {

                if (isset($product->ean)) $pns[$product->ean] = $product->ean;
                else $pns[$product->pn] = $product->pn;

                if (count($pns) > 300) {
                    $fileName = 'utils/pns_all'.$count_files.'.json';
                    Storage::put($fileName, json_encode(array_values($pns)));

                    $pns = [];
                    $count_files++;
                }
            }

            if (count($pns) > 0) {
                $fileName = 'utils/pns_all'.$count_files.'.json';
                Storage::put($fileName, json_encode(array_values($pns)));
            }

            $total = intval(count($pns))+($count_files*300);
            return ($fileName. ' - /pupp node XXXXX_all.js - '. strval($total));
        }
        catch (Throwable $th) {
            dd($th);
        }

        return null;
    }


    private function generateFilePNxEANScrape()
    {
        $pns = [];
        $fileName = 'ERROR';

        try {
            $count_files = 0;
            $products = Product::where('stock', '>', 100)->whereNull('ean')->whereNotNull('pn')
                ->where('created_at', '<', now()->addDays(-5)->format('Y-m-d H:i:s'))
                //->where('created_at', '>=', now()->addDays(-15)->format('Y-m-d H:i:s'))
                ->where('cost', '>', 100)
                ->where('category_id', '<>', 5247)  // Sistemas Operativos
                ->where('category_id', '<>', 5255)  // Software de diseño gráfico
                ->where('category_id', '<>', 5264)  // Software de seguridad y antivirus
                ->where('category_id', '<>', 5244)  // Software informático
                ->get();

            foreach ($products as $product) {

                //dd($product, $products->count(), $products);

                $pns[$product->pn] = $product->pn;
                if (count($pns) > 300) {
                    $fileName = 'utils/pns_ean'.$count_files.'.json';
                    Storage::put($fileName, json_encode(array_values($pns)));

                    $pns = [];
                    $count_files++;
                }
            }

            if (count($pns) > 0) {
                $fileName = 'utils/pns_ean'.$count_files.'.json';
                Storage::put($fileName, json_encode(array_values($pns)));
            }

            $total = intval(count($pns))+($count_files*300);
            return ($fileName. ' - /pupp node XXX_ean.js - '. strval($total));
        }
        catch (Throwable $th) {
            dd($th);
        }

        return null;
    }


    private function generateFileWortenIMGScrape()
    {
        $eans = [];
        $fileName = 'ERROR';
        $worten = Market::whereCode('worten')->first();
        if (isset($worten)) {
            $shopProducts = ShopProduct::whereMarketId($worten->id)
                ->whereNotNull('marketProductSku')
                ->where('marketProductSku', '<>', 'ERROR')
                ->where('marketProductSku', '<>', 'NO BRAND')
                ->where('marketProductSku', '<>', 'NO PRODUCT')
                ->where('marketProductSku', '<>', 'ORDER')
                ->where('marketProductSku', '<>', '')
                ->where('is_sku_child', false)
                ->where('created_at', '>', now()->addDays(-5)->format('Y-m-d H:i:s'))
                ->get();


            foreach ($shopProducts as $shopProduct) {
                if (!$shopProduct->product->images()->count() && isset($shopProduct->product->ean)) {
                    $eans[] = $shopProduct->product->ean;
                }
            }

            $fileName = 'utils/eans_4_worten_img_scrape.json';
            Storage::put($fileName, json_encode($eans));
        }

        return ($fileName. ' - /pupp node worten_img.js - '. count($eans));
    }



    private function generateFilePCCompoIMGScrape()
    {
        $eans = [];
        $fileName = 'ERROR';

        $count_files = 2;
        $products = Product::where('stock', '>', 0)
            ->where('created_at', '>', now()->addDays(-5)->format('Y-m-d H:i:s'))
            ->get();

        foreach ($products as $product) {
            if (!$product->images()->count() && isset($product->ean)) {
                $eans[$product->ean] = $product->ean;
            }

            if (count($eans) > 300) {
                $fileName = 'utils/eans_4_pccompo_img_scrape'.$count_files.'.json';
                Storage::put($fileName, json_encode(array_values($eans)));

                $eans = [];
                $count_files++;
            }
        }

        if (count($eans) > 0) {
            $fileName = 'utils/eans_4_pccompo_img_scrape'.$count_files.'.json';
            Storage::put($fileName, json_encode(array_values($eans)));
        }

        $total = intval(count($eans))+($count_files*1000);
        return ($fileName. ' - /pupp node pccompo.js - '. strval($total));
    }






    /*** PROCESS FILE ***/


    public function getFile()
    {
        $processTypes = [
            'aliexpress_scrape',
            'centralpoint_all_scrape',
            'pccompo_all_scrape',
            'Icecat_ean_img_scrape',
            'worten_img_scrape',
            'pccompo_img_scrape',
        ];

        return view('utils.getfile', compact('processTypes'));
    }


    public function processFile(Request $request)
    {
        if (!$process_Type = $request->input('process_type'))
            return redirect()->back()->withErrors('Falta escoger el tipo de proceso.');

        $res = 'NO se ha podido procesar el fichero.';
        try {
            //dd($request);
            if ($fileinputs = $request->file('fileinput')) {
                //dd($fileinputs);

                $fileinput = $fileinputs[0];
                //$fileExtension = $fileinput->getClientOriginalExtension();
                //$directory = ('public/img/' . $this->id . '/');
                //dd($fileinput);
                // $contents = Storage::get('mp/google/vox66/' .$ean. '_g.json');
                $contents = file_get_contents($fileinput->getRealPath());
                $json_contents = json_decode($contents);
                //dd($json_contents);

                if ($process_Type == 'aliexpress_scrape')
                    return $this->processFileAliexpressScrape($json_contents);
                elseif ($process_Type == 'centralpoint_all_scrape')
                    return $this->processFileCentralpointALLScrape($json_contents);
                elseif ($process_Type == 'pccompo_all_scrape')
                    return $this->processFilePCCompoALLScrape($json_contents);
                elseif ($process_Type == 'pccompo_ean_scrape')
                    $res = $this->processFilePCCompoEANScrape($json_contents);
                elseif ($process_Type == 'Icecat_ean_img_scrape')
                    $res = $this->processFileIcecatEANIMGScrape($json_contents);
                elseif ($process_Type == 'worten_img_scrape')
                    $res = $this->processFileWortenScrape($json_contents);
                elseif ($process_Type == 'pccompo_img_scrape')
                    $res = $this->processFilePCCompoScrape($json_contents);

            }
        }
        catch (Throwable $th) {
            dd($request, $th);
            //Storage::append('error/storeImageUrl_' .date('y-m-d_H'). '.json', json_encode($e));
            return ($res. ' '.$th->getMessage());
        }

        return redirect()->route('utils')->with('status', $res);
    }


    private function setNewPrice(array &$res)
    {
        $repriceMinimumFee = 3;         // %
        $repriceSubtractAmount = 0.10;

        foreach ($res as $key_pn => $res_pn) {
            foreach ($res_pn as $key_brand => $res_brand) {
                foreach ($res_brand as $key_item => $item) {

                    $price = str_replace(['€', ' '], '', $item[1]);
                    $price = str_replace(',', '.', $price);
                    $shop_product = $item[3];

                    $newPrice = (float)$price - $repriceSubtractAmount;
                    $bfits = FacadesMpe::getBfitsByPrice($shop_product->getCost(), $newPrice, $shop_product->param_mps_fee,
                        $shop_product->param_mp_fee, $shop_product->param_mp_fee_addon, $shop_product->tax);

                    // Minimum Fee 3%
                    if ($bfits['fee'] >= $repriceMinimumFee && $bfits['bfit'] >= $shop_product->param_bfit_min) {
                        $res[$key_pn][$key_brand][$key_item]['NEW_PRICEEEEEE'] = $newPrice;
                        $res[$key_pn][$key_brand][$key_item]['fee'] = $bfits['fee'];
                        $res[$key_pn][$key_brand][$key_item]['bfid'] = $bfits['bfit'];
                    }
                    else {
                        unset($res[$key_pn][$key_brand][$key_item]);
                        if (empty($res[$key_pn][$key_brand])) unset($res[$key_pn][$key_brand]);
                        if (empty($res[$key_pn])) unset($res[$key_pn]);
                        //$res[$key_pn][$key_brand][$key_item]['NO_PRICE'] = $newPrice;
                    }

                }
            }
        }
    }


    private function processFileAliexpressScrape($json_contents)
    {
        $count = [];
        $all_products = new Collection();
        $res = $res_2 = $res_3 = $res_4 = [];
        foreach($json_contents as $json_product) {
            $pn = $json_product[0];
            $brand = $json_product[1];
            $titles = $json_product[2];
            $prices = $json_product[3];
            $stores = $json_product[4];

            if (!empty($titles) && $titles != false &&
                !empty($prices) && $prices != false &&
                !empty($stores) && $stores != false) {

                $unsets = [];
                $count_store = 0;
                foreach ($stores as $store) {
                    if ($store == 'Marketplace e-Specialist Store')
                        $unsets[$count_store] = true;

                    $count_store++;
                }

                foreach ($unsets as $key => $value) {
                    unset($titles[$key]);
                    unset($prices[$key]);
                    unset($stores[$key]);
                }

                $shop_product = ShopProduct::whereShopId(1)
                    ->leftJoin('products', 'products.id', '=', 'shop_products.product_id')
                    ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                    ->where('products.pn', '=', $pn)
                    ->where('brands.name', '=', $brand)
                    ->first();

                if (isset($shop_product) && $shop_product->stock > 0) {

                    // TEST PRICES
                    $unsets = [];
                    foreach ($prices as $key => $price) {
                        $prices[$key] = str_replace(['€', ' '], '', $price);    // Delete symbols
                        $prices[$key] = str_replace('.', '', $prices[$key]);    // Delete thousands_sep
                        $prices[$key] = str_replace(',', '.', $prices[$key]);   // change ec_point , -> .
                        if (!is_numeric($prices[$key])) {
                            $old_price = $prices[$key];
                            $prices[$key] = substr($prices[$key], 0, strpos($prices[$key], '-'));   // 2.62-2.73, 499-599
                        }

                        if (!is_numeric($prices[$key])) {
                            dd('1', $prices[$key], $old_price ?? null);
                            $unsets[$key] = true;
                        }
                        else {
                            $prices[$key] = floatval($prices[$key]);
                            if ($shop_product->price < $prices[$key] || $shop_product->price * 0.90 > $prices[$key]) {
                                $unsets[$key] = true;
                            }
                        }
                    }

                    foreach ($unsets as $key => $value) {
                        unset($titles[$key]);
                        unset($prices[$key]);
                        unset($stores[$key]);
                    }

                    // TEST TITLES
                    $unsets = [];
                    foreach ($titles as $key_title => $title) {
                        if (strpos(strtoupper($title), strtoupper($brand))) {

                            if (!isset($stores[$key_title])) dd($key_title, $titles, $prices, $stores);

                            if (strpos(strtoupper($title), strtoupper($pn)))
                                $res[$pn][$brand][] = [$title, $prices[$key_title], $stores[$key_title], $shop_product, $shop_product->product->name, $shop_product->price];
                            else {
                                $pn_replace = str_replace(['-', '(', ')'], ' ', $pn);
                                $pn_parts = explode(' ', trim($pn_replace));
                                $count_pn_parts = 0;
                                foreach ($pn_parts as $pn_part) {
                                    if (empty($pn_part)) dd($pn, $pn_replace, $pn_parts, $pn_part);
                                    elseif (strpos(strtoupper($title), strtoupper($pn_part)))
                                        $count_pn_parts++;
                                }

                                if ($count_pn_parts == count($pn_parts)) {
                                    $res[$pn][$brand][] = [$title, $prices[$key_title], $stores[$key_title], $shop_product, $shop_product->product->name, $shop_product->price];
                                }
                                else {
                                    $percent = null;
                                    similar_text(strtoupper($title), $shop_product->product->name, $percent);
                                    if (!is_numeric($prices[$key_title])) {
                                        dd('2', $prices[$key_title]);
                                    }
                                    if ($percent > 40)
                                        $res_2[$pn][$brand][] = [$title, $prices[$key_title], $stores[$key_title], $shop_product, $shop_product->product->name, $shop_product->price, $percent];
                                    elseif ($prices[$key_title] / $shop_product->price > 0.90 && $prices[$key_title] / $shop_product->price < 1.10)
                                        $res_3[$pn][$brand][] = [$title, $prices[$key_title], $stores[$key_title], $shop_product, $shop_product->product->name, $shop_product->price, $percent];
                                    else
                                        $res_4[$pn][$brand][] = [$title, $prices[$key_title], $stores[$key_title], $shop_product, $shop_product->product->name, $shop_product->price, $percent];
                                }
                            }
                        }
                    }
                }

            }
        }

        $this->setNewPrice($res);
        $this->setNewPrice($res_2);
        $this->setNewPrice($res_3);

        dd($res, $res_2, $res_3, $res_4);

        return 'EANs insertados: '.$count;
    }


    private function processFileCentralpointALLScrape($json_contents)
    {
        /* [
            "STKC4000400",
            "Seagate One Touch Externe harde schijf",
            "Productcode: STKC4000400  |  EAN/UPC: 3660619409730",
            [
              "https://www02.cp-static.com/objects/high_pic/3/308/1351643833_externe-harde-schijven-seagate-stkc4000400.jpg",
              "https://www02.cp-static.com/objects/multimedia/7/733/1351643833_1893423113_externe-harde-schijven-seagate-one-touch-stkc4000400.jpg"
            ]
        ] */

        $count = 0;
        $all_products = new Collection();
        $res = $no_13 = $img = $img_ean = $no_brand = $no_product = [];
        foreach($json_contents as $json_product) {
            $pn_or_ean = $json_product[0];
            $title = $json_product[1];
            $codes = $json_product[2];
            $images = $json_product[3];

            /* if ($title != false)
                dd($pn_or_ean, $title, $codes, $images, $json_product); */

            //dd($pn_or_ean, $title, $codes, $images);

            if (!empty($title) && $title != false && !empty($codes) && $codes != false) {

                // Search PN
                $startpnpos = strpos($codes, 'Productcode:');
                $endpnpos = strpos($codes, '|', $startpnpos + 13);
                $pn_extracted = substr($codes, $startpnpos + 13, $endpnpos - $startpnpos - 16);
                $starteanpos = strpos($codes, 'EAN');
                $ean_extracted = substr($codes, $starteanpos + 9, strlen($codes) - $starteanpos);

                //dd($json_product, $codes, $pn_or_ean, $pn_extracted, $endpnpos, $startpnpos, $starteanpos, $ean_extracted);

                // Get EAN
                if (strlen($ean_extracted) == 12) $ean_extracted = '0'.$ean_extracted;
                if (in_array(strtoupper($pn_or_ean), [strtoupper($pn_extracted), strtoupper($ean_extracted)])) {
                    // Get BRAND
                    $startbrandpos = 0;
                    $endbrandpos = strpos($title, ' ');
                    $brand_extracted = substr($title, $startbrandpos, $endbrandpos);

                    if (strtoupper($pn_or_ean) == strtoupper($pn_extracted))
                        $products = Product::wherePn($pn_or_ean)->get();
                    else
                        $products = Product::whereEan($pn_or_ean)->get();

                    if ($products->count()) {
                        foreach($products as $product) {

                            $all_products->push($product);

                            // SAME PN && BRAND
                            if (
                                (strtoupper($pn_or_ean) == strtoupper($pn_extracted)) &&
                                (
                                strtoupper($product->brand->name) == strtoupper($brand_extracted) ||
                                (strtoupper($product->brand->name) == 'UNYKA' && strtoupper($brand_extracted) == 'UNYKACH') ||
                                (strtoupper($product->brand->name) == 'AVM COMPUTER SYSTEMS' && strtoupper($brand_extracted) == 'FRITZ') ||
                                (strtoupper($product->brand->name) == 'TOSHIBA' && strtoupper($brand_extracted) == 'DYNABOOK') ||
                                (strtoupper($product->brand->name) == 'KEEPOUT' && strtoupper($brand_extracted) == 'KEEP OUT') ||
                                (strtoupper($product->brand->name) == 'CISCO' && strtoupper($brand_extracted) == 'MERAKI GO') ||
                                (strtoupper($product->brand->name) == 'GOOGLE WIFI' && strtoupper($brand_extracted) == 'GOOGLE') ||
                                (strtoupper($product->brand->name) == 'SEAGATE CONSUMER' && strtoupper($brand_extracted) == 'SEAGATE') ||
                                (strtoupper($product->brand->name) == 'ACCO/KENSINGTON' && strtoupper($brand_extracted) == 'KENSINGTON')||
                                (strtoupper($product->brand->name) == 'KINGSTON' && strtoupper($brand_extracted) == 'HYPERX') ||
                                (substr(strtoupper($product->brand->name), 0, 4) == 'DELL' && substr(strtoupper($brand_extracted), 0, 4) == 'DELL') ||
                                (substr(strtoupper($product->brand->name), 0, 2) == 'HP' && substr(strtoupper($brand_extracted), 0, 2) == 'HP') ||
                                (substr(strtoupper($product->brand->name), 0, 2) == 'HP' && strtoupper($brand_extracted) == 'HEWLETT') ||
                                (substr(strtoupper($product->brand->name), 0, 7) == 'WESTERN' && strtoupper($brand_extracted) == 'WESTERN') ||
                                (substr(strtoupper($product->brand->name), 0, 4) == 'QNAP' && strtoupper($brand_extracted) == 'QNAP') ||
                                (substr(strtoupper($product->brand->name), 0, 4) == 'POLY' && strtoupper($brand_extracted) == 'POLY') ||
                                (substr(strtoupper($product->brand->name), 0, 8) == 'UBIQUITI' && strtoupper($brand_extracted) == 'UBIQUITI') ||
                                (substr(strtoupper($product->brand->name), 0, 7) == 'CRUCIAL' && strtoupper($brand_extracted) == 'CRUCIAL')
                                )
                            ) {

                                // INSERT EANS
                                if (!isset($product->ean)) {
                                    $res[] = [
                                        'pn'                => strtoupper($pn_or_ean),
                                        'pn_extracted'      => strtoupper($pn_extracted),
                                        'brand_extracted'   => strtoupper($brand_extracted),
                                        'brand'             => strtoupper($product->brand->name),
                                        'ean_extracted'     => $ean_extracted,
                                        'ean'               => $product->ean,
                                        'len_ean'           => strlen($ean_extracted),
                                        'product_id'        => $product->id,
                                    ];

                                    $product->ean = $ean_extracted;
                                    $product->save();
                                    $count++;
                                }


                                // INSERT IMAGES
                                if (!$product->images()->count() && !empty($images)) {
                                    $img[] = [
                                        'pn'                => strtoupper($pn_or_ean),
                                        'pn_extracted'      => strtoupper($pn_extracted),
                                        'brand_extracted'   => strtoupper($brand_extracted),
                                        'brand'             => strtoupper($product->brand->name),
                                        'imgs'              => $this->insertCentralpointImages($product, $images),
                                        'product_id'        => $product->id,
                                        'url'               => route('products.images', [$product]),
                                    ];
                                }
                            }
                            // INSERT IMAGES by EAN
                            elseif (isset($product->ean) && strtoupper($product->ean) == strtoupper($ean_extracted) &&
                                    !$product->images()->count() && !empty($images)) {

                                        $img_ean[] = [
                                            'pn'                => strtoupper($pn_or_ean),
                                            'pn_extracted'      => strtoupper($pn_extracted),
                                            'brand_extracted'   => strtoupper($brand_extracted),
                                            'brand'             => strtoupper($product->brand->name),
                                            'imgs'              => $this->insertCentralpointImages($product, $images),
                                            'product_id'        => $product->id,
                                            'url'               => route('products.images', [$product]),
                                        ];
                            }
                        }

                    }
                }
            }
        }

        Storage::append('utils/Centralpoint_all_' .date('y-m-d_H-i-s'). '_RES.json', json_encode($res));
        Storage::append('utils/Centralpoint_all_' .date('y-m-d_H-i-s'). '_IMG.json', json_encode($img));
        Storage::append('utils/Centralpoint_all_' .date('y-m-d_H-i-s'). '_NO_13.json', json_encode($no_13));
        Storage::append('utils/Centralpoint_all_' .date('y-m-d_H-i-s'). '_NO_BRAND.json', json_encode($no_brand));
        Storage::append('utils/Centralpoint_all_' .date('y-m-d_H-i-s'). '_NO_PRODUCT.json', json_encode($no_product));

        $statuses = Status::where('type', 'product')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $params = [];
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'products.created_at';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $products = $all_products;
        return view('product.index_images', compact('products', 'statuses', 'suppliers', 'params', 'order_params'));


        dd($no_13, $no_brand, $no_product, $res, $img, $img_ean);

        return 'EANs insertados: '.$count;
    }


    private function processFilePCCompoALLScrape($json_contents)
    {
        //dd('processFilePCCompoALLScrape', $json_contents);

        // \"ean\":\"8435490615236\",\"part_number\":\"KT-TK3170\"
        // \"brand\": \"Kyocera\",\n
        // data-brand=\"Kyocera\" data-category=\"Tintas\"
        $count = 0;
        $all_products = new Collection();
        $res = $no_13 = $img = $img_ean = $no_brand = $no_product = [];
        foreach($json_contents as $json_product) {
            $pn_or_ean = $json_product[0];
            $content = $json_product[1];
            $images = $json_product[2];

            //dd($pn_or_ean, $images);

            if (!empty($content)) {

                // Get PN
                $startpnpos = strpos($content, '"part_number"');
                $endpnpos = strpos($content, '"', $startpnpos + 15);
                $pn_extracted = substr($content, $startpnpos + 15, $endpnpos - $startpnpos - 15);

                // Get EAN
                $starteanpos = strpos($content, '"ean":');
                $endean = strpos($content, '"', $starteanpos + 7);
                $ean_extracted = substr($content, $starteanpos + 7, $endean - $starteanpos - 7);
                $ean_extracted = trim($ean_extracted);

                // Get EAN
                if (strlen($ean_extracted) == 12) $ean_extracted = '0'.$ean_extracted;
                if (in_array(strtoupper($pn_or_ean), [strtoupper($pn_extracted), strtoupper($ean_extracted)])) {

                    // Get BRAND
                    $startbrandpos = strpos($content, '"brand"');
                    $endbrandpos = strpos($content, '"', $startbrandpos + 10);
                    $brand_extracted = substr($content, $startbrandpos + 10, $endbrandpos - $startbrandpos - 10);

                    if (strtoupper($pn_or_ean) == strtoupper($pn_extracted))
                        $products = Product::wherePn($pn_or_ean)->get();
                    else
                        $products = Product::whereEan($pn_or_ean)->get();

                    //dd($pn_or_ean, $pn_extracted, $ean_extracted, $products);

                    if ($products->count()) {
                        foreach($products as $product) {

                            $all_products->push($product);

                            // SAME PN && BRAND
                            if (
                                (strtoupper($pn_or_ean) == strtoupper($pn_extracted)) &&
                                (
                                    strtoupper($product->brand->name) == strtoupper($brand_extracted) ||
                                    (strtoupper($product->brand->name) == 'UNYKA' && strtoupper($brand_extracted) == 'UNYKACH') ||
                                    (strtoupper($product->brand->name) == 'AVM COMPUTER SYSTEMS' && strtoupper($brand_extracted) == 'FRITZ') ||
                                    (strtoupper($product->brand->name) == 'TOSHIBA' && strtoupper($brand_extracted) == 'DYNABOOK TOSHIBA') ||
                                    (strtoupper($product->brand->name) == 'KEEPOUT' && strtoupper($brand_extracted) == 'KEEP OUT') ||
                                    (strtoupper($product->brand->name) == 'CISCO' && strtoupper($brand_extracted) == 'MERAKI GO') ||
                                    (strtoupper($product->brand->name) == 'GOOGLE WIFI' && strtoupper($brand_extracted) == 'GOOGLE') ||
                                    (strtoupper($product->brand->name) == 'HP ENT' && strtoupper($brand_extracted) == 'HPE') ||
                                    (strtoupper($product->brand->name) == 'SEAGATE CONSUMER' && strtoupper($brand_extracted) == 'SEAGATE') ||
                                    (strtoupper($product->brand->name) == 'ACCO/KENSINGTON' && strtoupper($brand_extracted) == 'KENSINGTON')||
                                    (strtoupper($product->brand->name) == 'HP ENT' && strtoupper($brand_extracted) == 'HPE ARUBA') ||
                                    (strtoupper($product->brand->name) == 'DELL TECHNOLOGIES' && strtoupper($brand_extracted) == 'DELL') ||
                                    (substr(strtoupper($product->brand->name), 0, 2) == 'HP' && substr(strtoupper($brand_extracted), 0, 2) == 'HP') ||
                                    (substr(strtoupper($product->brand->name), 0, 7) == 'CRUCIAL' && strtoupper($brand_extracted) == 'CRUCIAL')
                                )
                            ) {

                                // INSERT EANS
                                //if (strlen($ean_extracted) == 12) $ean_extracted = '0'.$ean_extracted;
                                if (!isset($product->ean)) {
                                    $res[] = [
                                        'pn'                => strtoupper($pn_or_ean),
                                        'pn_extracted'      => strtoupper($pn_extracted),
                                        'brand_extracted'   => strtoupper($brand_extracted),
                                        'brand'             => strtoupper($product->brand->name),
                                        'ean_extracted'     => $ean_extracted,
                                        'ean'               => $product->ean,
                                        'len_ean'           => strlen($ean_extracted),
                                        'product_id'        => $product->id,
                                    ];

                                    $product->ean = $ean_extracted;
                                    $product->save();
                                    $count++;
                                }

                                // INSERT IMAGES
                                if (!$product->images()->count() && !empty($images)) {
                                    $img[] = [
                                        'pn'                => strtoupper($pn_or_ean),
                                        'pn_extracted'      => strtoupper($pn_extracted),
                                        'brand_extracted'   => strtoupper($brand_extracted),
                                        'brand'             => strtoupper($product->brand->name),
                                        'imgs'              => $this->insertPCCompoImages($product, $images),
                                        'product_id'        => $product->id,
                                        'url'               => route('products.images', [$product]),
                                    ];
                                }
                            }
                            // INSERT IMAGES by EAN
                            elseif (isset($product->ean) && strtoupper($product->ean) == strtoupper($ean_extracted) &&
                                    !$product->images()->count() && !empty($images)) {

                                    $img_ean[] = [
                                        'pn'                => strtoupper($pn_or_ean),
                                        'pn_extracted'      => strtoupper($pn_extracted),
                                        'brand_extracted'   => strtoupper($brand_extracted),
                                        'brand'             => strtoupper($product->brand->name),
                                        'imgs'              => $this->insertPCCompoImages($product, $images),
                                        'product_id'        => $product->id,
                                        'url'               => route('products.images', [$product]),
                                    ];
                            }
                        }

                    }
                }
            }
        }

        Storage::append('utils/pccompo_all_' .date('y-m-d_H-i-s'). '_RES.json', json_encode($res));
        Storage::append('utils/pccompo_all_' .date('y-m-d_H-i-s'). '_IMG.json', json_encode($img));
        Storage::append('utils/pccompo_all_' .date('y-m-d_H-i-s'). '_NO_13.json', json_encode($no_13));
        Storage::append('utils/pccompo_all_' .date('y-m-d_H-i-s'). '_NO_BRAND.json', json_encode($no_brand));
        Storage::append('utils/pccompo_all_' .date('y-m-d_H-i-s'). '_NO_PRODUCT.json', json_encode($no_product));

        $statuses = Status::where('type', 'product')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $params = [];
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'products.created_at';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $products = $all_products;
        return view('product.index_images', compact('products', 'statuses', 'suppliers', 'params', 'order_params'));

        dd($no_13, $no_brand, $no_product, $res, $img, $img_ean);

        return 'EANs insertados: '.$count;
    }


    private function processFilePCCompoEANScrape($json_contents)
    {
        // \"ean\":\"8435490615236\",\"part_number\":\"KT-TK3170\"
        // \"brand\": \"Kyocera\",\n
        // data-brand=\"Kyocera\" data-category=\"Tintas\"
        $count = 0;
        $res = $no_res = [];
        foreach($json_contents as $json_product) {
            $pn = $json_product[0];
            $content = $json_product[1];
            //dd($ean, $images, $json_contents, $json_product);
            if (!empty($content)) {

                // Search PN
                $startpnpos = strpos($content, '"part_number"');
                $endpnpos = strpos($content, '"', $startpnpos + 15);
                $pn_extracted = substr($content, $startpnpos + 15, $endpnpos - $startpnpos - 15);

                // Get EAN
                if (strtoupper($pn) == strtoupper($pn_extracted)) {
                    // Get BRAND
                    $startbrandpos = strpos($content, '"brand"');
                    $endbrandpos = strpos($content, '"', $startbrandpos + 10);
                    $brand_extracted = substr($content, $startbrandpos + 10, $endbrandpos - $startbrandpos - 10);

                    // Get EAN
                    $starteanpos = strpos($content, '"ean":');
                    $endean = strpos($content, '"', $starteanpos + 7);
                    $ean_extracted = substr($content, $starteanpos + 7, $endean - $starteanpos - 7);

                    $products = Product::wherePn($pn)->whereNull('ean')->get();
                    if ($products->count()) {
                        foreach($products as $product) {
                            if (!isset($product->ean) &&
                                strlen($ean_extracted) == 13 &&
                                (
                                    strtoupper($product->brand->name) == strtoupper($brand_extracted) ||
                                    (strtoupper($product->brand->name) == 'TOSHIBA' && strtoupper($brand_extracted) == 'DYNABOOK TOSHIBA') ||
                                    (strtoupper($product->brand->name) == 'KEEPOUT' && strtoupper($brand_extracted) == 'KEEP OUT') ||
                                    (strtoupper($product->brand->name) == 'CISCO' && strtoupper($brand_extracted) == 'MERAKI GO') ||
                                    (strtoupper($product->brand->name) == 'GOOGLE WIFI' && strtoupper($brand_extracted) == 'GOOGLE') ||
                                    (strtoupper($product->brand->name) == 'HP ENT' && strtoupper($brand_extracted) == 'HPE') ||
                                    (strtoupper($product->brand->name) == 'SEAGATE CONSUMER' && strtoupper($brand_extracted) == 'SEAGATE') ||
                                    (strtoupper($product->brand->name) == 'ACCO/KENSINGTON' && strtoupper($brand_extracted) == 'KENSINGTON')||
                                    (strtoupper($product->brand->name) == 'HP ENT' && strtoupper($brand_extracted) == 'HPE ARUBA') ||
                                    (substr(strtoupper($product->brand->name), 0, 2) == 'HP' && strtoupper($brand_extracted) == 'HP') ||
                                    (substr(strtoupper($product->brand->name), 0, 7) == 'CRUCIAL' && strtoupper($brand_extracted) == 'CRUCIAL')
                                )) {

                                $res[] = [
                                    'pn'                => strtoupper($pn),
                                    'pn_extracted'      => strtoupper($pn_extracted),
                                    'brand_extracted'   => strtoupper($brand_extracted),
                                    'brand'             => strtoupper($product->brand->name),
                                    'ean_extracted'     => $ean_extracted,
                                    'ean'               => $product->ean,
                                    'len_ean'           => strlen($ean_extracted),
                                    'product'           => $product,
                                ];

                                $product->ean = $ean_extracted;
                                $product->save();
                                $count++;
                            }
                            else {
                                $no_res[] = [
                                    'pn'                => strtoupper($pn),
                                    'pn_extracted'      => strtoupper($pn_extracted),
                                    'brand_extracted'   => strtoupper($brand_extracted),
                                    'brand'             => strtoupper($product->brand->name),
                                    'ean_extracted'     => $ean_extracted,
                                    'ean'               => $product->ean,
                                    'len_ean'           => strlen($ean_extracted),
                                    'product'           => $product,
                                ];
                            }
                        }
                    }
                }
            }
        }

        Storage::append('utils/pccompo_eans_' .date('y-m-d_H-i-s'). '_RES.json', json_encode($res));
        Storage::append('utils/pccompo_eans_' .date('y-m-d_H-i-s'). '_NO_RES.json', json_encode($no_res));
        dd($res, $no_res);

        return 'EANs insertados: '.$count;
    }


    /* private function processFileEANSearchScrape($json_contents)
    {
        $count = [];
        foreach($json_contents as $json_product) {
            $pn = $json_product[0];
            $eans = $json_product[1];
            //dd($ean, $images, $json_contents, $json_product);
            if (!empty($eans) && count($eans) == 1) {
                $products = Product::wherePn($pn)->get();
                if ($products->count()) {
                    foreach($products as $product) {
                        if (!isset($product->ean)) {
                            $product->ean = $eans[0];
                            $product->save();
                            $count++;
                        }
                    }
                }
            }
        }

        return 'EANs insertados: '.$count;
    } */


    private function processFileWortenScrape($json_contents)
    {
        $imagesName = [];
        foreach($json_contents as $json_product) {
            $ean = $json_product[0];
            $images = $json_product[1];
            //dd($ean, $images, $json_contents, $json_product);
            if (!empty($images)) {
                $products = Product::whereNotNull('ean')->whereEan($ean)->get();
                if ($products->count()) {
                    foreach($products as $product) {
                        $product->deleteAllImages();
                        if (count($images) == 1)
                            $imagesName[$ean][] = $product->updateOrCreateExternalImage('https://www.worten.es'.$images[0]);
                        else {
                            // La 1a meitat del array d'imatges son de qualitat, la resta thumbails.
                            for ($i = 0; $i < intdiv(count($images), 2); $i++)
                                $imagesName[$ean][] = $product->updateOrCreateExternalImage('https://www.worten.es'.$images[$i]);
                        }
                    }
                }
            }

            //dd($json_product, $imagesName);
        }

        return 'Imágenes insertadas: '.json_encode($imagesName);
    }


    private function processFilePCCompoScrape($json_contents)
    {
        //dd('processFilePCCompoScrape', $json_contents);

        $imagesName = [];
        foreach($json_contents as $json_product) {
            $ean = $json_product[0];
            $images = $json_product[1];
            //dd($ean, $images, $json_contents, $json_product);
            if (!empty($images)) {
                $products = Product::whereNotNull('ean')->whereEan($ean)->get();
                //dd($products);
                if ($products->count()) {
                    foreach($products as $product) {
                        //  dd($product);
                        //$product->deleteAllImages();
                        //if (!$product->images()->count()) {

                            $imagesName[$ean][] = $this->insertPCCompoImages($product, $images);

                            //dd('processFilePCCompoScrape', $product, $imagesName, $images);

                            /* $insert_images = [];
                            foreach ($images as $image) {
                                if (substr($image, 0, 2) == '//') {

                                    // "//thumb.pccomponentes.com/w-85-85/articles/26/262285/nanocable-cable-hdmi-13-macho-macho-18m-comprar.jpg"
                                    if (strpos($image, '85-85'))
                                        $image = str_replace('85-85', '530-530', $image);
                                    // "//thumb.pccomponentes.com/w-530-530/articles/26/262285/nanocable-cable-hdmi-13-macho-macho-18m.jpg"
                                    elseif (!strpos($image, '530-530'))
                                        $image = null;

                                    if (isset($image)) {
                                        $image = substr($image, 2, strlen($image));
                                        $insert_images[] = $image;
                                    }
                                }
                            }

                            if (count($insert_images) == 1)
                                $imagesName[$ean][] = $product->updateOrCreateExternalImage($insert_images[0]);
                            elseif (count($insert_images) > 1) {
                                // Last image repeat first
                                for($i = 0; $i < count($insert_images)-1; $i++)
                                    $imagesName[$ean][] = $product->updateOrCreateExternalImage($insert_images[$i]);
                            } */
                        //}
                    }
                }
            }

            //dd($json_product, $imagesName);
        }

        return 'Imágenes insertadas: '.json_encode($imagesName);
    }


    private function processFileIcecatEANIMGScrape($json_contents)
    {
        // \"ean\":\"8435490615236\",\"part_number\":\"KT-TK3170\"
        // \"brand\": \"Kyocera\",\n
        // data-brand=\"Kyocera\" data-category=\"Tintas\"
        $count = 0;
        $res = $no_res = [];
        foreach($json_contents as $json_product) {
            $pn = $json_product[0];
            $content = $json_product[1];
            $images = $json_product[2];
            //dd($pn, $images);   //, $content, $json_product);

            if (!empty($content)) {

                // Search PN
                // ["DXS-3600-32S\/SI","ISR4331-SEC\/K9","10GB-LR-SFPP"]
                $PNcontent = substr($content, strpos($content, 'Código del producto'), 1000);
                $strStartPN = 'href="/es-ar/search?keyword=';
                $startPNpos = strpos($PNcontent, $strStartPN);
                $strEndPN = '" title="Buscar';
                $endPNpos = strpos($PNcontent, $strEndPN, $startPNpos + strlen($strStartPN));
                $pn_extracted = substr($PNcontent, $startPNpos + strlen($strStartPN), $endPNpos - $startPNpos - strlen($strStartPN));
                //dd($pn, $pn_extracted, strlen($strStartPN), strlen($strEndPN));


                /* $strStartPn = 'title="Buscar '.$pn. ' data-sheets">';
                $startpnpos = strpos($content, $strStartPn);
                $strEndPn = '</span>';
                $endpnpos = strpos($content, $strEndPn, $startpnpos + strlen($strStartPn));
                $pn_extracted = substr($content, $startpnpos + strlen($strStartPn) + 53, $endpnpos - $startpnpos - 90);
                $pn_extracted = trim($pn_extracted);
                dd($pn, $pn_extracted, strlen($strStartPn), strlen($strEndPn)); */

                // Get EAN
                if (strtoupper($pn) == strtoupper($pn_extracted)) {

                    // Get BRAND
                    $strStartBrand = 'href="/es-ar/search?supplierLocalName=';
                    $startbrandpos = strpos($content, $strStartBrand);
                    $strEndBrand = '" title="Buscar';
                    $endbrandpos = strpos($content, $strEndBrand, $startbrandpos + strlen($strStartBrand));
                    $brand_extracted = substr($content, $startbrandpos + strlen($strStartBrand), $endbrandpos - $startbrandpos - strlen($strStartBrand));
                    //dd(strlen($strStartBrand), strlen($strEndBrand), $startbrandpos, $endbrandpos, $brand_extracted);

                    // Get EAN
                    $EANcontent = substr($content, strpos($content, 'Código EAN/UPC'), 1000);
                    $strStartEAN = 'href="/es-ar/search?keyword=';
                    $startEANpos = strpos($EANcontent, $strStartEAN);
                    $strEndEAN = '" title="Buscar';
                    $endEANpos = strpos($EANcontent, $strEndEAN, $startEANpos + strlen($strStartEAN));
                    $ean_extracted = substr($EANcontent, $startEANpos + strlen($strStartEAN), $endEANpos - $startEANpos - strlen($strStartEAN));
                    //dd(strlen($strStartEAN), strlen($strEndEAN), $startEANpos, $endEANpos, $ean_extracted);



                    $products = Product::wherePn($pn)->whereNull('ean')->get();
                    if ($products->count()) {
                        foreach($products as $product) {

                            if (strlen($ean_extracted) == 13 &&
                                (
                                    strtoupper($product->brand->name) == strtoupper($brand_extracted) ||
                                    (strtoupper($product->brand->name) == 'EXTREME NETWORKS' && strtoupper($brand_extracted) == 'EXTREME%20NETWORKS') ||
                                    (strtoupper($product->brand->name) == 'HP INC' && strtoupper($brand_extracted) == 'HP') ||
                                    (strtoupper($product->brand->name) == 'EMERSON / VERTIV' && strtoupper($brand_extracted) == 'VERTIV') ||
                                    (strtoupper($product->brand->name) == 'HP ENT' && strtoupper($brand_extracted) == 'HEWLETT%20PACKARD%20ENTERPRISE') ||
                                    (strtoupper($product->brand->name) == 'GOOGLE WIFI' && strtoupper($brand_extracted) == 'GOOGLE') ||
                                    (strtoupper($product->brand->name) == 'HP ENT' && strtoupper($brand_extracted) == 'HPE') ||
                                    (strtoupper($product->brand->name) == 'SEAGATE CONSUMER' && strtoupper($brand_extracted) == 'SEAGATE') ||
                                    (strtoupper($product->brand->name) == 'ACCO/KENSINGTON' && strtoupper($brand_extracted) == 'KENSINGTON')||
                                    (strtoupper($product->brand->name) == 'HP ENT' && strtoupper($brand_extracted) == 'HPE ARUBA') ||
                                    (substr(strtoupper($product->brand->name), 0, 2) == 'HP' && strtoupper($brand_extracted) == 'HP') ||
                                    (substr(strtoupper($product->brand->name), 0, 7) == 'CRUCIAL' && strtoupper($brand_extracted) == 'CRUCIAL')
                                )) {

                                $res[] = [
                                    'pn'                => strtoupper($pn),
                                    'pn_extracted'      => strtoupper($pn_extracted),
                                    'brand_extracted'   => strtoupper($brand_extracted),
                                    'brand'             => strtoupper($product->brand->name),
                                    'ean_extracted'     => $ean_extracted,
                                    'ean'               => $product->ean,
                                    'len_ean'           => strlen($ean_extracted),
                                    'product'           => $product,
                                ];

                                $product->ean = $ean_extracted;
                                $product->save();
                                $count++;
                            }
                            else {
                                $no_res[] = [
                                    'pn'                => strtoupper($pn),
                                    'pn_extracted'      => strtoupper($pn_extracted),
                                    'brand_extracted'   => strtoupper($brand_extracted),
                                    'brand'             => strtoupper($product->brand->name),
                                    'ean_extracted'     => $ean_extracted,
                                    'ean'               => $product->ean,
                                    'len_ean'           => strlen($ean_extracted),
                                    'product'           => $product,
                                ];
                            }

                        }
                    }
                }
            }
        }

        Storage::append('utils/icecat_eans_img_' .date('y-m-d_H-i-s'). '_RES.json', json_encode($res));
        Storage::append('utils/icecat_eans_img_' .date('y-m-d_H-i-s'). '_NO_RES.json', json_encode($no_res));
        dd($res, $no_res);

        return 'EANs IMGs insertados: '.$count;
    }



    private function insertCentralpointImages(Product $product, $images)
    {
        /* [
            "https://www02.cp-static.com/objects/high_pic/3/308/1351643833_externe-harde-schijven-seagate-stkc4000400.jpg",
            "https://www02.cp-static.com/objects/multimedia/7/733/1351643833_1893423113_externe-harde-schijven-seagate-one-touch-stkc4000400.jpg"
        ] */

        $res = [];
        $insert_images = [];
        foreach ($images as $image) {
            if (!strpos($image, 'high_pic')) {
                $insert_images[] = $image;
            }
        }

        //dd($product, $images, $insert_images);

        for($i = 0; $i < count($insert_images); $i++)
            $res[] = $product->updateOrCreateExternalImage($insert_images[$i]);

        return $insert_images;
    }


    private function insertPCCompoImages(Product $product, $images)
    {
        $res = [];
        $insert_images = [];
        foreach ($images as $image) {
            if (substr($image, 0, 2) == '//') {

                // "//thumb.pccomponentes.com/w-85-85/articles/26/262285/nanocable-cable-hdmi-13-macho-macho-18m-comprar.jpg"
                if (strpos($image, '85-85'))
                    $image = str_replace('85-85', '530-530', $image);
                // "//thumb.pccomponentes.com/w-530-530/articles/26/262285/nanocable-cable-hdmi-13-macho-macho-18m.jpg"
                elseif (!strpos($image, '530-530'))
                    $image = null;

                if (isset($image)) {
                    //$image = substr($image, 2, strlen($image));
                    $insert_images[] = 'https:' .$image;
                    //dd($image, $insert_images);
                }
            }
        }

        if (count($insert_images) == 1)
            $res[] = $product->updateOrCreateExternalImage($insert_images[0]);
        elseif (count($insert_images) > 1) {
            // Last image repeat first
            for($i = 0; $i < count($insert_images)-1; $i++)
                $res[] = $product->updateOrCreateExternalImage($insert_images[$i]);
        }


        //dd('insertPCCompoImages', count($insert_images), $insert_images, $images, $product);


        return $insert_images;
    }


    /********* REMOVE BACKSLASHES *******/


    public function removeProductsBackslashes()
    {
        $count = 0;
        $products = Product::all();
        foreach ($products as $product) {
            if (strpos($product->name, '\\')) {
                $product->name = str_replace('\\', '', $product->name);
                $product->save();
                $count++;
            }

        }

        dd($count);
    }



    public function updateVox()
    {
        //$ws = new Ivox66WS();
        //return $ws->update();
        $res = FacadesVox66Api::update();
        dd($res);
    }


    /*********** IMPORT ***********/


    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'import'    => 'required'
        ]);

        $import = 'App\\Imports\\'.$request->input('import');
        $importClass = new $import();

        $functions = $importClass::FUNCTIONS;
        $import_text = $importClass::IMPORT_TEXT;

        return view('utils.import', compact('import', 'functions', 'import_text'));
    }



    public function importProcess(Request $request)
    {
        $validatedData = $request->validate([
            'fileinput'     => 'required',      //|mimes:csv,xls,xlsx',
            'import'        => 'required',
            'function'      => 'required'
        ]);

        $import = $validatedData['import'];
        $importClass = new $import();                           // App\Imports\ImportTone

        $function = $validatedData['function'];
        $res = $importClass->$function($validatedData['fileinput']);   // ImportTone->importProducts()

        return redirect()->route('utils')->with('status', $res);
    }


    /************* MAILJET **********/


    public function mailjetSectors()
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
                $sector = FacadesMpe::stripAccents($row[1]);
                if (!isset($sectors[$sector])) $sectors[$sector] = 1;
                else $sectors[$sector]++;
            }
        }

        arsort($sectors);
        $target_path = storage_path('app/imports/sectors_'.date('Y-m-d').'.csv');
        $fp = fopen($target_path, 'a');
        $screen = [];
        foreach ($sectors as $key => $value) {
            fputcsv($fp, [$key, $value]);
            $screen[] = [$key, $value];
        }
        fclose($fp);

        //return storage_path('app/imports/sectors.csv');
        dd($screen);
    }


    public function mailjetBlockeds()
    {
        try {

            // ELIMINA DE BBDDSPAIN TOTS ELS BLOCATS X MAILJET
            $blockeds_emails = [];
            $blockeds_path = storage_path('app/imports/BLOCKEDS/televisores_modo_hotel_y_cartelería_digital_blocked_hard_bounce_20210629_5234094F6AFE47BA8DD550D48F971D2F.csv');
            $csv_blockeds_array = file($blockeds_path);
            foreach ($csv_blockeds_array as $blocked_line) {
                $row = str_getcsv($blocked_line);
                if (isset($row[0])) {
                    $blockeds_emails[$row[0]] = true;
                }
            }

            $target_array = [];
            if (count($blockeds_emails)) {
                $source_path = storage_path('app/imports/BBDDSPAIN 2020 COL3.csv');
                $csv_array = file($source_path);
                foreach ($csv_array as $line) {
                    $row = str_getcsv($line);
                    if (isset($row[2]) && !isset($blockeds_emails[$row[2]])) $target_array[] = $line;
                }
            }

            // Create CLEAN BBDD
            if (count($target_array)) {
                $target_path = storage_path('app/imports/BBDDSPAIN 2020 COL3 CLEANED.csv');
                $fp = fopen($target_path, "w+");
                flock($fp, LOCK_EX);
                foreach($target_array as $line) {
                    fwrite($fp, $line);
                }
                flock($fp, LOCK_UN);
                fclose($fp);
            }

            dd(count($target_array));


        } catch (Throwable $th) {
            dd($th);
        }
    }


    public function mailjetDeleteds()
    {
        try {

            // ELIMINA DE BBDDSPAIN TOTS ELS CORREUS QUE NO VOLEN REBRE MÉS MAILING
            // /var/www/html/shop/storage/app/campaigns/CAMPAÑA_ID_DELETES.csv
            $deleteds_emails = [];
            $deleted_count = 0;
            $deleteds_path = storage_path('app/imports/DELETES/zhfw78e7n8u5u9vuqjjqczrgzbhyim_DELETES.csv');
            $csv_deleteds_array = file($deleteds_path);
            foreach ($csv_deleteds_array as $deleted_line) {
                $row = str_getcsv($deleted_line);
                //dd($row, $deleted_line);

                if (isset($row['7'])) {
                    $start = strpos($row['7'], '"')+1;
                    $end = strpos($row['7'], '"', 8) - $start;
                    $email = mb_substr($row['7'], $start, $end);

                    $deleteds_emails[$email] = '';
                    $deleted_count++;
                }
                elseif (isset($row['6'])) {
                    $start = strpos($row['6'], '"')+1;
                    $end = strpos($row['6'], '"', 8) - $start;
                    $email = mb_substr($row['6'], $start, $end);

                    $deleteds_emails[$email] = '';
                    $deleted_count++;
                }
                elseif (isset($row['5'])) {
                    $start = strpos($row['5'], '"')+1;
                    $end = strpos($row['5'], '"', 8) - $start;
                    $email = mb_substr($row['5'], $start, $end);

                    $deleteds_emails[$email] = true;
                    $deleted_count++;
                }

                /* if (isset($row[0])) {
                    $deleteds_emails[$row[0]] = true;
                } */
            }

            //dd($deleteds_emails, $row, $deleted_line);

            $target_array = [];
            if (count($deleteds_emails)) {
                $source_path = storage_path('app/imports/BBDDSPAIN 2020 COL3.csv');
                $csv_array = file($source_path);
                foreach ($csv_array as $line) {
                    $row = str_getcsv($line);
                    if (isset($row[2]) && !isset($deleteds_emails[$row[2]])) $target_array[] = $line;
                }
            }

            // Create CLEAN BBDD
            if (count($target_array)) {
                $target_path = storage_path('app/imports/BBDDSPAIN 2020 COL3 CLEANED.csv');
                $fp = fopen($target_path, "w+");
                flock($fp, LOCK_EX);
                foreach($target_array as $line) {
                    fwrite($fp, $line);
                }
                flock($fp, LOCK_UN);
                fclose($fp);
            }

            dd(count($target_array));


        } catch (Throwable $th) {
            dd($th);
        }
    }


    public function mailjet(Request $request)
    {
        $validatedData = $request->validate([
            'sectors'    => 'nullable'
        ]);

        $sectors = explode(',', $validatedData['sectors']);
        $sectors = array_map('trim', $sectors);
        //dd($validatedData, $sectors, $request);

        // CREA CSV LLISTA DE EMAILS SEGONS SECTORS
        $filename = date('Y-m-d').'_mailjet_'.preg_replace("/[^a-zA-Z]/", "", $validatedData['sectors']).'.csv';
        $source_path = storage_path('app/imports/BBDDSPAIN 2020 COL3.csv');
        $target_path = storage_path('app/imports/' .$filename);
        $fp = fopen($target_path, 'a');

        $csv_array = file($source_path);
        foreach ($csv_array as $line) {

            $row = str_getcsv($line);
            if (!isset($row[0]) || !isset($row[1]) || !isset($row[2]))
                Storage::append('imports/actividades/_ERROR.json', json_encode($row));
            else {
                $name = $row[0];
                $sector = $row[1];      //FacadesMpe::cleanStr(FacadesMpe::stripAccents($row[1]));
                //if (in_array($sector, $sectors)) {
                if (strpos($sector, 'HOTEL') !== false  || strpos($name, 'HOTEL') !== false ||
                    strpos($sector, 'HOSTAL') !== false || strpos($name, 'HOSTAL') !== false ||
                    strpos($sector, 'APART') !== false  || strpos($name, 'APART') !== false ||
                    strpos($sector, 'VACACI') !== false || strpos($name, 'VACACI') !== false ||
                    /* strpos($sector, 'ARQUITECTOS') !== false || */ /* strpos($name, 'ARQUITECTOS') !== false || */
                    strpos($sector, 'INMOBILIARIAS') !== false || /* strpos($name, 'INMOBILIARIAS') !== false || */
                    strpos($sector, 'AGENCIAS DE VIAJES') !== false || /* strpos($name, 'AGENCIA DE VIAJES') !== false || */
                    strpos($sector, 'GRAFICO') !== false || /* strpos($name, 'GRAFICO') !== false || */
                    strpos($sector, 'GRAFICAS') !== false || /* strpos($name, 'GRAFICAS') !== false || */
                    strpos($sector, 'PUBLICIDAD') !== false /* || strpos($name, 'PUBLICIDAD') !== false */
                    )  {

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



    /********************* ORDERS, SUPPLIERS, CATEGORIES & BRAND NAMES ****************/

    public function getSupplierOrders(Request $request)
    {
        $validatedData = $request->validate([
            'brand_name'    => 'required',
        ]);

        try {
            $res = [];
            //$brand_name = 'Apple';
            $order_items = OrderItem::select('order_items.*')
                ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                ->where('brands.name', $validatedData['brand_name'])
                ->get();

            foreach ($order_items as $order_item) {

                $res[] = [
                    $order_item->product->brand->name,
                    $order_item->product->supplier->name,
                    $order_item->order->created_at->format('Y-m-d')
                ];
            }

            Storage::append('utils/supplier_orders' .date('y-m-d_H-i'). '.json', json_encode($res));

            dd($validatedData, $res, $order_items);

        } catch (Throwable $th) {
            dd($validatedData, $res, $th);
        }
    }


    public function getOrderCategories(Request $request)
    {
        $validatedData = $request->validate([
            'shop_id'    => 'nullable',
        ]);

        try {
            $res = [];
            //$brand_name = 'Apple';
            $order_items = OrderItem::select(
                'categories.name',
                DB::raw('count(categories.name) as category_count'))
                ->leftJoin('orders', 'orders.id', '=', 'order_items.order_id')
                ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->orderBy('category_count', 'desc')
                ->groupBy('categories.name');

            if (isset($validatedData['shop_id']))
                $order_items->where('orders.shop_id', $validatedData['shop_id']);

            $order_items = $order_items->get()->toArray();

            Storage::append('utils/order_categories' .date('y-m-d_H-i'). '.json', json_encode($order_items));
            dd($order_items);

        } catch (Throwable $th) {
            dd($res, $th);
        }
    }



    /********************* DELETE OWN SUPPLIER FROM SHOP FILTERS ****************/


    public function getDeleteOwnSupplierFromShopFilters()
    {
        try {
            $own_suppliers = [1, 22, 23, 26, 37];        // 22 Depau, 23 Megasur, 26 SCE, 37 Aseuropa
            $shop_filters = ShopFilter::whereIn('supplier_id', $own_suppliers)->delete();

            dd($shop_filters);

        } catch (Throwable $th) {
            dd($th);
        }
    }


    /********************* TEST FILE ****************/

    public function test(Request $request)
    {
        $validatedData = $request->validate([
            'fileinput'     => 'required',
        ]);

        try {
            //dd($validatedData);
            $uploaded_file = $validatedData['fileinput'][0];    // UploadedFile
            /* XLSX, CSV, ...
            $inputFileType = IOFactory::identify($uploaded_file->getPathname());
            $reader = IOFactory::createReader($inputFileType);
            $spreadsheet = $reader->load($uploaded_file->getPathname());
            $sheet = $spreadsheet->getSheet(0);
            $file_rows = $sheet->toArray(null, true, true, true); */

            $contents = file_get_contents($uploaded_file->getRealPath());
            dd(json_decode($contents), $contents);


            /* $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['products']['header_rows']);

            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows)->keyBy((string)$this->supplier->supplierSku_field);
            $productsCollect = $this->supplier->filterProducts($productsCollect);

            return self::products($this->supplier, $productsCollect); */

        } catch (Throwable $th) {
            dd($request, $validatedData, $th);
        }
    }

}
