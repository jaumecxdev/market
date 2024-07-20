<?php

namespace App\Libraries;


use App\Address;
use App\AttributeMarketAttribute;
use App\Buyer;
use App\Country;
use App\Currency;
use App\Group;
use App\MarketAttribute;
use App\MarketCarrier;
use App\MarketCategory;
use App\Order;
use App\OrderItem;
use App\Product;
use App\Promo;
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
use DTS\eBaySDK\BulkDataExchange\Enums\JobStatus;
use DTS\eBaySDK\BulkDataExchange\Services\BulkDataExchangeService;
use DTS\eBaySDK\BulkDataExchange\Types\AbortJobRequest;
use DTS\eBaySDK\BulkDataExchange\Types\CreateUploadJobRequest;
use DTS\eBaySDK\BulkDataExchange\Types\GetJobsRequest;
use DTS\eBaySDK\BulkDataExchange\Types\GetJobStatusRequest;
use DTS\eBaySDK\BulkDataExchange\Types\StartUploadJobRequest;
use DTS\eBaySDK\BusinessPoliciesManagement\Enums\ProfileType;
use DTS\eBaySDK\BusinessPoliciesManagement\Services\BusinessPoliciesManagementService;
use DTS\eBaySDK\BusinessPoliciesManagement\Types\GetSellerProfilesRequest;
use DTS\eBaySDK\FileTransfer\Types\DownloadFileRequest;
use DTS\eBaySDK\FileTransfer\Types\UploadFileRequest;
use DTS\eBaySDK\Finding\Services\FindingService;
use DTS\eBaySDK\Finding\Types\FindItemsByKeywordsRequest;
use DTS\eBaySDK\MerchantData\Enums\GalleryTypeCodeType;
use DTS\eBaySDK\MerchantData\Enums\ListingTypeCodeType;
use DTS\eBaySDK\MerchantData\MerchantData;
use DTS\eBaySDK\MerchantData\Types\AmountType;
use DTS\eBaySDK\MerchantData\Types\BrandMPNType;
use DTS\eBaySDK\MerchantData\Types\BulkDataExchangeRequestsType;
use DTS\eBaySDK\MerchantData\Types\CategoryType;
use DTS\eBaySDK\MerchantData\Types\InventoryStatusType;
use DTS\eBaySDK\MerchantData\Types\ItemType;
use DTS\eBaySDK\MerchantData\Types\MerchantDataRequestHeaderType;
use DTS\eBaySDK\MerchantData\Types\NameValueListArrayType;
use DTS\eBaySDK\MerchantData\Types\NameValueListType;
use DTS\eBaySDK\MerchantData\Types\PictureDetailsType;
use DTS\eBaySDK\MerchantData\Types\PicturesType;
use DTS\eBaySDK\MerchantData\Types\ProductListingDetailsType;
use DTS\eBaySDK\MerchantData\Types\AddFixedPriceItemRequestType;
use DTS\eBaySDK\MerchantData\Types\EndFixedPriceItemRequestType as TypesEndFixedPriceItemRequestType;
use DTS\eBaySDK\MerchantData\Types\EndFixedPriceItemResponseType;
use DTS\eBaySDK\MerchantData\Types\ReviseFixedPriceItemRequestType;
use DTS\eBaySDK\MerchantData\Types\ReviseInventoryStatusRequestType;
use DTS\eBaySDK\MerchantData\Types\SellerPaymentProfileType;
use DTS\eBaySDK\MerchantData\Types\SellerProfilesType;
use DTS\eBaySDK\MerchantData\Types\SellerReturnProfileType;
use DTS\eBaySDK\MerchantData\Types\SellerShippingProfileType;
use DTS\eBaySDK\MerchantData\Types\StorefrontType;
use DTS\eBaySDK\MerchantData\Types\UploadSiteHostedPicturesRequestType;
use DTS\eBaySDK\MerchantData\Types\VariationProductListingDetailsType;
use DTS\eBaySDK\MerchantData\Types\VariationSpecificPictureSetType;
use DTS\eBaySDK\MerchantData\Types\VariationsType;
use DTS\eBaySDK\MerchantData\Types\VariationType;
use DTS\eBaySDK\Sdk;
use DTS\eBaySDK\Trading\Enums\DetailLevelCodeType;
use DTS\eBaySDK\Trading\Enums\EndReasonCodeType;
use DTS\eBaySDK\Trading\Enums\ListingDurationCodeType;
use DTS\eBaySDK\Trading\Enums\PictureSetCodeType;
use DTS\eBaySDK\Trading\Services\TradingService;
use DTS\eBaySDK\Trading\Types\CompleteSaleRequestType;
use DTS\eBaySDK\Trading\Types\CustomSecurityHeaderType;
use DTS\eBaySDK\Trading\Types\EndFixedPriceItemRequestType;
use DTS\eBaySDK\Trading\Types\GetApiAccessRulesRequestType;
use DTS\eBaySDK\Trading\Types\GetCategoriesRequestType;
use DTS\eBaySDK\Trading\Types\GetCategoryFeaturesRequestType;
use DTS\eBaySDK\Trading\Types\GetCategorySpecificsRequestType;
use DTS\eBaySDK\Trading\Types\GetItemRequestType;
use DTS\eBaySDK\Trading\Types\GetMyeBaySellingRequestType;
use DTS\eBaySDK\Trading\Types\GetOrdersRequestType;
use DTS\eBaySDK\Trading\Types\GetStoreRequestType;
use DTS\eBaySDK\Trading\Types\ItemListCustomizationType;
use DTS\eBaySDK\Trading\Types\LineItemType;
use DTS\eBaySDK\Trading\Types\PaginationType;
use DTS\eBaySDK\Trading\Types\ShipmentLineItemType;
use DTS\eBaySDK\Trading\Types\ShipmentTrackingDetailsType;
use DTS\eBaySDK\Trading\Types\ShipmentType;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EbayWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    private $credentials = null;
    private $requester_credentials = null;
    private $global_id = null;
    private $site_id = null;

    private $paypal_fee = 0.029;
    private $paypal_fee_addon = 0.35;

    private $payment_profile_id = 1;
    private $return_profile_id = 1;
    private $shipping_profile_id = 1;


    const DEFAULT_CONFIG = [
        // MarketWS
        'header' => null,
        'header_rows' => 1,
        'order_status_ignored' => ['CancelPending'],
        'errors_ignored' => ['21915465', '21920270', '21919189', '21917091'],
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



    function __construct(Shop $shop)
    {
        parent::__construct($shop);
        /* $this->storage_dir .= $shop->market->code.'/';
        if(!Storage::exists($this->storage_dir)) */
            Storage::makeDirectory($this->storage_dir);

        $this->payment_profile_id = intval($shop->payment);
        $this->return_profile_id = intval($shop->return);
        $this->shipping_profile_id = intval($shop->shipping);

        $this->global_id = $shop->country;
        $this->site_id = $shop->site;
        $this->credentials = [
            'appId'  => $shop->client_id,
            'certId' => $shop->client_secret,
            'devId'  => $shop->dev_id,
        ];

        $this->requester_credentials = new CustomSecurityHeaderType();
        $this->requester_credentials->eBayAuthToken = $shop->token;
    }

    // JOBS TYPE
    // AddFixedPriceItem: New Products
    // ReviseFixedPriceItem: Full Update Products
    // RelistFixedPriceItem: Not use. On Ebay the products end in a time, This reactivates them
    // EndFixedPriceItem: Remove Products
    // ReviseInventoryStatus: Update Price & Stocks of Products


    // ERROR CODES
    // 21918013
    // Puede que el anuncio infrinja la Política de anuncios duplicados.
    // 21919067
    // El anuncio infringe la Política de anuncios duplicados.
    // 21917164
    // Este producto pertenece a otra categoría, por lo que se ha asignado a ella.
    // 21917091
    // La revisión de StartPrice y Quantity solicitada es redundante.
    // 21915465
    // PayPal puede retrasar la transferencia de fondos para garantizar que la transacción no causa problemas.
    // 21920270
    // Algunas de las características del artículo personalizadas han sido sustituidas por las características del producto.
    // 21919189
    // Podrás poner en venta 96.320,72 € más este mes.
    // 73
    // El precio no es válido.



    /************** PRIVATE FUNCTIONS ***************/



    private function responseError($response)
    {
        return ($response->Ack === 'Failure');
    }


    private function storageAndDdErrorMessage($type, $response)
    {
        Storage::put($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$type. '.json',
            json_encode($response->errorMessage->toArray()));

    }


    private function unzipAttach($file_data)
    {
        $filename = $this->storage_dir. 'attachs/' .date('Y-m-d'). '.zip';
        Storage::put($filename, $file_data);
        $zip = new \ZipArchive();
        $zip->open(storage_path('app/'.$filename));

        return $zip->getFromIndex(0);
    }


    /************** PRIVATE FUNCTIONS - GET SERVICES ***************/


    private function getTradingService()
    {
        $trading_service = new TradingService([
            //'apiVersion'    => '1.13.0',
            //'marketplaceId' => 'EBAY-ES',   // DTS\eBaySDK\Constants\MarketplaceIds::ES
            // 'httpOptions' => [ 'timeout' => 5 ],

            'globalId'      => $this->global_id,   // DTS\eBaySDK\Constants\GlobalIds::ES
            'siteId'        => $this->site_id,         // DTS\eBaySDK\Constants\SiteIds::ES
            'credentials'   => $this->credentials,

            'debug'         => [
                'logfn'         => function ($msg) {
                                    },
                ],
            'sandbox'       => false,
        ]);

        return $trading_service;
    }


    private function getBulkDataExchangeService()
    {
        $trading_service_sandbox = new BulkDataExchangeService([
            //'apiVersion'    => '1.13.0',
            //'marketplaceId' => 'EBAY-ES',   // DTS\eBaySDK\Constants\MarketplaceIds::ES
            // 'httpOptions' => [ 'timeout' => 5 ],

            'globalId'      => $this->global_id,   // DTS\eBaySDK\Constants\GlobalIds::ES
            'siteId'        => $this->site_id,         // DTS\eBaySDK\Constants\SiteIds::ES
            'credentials'   => [
                'appId'  => '',
                'certId' => '',
                'devId'  => '',
            ],

            'authToken'   => '',

            'debug'         => [
                'logfn'         => function ($msg) {
                    Storage::put($this->storage_dir. 'debug/' .date('Y-m-d_H-i-s-u'). '.json', $msg);
                },
            ],
            'sandbox'       => true,
        ]);

        $trading_service = new BulkDataExchangeService([

            'globalId'      => $this->global_id,
            'siteId'        => $this->site_id,
            'credentials'   => $this->credentials,

            'authToken'     => $this->shop->token,

            'debug'         => [
                'logfn'         => function ($msg) {
                    Storage::put($this->storage_dir. 'debug/' .date('Y-m-d_H-i-s-u'). '.json', $msg);
                },
            ],
            'sandbox'       => false,
        ]);

        return $trading_service;
    }


    private function getSdkService()
    {
        

        $sdk = new Sdk([
            'credentials'   => $this->credentials,
            'authToken'     => $this->shop->token,
            'sandbox'       => false,
            //'debug'         => true,
        ]);

        return $sdk;
    }


    private function getBusinessPoliciesManagementService()
    {
        $trading_service = new BusinessPoliciesManagementService([
            //'apiVersion'    => '1.13.0',
            //'marketplaceId' => 'EBAY-ES',   // DTS\eBaySDK\Constants\MarketplaceIds::ES
            // 'httpOptions' => [ 'timeout' => 5 ],

            'globalId'      => $this->global_id,   // DTS\eBaySDK\Constants\GlobalIds::ES
            'siteId'        => $this->site_id,         // DTS\eBaySDK\Constants\SiteIds::ES
            'credentials'   => $this->credentials,
            'authToken'   => $this->shop->token,
            'debug'         => [
                'logfn'         => function ($msg) {
                    Storage::put($this->storage_dir. 'debug/' .date('Y-m-d_H-i-s-u'). '.json', $msg);
                },
            ],
            'sandbox'       => false,
        ]);

        return $trading_service;
    }


    private function getFindingService()
    {
        $trading_service = new FindingService([
            //'apiVersion'    => '1.13.0',
            //'marketplaceId' => 'EBAY-ES',   // DTS\eBaySDK\Constants\MarketplaceIds::ES
            // 'httpOptions' => [ 'timeout' => 5 ],

            'globalId'      => $this->global_id,   // DTS\eBaySDK\Constants\GlobalIds::ES
            //'siteId'        => $this->site_id,         // DTS\eBaySDK\Constants\SiteIds::ES
            'credentials'   => $this->credentials,

            'debug'         => [
                'logfn'         => function ($msg) {
                    Storage::put($this->storage_dir. 'debug/' .date('Y-m-d_H-i-s-u'). '.json', $msg);

                },
            ],
            'sandbox'       => false,
        ]);

        return $trading_service;
    }


    /************** PRIVATE FUNCTIONS - GETTERS ***************/


    // TODO: Make only one request. Similar to Mirakl same function
    private function getAllCategoriesRequest($marketCategoryId, $level = 1, $tree_parent_category = '', $root_category_id = null)
    {
        Log::info('getAllCategories: ' .$level. ' - ' .$marketCategoryId. ' - ' .$tree_parent_category);
        $service = $this->getTradingService();

        $request = new GetCategoriesRequestType();
        $request->RequesterCredentials = $this->requester_credentials;
        $request->ErrorLanguage = 'es_ES';
        $request->DetailLevel = ['ReturnAll'];       // ['ReturnAll'];
        //$request->ViewAllNodes = true;
        //if ($level == 1)
        $request->LevelLimit = $level;
        //$request->CategoryParent = ['625','1249','20710','293','58058','220','9800','15032','281'];
        $request->CategoryParent = [$marketCategoryId];

        $response = $service->getCategories($request);
        Storage::put($this->storage_dir. 'categories/' .$marketCategoryId. '.json', json_encode($response->toArray()));

        if (isset($response->Errors)) {
            $this->storageAndDdErrorMessage('getAllCategories', $response);
        }

        if ($response->Ack !== 'Failure') {

            if (!isset($root_category_id)) {
                $root_category = $this->market->root_categories()->where('marketCategoryId', $marketCategoryId)->first();
                if (isset($root_category)) {
                    $root_category_id = $root_category->id;
                    $tree_parent_category = $root_category->name;
                }
            }

            foreach ($response->CategoryArray->Category as $category) {

                if ($category->Expired != true) {
                    if (($category->CategoryLevel == 1) && ($category->CategoryID != $marketCategoryId)) {
                        $root_category = RootCategory::firstOrCreate([
                            'market_id'         => $this->market->id,
                            'name'              => $category->CategoryName,
                            'marketCategoryId'  => $category->CategoryID,
                        ],[]);

                        $rootCategoriesId = RootCategory::where('market_id', $this->market->id)->pluck('marketCategoryId')->all();

                        //if (in_array($category->CategoryID, ['625', '293', '1249', '58058', '15032',    '11450'])) {
                        if (in_array($category->CategoryID, $rootCategoriesId)) {
                            $this->getAllCategoriesRequest($category->CategoryID, 2, $category->CategoryName, $root_category->id);
                        }
                    }
                    else if ($category->CategoryLevel == $level) {
                        if ($category->LeafCategory == true) {

                            MarketCategory::updateOrCreate(
                                [
                                    'market_id'         => $this->market->id,
                                    'marketCategoryId'  => $category->CategoryID,
                                ],
                                [
                                    'name'              => $category->CategoryName,
                                    'parent_id'         => null,
                                    'path'              => $tree_parent_category,
                                    'root_category_id'  => $root_category_id,
                                ]
                            );
                        }
                        else {
                            $this->getAllCategoriesRequest($category->CategoryID, $level + 1,
                                $tree_parent_category . ' / ' .$category->CategoryName, $root_category_id);
                        }
                    }
                }
            }
        }

        return true;
    }


    private function getCategoryFeaturesRequest($mp_category_id = null)
    {
        $service = $this->getTradingService();
        $request = new GetCategoryFeaturesRequestType();
        $request->RequesterCredentials = $this->requester_credentials;
        $request->ErrorLanguage = 'es_ES';
        $request->AllFeaturesForCategory = true;
        if ($mp_category_id) $request->CategoryID = $mp_category_id;
        $request->DetailLevel = [DetailLevelCodeType::C_RETURN_ALL];
        $request->ViewAllNodes = true;

        return $service->getCategoryFeatures($request);
    }


    private function getCategorySpecificsRequest($marketCategoryId)
    {
        $service = $this->getTradingService();
        $request = new GetCategorySpecificsRequestType();
        $request->RequesterCredentials = $this->requester_credentials;
        $request->ErrorLanguage = 'es_ES';
        $request->CategoryID = [$marketCategoryId];
        $request->DetailLevel = [DetailLevelCodeType::C_RETURN_ALL];

        $response = $service->getCategorySpecifics($request);
        Storage::put($this->storage_dir. 'schemas/' .$marketCategoryId. '.json', json_encode($response->toArray()));

        return $response;
    }


    private function getMarketCategoryAttributes(MarketCategory $market_category)
    {
        $response = $this->getCategoryFeaturesRequest($market_category->marketCategoryId);
        if ($response->Ack !== 'Failure') {

            // VARIATIONS (SKU_ATTRIBUTES) - sizes and colors
            // GetCategoryFeatures -> SiteDefaults -> VariationsEnabled = false
            // Category.VariationsEnabled - If true, you can pass in Item.Variations in an
            // Add/Revise/Relist fixed-price item call when you list in this category.
            // true | false     Discos externos y accesorios TRUE, otros FALSE
            $variations_enabled = $response->Category[0]->VariationsEnabled ?? $response->SiteDefaults->VariationsEnabled;

            // SPECIFICS (CATEGORY_ATTRIBUTES) - All attributes
            // If the listing has variations, set ItemSpecifics.Name to Brand, specify the brand with ItemSpecifics.Value,
            // and use MPN or a GTIN to identify the variation in VariationSpecifics or VariationSpecificsSet.
            // 'Enabled' | 'Disables'   Todas las categorias actuales son ENABLED
            $item_specifics_enabled = $response->Category[0]->ItemSpecificsEnabled ?? $response->SiteDefaults->ItemSpecificsEnabled;

            // If Category accepts attributes...
            if ($variations_enabled || $item_specifics_enabled == 'Enabled') {
                $response = $this->getCategorySpecificsRequest($market_category->marketCategoryId);
                if ($response->Ack !== 'Failure') {
                    $this->saveMarketCategoryAttributes($market_category->id, $response->Recommendations);
                }
                else
                    Storage::put($this->storage_dir. 'errors/' .$market_category->marketCategoryId. '_ERROR.json', json_encode($response->toArray()));
            }
        }

        return $response;
    }


    private function getJobStatusRequest($job_id)
    {
        $sdk = $this->getSdkService();
        $exchangeService = $sdk->createBulkDataExchange();
        $getJobStatusRequest = new GetJobStatusRequest();
        $getJobStatusRequest->jobId = $job_id;

        return $exchangeService->getJobStatus($getJobStatusRequest);
    }


    private function getDownloadFileRequest($job_id, $file_reference_id)
    {
        $sdk = $this->getSdkService();
        $transferService = $sdk->createFileTransfer();
        $downloadFileReferenceId = $file_reference_id;
        $downloadFileRequest = new DownloadFileRequest();
        $downloadFileRequest->fileReferenceId = $downloadFileReferenceId;
        $downloadFileRequest->taskReferenceId = $job_id;

        return $transferService->downloadFile($downloadFileRequest);
    }


    private function saveItemsIDOrError($items, $page)
    {
        $pages = 0;
        $goods = $items['goods'] ?? [];
        $bads = $items['bads'] ?? [];
        $response = $this->getMyeBaySellingRequest($page, $pages);

        if (isset($response->ActiveList)) {
            foreach ($response->ActiveList->ItemArray->Item as $item) {
                if (in_array($item->ItemID, $goods)) {
                    $this->shop->shop_products()
                        ->where('mps_sku', $item->SKU)
                        ->update(['marketProductSku' => $item->ItemID]);
                }
                elseif (in_array($item->ItemID, $bads)) {
                    $this->shop->shop_products()
                        ->where('mps_sku', $item->SKU)
                        ->whereNull('marketProductSku')
                        ->update(['marketProductSku' => 'ERROR']);
                }
            }
        }

        if ($page < $pages) {
            $page++;
            $this->saveItemsIDOrError($items, $page);
        }
    }


    private function updateMarketProductSkus($newItemIds)
    {
        if (count($newItemIds)) {
            $responses = $this->getEbayProducts();
            foreach ($responses as $response) {
                if (isset($response->ActiveList)) {
                    foreach ($response->ActiveList->ItemArray->Item as $item) {
                        if (in_array($item->ItemID, $newItemIds)) {
                            $this->shop->shop_products()
                                ->where('mps_sku', $item->SKU)
                                ->update(['marketProductSku' => $item->ItemID]);
                        }
                    }
                }
            }
        }
    }


    private function getWarningErrors($response, $jobType)
    {
        $res = [];
        if (isset($response->Errors)) {
            foreach ($response->Errors as $error)
                if (!in_array($error->ErrorCode, $this->errors_ignored)) {
                    if ($jobType == 'ReviseInventoryStatus') $res['Errors'][$error->ErrorCode][] = $error->ShortMessage;
                    else $res[$response->ItemID]['Errors'][$error->ErrorCode][] = $error->ShortMessage;
                    foreach ($error->ErrorParameters as $parameter)
                        if ($parameter->Value != 'http://pages.ebay.es/help/policies/listing-multi.html')
                            if ($jobType == 'ReviseInventoryStatus') $res['Errors'][$error->ErrorCode]['Params'][] = $parameter->Value;   // ItemID, SKU
                            else $res[$response->ItemID]['Errors'][$error->ErrorCode]['Params'][] = $parameter->Value;
                }

            if ($jobType == 'ReviseInventoryStatus') {
                if (isset($response->InventoryStatus) && isset($res['Errors'])) {
                    foreach ($response->InventoryStatus as $inventory) {
                        $res['Inventories'][$inventory->ItemID][] = $inventory->SKU;
                    }
                }
            }
        }

        return $res;
    }


    private function getJobResponses($responses, $jobType)
    {
        $res = [];
        $total_count = 0;
        $success_count = 0;
        foreach ($responses as $response) {

            $total_count++;
            if ($response->Ack == 'Failure') {
                foreach ($response->Errors as $error)
                    if (!in_array($error->ErrorCode, $this->errors_ignored))
                        $res[$response->Ack][$total_count]['Errors'][$error->ErrorCode][] = $error->ShortMessage;
            }
            // Warning | Success
            else {

                // Goods List. array_keys
                // Works in all Request, except: ReviseInventoryStatus
                if ($jobType == 'AddFixedPriceItem')
                    $res['itemIds'][] = $response->ItemID;

                $success_count++;
                if ($warning_errors = $this->getWarningErrors($response, $jobType))
                    $res[$response->Ack][$total_count][] = $warning_errors;

                if (in_array($jobType, ['AddFixedPriceItem', 'ReviseFixedPriceItem', 'RelistFixedPriceItem']))
                    foreach ($response->Fees->Fee as $fee)
                        if (strval($fee->Fee->value) != '0')
                            $res[$response->Ack][$total_count][$response->ItemID]['Fees'][] = [$fee->Name, $fee->Fee->value];
            }
        }

        $res['total_count'] = $total_count;
        $res['success_count'] = $success_count;

        return $res;
    }


    private function getJobStatus($jobId)
    {
        $res = [];
        $getJobStatusResponse = $this->getJobStatusRequest($jobId);
        if (isset($getJobStatusResponse->errorMessage)) {
            $res[$jobId] = $getJobStatusResponse;
        }
        else {
            Storage::append($this->storage_dir. 'jobs/' .date('Y-m-d'). '_getJobStatus.json',
                json_encode($getJobStatusResponse->toArray()));

            $jobType = $getJobStatusResponse->jobProfile[0]->jobType;        // operation
            $job_status = $getJobStatusResponse->jobProfile[0]->jobStatus;
            $creationTime = $getJobStatusResponse->jobProfile[0]->creationTime->format('Y-m-d H:i');

            $res[$jobId] = [$jobType, $job_status, $creationTime];
            if ($job_status ==  JobStatus::C_COMPLETED) {
                $downloadFileResponse = $this->getDownloadFileRequest($jobId, $getJobStatusResponse->jobProfile[0]->fileReferenceId);

                if (isset($downloadFileResponse->errorMessage)) {
                    $res[$jobId] = $downloadFileResponse;
                } elseif ($downloadFileResponse->hasAttachment()) {
                    $attachment = $downloadFileResponse->attachment();
                    if ($attachment !== false) {
                        $xml = $this->unzipAttach($attachment['data']);
                        if ($xml !== false) {

                            $merchantData = new MerchantData();
                            $responses = $merchantData->$jobType($xml);
                            $res[$jobId] = $this->getJobResponses($responses, $jobType);

                            if ($jobType == 'AddFixedPriceItem' && isset($res[$jobId]['itemIds'])) {
                                $this->updateMarketProductSkus($res[$jobId]['itemIds']);
                                unset($res[$jobId]['itemIds']);
                                $null_product_ids = $this->shop->shop_products()->whereNull('marketProductSku')->pluck('product_id')->all();
                                $this->shop->shop_products()->whereNull('marketProductSku')->update(['marketProductSku' => 'ERROR']);
                                if (count($null_product_ids)) $res[$jobId]['null_product_ids'] = $null_product_ids;
                            }

                            Storage::append($this->storage_dir.date('Y-m-d'). '_' .$jobId .'_' .$jobType. '_getJob.json',
                                json_encode($res));
                        }
                    }
                }
            }
            // Job Removed
            elseif ($job_status ==  JobStatus::C_ABORTED) {
                $res[$jobId] = [
                    'total_count' => 0,
                    'success_count' => 0,
                ];
            }
        }

        return $res;
    }


    private function getJobRequest(ShopJob $shop_job)
    {
        $res = $this->getJobStatus($shop_job->jobId);
        if (count($res)) {
            foreach ($res as $job_res) {
                if (isset($job_res['total_count'])) {
                    $shop_job->total_count = $job_res['total_count'];
                    $shop_job->success_count = $job_res['success_count'];
                    $shop_job->save();
                }
            }
        }

        return $res;
    }


    private function getOrdersRequest($page = 1, $days = 7)
    {
        $service = $this->getTradingService();
        $mp_request = new GetOrdersRequestType();
        $mp_request->RequesterCredentials = $this->requester_credentials;
        $mp_request->ModTimeFrom = now()->addDays(-$days)->toDateTime();
        $mp_request->DetailLevel = [DetailLevelCodeType::C_RETURN_ALL];
        $mp_request->Pagination = new PaginationType();
        $mp_request->Pagination->PageNumber = $page;

        $response = $service->getOrders($mp_request);
        Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_' .$page. '.json', json_encode($response->toArray()));

        return $response;
    }


    /* private function getOrdersPages($response)
    {
        return intval($response->PaginationResult->TotalNumberOfPages);
    } */


    private function getItemRequest($marketProductSku)
    {
        $service = $this->getTradingService();
        $request = new GetItemRequestType();
        $request->RequesterCredentials = $this->requester_credentials;
        $request->ErrorLanguage = 'es_ES';
        $request->DetailLevel = [DetailLevelCodeType::C_RETURN_ALL];
        $request->IncludeItemCompatibilityList = true;
        $request->IncludeItemSpecifics = true;
        $request->IncludeTaxTable = true;
        $request->IncludeWatchCount = true;
        $request->ItemID = $marketProductSku;

        $response = $service->getItem($request);

        Storage::put($this->storage_dir. 'products/' .$marketProductSku. '.json', json_encode($response->toArray()));

        return $response;
    }


    private function getMyeBaySellingRequest($page, &$pages)
    {
        try {

            $service = $this->getTradingService();
            $request = new GetMyeBaySellingRequestType();
            $request->RequesterCredentials = $this->requester_credentials;
            $request->ErrorLanguage = 'es_ES';
            $request->DetailLevel = [DetailLevelCodeType::C_RETURN_ALL];

            // Listing Options: ActiveList, SoldList, UnsoldList

            // Listing Active Products
            $request->ActiveList = new ItemListCustomizationType();
            $request->ActiveList->IncludeNotes = true;
            $request->ActiveList->Pagination = new PaginationType();
            $request->ActiveList->Pagination->PageNumber = $page;

            // Ended without a purchase Products
            /*$request->ActiveList = new ItemListCustomizationType();
            $request->ActiveList->Include = false;
            $request->UnsoldList = new ItemListCustomizationType();
            $request->UnsoldList->Include = true;
            $request->UnsoldList->IncludeNotes = true;
            $request->UnsoldList->Pagination = new PaginationType();
            $request->UnsoldList->Pagination->PageNumber = $page;
            $request->UnsoldList->Pagination->EntriesPerPage = 200; // max: 200*/

            $response = $service->getMyeBaySelling($request);
            Storage::put($this->storage_dir. 'products/myebayselling_' .strval($page). '.json', json_encode($response->toArray()));
            if ($response->Ack !== 'Failure') {
                $pages = isset($response->ActiveList) ?
                    $response->ActiveList->PaginationResult->TotalNumberOfPages :
                    $response->UnsoldList->PaginationResult->TotalNumberOfPages;
            }
            else
                $pages = 0;

            // DTS\eBaySDK\Trading\Types\GetMyeBaySellingResponseType->ActiveList
            //      ->ItemArray->Item (0-199)
            //      ->PaginationResult->TotalNumberOfPages == 2

            return $response;

        } catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/myebayselling_' .strval($page). '.json', json_encode($th->getMessage()));
        }

        return null;
    }


    private function getEbayProducts()
    {
        $page = 1;
        $responses = [];
        do {
            $responses[] = $this->getMyeBaySellingRequest($page, $pages);
            $page++;

        } while ($page <= $pages);

        return $responses;
    }


    private function getGroupsRequest()
    {
        $service = $this->getTradingService();

        $request = new GetStoreRequestType();
        $request->RequesterCredentials = $this->requester_credentials;
        $request->ErrorLanguage = 'es_ES';
        $request->DetailLevel = ['ReturnAll'];
        $response = $service->getStore($request);
        Storage::put($this->storage_dir. 'groups/' .date('Y-m-d'). '.json', json_encode($response->toArray()));

        if (isset($response->Errors)) {
            $this->storageAndDdErrorMessage('getGroups', $response);
            Storage::put($this->storage_dir. 'errors/' .date('Y-m-d'). '_getGroups.json', json_encode($response->toArray()));
            return false;
        }

        if ($response->Ack !== 'Failure') {
            foreach ($response->Store->CustomCategories->CustomCategory as $category) {
                Group::updateOrCreate(
                    [
                        'market_id'             => $this->market->id,
                        'marketGroupId'         => $category->CategoryID
                    ],
                    [
                        'shop_id'               => $this->shop->id,
                        'name'                  => $category->Name,
                        'marketGroupParentId'   => null,
                    ]
                );

                if ($category->ChildCategory) {
                    foreach ($category->ChildCategory as $child) {
                        Group::updateOrCreate(
                            [
                                'market_id'             => $this->market->id,
                                'marketGroupId'         => $child->CategoryID
                            ],
                            [
                                'shop_id'               => $this->shop->id,
                                'name'                  => $child->Name,
                                'marketGroupParentId'   => $category->CategoryID,
                            ]
                        );
                    }
                }
            }

            return $response->Store->CustomCategories->toArray();
        }

        return false;
    }


    /************** PRIVATE FUNCTIONS - BUILDERS ***************/


    private function buildPropertyFeed(AttributeMarketAttribute $attribute_market_attribute,
                                       Property $property,
                                       $value)
    {
        $property_feed = null;

        if ($attribute_market_attribute->if_exists) {
            if ($value == $attribute_market_attribute->if_exists)
                $property_feed = $attribute_market_attribute->if_exists_value;
        }
        else {
            if ($property->custom)
                $property_feed = $value;
            elseif ($property->property_values->count()) {
                $property_feed_value = $this->attribute_match(
                    $attribute_market_attribute->pattern,
                    $attribute_market_attribute->mapping,
                    $value,
                    $property->property_values);

                if ($property_feed_value) {
                    $property_feed = $property_feed_value;
                }
            }
        }

        return $property_feed;
    }


    private function buildPropertyFeedByField(AttributeMarketAttribute $attribute_market_attribute,
                                              Property $property,
                                              $field_value)
    {
        $property_feed = null;
        $value = is_object($field_value) ? $field_value->name : $field_value;
        $property_feed = $this->buildPropertyFeed($attribute_market_attribute, $property, $value);

        return $property_feed;
    }


    private function buildPropertyFeedByAttribute(AttributeMarketAttribute $attribute_market_attribute,
                                                  Property $property,
                                                  Product $product)
    {
        $property_feed = null;
        $attribute = $attribute_market_attribute->attribute;
        $product_attributes = $product->product_attributes->where('attribute_id', $attribute->id);

        if ($property->datatype == 'Text') {

            foreach ($product_attributes as $product_attribute) {
                $property_feed = $this->buildPropertyFeed($attribute_market_attribute, $property, $product_attribute->value);
                if ($property_feed) break;
            }
        }
        else if ($property->datatype == 'LIST') {
            $property_feed_values = null;
            foreach ($product_attributes as $product_attribute) {
                $property_feed = $this->buildPropertyFeed($attribute_market_attribute, $property, $product_attribute->value);
                if ($property_feed) $property_feed_values[] = $property_feed[$property->name];
            }
            if ($property_feed_values) $property_feed = $property_feed_values;
        }

        return $property_feed;
    }


    private function buildItemSpecificsFeed($type_name, ShopProduct $shop_product)
    {
        $item_specifics = null;
        $item_specifics_name_value_list = [];

        //$market_attributes = $shop_product->market_category->market_attributes($type_name)->get();
        $market_attributes = $shop_product->market_category->market_attributes;
        foreach ($market_attributes as $market_attribute) {

            $property_feed = null;
            foreach ($market_attribute->attribute_market_attributes as $attribute_market_attribute) {
                $property = $attribute_market_attribute->property;

                // PROPERTY FIXED
                if ($attribute_market_attribute->fixed && $attribute_market_attribute->fixed_value) {
                    $property_feed = $attribute_market_attribute->fixed_value;
                }
                // PRODUCT FIELD
                elseif ($product_field = $attribute_market_attribute->field) {
                    $property_feed = $this->buildPropertyFeedByField($attribute_market_attribute, $property, $shop_product->product->{$product_field});
                }
                // PRODUCT ATTRIBUTE
                elseif ($attribute_market_attribute->attribute_id) {
                    $property_feed = $this->buildPropertyFeedByAttribute($attribute_market_attribute, $property, $shop_product->product);
                }

                if ($property_feed) {
                    $name_value_list = new NameValueListType();
                    $name_value_list->Name = $market_attribute->name;
                    $name_value_list->Value = [];
                    $name_value_list->Value[] = $property_feed;
                    $item_specifics_name_value_list[] = $name_value_list;
                }
            }
        }

        if (!empty($item_specifics_name_value_list)) {
            $item_specifics = new NameValueListArrayType();
            $item_specifics->NameValueList = $item_specifics_name_value_list;
        }

        return $item_specifics;
    }


    private function buildItemSKUVariation(ShopProduct $shop_product, SupportCollection $sku_market_attributes)
    {
        $shop_product->setPriceStock();
        $variation = new VariationType();
        $variation->SKU = $shop_product->mps_sku; //$shop_product->getMPSSku();
        $variation->Quantity = $shop_product->stock;
        $variation->StartPrice = new AmountType(['value' => $shop_product->price]);

        //$variation->VariationProductListingDetails = new VariationProductListingDetailsType();

        $item_specifics = $this->buildItemSpecificsFeed('type_sku', $shop_product);
        if ($item_specifics) $variation->VariationSpecifics[] = $item_specifics;

        return $variation;
    }


    private function buildItemSKUVariationSpecificsSet(ShopProduct $shop_product, SupportCollection $sku_market_attributes)
    {
        if ($shop_product->product->hasSkuInfo()) {

            $variationSpecificsSet = new NameValueListArrayType();
            foreach ($sku_market_attributes as $sku_market_attribute) {

                $name_values = null;
                foreach ($sku_market_attribute->attribute_market_attributes as $attribute_market_attribute) {
                    $product_field = $attribute_market_attribute->field;
                    // 'size', 'color', 'material', 'style', 'gender'
                    if ($product_field &&
                        $shop_product->product->{$product_field} &&
                        $shop_product->product->isSkuField($product_field)) {

                        $name_values[] = $shop_product->product->{$product_field};
                        foreach ($shop_product->product->childs as $child) {
                            if ($child->{$product_field})
                                $name_values[] = $child->{$product_field};
                        }
                    }
                }

                if (isset($name_values)) {
                    $nameValue = new NameValueListType();
                    $nameValue->Name = $sku_market_attribute->name;
                    $nameValue->Value = array_unique($name_values);
                    $variationSpecificsSet->NameValueList[] = $nameValue;
                }
            }

            return $variationSpecificsSet;
        }

        return null;
    }


    private function buildItemSKUPictures(ShopProduct $shop_product)
    {
        if ($shop_product->product->url_sku_image) {
            $variation_specific_name = $shop_product->product->color ? 'Color' : ($shop_product->product->material ? 'Material' : null);
            if ($variation_specific_name) {
                $variation_specific_values = [];
                $pictures = new PicturesType();
                $pictures->VariationSpecificName = $variation_specific_name;
                $variation_specific_value = $shop_product->product->color ?? $shop_product->product->material;
                if (!in_array($variation_specific_value, $variation_specific_values)) {
                    $variation_specific_values[] = $variation_specific_value;
                    $pictureSet = new VariationSpecificPictureSetType();
                    $pictureSet->VariationSpecificValue = $variation_specific_value;
                    $pictureSet->PictureURL[] = $shop_product->product->public_url_sku_image;
                    $pictures->VariationSpecificPictureSet[] = $pictureSet;

                    foreach ($shop_product->product->childs as $child) {
                        $variation_specific_value = $child->color ?? $child->material;
                        if (!in_array($variation_specific_value, $variation_specific_values)) {
                            $variation_specific_values[] = $variation_specific_value;
                            $pictureSet = new VariationSpecificPictureSetType();
                            $pictureSet->VariationSpecificValue = $variation_specific_value;
                            $pictureSet->PictureURL[] = $child->public_url_sku_image;
                            $pictures->VariationSpecificPictureSet[] = $pictureSet;
                        }
                    }
                }

                return $pictures;
            }
        }

        return null;
    }


    private function buildEbayTitle(Product $product)
    {
        $promo_text = '';
        if ($promo = Promo::whereShopId($this->shop->id)->whereProductId($product->id)->first()) {
            if($promo->begins_at->lte(now()) && $promo->ends_at->gte(now())){
                $promo_text = 'PROMO 5% ';
            }
        }

        return mb_substr($promo_text.$product->buildTitle(), 0, 80);
    }


    private function buildEbayDescription(ShopProduct $shop_product)
    {
        // EDITOR EBAY: Titols: 24px - Lletra: 14px, table: 8px
        // WEB EBAY: Titols: 32px - Lletra: 18px, table: 12px
        $desc = '<span style="font-family: Arial, Helvetica, sans-serif; font-size: 16px">';
        $desc .= $shop_product->buildDescription4Html();
        $desc .= '</span>';

        return $desc;
    }


    private function buildItemFeed(ShopProduct $shop_product)
    {
        $shop_product->setPriceStock();
        $market_category = $shop_product->market_category;
        $title = $this->buildEbayTitle($shop_product->product);

        // SAME VALUES FOR ALL ITEMS

        $item = new ItemType();
        $item->ListingType = ListingTypeCodeType::C_FIXED_PRICE_ITEM;
        // Let the listing be automatically renewed every 30 days until cancelled.
        $item->ListingDuration = ListingDurationCodeType::C_GTC;
        $item->Country = 'ES';
        $item->Location = 'Spain';
        $item->PostalCode = '17300';
        $item->Currency = 'EUR';
        // Return ListingRecommendations containers
        $item->IncludeRecommendations = true;
        // $item->VATDetails = new VATDetailsType();
        // $item->VATDetails->VATPercent = 21;

        // NEW SYSTEM BY SELLER PROFILES
        $item->SellerProfiles = new SellerProfilesType();
        // PayPal:Pago inmediato - 
        $item->SellerProfiles->SellerPaymentProfile = new SellerPaymentProfileType();
        $item->SellerProfiles->SellerPaymentProfile->PaymentProfileID = $this->payment_profile_id;
        // Devoluciones aceptadas,Vendedor,14 días
        $item->SellerProfiles->SellerReturnProfile = new SellerReturnProfileType();
        $item->SellerProfiles->SellerReturnProfile->ReturnProfileID = $this->return_profile_id;
        // Otro envío en 48h + 2 preparació
        $item->SellerProfiles->SellerShippingProfile = new SellerShippingProfileType();
        $item->SellerProfiles->SellerShippingProfile->ShippingProfileID = $this->shipping_profile_id;

        // SPECIFIC DATA FOR ITEM

        // ItemID & StoreCategoryID for ReviseFixedPriceItem
        if (isset($shop_product->marketProductSku) &&
            !empty($shop_product->marketProductSku) &&
            ($shop_product->marketProductSku != 'ERROR')) {
            $item->ItemID = $shop_product->marketProductSku;
        }

        $marketGroupId = $shop_product->shop->shop_groups()->where('market_category_id', $market_category->id)
            ->leftjoin('groups', 'shop_groups.group_id', '=', 'groups.id')->value('marketGroupId');
        if ($marketGroupId) {
            $item->Storefront = new StorefrontType();
            $item->Storefront->StoreCategoryID = intval($marketGroupId);
        }

        // New Product | Manufacturer refurbished | Used
        $status = $shop_product->product->status;
        if ($status)
            $item->ConditionID = ($status->name == 'Nuevo') ? 1000 : (($status->name == 'Remanufacturado') ? 2000 : 3000);

        $item->Title = $title;
        $item->Description = $this->buildEbayDescription($shop_product);

        $item->ProductListingDetails = new ProductListingDetailsType();
        $item->ProductListingDetails->BrandMPN = new BrandMPNType();
        $item->ProductListingDetails->BrandMPN->Brand = $shop_product->product->brand->name;
        // Manufacturer Part Number
        if ($shop_product->product->pn) $item->ProductListingDetails->BrandMPN->MPN =  $shop_product->product->pn;
        if ($shop_product->product->ean) $item->ProductListingDetails->EAN = $shop_product->product->ean;
        if ($shop_product->product->upc) $item->ProductListingDetails->UPC = $shop_product->product->upc;
        if ($shop_product->product->isbn) $item->ProductListingDetails->ISBN = $shop_product->product->isbn;
        $item->ProductListingDetails->UseStockPhotoURLAsGallery = true;
        // $item->ProductListingDetails->IncludeeBayProductDetails = true;      // Default value: true
        $item->PictureDetails = new PictureDetailsType();
        $item->PictureDetails->GalleryType = GalleryTypeCodeType::C_GALLERY;    // PLUS ???
        $item->PictureDetails->PictureURL = $shop_product->product->getAllUrlImages(12)->toArray();

        // SPECIFIC DATA FOR ITEM CATEGORY

        $item->PrimaryCategory = new CategoryType();
        $item->PrimaryCategory->CategoryID = $market_category->marketCategoryId;     // No necesary if matching catalog product

        // CATEGORY SPECIFIC ATTRIBUTES

        $item_specifics = $this->buildItemSpecificsFeed('type_category', $shop_product);
        if ($item_specifics) $item->ItemSpecifics = $item_specifics;

        // SKU VARIATIONS ATTRIBUTES DATA FOR ITEM CATEGORY

        // !$shop_product->product->hasSkuInfo()
        if (!count($shop_product->product->childs)) {

            $item->SKU = $shop_product->mps_sku;        //$shop_product->getMPSSku();
            $item->StartPrice = new AmountType(['value' => $shop_product->price]);
            $item->Quantity = $shop_product->stock;

        }
        // Product has Childs
        else {

            // sku attributes
            $sku_market_attributes = $market_category->market_attributes('type_sku')->get();

            // Set Variation Specifics Set
            $item->Variations = new VariationsType();
            $variationSpecificsSet = $this->buildItemSKUVariationSpecificsSet($shop_product, $sku_market_attributes);
            if ($variationSpecificsSet) $item->Variations->VariationSpecificsSet = $variationSpecificsSet;

            // Set Variation Pictures
            $pictures = $this->buildItemSKUPictures($shop_product);
            if ($pictures) $item->Variations->Pictures[] = $pictures;

            // Build PARENT SKU Variation Attributes
            $variation = $this->buildItemSKUVariation($shop_product, $sku_market_attributes);
            if ($variation) $item->Variations->Variation[] = $variation;

            foreach ($shop_product->product->childs as $child) {
                // Build CHILD SKU Variation Attributes
                $variation = $this->buildItemSKUVariation($child->shop_product($this->shop->id)->first(), $sku_market_attributes);
                if ($variation) $item->Variations->Variation[] = $variation;
            }
        }

        /* if (isset($shop_product->last_product_id)) {
            $shop_product->last_product_id = null;
            $shop_product->save();
        } */

        Storage::put($this->storage_dir. 'feeds/' .date('Y-m-d'). '/' .$shop_product->market_category->marketCategoryId. '/' .
            $shop_product->product_id. '.json', json_encode($item->toArray()));

        return $item;
    }


    private function buildInventoryFeed(ShopProduct $shop_product)
    {
        $shop_product->setPriceStock();

        $inventoryStatus = new InventoryStatusType();
        $inventoryStatus->ItemID = $shop_product->marketProductSku;
        $inventoryStatus->Quantity = $shop_product->stock;
        $inventoryStatus->StartPrice = new AmountType(['value' => $shop_product->price]);
        //$inventoryStatus->SKU = $shop_product->getMPSSku();

        return $inventoryStatus;
    }


    private function buildRelistItemFeed($marketProductSku)
    {
        $item = new ItemType();
        $item->ItemID = $marketProductSku;

        return $item;
    }


    /************** PRIVATE FUNCTIONS - POSTS ***************/


    private function createBulkDataPayload()
    {
        $payload = new BulkDataExchangeRequestsType();
        $payload->Header = new MerchantDataRequestHeaderType();
        $payload->Header->SiteID = intval($this->site_id);         // DTS\eBaySDK\Constants\SiteIds::ES
        $payload->Header->Version = '951';

        return $payload;
    }


    private function buildRequestItemFeed(ShopProduct $shop_product, $request_type)
    {
        $request_type = '\DTS\eBaySDK\MerchantData\Types\\' .$request_type. 'RequestType';
        $request = new $request_type();
        $request->Version = '951';
        $request->Item = $this->buildItemFeed($shop_product);

        return $request;
    }


    private function postRemovePayload(array $marketProductSkus)
    {
        $res = [];
        $payload_end = $this->createBulkDataPayload();
        $count_end = 0;
        foreach ($marketProductSkus as $marketProductSku) {

            $request = new TypesEndFixedPriceItemRequestType();
            $request->Version = '951';
            $request->EndingReason = EndReasonCodeType::C_INCORRECT;
            $request->ItemID = $marketProductSku;

            $payload_end->EndFixedPriceItemRequest[] = $request;

            $count_end++;
        }

        if ($count_end > 0)
            $res[] = $this->postPayload($payload_end, 'EndFixedPriceItem');

        return $res;
    }


    // AddFixedPriceItem, ReviseFixedPriceItem, ReviseInventoryStatus, EndFixedPriceItem, RelistFixedPriceItem, verifyAddFixedPriceItem
    private function postPayload(BulkDataExchangeRequestsType $payload, $job_type = 'ReviseInventoryStatus')
    {
        $products_result = [];
        $sdk = $this->getSdkService();
        $exchangeService = $sdk->createBulkDataExchange();
        $createUploadJobRequest = new CreateUploadJobRequest();
        $createUploadJobRequest->UUID = uniqid();
        // AddFixedPriceItem, ReviseFixedPriceItem, ReviseInventoryStatus
        $createUploadJobRequest->uploadJobType = $job_type;

        $createUploadJobResponse = $exchangeService->createUploadJob($createUploadJobRequest);
        Storage::append($this->storage_dir. 'jobs/' .date('Y-m-d'). '_createUploadJob.json',
            json_encode($createUploadJobResponse->toArray()));

        if (isset($createUploadJobResponse->errorMessage)) {
            $products_result['errors'][] = $createUploadJobResponse->errorMessage->toArray();
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_createUploadJob.json',
                json_encode($createUploadJobResponse->errorMessage->toArray()));
        }
        else {
            $job_id = $createUploadJobResponse->jobId;
            $products_result['job'] = $job_id;
            ShopJob::create([
                'shop_id'       => $this->shop->id,
                'jobId'         => $job_id,
                'operation'     => $job_type,
            ]);

            $uploadFileRequest = new UploadFileRequest();
            $uploadFileRequest->fileReferenceId = $createUploadJobResponse->fileReferenceId;
            $uploadFileRequest->taskReferenceId = $createUploadJobResponse->jobId;
            $uploadFileRequest->fileFormat = 'gzip';

            $payloadXml = $payload->toRequestXml();
            $uploadFileRequest->attachment(gzencode($payloadXml, 9));
            $transferService = $sdk->createFileTransfer();
            $uploadFileResponse = $transferService->uploadFile($uploadFileRequest);

            Storage::append($this->storage_dir. 'jobs/' .date('Y-m-d'). '_uploadFile.json',
                json_encode($uploadFileResponse->toArray()));

            if (isset($uploadFileResponse->errorMessage)) {
                $products_result['errors'][] = $uploadFileResponse->errorMessage->toArray();
                Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_uploadFile.json',
                    json_encode($uploadFileResponse->errorMessage->toArray()));
            }
            else {
                $startUploadJobRequest = new StartUploadJobRequest();
                $startUploadJobRequest->jobId = $createUploadJobResponse->jobId;
                $startUploadJobResponse = $exchangeService->startUploadJob($startUploadJobRequest);

                Storage::append($this->storage_dir. 'jobs/' .date('Y-m-d'). '_startUploadJob.json',
                    json_encode($startUploadJobResponse->toArray()));

                if (isset($startUploadJobResponse->errorMessage)) {
                    $products_result['errors'][] = $startUploadJobResponse->errorMessage->toArray();
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_startUploadJob.json',
                        json_encode($startUploadJobResponse->errorMessage->toArray()));
                }
            }
        }

        Storage::append($this->storage_dir. 'jobs/' .date('Y-m-d'). '_postPayload.json',
            json_encode($products_result));

        Storage::put($this->storage_dir. 'payloads/' .date('Y-m-d'). '_' .$job_type. '.json',
            json_encode($payload->toArray()));

        return $products_result;
    }


    private function postProducts(SupportCollection $shop_products, $request_type)
    {
        $res = [];
        $payload = $this->createBulkDataPayload();
        $product_id_list = [];

        $count_products = 0;
        $payload_type = $request_type. 'Request';
        foreach ($shop_products as $shop_product) {
            $payload->{$payload_type}[] = $this->buildRequestItemFeed($shop_product, $request_type);
            $product_id_list[$shop_product->product_id] = $count_products;
            $count_products++;
        }

        Storage::append($this->storage_dir. 'products/' .date('Y-m-d'). '_postProducts_array.json', json_encode($product_id_list));

        if ($count_products > 0) {
            $res[] = $this->postPayload($payload, $request_type);
        }

        return $res;
    }


    private function postPricesStocksPayload(SupportCollection $shop_products)
    {
        $res = [];
        $payload_add = $this->createBulkDataPayload();
        $payload_inventory = $this->createBulkDataPayload();
        $endFixedPriceItems = [];

        $count_end_add = 0;
        $count_inventory = 0;
        $count_request_inventory = 0;
        $request_inventory = new ReviseInventoryStatusRequestType();
        $request_inventory->Version = '951';
        foreach ($shop_products as $shop_product) {

            // DELETE & CREATE: EndFixedPriceItem & AddFixedPriceItem
            /* if (isset($shop_product->last_product_id)) {
                $endFixedPriceItems[] = $shop_product->marketProductSku;
                $shop_product->marketProductSku = null;
                $shop_product->save();
                $shop_product->refresh();
                $payload_add->AddFixedPriceItemRequest[] = $this->buildRequestItemFeed($shop_product, 'AddFixedPriceItem');

                $count_end_add++;
            }
            // UPDATE PRICES & STOCKS: ReviseInventoryStatus
            else { */
                $count_request_inventory++;
                $count_inventory++;
                $request_inventory->InventoryStatus[] = $this->buildInventoryFeed($shop_product);

                if ($count_request_inventory > 3) {
                    $payload_inventory->ReviseInventoryStatusRequest[] = $request_inventory;

                    $count_request_inventory = 0;
                    $request_inventory = new ReviseInventoryStatusRequestType();
                    $request_inventory->Version = '951';
                }
            //}
        }

        if ($count_request_inventory > 0)
            $payload_inventory->ReviseInventoryStatusRequest[] = $request_inventory;

        if ($count_end_add > 0) {
            $res[] = $this->postRemovePayload($endFixedPriceItems);
            $res[] = $this->postPayload($payload_add, 'AddFixedPriceItem');
        }

        if ($count_inventory > 0) {
            $res[] = $this->postPayload($payload_inventory, 'ReviseInventoryStatus');
        }

        return $res;
    }


    private function resetEbayPayload($marketProductSkus)
    {
        $res = [];
        $payload = $this->createBulkDataPayload();

        $count_products = 0;
        $count_request = 0;
        $request = new ReviseInventoryStatusRequestType();
        $request->Version = '951';
        foreach ($marketProductSkus as $marketProductSku) {

                $count_request++;
                $count_products++;

                $inventoryStatus = new InventoryStatusType();
                $inventoryStatus->ItemID = $marketProductSku;
                $inventoryStatus->Quantity = 0;
                //$inventoryStatus->StartPrice = new AmountType(['value' => $shop_product->price]);

                $request->InventoryStatus[] = $inventoryStatus;

                if ($count_request > 3) {
                    $payload->ReviseInventoryStatusRequest[] = $request;

                    $count_request = 0;
                    $request = new ReviseInventoryStatusRequestType();
                    $request->Version = '951';
                }
        }

        if ($count_request > 0)
            $payload->ReviseInventoryStatusRequest[] = $request;

        if ($count_products > 0) {
            $res[] = $this->postPayload($payload, 'ReviseInventoryStatus');
        }

        return $res;
    }


    private function removeOneProduct($marketProductSku)
    {
        try {
            $service = $this->getTradingService();

            $request = new EndFixedPriceItemRequestType();
            $request->RequesterCredentials = $this->requester_credentials;
            $request->ErrorLanguage = 'es_ES';
            $request->DetailLevel = ['ReturnAll'];

            $request->EndingReason = EndReasonCodeType::C_INCORRECT;        //['Incorrect'];        //[EndReasonCodeType::C_INCORRECT];
            $request->ItemID = $marketProductSku;

            $response = $service->endFixedPriceItem($request);
            Storage::append($this->storage_dir. 'endfixedprice/' .$marketProductSku. '.json', json_encode($response->toArray()));

            if (isset($response->Errors))
                return json_encode($response->toArray());

            if ($response->Ack !== 'Failure') {

                $shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first();
                $shop_product->delete();
                return $marketProductSku;
            }

            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$marketProductSku. '_remove', json_encode($marketProductSku));
            return ['ERROR marketProductSku:' => $marketProductSku];
        }
        catch (Throwable $th) {
            //Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_' .$marketProductSku. '_remove', json_encode($th->getMessage()));
            return ['ERROR:' => $th, 'marketProductSku:' => $marketProductSku];
        }
    }


    private function postEbayTrackings(Order $order, $shipment_data)
    {
        try {
            $service = $this->getTradingService();

            $req = new CompleteSaleRequestType();
            $req->RequesterCredentials = $this->requester_credentials;
            $req->ErrorLanguage = 'es_ES';
            $req->DetailLevel = ['ReturnAll'];

            $req->OrderID = $order->marketOrderId;
            $req->Shipped = true;
            if (isset($shipment_data['order_item_id' ])) $req->OrderLineItemID = $shipment_data['order_item_id' ];
            if (isset($shipment_data['tracking'])) {
                $market_carrier = MarketCarrier::find($shipment_data['market_carrier_id']);
                $req->Shipment = new ShipmentType();
                $req->Shipment->ShipmentTrackingDetails = new ShipmentTrackingDetailsType();
                $req->Shipment->ShipmentTrackingDetails->ShippingCarrierUsed = $market_carrier->name ?? 'Correos Express';
                $req->Shipment->ShipmentTrackingDetails->ShipmentTrackingNumber = $shipment_data['tracking'];
                if (isset($shipment_data['order_item_id'])) {
                    $order_item = OrderItem::find($shipment_data['order_item_id']);
                    $req->Shipment->ShipmentTrackingDetails->ShipmentLineItem = new ShipmentLineItemType();
                    $req->Shipment->ShipmentTrackingDetails->ShipmentLineItem->LineItem = new LineItemType();
                    $req->Shipment->ShipmentTrackingDetails->ShipmentLineItem->LineItem->ItemID = $order_item->marketItemId ?? '';
                    $req->Shipment->ShipmentTrackingDetails->ShipmentLineItem->LineItem->Quantity = $shipment_data['quantity'] ?? $order_item->quantity;
                }
            }

            return $service->completeSale($req);
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_postEbayTrackings.json', json_encode([$order, $shipment_data, $th]));
            return json_encode(['Ack' => 'Failure', 'Errors' => ['LongMessage' => $th->getMessage()]]);
        }
    }



    /************** PRIVATE FUNCTIONS - SAVES & UPDATES ***************/


    private function saveMarketCategoryAttributes($market_category_id, $recomendations) {

        foreach ($recomendations as $recomendation) {
            foreach ($recomendation->NameRecommendation as $namerecomendation) {

                // $namerecomendation->ValidationRules->MaxValues       // 1 | 30
                // $namerecomendation->ValidationRules->AspectUsage     // null | 'Instance' | 'Product' -> Tots null menys 'Garantía del fabricante'
                // $namerecomendation->ValidationRules->ValueFormat     // FullDate | PartialDate | Year -> Tots null

                // specific | variation
                $type = Type::firstOrCreate(
                    [
                        'market_id' => $this->market->id,
                        // Disabled -> No can be Variation | Enables -> YES can be Variation | Null OR NO EXISTS Can be Variation & Specific
                        'name' => ($namerecomendation->ValidationRules->VariationSpecifics == 'Disabled') ? 'type_category' : 'type_sku',
                        'type' => 'market_attribute',
                    ],
                    []
                );

                $market_attribute = MarketAttribute::updateOrCreate(
                    [
                        'market_id' => $this->market->id,
                        'market_category_id' => $market_category_id,
                        'name' => $namerecomendation->Name,   //
                        'code' => null,
                    ],
                    [
                        'type_id' => $type->id,
                        'datatype' => $namerecomendation->ValidationRules->ValueType,          // Text
                        'required' => ($namerecomendation->ValidationRules->MinValues == 1),   // null | 1
                    ]
                );

                $attribute_property = Property::updateOrCreate(
                    [
                        'market_attribute_id' => $market_attribute->id,
                        'name' => null,
                    ],
                    [
                        'datatype' => $namerecomendation->ValidationRules->ValueType,     // Text
                        'required' => ($namerecomendation->ValidationRules->MinValues == 1),   // null | 1
                        // 'SelectionOnly' | 'FreeText' | Prefilled
                        'custom' => ($namerecomendation->ValidationRules->SelectionMode == 'SelectionOnly') ? false : true,
                        'custom_value' => null,
                        'custom_value_field' => null,
                    ]
                );

                foreach ($namerecomendation->ValueRecommendation as $valuerecomendation) {

                    // id, property_id, name, value
                    // $valuerecomendation->ValidationRules": []
                    PropertyValue::firstOrCreate(
                        [
                            'property_id' => $attribute_property->id,
                            'name' => null,
                            'value' => $valuerecomendation->Value,
                        ],
                        []
                    );
                }
            }
        }
    }


    // TODO: When NO $mp_order->ShippingAddress->Phone, HOW TO get Buyer Phone viewed in Order ebay console
    private function updateOrCreateOrder($mp_order)
    {
        try {
            $country = Country::firstOrCreate([
                'code'      => $mp_order->ShippingAddress->Country,
            ],[]);

            $address = Address::firstOrCreate([
                'country_id'            => $country->id,
                'market_id'             => $this->market->id,
                'marketBuyerId'         => $mp_order->BuyerUserID,
            ],[
                'name'                  => $mp_order->ShippingAddress->Name,
                'address1'              => $mp_order->ShippingAddress->Street1,
                'address2'              => isset($mp_order->ShippingAddress->Street2) ? $mp_order->ShippingAddress->Street2 : null,
                'city'                  => $mp_order->ShippingAddress->CityName,
                'state'                 => $mp_order->ShippingAddress->StateOrProvince,
                'zipcode'           => $mp_order->ShippingAddress->PostalCode,
                'phone'                 => $mp_order->ShippingAddress->Phone,
            ]);


            // id, shipping_address_id, billing_address_id, name, email, phone, company_name, tax_region, tax_name, tax_value
            // market_id, marketBuyerId
            $buyer = Buyer::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketBuyerId'         => $mp_order->BuyerUserID,
            ],[
                // ES shopper OR Real name
                'name'                  => $mp_order->ShippingAddress->Name,
                'shipping_address_id'   => isset($address) ? $address->id : null,
                'billing_address_id'    => null,
                'email'                 => null,
                'phone'                 => $mp_order->ShippingAddress->Phone,
                'company_name'          => null,
                'tax_region'            => null,
                'tax_name'              => null,
                'tax_value'             => null,
            ]);

            // id, supplier_id, market_id, name, supplierStatusName, marketStatusName, type
            $status = Status::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketStatusName'      => $mp_order->OrderStatus,
                'type'                  => 'order',
            ],[
                'name'                  => $mp_order->OrderStatus,
            ]);

            $currency = Currency::firstOrCreate([
                'code'             => $mp_order->Total->currencyID,
            ],[]);

            $order = Order::whereMarketId($this->market->id)->whereShopId($this->shop->id)->where('marketOrderId', $mp_order->ExtendedOrderID)->first();
            $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $order = Order::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'marketOrderId'         => $mp_order->ExtendedOrderID,
            ],[
                'buyer_id'              => isset($buyer) ? $buyer->id : null,
                'shipping_address_id'   => isset($address) ? $address->id : null,
                'billing_address_id'    => null,
                'currency_id'           => $currency->id,
                'status_id'             => $status->id,
                'type_id'               => null,
                'SellerId'              => $mp_order->BuyerUserID,
                'SellerOrderId'         => null,
                'info'                  => substr($mp_order->BuyerCheckoutMessage, 0, 255) ?? '',
                'price'                 => $mp_order->Total->value,
                'tax'                   => 0,
                'shipping_price'        => $mp_order->ShippingServiceSelected->ShippingServiceCost->value,
                'shipping_tax'          => 0,
                'notified'              => $notified,
                'notified_updated'      => $notified_updated,
            ]);

            $order->created_at = Carbon::instance($mp_order->CreatedTime)->addHours(1)->format('Y-m-d H:i:s');
            $order->updated_at = Carbon::instance($mp_order->CheckoutStatus->LastModifiedTime)->addHours(1)->format('Y-m-d H:i:s');
            $order->save();

            // EBAY calculate FEE by TRANSACTIONS, NO ORDER_ITEMS
            // THEN put COMISSION in 1 order_item
            $order_items_count = 0;
            foreach ($mp_order->TransactionArray->Transaction as $mp_order_item) {

                $buyer->email = $mp_order_item->Buyer->Email;
                $buyer->save();

                /* if ($shop_product = $this->shop->shop_products()
                    ->where('marketProductSku', $mp_order_item->Item->ItemID)
                    ->first())
                    $product = $shop_product->product;
                else
                    $product = Product::find(FacadesMpe::getIdFromMPSSku($mp_order_item->Item->SKU));

                $price = floatval($mp_order_item->TransactionPrice->value);
                // Ebay informa dels costos de PayPal NO de ebay. ebay_bfit_real == paypal_bfit
                // Paypal fee_bfit: $mp_order->ExternalTransaction['FeeOrCreditAmount']->value  // 3.23,
                // Paypal fee_bfit: $mp_order->MonetaryDetails->Payments->Payment[$payment]->FeeOrCreditAmount->value ?? 0;
                //$paypal_fee = 0.029;
                //$paypal_fee_addon = 0.35;
                $paypal_bfit_real = $mp_order->MonetaryDetails->Payments->Payment[$payment]->FeeOrCreditAmount->value ?? 0;
                $paypal_fee_real = (floatval($paypal_bfit_real) - $this->paypal_fee_addon) / $price;
                $paypal_bfit = ($price * $this->paypal_fee) + $this->paypal_fee_addon;
                $mp_bfit = $price * (floatval($shop_product->param_mp_fee)/100 - $this->paypal_fee);
                $mp_bfit_total = $price * (floatval($shop_product->param_mp_fee) / 100) + floatval($shop_product->param_mp_fee_addon); //$paypal_bfit + $mp_bfit;
                $mp_bfit_real_total = floatval($paypal_bfit_real) + $mp_bfit; */

                $shipping_price = ($order_items_count == 0) ? $mp_order->ShippingServiceSelected->ShippingServiceCost->value : 0;


                $order_item = $order->updateOrCreateOrderItem(
                    $mp_order_item->TransactionID,
                    $mp_order_item->Item->SKU,
                    $mp_order_item->Item->ItemID,
                    $mp_order_item->Item->Title,
                    $mp_order_item->QuantityPurchased,
                    floatval($mp_order_item->TransactionPrice->value),
                    0,
                    $shipping_price,
                    0,
                    null
                );

                $order_items_count++;

                /* if ($shop_product) {
                    $order_item = OrderItem::updateOrCreate([
                        'order_id'          => $order->id,
                        'product_id'        => $product->id ?? null,
                        'marketOrderId'     => $order->marketOrderId,
                        'marketItemId'      => $mp_order_item->TransactionID,
                    ],[
                        'marketProductSku'  => $shop_product->marketProductSku ?? null,
                        'currency_id'       => $currency->id,
                        'MpsSku'            => $mp_order_item->Item->SKU,
                        'name'              => $mp_order_item->Item->Title,
                        'info'              => '',
                        'quantity'          => $mp_order_item->QuantityPurchased,
                        'price'             => $price,
                        'tax'               => 0,
                        'shipping_price'    => 0,
                        'shipping_tax'      => 0,

                        'cost'              => $product->cost ?? 0,
                        'bfit'              => $this->getBfit($price,
                                                    $shop_product->param_fee ?? 0,
                                                    $shop_product->param_bfit_min ?? 0,
                                                    $product->tax ?? 21),

                        'mp_bfit'           => $mp_bfit_real_total,
                    ]);


                    // CONTROL_RATE
                    // $mp_bfit_real QUE DONA EBAY ES sense IVA -> Al compte iva inclòs

                    //$payment_amount = $mp_order->MonetaryDetails->Payments->Payment[0]->PaymentAmount->value;

                    $mp_bfit_paypal = $this->getMarketBfit($price, $shop_product->param_mp_fee ?? 0, $shop_product->param_mp_fee_addon ?? 0, $product->tax ?? 21);
                    $control_rate_info = [
                        'OK'                        => ($mp_bfit_total != $mp_bfit_real_total) ? 'BFIT_LOCAL_DIFERENT_REAL' : 'OK',

                        'mp_fee'                    => $shop_product->param_mp_fee ?? 0,
                        'mp_fee_addon'              => $shop_product->param_mp_fee_addon ?? 0,
                        'paypal_fee_real'           => $paypal_fee_real,
                        'paypal_bfit_REAL'          => $paypal_bfit_real,
                        'paypal_bfit'               => $paypal_bfit,
                        'ebay_bfit'                 => $mp_bfit,
                        'mp_bfit_total'             => $mp_bfit_total,
                        'mp_bfit_REAL_TOTAL'        => $mp_bfit_real_total,
                        'order_id'                  => $order->marketOrderId,
                        'price'                     => $price,
                    ];
                    Storage::append($this->storage_dir. 'orders/' .date('Y-m-d'). '_CONTROL_RATE.json', json_encode($control_rate_info));
                }
                else {
                    Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_ORDER_ITEM.json', json_encode($mp_order->TransactionArray->Transaction));
                } */


            }
        } catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_updateOrCreateOrder.json',
                json_encode([$mp_order->TransactionArray->Transaction, $th->getMessage()]));
            return false;
        }

        return true;
    }


    private function updateOrCreateOrders($response)
    {
        $count_orders = 0;
        foreach ($response->OrderArray->Order as $mp_order) {
            $this->updateOrCreateOrder($mp_order);
            $count_orders++;
        }

        return $count_orders;
    }


    /************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getBrands()
    {
        return null;
    }


    public function getCategories($marketCategoryId = null)
    {
        $marketCategoryId = $marketCategoryId ?? -1;
        $this->getAllCategoriesRequest(strval($marketCategoryId));

        return true;
    }


    public function getAttributes(Collection $market_categories)
    {
        foreach ($market_categories as $market_category) {
            $this->getMarketCategoryAttributes($market_category);
        }

        return true;
    }


    public function getFeed(ShopProduct $shop_product)
    {
        dd($this->buildItemFeed($shop_product));
    }


    public function getJobs()
    {
        Log::channel('commands')->info('get:jobs getJobs');
        // GET non-terminated JOBS
        $jobs_result = [];
        $shop_jobs = $this->shop->shop_jobs()->whereNull('total_count')->get();
        $jobs_result['jobs_count'] = $shop_jobs->count();
        foreach ($shop_jobs as $shop_job)  {

            $job_result = $this->getJobRequest($shop_job);
            $jobs_result['jobs'][] = $job_result;
        }

        return $jobs_result;
    }


    public function getOrders()
    {
        $page = 0;
        $days = 7;
        do {
            $page++;
            $response = $this->getOrdersRequest($page, $days);
            if ($this->responseError($response)) {
                Storage::put($this->storage_dir. 'errors/' .date('Y-m-d'). '_getOrders.json',
                    json_encode($response->toArray()));
                return false;
            }
            else {
                $pages = intval($response->PaginationResult->TotalNumberOfPages);   // $this->getOrdersPages($response);
                $count_orders = $this->updateOrCreateOrders($response);
            }

        } while ($page < $pages);

        return $count_orders;
    }


    public function getGroups()
    {
        return $this->getGroupsRequest();
    }


    public function getCarriers()
    {
        $market_carriers = [
            [
                'market_id'     => $this->market->id,
                'code'      => 'Correos EXPRESS',
                'name'      => 'Correos EXPRESS',
            ],
            [
                'market_id'     => $this->market->id,
                'code'      => 'SEUR',
                'name'      => 'SEUR',
            ],
            [
                'market_id'     => $this->market->id,
                'code'      => 'MRW',
                'name'      => 'MRW',
            ],
            [
                'market_id'     => $this->market->id,
                'code'      => 'UPS',
                'name'      => 'UPS',
            ],
            [
                'market_id'     => $this->market->id,
                'code'      => 'DHL Express',
                'name'      => 'DHL Express',
            ],
            [
                'market_id'     => $this->market->id,
                'code'      => 'Nacex',
                'name'      => 'Nacex',
            ],
            [
                'market_id'     => $this->market->id,
                'code'      => 'GLS',
                'name'      => 'GLS',
            ],
        ];

        DB::table('market_carriers')->insertOrIgnore($market_carriers);
        return true;
    }


    public function getOrderComments(Order $order)
    {
        return false;
    }


    /************ PUBLIC FUNCTIONS - POSTS *******************/


    public function postNewProduct(ShopProduct $shop_product)
    {
        $shop_products = new SupportCollection([$shop_product]);
        return $this->postNewProducts($shop_products);
        //return $this->postProduct($shop_product,'AddFixedPriceItem');
    }


    public function postUpdatedProduct(ShopProduct $shop_product)
    {
        $shop_products = new SupportCollection([$shop_product]);
        return $this->postUpdatedProducts($shop_products);
        //return $this->postProduct($shop_product,'ReviseFixedPriceItem');
    }


    public function postPriceProduct(ShopProduct $shop_product)
    {
        $shop_products = new SupportCollection([$shop_product]);
        return $this->postPricesStocks($shop_products);
        //return $this->postProduct($shop_product,'ReviseInventoryStatus');
    }


    public function postNewProducts($shop_products = null)
    {
        if ($jobs = $this->shop->shop_jobs()->whereOperation('AddFixedPriceItem')->whereNull('total_count')->exists())
            return null;

        $shop_products = $this->getShopProducts4Create($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos nuevos en esta Tienda';

        return $this->postProducts($shop_products, 'AddFixedPriceItem');
    }


    public function postUpdatedProducts($shop_products = null)
    {
        $shop_products = $this->getShopProducts4Update($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

        return $this->postProducts($shop_products, 'ReviseFixedPriceItem');
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
        if (isset($marketProductSku)) {
            return $this->removeOneProduct($marketProductSku);
        }
        else {
            // REMOVE SHOP_PRODUCTS CODITIONAL
            $shop_products = $this->getShopProducts4Update(null);
            if (!$shop_products->count()) return 'No se han encontrado productos para eliminar en esta Tienda';

            $result['count'] = 0;
            $result['count_removed'] = 0;
            $result['count_problems'] = 0;
            $result['marketProductSku_removeds'] = null;
            $result['problems'] = null;
            foreach($shop_products as $shop_product) {

                // REMOVE PRODUCTS WITHOUT ATTRIBUTES
                if (!$shop_product->product->provider_id) {

                    $res = $this->removeOneProduct($shop_product->marketProductSku);
                    if ($res == $shop_product->marketProductSku) {
                        $result['marketProductSku_removeds'][] = $res;
                        $result['count_removed']++;
                    }
                    else {
                        $result['problems'][$shop_product->marketProductSku] = $res;
                        $result['count_problems']++;
                    }

                    $result['count']++;
                }
            }
        }
    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        $res = $this->postEbayTrackings($order, $shipment_data);

        if ($res->Ack == 'Success')
            return true;

        Storage::put($this->storage_dir. 'errors/' .date('Y-m-d'). '_postOrderTrackings.json', $res);
        return (isset($res->Errors)) ? $res->Errors : false;
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return false;
    }


    public function synchronize()
    {
        $res = [];
        try {
            $offer_pages = $this->getEbayProducts();
            foreach ($offer_pages as $offer_page) {
                if (isset($offer_page->ActiveList)) {
                    foreach ($offer_page->ActiveList->ItemArray->Item as $offer) {

                        $marketProductSku = $offer->ItemID;
                        $res['ONLINE_OFFERS'][] = $marketProductSku;
                        $shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku);
                        if (!isset($shop_product)) {

                            $mps_sku = $offer->SKU;
                            //$product_id = FacadesMpe::getIdFromMPSSku($mps_sku);
                            $shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $mps_sku);
                            if (isset($shop_product)) {
                                $res['NEW_SKUS'][$mps_sku][] = $marketProductSku;
                                $shop_product->marketProductSku = $marketProductSku;
                                $shop_product->enabled = true;
                                $shop_product->save();
                            } else {
                                // DELETE ONLINE
                                $res['DELETE_ONLINE'][] = $marketProductSku;
                            }
                        } elseif (!$shop_product->enabled) {
                            $res['ENABLED'][] = $marketProductSku;
                            $shop_product->enabled = true;
                            $shop_product->save();
                        }
                    }
                }
            }

            // RESETS SERVER OFFERS THAT NOT EXIST IN ONLINE
            if (isset($res['ONLINE_OFFERS'])) {
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
                foreach ($res['NEW_SKUS'] as $mps_sku => $marketProductSkus) {
                    if (count($marketProductSkus) > 1)
                        for($i=0; $i<count($marketProductSkus)-1; $i++)
                            $res['DELETE_ONLINE'][] = $marketProductSkus[$i];
                        //$res['DELETE_ONLINE'][] = $marketProductSkus[0];
                }
            }

            // REMOVE ONLINE OFFERS THAT NOT IN SERVER
            if (isset($res['DELETE_ONLINE'])) {
                $res['POST_DELETES'] = $this->postRemovePayload($res['DELETE_ONLINE']);
            }

            return $res;
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_synchronize.txt', $th->getMessage());
            return $th->getMessage();
        }
    }


    public function syncCategories()
    {
        $changes = [];
        try {
            $market_params = $this->market->market_params;
            $offer_pages = $this->getEbayProducts();
            foreach ($offer_pages as $offer_page) {
                if (isset($offer_page->ActiveList)) {
                    foreach ($offer_page->ActiveList->ItemArray->Item as $offer) {

                        $marketProductSku = $offer->ItemID;
                        if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku)) {
                            if ($offer->Quantity > 0 && $offer->QuantityAvailable > 0) {
                                $response = $this->getItemRequest($marketProductSku);
                                if ($response->Ack == 'Success') {
                                    $new_category_code = $response->Item->PrimaryCategory->CategoryID;
                                    $shop_product_category_code = $shop_product->market_category->marketCategoryId;

                                    if ($new_category_code != $shop_product_category_code) {
                                        $shop_product->longdesc = utf8_encode($shop_product->longdesc);
                                        $changes['CATEGORY CHANGES'][$new_category_code][] = [
                                            'old_code' => $shop_product_category_code,
                                            'new_code' => $new_category_code,
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
                                            $shop_product->market_category_id = $new_market_category->id;
                                            $shop_product->save();

                                            $old_mp_fee = $shop_product->param_mp_fee;
                                            $shop_product->setMarketParams($market_params);
                                            if ($old_mp_fee != $shop_product->param_mp_fee) {
                                                $changes['MP FEE CHANGES'][] = [
                                                'mp_sku' => $shop_product->marketProductSku,
                                                'old_mp_fee' => $old_mp_fee,
                                                'new_mp_fee' => $shop_product->param_mp_fee
                                            ];
                                            }
                                        } else {
                                            $changes['NO MARKET_CATEGORIES FOUND'][] = $new_category_code;
                                        }
                                    }
                                }
                            }
                        }
                        else {

                            $changes['NOT FOUND'][] = $offer;
                        }
                    }
                }
            }

            Storage::append($this->storage_dir. 'categories/' .date('Y-m-d'). '_syncCategories.txt', json_encode($changes));
            return $changes;
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_syncCategories.txt', $th->getMessage());
            return $th->getMessage();
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
                $res['POST_DELETES'] = $this->postRemovePayload($res['DELETE_ONLINE']);
            }

            return $res;
        }
        catch (Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_removeWithoutStock.txt', $th->getMessage());
            return $th->getMessage();
        }
    }


    /************* REQUEST FUNCTIONS *********************/


    public function test()
    {
        $list = null;
        $shop_products = $this->shop->shop_products()->get();
        foreach ($shop_products as $shop_product) {
            if ($shop_product->product->supplier_id == 2) {
                $shop_product->setPriceStock();
                $list[$shop_product->product->id] = [
                    'id'        => $shop_product->id,
                    'price'     => $shop_product->price,
                ];
            }
        }
        ksort($list);
        Storage::put($this->storage_dir. 'test.json', json_encode($list));
        dd($list);
    }


    public function getProduct($marketProductSku)
    {
        $response = $this->getItemRequest($marketProductSku);
        dd($response);
    }


    public function getAllProducts()
    {
        /* $shop_products = $this->shop->shop_products()->get();
        $res = null;
        foreach ($shop_products as $shop_product) {
            $res[$shop_product->product->category_id] = $shop_product->product->category->name;
            //$res[] = $shop_product->product->category_id;
        }
        dd($res); */

        $page = 1;
        $responses = [];
        do {
            $responses[] = $this->getMyeBaySellingRequest($page,$pages);
            $page++;

        } while ($page <= $pages);

        // Update ItemIDs
        foreach ($responses as $response) {

            dd($response, $responses);

            /* foreach($response->ActiveList->ItemArray->Item as $item) {
                if (!isset($item->SellingStatus->ListingStatus) || $item->SellingStatus->ListingStatus != 'Active')
                    dd($item, $response, $responses);
            } */


            /* if (isset($response->ActiveList)) {
                foreach ($response->ActiveList->ItemArray->Item as $item) {
                    $this->shop->shop_products()
                        ->where('product_id', $this->getIdFromMPSSku($item->SKU))
                        ->whereNull('marketProductSku')
                        ->update(['marketProductSku' => $item->ItemID]);
                }
            } */
        }

        dd($responses);
    }


    public function setDefaultShopFilters()
    {
        // (`id`, `shop_id`, `product_id`, `brand_id`, `category_id`, `supplier_id`, `type_id`, `status_id`, `cost_min`, `cost_max`, `stock_min`, `stock_max`, `limit_products`)
        $shop_id = $this->shop->id;
        $status_id = 1;     // nuevo
        $stock_min = 5;
        $cost_max = 400;
        // Discos duros: 2898
        // Memoria RAM: 2992
        $categories_id = [1722, 2574, 2576, 2578, 2847, 2887, 2898, 2905, 2919, 2920, 2921, 2923, 2933, 2978, 2992, 2997,
            3038, 3043, 3054, 3067, 3126, 3136, 3155, 3165, 3167, 3178, 3179, 3180, 3182, 3188, 3193, 3194, 3196, 3199, 3223, 3225, 3227, 3235, 3665,
            4236, 4783, 4957,
            5245, 5499];
        // 3188, 3193, 3665, 3067, 3223, 1722, 4957, 3199, 2978, 3054
        $suppliers_outlet_id = [2, 5];
        $suppliers_id = [1, 7, 8, 10, 11, 12, 13, 14];

        // Remove current Shop Filters
        $this->shop->shop_filters()->delete();

        // Add Default Shop Filters
        $res = null;
        foreach($suppliers_outlet_id as $supplier_outlet_id) {
            $res[] = ShopFilter::create([
                'shop_id'       => $shop_id,
                'supplier_id'   => $supplier_outlet_id,
                'stock_min'     => 1,
            ]);
        }

        foreach($suppliers_id as $supplier_id) {
            foreach($categories_id as $category_id) {

                $brand_id = null;
                $cost_min = null;
                // Tarjetas de memoria flash -> Kingston
                if ($category_id == 2997) {
                    $brand_id = 10;
                    $cost_min = 10;
                }

                $res[] = ShopFilter::create([
                    'shop_id'       => $shop_id,
                    'brand_id'      => $brand_id,
                    'category_id'   => $category_id,
                    'supplier_id'   => $supplier_id,
                    'status_id'     => $status_id,
                    'stock_min'     => $stock_min,
                    'cost_min'      => $cost_min,
                    'cost_max'      => $cost_max,
                ]);
            }
        }

        dd($res);
    }


    public function getJob($jobId)
    {
        dd($this->getJobStatus($jobId));
    }




    public function getSellerProfiles()
    {
        $service = $this->getBusinessPoliciesManagementService();
        $request = new GetSellerProfilesRequest();
        $request->includeDetails = true;
        $request->profileType[] = ProfileType::C_PAYMENT;
        $request->profileType[] = ProfileType::C_RETURN_POLICY;
        $request->profileType[] = ProfileType::C_SHIPPING;
//        $request->profileType[] = ProfileType::C_SHIPPING_DISCOUNT;
//        $request->profileType[] = ProfileType::C_SHIPPING_EXCLUSION;
//        $request->profileType[] = ProfileType::C_SHIPPING_RATE;

        $response = $service->getSellerProfiles($request);
        Storage::put($this->storage_dir. 'requests/getSellerProfiles.json', json_encode($response->toArray()));
        dd($response);

        return $response;
    }


    public function postVerifyProducts()
    {
        if ($jobs = $this->shop->shop_jobs()->whereNull('total_count')->exists())
            return null;

        $shop_products = $this->shop->shop_products()
            ->whereNull('marketProductSku')
            ->get();

        if (!$shop_products->count()) return 0;
        return $this->postProducts($shop_products, 'VerifyAddFixedPriceItem');
    }


    public function findItemsByProduct($value)
    {
        $service = $this->getFindingService();
        $request = new FindItemsByKeywordsRequest();

//        $productId = new ProductId();
        $request->keywords = '15-DA0258NS';
//        $productId->type = 'ReferenceID';
//        $request->productId = $productId;

        $response = $service->findItemsByKeywords($request);
        Storage::put($this->storage_dir. 'products/' .$value. '.json', json_encode($response->toArray()));
        dd($response);

        return $response;
    }


    public function removeNonTerminatedJobs()
    {
        $res = [];
        $service = $this->getBulkDataExchangeService();
        $request = new GetJobsRequest();
        $request->jobStatus[] = JobStatus::C_CREATED;
        //$request->jobType = ['ReviseInventoryStatus'];
        $response = $service->getJobs($request);

        if ($response->jobProfile)
            foreach ($response->jobProfile as $jobProfile) {
                if ($jobProfile->jobStatus == 'Created') {

                    Storage::put($this->storage_dir. 'jobs/' .date('Y-m-d'). '_removeFakeJob.json',
                        json_encode($response->toArray()));

                    $service = $this->getBulkDataExchangeService();
                    $request = new AbortJobRequest();
                    $request->jobId = $jobProfile->jobId;
                    $res[$jobProfile->jobId] = $service->abortJob($request);

                    if ($shop_job = $this->shop->shop_jobs()->firstWhere('jobId', $jobProfile->jobId)) {
                        $shop_job->total_count = 0;
                        //$shop_job->success_count = 0;
                        $shop_job->save();
                    }
                }
            }

        dd($res);
    }


    public function RequestGetJobs()
    {
        $exchangeService = $service = $this->getBulkDataExchangeService();
        $getJobsRequest = new GetJobsRequest();

        $jobStatus = new JobStatus();
        $getJobsRequest->jobStatus[] = $jobStatus::C_CREATED;
        $getJobsRequest->jobStatus[] = $jobStatus::C_FAILED;
        $getJobsRequest->jobStatus[] = $jobStatus::C_IN_PROCESS;

        $response = $exchangeService->getJobs($getJobsRequest);
        dd($response);
    }


    public function RequestRemoveJob($jobId)
    {
        $service = $this->getBulkDataExchangeService();
        $request = new AbortJobRequest();
        $request->jobId = $jobId;
        $response = $service->abortJob($request);

        Storage::append($this->storage_dir. 'jobs/' .date('Y-m-d'). '_removeJob.json',
            json_encode($response->toArray()));

        dd($response);
    }


    public function RequestGetJob($jobId, $job_type = 'uploadSiteHostedPictures')
    {
        $getJobStatusResponse = $this->getJobStatusRequest($jobId);

        if (isset($getJobStatusResponse->errorMessage))
            dd($getJobStatusResponse);
        else {
            if ($getJobStatusResponse->jobProfile[0]->jobStatus ==  JobStatus::C_COMPLETED) {
                $downloadFileResponse = $this->getDownloadFileRequest($jobId, $getJobStatusResponse->jobProfile[0]->fileReferenceId);

                if ($downloadFileResponse->hasAttachment()) {
                    $attachment = $downloadFileResponse->attachment();
                    if ($attachment !== false) {

                        $xml = $this->unzipAttach($attachment['data']);
                        if ($xml !== false) {
                            $merchantData = new MerchantData();


                            $responses = $merchantData->{$job_type}($xml);


                            dd($responses);
                        }
                    }
                }

                dd($downloadFileResponse);
            }

            dd($getJobStatusResponse);
        }
    }


    public function uploadImageToEPS($PictureName = 'LocuraHeader', $ExternalPictureURL = 'https://public.mpespecialist.com/img/header.jpg')
    {
        /* Large Merchant Services APIs:
        Merchant Data API (data file)       https://developer.ebay.com/DevZone/merchant-data/CallRef/index.html
        Bulk Data Exchange API (API)
        File Transfer API (API)
         */

        // BULK DATA SYSTEM
        $payload = $this->createBulkDataPayload();

        // Merchant Data API
        $item_request = [];
        $request = new UploadSiteHostedPicturesRequestType();
        $request->Version = '951';
        $request->PictureName = $PictureName;
        $request->ExternalPictureURL = [$ExternalPictureURL];
        $PictureSetCodeType = new PictureSetCodeType();
        $request->PictureSet = $PictureSetCodeType::C_SUPERSIZE;
        //$request->PictureUploadPolicy = new PictureUploadPolicyCodeType();
        //$request->PictureUploadPolicy = PictureUploadPolicyCodeType::C_ADD;
        $item_request[] = $request;
        $payload->UploadSiteHostedPicturesRequest = $item_request;

        // Bulk Data Exchange API (API)
        $sdk = $this->getSdkService();
        $exchangeService = $sdk->createBulkDataExchange();
        $createUploadJobRequest = new CreateUploadJobRequest();
        $createUploadJobRequest->UUID = uniqid();
        $createUploadJobRequest->uploadJobType = 'UploadSiteHostedPictures';
        $createUploadJobResponse = $exchangeService->createUploadJob($createUploadJobRequest);

        if (isset($createUploadJobResponse->errorMessage))
            dd($createUploadJobResponse);
        else {
            ShopJob::create([
                'shop_id' => $this->shop->id,
                'jobId' => $createUploadJobResponse->jobId,
                'operation' => 'UploadSiteHostedPictures',
            ]);

            // File Transfer API (API) - uploadFile & downloadFile
            $uploadFileRequest = new UploadFileRequest();
            $uploadFileRequest->fileReferenceId = $createUploadJobResponse->fileReferenceId;
            $uploadFileRequest->taskReferenceId = $createUploadJobResponse->jobId;
            $uploadFileRequest->fileFormat = 'gzip';

            $payloadXml = $payload->toRequestXml();
            $uploadFileRequest->attachment(gzencode($payloadXml, 9));
            $transferService = $sdk->createFileTransfer();
            $uploadFileResponse = $transferService->uploadFile($uploadFileRequest);

            if (isset($uploadFileResponse->errorMessage))
                dd($payloadXml, $uploadFileResponse);
            else {
                // Bulk Data Exchange API
                $startUploadJobRequest = new StartUploadJobRequest();
                $startUploadJobRequest->jobId = $createUploadJobResponse->jobId;
                $startUploadJobResponse = $exchangeService->startUploadJob($startUploadJobRequest);

                if (isset($startUploadJobResponse->errorMessage))
                    dd($uploadFileResponse, $startUploadJobResponse);

                dd($createUploadJobResponse->jobId, $payloadXml, $uploadFileResponse, $startUploadJobResponse);
            }

            dd($createUploadJobResponse->jobId, $payloadXml, $uploadFileResponse);
        }

        dd($payload, $createUploadJobResponse);
    }


    public function getCategoriesRequest($marketCategoryId)
    {
        $service = $this->getTradingService();
        $request = new GetCategoriesRequestType();
        $request->RequesterCredentials = $this->requester_credentials;
        $request->ErrorLanguage = 'es_ES';
        $request->DetailLevel = ['ReturnAll'];       // ['ReturnAll'];
        //$request->ViewAllNodes = true;
        //if ($level == 1)
        //$request->LevelLimit = $level;
        //$request->CategoryParent = ['625','1249','20710','293','58058','220','9800','15032','281'];
        $request->CategoryParent = [$marketCategoryId];

        $response = $service->getCategories($request);
        Storage::put($this->storage_dir . 'categories/' . $marketCategoryId . '.json', json_encode($response->toArray()));

        return $response;
    }


    public function getCategorySpecifics($marketCategoryId)
    {
        $response = $this->getCategoryFeaturesRequest($marketCategoryId);
        if ($response->Ack !== 'Failure') {

            $variations_enabled = $response->Category[0]->VariationsEnabled ?? $response->SiteDefaults->VariationsEnabled;
            $item_specifics_enabled = $response->Category[0]->ItemSpecificsEnabled ?? $response->SiteDefaults->ItemSpecificsEnabled;

            // If Category accepts attributes...
            if ($variations_enabled || $item_specifics_enabled == 'Enabled') {
                $response = $this->getCategorySpecificsRequest($marketCategoryId);
            }
        }

        dd($response);
    }


    public function relistProduct($marketProductSku, $job_type = 'RelistFixedPriceItem')
    {
        // BULK DATA SYSTEM
        $payload = $this->createBulkDataPayload();

        // Create new product feed
        $item_request = [];
        $request_type = 'DTS\eBaySDK\MerchantData\Types\RelistFixedPriceItemRequestType';
        $request = new $request_type();     //AddFixedPriceItemRequestType || ReviseFixedPriceItemRequestType
        $request->Version = '951';

        //$request->Item = $this->buildItemFeed($shop_product);
        $shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first();
        $request->Item = $this->buildRelistItemFeed($shop_product);

        $item_request[] = $request;
        $payload_type = $job_type. 'Request';
        $payload->{$payload_type} = $item_request;

        return $this->postPayload($payload, $job_type);
    }


    public function getGetApiAccessRulesRequest()
    {
        $service = $this->getTradingService();
        $request = new GetApiAccessRulesRequestType();
        $request->RequesterCredentials = $this->requester_credentials;
        $request->ErrorLanguage = 'es_ES';
        $request->DetailLevel = [DetailLevelCodeType::C_RETURN_ALL];




        $response = $service->getApiAccessRules($request);
        Storage::put($this->storage_dir. 'api/' .date('Y-m-d_H').'_access_rules.json', json_encode($response->toArray()));
        dd($response);

        if ($response->Ack !== 'Failure') {
            $pages = isset($response->ActiveList) ?
                $response->ActiveList->PaginationResult->TotalNumberOfPages :
                $response->UnsoldList->PaginationResult->TotalNumberOfPages;
        }
        else
            $pages = 0;


        return $response;
    }



}
