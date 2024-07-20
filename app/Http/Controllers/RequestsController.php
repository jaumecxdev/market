<?php

namespace App\Http\Controllers;

use App\Libraries\AliexpressWS;
use App\Libraries\AmazonWS;
use App\Libraries\EbayWS;
use App\Libraries\JoomWS;
use App\Libraries\MagentoWS;
use App\Libraries\MarketWS;
use App\Libraries\WishWS;
use App\Libraries\WortenWS;
use App\Shop;
use App\ShopProduct;
use Illuminate\Http\Request;
use Throwable;

class RequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        $requests_name = [
            'test'                      => '(All) test',
            'set_default_config'        => '(All) set_default_config',
            'set_default_filters'       => '(All) set_default_filters',
            'set_shop_categories'       => '(All) set_shop_categories',

            'get_product'               => '(All) get_product',
            'get_all_products'          => '(All) get_all_products',
            'remove_product'            => '(All) remove_product',
            'remove_all_products'       => '(All) remove_all_products',
            'get_shop_config'           => '(All) get_shop_config',
            'get_job'                   => '(All) get_job',
            'get_carriers'              => '(All) get_carriers',
            'synchronize'               => '(All) synchronize',
            'sync_categories'           => '(All) sync_categories',
            'remove_without_stock'      => '(All) remove_without_stock',
            'reset_no_product'          => '(All) reset_no_product',
            'get_LOCAL_property_values' => '(All) get_LOCAL_property_values',
            'get_BAD_products_fees'     => '(All) get_BAD_products_fees',

            'get_buybox_prices'         => '(Mirakl & Amazon) get_buybox_prices',
            'authorize_app'         => '(Aliexpress & Wish & Joom) authorize_app',
            'get_brands'            => '(Wish & Prestaedu) get_brands',
            'get_category_schema'   => '(Aliexpress & Prestashops) get_category_schema',


            'get_report'            => '(Amazon) get_report',
            'get_fees_estimates'    => '(Amazon) get_fees_estimates',
            'get_competitive_pricing'=>'(Amazon) get_competitive_pricing',
            'get_lowest_offer'      => '(Amazon) get_lowest_offer',
            'get_lowest_price'      => '(Amazon) get_lowest_price',

            'get_all_local_products'=> '(Aliexpress) get_all_local_products',
            'set_all_sku_products'  => '(Aliexpress) set_all_sku_products',
            'get_category_tree'     => '(Aliexpress) get_category_tree',
            'get_order'             => '(Aliexpress) get_order',
            'translate_colors'      => '(Aliexpress) translate_colors',

            'verify_products'       => '(ebay) verify_products',
            'seller_profiles'       => '(ebay) seller_profiles',
            'get_items_by_product'  => '(ebay) findItemsByProduct',
            'get_jobs'              => '(ebay) RequestGetJobs',
            'remove_job'            => '(ebay) RequestRemoveJob',
            'remove_jobs'           => '(ebay) removeNonTerminatedJobs',
            'upload_image'          => '(ebay) upload_image',
            'categories'            => '(ebay) categories',
            'category_specifics'    => '(ebay) category_specifics',
            'relist_product'        => '(ebay) relist_product',
            'api_rules'             => '(ebay) api_rules',

            'get_all_jobs'          => '(Worten) get_all_jobs',
            'get_offer'             => '(Worten) get_offer',
            'clean_errors'          => '(Worten) clean_errors',

            'refresh_token'         => '(Joom) refresh_token',
            'get_colors'            => '(Joom) get_colors',

            'get_all_products_jobs' => '(Wish) get_all_products_jobs',
            'get_currencies'        => '(Wish) get_currencies',
            //'remove_all_products'   => '(Wish) remove_all_products',

            'get_images'            => '(Presta ALL) get_images',
            'get_configuration'     => '(Presta ALL) get_configuration',

            'get_token'             => '(Magento) get_token',

            'local_ddbb_json'       => '(NO SHOP) local_ddbb_json'
        ];

        return view('requests.index', compact('shops', 'requests_name'));
    }


    public function getRequest(Request $request)
    {
        $validatedData = $request->validate([
            'shop_id'       => 'nullable|exists:shops,id',
            'request_name'  => 'required|max:255',
            'param'         => 'nullable|max:255',
            'param2'        => 'nullable|max:255',
            'param3'        => 'nullable|max:255',
        ]);

        try {

            if (isset($validatedData['shop_id']))
                $shop = Shop::findOrFail($validatedData['shop_id']);

            if (!isset($shop))
                return back()->withErrors('Select Shop');

            $res = null;
            switch ($validatedData['request_name']) {

                case 'test':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->test();
                    dd($res);
                    break;

                case 'set_default_config':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->setDefaultConfig();
                    dd($res);
                    break;

                case 'set_default_filters':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->setDefaultShopFilters($validatedData['param']);
                    dd($res);
                    break;

                case 'set_shop_categories':
                    return $shop->setShopParamCategories();
                    break;

                case 'get_product':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->getProduct($validatedData['param']);
                    break;

                case 'get_all_products':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->getAllProducts($validatedData['param']);
                    dd($res);
                    break;

                case 'remove_product':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->removeProduct($validatedData['param']);
                    dd($res);
                    break;

                case 'remove_all_products':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->removeAllProducts();
                    dd($res);
                    break;

                case 'get_shop_config':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->getShopConfig($validatedData['param']);
                    dd($res);
                    break;

                case 'get_job':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->getJob($validatedData['param'], $validatedData['param2']);
                    dd($res);
                    break;

                case 'get_carriers':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->getCarriers();
                    dd($res);
                    break;

                case 'synchronize':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->synchronize();
                    dd($res);
                    break;

                case 'sync_categories':
                    if ($ws = MarketWS::getMarketWS($shop))
                        $res = $ws->syncCategories();
                    dd($res);
                    break;

                case 'remove_without_stock':
                    if ($ws = MarketWS::getMarketWS($shop)) $res = $ws->removeWithoutStock();
                    dd($res);
                    break;

                case 'reset_no_product':
                    $res = $shop->shop_products()->where('marketProductSku', 'NO PRODUCT')->update(['marketProductSku' => null]);
                    dd($res);
                    break;

                case 'get_LOCAL_property_values':
                    $res = $shop->getPropertyValues($validatedData['param'], $validatedData['param2']);     //$market_category_name, $market_attribute_name
                    dd($res);
                    break;

                case 'get_BAD_products_fees':
                    $shop_products = ShopProduct::select('shop_products.market_category_id')
                        ->whereNotNull('shop_products.market_category_id')
                        ->where('shop_products.stock', '>', 0)
                        ->where('shop_products.shop_id', $shop->id)
                        ->where('shop_products.param_mp_fee', $validatedData['param'])
                        ->groupBy('shop_products.market_category_id')
                        ->get();

                    foreach ($shop_products as $shop_product) {
                        $res[] = $shop_product->market_category->marketCategoryId;
                    }
                    dd($res, $shop_products);
                    break;


                // SOME MARKETPLACES
                case 'get_buybox_prices':
                    if (in_array($shop->market->code, ['amazonsp', 'carrefour', 'pccompo', 'worten']) && $ws = MarketWS::getMarketWS($shop))
                        return $ws->getBuyBoxPrices();
                    break;

                case 'authorize_app':
                    if (in_array($shop->market->code, ['ae', 'wish', 'joom']) && $ws = MarketWS::getMarketWS($shop))
                        return $ws->authorize();
                    break;

                case 'get_brands':
                    if (in_array($shop->market->code, ['wish','prestaedu']) && $ws = MarketWS::getMarketWS($shop))
                        $res = $ws->getBrands();
                    dd($res);
                    break;

                case 'get_category_schema':
                    if (in_array($shop->market->code, ['ae', 'prestaedu', 'prestahp']) && $ws = MarketWS::getMarketWS($shop))
                        $res = $ws->getCategorySchema($validatedData['param']);
                    dd($res);
                    break;



                // AMAZON ONLY
                case 'get_report':
                    if ($shop->market->code == 'amazon' && $ws = new AmazonWS($shop))
                        $res = $ws->getReportRequestType($validatedData['param']);
                    dd($res);
                    break;

                case 'get_fees_estimates':
                    if ($shop->market->code == 'amazon' && $ws = new AmazonWS($shop))
                        $res = $ws->getFeesEstimates($validatedData['param']);
                    dd($res);
                    break;

                case 'get_competitive_pricing':
                    if ($shop->market->code == 'amazon' && $ws = new AmazonWS($shop))
                        $res = $ws->getCompetitivePricing($validatedData['param']);
                    dd($res);
                    break;

                case 'get_lowest_offer':
                    if ($shop->market->code == 'amazon' && $ws = new AmazonWS($shop))
                        $res = $ws->getLowestOffer($validatedData['param']);
                    dd($res);
                    break;

                case 'get_lowest_price':
                    if ($shop->market->code == 'amazon' && $ws = new AmazonWS($shop))
                        $res = $ws->getLowestPrice($validatedData['param']);
                    dd($res);
                    break;


                // ALIEXPRESS ONLY
                case 'get_all_local_products':
                    if ($shop->market->code == 'ae' && $ws = new AliexpressWS($shop))
                        $res = $ws->getAllLocalProducts();
                    dd($res);
                    break;

                case 'set_all_sku_products':
                    if ($shop->market->code == 'ae' && $ws = new AliexpressWS($shop))
                        $res = $ws->SetAllSkuProducts();
                    dd($res);
                    break;

                case 'get_shipping_template':
                    if ($shop->market->code == 'ae' && $ws = new AliexpressWS($shop))
                        $res = $ws->getShippingTemplate();
                    dd($res);
                    break;

                case 'get_service_template':
                    if ($shop->market->code == 'ae' && $ws = new AliexpressWS($shop))
                        $res = $ws->getServiceTemplate();
                    dd($res);
                    break;

                case 'get_category_tree':
                    if ($shop->market->code == 'ae' && $ws = new AliexpressWS($shop))
                        $res = $ws->getCategoryTreeRequest($validatedData['param']);
                    dd($res);
                    break;

                case 'get_order':
                    if ($shop->market->code == 'ae' && $ws = new AliexpressWS($shop))
                        $res = $ws->requestGetOrder($validatedData['param']);
                    dd($res);
                    break;

                case 'translate_colors':
                    if ($shop->market->code == 'ae' && $ws = new AliexpressWS($shop))
                        $res = $ws->requestTranslateColors();
                    dd($res);
                    break;


                // EBAY ONLY
                case 'verify_products':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->postVerifyProducts();
                    dd($res);
                    break;

                case 'seller_profiles':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->getSellerProfiles();
                    dd($res);
                    break;

                case 'get_items_by_product':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->findItemsByProduct($validatedData['param']);
                    dd($res);
                    break;

                case 'get_jobs':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->RequestGetJobs();
                    dd($res);
                    break;

                case 'remove_job':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->RequestRemoveJob($validatedData['param']);
                    dd($res);
                    break;

                case 'remove_jobs':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->removeNonTerminatedJobs();
                    dd($res);
                    break;

                case 'upload_image':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->uploadImageToEPS($validatedData['param'], $validatedData['param2']);
                    dd($res);
                    break;

                case 'categories':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->getCategoriesRequest($validatedData['param']);
                    dd($res);
                    break;

                case 'category_specifics':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->getCategorySpecifics($validatedData['param']);
                    dd($res);
                    break;

                case 'relist_product':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->relistProduct($validatedData['param']);
                    dd($res);
                    break;

                case 'api_rules':
                    if ($shop->market->code == 'ebay' && $ws = new EbayWS($shop))
                        $res = $ws->getGetApiAccessRulesRequest();
                    dd($res);
                    break;


                // WORTEN ONLY
                case 'get_all_jobs':
                    if ($shop->market->code == 'worten' && $ws = new WortenWS($shop))
                        $res = $ws->getAllJobs();
                    dd($res);
                    break;

                case 'get_offer':
                    if ($shop->market->code == 'worten' && $ws = new WortenWS($shop))
                        $res = $ws->getOffer($validatedData['param']);
                    dd($res);
                    break;

                case 'clean_errors':
                    if ($shop->market->code == 'worten' && $ws = new WortenWS($shop))
                        $res = $ws->cleanErrors();
                    dd($res);
                    break;


                // ALL PRESTASHOPS
                case 'get_images':
                    if (in_array($shop->market->code, ['pceducacion', 'thehpshop', 'udg']) && $ws = MarketWS::getMarketWS($shop))
                        $res = $ws->getImages($validatedData['param']);
                    dd($res);
                    break;

                case 'get_configuration':
                    if (in_array($shop->market->code, ['pceducacion', 'thehpshop', 'udg']) && $ws = MarketWS::getMarketWS($shop))
                        $res = $ws->getConfiguration();
                    dd($res);
                    break;

                // MAGENTO
                case 'get_token':
                    if ($shop->market->code == 'magento' && $ws = new MagentoWS($shop))
                        $res = $ws->getToken();
                    dd($res);
                    break;


                // JOOM
                case 'refresh_token':
                    if ($shop->market->code == 'joom' && $ws = new JoomWS($shop))
                        $res = $ws->refreshToken();
                    dd($res);
                    break;

                case 'get_colors':
                    if ($shop->market->code == 'joom' && $ws = new JoomWS($shop))
                        $res = $ws->getColors();
                    dd($res);
                    break;


                // WISH
                case 'get_all_products_jobs':
                    if ($shop->market->code == 'wish' && $ws = new WishWS($shop))
                        $res = $ws->getAllProductsJob();
                    dd($res);
                    break;

                case 'get_currencies':
                    if ($shop->market->code == 'wish' && $ws = new WishWS($shop))
                        $res = $ws->getCurrencies();
                    dd($res);
                    break;

                /* case 'remove_all_products':
                    if ($shop->market->code == 'wish' && $ws = new WishWS($shop))
                        $res = $ws->removeAllProducts();
                    dd($res);
                    break; */


                // NO SHOP REQUESTS
                case 'local_ddbb_json':
                    $this->getLocalDDBBJson();
                    break;
            }

            return back()->withErrors('Consulta no encontrada.');


        } catch (Throwable $th) {
            dd($th);
        }

    }






}
