<?php

namespace App\Libraries;

use App\Shop;
use App\Buyer;
use App\Order;
use Throwable;
use App\Status;
use App\Address;
use App\Country;
use App\Product;
use App\ShopJob;
use DOMDocument;
use App\Category;
use App\Currency;
use App\OrderItem;
use App\ShopFilter;
use App\ShopProduct;
use App\RootCategory;
use SimpleXMLElement;
use App\MarketCarrier;
use GuzzleHttp\Client;
use App\MarketCategory;
use App\Libraries\MarketWS;
use App\MarketParam;
use App\Traits\HelperTrait;
use Illuminate\Support\Carbon;
use SellingPartnerApi\Document;
use SellingPartnerApi\Endpoint;
use SellingPartnerApi\ReportType;
use SellingPartnerApi\Api\FeesApi;
use SellingPartnerApi\Api\FeedsApi;
use SellingPartnerApi\Api\OrdersApi;
use SellingPartnerApi\Api\TokensApi;
use SellingPartnerApi\Configuration;
use SellingPartnerApi\Api\CatalogApi;
use SellingPartnerApi\Api\ReportsApi;
use SellingPartnerApi\Api\SellersApi;
use SellingPartnerApi\Api\ShippingApi;
use Illuminate\Support\Facades\Storage;
use SellingPartnerApi\Api\OldCatalogApi;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Database\Eloquent\Builder;
use SellingPartnerApi\Api\VendorOrdersApi;
use Illuminate\Database\Eloquent\Collection;
use SellingPartnerApi\Api\ProductPricingApi;
use SellingPartnerApi\Api\VendorInvoicesApi;
use SellingPartnerApi\Api\VendorShippingApi;
use SellingPartnerApi\Model\Shipping\Dimensions;
use SellingPartnerApi\Model\Shipping\ServiceType;
use SellingPartnerApi\Model\VendorInvoices\Invoice;
use SellingPartnerApi\Model\Shipping\GetRatesRequest;
use SellingPartnerApi\Model\Tokens\RestrictedResource;
use SellingPartnerApi\Model\Orders\Order as OrdersOrder;
use SellingPartnerApi\Model\Feeds\CreateFeedSpecification;
use SellingPartnerApi\Api\VendorDirectFulfillmentOrdersApi;
use SellingPartnerApi\Model\Feeds\CreateFeedDocumentResult;
use SellingPartnerApi\Model\Shipping\CreateShipmentRequest;
use SellingPartnerApi\Model\Fees\MoneyType as FeesMoneyType;
use SellingPartnerApi\Model\Shipping\ContainerSpecification;
use SellingPartnerApi\Api\VendorDirectFulfillmentShippingApi;
use SellingPartnerApi\Api\VendorDirectFulfillmentInventoryApi;
use SellingPartnerApi\Model\Reports\CreateReportSpecification;
use SellingPartnerApi\Model\Feeds\FeedDocumentEncryptionDetails;
use SellingPartnerApi\Model\Shipping\Address as ShippingAddress;
use SellingPartnerApi\Model\VendorShipping\ShipmentConfirmation;
use SellingPartnerApi\Model\VendorInvoices\SubmitInvoicesRequest;
use SellingPartnerApi\Model\Feeds\CreateFeedDocumentSpecification;
use SellingPartnerApi\Model\Tokens\CreateRestrictedDataTokenRequest;
use SellingPartnerApi\Model\VendorOrders\Order as VendorOrdersOrder;
use SellingPartnerApi\Model\VendorOrders\Address as VendorOrdersAddress;
use SellingPartnerApi\Model\VendorDirectFulfillmentInventory\ItemDetails;
use SellingPartnerApi\Model\VendorDirectFulfillmentInventory\InventoryUpdate;
use SellingPartnerApi\Model\VendorShipping\SubmitShipmentConfirmationsRequest;
use SellingPartnerApi\Model\Fees\FeesEstimateRequest as FeesFeesEstimateRequest;
use SellingPartnerApi\Model\Fees\PriceToEstimateFees as FeesPriceToEstimateFees;
use SellingPartnerApi\Model\VendorDirectFulfillmentInventory\PartyIdentification;
use SellingPartnerApi\Model\Fees\GetMyFeesEstimateRequest as FeesGetMyFeesEstimateRequest;
use SellingPartnerApi\Model\VendorDirectFulfillmentInventory\SubmitInventoryUpdateRequest;


class AmazonSPWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;


    //private $client = null;
    protected $refresh_token;      // Aztr|...
    protected $client_id;      // App ID from Seller Central, amzn1.sellerapps.app.cfbfac4a-...... - APP ID
    protected $client_secret;      // The corresponding Client Secret - APP SECRET
    protected $region; // or NORTH_AMERICA / FAR_EAST \ClouSale\AmazonSellingPartnerAPI\SellingPartnerRegion::$EUROPE
    protected $access_key; // Access Key of AWS IAM User, for example AKIAABCDJKEHFJDS - Developer
    protected $secret_key; // Secret Key of AWS IAM User - Develope
    protected $endpoint; // or NORTH_AMERICA / FAR_EAST
    protected $role_arn; // AWS IAM Role ARN for example: arn:aws:iam::123456789:role/Your-Role-Name

    protected $accessToken;
    protected $marketplace_id;

    protected $iam_user_config;
    protected $iam_role_config;

    protected $seller_id;

    protected $seller_type;

    protected $RDT;     // Restricted Data Token

    //private $assumedRole;


   /*  private $paypal_fee = 0.029;
    private $paypal_fee_addon = 0.35;

    private $shipping_time = '2-3';
    private $shipping = 3.99;
    private $country_shipping_prices = [
        'AD'    => 3.99,   // Andorra
        'PT'    => 3.99,   // Portugal
        'ES'    => 3.99,
    ];
 */

    const DEFAULT_CONFIG = [
        'errors_ignored'    => [],
        'order_status_ignored' => ['Pending','Shipped'],
        'seller_type'       => 'seller',        // vendor
    ];


    public function __construct(Shop $shop)
    {
        parent::__construct($shop);

        if ($this->config = json_decode($shop->config)) {
            if (isset($this->config->seller_type))
                $this->seller_type = $this->config->seller_type;
            else
                $this->seller_type = 'seller';
        }

        // SELLER PARAMS

        // Seller ID of Amazon Shop
        // $shop->marketSellerId
        //$this->seller_id = '';     // Nana Blanca Seller ID
        $this->seller_id = $shop->marketSellerId;

        // Seller | Vendor Region
        // $EUROPE: eu-west-1
        // $FAR_EAST: us-west-2
        // $NORTH_AMERICA: us-east-1
        // $shop->site
        //$this->region = 'eu-west-1';    //SellingPartnerRegion::$EUROPE; // or NORTH_AMERICA / FAR_EAST \ClouSale\AmazonSellingPartnerAPI\SellingPartnerRegion::$EUROPE
        $this->region = $shop->site;

        // Seller | Vendor Endpoint
        // $EUROPE: https://sellingpartnerapi-eu.amazon.com
        // $FAR_EAST: https://sellingpartnerapi-fe.amazon.com
        // $NORTH_AMERICA: https://sellingpartnerapi-na.amazon.com
        // $shop->endpoint
        //$this->endpoint = 'https://sellingpartnerapi-eu.amazon.com';    //SellingPartnerEndpoint::$EUROPE; // or NORTH_AMERICA / FAR_EAST
        $this->endpoint = $shop->endpoint;

        // Marketplace ID Spain
        // $shop->country
        //$this->marketplace_id = '';
        $this->marketplace_id = $shop->country;

        // DEVELOPER PARAMS

        // DEVELOPER ID:  - AWS Access ID
        // $shop->dev_id
        //$this->access_key     = ''; // Access Key of AWS IAM User, for example AKIAABCDJKEHFJDS - Developer
        $this->access_key = $shop->dev_id;

        // DEVELOPER ID:  - AWS Access Secret
        // $shop->dev_secret
        //$this->secret_key     = ''; // Secret Key of AWS IAM User - Develope
        $this->secret_key = $shop->dev_secret;

        // APP PARAMS

        // APP MPe Sellers Only - Authorization Refresh Token
        // $shop->refresh
        // APP SELLER ONLY
        // $this->refresh_token = '';
        // APP SELLER & VENDOR SYNC
        //$this->refresh_token = '';
        $this->refresh_token = $shop->refresh;

        // APP MPe Sellers Only - IAM ARN Role
        // $shop->header_url
        $this->role_arn       = '';
        $this->role_arn = $shop->header_url;

        // APP MPe Sellers Only - LWA Client ID
        // $shop->client_id
        //$this->client_id = '';       // APP SELLER ONLY
        //$this->client_id = '';         // APP SELLER & VENDOR SYNC
        $this->client_id = $shop->client_id;

        // APP MPe Sellers Only - LWA Client Secret
        // $shop->client_secret
        //$this->client_secret = '';        // APP SELLER ONLY
        //$this->client_secret = '';          // APP SELLER & VENDOR SYNC
        $this->client_secret = $shop->client_secret;
    }


    private function getShopProductMpsSku(ShopProduct $shop_product)
    {
        $mps_sku = mb_substr(str_replace(['.', '_'], ['', '-'], $shop_product->mps_sku), 0, 40);
        $shop_product->mps_sku = $mps_sku;
        $shop_product->save();
        $shop_product->refresh();

        return $mps_sku;
    }


    private function getIAMRoleConfig()
    {
        try {
            // OLD JLEVERS API
            // SellingPartnerApi\ConfigurationOptions
            /* $configurationOptions = new \SellingPartnerApi\ConfigurationOptions(
                $this->client_id,
                $this->client_secret,
                $this->refresh_token,
                $this->access_key,
                $this->secret_key,
                $this->region,
                $this->endpoint,
                null,  //
                null,  // More about these parameters in the `ConfigurationOptions` section below
                null,  //
                $this->role_arn
            ); */


            $configurationOptions = [
                'lwaClientId'           => $this->client_id,
                'lwaClientSecret'       => $this->client_secret,
                'lwaRefreshToken'       => $this->refresh_token,
                'awsAccessKeyId'        => $this->access_key,
                'awsSecretAccessKey'    => $this->secret_key,
                'endpoint'              => Endpoint::EU,
                'roleArn'               => $this->role_arn,
            ];

            Storage::append($this->shop_dir. 'oauth/' .date('Y-m-d_H-i'). '_getIAMRoleConfig.json', json_encode($configurationOptions));

            $config = new Configuration($configurationOptions);

            return $config;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$configurationOptions, $config ?? null]);
        }
    }


    private function amznGetCarrierCodes()
    {
        try {
            $carrier_codes = [];
            $doc = new DOMDocument();
            $doc->load('https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/amzn-base.xsd');
            $doc->save(storage_path('app/public/xsd/amzn-base.xml'));
            $xmlfile = file_get_contents(storage_path('app/public/xsd/amzn-base.xml'));
            $parseObj = str_replace($doc->lastChild->prefix.':',"",$xmlfile);
            $ob = simplexml_load_string($parseObj);
            $json  = json_encode($ob);
            $data = json_decode($json, true);

            foreach ($data['element'] as $element) {
                if (isset($element['@attributes']['name']) && $element['@attributes']['name'] == 'CarrierCode')
                    if (isset($element['simpleType']['restriction']['enumeration'])) {
                        foreach ($element['simpleType']['restriction']['enumeration'] as $enumeration) {
                            $carrier_codes[] = $enumeration['@attributes']['value'] ?? null;
                        }
                    }
            }

            return $carrier_codes;


            /* $client = new Client();
            $response = $client->get('https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/amzn-base.xsd'
            );

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'xsd/' .date('Y-m-d'). '_amzn-base.xsd', $contents);

                $amzn_base = simplexml_load_string($contents);
                foreach ($amzn_base->comment as $data) {

                }
            }
             */

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    /************** PUBLIC FUNCTIONS - GETTERS DEV IN DESCENDANT ***************/


    public function getItemRowProduct(ShopProduct $shop_product)
    {
        // Develop in descendant
    }


    public function getItemRowOffer(ShopProduct $shop_product, $extra_data = ['only_stocks' => false, 'buybox_price' => null])
    {
        // Develop in descendant
    }


    public function getItemRowPromo(ShopProduct $shop_product, $extra_data)
    {
        // Develop in descendant
    }



    /************** PRIVATE FUNCTIONS - AMAZON ***************/


    private function amznGetParticipations()
    {
        try {
            //$this->iam_role_config = $this->getIAMRoleConfig();
            $api = new SellersApi($this->iam_role_config);

            $res = $api->getMarketplaceParticipations();
            $this->logStorage($this->shop_dir. 'reports/', __METHOD__, $res);

            if ($res && !$res->getErrors() && $payload = $res->getPayload())
                return $payload;

            return $this->nullAndStorage(__METHOD__, $res);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function amznGetEstimatedFee($asin, $sku_mps, $amount, $currency_code = 'EUR')        // B00Z2B6JE8, 150.00, EUR
    {
        try {
            $apiInstance = new FeesApi($this->iam_role_config);

            $body = new FeesGetMyFeesEstimateRequest();
            $fees_estimate_request = new FeesFeesEstimateRequest();
            $fees_estimate_request->setMarketplaceId($this->marketplace_id);
            $price_to_estimate = new FeesPriceToEstimateFees();
            $listing_price = new FeesMoneyType();
            $listing_price->setAmount($amount);
            $listing_price->setCurrencyCode($currency_code);
            $price_to_estimate->setListingPrice($listing_price);
            $fees_estimate_request->setPriceToEstimateFees($price_to_estimate);
            $fees_estimate_request->setIdentifier($sku_mps);
            $body->setFeesEstimateRequest($fees_estimate_request);

            // Rate (requests per second): 10
            $res = $apiInstance->getMyFeesEstimateForASIN($asin, $body);

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {

                $fees_result = $payload->getFeesEstimateResult();
                if ($fees_result && !$fees_result->getError())
                    return $fees_result->getFeesEstimate()->getTotalFeesEstimate()->getAmount();

                /* $fees = $payload->getFeesEstimateResult();
                $estimated = $fees->getFeesEstimate();
                $total = $estimated->getTotalFeesEstimate();
                $amount = $total->getAmount();
                if ($detail_list = $estimated->getFeeDetailList()) {
                    foreach ($detail_list as $detail) {
                    }
                }
*/
            }

            // market_params
            // market_id, market_category_id, fee, fee_addon

            // shop_products
            // param_mp_fee, param_mp_fee_addon,

            // mp_fee:

            return $this->nullAndStorage(__METHOD__, [$asin, $sku_mps, $amount, $currency_code, $res ?? null]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$asin, $sku_mps, $amount, $currency_code, $res ?? null]);
        }
    }


    private function amznGetLastEstimatedFee()
    {
        try {
            $shop_products = $this->shop->shop_products()
                    ->whereNotNull('marketProductSku')
                    ->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)
                    ->where('is_sku_child', false)
                    ->where('stock', '>', 0)
                    ->where('created_at', '>', now()->subHours(4))//->format('Y-m-d H:i:s'))
                    ->get();

            //if ($shop_products = $this->getShopProducts4Update()->where('price', '>', 0)->where('stock', '>', 0)) {
            if ($shop_products->count()) {
                $count = 0;
                $res = [];
                foreach ($shop_products as $shop_product) {
                    if ($shop_product->created_at->gte(now()->subDays(5)) &&
                        $mp_bfit = $this->amznGetEstimatedFee($shop_product->marketProductSku, $shop_product->mps_sku, $shop_product->price)) {

                        if ($mp_bfit) {
                            $res[] = [$mp_bfit, $shop_product->marketProductSku, $shop_product->mps_sku, $shop_product->price];

                            $price = $shop_product->price;
                            $mp_bfit = (float)$mp_bfit;

                            if ($fee_calc = $this->mpFeeCalc($price, $mp_bfit)) {
                                $mp_lot = $fee_calc[0];
                                $mp_fee = $fee_calc[1];
                                $mp_lot_fee = $fee_calc[2];

                                $mp_bfit_min = 0.3;
                                $shop_product->mp_bfit = $mp_bfit;
                                $shop_product->param_mp_lot = $mp_lot;
                                $shop_product->param_mp_fee = $mp_fee * 100;
                                $shop_product->param_mp_lot_fee = $mp_lot_fee * 100;
                                $shop_product->param_mp_bfit_min = $mp_bfit_min;
                                $shop_product->save();
                            }
                        }

                        $count++;

                        // 429 QuotaExceeded
                        if ($count >= 10) {
                            sleep(1);
                            $count = 0;
                        }
                    }
                }

                $this->logStorage($this->shop_dir. 'fees/', __METHOD__, $res);
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products);
        }
    }



    private function amznGetCompetitivePricing($asins)
    {
        try {
            if (!isset($this->iam_role_config)) $this->iam_role_config = $this->getIAMRoleConfig();
            $apiInstance = new ProductPricingApi($this->iam_role_config);
            // Rate (requests per second): 10
            $res = $apiInstance->getCompetitivePricing($this->marketplace_id, 'Asin', $asins, null);
            // getPricing($this->marketplace_id, 'Asin', $asins, null, 'New');      // Preus del propi Listing

            $prices = [];
            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {

                foreach ($payload as $competitive_price) {
                    $asin = $competitive_price->getAsin();
                    if ($amzn_product = $competitive_price->getProduct()) {
                        if ($cpricing = $amzn_product->getCompetitivePricing()) {
                            $cprices = $cpricing->getCompetitivePrices();
                            foreach ($cprices as $cprice) {

                                $price = $cprice->getPrice();
                                $shipping = $price->getShipping()->getAmount();
                                $landed = $price->getLandedPrice()->getAmount();
                                $listing = $price->getListingPrice()->getAmount();

                                $competitive = ($landed > $listing) ? $listing : $landed;
                                $competitive += $shipping;

                                if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $asin)) {
                                    if ($competitive && $shop_product->price != $competitive) break;
                                }
                            }

                            $prices[$asin] = $competitive ?? null;
                        }
                    }
                }

                return $prices;
            }

            return $this->nullAndStorage(__METHOD__, $asins);

        } catch (Throwable $th) {
            // Error:429 code: "QuotaExceeded", message: "You exceeded your quota for the requested resource."
            if ($th->getCode() == 429) return $this->nullWithErrors($th, __METHOD__.'_QuotaExceeded', [$asins, json_decode($th->getMessage())]);
            return $this->nullWithErrors($th, __METHOD__, $asins);
        }
    }


    private function amznGetListingCompetitivePricing($asins)
    {
        try {
            $prices = [];
            $chunks = array_chunk($asins, 10);
            $count = 0;
            foreach ($chunks as $chunk_asins) {

                if ($res = $this->amznGetCompetitivePricing($chunk_asins))
                    $prices += $res;    //$prices = array_merge($prices, $res);
                else {
                    $this->nullAndStorage(__METHOD__.'_QuotaExceeded_pricing', [$chunk_asins]);
                    //break;
                    return $prices;
                }

                $count++;

                // QuotaExceeded ?
                if ($count >= 5) {
                    sleep(5);
                    $count = 0;
                }
            }

            return $prices;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $asins);
        }
    }


    private function amznGetItemOffers($asin)        // B00Z2B6JE8, 150.00, EUR
    {
        try {
            $apiInstance = new ProductPricingApi($this->iam_role_config);
            $res = $apiInstance->getItemOffers($this->marketplace_id, 'New', $asin);

            $lowest_amount = null;
            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {

                if ($payload->getStatus() == 'Success') {
                    $asin = $payload->getAsin();
                    $summary = $payload->getSummary();
                    $lowest_prices = $summary->getLowestPrices();

                    foreach ($lowest_prices as $lowest_price) {
                        $amount = $lowest_price->getLandedPrice()->getAmount();
                        $lowest_amount = (!isset($lowest_amount) || $amount < $lowest_amount) ? $amount : $lowest_amount;
                    }

                    return $lowest_amount;
                }
            }

            return $this->nullAndStorage(__METHOD__, $asin);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $asin);
        }
    }


    private function amznGetCatalog($asin)
    {
        try {
            //$this->iam_role_config = $this->getIAMRoleConfig();
            $api = new CatalogApi($this->iam_role_config);

            // Rate (requests per second): 5
            // $included_data: ['summaries', 'attributes', 'images', 'productTypes', 'salesRanks', 'variations', 'vendorDetails']
            // $res->getImages(): ARRAY ItemImagesByMarketplace: ['marketplace_id', 'images' => ARRAY ItemImage getLink()]
            // $res->getProductTypes(): ARRAY ItemProductTypeByMarketplace: ['marketplace_id', 'product_type' => 'PHONE']
            // attributes: Necesita tenir marca registrada
            // salesRanks: Retorna NULL
            if ($res = $api->getCatalogItem($asin, [$this->marketplace_id], ['summaries', 'images', 'productTypes', 'salesRanks']))
                return $res;    // \SellingPartnerApi\Model\Catalog\Item

            return $this->nullAndStorage(__METHOD__, $asin);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $asin);
        }
    }


    private function amznlistCatalogCategories($asin, $seller_sku)
    {
        try {
            //$asin = 'B07FWXYSM8';
            //$seller_sku = '78680-MRJQU11001-4713883722711';
            //$this->iam_role_config = $this->getIAMRoleConfig();
            $res = null;
            $api = new OldCatalogApi($this->iam_role_config);
            $res = $api->listCatalogCategories($this->marketplace_id, $asin, $seller_sku);

            if ($res && !$res->getErrors() && $payload = $res->getPayload())
                foreach ($payload as $catalog_categories)
                    return $catalog_categories->getProductCategoryId();

            return $asin;   //$this->nullAndStorage(__METHOD__, [$asin, $seller_sku]);

        } catch (Throwable $th) {
            if ($th->getCode() == 429) return $this->nullWithErrors($th, __METHOD__.'_QuotaExceeded', [$asin, $seller_sku]);
            $this->nullWithErrors($th, __METHOD__, [$asin, $seller_sku]);
            return $asin;
        }
    }


    public function amznGetAsinByEan($ean)
    {
        try {
            $apiInstance = new OldCatalogApi($this->iam_role_config);

            // CUSTOM MPE FUNCTION -> FUNCIONA ONLY 1 EAN
            //$res = $apiInstance->searchCatalogItems($this->marketplace_id, $ean);   //'4713392051395');
            //if ($res && $res->numberOfResults != 0)
            //    return $res->items[0]->asin;

            //$ean = '4713392051395';
            //$res = $apiInstance->listCatalogItems($this->marketplace_id, null, null, null, null, $ean, null, null);   //'4713392051395');
            // payload->items-> 0 ->attribute_sets->product_group => "Marcas de productos electrónicos prémium"

            //searchCatalogItems($keywords, $marketplace_ids, $included_data, $brand_names, $classification_ids, $page_size, $page_token, $keywords_locale, $locale): \SellingPartnerApi\Model\Catalog\ItemSearchResults
            // Rate (requests per second): 1
            $res = $apiInstance->listCatalogItems($this->marketplace_id, null, null, null, null, $ean);

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {

                if ($items = $payload->getItems()) {
                    foreach ($items as $item) {
                        if ($ids = $item->getIdentifiers())
                            if ($mp_asin = $ids->getMarketplaceAsin())
                                $asin = $mp_asin->getAsin();

                        if ($attr_sets = $item->getAttributeSets()) {
                            foreach ($attr_sets as $attr_set) {
                                /* $brand = $attr_set->getBrand();     // "Western Digital"
                                $color = $attr_set->getColor();     // "Azul (Azul Cerúleo)"
                                $hdi = $attr_set->getHardDiskInterface();     // "usb_3.0"
                                $color = $attr_set->getHardDiskSize();     // "value" => 2.0, "units" => "TB"
                                $idimen = $attr_set->getItemDimensions();     // height, length, width, weight
                                $list_price = $attr_set->getListPrice();     // "amount" => 89.99, "currency_code" => "EUR"
                                $model = $attr_set->getModel();     // "WDBYVG0020BBL-WESN" */
                                $pn = $attr_set->getPartNumber();     // "WDBYVG0020BBL-WESN"
                                //$market_category_type = $attr_set->getProductTypeName();     // "COMPUTER_DRIVE_OR_STORAGE"
                                //$small_image = $attr_set->getSmallImage();     // "url" => "https://m.media-amazon.com/images/I/41gI63K7N4L._SL75_.jpg"
                                $title = $attr_set->getTitle();     // "WD My Passport disco duro portátil 2TB con protección con contraseña y software de copia de seguridad automática, Azul, Compatible con PC, Xbox y PS4"
                                $brand_name = $attr_set->getBrand(); //$attr_set->getManufacturer();

                                break;
                            }
                        }

                        return [
                            'asin'  => $asin ?? null,
                            'name'  => $title ?? null,
                            'pn'    => $pn ?? null,
                            'brand_name'   => $brand_name ?? null
                        ];

                        /*
                        if ($relationships = $item->getRelationships()) {
                            foreach ($relationships as $relationship) {
                                $ids = $relationship->getIdentifiers();
                            }
                        }

                        if ($sales_rankings = $item->getSalesRankings()) {
                        } */

                    }
                }
            }


            /* $res = $apiInstance->searchCatalogItems([$ean], [$this->marketplace_id]);
            if ($res && $res->getNumberOfResults() >= 1)
                if ($items = $res->getItems()) {
                    foreach ($items as $item) {
                        $asin = $item->getAsin();
                        $summaries = $item->getSummaries();
                        foreach ($summaries as $summary) {
                            $name = $summary->getItemName();
                            $pn = $summary->getModelNumber();
                            //$brand_name = $summary->getManufacturer();
                        }

                        return [
                            'asin'  => $asin,
                            'name'  => $name,
                            'pn'    => $pn,
                        ];
                    }
                } */

            return null;        // 'NO PRODUCT'

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $ean);
        }
    }


    private function amznCreateReport($report_type, $report_options = null)
    {
        // https://github.com/amzn/selling-partner-api-docs/blob/main/references/reports-api/reportType_string_array_values.md
        // GET_XML_BROWSE_TREE_DATA                 categories
        // GET_FLAT_FILE_OPEN_LISTINGS_DATA         sku, asin, price, quantity
        // GET_MERCHANT_LISTINGS_ALL_DATA           item-name, listing-id ???, seller-sku(=mps_sku), price, quantity, ... asin1, product-id(=asin1), ... status
        // GET_MERCHANT_LISTINGS_DATA               Tab-delimited flat file detailed active listings report.
        // GET_MERCHANT_LISTINGS_INACTIVE_DATA      Tab-delimited flat file detailed inactive listings report
        // GET_MERCHANT_LISTINGS_DATA_BACK_COMPAT   Tab-delimited flat file open listings report.
        // GET_MERCHANT_LISTINGS_DATA_LITE          Tab-delimited flat file active listings report that contains only the SKU, ASIN, Price, and Quantity fields for items that have a quantity greater than zero
        // GET_MERCHANT_LISTINGS_DATA_LITER         Tab-delimited flat file active listings report that contains only the SKU and Quantity fields for items that have a quantity greater than zero
        // GET_MERCHANT_CANCELLED_LISTINGS_DATA     ab-delimited flat file canceled listings report
        // GET_MERCHANT_LISTINGS_DEFECT_DATA        Tab-delimited flat file listing quality and suppressed listing report that contains listing information that is incomplete or incorrect
        // GET_REFERRAL_FEE_PREVIEW_REPORT          Tab-delimited flat file that contains the seller's open listings as well as the price and estimated referral fees for each SKU.
        // GET_FLAT_FILE_PENDING_ORDERS_DATA        Tab-delimited flat file report that shows all pending orders

        try {
            //$config = $this->getIAMRoleConfig();
            $apiInstance = new ReportsApi($this->iam_role_config);
            $body = new CreateReportSpecification();
            $body->setReportType($report_type);     // Categories: 'GET_XML_BROWSE_TREE_DATA'
            $body->setMarketplaceIds([$this->marketplace_id]);    // optional
            if ($report_options) {
                $body->setReportOptions($report_options);   //['BrowseNodeId' => ]);
            }

            $res = $apiInstance->createReport($body);
            $this->logStorage($this->shop_dir. 'reports/', __METHOD__, $res->__toString());

            if ($res)
                return $res->getReportId();

            return $this->nullAndStorage(__METHOD__, [$report_type, $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $report_type);
        }
    }


    private function amznGetReportDocument($report_id, $report_type = 'GET_MERCHANT_LISTINGS_DATA_LITE', $count_calls = 1)
    {
        try {
            //$config = $this->getIAMRoleConfig();
            // Get Report
            $apiInstance = new ReportsApi($this->iam_role_config);
            $res = $apiInstance->getReport($report_id);
            $this->logStorage($this->shop_dir. 'reports/', __METHOD__, $res->jsonSerialize());

            if ($res && $res->getProcessingStatus() == 'IN_PROGRESS') {
                sleep(15);
                $res = $apiInstance->getReport($report_id);
                $this->logStorage($this->shop_dir. 'reports/', __METHOD__, $res->jsonSerialize());
                if (!$res || $res->getProcessingStatus() == 'IN_PROGRESS')
                    return $this->nullAndStorage(__METHOD__, ['IN_PROGRESS', $report_id]);
            }

            // 'amzn1.spdoc.1.3.7f248c30-a678-40a9-ad78-bfd87736d489.TOV3OFEU15ZCE.316'
            if ($report_document_id = $res->getReportDocumentId()) {

                // Get Report Document
                $apiInstance = new ReportsApi($this->iam_role_config);
                $feed_doc = $apiInstance->getReportDocument($report_document_id);
                $this->logStorage($this->shop_dir. 'reports/', __METHOD__.'_getReportDocument', $feed_doc->jsonSerialize());

                if ($feed_doc) {

                    //$ca = $feed_doc->getCompressionAlgorithm();
                    //$caav = $feed_doc->getCompressionAlgorithmAllowableValues();
                    //$model_name = $feed_doc->getModelName();
                    //$doc_id = $feed_doc->getReportDocumentId();
                    //$url = $feed_doc->getUrl();

                    /* if ($report_type == 'GET_XML_BROWSE_TREE_DATA') {
                        $contentType = 'text/xml';
                        $documentType = ReportType::GET_XML_BROWSE_TREE_DATA;
                    }
                    elseif ($report_type == 'GET_MERCHANT_LISTINGS_DATA_LITE') {
                        $contentType = 'text/tab-separated-values';
                        $documentType = ReportType::GET_MERCHANT_LISTINGS_DATA_LITE;
                    }
                    else {
                        $contentType = 'text/tab-separated-values';
                        $documentType = ReportType::GET_FLAT_FILE_OPEN_LISTINGS_DATA;
                    } */

                    //$report_id = $this->amznCreateReport('GET_MERCHANT_LISTINGS_DATA_LITE', $options);
                    //$inactive_report_id = $this->amznCreateReport('GET_MERCHANT_LISTINGS_INACTIVE_DATA', $options);
                    //$canceled_report_id = $this->amznCreateReport('GET_MERCHANT_CANCELLED_LISTINGS_DATA', $options);
                    //$defect_report_id = $this->amznCreateReport('GET_MERCHANT_LISTINGS_DEFECT_DATA', $options);


                    $contentType = 'text/tab-separated-values';
                    if ($report_type == 'GET_XML_BROWSE_TREE_DATA') $contentType = 'text/xml';

                    //$documentType = null;
                    switch ($report_type) {
                        case 'GET_XML_BROWSE_TREE_DATA':
                            $documentType = ReportType::GET_XML_BROWSE_TREE_DATA;
                            break;

                        case 'GET_MERCHANT_LISTINGS_ALL_DATA':
                            $documentType = ReportType::GET_MERCHANT_LISTINGS_ALL_DATA;
                            break;

                        case 'GET_MERCHANT_LISTINGS_DATA_LITE':
                            $documentType = ReportType::GET_MERCHANT_LISTINGS_DATA_LITE;
                            break;

                        case 'GET_MERCHANT_LISTINGS_INACTIVE_DATA':
                            $documentType = ReportType::GET_MERCHANT_LISTINGS_INACTIVE_DATA;
                            break;

                        case 'GET_MERCHANT_CANCELLED_LISTINGS_DATA':
                            $documentType = ReportType::GET_MERCHANT_CANCELLED_LISTINGS_DATA;
                            break;

                        case 'GET_MERCHANT_LISTINGS_DEFECT_DATA':
                            $documentType = ReportType::GET_MERCHANT_LISTINGS_DEFECT_DATA;
                            break;

                        default:
                            $documentType = ReportType::GET_FLAT_FILE_OPEN_LISTINGS_DATA;
                            break;
                    }

                    $docToDownload = new Document($feed_doc, $documentType);
                    $contents = $docToDownload->download();  // The raw report text

                    // SimpleXMLElement || Array
                    $data = $docToDownload->getData();

                    $filename = $this->shop_dir.'feeds/'.date('Y-m-d_H-i'). '_document';
                    if ($contentType == 'text/xml')
                        Storage::append($filename.'.xml', $data->asXML());
                    else
                        Storage::append($filename.'.csv', json_encode($data));

                    return $data;
                }
            }

            return $this->nullAndStorage(__METHOD__, $report_id);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $report_id);
        }
    }


    /************** PRIVATE FUNCTIONS - ORDERS ***************/


    private function amzncreateRestrictedDataToken()
    {
        // Necessary FOR GET Buyer & Shipping INFO
        try {
            $apiInstance = new TokensApi($this->iam_role_config);

            $body = new CreateRestrictedDataTokenRequest();
            $RestrictedResource = new RestrictedResource();
            $RestrictedResource->setPath('/orders/v0/orders');
            $RestrictedResource->setMethod('GET');
            $body->setRestrictedResources($RestrictedResource);

            if ($res = $apiInstance->createRestrictedDataToken($body)) {
                $this->RDT = $res->getRestrictedDataToken();

                return $this->RDT;
            }

            return $this->nullAndStorage(__METHOD__, null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function amznGetOrders()
    {
        try {
            //$RDT = $this->amzncreateRestrictedDataToken();
            $apiInstance = new OrdersApi($this->iam_role_config);
            // created_before" => "2021-06-17T10:02:24Z"
            $res = $apiInstance->getOrders([$this->marketplace_id], null, null, Carbon::now()->subDays(1)->format('Y-m-d\TH:i:s'));
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res);

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                // SellingPartnerApi\Model\Orders\OrdersList
                return $payload->getOrders();
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function amznGetOrder($amzn_order_id)
    {
        try {
            $apiInstance = new OrdersApi($this->iam_role_config);
            // created_before" => "2021-06-17T10:02:24Z"
            $res = $apiInstance->getOrder($amzn_order_id);
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                // SellingPartnerApi\Model\Orders\OrdersList
                return $payload;
            }

            return $this->nullAndStorage(__METHOD__, [$amzn_order_id, $res ?? null]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$amzn_order_id, $res ?? null]);
        }
    }


    private function amznGetOrderBuyerInfo($amzn_order_id)
    {
        try {
            $apiInstance = new OrdersApi($this->iam_role_config);
            // created_before" => "2021-06-17T10:02:24Z"
            // Rate (requests per second): 0.0055 -> 1 cada 3 minuts
            // We recommend using the getOrders operation to get buyer information for an order, as the getOrderBuyerInfo operation is scheduled for deprecation on January 12, 2022. For more information, see the Tokens API Use Case Guide.
            $res = $apiInstance->getOrderBuyerInfo($amzn_order_id);
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                // SellingPartnerApi\Model\Orders\OrdersList
                return [
                    'name'            => $payload->getBuyerName(),
                    'email'           => $payload->getBuyerEmail(),
                    'county'          => $payload->getBuyerCounty(),
                    'purchase_order_number' => $payload->getPurchaseOrderNumber()
                ];
            }

            return $this->nullAndStorage(__METHOD__, [$amzn_order_id, $res ?? null]);

        } catch (Throwable $th) {
            // Error:429 code: "QuotaExceeded", message: "You exceeded your quota for the requested resource."
            if ($th->getCode() == 429) return $this->nullWithErrors($th, __METHOD__.'_QuotaExceeded', null);
            return $this->nullWithErrors($th, __METHOD__, [$amzn_order_id, $res ?? null]);
        }
    }


    private function amznGetOrderShippingAddress($amzn_order_id)
    {
        try {
            $apiInstance = new OrdersApi($this->iam_role_config);
            // created_before" => "2021-06-17T10:02:24Z"
            // Rate (requests per second): 0.0055 - 1 cada 3 minuts
            // We recommend using the getOrders operation to get shipping address information for an order, as the getOrderAddress operation is scheduled for deprecation on January 12, 2022. For more information, see the Tokens API Use Case Guide.
            $res = $apiInstance->getOrderAddress($amzn_order_id);
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                // SellingPartnerApi\Model\Orders\OrdersList
                return $payload->getShippingAddress();
            }

            return $this->nullAndStorage(__METHOD__, [$amzn_order_id, $res ?? null]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$amzn_order_id, $res ?? null]);
        }
    }


    private function amznGetOrderItemsBuyerInfo($amzn_order_id)
    {
        try {
            $apiInstance = new OrdersApi($this->iam_role_config);
            // created_before" => "2021-06-17T10:02:24Z"
            $res = $apiInstance->getOrderItemsBuyerInfo($amzn_order_id);
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                // SellingPartnerApi\Model\Orders\OrdersList

                // ARRAY: SellingPartnerApi\Model\Orders\OrderItemBuyerInfo
                // "order_item_id", "buyer_customized_info", "gift_wrap_price", "gift_wrap_tax", "gift_message_text", "gift_wrap_level"
                return $payload->getOrderItems();
            }

            return $this->nullAndStorage(__METHOD__, [$amzn_order_id, $res ?? null]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$amzn_order_id, $res ?? null]);
        }
    }


    private function amznGetOrderItems($amzn_order_id)
    {
        try {
            $apiInstance = new OrdersApi($this->iam_role_config);
            // created_before" => "2021-06-17T10:02:24Z"
            $res = $apiInstance->getOrderItems($amzn_order_id);
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                // SellingPartnerApi\Model\Orders\OrdersList
                return $payload->getOrderItems();
            }

            return $this->nullAndStorage(__METHOD__, [$amzn_order_id, $res ?? null]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$amzn_order_id, $res ?? null]);
        }
    }


    private function amznGetShippingAccount()
    {
        try {
            $apiInstance = new ShippingApi($this->iam_role_config);
            // created_before" => "2021-06-17T10:02:24Z"
            $res = $apiInstance->getAccount();
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            return $res;

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                // SellingPartnerApi\Model\Orders\OrdersList
                return $payload->getAccountId();
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function amznGetShippingRates()
    {
        try {
            $apiInstance = new ShippingApi($this->iam_role_config);
            // created_before" => "2021-06-17T10:02:24Z"
            $body = new GetRatesRequest();
            //$specs = new ContainerSpecification();
            //$dim = new Dimensions();
            //$dim->setHeight(2); $dim->setLength(1); $dim->setUnit(2); $dim->setWidth(3);
            //$specs->setDimensions($dim);
            //$body->setContainerSpecifications($specs);
            //$types = [ServiceType::GROUND, ServiceType::STANDARD, ServiceType::PREMIUM];
            //$body->setServiceTypes($types);
            /* $body->setShipDate(now()->format('Y-m-d\TH:i:s\Z'));
            $from_address = new ShippingAddress();
            $from_address->setCountryCode('ES'); */
            //$body->setShipFrom($from_address);
            $to_address = new ShippingAddress();
            $to_address->setCountryCode('ES');
            $to_address->setPostalCode('28860');
            $body->setShipTo($to_address);
            $res = $apiInstance->getRates($body);
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            return $res;

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                // SellingPartnerApi\Model\Orders\OrdersList
                return $payload->getServiceRates();
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function amznCreateShipment()
    {
        try {
            $apiInstance = new ShippingApi($this->iam_role_config);
            // created_before" => "2021-06-17T10:02:24Z"
            $body = new CreateShipmentRequest();
            $ship_to_address = new ShippingAddress();
            $ship_to_address->setCountryCode('ES');
            $ship_to_address->setPostalCode('28860');
            $body->setShipTo($ship_to_address);
            $res = $apiInstance->createShipment($body);
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            return $res;

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                // SellingPartnerApi\Model\Orders\OrdersList
                return $payload->getShipmentId();
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function firstOrCreateAddress($amzn_shipping_address)
    {
        try {
            if ($country_code = $amzn_shipping_address->getCountryCode())
                $country = Country::firstOrCreate([
                    'code'      => $country_code,
                ],[]);

            $name = $amzn_shipping_address->getName();
            $address_line1 = $amzn_shipping_address->getAddressLine1();
            $city = $amzn_shipping_address->getCity();
            if ($name || $address_line1 || $city)
                return Address::updateOrCreate([
                    'country_id'            => $country->id ?? null,
                    'market_id'             => $this->market->id,
                    'marketBuyerId'         => null,
                    'name'                  => $name ?? $address_line1 ?? $city,
                ],[
                    'address1'              => $amzn_shipping_address->getAddressLine1(),
                    'address2'              => $amzn_shipping_address->getAddressLine2(),
                    'address3'              => $amzn_shipping_address->getAddressLine3(),
                    'city'                  => $amzn_shipping_address->getCity(),
                    'state'                 => $amzn_shipping_address->getStateOrRegion(),
                    'zipcode'               => $amzn_shipping_address->getPostalCode(),
                    'phone'                 => $amzn_shipping_address->getPhone(),
                    'district'              => $amzn_shipping_address->getDistrict(),
                    'municipality'          => $amzn_shipping_address->getMunicipality(),
                ]);

            return $this->nullAndStorage(__METHOD__, $amzn_shipping_address);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $amzn_shipping_address);
        }
    }


    private function updateOrCreateOrder(OrdersOrder $amzn_order)
    {
        try {
            // ORDER STATUSES
            // PendingAvailability (This status is available for pre-orders only. The order has been placed, payment has not been authorized, and the release date of the item is in the future.);
            // Pending (The order has been placed but payment has not been authorized);
            // Unshipped (Payment has been authorized and the order is ready for shipment, but no items in the order have been shipped);
            // PartiallyShipped (One or more, but not all, items in the order have been shipped);
            // Shipped (All items in the order have been shipped);
            // InvoiceUnconfirmed (All items in the order have been shipped. The seller has not yet given confirmation to Amazon that the invoice has been shipped to the buyer.);
            // Canceled (The order has been canceled);
            // Unfulfillable (The order cannot be fulfilled. This state applies only to Multi-Channel Fulfillment orders.).

            $amzn_order_id = $amzn_order->getAmazonOrderId();

            $marketStatusName = $amzn_order->getOrderStatus();
            $status = Status::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketStatusName'      => $marketStatusName,
                'type'                  => 'order',
            ],[
                'name'                  => $marketStatusName,
            ]);

            $price = 0;
            if ($amzn_order_total = $amzn_order->getOrderTotal()) {
                $price = (float)$amzn_order_total->getAmount() ?? 0;
                $currency = Currency::firstOrCreate([
                    'code'             => $amzn_order_total->getCurrencyCode(),
                ],[]);
            }

            $order = $this->shop->orders()->where('marketOrderId', $amzn_order_id)->first();
            $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;

            //$shipping_price = (float)$order_info->result->data->logistics_amount->amount ?? 0;
            //Storage::append('errors/AMAZON_ORDERS_TEST.json', [$notified, $notified_updated, $status->toArray()]);

            $order = Order::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'marketOrderId'         => $amzn_order_id,
            ],[
                //'buyer_id'              => $buyer->id ?? null,
                //'shipping_address_id'   => $address->id ?? null,
                'billing_address_id'    => null,
                'currency_id'           => $currency->id ?? 1,
                'status_id'             => $status->id ?? null,
                'type_id'               => null,
                'SellerId'              => null,
                'SellerOrderId'         => $amzn_order->getSellerOrderId(),
                'info'                  => null, //$order_info->result->data->memo ?? '',   // BUYER message
                'price'                 => (float)$price,
                'tax'                   => 0,
                'shipping_price'        => 0,
                'shipping_tax'          => 0,       // mp_shipping_fee: isset($order_info) ? (floatval($order_info->result->data->logisitcs_escrow_fee_rate)*100) : 0,
                'notified'              => $notified,
                'notified_updated'      => $notified_updated,
            ]);

            // Shipping Address
            /* if (!$order->shipping_address_id) {

                if ($amzn_shipping_address = $this->amznGetOrderShippingAddress($amzn_order_id)) {
                    $address = $this->firstOrCreateAddress($amzn_shipping_address);

                    $order->shipping_address_id = $address->id;
                    $order->save();
                }
            } */

            // Buyer
            /* if (!$order->buyer_id || !$order->buyer->shipping_address_id) {

                if ($amzn_buyer = $this->amznGetOrderBuyerInfo($amzn_order_id)) {
                    if ($amzn_buyer['email'] || $amzn_buyer['name']) {
                        $buyer = Buyer::firstOrCreate([
                            'market_id'             => $this->market->id,
                            'marketBuyerId'         => null,
                            'name'                  => $amzn_buyer['name'] ?? $amzn_buyer['email'],
                            'email'                 => $amzn_buyer['email'] ?? null,
                        ],[
                            // ES shopper OR Real name
                            'shipping_address_id'   => $address->id ?? null,
                            //'billing_address_id'    => null,
                            'phone'                 => $address->phone ?? null,

                            //'company_name'          => null,
                            //'tax_region'            => null,
                            //'tax_name'              => null,
                            //'tax_value'             => null,
                        ]);

                        $order->buyer_id = $buyer->id;
                        $order->save();
                    }
                }
            } */

            // 2021-07-19T18:21:49Z
            // Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $amzn_order->getPurchaseDate())->format('Y-m-d H:i:s')  WORKS FINE
            $order->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $amzn_order->getPurchaseDate());
            $order->updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $amzn_order->getLastUpdateDate());
            $order->save();

            //$this->amznGetOrderItemsBuyerInfo($amzn_order_id);

            $order_items_count = 0;
            if ($amzn_order_items = $this->amznGetOrderItems($amzn_order_id)) {

                foreach ($amzn_order_items as $amzn_order_item) {

                    $mps_sku = $amzn_order_item->getSellerSku();
                    $product_info = $amzn_order_item->getProductInfo();

                    $mp_bfit = null;
                    $mp_fee = null;
                    if ($cod_fee = $amzn_order_item->getCodFee()) {
                        $mp_bfit = (float)$cod_fee->getAmount() ?? null;
                        $mp_fee = (float)($mp_bfit / $order->price);
                        $mp_fee *= 100;
                    }

                    $amzn_item_price_amount = 0;
                    if ($amzn_item_price = $amzn_order_item->getItemPrice())
                        $amzn_item_price_amount = (float)$amzn_item_price->getAmount();

                    $amzn_item_tax_amount = 0;
                    if ($amzn_item_tax = $amzn_order_item->getItemTax())
                        $amzn_item_tax_amount = (float)$amzn_item_tax->getAmount();

                    $amzn_item_shipping_price_amount = 0;
                    if ($amzn_item_shhipping_price = $amzn_order_item->getShippingPrice())
                        $amzn_item_shipping_price_amount = (float)$amzn_item_shhipping_price->getAmount();

                    $amzn_item_shipping_tax_amount = 0;
                    if ($amzn_item_shipping_tax = $amzn_order_item->getShippingTax())
                        $amzn_item_shipping_tax_amount = (float)$amzn_item_shipping_tax->getAmount();

                    $amzn_item_promotion_discount_amount = 0;
                    if ($amzn_item_promotion_discount = $amzn_order_item->getPromotionDiscount())
                        $amzn_item_promotion_discount_amount = (float)$amzn_item_promotion_discount->getAmount();

                    $amzn_order_item_promotion_discount_tax_amount = 0;
                    if ($amzn_order_item_promotion_discount_tax = $amzn_order_item->getPromotionDiscountTax())
                        $amzn_order_item_promotion_discount_tax_amount = (float)$amzn_order_item_promotion_discount_tax->getAmount();

                    $order_item = $order->updateOrCreateOrderItem(
                        $amzn_order_item->getOrderItemId(),
                        $mps_sku,
                        $amzn_order_item->getAsin(),
                        $amzn_order_item->getTitle(),
                        $amzn_order_item->getQuantityOrdered(),
                        $amzn_item_price_amount,
                        $amzn_item_tax_amount,
                        $amzn_item_shipping_price_amount,
                        $amzn_item_shipping_tax_amount,
                        $amzn_order_item->getConditionNote(),
                        ['mp_fee' => $mp_fee, 'mp_bfit' => $mp_bfit]
                    );

                    $order_items_count++;
                }
            }

            return $order_items_count;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $amzn_order->__toString());
        }
    }


    /**************** AMAZON FEEDS & BUILDS *****************/


    private function amznBuildEnvelope($messageType, $purge = false)
    {
        try {
            // amzn-envelope.xsd
            $envelope = new SimpleXMLElement('<?xml version="1.0" ?><AmazonEnvelope></AmazonEnvelope>');
            $envelope->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $envelope->addAttribute('xsi:xsi:noNamespaceSchemaLocation', 'amzn-envelope.xsd');

            // amzn-header.xsd
            $Header = $envelope->addChild('Header');
            $Header->addChild('DocumentVersion', '1.01');
            $Header->addChild('MerchantIdentifier', $this->seller_id);

            // Image, Inventory, Override, Price, Product, ProductImage, ....
            $envelope->addChild('MessageType', $messageType);
            if ($purge) $envelope->addChild('PurgeAndReplace', true);

            return $envelope;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $messageType);
        }
    }


    private function amznBuildProductFeed(SimpleXMLElement &$feedContent, ShopProduct $shop_product, $OperationType = 'Update')
    {
        // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/Product.xsd
        // https://gist.github.com/kitwalker12/87a6939540a948cac7041459aa718189
        // https://github.com/dmichael/amazon-mws/tree/master/examples/xsd

        // Message: Price
        $Message = $feedContent->addChild('Message');
        $Message->addChild('MessageID', hexdec(uniqid()));       // d{1,20}     uniqid() -> 13d HEX
        // Update, Delete, PartialUpdate
        $Message->addChild('OperationType', $OperationType);
        //$Message->addChild('PurgeAndReplace', true);

        $Product = $Message->addChild('Product');
        $Product->addChild('SKU', $this->getShopProductMpsSku($shop_product));      //$shop_product->getMPSSku(40));
        $Product->addChild('Condition', 'New');    // 11 Nuevo

        $StandardProductID = $Product->addChild('StandardProductID');
        $Type = $StandardProductID->addChild('Type', 'EAN');
        $Value = $StandardProductID->addChild('Value', $shop_product->product->ean);

        //$ProductTaxCode = $Product->addChild('ProductTaxCode', 'A_GEN_NOTAX');  // Not used in Canada, Europe, or Japan

        $DescriptionData = $Product->addChild('DescriptionData');
        $Title = $DescriptionData->addChild('Title', FacadesMpe::buildString($shop_product->buildTitle()));
        $Description = $DescriptionData->addChild('Description', FacadesMpe::buildText($shop_product->buildDescription4Mobile()));
        $Brand = $DescriptionData->addChild('Brand', $shop_product->product->brand->name);
        $Manufacturer = $DescriptionData->addChild('Manufacturer', $shop_product->product->brand->name);
        if (isset($shop_product->product->pn))
            $MfrPartNumber = $DescriptionData->addChild('MfrPartNumber', $shop_product->product->pn);
        //$BulletPoint = $DescriptionData->addChild('BulletPoint', FacadesMpe::buildText($shop_product->buildDescription4Mobile()));
        //$BulletPoint = $DescriptionData->addChild('BulletPoint', FacadesMpe::buildText($shop_product->buildDescription4Mobile()));
        $MSRP = $DescriptionData->addChild('MSRP', ($shop_product->price / 1.21 ) / 0.95);
        $MSRPWithTax = $DescriptionData->addChild('MSRPWithTax', $shop_product->price / 0.95);
        //$ItemType = $DescriptionData->addChild('ItemType', 'standard-laptop-computers');
        // $SearchTerms = $DescriptionData->addChild('SearchTerms', 'standard-laptop-computers');

        if (isset($shop_product->product->pn)) $DescriptionData->addChild('SearchTerms', $shop_product->product->pn);
        $DescriptionData->addChild('SearchTerms', $shop_product->product->ean);
        $DescriptionData->addChild('SearchTerms', $shop_product->product->brand->name);


        if (isset($shop_product->market_category_id)) {
            $DescriptionData->addChild('SearchTerms', $shop_product->market_category->name);
            $DescriptionData->addChild('SearchTerms', $shop_product->market_category->root_category->name);

            // $PlatinumKeywords = $DescriptionData->addChild('PlatinumKeywords', 'standard-laptop-computers');
            $RecommendedBrowseNode = $DescriptionData->addChild('RecommendedBrowseNode', $shop_product->market_category->marketCategoryId);
        }

        $ProductData = $Product->addChild('ProductData');
        $Computers = $ProductData->addChild('Computers');
        $ProductType = $Computers->addChild('ProductType');
        $NotebookComputer = $ProductType->addChild('NotebookComputer');
        //$Color = $Computers->addChild('Color', 'White');
        //$ComputerCpuType = $Computers->addChild('ComputerCpuType', 'Core_2_Quad_Q9000');


        /* <Message>
            <MessageID>1</MessageID>
            <OperationType>Update</OperationType>
            <Product>
                <SKU>RO7WA11930KB1CASA</SKU>
                <StandardProductID>
                    <Type>UPC</Type>
                    <Value>4015643103921</Value>
                </StandardProductID>
                <ProductTaxCode>A_GEN_NOTAX</ProductTaxCode>
                <DescriptionData>
                    <Title>Example Product Title</Title>
                    <Brand>Example Product Brand</Brand>
                    <Description>This is an example product description.</Description>
                    <BulletPoint>Example Bullet Point 1</BulletPoint>
                    <BulletPoint>Example Bullet Point 2</BulletPoint>
                    <MSRP currency="USD">25.19</MSRP>
                    <Manufacturer>Example Product Manufacturer</Manufacturer>
                    <ItemType>example-item-type</ItemType>
                </DescriptionData>
                <ProductData>
                    <Health>
                        <ProductType>
                            <HealthMisc>
                                <Ingredients>Example Ingredients</Ingredients>
                                <Directions>Example Directions</Directions>
                            </HealthMisc>
                        </ProductType>
                    </Health>
                </ProductData>
            </Product>
        </Message> */



        // Discount Price
        /* $Sale = $Price->addChild('Sale');
        $SalePrice = $Sale->addChild('SalePrice', $shop_product->price);
        $StartDate = $Sale->addChild('StartDate', Carbon::now()->format('Y-m-d\TH:i:s'));
        $EndDate = $Sale->addChild('EndDate', Carbon::now()->addDays(10)->format('Y-m-d\TH:i:s')); */

        return true;
    }


    private function amznBuildProductOfferFeed(SimpleXMLElement &$feedContent, ShopProduct $shop_product, $OperationType = 'Update')
    {
        // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/Product.xsd
        // https://gist.github.com/kitwalker12/87a6939540a948cac7041459aa718189
        // https://github.com/dmichael/amazon-mws/tree/master/examples/xsd

        // Message: Price
        $Message = $feedContent->addChild('Message');
        $Message->addChild('MessageID', hexdec(uniqid()));       // d{1,20}     uniqid() -> 13d HEX
        // Update, Delete, PartialUpdate
        $Message->addChild('OperationType', $OperationType);
        //$Message->addChild('PurgeAndReplace', true);

        $Product = $Message->addChild('Product');

        $mps_sku = $this->getShopProductMpsSku($shop_product);
        $Product->addChild('SKU', $mps_sku);      //$shop_product->getMPSSku(40));

        $Product->addChild('Condition', 'New');    // 11 Nuevo
        $Product->addChild('ItemPackageQuantity', '1');
        $Product->addChild('NumberOfItems', '1');

        $StandardProductID = $Product->addChild('StandardProductID');
        $Type = $StandardProductID->addChild('Type', 'EAN');
        $Value = $StandardProductID->addChild('Value', $shop_product->product->ean);

        //$ProductTaxCode = $Product->addChild('ProductTaxCode', 'A_GEN_NOTAX');  // Not used in Canada, Europe, or Japan

        /* $DescriptionData = $Product->addChild('DescriptionData');
        $Title = $DescriptionData->addChild('Title', FacadesMpe::buildString($shop_product->buildTitle()));
        $Description = $DescriptionData->addChild('Description', FacadesMpe::buildText($shop_product->buildDescription4Mobile()));
        $Brand = $DescriptionData->addChild('Brand', $shop_product->product->brand->name);
        $Manufacturer = $DescriptionData->addChild('Manufacturer', $shop_product->product->brand->name);
        if (isset($shop_product->product->pn))
            $MfrPartNumber = $DescriptionData->addChild('MfrPartNumber', $shop_product->product->pn);
        //$BulletPoint = $DescriptionData->addChild('BulletPoint', FacadesMpe::buildText($shop_product->buildDescription4Mobile()));
        //$BulletPoint = $DescriptionData->addChild('BulletPoint', FacadesMpe::buildText($shop_product->buildDescription4Mobile()));
        $MSRP = $DescriptionData->addChild('MSRP', ($shop_product->price / 1.21 ) / 0.95);
        $MSRPWithTax = $DescriptionData->addChild('MSRPWithTax', $shop_product->price / 0.95);
        //$ItemType = $DescriptionData->addChild('ItemType', 'standard-laptop-computers');
        // $SearchTerms = $DescriptionData->addChild('SearchTerms', 'standard-laptop-computers');

        if (isset($shop_product->product->pn)) $DescriptionData->addChild('SearchTerms', $shop_product->product->pn);
        $DescriptionData->addChild('SearchTerms', $shop_product->product->ean);
        $DescriptionData->addChild('SearchTerms', $shop_product->product->brand->name);


        if (isset($shop_product->market_category_id)) {
            $DescriptionData->addChild('SearchTerms', $shop_product->market_category->name);
            $DescriptionData->addChild('SearchTerms', $shop_product->market_category->root_category->name);

            // $PlatinumKeywords = $DescriptionData->addChild('PlatinumKeywords', 'standard-laptop-computers');
            $RecommendedBrowseNode = $DescriptionData->addChild('RecommendedBrowseNode', $shop_product->market_category->marketCategoryId);
        } */

        /* $ProductData = $Product->addChild('ProductData');
        $Computers = $ProductData->addChild('Computers');
        $ProductType = $Computers->addChild('ProductType');
        $NotebookComputer = $ProductType->addChild('NotebookComputer'); */
        //$Color = $Computers->addChild('Color', 'White');
        //$ComputerCpuType = $Computers->addChild('ComputerCpuType', 'Core_2_Quad_Q9000');


        /* <Message>
            <MessageID>1</MessageID>
            <OperationType>Update</OperationType>
            <Product>
                <SKU>RO7WA11930KB1CASA</SKU>
                <StandardProductID>
                    <Type>UPC</Type>
                    <Value>4015643103921</Value>
                </StandardProductID>
                <ProductTaxCode>A_GEN_NOTAX</ProductTaxCode>
                <DescriptionData>
                    <Title>Example Product Title</Title>
                    <Brand>Example Product Brand</Brand>
                    <Description>This is an example product description.</Description>
                    <BulletPoint>Example Bullet Point 1</BulletPoint>
                    <BulletPoint>Example Bullet Point 2</BulletPoint>
                    <MSRP currency="USD">25.19</MSRP>
                    <Manufacturer>Example Product Manufacturer</Manufacturer>
                    <ItemType>example-item-type</ItemType>
                </DescriptionData>
                <ProductData>
                    <Health>
                        <ProductType>
                            <HealthMisc>
                                <Ingredients>Example Ingredients</Ingredients>
                                <Directions>Example Directions</Directions>
                            </HealthMisc>
                        </ProductType>
                    </Health>
                </ProductData>
            </Product>
        </Message> */



        // Discount Price
        /* $Sale = $Price->addChild('Sale');
        $SalePrice = $Sale->addChild('SalePrice', $shop_product->price);
        $StartDate = $Sale->addChild('StartDate', Carbon::now()->format('Y-m-d\TH:i:s'));
        $EndDate = $Sale->addChild('EndDate', Carbon::now()->addDays(10)->format('Y-m-d\TH:i:s')); */

        return true;
    }


    private function amznBuildProductImageFeed(SimpleXMLElement &$feedContent, ShopProduct $shop_product, $OperationType = 'Update')
    {
        // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/ProductImage.xsd

        $count = 0;
        $images = $shop_product->product->getAllUrlImages(6)->toArray();
        foreach ($images as $image) {
            // Message: ProductImage
            $Message = $feedContent->addChild('Message');
            $Message->addChild('MessageID', hexdec(uniqid()));       // d{1,20}     uniqid() -> 13d HEX
            // Update, Delete, PartialUpdate
            $Message->addChild('OperationType', $OperationType);

            $ProductImage = $Message->addChild('ProductImage');
            $ProductImage->addChild('SKU', $this->getShopProductMpsSku($shop_product));
            $ImageType = ($count == 0) ? 'Main' : ('PT' .$count);
            $ProductImage->addChild('ImageType', $ImageType);
            $ProductImage->addChild('ImageLocation', $image);

            $count++;
        }


        return true;
    }


    private function amznBuildPriceFeed(SimpleXMLElement &$feedContent, ShopProduct $shop_product, $OperationType = 'Update')
    {
        // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/Price.xsd
        // https://github.com/dmichael/amazon-mws/tree/master/examples/xsd

        $shop_product->setPriceStock();
        if ($shop_product->buybox_price != 0) {
            $shop_product->setReprice();
        }

        // Message: Price
        $Message = $feedContent->addChild('Message');
        $Message->addChild('MessageID', hexdec(uniqid()));       // d{1,20}     uniqid() -> 13d HEX
        // Update, Delete, PartialUpdate
        $Message->addChild('OperationType', $OperationType);

        $Price = $Message->addChild('Price');

        $mps_sku = $this->getShopProductMpsSku($shop_product);
        $Price->addChild('SKU', $mps_sku);      //$shop_product->getMPSSku(40));
        $standardPrice = $Price->addChild('StandardPrice', $shop_product->price);        //number_format($shop_product->price, 2, ",", ""));
        $standardPrice->addAttribute('currency', $shop_product->currency->code);

        // Discount Price
        if ($shop_product->param_discount_price != 0 && $shop_product->param_starts_at && $shop_product->param_ends_at) {
            $Sale = $Price->addChild('Sale');
            // Carbon::createFromFormat('Y-m-d\TH:i:s\Z');              //format('Y-m-d\TH:i:s.uZ'));
            // 2002-10-10T12:00:00Z
            $StartDate = $Sale->addChild('StartDate', $shop_product->param_starts_at->format('Y-m-d').'T00:00:00Z');
            $EndDate = $Sale->addChild('EndDate', $shop_product->param_ends_at->format('Y-m-d').'T00:00:00Z');

            $SalePrice = $Sale->addChild('SalePrice', $shop_product->discount_price);
            $SalePrice->addAttribute('currency', $shop_product->currency->code);
        }

        //$Message->addChild('BaseCurrencyCodeWithDefault', $shop_product->currency->code);

        return true;
    }


    private function amznBuildInventoryFeed(SimpleXMLElement &$feedContent, ShopProduct $shop_product, $OperationType = 'Update')
    {
        // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/Inventory.xsd

        // Message: Price
        $Message = $feedContent->addChild('Message');
        $Message->addChild('MessageID', hexdec(uniqid()));       // d{1,20}     uniqid() -> 13d HEX
        // Update, Delete, PartialUpdate
        $Message->addChild('OperationType', $OperationType);

        $Inventory = $Message->addChild('Inventory');
        $mps_sku = $this->getShopProductMpsSku($shop_product);
        $Inventory->addChild('SKU', $mps_sku);
        $Quantity = $Inventory->addChild('Quantity', $shop_product->stock);
        $FulfillmentLatency = $Inventory->addChild('FulfillmentLatency', 1);

        return true;
    }


    private function brandsAreEqual($amzn_brand_name, $mpe_brand_name)
    {
        if ($mpe_brand_name == 'Engel axil') $mpe_brand_name = 'Engel';
        if ($mpe_brand_name == 'Creative Labs') $mpe_brand_name = 'Creative';

        if ($amzn_brand_name == 'S3Plus') $amzn_brand_name = 'S3 PLUS';

        if ($amzn_brand_name == 'CAT PHONES') $amzn_brand_name = 'CAT';
        if ($amzn_brand_name == 'Gigabyte Technology') $amzn_brand_name = 'Gigabyte';
        if ($amzn_brand_name == 'WD BLACK') $amzn_brand_name = 'Western Digital';
        if ($amzn_brand_name == 'Unykach') $amzn_brand_name = 'Unyka';

        $amzn_brand_name = mb_strtoupper($amzn_brand_name);
        $mpe_brand_name = mb_strtoupper($mpe_brand_name);

        if (mb_strpos($amzn_brand_name, $mpe_brand_name) !== false || mb_strpos($mpe_brand_name, $amzn_brand_name) !== false)
            return true;

        if (
            (mb_strpos($amzn_brand_name, 'TOSHIBA') !== false || mb_strpos($amzn_brand_name, 'DYNABOOK') !== false) &&
            (mb_strpos($mpe_brand_name, 'TOSHIBA') !== false || mb_strpos($mpe_brand_name, 'DYNABOOK') !== false)) {
                return true;
        }

        if (
            (mb_strpos($amzn_brand_name, 'IBM') !== false || mb_strpos($amzn_brand_name, 'LENOVO') !== false) &&
            (mb_strpos($mpe_brand_name, 'IBM') !== false || mb_strpos($mpe_brand_name, 'LENOVO') !== false)) {
                return true;
        }

        if (
            (mb_strpos($amzn_brand_name, 'KINGSTON') !== false || mb_strpos($amzn_brand_name, 'HYPERX') !== false) &&
            (mb_strpos($mpe_brand_name, 'KINGSTON') !== false || mb_strpos($mpe_brand_name, 'HYPERX') !== false)) {
                return true;
        }

        if (
            (mb_strpos($amzn_brand_name, 'D-LINK') !== false || mb_strpos($amzn_brand_name, 'DLINK') !== false) &&
            (mb_strpos($mpe_brand_name, 'D-LINK') !== false || mb_strpos($mpe_brand_name, 'DLINK') !== false)) {
                return true;
        }

        if (
            (mb_substr($amzn_brand_name, 0, 2) == 'HP' || mb_strpos($amzn_brand_name, 'HEWLETT') !== false || mb_strpos($amzn_brand_name, 'ARUBA') !== false) &&
            (mb_substr($mpe_brand_name, 0, 2) == 'HP' || mb_strpos($mpe_brand_name, 'HEWLETT') !== false)) {
                return true;
        }

        if (
            (mb_substr($amzn_brand_name, 0, 5) == 'URBAN' || mb_strpos($amzn_brand_name, 'UAG') !== false) &&
            (mb_substr($mpe_brand_name, 0, 5) == 'URBAN' || mb_strpos($mpe_brand_name, 'UAG') !== false)) {
                return true;
        }

        if ((mb_substr($amzn_brand_name, 0, 4) == 'DELL' && mb_substr($mpe_brand_name, 0, 4) == 'DELL') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'IRIS' && mb_substr($mpe_brand_name, 0, 4) == 'IRIS') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'TRUST' && mb_substr($mpe_brand_name, 0, 5) == 'TRUST') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'UBIQUITI' && mb_substr($mpe_brand_name, 0, 8) == 'UBIQUITI') ||
            (mb_substr($amzn_brand_name, 0, 9) == 'HIKVISION' && mb_substr($mpe_brand_name, 0, 9) == 'HIKVISION') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'QNAP' && mb_substr($mpe_brand_name, 0, 4) == 'QNAP') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'MOTOROLA' && mb_substr($mpe_brand_name, 0, 8) == 'MOTOROLA') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'KODAK' && mb_substr($mpe_brand_name, 0, 5) == 'KODAK') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'AXIS' && mb_substr($mpe_brand_name, 0, 4) == 'AXIS') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'LOGITECH' && mb_substr($mpe_brand_name, 0, 8) == 'LOGITECH') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'JAYBIRD' && mb_substr($mpe_brand_name, 0, 8) == 'LOGITECH') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'BLUE' && mb_substr($mpe_brand_name, 0, 8) == 'LOGITECH') ||
            (mb_substr($amzn_brand_name, 0, 3) == 'NOX' && mb_substr($mpe_brand_name, 0, 3) == 'NOX') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'FRACTAL' && mb_substr($mpe_brand_name, 0, 7) == 'FRACTAL') ||
            (mb_substr($amzn_brand_name, 0, 10) == 'POCKETBOOK' && mb_substr($mpe_brand_name, 0, 10) == 'POCKETBOOK') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'MARS' && mb_substr($mpe_brand_name, 0, 4) == 'MARS') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'KEEP' && mb_substr($mpe_brand_name, 0, 4) == 'KEEP') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'APPROX' && mb_substr($mpe_brand_name, 0, 6) == 'APPROX') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'APPROX' && mb_substr($mpe_brand_name, 0, 7) == 'KEEPOUT') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'BILLOW' && mb_substr($mpe_brand_name, 0, 6) == 'BILLOW') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'ZEBRA' && mb_substr($mpe_brand_name, 0, 5) == 'ZEBRA') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'ZEBRA' && mb_substr($mpe_brand_name, 0, 7) == 'EXTREME') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'POLY' && mb_substr($mpe_brand_name, 0, 4) == 'POLY') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'BENQ' && mb_substr($mpe_brand_name, 0, 5) == 'ZOWIE') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'BENQ' && mb_substr($mpe_brand_name, 0, 4) == 'BENQ') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'NEVIR' && mb_substr($mpe_brand_name, 0, 9) == 'VIEWSONIC') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'SURFACE' && mb_substr($mpe_brand_name, 0, 9) == 'MICROSOFT') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'XGIMI' && mb_substr($mpe_brand_name, 0, 4) == 'HALO') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'WESTERN' && mb_substr($mpe_brand_name, 0, 2) == 'WD') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'CRUCIAL' && mb_substr($mpe_brand_name, 0, 6) == 'MICRON') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'MICRON' && mb_substr($mpe_brand_name, 0, 7) == 'CRUCIAL') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'MICRON' && mb_substr($mpe_brand_name, 0, 6) == 'MICRON') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'TOSHIBA' && mb_substr($mpe_brand_name, 0, 6) == 'KIOXIA') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'SEAGATE' && mb_substr($mpe_brand_name, 0, 5) == 'LACIE') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'LACIE' && mb_substr($mpe_brand_name, 0, 7) == 'SEAGATE') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'HGST' && mb_substr($mpe_brand_name, 0, 7) == 'WESTERN') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'CLEARONE' && mb_substr($mpe_brand_name, 0, 7) == 'LAIA') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'ARLO' && mb_substr($mpe_brand_name, 0, 7) == 'HP') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'SCANSNAP' && mb_substr($mpe_brand_name, 0, 7) == 'FUJITSU') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'LEITZ' && mb_substr($mpe_brand_name, 0, 5) == 'REXEL') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'EPOS' && mb_substr($mpe_brand_name, 0, 10) == 'SENNHEISER') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'GOODYEAR' && mb_substr($mpe_brand_name, 0, 11) == 'CLICKERLAND') ||
            (mb_substr($amzn_brand_name, 0, 11) == 'PLANTRONICS' && mb_substr($mpe_brand_name, 0, 4) == 'POLY') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'POLY' && mb_substr($mpe_brand_name, 0, 11) == 'PLANTRONICS') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'HITACHI' && mb_substr($mpe_brand_name, 0, 2) == 'LG') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'KROM' && mb_substr($mpe_brand_name, 0, 3) == 'NOX') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'JOBGAR' && mb_substr($mpe_brand_name, 0, 3) == 'NOX') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'ENGEL' && mb_substr($mpe_brand_name, 0, 5) == 'ENGEL') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'EPSON' && mb_substr($mpe_brand_name, 0, 5) == 'EPSON') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'ACCO' && mb_substr($mpe_brand_name, 0, 4) == 'ACCO') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'KRAMER' && mb_substr($mpe_brand_name, 0, 6) == 'KRAMER') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'GAMBER' && mb_substr($mpe_brand_name, 0, 8) == 'GJOHNSON') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'STAR' && mb_substr($mpe_brand_name, 0, 4) == 'STAR') ||
            (mb_substr($amzn_brand_name, 0, 3) == 'ELO' && mb_substr($mpe_brand_name, 0, 3) == 'ELO') ||
            (mb_substr($amzn_brand_name, 0, 9) == 'NEOMOUNTS' && mb_substr($mpe_brand_name, 0, 9) == 'NEOMOUNTS') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'JABRA' && mb_substr($mpe_brand_name, 0, 5) == 'JABRA') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'JABRA' && mb_substr($mpe_brand_name, 0, 8) == 'GN AUDIO') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'VISION' && mb_substr($mpe_brand_name, 0, 12) == 'CAMERAVISION') ||
            (mb_substr($amzn_brand_name, 0, 9) == 'CABLES2GO' && mb_substr($mpe_brand_name, 0, 5) == 'C2G') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'NOBO' && mb_substr($mpe_brand_name, 0, 4) == 'ACCO') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'PEERLESS' && mb_substr($mpe_brand_name, 0, 7) == 'EMERSON') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'PEERLESS' && mb_substr($mpe_brand_name, 0, 8) == 'PEERLESS') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'EMERSON' && mb_substr($mpe_brand_name, 0, 7) == 'EMERSON') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'VERTIV' && mb_substr($mpe_brand_name, 0, 5) == 'EPSON') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'VERTIV' && mb_substr($mpe_brand_name, 0, 7) == 'EMERSON') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'VOGEL' && mb_substr($mpe_brand_name, 0, 5) == 'VOGEL') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'PHYSIX' && mb_substr($mpe_brand_name, 0, 5) == 'VOGEL') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'YEALINK' && mb_substr($mpe_brand_name, 0, 7) == 'YEALINK') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'STINGER' && mb_substr($mpe_brand_name, 0, 6) == 'WOXTER') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'IMOO' && mb_substr($mpe_brand_name, 0, 4) == 'OPPO') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'FONESTAR' && mb_substr($mpe_brand_name, 0, 8) == 'FONESTAR') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'MUZYBAR' && mb_substr($mpe_brand_name, 0, 7) == 'PREMIER') ||
            (mb_substr($amzn_brand_name, 0, 3) == 'AVM' && mb_substr($mpe_brand_name, 0, 5) == 'FRITZ') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'BE QUIET' && mb_substr($mpe_brand_name, 0, 8) == 'BE QUIET') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'IN WIN' && mb_substr($mpe_brand_name, 0, 6) == 'IN WIN') ||
            (mb_substr($amzn_brand_name, 0, 2) == 'IN' && mb_substr($mpe_brand_name, 0, 6) == 'IN WIN') ||
            (mb_substr($amzn_brand_name, 0, 9) == 'DEEP COOL' && mb_substr($mpe_brand_name, 0, 8) == 'DEEPCOOL') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'LIAN' && mb_substr($mpe_brand_name, 0, 4) == 'LIAN') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'DAHUA' && mb_substr($mpe_brand_name, 0, 4) == 'IMOU') ||
            (mb_substr($amzn_brand_name, 0, 10) == 'TEAM GROUP' && mb_substr($mpe_brand_name, 0, 8) == 'TEAM GROUP') ||
            (mb_substr($amzn_brand_name, 0, 10) == 'TEAM GROUP' && mb_substr($mpe_brand_name, 0, 9) == 'TEAMGROUP') ||
            (mb_substr($amzn_brand_name, 0, 10) == 'THERMALTAK' && mb_substr($mpe_brand_name, 0, 10) == 'THERMALTAK') ||
            (mb_substr($amzn_brand_name, 0, 3) == 'ECS' && mb_substr($mpe_brand_name, 0, 3) == 'ECS') ||
            (mb_substr($amzn_brand_name, 0, 9) == 'ALPHACOOL' && mb_substr($mpe_brand_name, 0, 9) == 'ALPHACOOL') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'RASCOM' && mb_substr($mpe_brand_name, 0, 6) == 'NOCTUA') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'XIAOMI' && mb_substr($mpe_brand_name, 0, 6) == 'XIAOMI') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'SMART MI' && mb_substr($mpe_brand_name, 0, 6) == 'XIAOMI') ||
            (mb_substr($amzn_brand_name, 0, 10) == 'TT ESPORTS' && mb_substr($mpe_brand_name, 0, 11) == 'THERMALTAKE') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'SQUARE' && mb_substr($mpe_brand_name, 0, 4) == 'SONY') ||
            (mb_substr($amzn_brand_name, 0, 11) == 'PLAYSTATION' && mb_substr($mpe_brand_name, 0, 4) == 'SONY') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'KOCH' && mb_substr($mpe_brand_name, 0, 6) == 'CAPCOM') ||
            (mb_substr($amzn_brand_name, 0, 4) == 'INEC' && mb_substr($mpe_brand_name, 0, 6) == 'PHASAK') ||
            (mb_substr($amzn_brand_name, 0, 7) == 'BULLITT' && mb_substr($mpe_brand_name, 0, 3) == 'CAT') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'BULLIT' && mb_substr($mpe_brand_name, 0, 11) == 'CATERPILLAR') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'I-TEC' && mb_substr($mpe_brand_name, 0, 5) == 'I-TEC') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'WACOM' && mb_substr($mpe_brand_name, 0, 5) == 'WACOM') ||
            (mb_substr($amzn_brand_name, 0, 10) == 'COMPULOCKS' && mb_substr($mpe_brand_name, 0, 10) == 'COMPULOCKS') ||
            (mb_substr($amzn_brand_name, 0, 9) == 'NANOCABLE' && mb_substr($mpe_brand_name, 0, 10) == 'NANO CABLE') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'BOSCH' && mb_substr($mpe_brand_name, 0, 5) == 'BOSCH') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'BRAUN' && mb_substr($mpe_brand_name, 0, 5) == 'BRAUN') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'ORAL-B' && mb_substr($mpe_brand_name, 0, 5) == 'BRAUN') ||
            (mb_substr($amzn_brand_name, 0, 5) == 'MUVIT' && mb_substr($mpe_brand_name, 0, 5) == 'MUVIT') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'INSTAX' && mb_substr($mpe_brand_name, 0, 4) == 'FUJI') ||
            (mb_substr($amzn_brand_name, 0, 6) == 'WWDDVH' && mb_substr($mpe_brand_name, 0, 9) == 'KINGSMITH') ||
            (mb_substr($amzn_brand_name, 0, 8) == 'BE QUIET' && mb_substr($mpe_brand_name, 0, 3) == 'AMD') ||
            (mb_substr($amzn_brand_name, 0, 3) == 'APC' && mb_substr($mpe_brand_name, 0, 3) == 'APC') ||
            (mb_substr($amzn_brand_name, 0, 3) == 'AMD' && mb_substr($mpe_brand_name, 0, 8) == 'Be Quiet'))
            return true;

        if (mb_strpos($amzn_brand_name, 'PHILIPS') !== false && mb_strpos($mpe_brand_name, 'PHILIPS') !== false)
            return true;

        if (mb_strpos($amzn_brand_name, 'OPTOMA') !== false && mb_strpos($mpe_brand_name, 'OPTOMA') !== false)
            return true;

        if (mb_strpos($amzn_brand_name, 'HANNS') !== false && mb_strpos($mpe_brand_name, 'HANNS') !== false)
            return true;

        if (mb_strpos($amzn_brand_name, 'MSI') !== false && mb_strpos($mpe_brand_name, 'MSI') !== false)
            return true;

        if (mb_strpos($amzn_brand_name, 'MAXCOM') !== false && mb_strpos($mpe_brand_name, 'MAXOM') !== false)
            return true;

        if (mb_strpos($amzn_brand_name, 'A-DATA') !== false && mb_strpos($mpe_brand_name, 'ADATA') !== false)
            return true;

        if (mb_strpos($amzn_brand_name, 'SEAGATE') !== false && mb_strpos($mpe_brand_name, 'SEAGATE') !== false)
            return true;

        if (mb_strpos($amzn_brand_name, 'STARTECH') !== false && mb_strpos($mpe_brand_name, 'STARTECH') !== false)
            return true;

        if (mb_strpos($amzn_brand_name, 'LEVEL') !== false && mb_strpos($mpe_brand_name, 'LEVEL') !== false)
            return true;

        if (mb_strpos($amzn_brand_name, 'KENSINGTON') !== false && mb_strpos($mpe_brand_name, 'KENSINGTON') !== false)
            return true;

        return ($amzn_brand_name == $mpe_brand_name);
    }



    private function getAsins(Collection $shop_products)
    {
        try {
            $res = [];
            foreach ($shop_products as $shop_product) {
                if (!$shop_product->isUpgradeable()) {
                    if (isset($shop_product->product->ean) &&
                        $shop_product->product->ean != $shop_product->product->pn &&
                        $item = $this->amznGetAsinByEan($shop_product->product->ean)) {

                        if (isset($item['asin']) && $item['asin'] != '' &&
                            isset($item['brand_name']) && $this->brandsAreEqual($item['brand_name'], $shop_product->product->brand->name)) {

                            $shop_product->marketProductSku = $item['asin'];

                            if ((!$shop_product->product->name || $shop_product->product->name == '') && isset($item['name']) && $item['name'] != '') {
                                $shop_product->product->name = $item['name'];
                                $shop_product->product->save();
                            }

                            if ((!$shop_product->product->pn || $shop_product->product->pn == '') && isset($item['pn']) && $item['pn'] != '') {
                                $shop_product->product->pn = $item['pn'];
                                $shop_product->product->save();
                            }
                        }
                        else {
                            $data = [$item, $shop_product->product->brand->name, $shop_product->marketProductSku, $shop_product->mps_sku];
                            if ($item['brand_name'] == 'StarTech.com')
                                $res['STARTECH'][] = $data;
                            elseif ($item['brand_name'] == 'Desconocido')
                                $res['DESCONOCIDO'][] = $data;
                            elseif (!isset($item['brand_name']))
                                $res['NULL'][] = $data;
                            else
                                $res['ASIN'][] = $data;

                            $shop_product->marketProductSku = 'NO PRODUCT';
                        }
                    }
                    else
                        $shop_product->marketProductSku = 'NO PRODUCT';

                    $shop_product->save();

                    sleep(1);
                }
            }

            if (!empty($res))
                $this->nullAndStorage(__METHOD__.'_NO_BRAND', $res);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product ?? null);
        }
    }


    private function buildFlatFileHeader($feedType = 'POST_FLAT_FILE_INVLOADER_DATA')      // POST_FLAT_FILE_INVLOADER_DATA // _POST_FLAT_FILE_LISTINGS_DATA_
    {
        try {
            // 47 columns
            if ($feedType == 'POST_FLAT_FILE_INVLOADER_DATA')
                return ['sku', 'product-id', 'product-id-type', 'price', 'minimum-seller-allowed-price', 'maximum-seller-allowed-price', 'item-condition',
                    'quantity', 'add-delete', 'will-ship-internationally', 'expedited-shipping', 'item-note', 'merchant-shipping-group-name', 'product_tax_code',
                    'fulfillment_center_id', 'handling-time', 'batteries_required', 'are_batteries_included', 'battery_cell_composition', 'battery_type',
                    'number_of_batteries', 'battery_weight', 'battery_weight_unit_of_measure', 'number_of_lithium_ion_cells', 'number_of_lithium_metal_cells',
                    'lithium_battery_packaging', 'lithium_battery_energy_content', 'lithium_battery_energy_content_unit_of_measure', 'lithium_battery_weight',
                    'lithium_battery_weight_unit_of_measure', 'supplier_declared_dg_hz_regulation1', 'supplier_declared_dg_hz_regulation2', 'supplier_declared_dg_hz_regulation3',
                    'supplier_declared_dg_hz_regulation4', 'supplier_declared_dg_hz_regulation5', 'hazmat_united_nations_regulatory_id', 'safety_data_sheet_url',
                    'item_weight', 'item_weight_unit_of_measure', 'item_volume', 'item_volume_unit_of_measure', 'flash_point', 'ghs_classification_class1',
                    'ghs_classification_class2', 'ghs_classification_class3', 'list_price', 'uvp_list_price'];

            /* elseif ($feedType == '_POST_FLAT_FILE_LISTINGS_DATA_')
                return ['sku', 'product-id', 'product-id-type', 'price', 'minimum-seller-allowed-price', 'maximum-seller-allowed-price', 'item-condition',
                    'quantity', 'add-delete', 'will-ship-internationally', 'expedited-shipping', 'item-note', 'merchant-shipping-group-name', 'product_tax_code',
                    'fulfillment_center_id', 'handling-time', 'batteries_required', 'are_batteries_included', 'battery_cell_composition', 'battery_type',
                    'number_of_batteries', 'battery_weight', 'battery_weight_unit_of_measure', 'number_of_lithium_ion_cells', 'number_of_lithium_metal_cells',
                    'lithium_battery_packaging', 'lithium_battery_energy_content', 'lithium_battery_energy_content_unit_of_measure', 'lithium_battery_weight',
                    'lithium_battery_weight_unit_of_measure', 'supplier_declared_dg_hz_regulation1', 'supplier_declared_dg_hz_regulation2', 'supplier_declared_dg_hz_regulation3',
                    'supplier_declared_dg_hz_regulation4', 'supplier_declared_dg_hz_regulation5', 'hazmat_united_nations_regulatory_id', 'safety_data_sheet_url',
                    'item_weight', 'item_weight_unit_of_measure', 'item_volume', 'item_volume_unit_of_measure', 'flash_point', 'ghs_classification_class1',
                    'ghs_classification_class2', 'ghs_classification_class3', 'list_price', 'uvp_list_price']; */

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $feedType);
        }
    }


    private function buildFlatFileItem(ShopProduct $shop_product, $delete = false, $competitive_price = 0, $product_ids_today_orders)
    {
        try {
            $shop_product->setPriceStock(null, false, $product_ids_today_orders);
            //if (!$shop_product->wasChanged()) return $this->nullAndStorage(__METHOD__.'NOT_WASCHANGED', ['NOT WASCHANGED', $shop_product, $delete, $competitive_price]);

            // STOP REPRICE ?

            if ($shop_product->buybox_price != 0) {
                //$shop_product->setBuyBoxPrice($shop_product->buybox_price);
                $shop_product->setReprice();
                /* if ($shop_product->price > $competitive_price)
                    $shop_product->setReprice(); */
            }

            $mps_sku = $this->getShopProductMpsSku($shop_product);

            // Discount Price
            /* if ($shop_product->param_discount_price != 0 && $shop_product->param_starts_at && $shop_product->param_ends_at) {
                $Sale = $Price->addChild('Sale');
                $SalePrice = $Sale->addChild('SalePrice', $shop_product->param_discount_price);
                $SalePrice->addAttribute('currency', $shop_product->currency->code);
                $StartDate = $Sale->addChild('StartDate', $shop_product->param_starts_at->format('Y-m-d\TH:i:s'));
                $EndDate = $Sale->addChild('EndDate', $shop_product->param_ends_at->format('Y-m-d\TH:i:s'));
            } */

            $product_id = $shop_product->product->ean;
            $product_id_type = 4;
            if ($shop_product->isUpgradeable()) {
                $product_id = $shop_product->marketProductSku;
                $product_id_type = 1;
            }

            // Politica envíos
            if ($this->supplier_shippings) {
                $expedited = (isset($this->supplier_shippings[$shop_product->product->supplier_id]) &&
                    in_array($this->supplier_shippings[$shop_product->product->supplier_id], ['PlantilladeAmazonPortugal', 'PlantillaEspanaPremiumPortugal'])) ?
                    28 : 27;
                $shipping_template = $this->supplier_shippings[$shop_product->product->supplier_id] ?? $this->supplier_shippings[0];
            }
            else {
                $expedited = 27;
                $shipping_template = $this->shop->shipping;
            }

            // country_of_origin: Spain

            return [
                $mps_sku,                                           // $shop_product->getMPSSku(40),
                $product_id,                                        // product-id: ASIN, EAN, UPC, ISBN
                $product_id_type,                                   // product-id-type: 1 -> ASIN, 2 = ISBN, 3 = UPC, 4 = EAN
                number_format($shop_product->price, 2, ",", ""),    // 'price'  DECIMAL POINT: ,
                '',                                                 // 'minimum-seller-allowed-price'       // eliminar
                '',                                                 // 'maximum-seller-allowed-price'       // eliminar
                $shop_product->product->status_id == 1 ? 11 : 1,    // 'item-condition'  11 -> Nuevo, 1 -> De 2ª mano - como nuevo
                $shop_product->stock,                               // 'quantity'
                $delete ? 'x' : 'a',                                // 'add-delete' 'a = (update/add), d = (delete), x = (completely from system)
                $expedited,                                         // 'will-ship-internationally'  27 -> España solo | 28 -> Entrega estándar Europa 10
                27,                                                 // 'expedited-shipping', 27 -> España solo
                '',                                                 // 'item-note'
                $shipping_template,                                 // 'merchant-shipping-group-name'       // España peninsular
                '',                                                 // 'product_tax_code'
                '',                                                 // 'fulfillment_center_id'
                1,                                                  // 'handling-time'                      // 2
                '','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',''
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_product, $delete]);
        }
    }


    private function buildFlatFileData(Collection $shop_products, $feed_type = 'POST_FLAT_FILE_INVLOADER_DATA', $delete = false, $only_prices_stocks = false)
    {
        try {
            $this->iam_role_config = $this->getIAMRoleConfig();
            /* $asins = $shop_products->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)
                ->sortBy('buybox_updated_at')
                ->pluck('marketProductSku')
                ->toArray(); */

            // STOP REPRICE ?
            //$prices = $this->amznGetListingCompetitivePricing($asins);
            //$prices = null;

            // POST_FLAT_FILE_INVLOADER_DATA    Creates or updating listings for products already in Amazon's catalog.
            // POST_FLAT_FILE_LISTINGS_DATA     Creates a listing for a product not yet in Amazon's catalog.
            $header = $this->buildFlatFileHeader($feed_type);   // POST_FLAT_FILE_INVLOADER_DATA  // _POST_FLAT_FILE_LISTINGS_DATA_
            Storage::makeDirectory($this->shop_dir. 'flatfiles');
            $file_name = 'flatfiles/' .date('Y-m-d_H-i-s'). '.txt';
            $file_path = storage_path('app/' .$this->shop_dir.$file_name);
            $fp = fopen($file_path, 'w');
            fputcsv($fp, $header, "\t");

            $count = 0;
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            foreach ($shop_products as $shop_product) {
                // No generate Normal FlatFile -> ONLY XML Price Feed (amznBuildPriceFeed)
                if ($only_prices_stocks && $shop_product->param_discount_price != 0) continue;

                if ($feed_type == 'POST_FLAT_FILE_INVLOADER_DATA') {
                    if ($shop_product->isUpgradeable()) {
                        if ($item = $this->buildFlatFileItem($shop_product, $delete, $prices[$shop_product->marketProductSku] ?? null, $product_ids_today_orders)) {
                            fputcsv($fp, $item, "\t");
                            $count++;
                        }
                    }
                }
                // POST_FLAT_FILE_LISTINGS_DATA
                else {
                    if (!$shop_product->isUpgradeable()) {
                        if ($item = $this->buildFlatFileItem($shop_product, $delete, $prices[$shop_product->marketProductSku] ?? null, $product_ids_today_orders)) {
                            fputcsv($fp, $item, "\t");
                            $count++;
                        }
                    }
                }
            }

            fclose($fp);

            if ($count > 0)
                return Storage::get($this->shop_dir.$file_name);

            return $this->nullAndStorage(__METHOD__, [$feed_type, $delete, $only_prices_stocks]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products->pluck('marketProductSku')->toArray());
        }
    }


    private function buildFlatFileItem4Delete($mps_sku, $asin)
    {
        try {
            return [
                $mps_sku,                                           // $shop_product->getMPSSku(40),
                $asin,                                              // product-id: ASIN, EAN, UPC, ISBN
                1,                                                  // product-id-type: 1 -> ASIN, 2 = ISBN, 3 = UPC, 4 = EAN
                0,                                                  // 'price'  DECIMAL POINT: ,
                '',                                                 // 'minimum-seller-allowed-price'       // eliminar
                '',                                                 // 'maximum-seller-allowed-price'       // eliminar
                11,                                                 // 'item-condition'  11 -> Nuevo
                0,                                                  // 'quantity'
                'x',                                                // 'add-delete' 'a = (update/add), d = (delete), x = (completely from system)
                27,                                                 // 'will-ship-internationally'  27 -> España solo
                27,                                                 // 'expedited-shipping', 27 -> España solo
                '',                                                 // 'item-note'
                'Plantilla de Amazon',                              // 'merchant-shipping-group-name'       // España peninsular
                '',                                                 // 'product_tax_code'
                '',                                                 // 'fulfillment_center_id'
                1,                                                  // 'handling-time'                      // 2
                '','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',''
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$mps_sku, $asin]);
        }
    }


    private function buildFlatFileData4Delete(array $mps_sku_asins)
    {
        try {
            // POST_FLAT_FILE_INVLOADER_DATA    Creates or updating listings for products already in Amazon's catalog.
            // POST_FLAT_FILE_LISTINGS_DATA     Creates a listing for a product not yet in Amazon's catalog.
            $header = $this->buildFlatFileHeader('POST_FLAT_FILE_INVLOADER_DATA');   // POST_FLAT_FILE_INVLOADER_DATA  // _POST_FLAT_FILE_LISTINGS_DATA_
            Storage::makeDirectory($this->shop_dir. 'flatfiles');
            $file_name = 'flatfiles/' .date('Y-m-d_H-i-s'). '_delete.txt';
            $file_path = storage_path('app/' .$this->shop_dir.$file_name);
            $fp = fopen($file_path, 'w');
            fputcsv($fp, $header, "\t");

            $count = 0;
            foreach ($mps_sku_asins as $mps_sku_asin) {
                if ($item = $this->buildFlatFileItem4Delete($mps_sku_asin[0], $mps_sku_asin[1])) {
                    fputcsv($fp, $item, "\t");
                    $count++;
                }
            }

            fclose($fp);

            if ($count > 0)
                return Storage::get($this->shop_dir.$file_name);

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $mps_sku_asins);
        }
    }


    private function amznPostFeed($feed_data, $feed_type, $content_type)      // 'POST_PRODUCT_PRICING_DATA', 'text/xml'
    {
        try {
            Storage::append($this->shop_dir. 'posts/' .date('Y-m-d_H-i'). '_amznPostFeed.json', json_encode([$feed_type, $content_type, $feed_data]));
            //$config = $this->getIAMRoleConfig();
            $apiInstance = new FeedsApi($this->iam_role_config);

            // Create Feed Document
            $body = new CreateFeedDocumentSpecification();
            $body->setContentType($content_type);      // "text/xml" or "text/tab-separated-values"
            $feed_document = $apiInstance->createFeedDocument($body);

            if ($feed_document && $feed_doc_id = $feed_document->getFeedDocumentId()) {

                if ($content_type == 'text/tab-separated-values') $documentType = ReportType::GET_FLAT_FILE_OPEN_LISTINGS_DATA;
                // 'text/xml'
                else $documentType = ReportType::GET_ORDER_REPORT_DATA_INVOICING;

                $documentInfo = new CreateFeedDocumentResult();
                $documentInfo->setFeedDocumentId($feed_doc_id);
                $documentInfo->setUrl($feed_document->getUrl());
                //$documentInfo->setEncryptionDetails();

                $docToUpload = new Document($documentInfo, $documentType);
                //$docToUpload = new Document($payload, $content_type, $feed_type);

                $docToUpload->upload($feed_data);

                // Create Feed
                $body2 = new CreateFeedSpecification();
                $body2->setMarketplaceIds([$this->marketplace_id]);
                $body2->setInputFeedDocumentId($feed_doc_id);
                // POST_PRODUCT_DATA, _POST_PRODUCT_PRICING_DATA_, _POST_INVENTORY_AVAILABILITY_DATA_,
                $body2->setFeedType($feed_type);
                // Rate (requests per second): 0.0083 -> 2 minutes
                $feed_response = $apiInstance->createFeed($body2);
                if ($feed_response && $feed_id = $feed_response->getFeedId()) {

                    ShopJob::create([
                        'shop_id'   => $this->shop->id,
                        'jobId'     => $feed_id,
                        'operation' => $feed_type,
                    ]);

                    //sleep(10);

                    return $feed_id;
                }
            }

            return $this->nullAndStorage(__METHOD__, [$feed_type, $content_type, $feed_data]);

        } catch (Throwable $th) {
            // Error:429 code: "QuotaExceeded", message: "You exceeded your quota for the requested resource."
            if ($th->getCode() == 429) return $this->nullWithErrors($th, __METHOD__.'_QuotaExceeded', null);
            return $this->nullWithErrors($th, __METHOD__, [$feed_type, $content_type, $feed_data]);
        }
    }


    private function amznGetFeed(ShopJob $shop_job)
    {
        try {
            $shop_job_result = [];
            $apiInstance = new FeedsApi($this->iam_role_config);
            // Rate (requests per second): 0.0222 - 1 cada 4 segons
            $res_feed = $apiInstance->getFeed($shop_job->jobId);
            $this->logStorage($this->shop_dir. 'feeds/', __METHOD__, $res_feed->__toString());

            if ($res_feed) {

                $feed_id = $res_feed->getFeedId();
                $feed_type = $res_feed->getFeedType();
                $marketplace_ids = $res_feed->getMarketplaceIds();
                $processing_status = $res_feed->getProcessingStatus();  // "DONE"

                $shop_job_result[$feed_id]['Type'] = $feed_type;
                $shop_job_result[$feed_id]['Status'] = $processing_status;

                if ($processing_status == 'DONE') {
                    $feed_document_id = $res_feed->getResultFeedDocumentId();   // amzn1.tortuga.3.f8d917c3-5f54-486f-ab79-e2e80825eaf1.T9YBKRUIYMBLC

                    // Get Doc Results
                    $apiInstance = new FeedsApi($this->iam_role_config);
                    // Rate (requests per second): 0.0222
                    $feed_doc = $apiInstance->getFeedDocument($feed_document_id);
                    $this->logStorage($this->shop_dir. 'feeds/', __METHOD__.'_getFeedDocument', $feed_doc->__toString());

                    if ($feed_doc) {

                        $ca = $feed_doc->getCompressionAlgorithm();
                        $caav = $feed_doc->getCompressionAlgorithmAllowableValues();
                        $doc_id = $feed_doc->getFeedDocumentId();
                        $model_name = $feed_doc->getModelName();
                        $url = $feed_doc->getUrl();

                        if (in_array($shop_job->operation, ['POST_FLAT_FILE_INVLOADER_DATA'])) {
                            $contentType = 'text/tab-separated-values';
                            $documentType = ReportType::GET_FLAT_FILE_OPEN_LISTINGS_DATA;
                        }
                        else {
                            $contentType = 'text/xml';
                            $documentType = ReportType::GET_ORDER_REPORT_DATA_INVOICING;
                        }

                        $docToDownload = new Document($feed_doc, $documentType);   // "text/xml", "text/tab-separated-values"
                        $contents = $docToDownload->download();  // The raw report text

                        $data = $docToDownload->getData();
                        $filename = $this->shop_dir.'feeds/'.date('Y-m-d_H-i'). '_document';

                        if ($contentType == 'text/xml') {
                            Storage::append($filename.'.xml', $data->asXML());

                            $merchant_id = $data->Header->MerchantIdentifier;      // AOMPAKWU9QV0Z
                            //$feed_id = $data->Message->ProcessingReport->DocumentTransactionID; // == feed_id
                            $count_processed = $data->Message->ProcessingReport->ProcessingSummary->MessagesProcessed;  // 1
                            $count_successful = $data->Message->ProcessingReport->ProcessingSummary->MessagesSuccessful;  // 1
                            $count_error = $data->Message->ProcessingReport->ProcessingSummary->MessagesWithError;  // 1
                            $count_warning = $data->Message->ProcessingReport->ProcessingSummary->MessagesWithWarning;  // 1
                        }
                        elseif ($shop_job->operation == 'POST_FLAT_FILE_INVLOADER_DATA') {
                            Storage::append($filename.'.json', json_encode($data));

                            $collection4NoAuth = new Collection();
                            if (isset($data[0]['Feed Processing Summary'])) {

                                $count_processed = preg_replace('/[^0-9]/', '', $data[0]['Feed Processing Summary']);
                                $count_successful = preg_replace('/[^0-9]/', '', $data[1]['Feed Processing Summary']);

                                for($i=0; $i<count($data)-4; $i++) {
                                    if (isset($data[$i+4])) {

                                        // "original-record-number\tsku\terror-code\terror-type\terror-message",
                                        // "1\t341926-RZ03-03541800-R3M1-8886419346593\t8567\tError\tSKU 341926-RZ03-03541800-R3M1-8886419346593 does not match any ASIN and contains invalid values for attributes required for creation of a new ASIN.  New ASIN creation requires the following attributes, for which this SKU provided invalid attribute value(s)",
                                        $rows = str_getcsv($data[$i+4]['Feed Processing Summary'], "\t");
                                        $mps_sku = $rows[1] ?? null;
                                        // 6039     Merchant is not authorized to sell products under this restricted product group.
                                        // 17002    Please review your selling price and maximum price and ensure that your maximum price is greater than or equal to your selling price.
                                        $error_code = $rows[2] ?? null;
                                        $error_type = $rows[3] ?? null;
                                        $error_message = $rows[4] ?? null;

                                        // [Warning][17002]: Please review your selling price and maximum price and ensure that your maximum price is greater than or equal to your selling price.
                                        $shop_job_result[$feed_id][$error_type][$error_code]['message'] = $error_message;
                                        $shop_job_result[$feed_id][$error_type][$error_code][] = $mps_sku;

                                        // 6039: Merchant is not authorized to sell products under this restricted product group.
                                        // 6024: You are not authorized to list products under this brand
                                        if (in_array($error_code, ['6024', '6039'])) {
                                            if ($shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $mps_sku))
                                                $collection4NoAuth->add($shop_product);
                                        }
                                        //
                                        // REVISAR BE AQUESTS ERRORS I EL PERQUE EXACTAMENT PASSA
                                        //
                                        // The SKU data provided is different from what's already in the Amazon catalog (Merchant: 'base_product' / Amazon: 'product_bundle')
                                        // This SKU is not in the Amazon catalog
                                        // Despres de '8541' hi ha un '13013' -> NO TRACTAR '13013'
                                        elseif (in_array($error_code, ['8541'])) {
                                            // Product NO created on Amazon
                                            if ($shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $mps_sku)) {
                                                $shop_product->marketProductSku = 'NO PRODUCT';
                                                $shop_product->save();
                                            }
                                        }
                                    }
                                }
                            }
                            else {
                                $count_processed = preg_replace('/[^0-9]/', '', $data[0]['']);
                                $count_successful = preg_replace('/[^0-9]/', '', $data[1]['']);
                            }

                            /* [
                                {
                                    "Feed":"\tNumber",
                                    "Processing":"of",
                                    "Summary:":"records",
                                    "":"processed\t\t4"
                                },
                                {
                                    "Feed":"\tNumber",
                                    "Processing":"of",
                                    "Summary:":"records",
                                    "":"successful\t\t4"
                                }
                            ] */



                            // Remove Online & NO AUTH local
                            if ($collection4NoAuth->count()) {

                                $feed_type = 'POST_FLAT_FILE_INVLOADER_DATA';
                                $delete = true;
                                if ($listingsDataContent = $this->buildFlatFileData($collection4NoAuth, $feed_type, $delete)) {
                                    $shop_job_result[$feed_id]['NO AUTH'] = $this->amznPostFeed($listingsDataContent, $feed_type, 'text/tab-separated-values');
                                }

                                foreach ($collection4NoAuth as $shop_product) {
                                    $shop_product->marketProductSku = 'NO AUTH';
                                    $shop_product->save();
                                }
                            }
                        }

                        $shop_job_result[$feed_id]['Total'] = $count_processed;
                        $shop_job_result[$feed_id]['Success'] = $count_successful;
                        $this->logStorage($this->shop_dir. 'feeds/', __METHOD__, json_encode($shop_job_result));
                        unset($shop_job_result[$feed_id]['Warning']);

                        $shop_job->total_count = (int)$count_processed;
                        $shop_job->success_count = (int)$count_successful;
                        $shop_job->save();
                    }

                    sleep(50);
                }
            }

            return $shop_job_result;

        } catch (Throwable $th) {
            // Error:429 code: "QuotaExceeded", message: "You exceeded your quota for the requested resource."
            if ($th->getCode() == 429) return $this->nullWithErrors($th, __METHOD__.'_QuotaExceeded', null);
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    /************** PRIVATE FUNCTIONS - POSTS ***************/


    private function postlPricesStocksAsinPayload(Collection $shop_products, $feed_type = 'POST_FLAT_FILE_INVLOADER_DATA', $only_prices_stocks = false)
    {
        try {
            $res = [];
            $this->iam_role_config = $this->getIAMRoleConfig();
            if (!$only_prices_stocks) $this->getAsins($shop_products);

            $chunks = $shop_products->chunk(500);
            foreach ($chunks as $chunk) {

                if ($listingsDataContent = $this->buildFlatFileData($chunk, $feed_type, false, $only_prices_stocks)) {
                    $res[] = $this->amznPostFeed($listingsDataContent, $feed_type, 'text/tab-separated-values');
                }

                //break;  // FAKE TEST
            }

            if (count($res)) return $res;

            return $this->nullAndStorage(__METHOD__, [$shop_products, $feed_type]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products->pluck('marketProductSku')->toArray());
        }
    }


    private function postPricesOnlyDiscountsPayload(Collection $shop_products)
    {
        try {
            $this->iam_role_config = $this->getIAMRoleConfig();

            $count = 0;
            $priceFeedContent = $this->amznBuildEnvelope('Price');
            foreach ($shop_products as $shop_product) {

                if ($shop_product->isUpgradeable() &&
                    $shop_product->param_discount_price != 0 && $shop_product->param_starts_at && $shop_product->param_ends_at) {

                    // Price Feed
                    $this->amznBuildPriceFeed($priceFeedContent, $shop_product);
                    $count++;
                }
            }

            $products_result = ['count_post_discounts' => $count];

            if ($count > 0) {
                // Post Price Feed
                $res_price = $this->amznPostFeed($priceFeedContent->asXML(), 'POST_PRODUCT_PRICING_DATA', 'text/xml');
                $products_result['price'][] = $res_price;

                // Storage Price Feed
                $feed_filename = $this->shop_dir. 'feeds/' .date('Y-m-d_H-i'). '_priceFeedContent_'.$shop_product->product->ean.'.xml';
                Storage::append($feed_filename, $priceFeedContent->asXML());
            }

            Storage::append($this->shop_dir. 'posts/' .date('Y-m-d_H-i'). '_prices_stocks.json', json_encode($products_result));

            return $products_result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$count, $shop_product]);
        }
    }


    private function postNewProductsPayload(Collection $shop_products)
    {
        try {
            $this->iam_role_config = $this->getIAMRoleConfig();

            $count = 0;
            $res = [];
            $res = ['count' => $shop_products->count()];

            $ProductFeedContent = $this->amznBuildEnvelope('Product');
            $InventoryFeedContent = $this->amznBuildEnvelope('Inventory');
            $PriceFeedContent = $this->amznBuildEnvelope('Price');
            $ProductImageFeedContent = $this->amznBuildEnvelope('ProductImage');

            foreach ($shop_products as $shop_product) {

                $this->amznBuildProductFeed($ProductFeedContent, $shop_product);
                $this->amznBuildInventoryFeed($InventoryFeedContent, $shop_product);
                $this->amznBuildPriceFeed($PriceFeedContent, $shop_product);
                $this->amznBuildProductImageFeed($ProductImageFeedContent, $shop_product);

                $count++;
            }

            // Storage Feeds
            $dir = $this->shop_dir. 'feeds/' .date('Y-m-d_H-i');
            Storage::append($dir.'_Product.xml', $ProductFeedContent->asXML());
            Storage::append($dir.'_Inventory.xml', $InventoryFeedContent->asXML());
            Storage::append($dir.'_Price.xml', $PriceFeedContent->asXML());
            Storage::append($dir.'_ProductImage.xml', $ProductImageFeedContent->asXML());

            // Post Product Feed
            $res['Product'] = $this->amznPostFeed($ProductFeedContent->asXML(), 'POST_PRODUCT_DATA', 'text/xml');
            $res['Product'] = $this->amznPostFeed($InventoryFeedContent->asXML(), 'POST_INVENTORY_AVAILABILITY_DATA', 'text/xml');
            $res['Product'] = $this->amznPostFeed($PriceFeedContent->asXML(), 'POST_PRODUCT_PRICING_DATA', 'text/xml');
            $res['Product'] = $this->amznPostFeed($ProductImageFeedContent->asXML(), 'POST_PRODUCT_IMAGE_DATA', 'text/xml');

            $res['count_feeds'] = $count;
            Storage::append($this->shop_dir. 'posts/' .date('Y-m-d_H-i'). '_products.json', json_encode($res));

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    // NOT USED
    private function postPricesStocksPayload(Collection $shop_products, array $ean_asins)
    {
        try {
            $this->iam_role_config = $this->getIAMRoleConfig();

            $count = 0;
            $products_result = ['count_products' => $shop_products->count()];

            $productFeedContent = $this->amznBuildEnvelope('Product', true);
            $priceFeedContent = $this->amznBuildEnvelope('Price');
            $inventoryFeedContent = $this->amznBuildEnvelope('Inventory');

            foreach ($shop_products as $shop_product) {

                if (isset($ean_asins[$shop_product->product->ean])) {

                    // Product Offer Feed
                    $this->amznBuildProductOfferFeed($productFeedContent, $shop_product);

                    // Price Feed
                    $this->amznBuildPriceFeed($priceFeedContent, $shop_product);

                    // Inventory Feed
                    $this->amznBuildInventoryFeed($inventoryFeedContent, $shop_product);

                    $count++;
                }
            }

            $products_result = ['count_post' => $count];

            if ($count > 0) {
                // Post Product Offer Feed
                $res_product = $this->amznPostFeed($productFeedContent->asXML(), 'POST_PRODUCT_DATA', 'text/xml');
                $products_result['product'][] = $res_product;

                // Post Price Feed
                $res_price = $this->amznPostFeed($priceFeedContent->asXML(), 'POST_PRODUCT_PRICING_DATA', 'text/xml');
                $products_result['price'][] = $res_price;

                // Post Inventory Feed
                $res_inventory = $this->amznPostFeed($inventoryFeedContent->asXML(), 'POST_INVENTORY_AVAILABILITY_DATA', 'text/xml');
                $products_result['inventory'][] = $res_inventory;

                // Storage  Product Offer Feed
                $feed_filename = $this->shop_dir. 'feeds/' .date('Y-m-d_H-i'). '_productOfferFeedContent_'.$shop_product->product->ean.'.xml';
                Storage::append($feed_filename, $productFeedContent->asXML());

                // Storage Price Feed
                $feed_filename = $this->shop_dir. 'feeds/' .date('Y-m-d_H-i'). '_priceFeedContent_'.$shop_product->product->ean.'.xml';
                Storage::append($feed_filename, $priceFeedContent->asXML());

                // Storage Inventory Feed
                $feed_filename = $this->shop_dir. 'feeds/' .date('Y-m-d_H-i'). '_inventoryFeedContent_'.$shop_product->product->ean.'.xml';
                Storage::append($feed_filename, $inventoryFeedContent->asXML());

                $products_result['price_stock'] = $count;
                Storage::append($this->shop_dir. 'posts/' .date('Y-m-d_H-i'). '_prices_stocks.json', json_encode($products_result));
            }

            return $products_result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$count, $shop_product]);
        }
    }


    private function postDeletePayload(ShopProduct $shop_product)
    {
        try {
            $this->iam_role_config = $this->getIAMRoleConfig();
            $shop_products = new Collection([$shop_product]);
            $feed_type = 'POST_FLAT_FILE_INVLOADER_DATA';
            $delete = true;
            if ($listingsDataContent = $this->buildFlatFileData($shop_products, $feed_type, $delete)) {
                return $this->amznPostFeed($listingsDataContent, $feed_type, 'text/tab-separated-values');
            }

            return $this->nullAndStorage(__METHOD__, [$shop_products, $feed_type]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products->pluck('marketProductSku')->toArray());
        }


        /* try {
            $this->iam_role_config = $this->getIAMRoleConfig();

            $PriceFeedContent = $this->amznBuildEnvelope('Price');
            $this->amznBuildPriceFeed($PriceFeedContent, $shop_product, 'Delete');

            $dir = $this->shop_dir. 'feeds/' .date('Y-m-d_H-i');
            Storage::append($dir.'_Price_Delete.xml', $PriceFeedContent->asXML());

            return $this->amznPostFeed($PriceFeedContent->asXML(), 'POST_PRODUCT_PRICING_DATA', 'text/xml');

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        } */
    }


    /************** PRIVATE VENDOR FUNCTIONS ***************/


    private function amznVendorGetOrders()
    {
        try {
            $apiInstance = new VendorOrdersApi($this->iam_role_config);

            //$res = $apiInstance->getPurchaseOrders(null, Carbon::now()->subDays(10)->format('Y-m-d\TH:i:s'));
            $res = $apiInstance->getPurchaseOrders();
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                return $payload->getOrders();   // SellingPartnerApi\Model\VendorOrders\OrderList::getOrders
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function amznUpdateOrCreateVendorAddress($party_id, VendorOrdersAddress $amzn_ship_address = null)
    {
        try {
            if ($amzn_ship_address) {
                if ($country_code = $amzn_ship_address->getCountryCode())
                $country = Country::firstOrCreate([
                    'code'      => $country_code,
                ],[]);

                return Address::updateOrCreate([
                    'country_id'            => $country->id ?? null,
                    'market_id'             => $this->market->id,
                    'marketBuyerId'         => null,
                    'name'                  => $party_id,
                ],[
                    'address1'              => $amzn_ship_address->getAddressLine1() ?? null,
                    'address2'              => $amzn_ship_address->getAddressLine2() ?? null,
                    'address3'              => $amzn_ship_address->getAddressLine3() ?? null,
                    'city'                  => $amzn_ship_address->getCity() ?? null,
                    'state'                 => $amzn_ship_address->getStateOrRegion() ?? null,
                    'zipcode'               => $amzn_ship_address->getPostalCode() ?? null,
                    'phone'                 => $amzn_ship_address->getPhone() ?? null,
                    'district'              => $amzn_ship_address->getDistrict() ?? null,
                    //'municipality'          => $amzn_ship_address->getMunicipality(),
                ]);
            }
            else {
                return Address::updateOrCreate([
                    'country_id'            => null,
                    'market_id'             => $this->market->id,
                    'marketBuyerId'         => null,
                    'name'                  => $party_id,
                ],[]);
            }


        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $amzn_ship_address->__toString());
        }
    }


    private function updateOrCreateVendorOrder(VendorOrdersOrder $amzn_order)
    {
        try {
            // ORDER STATUSES: purchase_order_state
            // Closed:

            // PendingAvailability (This status is available for pre-orders only. The order has been placed, payment has not been authorized, and the release date of the item is in the future.);
            // Pending (The order has been placed but payment has not been authorized);
            // Unshipped (Payment has been authorized and the order is ready for shipment, but no items in the order have been shipped);
            // PartiallyShipped (One or more, but not all, items in the order have been shipped);
            // Shipped (All items in the order have been shipped);
            // InvoiceUnconfirmed (All items in the order have been shipped. The seller has not yet given confirmation to Amazon that the invoice has been shipped to the buyer.);
            // Canceled (The order has been canceled);
            // Unfulfillable (The order cannot be fulfilled. This state applies only to Multi-Channel Fulfillment orders.).

            $amzn_order_id = $amzn_order->getPurchaseOrderNumber();

            // Closed
            $marketStatusName = $amzn_order->getPurchaseOrderState();
            $status = Status::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketStatusName'      => $marketStatusName,
                'type'                  => 'order',
            ],[
                'name'                  => $marketStatusName,
            ]);

            $amzn_order_details = $amzn_order->getOrderDetails();

            $amzn_ship = $amzn_order_details->getShipToParty();
            $shipping_address = $this->amznUpdateOrCreateVendorAddress($amzn_ship->getPartyId(), $amzn_ship->getAddress());

            $amzn_bill = $amzn_order_details->getBillToParty();
            $billing_address = $this->amznUpdateOrCreateVendorAddress($amzn_bill->getPartyId(), $amzn_bill->getAddress());

            if ($amzn_buying = $amzn_order_details->getBuyingParty()) {
                $buying_address = $this->amznUpdateOrCreateVendorAddress($amzn_buying->getPartyId(), $amzn_buying->getAddress());

                $buyer = Buyer::firstOrCreate([
                    'market_id'             => $this->market->id,
                    'marketBuyerId'         => $amzn_buying->getPartyId(),
                    'name'                  => $amzn_buying->getPartyId(),
                    //'email'                 => $amzn_buyer['email'] ?? null,
                ],[
                    //'shipping_address_id'   => $address->id ?? null,
                    'billing_address_id'    => $buying_address->id ?? null,
                ]);
            }

            $order = $this->shop->orders()->where('marketOrderId', $amzn_order_id)->first();
            $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;

            $order = Order::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'marketOrderId'         => $amzn_order_id,
            ],[
                'buyer_id'              => $buyer->id ?? null,
                'shipping_address_id'   => $shipping_address->id ?? null,
                'billing_address_id'    => $billing_address->id ?? null,
                'currency_id'           => 1,
                'status_id'             => $status->id ?? null,
                'type_id'               => null,
                'SellerId'              => null,
                'SellerOrderId'         => null,
                'info'                  => null,
                'price'                 => null,
                'tax'                   => 0,
                'shipping_price'        => 0,
                'shipping_tax'          => 0,       // mp_shipping_fee: isset($order_info) ? (floatval($order_info->result->data->logisitcs_escrow_fee_rate)*100) : 0,
                'notified'              => $notified,
                'notified_updated'      => $notified_updated,
            ]);

            // 2021-07-19T18:21:49Z
            // Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $amzn_order->getPurchaseDate())->format('Y-m-d H:i:s')  WORKS FINE
            $order->created_at = new Carbon($amzn_order_details->getPurchaseOrderDate());  // ::create createFromFormat('Y-m-d\TH:i:s\Z', $amzn_order_details->getPurchaseOrderDate());
            $order->updated_at = new Carbon($amzn_order_details->getPurchaseOrderStateChangedDate());      //::createFromFormat('Y-m-d\TH:i:s\Z', $amzn_order_details->getPurchaseOrderStateChangedDate());
            $order->save();

            $order_items_count = 0;
            if ($amzn_order_items = $amzn_order_details->getItems()) {

                foreach ($amzn_order_items as $amzn_order_item) {

                    $mps_sku = $amzn_order_item->getVendorProductIdentifier();              // MPS_SKU | EAN
                    $marketProductSku = $amzn_order_item->getAmazonProductIdentifier();     // ASIN
                    $quantity = $amzn_order_item->getOrderedQuantity()->getAmount();

                    $net_cost_amount = 0;
                    $net_cost_currency_code = 'EUR';
                    if ($net_cost = $amzn_order_item->getNetCost()) {
                        $net_cost_amount = $net_cost->getAmount();
                        $net_cost_currency_code = $net_cost->getCurrencyCode();
                    }

                    $list_price_amount = 0;
                    $list_price_currency_code = 'EUR';
                    if ($list_price = $amzn_order_item->getListPrice()) {
                        $list_price_amount = $list_price->getAmount();
                        $list_price_currency_code = $list_price->getCurrencyCode();
                    }

                    $order_item = $order->updateOrCreateOrderItem(
                        $amzn_order_id.'_'.$amzn_order_item->getItemSequenceNumber(),
                        $mps_sku,
                        $marketProductSku,
                        $mps_sku,
                        $quantity,
                        $list_price_amount,
                        0,
                        0,
                        0,
                        0,
                        null
                    );

                    $order_items_count++;
                }
            }

            return $order_items_count;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $amzn_order->__toString());
        }
    }


    private function amznVendorFulfillmentGetOrders()
    {
        // Access to requested resource is denied.
        try {
            $apiInstance = new VendorDirectFulfillmentOrdersApi($this->iam_role_config);

            //$res = $apiInstance->getPurchaseOrders(null, Carbon::now()->subDays(10)->format('Y-m-d\TH:i:s'));
            $created_after = Carbon::now()->subDays(365)->format('Y-m-d\TH:i:s');
            $created_before = Carbon::now()->format('Y-m-d\TH:i:s');
            $res = $apiInstance->getOrders($created_after, $created_before);
            $this->logStorage($this->shop_dir. 'orders/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                return $payload->getOrders();   // SellingPartnerApi\Model\VendorDirectFulfillmentOrders\OrderList::getOrders
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function amznVendorSubmitInvoices()
    {
        try {
            $apiInstance = new VendorInvoicesApi($this->iam_role_config);
            // \SellingPartnerApi\Model\VendorInvoices\SubmitInvoicesRequest
            $body = new SubmitInvoicesRequest();
            // \SellingPartnerApi\Model\VendorInvoices\Invoice[]
            $invoices = [];
            $invoice = new Invoice();
            $invoice->setBillToParty('BCN2');
            $id = '12345';
            $invoice->setId($id);
            $invoices[] = $invoice;

            $body->setInvoices($invoices);
            $res = $apiInstance->submitInvoices($body);
            $this->logStorage($this->shop_dir. 'shipping/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                return $payload->getTransactionId();
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function amznVendorSubmitShipmentConfirmations()
    {
        try {
            $apiInstance = new VendorShippingApi($this->iam_role_config);
            // \SellingPartnerApi\Model\VendorInvoices\SubmitInvoicesRequest
            // \SellingPartnerApi\Model\VendorShipping\SubmitShipmentConfirmationsRequest
            $body = new SubmitShipmentConfirmationsRequest();
            //SellingPartnerApi\Model\VendorShipping\ShipmentConfirmation[]
            $shipment_confirmations = [];
            $shipment_confirmation = new ShipmentConfirmation();
            $shipment_confirmation->setSellingParty('BCN2');

            $body->setShipmentConfirmations($shipment_confirmations);
            $res = $apiInstance->submitShipmentConfirmations($body);
            $this->logStorage($this->shop_dir. 'shipping/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                return $payload->getTransactionId();
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function amznVendorSubmitInventoryUpdate(Collection $shop_products)
    {
        try {
            $apiInstance = new VendorDirectFulfillmentInventoryApi($this->iam_role_config);

            $warehouse_id = $this->shop->marketSellerId;
            $body = new SubmitInventoryUpdateRequest();
            $inventory = new InventoryUpdate();
            $inventory->setIsFullUpdate(true);
            // \SellingPartnerApi\Model\VendorDirectFulfillmentInventory\PartyIdentification
            $selling_party = new PartyIdentification();
            $party_id = 'BCN2';  // BCN2, MAD4, MAD9
            $selling_party->setPartyId($party_id);
            $inventory->setSellingParty($selling_party);

            $items = [];
            foreach ($shop_products as $shop_product) {
                $shop_product->setPriceStock();
                $item = new ItemDetails();
                $item->setIsObsolete(false);        // When true, the item is permanently unavailable.
                $item->setAvailableQuantity($shop_product->stock);
                $mps_sku = $this->getShopProductMpsSku($shop_product);
                $item->setVendorProductIdentifier($mps_sku);
                if ($shop_product->isUpgradeable()) {
                    $item->setBuyerProductIdentifier($shop_product->marketProductSku);
                }

                $items[] = $item;
            }

            // \SellingPartnerApi\Model\VendorDirectFulfillmentInventory\ItemDetails[]
            $inventory->setItems($items);
            $body->setInventory($inventory);

            $res = $apiInstance->submitInventoryUpdate($warehouse_id, $body);
            $this->logStorage($this->shop_dir. 'inventory/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                return $payload->getTransactionId();   // SellingPartnerApi\Model\VendorDirectFulfillmentInventory\TransactionReference::getTransactionId
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function amznVendorGetShippingLabels()
    {
        // InvalidInput: Application do not have access to some or all requested resource
        try {
            $apiInstance = new VendorDirectFulfillmentShippingApi($this->iam_role_config);
            $created_after = Carbon::now()->subDays(365)->format('Y-m-d\TH:i:s\Z');
            $created_before = Carbon::now()->format('Y-m-d\TH:i:s\Z');
            $res = $apiInstance->getShippingLabels($created_after, $created_before, 'BCN2');
            $this->logStorage($this->shop_dir. 'shipping/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                return $payload->getShippingLabels();   // SellingPartnerApi\Model\VendorDirectFulfillmentShipping\ShippingLabelList::getShippingLabels
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }


    private function amznVendorGetCustomerInvoices()
    {
        // Unauthorized: Access to requested resource is denied.
        try {
            $apiInstance = new VendorDirectFulfillmentShippingApi($this->iam_role_config);
            $created_after = Carbon::now()->subDays(365)->format('Y-m-d\TH:i:s\Z');
            $created_before = Carbon::now()->format('Y-m-d\TH:i:s\Z');
            $res = $apiInstance->getCustomerInvoices($created_after, $created_before, 'BCN2');
            $this->logStorage($this->shop_dir. 'shipping/', __METHOD__, $res->__toString());

            if ($res && !$res->getErrors() && $payload = $res->getPayload()) {
                return $payload->getCustomerInvoices();
            }

            return $this->nullAndStorage(__METHOD__, $res ?? null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $res ?? null);
        }
    }



    /************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getBrands($id_min = null)
    {
        return 'No code.';
    }


    public function getCategories($marketCategoryId = null)
    {
        try {
            $count = 0;
            if ($this->iam_role_config = $this->getIAMRoleConfig()) {
                $options = null;
                if ($marketCategoryId) $options = ['BrowseNodeId' => $marketCategoryId];

                $report_id = $this->amznCreateReport('GET_XML_BROWSE_TREE_DATA', $options);
                sleep(15);
                //$report_id = '51460018838';

                if ($report_id) {

                    if ($xml_result = $this->amznGetReportDocument($report_id, 'GET_XML_BROWSE_TREE_DATA')) {

                        foreach ($xml_result->Node as $node) {

                            $count++;
                            $node_id = $node->browseNodeId;
                            $node_name = utf8_decode($node->browseNodeName);
                            $node_context_name = $node->browseNodeStoreContextName;
                            $node_product_type = $node->productTypeDefinitions;
                            if (strlen($node_product_type) > 255) {
                                Storage::append($this->shop_dir. 'reports/' .date('Y-m-d_H-i'). '_cat_type_too_long.txt', $node_product_type);
                                $node_product_type = mb_substr($node_product_type, 0, 255);
                            }

                            $node_path_name = utf8_decode($node->browsePathByName);
                            $node_path_id = $node->browsePathById;
                            $path_ids = explode(',', $node_path_id);
                            $root_marketCategoryId = $path_ids[0];
                            $root_category_name = (count($path_ids) == 2) ? $node_name : null;

                            if (!$root_category = $this->market->root_categories()->where('marketCategoryId', $root_marketCategoryId)->first()) {
                                $root_category = RootCategory::create([
                                    'market_id'         => $this->market->id,
                                    'name'              => $root_category_name ?? $root_marketCategoryId,
                                    'marketCategoryId'  => $root_marketCategoryId,
                                ]);
                            }
                            elseif ($root_category_name) {
                                $root_category->name = $root_category_name;
                                $root_category->save();
                            }
                            /* elseif ($root_category->name == $root_category->marketCategoryId) {
                                $root_category_name = (count($path_ids) == 2) ? $node_name : null;
                                if ($root_category_name) {
                                    $root_category->name = $root_category_name;
                                    $root_category->save();
                                }
                            } */

                            if ((count($path_ids) > 1 && ($path_ids[0] != $path_ids[1]))) {
                                $parent_id = $path_ids[count($path_ids)-2];
                                $parent = $this->market->market_categories()->where('marketCategoryId', $parent_id)->first();
                                MarketCategory::updateOrCreate(
                                    [
                                        'market_id'         => $this->market->id,
                                        'marketCategoryId'  => $node_id,
                                    ],
                                    [
                                        'name'              => $node_name,
                                        'parent_id'         => $parent->id ?? null,
                                        'path'              => $node_path_name,
                                        'root_category_id'  => $root_category->id,
                                        'type'              => $node_product_type,
                                    ]
                                );
                            }
                        }

                        return $count;
                    }
                    else
                        dd($report_id);
                }
            }

            return $this->nullAndStorage(__METHOD__, null);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    public function getAttributes(Collection $market_categories)
    {
        return 'No code.';
    }


    public function getFeed(ShopProduct $shop_product)
    {
        try {
            $ProductFeedContent = $this->amznBuildEnvelope('Product');
            $InventoryFeedContent = $this->amznBuildEnvelope('Inventory');
            $PriceFeedContent = $this->amznBuildEnvelope('Price');
            $ProductImageFeedContent = $this->amznBuildEnvelope('ProductImage');

            $this->amznBuildProductFeed($ProductFeedContent, $shop_product);
            $this->amznBuildInventoryFeed($InventoryFeedContent, $shop_product);
            $this->amznBuildPriceFeed($PriceFeedContent, $shop_product);
            $this->amznBuildProductImageFeed($ProductImageFeedContent, $shop_product);

            $priceFeed = $this->buildFlatFileItem($shop_product, false, 0, []);

            return [$priceFeed, $ProductFeedContent, $InventoryFeedContent, $PriceFeedContent, $ProductImageFeedContent];

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function getJobs()
    {
        try {
            if ($this->iam_role_config = $this->getIAMRoleConfig()) {
                $shop_jobs = $this->shop->shop_jobs()->whereNull('total_count')->get();
                $jobs_result['jobs'] = $shop_jobs->count();
                foreach ($shop_jobs as $shop_job) {
                    $jobs_result[] = $this->amznGetFeed($shop_job);
                    sleep(4);
                }

                // GET ESTIMATES FEES
                /* if ($shop_products = $this->getShopProducts4Update()) {
                    $count = 0;
                    foreach ($shop_products as $shop_product) {
                        if ($shop_product->price != 0 && $shop_product->stock > 0) {

                            if ($shop_product->created_at->gte(now()->subDays(5)) &&
                                $mp_bfit = $this->amznGetEstimatedFee($shop_product->marketProductSku, $shop_product->mps_sku, $shop_product->price)) {

                                if ($mp_bfit) {
                                    $price = $shop_product->price;
                                    $mp_bfit = (float)$mp_bfit;

                                    if ($fee_calc = $this->mpFeeCalc($price, $mp_bfit)) {
                                        $mp_lot = $fee_calc[0];
                                        $mp_fee = $fee_calc[1];
                                        $mp_lot_fee = $fee_calc[2];

                                        $mp_bfit_min = 0.3;
                                        $shop_product->param_mp_lot = $mp_lot;
                                        $shop_product->param_mp_fee = $mp_fee * 100;
                                        $shop_product->param_mp_lot_fee = $mp_lot_fee * 100;
                                        $shop_product->param_mp_bfit_min = $mp_bfit_min;
                                        $shop_product->save();
                                    }
                                }

                                $count++;

                                // 429 QuotaExceeded
                                if ($count >= 10) {
                                    sleep(1);
                                    $count = 0;
                                }
                            }
                        }
                    }
                } */

                return $jobs_result;
            }

            return $this->nullAndStorage(__METHOD__, null);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);

        } finally {
            $this->amznGetLastEstimatedFee();
        }
    }


    public function getOrders()
    {
        try {
            $res_orders = [];
            $this->iam_role_config = $this->getIAMRoleConfig();

            $amzn_orders = [];
            if ($this->seller_type == 'seller') {
                $amzn_orders = $this->amznGetOrders();
                if ($amzn_orders || count($amzn_orders)) {

                    foreach ($amzn_orders as $amzn_order) {
                        $res_orders[] = $this->updateOrCreateOrder($amzn_order);
                    }

                    return $res_orders;
                }
            }
            // vendor
            else {
                $amzn_orders = $this->amznVendorGetOrders();
                //$amzn_orders = $this->amznVendorFulfillmentGetOrders();


                /* if ($amzn_orders || count($amzn_orders)) {

                    foreach ($amzn_orders as $amzn_order) {
                        $res_orders[] = $this->updateOrCreateVendorOrder($amzn_order);
                    }



                    return $res_orders;
                } */
            }

            if (empty($amzn_orders))
                return 'No hay pedidos en '.$this->shop->name;

            return $this->msgAndStorage(__METHOD__, 'Error obteniendo pedidos.', $amzn_orders ?? null);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $amzn_orders ?? null);
        }
    }


    public function getGroups()
    {
        return 'No code.';
    }


    public function getCarriers()
    {
        try {
            if ($carrier_codes = $this->amznGetCarrierCodes()) {
                foreach ($carrier_codes as $carrier_code) {
                    MarketCarrier::updateOrCreate([
                        'market_id'     => $this->market->id,
                        'code'          => $carrier_code,
                    ], [
                        'name'          => $carrier_code,
                        'url'           => null,
                    ]);
                }

                return true;
            }

            return $carrier_codes;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    public function getOrderComments(Order $order)
    {
        return 'No code.';
    }


    /************ PUBLIC FUNCTIONS - POSTS *******************/


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
        return 'No code.';
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

            $res_prices_stocks = $this->postlPricesStocksAsinPayload($shop_products, 'POST_FLAT_FILE_INVLOADER_DATA');
            $res_discounts = $this->postPricesOnlyDiscountsPayload($shop_products);

            return [$res_prices_stocks, $res_discounts];

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products->pluck('marketProductSku')->toArray());

        } finally {
            sleep(600); // 10 minutes
            $sync_categories = $this->syncCategories();     //$shop_products);
        }
    }


    public function postUpdatedProducts($shop_products = null)
    {
        return 'No code.';
    }


    public function postPricesStocks($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            $res_prices_stocks = $this->postlPricesStocksAsinPayload($shop_products, 'POST_FLAT_FILE_INVLOADER_DATA', true);
            $res_discounts = $this->postPricesOnlyDiscountsPayload($shop_products);

            return [$res_prices_stocks, $res_discounts];

            /* $ean_asins = [];
            return $this->postPricesStocksPayload($shop_products, $ean_asins); */

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products->pluck('marketProductSku')->toArray());
        }
    }


    public function postGroups($shop_products = null)
    {
        return 'No code.';
    }


    public function removeProduct($marketProductSku = null)
    {
        try {
            $res = [];
            if ($marketProductSku && $shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first()) {
                $res['online'] = $this->postDeletePayload($shop_product);
                $res['local'] = $shop_product->deleteSecure();

                return $marketProductSku;
            }

            return $this->msgAndStorage(__METHOD__, 'Error eliminando el producto.', $marketProductSku);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketProductSku);
        }
    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        try {
            /* $validatedData = $request->validate([
                'market_carrier_id' => 'nullable|exists:market_carriers,id',
                'tracking'          => 'nullable|max:255',
                'desc'              => 'nullable|max:255',

                'order_item_id'     => 'nullable|exists:order_items,id',
                'quantity'          => 'nullable|numeric|gte:0',
            ]);

            $validatedData['full'] = $request->has('full') ? 1 : 0;
            $validatedData['market_id'] = $order->market->id;
            $validatedData['shop_id'] = $order->shop->id;
            $validatedData['order_id'] = $order->id;
            $validatedData['quantity'] = $validatedData['quantity'] ?? 0; */


            /* $market_carrier = MarketCarrier::find($shipment_data['market_carrier_id']);
            $type = isset($shipment_data['full']) ? 'all' : 'part';

            $req = new AliexpressSolutionOrderFulfillRequest();
            $req->setServiceName($market_carrier->code);
            $req->setTrackingWebsite($market_carrier->url);
            $req->setOutRef($order->marketOrderId);
            $req->setSendType($type);
            $req->setDescription($shipment_data['desc']);
            $req->setLogisticsNo($shipment_data['tracking']); */

            $this->iam_role_config = $this->getIAMRoleConfig();

            $count = 0;
            $orderFulfillmentFeedContent = $this->amznBuildEnvelope('OrderFulfillment');

            $orderFulfillmentFeedContent->addChild('AmazonOrderID', $order->marketOrderId);
            $orderFulfillmentFeedContent->addChild('FulfillmentDate', now()->format('Y-m-d\TH:i:s'));

            $market_carrier = MarketCarrier::find($shipment_data['market_carrier_id']);
            $FulfillmentData = $orderFulfillmentFeedContent->addChild('FulfillmentData');
            $FulfillmentData->addChild('CarrierCode', $market_carrier->code);
            $FulfillmentData->addChild('ShipperTrackingNumber', $shipment_data['tracking']);
            //$FulfillmentData->addChild('ShippingMethod', $market_carrier->code);


            // NO Complete shipping -> partial
            if (isset($validatedData['order_item_id'])) {
                $order_item = OrderItem::find($validatedData['order_item_id']);
                $item = $orderFulfillmentFeedContent->addChild('Item');
                $item->addChild('AmazonOrderItemCode', $order_item->marketProductSku);
                if (isset($validatedData['quantity']))
                    $item->addChild('Quantity', $validatedData['quantity']);
                else
                    $item->addChild('Quantity', $order_item->quantity);
            }

            $feed_filename = $this->shop_dir. 'feeds/' .date('Y-m-d_H-i'). '_orderFulfillmentFeedContent_'.$order->id.'.xml';
            Storage::append($feed_filename, $orderFulfillmentFeedContent->asXML());

            $res_fulfillment = $this->amznPostFeed($orderFulfillmentFeedContent->asXML(), 'POST_ORDER_FULFILLMENT_DATA', 'text/xml');
            Storage::append($this->shop_dir. 'posts/' .date('Y-m-d_H-i'). '_order_fulfillment.json', json_encode($res_fulfillment));

            return $res_fulfillment;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$order, $shipment_data]);
        }
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return 'No code.';
    }


    public function synchronize()
    {
        try {
            $res = [];
            if ($this->iam_role_config = $this->getIAMRoleConfig()) {
                $options = null;

                // GET_MERCHANT_LISTINGS_DATA_LITE: ONLY ACTIVE LISTINGS: seller-sku, quantity, price, product-id, Business Price
                $report_id = $this->amznCreateReport('GET_MERCHANT_LISTINGS_DATA_LITE', $options);
                //$all_report_id = $this->amznCreateReport('GET_MERCHANT_LISTINGS_ALL_DATA', $options);
                //$inactive_report_id = $this->amznCreateReport('GET_MERCHANT_LISTINGS_INACTIVE_DATA', $options);
                //$canceled_report_id = $this->amznCreateReport('GET_MERCHANT_CANCELLED_LISTINGS_DATA', $options);
                //$defect_report_id = $this->amznCreateReport('GET_MERCHANT_LISTINGS_DEFECT_DATA', $options);
                sleep(20);

                //$report_id = '62324018906';
                //$all_report_id = '62333018906';
                //$inactive_report_id = '62325018906';
                //$canceled_report_id = '62326018906';
                //$defect_report_id = '62327018906';

                if ($report_id && $listings = $this->amznGetReportDocument($report_id, 'GET_MERCHANT_LISTINGS_DATA_LITE')) {

                    //$all_listings = $this->amznGetReportDocument($all_report_id, 'GET_MERCHANT_LISTINGS_ALL_DATA');
                    //$inactive_listings = $this->amznGetReportDocument($inactive_report_id, 'GET_MERCHANT_LISTINGS_INACTIVE_DATA');
                    //$canceled_listings = $this->amznGetReportDocument($canceled_report_id, 'GET_MERCHANT_CANCELLED_LISTINGS_DATA');
                    //$defect_listings = $this->amznGetReportDocument($defect_report_id, 'GET_MERCHANT_LISTINGS_DEFECT_DATA');

                    Storage::append($this->shop_dir. 'reports/' .date('Y-m-d_H-i'). '_GET_MERCHANT_LISTINGS_DATA_LITE.json', json_encode($listings));


                    $res['count online'] = count($listings);
                    foreach ($listings as $listing) {

                        $mps_sku = $listing['seller-sku'];
                        $quantity = $listing['quantity'];
                        $price = $listing['price'];
                        $asin = $listing['product-id'];

                        if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $asin)) {

                            if (!$shop_product->enabled) {
                                $shop_product->enabled = true;
                                $res['activeds'][] = $shop_product->mps_sku;
                            }

                            if ($shop_product->mps_sku != $mps_sku) {
                                $res['REMOVE_ONLINE'][] = [$mps_sku, $asin];
                                $shop_product->marketProductSku = null;
                                $shop_product->save();
                                //$shop_product->mps_sku = $mps_sku;
                                //$res['new mps_sku'][] = $shop_product->mps_sku;
                            }

                            $shop_product->save();
                            $res['online'][] = $shop_product->id;
                        }
                        else {
                            $res['REMOVE_ONLINE'][] = [$mps_sku, $asin];
                        }
                    }

                    if (isset($res['REMOVE_ONLINE'])) {
                        $feed_type = 'POST_FLAT_FILE_INVLOADER_DATA';
                        if ($listingsDataContent4Delete = $this->buildFlatFileData4Delete($res['REMOVE_ONLINE'])) {
                            $res['Removed online'] = $this->amznPostFeed($listingsDataContent4Delete, $feed_type, 'text/tab-separated-values');
                        }
                    }

                    if (isset($res['online'])) {
                        $collection4NoAuth = new Collection();
                        foreach ($this->shop->shop_products as $shop_product) {
                            if ($shop_product->isUpgradeable() && !in_array($shop_product->id, $res['online'])) {
                                $collection4NoAuth->add($shop_product);
                            }
                        }

                        // Posible error de precios altos   --> NO AUTH
                        // Inactivo (agotado)               --> deleteSecure
                        // Inactivo                         --> NO AUTH
                        // Remove Online & NO AUTH local
                        if ($collection4NoAuth->count()) {

                            $feed_type = 'POST_FLAT_FILE_INVLOADER_DATA';
                            $delete = true;
                            if ($listingsDataContent = $this->buildFlatFileData($collection4NoAuth, $feed_type, $delete)) {
                                $res['NO AUTH'] = $this->amznPostFeed($listingsDataContent, $feed_type, 'text/tab-separated-values');
                            }

                            foreach ($collection4NoAuth as $shop_product) {
                                if ($shop_product->stock == 0)
                                    $shop_product->deleteSecure();
                                else {
                                    $shop_product->marketProductSku = 'NO AUTH';
                                    $shop_product->save();
                                }
                            }
                        }

                        unset($res['online']);
                    }

                     // REMOVE WITHOUT STOCK
                    foreach ($this->shop->shop_products as $shop_product)
                    if ($shop_product->stock == 0 && !$shop_product->isUpgradeable() && $shop_product->marketProductSku != 'NO AUTH')
                        $shop_product->deleteSecure();

                    return $res;
                }
                else
                    dd($report_id);
            }

            return $this->msgAndStorage(__METHOD__, 'Error sincronizando con el Marketplace.', $report_id ?? null);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $report_id ?? null);
        }
    }


    public function syncCategories($shop_products = null)
    {
        try {
            //$shop_products = $this->getShopProducts4Update()->where('stock', '>', 0)->where('price', '>', 600)->where('price', '<=', 5000);
            //if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            if (!$shop_products) {
                $shop_products = $this->shop->shop_products()
                    ->whereNotNull('marketProductSku')
                    ->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)
                    ->where('is_sku_child', false)
                    ->where('stock', '>', 0)
                    ->where(function (Builder $query) {
                        return $query
                            ->where('created_at', '>', now()->subHours(4))       //->format('Y-m-d H:i:s'))
                            ->orWhereNull('market_category_id');
                    })
                    ->get();
            }
                //$shop_products = $this->getShopProducts4Update()->where('stock', '>', 0)->where('created_at', '>', now()->subDays(1)->format('Y-m-d H:i:s'));
            if (!$shop_products->count()) return 'No se han encontrado productos nuevos para actualizar en esta Tienda';

            //$shop_products = $shop_products->whereNull('market_category_id');
            //if (!$shop_products->count()) return 'No se han encontrado productos sin categoría para actualizar en esta Tienda';

            $res = [];
            if ($this->iam_role_config = $this->getIAMRoleConfig()) {

                $count_product_categories_found = 0;
                $market_params = $this->market->market_params;
                foreach ($shop_products as $shop_product) {

                    $product_category_id = $this->amznlistCatalogCategories($shop_product->marketProductSku, $shop_product->mps_sku);
                    if (!$product_category_id) break;

                    if ($product_category_id != $shop_product->marketProductSku) {

                        $count_product_categories_found++;
                        $shop_product_marketCategoryId = $shop_product->market_category->marketCategoryId ?? null;
                        if ($shop_product_marketCategoryId != $product_category_id) {

                            $shop_product_marketCategoryName = $shop_product->market_category->name ?? null;
                            if ($new_market_category = $this->market->market_categories()->firstWhere('marketCategoryId', $product_category_id)) {

                                $shop_product->market_category_id = $new_market_category->id;
                                $shop_product->save();
                                $shop_product->refresh();

                                $shop_product->setMarketParams();

                                $res['changes'][$product_category_id][] = [
                                    'mps_sku'               => $shop_product->mps_sku,
                                    'mps_category_name'     => $shop_product->category->name ?? null,
                                    'old_marketCategoryName'=> $shop_product_marketCategoryName ?? null,
                                    'new_marketCategoryName'=> $shop_product->market_category->name ?? null,
                                    'old_marketCategoryId'  => $shop_product_marketCategoryId,
                                    'new_marketCategoryId'  => $product_category_id
                                ];

                                /* $old_mp_fee = $shop_product->param_mp_fee;
                                $shop_product->setMarketParams($market_params);
                                if ($old_mp_fee != $shop_product->param_mp_fee)
                                    $changes['MP FEE CHANGES'][] = [
                                        'mp_sku'        => $shop_product->marketProductSku,
                                        'old_mp_fee'    => $old_mp_fee,
                                        'new_mp_fee'    => $shop_product->param_mp_fee
                                    ]; */
                            }
                            else {
                                $res['NO MARKET_CATEGORIES FOUND'][] = $product_category_id;
                                $this->nullAndStorage(__METHOD__, ['NO MARKET_CATEGORIES FOUND', $this->shop->code, $product_category_id]);
                            }
                        }
                    }

                    sleep(5);
                }

                $res['count_online_products_found'] = $shop_products->count();
                $res['count_product_categories_found'] = $count_product_categories_found;
            }

            if (count($res))
                Storage::append($this->storage_dir. 'categories/' .date('Y-m-d_H-i'). '_info.json', json_encode($res));

            return $res;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$shop_products->pluck('marketProductSku')->toArray() ?? null, $res]);
        }
    }


    public function removeWithoutStock()
    {
        try {
            $res = [];
            $collection4NoAuth = new Collection();
            foreach ($this->shop->shop_products as $shop_product) {
                $shop_product->setPriceStock();
                if ($shop_product->stock == 0) {
                    if (!$shop_product->isUpgradeable())
                        $shop_product->deleteSecure();
                    else
                        $collection4NoAuth->add($shop_product);
                }
            }

            // REMOVE ONLINE OFFERS
            if ($collection4NoAuth->count()) {
                $this->iam_role_config = $this->getIAMRoleConfig();
                $feed_type = 'POST_FLAT_FILE_INVLOADER_DATA';
                $delete = true;
                if ($listingsDataContent = $this->buildFlatFileData($collection4NoAuth, $feed_type, $delete)) {
                    $res['DELETES RESULT'] = $this->amznPostFeed($listingsDataContent, $feed_type, 'text/tab-separated-values');
                }

                foreach ($collection4NoAuth as $shop_product) {
                    $shop_product->deleteSecure();
                }
            }

            return 'Eliminados Online: '.$collection4NoAuth->count();

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    public function getSomeBuyboxPrices()
    {
        try {
            $asins = $this->shop->shop_products()->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)
                ->where('stock', '>', 0)
                ->orderBy('buybox_updated_at', 'ASC')
                ->limit(500)
                ->pluck('marketProductSku')
                ->toArray();

            //$asins = ['B075BSLTKB', 'B08VWQ2MVC', 'B098QTG3SJ', 'B0966HZNCJ', 'B08Z85JCQX'];

            $this->iam_role_config = $this->getIAMRoleConfig();
            $count = 0;
            if ($prices = $this->amznGetListingCompetitivePricing($asins)) {

                foreach ($prices as $marketProductSku => $price) {
                    if ($shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first()) {
                        $shop_product->setBuyBoxPrice($price);
                        $count++;
                    }
                }

                $this->logStorage($this->shop_dir. 'prices/', __METHOD__, ['ok' => $count, 'prices' => $prices]);

                return ['ok' => $count, 'prices' => $prices];
            }

            return $this->nullAndStorage(__METHOD__, $asins ?? null);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $asins ?? null);
        }
    }


    public function getAsinByEan($ean)
    {
        try {
            if (!isset($this->iam_role_config)) $this->iam_role_config = $this->getIAMRoleConfig();
            return $this->amznGetAsinByEan($ean);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $asins ?? null);
        }
    }


    /************* REQUEST FUNCTIONS *********************/


    public function getProduct($marketProductSku)
    {
       try {
            $this->iam_role_config = $this->getIAMRoleConfig();
            dd($this->amznGetCatalog($marketProductSku));


            $res = $this->syncCategories();
            dd($res);
            $prices = $this->amznGetListingCompetitivePricing([$marketProductSku]);
            $mp_bfit = $this->amznGetEstimatedFee($marketProductSku, '1066621-16258O-8436044528514', 128.94);
            if ($mp_bfit) {
                $price = 128.94;
                $mp_bfit = (float)$mp_bfit;

                if ($fee_calc = $this->mpFeeCalc($price, $mp_bfit)) {
                    $mp_lot = $fee_calc[0];
                    $mp_fee = $fee_calc[1];
                    $mp_lot_fee = $fee_calc[2];

                   /*  $mp_bfit_min = 0.3;
                    $shop_product->param_mp_lot = $mp_lot;
                    $shop_product->param_mp_fee = $mp_fee * 100;
                    $shop_product->param_mp_lot_fee = $mp_lot_fee * 100;
                    $shop_product->param_mp_bfit_min = $mp_bfit_min;
                    $shop_product->save(); */

                    dd($prices, $mp_bfit, $mp_lot, $mp_fee * 100, $mp_lot_fee * 100);
                }
            }
            dd($prices, $mp_bfit);





            $res = [];
            $count = $count_ok = 0;
            $products = Product::whereSupplierId(41)->get();
            foreach ($products as $product) {
                $count++;
                $item = $this->amznGetAsinByEan($product->ean);
                if (isset($item['asin']) && $item['asin'] != '') {
                    $res[$product->category->name ?? $product->category_id][] = [$product->ean, $item['asin']];
                    $count_ok++;

                    if (isset($item['pn']) && !isset($product->pn)) {
                        $product->pn = $item['pn'];
                        $product->save();
                    }
                }

                sleep(1);
                if ($count == 400) sleep(5);
                if ($count == 800) sleep(5);
                if ($count == 1200) sleep(5);
            }

            Storage::append('errors/grutinet_amazon_asins.json', json_encode([$count, $count_ok, $res]));
            dd($count, $count_ok, $res);




            //$ean = '0065030860352';
            //$asin = $this->amznGetAsinByEan($ean);
            $asin = 'B07W7LF6DY';
            $asins = [$asin];
            $mps_sku = '96821-920-010300-5099206096103';
            $price = 341.03;

            $res = $this->amznGetCompetitivePricing($asins);
            $mp_bfit = $this->amznGetEstimatedFee($asin, $mps_sku, $price);
            $product_category_id = $this->amznlistCatalogCategories($asin, $mps_sku);
            $mp_fee_calc = $this->mpFeeCalc($price, $mp_bfit);

            dd($asins, $res, $mp_bfit, $product_category_id, $mp_fee_calc);


            dd($this->getOrdersReport());




            //dd($this->amznGetCatalog($marketProductSku));
            //$ean = '5099206082816';
            dd($this->amznGetAsinByEan($marketProductSku));

            dd('FI');

            //dd($this->amznGetParticipations());

            $apiInstance = new TokensApi($this->iam_role_config);

            $body = new CreateRestrictedDataTokenRequest();
            $RestrictedResource = new RestrictedResource();
            $RestrictedResource->setPath('/orders/v0/orders');
            $RestrictedResource->setMethod('GET');
            $body->setRestrictedResources($RestrictedResource);

            if ($res = $apiInstance->createRestrictedDataToken($body)) {

                //$this->shop->store_url = $res->getRestrictedDataToken();
                //$this->shop->save();

                dd($res);   //, $res->getRestrictedDataToken(), $res->getModelName(), $res->getExpiresIn());

            }

            dd($body);

        }
        catch (Throwable $th) {
            dd($th);
        }
    }


    public function getAllProducts()
    {
        try {
            //$this->getProductMarketCategories();



            $res = [];
            if ($this->iam_role_config = $this->getIAMRoleConfig()) {

                $options = null;

                // ONLY ACTIVE LISTINGS: seller-sku, quantity, price, product-id, Business Price
                $report_id = $this->amznCreateReport('GET_MERCHANT_LISTINGS_DATA_LITE', $options);
                sleep(15);
                //$report_id = '55602018875';

                if ($report_id && $listings = $this->amznGetReportDocument($report_id, 'GET_MERCHANT_LISTINGS_DATA_LITE')) {

                    $res['count online'] = count($listings);

                    Storage::append($this->shop_dir. 'reports/' .date('Y-m-d_H-i'). '_GET_MERCHANT_LISTINGS_DATA_LITE.json', json_encode($listings));

                    $not_found = [];
                    $collection4Delete = new Collection();
                    foreach ($listings as $listing) {
                        if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $listing['product-id']))
                            $collection4Delete->add($shop_product);
                        else
                            $not_found[] = $listing;
                    }

                    // REMOVE ONLINE OFFERS
                    if ($collection4Delete->count()) {
                        //$this->iam_role_config = $this->getIAMRoleConfig();
                        $feed_type = 'POST_FLAT_FILE_INVLOADER_DATA';
                        $delete = true;
                        if ($listingsDataContent = $this->buildFlatFileData($collection4Delete, $feed_type, $delete)) {
                            $res['DELETES RESULT'] = $this->amznPostFeed($listingsDataContent, $feed_type, 'text/tab-separated-values');
                        }

                        foreach ($collection4Delete as $shop_product) {
                            $shop_product->marketProductSku = null;
                            $shop_product->save();
                        }
                    }


                    dd($report_id, $listings, $collection4Delete, $not_found, $res);

                }
            }

            dd('getAllProducts', $report_id ?? null);

            return $this->msgAndStorage(__METHOD__, 'Error obteniendo todos los productos del MArketplace.', $report_id ?? null);

        } catch (Throwable $th) {
            dd($th);
            return $this->msgWithErrors($th, __METHOD__, $report_id ?? null);
        }
    }



    public function removeAllProducts()
    {
        if ($this->iam_role_config = $this->getIAMRoleConfig()) {
            $options = null;

            // ONLY ACTIVE LISTINGS: seller-sku, quantity, price, product-id, Business Price
            $report_id = $this->amznCreateReport('GET_MERCHANT_LISTINGS_DATA_LITE', $options);
            sleep(15);
            //$report_id = '55602018875';
            if ($report_id && $listings = $this->amznGetReportDocument($report_id, 'GET_MERCHANT_LISTINGS_DATA_LITE')) {

                $collection4Delete = new Collection();
                foreach ($listings as $listing) {
                    if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $listing['product-id']))
                        $collection4Delete->add($shop_product);
                    else
                        $not_found[] = $listing;
                }

                // REMOVE ONLINE OFFERS
                if ($collection4Delete->count()) {
                    $feed_type = 'POST_FLAT_FILE_INVLOADER_DATA';
                    $delete = true;
                    if ($listingsDataContent = $this->buildFlatFileData($collection4Delete, $feed_type, $delete)) {
                        $res['DELETES RESULT'] = $this->amznPostFeed($listingsDataContent, $feed_type, 'text/tab-separated-values');
                    }
                }

                if ($this->shop->shop_products->count()) {
                    $shop_products = $this->shop->shop_products;
                    foreach ($shop_products as $shop_product) {
                        $shop_product->deleteSecure();
                    }
                }

                dd($report_id, $listings, $collection4Delete, $not_found ?? null, $res ?? null);
            }

            dd('removeAllProducts', $report_id ?? null);
        }

        dd('NO getIAMRoleConfig');
    }


    private function getProductMarketCategories()
    {
        try {
            $new_marketCategoryIds = [];
            $not_found = [];
            if ($this->iam_role_config = $this->getIAMRoleConfig()) {
                if ($shop_products = $this->getShopProducts4Update()->where('stock', '>', 0)) {

                    foreach ($shop_products as $shop_product) {
                        if (!$shop_product->market_category_id) {
                            $marketCategoryId = $this->amznlistCatalogCategories($shop_product->marketProductSku, $shop_product->mps_sku);
                            if (!$marketCategoryId) break;

                            if ($marketCategoryId != $shop_product->marketProductSku) {
                                if ($market_category = $this->market->market_categories()->firstWhere('marketCategoryId', $marketCategoryId)) {
                                    $shop_product->market_category_id = $market_category->id;
                                    $shop_product->save();

                                    $new_marketCategoryIds[] = [$shop_product->marketProductSku, $shop_product->mps_sku, $market_category];
                                }
                                else {
                                    $not_found[] = [$shop_product->marketProductSku, $shop_product->mps_sku, $marketCategoryId];
                                }
                            }

                            sleep(3);
                        }
                    }

                    Storage::append($this->shop_dir. 'categories/' .date('Y-m-d_H-i'). '_new.json', json_encode($new_marketCategoryIds));
                    Storage::append($this->shop_dir. 'categories/' .date('Y-m-d_H-i'). '_not_found.json', json_encode($not_found));
                }
            }

            dd('FI', $new_marketCategoryIds, $not_found);

        } catch (Throwable $th) {
            dd($th, $new_marketCategoryIds, $not_found);
            return $this->msgWithErrors($th, __METHOD__, $report_id ?? null);
        }
    }


    public function getMarketParams()
    {
        try {
            $res = [];
            $market_id = 19;
            $mps_mp_categories = [];
            $cat_fees = $cat_fees_null = [];
            $count = 0;

            if ($this->iam_role_config = $this->getIAMRoleConfig()) {
                if ($shop_products = $this->getShopProducts4Update()->where('stock', '>', 0)) {

                    foreach ($shop_products as $shop_product) {
                        if ($shop_product->market_category_id) {
                            $marketCategoryId = $shop_product->market_category->marketCategoryId;
                            if (!in_array($marketCategoryId, $mps_mp_categories)) {
                                $mps_mp_categories[] = $marketCategoryId;

                                $new_marketCategoryId = $this->amznlistCatalogCategories($shop_product->marketProductSku, $shop_product->mps_sku);
                                if (!$new_marketCategoryId) break;

                                $mp_bfit = $this->amznGetEstimatedFee($shop_product->marketProductSku, $shop_product->mps_sku, $shop_product->price);
                                if ($new_marketCategoryId && $new_marketCategoryId != $shop_product->marketProductSku && $mp_bfit) {
                                    $price = $shop_product->price;
                                    $mp_bfit = (float)$mp_bfit;

                                    if ($fee_calc = $this->mpFeeCalc($price, $mp_bfit)) {
                                        $mp_lot = $fee_calc[0];
                                        $mp_fee = $fee_calc[1];
                                        $mp_lot_fee = $fee_calc[2];

                                        $mp_bfit_min = 0.3;
                                        $shop_product->param_mp_lot = $mp_lot;
                                        $shop_product->param_mp_fee = $mp_fee * 100;
                                        $shop_product->param_mp_lot_fee = $mp_lot_fee * 100;
                                        $shop_product->param_mp_bfit_min = $mp_bfit_min;
                                        $shop_product->save();

                                        if ($market_category = $this->market->market_categories()->firstWhere('marketCategoryId', $new_marketCategoryId)) {

                                            $shop_product->market_category_id = $market_category->id;
                                            $shop_product->save();

                                            $market_param = MarketParam::updateOrCreate([
                                                'market_id'             => $market_id,
                                                'market_category_id'    => $market_category->id,
                                            ], [
                                                'fee'                   => $mp_fee * 100,
                                                'lot'                   => $mp_lot,
                                                'lot_fee'               => $mp_lot_fee * 100,
                                                'bfit_min'              => $mp_bfit_min,
                                            ]);
                                        }

                                        $cat_fees[$new_marketCategoryId][] = [$shop_product->marketProductSku, $shop_product->mps_sku, $mp_fee, $fee_calc];
                                    } else {
                                        $mp_fee = round($mp_bfit / $price, 2);
                                        $cat_fees_null[$new_marketCategoryId][] = [$shop_product->marketProductSku, $shop_product->mps_sku, $mp_fee, $price, $mp_bfit];
                                    }

                                    $res[$new_marketCategoryId][] = [$shop_product->param_mp_lot, $shop_product->param_mp_fee, $shop_product->param_mp_lot_fee, $shop_product->param_mp_bfit_min];
                                    $count++;

                                    if ($count >= 10) {
                                        sleep(1);
                                        $count = 0;
                                    }
                                }
                            }
                        }
                    }

                    Storage::append($this->shop_dir. 'fees/' .date('Y-m-d_H-i'). '_category_lot_fees.json', json_encode($cat_fees));
                    Storage::append($this->shop_dir. 'fees/' .date('Y-m-d_H-i'). '_category_lot_fees_null.json', json_encode($cat_fees_null));
                    Storage::append($this->shop_dir. 'fees/' .date('Y-m-d_H-i'). '_category_lot_product.json', json_encode($res));
                }
            }

            dd('FI', $res, $cat_fees, $cat_fees_null, $mps_mp_categories, $shop_products);

        } catch (Throwable $th) {
            dd($th, $res, $cat_fees, $cat_fees_null, $mps_mp_categories, $shop_products);
            return $this->msgWithErrors($th, __METHOD__, $report_id ?? null);
        }
    }


    private function mpFeeCalc($price, $mp_bfit)
    {
        $calcs = [];
        $res = null;
        $fee_types = [
            [100, 0.1545, 0.0824],  // mp_lot, mp_fee, mp_lot_fee
            [200, 0.1545, 0.1030],
            [250, 0.1545, 0.0515],
            [0, 0.0721, 0],
            [0, 0.1545, 0],
        ];

        foreach ($fee_types as $fee_type) {
            if ($fee_type[0] != 0 && $price >= $fee_type[0])
                $mp_bfit_type = ($fee_type[0] * $fee_type[1]) + (($price - $fee_type[0]) * $fee_type[2]);
            else
                $mp_bfit_type = $price * $fee_type[1];

            if ($mp_bfit >= $mp_bfit_type-0.1 && $mp_bfit <= $mp_bfit_type+0.1)
                $res = $fee_type;

            $calcs[] = [$fee_type, $mp_bfit, $mp_bfit_type];
        }

        return $res;
    }


    public function getBuyBoxPrices()
    {
        try {
            $best_price = $best_price_5 = $best_price_10 = $best_price_15 = $best_price_20 = $best_price_25 = $bad_price = [];

            $this->iam_role_config = $this->getIAMRoleConfig();
            $asins = $this->getShopProducts4Update()->pluck('marketProductSku')->toArray();
            $prices = $this->amznGetListingCompetitivePricing($asins);

            foreach ($prices as $marketProductSku => $price) {
                //dd($marketProductSku, $price, $prices);
                if ($shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first()) {

                    $shop_product->setBuyBoxPrice($price);

                    if ($shop_product->price <= $price) $best_price[] = [$marketProductSku => $price];
                    elseif ($shop_product->price <= $price+5 && $shop_product->cost >= 100) $best_price_5[] = [$marketProductSku => $price];
                    elseif ($shop_product->price <= $price+10 && $shop_product->cost >= 200) $best_price_10[] = [$marketProductSku => $price];
                    elseif ($shop_product->price <= $price+15 && $shop_product->cost >= 400) $best_price_15[] = [$marketProductSku => $price];
                    elseif ($shop_product->price <= $price+20 && $shop_product->cost >= 600) $best_price_20[] = [$marketProductSku => $price];
                    elseif ($shop_product->price <= $price+25 && $shop_product->cost >= 800) $best_price_25[] = [$marketProductSku => $price];
                    else $bad_price[] = [$marketProductSku => $price];
                }
            }

            dd($best_price, $best_price_5, $best_price_10, $best_price_15, $best_price_20, $best_price_25, $bad_price, $prices, $asins);

        } catch (Throwable $th) {
            dd($th, $prices ?? null);
        }
    }


    public function setDefaultShopFilters()
    {
        // INTEGRAR ONLY MPE FILES
        // 13-30	Esprinet:	1%
        // 14-27	Desyman:	Mateixos preus. Alguna vegada inclus a favor de MPe
        // 36		Dmi:		Idiomund no integra DMI
        // 38-23	Megasur:	Mateixos preus

        // INTEGRAR IDIOMUND + MPE
        // 10-29	Vinzeo: 	4.5% - 6%
        // 8-31	    Ingram:		Molta diferència
        // 11-35	Techdata:	Molta diferència    MPE NO INTEGRA


        $shop_id = $this->shop->id;
        $status_id = 1;     // nuevo
        $cost_min = 30;
        $cost_max = 1000;
        $stock_min = 5;
        // 1 Blanes 14-27 desyman, 13-30 Esprinet, // 35 Techdata NO ACTUALITZA
        $supplier_ids = [8, 10, 11, 13, 14, 16, 24, 27, 29, 30, 31, 36];
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
                'Conmutadores KVM', 'Lectores de códigos de barras', 'Software de videojuegos','Adaptadores','Cables de alimentación',
                'Accesorios para piscinas y jacuzzis','Dispositivos biométricos']
                )
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
                'Altavoces de repuesto para portátiles','Discos duros','Discos Duros Externos','Discos duros SSD',
                'Fuentes de alimentación para ordenadores','Memoria RAM','Placas madre',
                'Piezas de refrigeración del sistema y ventiladores para ordenadores','Altavoces de repuesto para tablets',
                'Altavoces de repuesto para TV y barras de sonido','Cajas de ordenadores y servidores','Componentes para ordenadores',
                'Concentradores y conmutadores Switches Hubs','Conmutadores KVM','Dispositivos de almacenamiento'])
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
                'Impresoras Multifuncionales','Impresoras, fotocopiadoras y faxes','Supresores de sobretensión y regletas SAI UPS',
                'Puentes y enrutadores','Redes','Repetidores y transceptores','Adaptadores de Power over Ethernet POE',
                'Enrutadores inalámbricos','Enrutadores y puertas de enlace VoIP','Adaptadores y tarjetas de red','Escáneres',
                'Purificadores de aire','Puntos de acceso inalámbrico, Amplificadores y Repetidores de Red','Impresoras Matriciales',
                'Kits de mantenimiento de impresoras','Kits de tambores de impresoras','Adaptadores USB',
                'Adaptadores y tarjetas de interfaz de red','Adaptadores y tarjetas sintonizadoras de televisión',
                'Cargadores y adaptadores de alimentación'])
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

        return redirect()->route('shops.shop_filters.index', [$this->shop])->with('status', 'Filtros creados correctamente.');
    }


    public function getOrdersReport()
    {
        try {
            $res = [];
            if ($this->iam_role_config = $this->getIAMRoleConfig()) {
                $options = null;

                // GET_MERCHANT_LISTINGS_DATA_LITE: ONLY ACTIVE LISTINGS: seller-sku, quantity, price, product-id, Business Price
                // GET_FLAT_FILE_PENDING_ORDERS_DATA
                // GET_CONVERGED_FLAT_FILE_PENDING_ORDERS_DATA
                $report_id = $this->amznCreateReport('GET_CONVERGED_FLAT_FILE_PENDING_ORDERS_DATA', $options);
                sleep(20);
                //$report_id = '52084018851';

                if ($report_id && $amzn_orders = $this->amznGetReportDocument($report_id, 'GET_CONVERGED_FLAT_FILE_PENDING_ORDERS_DATA')) {

                    $res['count online'] = count($amzn_orders);
                    foreach ($amzn_orders as $amzn_order) {

                        dd($amzn_order, $amzn_orders);

                        /* $mps_sku = $listing['seller-sku'];
                        $quantity = $listing['quantity'];
                        $price = $listing['price'];
                        $asin = $listing['product-id'];
                        //$product_id = FacadesMpe::getIdFromMPSSku($mps_sku);

                        if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $asin)) {

                            if (!$shop_product->enabled) {
                                $shop_product->enabled = true;
                                $res['activeds'][] = $shop_product->mps_sku;
                            }

                            if ($shop_product->mps_sku != $mps_sku) {
                                $shop_product->mps_sku = $mps_sku;
                                $res['new mps_sku'][] = $shop_product->mps_sku;
                            }

                            $shop_product->save();
                            $res['online'][] = $shop_product->id;
                        }
                        else {
                            $res['REMOVE_ONLINE'][] = [$mps_sku, $asin];
                        } */
                    }

                    dd($report_id, $res, $amzn_orders);

                    return $res;
                }
            }

            dd('getOrdersReport', $report_id ?? null);

            return $this->msgAndStorage(__METHOD__, 'Error sincronizando con el Marketplace.', $report_id ?? null);

        } catch (Throwable $th) {
            dd($res, $th);
            return $this->msgWithErrors($th, __METHOD__, $report_id ?? null);
        }
    }



}
