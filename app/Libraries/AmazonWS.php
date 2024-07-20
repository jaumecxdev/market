<?php

namespace App\Libraries;

use App\Address;
use App\Buyer;
use App\Country;
use App\Currency;
use App\MarketCategory;
use App\Order;
use App\Product;
use App\RootCategory;
use App\Shop;
use App\ShopJob;
use App\ShopProduct;
use App\Status;
use App\Traits\HelperTrait;
use DOMDocument;
use Facades\App\Facades\Mpe as FacadesMpe;
use Throwable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use MarketplaceWebService_Client;
use MarketplaceWebService_Exception;
use MarketplaceWebService_Model_RequestReportRequest;
use MarketplaceWebService_Model_GetReportRequestListRequest;
use MarketplaceWebService_Model_GetReportRequest;
use MarketplaceWebService_Model_SubmitFeedRequest;
use MarketplaceWebService_Model_GetFeedSubmissionListRequest;
use MarketplaceWebServiceOrders_Model_ListOrdersByNextTokenRequest;
use MarketplaceWebService_Model_GetFeedSubmissionResultRequest;
use MarketplaceWebServiceOrders_Model_ListOrderItemsRequest;
use MarketplaceWebServiceOrders_Model_ListOrdersRequest;
use MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest;

use MarketplaceWebService_Model_IdList;
use MarketplaceWebService_Model_ContentType;
use MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenRequest;
use MarketplaceWebServiceOrders_Model_Order;
use MarketplaceWebServiceOrders_Client;
use MarketplaceWebServiceOrders_Model_OrderItem;
use MarketplaceWebServiceProducts_Client;
use MarketplaceWebServiceProducts_Exception;
use MarketplaceWebServiceProducts_Model_ASINListType;
use MarketplaceWebServiceProducts_Model_FeesEstimateRequest;
use MarketplaceWebServiceProducts_Model_FeesEstimateRequestList;
use MarketplaceWebServiceProducts_Model_GetCompetitivePricingForASINRequest;
use MarketplaceWebServiceProducts_Model_GetLowestOfferListingsForASINRequest;
use MarketplaceWebServiceProducts_Model_GetLowestPricedOffersForASINRequest;
use MarketplaceWebServiceProducts_Model_GetMyFeesEstimateRequest;
use MarketplaceWebServiceProducts_Model_GetServiceStatusRequest;
use MarketplaceWebServiceProducts_Model_IdListType;
use MarketplaceWebServiceProducts_Model_MoneyType;
use MarketplaceWebServiceProducts_Model_PriceToEstimateFees;
use SimpleXMLElement;
use MarketplaceWebServiceOrders_Model_GetOrderRequest;

/**
 * Class AmazonWS                   Amazon Web Service
 */
class AmazonWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    private $delimiter = "\t";      // chr(9) TABULADOR

    private $repriceMinimumFee = 3;         // %
    private $repriceSubtractAmount = 0.10;   // €

    private $sellerId = null;
    private $mwsAuthToken = null;
    private $awsAccessKeyId = null;
    private $awsSecretAccessKey = null;

    private $applicationName = null;
    private $applicationVersion = null;
    private $marketplaceId = null;

    private $documentVersion = null;

    private $mws_endpoint = null;


    const DEFAULT_CONFIG = [
        // MarketWS
        'header' => null,
        'header_rows' => 1,
        'order_status_ignored' => [],
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

        $this->sellerId = $shop->marketSellerId;
        $this->mwsAuthToken = $shop->token;
        $this->awsAccessKeyId = $shop->client_id;
        $this->awsSecretAccessKey = $shop->client_secret;

        $this->mws_endpoint = $shop->endpoint;
        $this->marketplaceId = $shop->marketShopId;

        $this->applicationName = $shop->app_name;          
        $this->applicationVersion = $shop->app_version;     // '1';

        $this->documentVersion = '1.01';
    }


    /************** PRIVATE FUNCTIONS - GENERAL ***************/


    private function storageResponse($call, $response, $filetype = 'xml')
    {
        try {
            $filename = $this->shop_dir.'responses/'.date('Y-m-d_H-i-s').'_'.$call;
            $filename .= ($filetype == 'xml') ? '.xml' : 'csv';
            if ($response) {
                $contents = ($filetype == 'xml') ? $response->toXML() : $response;
                Storage::append($filename, $contents);

                return $filename;
            }

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$call, $response, $filetype]);
        }
    }


    // $ex: MarketplaceWebService_Exception || MarketplaceWebServiceProducts_Exception
    private function throwableArray(Throwable $th, $functionName)
    {
        $ex_array = [
            'code'      => $th->getCode() ?? null,
            'file'      => $th->getFile() ?? null,
            'line'      => $th->getLine() ?? null,
            'message'   => $th->getMessage() ?? null,
            'trace'     => $th->getTraceAsString() ?? null,
            //'responseHeaderMetadata' => $ex->getResponseHeaderMetadata() ?? null,
            //'xml' => $ex->getXML() ?? null,
        ];
        Storage::append($this->shop_dir. 'errors/' .date('Y-m-d'). '_'.$functionName.'.json', json_encode($ex_array));
        return $ex_array;
    }


    private function requestIdStorage($res, $serviceName, $storage_type = 'json')
    {
        if ($storage_type == 'json') Storage::append($this->shop_dir. $serviceName. '/' .date('Y-m-d'). '.json', json_encode($res));
        else Storage::append($this->shop_dir. $serviceName. '/' .date('Y-m-d'). '.xml', $res->toXML());

        if ($res->isSetResponseMetadata()) {
            $responseMetadata = $res->getResponseMetadata();
            if ($responseMetadata->isSetRequestId()) {
                $RequestId = $responseMetadata->getRequestId();
                Storage::append($this->shop_dir. $serviceName.'/' .date('Y-m-d'). '_RequestId.txt', (string)$RequestId);
            }
        }
    }


    /* private function getQuotaRemaining($res)
    {
        if ($res->isSetResponseHeaderMetadata()) {
            $responseHeaderMetadata = $res->getResponseHeaderMetadata();
            return $responseHeaderMetadata['x-mws-quota-remaining'];
        }

        return 0;
    } */



    /************** PRIVATE FUNCTIONS - GET SERVICES ***************/


    private function getService()
    {
        $config = [
            'ServiceURL' => 'https://mws.amazonservices.es',
            //'UserAgent' => 'MarketplaceWebServiceProducts PHP7 Library',
            //'Headers' => ['Content-Type' => 'text/xml'],
        ];

        $service = new MarketplaceWebService_Client(
            $this->awsAccessKeyId,
            $this->awsSecretAccessKey,
            $config,
            $this->applicationName,
            $this->applicationVersion
        );

        return $service;
    }


    private function getServiceProduct()
    {
        $config = [
            'ServiceURL' => '1',
        ];

        $service = new MarketplaceWebServiceProducts_Client(
            $this->awsAccessKeyId,
            $this->awsSecretAccessKey,
            $this->applicationName,
            $this->applicationVersion,
            $config
        );

        return $service;
    }


    private function getServiceOrder()
    {
        $config = [
            'ServiceURL' => '',
        ];

        $service = new MarketplaceWebServiceOrders_Client(
            $this->awsAccessKeyId,
            $this->awsSecretAccessKey,
            $this->applicationName,
            $this->applicationVersion,
            $config
        );

        return $service;
    }


    /************** PRIVATE FUNCTIONS - REPORTS ***************/



    /* private function handleReportRequestInfoList($reportRequestInfoList)
    {
        $reportRequestList = [];
        foreach($reportRequestInfoList as $reportRequestInfo) {

            if ($reportRequestInfo->isSetReportProcessingStatus()) {
                $reportProcessingStatus = $reportRequestInfo->getReportProcessingStatus();
                if ($reportProcessingStatus == '_DONE_') {
                    if ($reportRequestInfo->isSetGeneratedReportId()) {
                        $generatedReportId = $reportRequestInfo->getGeneratedReportId();

                        $reportRequestList[] = [
                            'reportRequestId'       => $reportRequestInfo->getReportRequestId(),            // "50025018541"
                            'reportType'            => $reportRequestInfo->getReportType(),                 // "_GET_XML_BROWSE_TREE_DATA_"
                            'reportProcessingStatus'=> $reportRequestInfo->getReportProcessingStatus(),     // "_DONE_"
                            'generatedReportId'     => $generatedReportId,                                  // "24549957718018536"

                        ];
                    }
                }
                elseif (in_array($reportProcessingStatus, ['_CANCELLED_', '_DONE_NO_DATA_']))
                    $reportRequestList[] = false;
            }
        }

        return $reportRequestList;
    } */


    /* private function handleReportList($reportInfoList)
    {
        $reportList = [];
        foreach($reportInfoList as $reportInfo) {
            if ($reportInfo->isSetReportId()) {
                $reportId = $reportInfo->getReportId();
                $reportList[] = [
                    'reportRequestId'   => $reportInfo->getReportRequestId(),   // "50025018541"
                    'reportType'        => $reportInfo->getReportType(),        // "_GET_XML_BROWSE_TREE_DATA_"
                    'reportId'          => $reportId                            // "24675112193018541"
                ];
            }
        }

        return $reportList;
    } */


    private function getCSVReport($contents)
    {
        try {
            $line_count = 0;
            $columns = null;
            $values = null;
            $csv = null;
            $lines = explode("\n", $contents);
            foreach ($lines as $line) {
                $csv_array = str_getcsv($line, $this->delimiter);
                $csv[] = $csv_array;

                // header
                if ($line_count == 0) {
                    foreach($csv_array as $column) {
                        $columns[] = $column;
                    }
                }
                // listings
                else {
                    if (is_array($csv_array) && isset($csv_array[0]))
                        foreach($csv_array as $value) {
                            $values[$line_count][] = $value;
                        }
                }

                $line_count++;
            }

            $filename = $this->shop_dir.'reports/'.date('Y-m-d_H-i-s');
            Storage::put($filename.'.csv', $contents);

            //Storage::put($this->shop_dir. 'reports/' .date('Y-m-d_H-i-s'). '.json', json_encode($csv));

            return [$columns, $values];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $contents);
        }
    }


    private function amznRequestReport($reportType = '_GET_MERCHANT_LISTINGS_DATA_', $reportOptions = null)
    {
        try {
            $req = new MarketplaceWebService_Model_RequestReportRequest();
            //$marketplaceIdArray = ["Id" => [$this->marketplaceId]];
            //$req->setMarketplaceIdList($marketplaceIdArray);
            $req->setMarketplace($this->marketplaceId);
            $req->setMerchant($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $req->setReportType($reportType);
            if (isset($reportOptions)) $req->setReportOptions($reportOptions);   // 'RootNodesOnly=true', BrowseNodeId=6198073031, MarketplaceId=A1RKKUPIHCS9HS

            $service = $this->getService();
            $res = $service->requestReport($req);
            //$this->storageResponse('amznRequestReport', $res);

            if ($res->isSetRequestReportResult() && $requestReportResult = $res->getRequestReportResult())
                if ($requestReportResult->isSetReportRequestInfo() && $reportRequestInfo = $requestReportResult->getReportRequestInfo())
                    if ($reportRequestInfo->isSetReportRequestId() && $reportRequestId = $reportRequestInfo->getReportRequestId())
                        return $reportRequestId;        // "50002018536"

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$reportType, $reportOptions]);
        }
    }


    private function amznGetReportRequestList($reportRequestId, $reportType = '_GET_MERCHANT_LISTINGS_DATA_')
    {
        try {
            $req = new MarketplaceWebService_Model_GetReportRequestListRequest();
            $req->setMarketplace($this->marketplaceId);
            $req->setMerchant($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);

            $reportRequestIdList = new MarketplaceWebService_Model_IdList();
            $reportRequestIdList->setId($reportRequestId);
            $req->setReportRequestIdList($reportRequestIdList);
            //$req->setReportTypeList();
            //$req->setReportProcessingStatusList();  // _SUBMITTED_, _IN_PROGRESS_, _CANCELLED_, _DONE_, _DONE_NO_DATA_

            $service = $this->getService();
            $res = $service->getReportRequestList($req);
            //$this->storageResponse('amznGetReportRequestList', $res);

            if ($res->isSetGetReportRequestListResult() && $getReportRequestListResult = $res->getGetReportRequestListResult())
                if ($getReportRequestListResult->isSetReportRequestInfo() && $reportRequestInfoList = $getReportRequestListResult->getReportRequestInfoList()) {

                    foreach($reportRequestInfoList as $reportRequestInfo) {
                        if ($reportRequestInfo->isSetReportProcessingStatus() &&
                            $reportProcessingStatus = $reportRequestInfo->getReportProcessingStatus())
                            if ($reportProcessingStatus == '_DONE_' &&
                                $reportRequestInfo->isSetGeneratedReportId() &&
                                $generatedReportId = $reportRequestInfo->getGeneratedReportId())
                                    return $generatedReportId;          // 30481625286018751
                                else
                                    return $reportProcessingStatus;     // _CANCELLED_, _DONE_NO_DATA_
                    }
                }

            return null;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$reportRequestId, $reportType]);
        }
    }


    private function amznGetReport($reportId, $reportType = '_GET_MERCHANT_LISTINGS_DATA_')
    {
        try {
            $req = new MarketplaceWebService_Model_GetReportRequest();
            $req->setMarketplace($this->marketplaceId);
            $req->setMerchant($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $req->setReportId($reportId);

            $reportDirectory = $this->shop_dir.'reports/'.$reportType.'/';
            $TXTFilename = date('Y-m-d_H-i-s'). '.txt';         // XML, TXT_CSV, ...
            if(!File::exists($reportDirectory.$TXTFilename)) {
                //Storage::makeDirectory($reportDirectory);
                Storage::put($reportDirectory.$TXTFilename, '');
            }

            $reportFilename = storage_path('app/' .$reportDirectory.$TXTFilename);
            $f = @fopen($reportFilename, 'rw+');
            //$f = @fopen('php://memory', 'rw+');
            $req->setReport($f);

            $service = $this->getService();
            $res = $service->getReport($req);
            //$this->storageResponse('amznGetReport', $res);
            //$this->requestIdStorage($res, $reportType);

            // OPCIO 1
            /* rewind($f);
            $contents = stream_get_contents($f);
            fclose($f);
            return $contents; */

            // OPCIO 2
            if ($res->isSetGetReportResult()) {
                $getReportResult = $res->getGetReportResult();

                if ($getReportResult->isSetContentMd5()) {
                    return stream_get_contents($req->getReport());      // CSV | XML | PDF

                    //$contentMd5 = $getReportResult->getContentMd5();
                    //Storage::put($this->shop_dir. $reportType.'/' .date('Y-m-d_H-i-s'). '_ContentMd5.json', json_encode($contentMd5));

                    /* if ($reportType == '_GET_MERCHANT_LISTINGS_DATA_') {
                        //$report = stream_get_contents($req->getReport());
                        //return simplexml_load_string($report);
                        return $this->getCSVListingsReport(stream_get_contents($req->getReport()));
                    }
                    else {
                        $report = stream_get_contents($req->getReport());
                        //Storage::put($this->shop_dir. $reportType.'/' .date('Y-m-d_H-i-s'). '.xml', $report);
                        return simplexml_load_string($report);
                    } */
                }
            }

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$reportId, $reportType]);
        }
    }


    /* private function getReportRequestListByNextToken($nextToken, $reportType = '_GET_MERCHANT_LISTINGS_DATA_')
    {
        $reportRequestList = [];

        try {
            $req = new MarketplaceWebService_Model_GetReportRequestListByNextTokenRequest();
            $req->setMarketplace($this->marketplaceId);
            $req->setMerchant($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $req->setNextToken($nextToken);

            $service = $this->getService();
            $res = $service->getReportRequestListByNextToken($req);
            $this->requestIdStorage($res, $reportType);

            if ($res->isSetGetReportRequestListByNextTokenResult()) {
                $getReportRequestListByNextTokenResult = $res->getGetReportRequestListByNextTokenResult();

                if ($getReportRequestListByNextTokenResult->isSetReportRequestInfo()) {
                    $reportRequestInfoList = $getReportRequestListByNextTokenResult->getReportRequestInfoList();
                    $reportRequestList = $this->handleReportRequestInfoList($reportRequestInfoList);
                }

                if ($getReportRequestListByNextTokenResult->isSetNextToken()) {
                    $reportRequestList[] = $this->getReportRequestListByNextToken($getReportRequestListByNextTokenResult->getNextToken(), $reportType);
                }
            }

            return $reportRequestList;

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->msgWithErrors($ex, __METHOD__, [$nextToken, $reportType]);
        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$nextToken, $reportType]);
        }
    } */


    /* private function getReportListByNextToken($nextToken, $reportType = '_GET_MERCHANT_LISTINGS_DATA_')
    {
        $reportList = [];

        try {
            $req = new MarketplaceWebService_Model_GetReportListByNextTokenRequest();
            $req->setMerchant($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $req->setNextToken($nextToken);

            $service = $this->getService();
            $res = $service->getReportListByNextToken($req);
            $this->storageResponse('getReportListByNextToken', $res);
            //$this->requestIdStorage($res, $reportType);

            if ($res->isSetGetReportListByNextTokenResult()) {
                $getReportListByNextTokenResult = $res->getGetReportListByNextTokenResult();

                if ($getReportListByNextTokenResult->isSetReportInfo()) {
                    $reportInfoList = $getReportListByNextTokenResult->getReportInfoList();
                    $reportList = $this->handleReportList($reportInfoList);
                }

                if ($getReportListByNextTokenResult->isSetNextToken()) {
                    $reportList[] = $this->getReportListByNextToken($getReportListByNextTokenResult->getNextToken(), $reportType);
                }
            }

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->msgWithErrors($ex, __METHOD__, [$nextToken, $reportType]);
        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$nextToken, $reportType]);
        }

        return $reportList;
    }


    private function getReportList($reportRequestId, $reportType = '_GET_MERCHANT_LISTINGS_DATA_')
    {
        $reportList = [];

        try {
            $req = new MarketplaceWebService_Model_GetReportListRequest();
            $req->setMerchant($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);

            $ReportRequestIdList = new MarketplaceWebService_Model_IdList();
            $req->setReportRequestIdList($ReportRequestIdList->withId($reportRequestId));
            $req->setAcknowledged(false);

            //$reportTypeList = new MarketplaceWebService_Model_TypeList();
            //$req->setReportTypeList($reportTypeList->withType($reportTypeList));

            $service = $this->getService();
            $res = $service->getReportList($req);
            $this->storageResponse('getReportList', $res);
            //$this->requestIdStorage($res, $reportType);

            if ($res->isSetGetReportListResult()) {
                $getReportListResult = $res->getGetReportListResult();

                if ($getReportListResult->isSetReportInfo()) {
                    $reportInfoList = $getReportListResult->getReportInfoList();
                    $reportList = $this->handleReportList($reportInfoList);
                }

                if ($getReportListResult->isSetNextToken()) {
                    $reportList[] = $this->getReportListByNextToken($getReportListResult->getNextToken(), $reportType);
                }
            }

            return $reportList;

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->msgWithErrors($ex, __METHOD__, [$reportRequestId, $reportType]);
        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$reportRequestId, $reportType]);
        }
    }
 */


    private function getReport($reportRequestId, $reportType)
    {
        try {
            //$reportResults = [];
            $generatedReportId = $this->amznGetReportRequestList($reportRequestId);
            if (!is_numeric($generatedReportId)) return $generatedReportId;     // _CANCELLED_, _DONE_NO_DATA_

            return $this->amznGetReport($generatedReportId, $reportType);       // Report contents: XML | CSV (tab-delimited) | PDF.



            /* foreach ($reportRequestList as $reportRequest) {

                //Storage::put($this->shop_dir. 'report/' .date('Y-m-d_H-i-s'). '__getReportRequest_2.json', json_encode($reportRequest));

                // Illegal string offset 'reportProcessingStatus
                if ($reportRequest['reportProcessingStatus'] == '_DONE_') {
                    // Returns: [
                    //    'reportRequestId'   => "50025018541",
                    //    'reportType'        => "_GET_MERCHANT_LISTINGS_DATA_",
                    //    'reportId'          => "24675112193018541"];
                    $reportList = $this->getReportList($reportRequest['reportRequestId'], $reportType);
                    foreach ($reportList as $report)                       // 24663702585018540
                        $reportResults[] =
                }
                else
                    $reportResults[] = $reportRequest;
            } */

            //Storage::put($this->shop_dir. 'report/' .date('Y-m-d_H-i-s'). '__getReportRequest_.json', json_encode($reportResults));
            //return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$reportRequestId, $reportType]);
        }
    }


    private function getAmazonProducts($reportRequestId = null)
    {
        try {
            $reportType = '_GET_MERCHANT_LISTINGS_DATA_';
            if (!isset($reportRequestId)) {
                $reportRequestId = $this->amznRequestReport($reportType);
                sleep(20);
            }

            $generatedReportId = $this->amznGetReportRequestList($reportRequestId);
            if (!is_numeric($generatedReportId)) return $generatedReportId;     // _CANCELLED_, _DONE_NO_DATA_

            $report_contents = $this->amznGetReport($generatedReportId, $reportType);
            if (isset($report_contents) && !in_array($report_contents, ['_CANCELLED_', '_DONE_NO_DATA_'])) {

                [$columns, $values] = $this->getCSVReport($report_contents);
                return [$columns, $values];
            }

            return null;

            /* foreach ($reportResults as $reportResult) {
                if (isset($reportResult['reportProcessingStatus']))
                    return $reportRequestId;
                else
                    foreach ($reportResult as $report)
                        $amazonProducts[] = array_merge($amazonProducts, $report);
            } */

            /* Storage::put($this->shop_dir. 'report/' .date('Y-m-d_H-i-s'). '__getAmazonProducts_.json', json_encode($amazonProducts));
            return $amazonProducts; */

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $reportRequestId);
        }
    }


    /************** PRIVATE FUNCTIONS - AMAZON API ***************/


    private function getMyFeesEstimateRequest($amount, $id, $idType = 'ASIN')
    {
        try {
            $req = new MarketplaceWebServiceProducts_Model_GetMyFeesEstimateRequest();
            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $feesEstimateRequestList = new MarketplaceWebServiceProducts_Model_FeesEstimateRequestList();
            $feesEstimateRequest = new MarketplaceWebServiceProducts_Model_FeesEstimateRequest();
            $feesEstimateRequest->setMarketplaceId($this->marketplaceId);
            $feesEstimateRequest->setIdentifier(rand(1111111111, 9999999999));
            $feesEstimateRequest->setIdType($idType);
            $feesEstimateRequest->setIdValue($id);
            $priceToEstimateFees = new MarketplaceWebServiceProducts_Model_PriceToEstimateFees();
            $moneyType = new MarketplaceWebServiceProducts_Model_MoneyType();
            $moneyType->setCurrencyCode('EUR');
            $moneyType->setAmount($amount);
            $priceToEstimateFees->setListingPrice($moneyType);
            $feesEstimateRequest->setPriceToEstimateFees($priceToEstimateFees);
            $feesEstimateRequestList->setFeesEstimateRequest($feesEstimateRequest);
            $req->setFeesEstimateRequestList($feesEstimateRequestList);

            $service = $this->getServiceProduct();
            $getMyFeesEstimateResponse = $service->getMyFeesEstimate($req);
            //$this->requestIdStorage($getMyFeesEstimateResponse, __FUNCTION__);

            if ($getMyFeesEstimateResponse->isSetGetMyFeesEstimateResult() && $getMyFeesEstimateResult = $getMyFeesEstimateResponse->getGetMyFeesEstimateResult())
                if ($getMyFeesEstimateResult->isSetFeesEstimateResultList() && $feesEstimateResultList = $getMyFeesEstimateResult->getFeesEstimateResultList())
                    if ($feesEstimateResultList->isSetFeesEstimateResult() && $feesEstimateResult = $feesEstimateResultList->getFeesEstimateResult())

                        foreach ($feesEstimateResult as $feesEstimateItem) {
                            if ($feesEstimateItem->isSetFeesEstimate() && $feesEstimate = $feesEstimateItem->getFeesEstimate())
                                if ($feesEstimate->isSetTotalFeesEstimate() && $totalFeesEstimate = $feesEstimate->getTotalFeesEstimate())
                                    if ($totalFeesEstimate->isSetAmount())
                                       return $totalFeesEstimate->getAmount();
                        }

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$amount, $id, $idType]);
        }
    }



    private function amznGetLowestOfferListingsForASIN($asins)
    {
        try {
            $req = new  MarketplaceWebServiceProducts_Model_GetLowestOfferListingsForASINRequest();
            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $req->setMarketplaceId($this->marketplaceId);
            $asinList = new MarketplaceWebServiceProducts_Model_ASINListType();

            //$asins = ['B07VJJXDBX','B07C4B1ZXC'];
            $asinList->setASIN($asins);
            $req->setASINList($asinList);

            $service = $this->getServiceProduct();
            $res = $service->getLowestOfferListingsForASIN($req);
            //$this->requestIdStorage($res, __FUNCTION__);

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $asins);
        }
    }


    private function amznGetMatchingProductForIdRequest($idType = 'EAN')
    {
        try {
            $req = new MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest();
            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);   // Optional
            $req->setMarketplaceId($this->marketplaceId);
            $req->setIdType($idType);

            return $req;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $idType);
        }
    }


    private function amznGetMatchingProductForId($ean, MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest $req)
    {
        try {
            $id_list = new MarketplaceWebServiceProducts_Model_IdListType();
            $id_list->setId($ean);
            $req->setIdList($id_list);
            $service = $this->getServiceProduct();
            $res = $service->getMatchingProductForId($req);
            //$this->requestIdStorage($res, __FUNCTION__);

            return $res;

        } catch (MarketplaceWebServiceProducts_Exception $ex) {
            $this->nullWithErrors($ex, __METHOD__, [$ean, $req]);
        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, [$ean, $req]);
        }
    }


    /************** PRIVATE FUNCTIONS - AMAZON PRICING ***************/


    // No channel information: 20 ains x call
    private function getCompetitivePricingForASINRequest($asins)
    {
        $resp = [];
        try {
            $req = new  MarketplaceWebServiceProducts_Model_GetCompetitivePricingForASINRequest();
            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $req->setMarketplaceId($this->marketplaceId);
            $asinList = new MarketplaceWebServiceProducts_Model_ASINListType();

            //$asins = ['B07VJJXDBX','B07C4B1ZXC'];
            $asinList->setASIN($asins);
            $req->setASINList($asinList);


            $service = $this->getServiceProduct();
            $res = $service->getCompetitivePricingForASIN($req);
            $this->requestIdStorage($res, __FUNCTION__);

            if ($res->isSetGetCompetitivePricingForASINResult()) {
                $getCompetitivePricingForASINResult = $res->getGetCompetitivePricingForASINResult();
                foreach ($getCompetitivePricingForASINResult as $getCompetitivePricingForASIN) {

                    $asin = null;
                    if ($getCompetitivePricingForASIN->isSetASIN()) {
                        $asin = $getCompetitivePricingForASIN->getASIN();
                    }

                    if ($getCompetitivePricingForASIN->isSetProduct()) {
                        $product = $getCompetitivePricingForASIN->getProduct();

                        if ($product->isSetCompetitivePricing()) {
                            $competitivePricing = $product->getCompetitivePricing();
                            if ($competitivePricing->isSetCompetitivePrices()) {
                                $competitivePrices = $competitivePricing->getCompetitivePrices();
                                if ($competitivePrices->isSetCompetitivePrice()) {
                                    $competitivePrice = $competitivePrices->getCompetitivePrice();

                                    foreach ($competitivePrice as $competitivePriceType) {

                                        $condition = null;
                                        $priceId = null;
                                        $amount = null;
                                        $amount_shipping = null;

                                        if ($competitivePriceType->isSetCondition()) {
                                            $condition = $competitivePriceType->getCondition();
                                        }

                                        if ($competitivePriceType->isSetCompetitivePriceId()) {
                                            $priceId = $competitivePriceType->getCompetitivePriceId();
                                        }

                                        if ($competitivePriceType->isSetPrice()) {
                                            $price = $competitivePriceType->getPrice();
                                            if ($price->isSetListingPrice()) {
                                                $listingPrice = $price->getListingPrice();
                                                if ($listingPrice->isSetAmount()) {
                                                    $amount = $listingPrice->getAmount();
                                                }
                                            }
                                            if ($price->isSetShipping()) {
                                                $shipping = $price->getShipping();
                                                if ($shipping->isSetAmount()) {
                                                    $amount_shipping = $shipping->getAmount();
                                                }
                                            }
                                        }

                                        $resp[$asin][] = [
                                            'condition'             => $condition,              // new
                                            'priceId'               => $priceId,                // 1
                                            'amount'                => $amount,                 // float €
                                            'amount_shipping'       => $amount_shipping,        // float €
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->throwableArray($ex, __FUNCTION__);
        } catch (Throwable $th) {
            return FacadesMpe::throwableArray($th, 'Amazon_'.__FUNCTION__, 'asins', $asins);
        }

        return $resp;
    }


    // No includes Amazon: 20 asins x call
    private function getBestOfferListings($asins)
    {
        try {
            $resp = [];
            $res = $this->amznGetLowestOfferListingsForASIN($asins);

            if ($res->isSetGetLowestOfferListingsForASINResult() && $getLowestOfferListingsForASINResult = $res->getGetLowestOfferListingsForASINResult())

                foreach ($getLowestOfferListingsForASINResult as $getLowestOfferListingsForASIN) {

                    $asin = null;
                    if ($getLowestOfferListingsForASIN->isSetASIN())
                        $asin = $getLowestOfferListingsForASIN->getASIN();

                    if ($getLowestOfferListingsForASIN->isSetProduct() && $product = $getLowestOfferListingsForASIN->getProduct())
                        if ($product->isSetLowestOfferListings() && $lowestOfferListings = $product->getLowestOfferListings())
                            if ($lowestOfferListings->isSetLowestOfferListing() && $lowestOfferListing = $lowestOfferListings->getLowestOfferListing())

                                foreach ($lowestOfferListing as $lowestOfferListingType) {

                                    $condition = null;
                                    $channel = null;
                                    $amount = null;
                                    $amount_shipping = null;

                                    if ($lowestOfferListingType->isSetQualifiers() && $qualifiers = $lowestOfferListingType->getQualifiers()) {
                                        if ($qualifiers->isSetItemCondition())
                                            $condition = $qualifiers->getItemCondition();

                                        if ($qualifiers->isSetFulfillmentChannel())
                                            $channel = $qualifiers->getFulfillmentChannel();
                                    }

                                    if ($lowestOfferListingType->isSetPrice() && $price = $lowestOfferListingType->getPrice()) {
                                        if ($price->isSetListingPrice() && $listingPrice = $price->getListingPrice())
                                            if ($listingPrice->isSetAmount())
                                                $amount = $listingPrice->getAmount();

                                        if ($price->isSetShipping() && $shipping = $price->getShipping())
                                            if ($shipping->isSetAmount())
                                                $amount_shipping = $shipping->getAmount();
                                    }

                                    $resp[$asin][] = [
                                        'condition'             => $condition,              // new
                                        'channel'               => $channel,                // Amazon | Merchant
                                        'amount'                => $amount,                 // float €
                                        'amount_shipping'       => $amount_shipping,        // float €
                                    ];
                                }

                }

            return $resp;

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->nullWithErrors($ex, __METHOD__, $asins);
        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $asins);
        }
    }


    // Get BuyBox Winner. Includes Amazon Or Not; 1 asin x call
    private function getLowestPricedOffersForASINRequest($asin)
    {
        $resp = [];
        try {
            $req = new  MarketplaceWebServiceProducts_Model_GetLowestPricedOffersForASINRequest();
            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $req->setMarketplaceId($this->marketplaceId);
            $req->setItemCondition('New');
            $req->setASIN($asin);

            $service = $this->getServiceProduct();
            $res = $service->getLowestPricedOffersForASIN($req);
            $this->requestIdStorage($res, __FUNCTION__);

            if ($res->isSetGetLowestPricedOffersForASINResult()) {
                $getLowestPricedOffersForASINResult = $res->getGetLowestPricedOffersForASINResult();
                if ($getLowestPricedOffersForASINResult->isSetSummary()) {
                    $summary = $getLowestPricedOffersForASINResult->getSummary();
                    if ($summary->isSetLowestPrices()) {
                        $lowestPrices = $summary->getLowestPrices();
                        if ($lowestPrices->isSetLowestPrice()) {
                            $lowestPrice = $lowestPrices->getLowestPrice();

                            foreach ($lowestPrice as $lowestPriceType) {

                                $condition = null;
                                $channel = null;
                                $amount = null;
                                $amount_shipping = null;

                                if ($lowestPriceType->isSetcondition()) {
                                    $condition = $lowestPriceType->getcondition();
                                }

                                if ($lowestPriceType->isSetfulfillmentChannel()) {
                                    $channel = $lowestPriceType->getfulfillmentChannel();
                                }

                                if ($lowestPriceType->isSetListingPrice()) {
                                    $listingPrice = $lowestPriceType->getListingPrice();
                                    if ($listingPrice->isSetAmount()) {
                                        $amount = $listingPrice->getAmount();
                                    }
                                }

                                if ($lowestPriceType->isSetShipping()) {
                                    $shipping = $lowestPriceType->getShipping();
                                    if ($shipping->isSetAmount()) {
                                        $amount_shipping = $shipping->getAmount();
                                    }
                                }

                                $resp[] = [
                                    'condition'             => $condition,              // new
                                    'channel'               => $channel,                // Amazon | Merchant
                                    'amount'                => $amount,                 // float €
                                    'amount_shipping'       => $amount_shipping,        // float €
                                ];
                            }
                        }
                    }
                }
            }

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->throwableArray($ex, __FUNCTION__);
        } catch (Throwable $th) {
            return $this->throwableArray($th, __FUNCTION__);
        }

        return $resp;
    }


    /************** PRIVATE FUNCTIONS - AMAZON FEEDS ***************/


    private function amznSubmitFeed($feedContent, $feedType = '_POST_FLAT_FILE_INVLOADER_DATA_', $purgeAndReplace = false)
    {
        try {
            $req = new MarketplaceWebService_Model_SubmitFeedRequest();
            $req->setMerchant($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);   // Optional
            $marketplaceIdArray = ['Id' => [$this->marketplaceId]];
            $req->setMarketplaceIdList($marketplaceIdArray);


            $contentType = new MarketplaceWebService_Model_ContentType();
            // text/tab-separated-values; ch    arset=iso-8859-1
            // text/xml
            // 'application/octet-stream'
            $contentType->setContentType('application/octet-stream');
            $contentType->setParameters([]);
            $req->setContentType($contentType);

            $req->setPurgeAndReplace(false);    // Default: false
            //$req->setFeedOptions();


            $req->setFeedType($feedType);


            $feedHandle = @fopen('php://memory', 'rw+');        // Windows: temp; Linux: memory
            fwrite($feedHandle, $feedContent->asXML());
            rewind($feedHandle);
            $req->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));
            rewind($feedHandle);
            $req->setFeedContent($feedHandle);

            rewind($feedHandle);
            $service = $this->getService();

            $res = $service->submitFeed($req);
            @fclose($feedHandle);




            /* $req->setFeedContent($feedContent);
            $feedHandle = @fopen('php://temp', 'rw+');      // Windows: temp; Linux: memory
            fwrite($feedHandle, $feedContent);
            rewind($feedHandle);
            $req->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));

            rewind($feedHandle);
            $req->setFeedContent($feedHandle);      // stream_get_contents
            rewind($feedHandle);

            $service = $this->getService();
            $res = $service->submitFeed($req);
            @fclose($feedHandle); */

            if ($res->isSetSubmitFeedResult() && $submitFeedResult = $res->getSubmitFeedResult())
                if ($submitFeedResult->isSetFeedSubmissionInfo() && $feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo())
                    if ($feedSubmissionInfo->isSetFeedSubmissionId() && $feedSubmissionId = $feedSubmissionInfo->getFeedSubmissionId()) {
                        ShopJob::create([
                            'shop_id'   => $this->shop->id,
                            'jobId'     => $feedSubmissionId,
                            'operation' => $feedType,               // $feedSubmissionInfo->getFeedType();
                        ]);

                        return $feedSubmissionId;
                    }

            return [
                'code'      => $feedType,
                'message'   => 'Submit Feed is NOT Set',
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$feedContent, $feedType, $purgeAndReplace]);
        }
    }


    private function handleInvloaderFeedResult(ShopJob $shopJob, $contents)
    {
        $res = [
            'total_count'   => 0,
            'success_count' => 0,

        ];
        $resp = [];
        $columns = [];
        $csv = [];
        $line_count = 0;
        $lines = explode("\n", $contents);
        foreach ($lines as $line) {
            $csv_array = str_getcsv($line, $this->delimiter);
            $csv[] = $csv_array;

            /*
            Resumen de procesamiento de fichero:
                Número de registros procesados		1
                Número de registros correctos		1
            */

            /*
            Resumen de procesamiento de fichero:
                Número de registros procesados		1
                Número de registros correctos		0

            original-record-number	sku	error-code	error-type	error-message
            1	38377_MZ-V7P1T0BW_8801643205379	6024	Error	El vendedor no está autorizado a publicar productos de esta marca en esta línea de producto o categoría. Para más información, consulta http://sellercentral.amazon.es/gp/errorcode/6024
            */


            // Number of records processed
            // Number of records successful
            if ($line_count == 1) {
                $res['total_count'] = (integer)$csv_array[3] ?? null;
            }
            elseif ($line_count == 2) {
                $res['success_count'] = (integer)$csv_array[3] ?? null;
            }
            elseif ($line_count == 4 && (isset($csv_array[0]) && $csv_array[0] != null)) {
                // "original-record-number", "sku", "error-code", "error-type", "error-message"
                foreach($csv_array as $column)
                    $columns[] = $column;
            }
            elseif ($line_count > 4 && (isset($csv_array[0]) && $csv_array[0] != null)) {             // \tNumber of records processed\t\t0
                $col_count = 0;
                foreach($csv_array as $column_data) {
                    // Binary TO String
                    $column_data = utf8_encode($column_data);
                    $resp['responses'][$line_count-5][$columns[$col_count] ?? $col_count] = $column_data;


                    $col_count++;
                }
            }

            $line_count++;
        }

        Storage::append($this->shop_dir. __FUNCTION__.'/' .date('Y-m-d'). '_'.$shopJob->jobId.'.json', json_encode($resp));

        if (isset($resp['responses'])) {
            foreach ($resp['responses'] as $response) {
                if (isset($response['sku']) && $response['sku'] != '') {
                    if (isset($response['error-type']) && $response['error-type'] == 'Error') {

                        // ERROR CODES
                        // 6024     You are not authorized to list products under this brand.
                        //          El vendedor no está autorizado a publicar productos de esta marca en esta línea de producto o categoría. Para más información, consulta http://sellercentral.amazon.es/gp/errorcode/6024
                        // 6039     Merchant is not authorized to sell products under this restricted product group.
                        // 90111    The price field contains an invalid value: 2628.15. The value "2628.15" is not a valid CURRENCY.
                        // 90215    100% of the products in your file did not process successfully. We recommend using Check My File to help you identify and correct common listing errors before updating your inventory. To use Check My File, upload your file on the "Add Products via Upload" page in the "Check My File" section
                        if (in_array($response['error-code'], ['6024', '6039']))
                            $marketProductSkuErrorType = 'NO BRAND';
                        else {
                            $res[] = $response;
                            $marketProductSkuErrorType = 'ERROR';
                        }

                        $this->shop->shop_products()
                            ->whereMpsSku($response['sku'])
                            ->update(['marketProductSku' => $marketProductSkuErrorType]);
                    }
                }
            }
        }

        // Update Job
        $shopJob->total_count = (integer)$res['total_count'];
        $shopJob->success_count = (integer)$res['success_count'];
        $shopJob->save();

        //Storage::append($this->shop_dir. __FUNCTION__.'/' .date('Y-m-d'). '_'.$shopJob->jobId.'.json', json_encode($res));

        return $res;
    }


    private function amznGetFeedSubmissionResult($job_id)
    {
        try {
            $req = new MarketplaceWebService_Model_GetFeedSubmissionResultRequest();
            $req->setMarketplace($this->marketplaceId);
            $req->setMerchant($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $req->setFeedSubmissionId($job_id);
            $handle = @fopen('php://memory', 'rw+');
            $req->setFeedSubmissionResult($handle);
            $service = $this->getService();

            $res = $service->getFeedSubmissionResult($req);

            return [$res, $handle];

        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, $job_id);
            return [null, null];
        }
    }


    private function GetFeedSubmissionResult(ShopJob $shopJob)
    {
        try {
            [$res, $handle] = $this->amznGetFeedSubmissionResult($shopJob->jobId);

            if ($res->isSetGetFeedSubmissionResultResult()) {
                $getFeedSubmissionResultResult = $res->getGetFeedSubmissionResultResult();
                if ($getFeedSubmissionResultResult->isSetContentMd5()) {
                    //$contentMd5 = $getFeedSubmissionResultResult->getContentMd5();

                    rewind($handle);
                    $contents = stream_get_contents($handle);
                    Storage::append($this->shop_dir. __FUNCTION__.'/' .date('Y-m-d_H-i-s'). '_'.$shopJob->jobId.'.txt', $contents);
                    fclose($handle);

                    if ($shopJob->operation == '_POST_FLAT_FILE_INVLOADER_DATA_')
                        return $this->handleInvloaderFeedResult($shopJob, $contents);
                    else {
                        // _POST_PRODUCT_DATA_
                        return $this->handleProductFeedResult($shopJob, $contents);         // 'NO SERVICE NAME CODE FOUND.';
                    }
                }
            }

            return [
                'code'      => 'Get Feed',
                'message'   => 'Feed Submission Result is NOT Set',
            ];

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->nullWithErrors($ex, __METHOD__, $shopJob);
        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shopJob);
        }
    }


    private function handleFeedSubmissionInfoList($feedSubmissionInfoList, ShopJob $shopJob)
    {
        try {
            $jobResult = [];
            foreach($feedSubmissionInfoList as $feedSubmissionInfo) {
                if ($feedSubmissionInfo->isSetFeedSubmissionId())
                    $feedSubmissionId = $feedSubmissionInfo->getFeedSubmissionId();     // == $shopJob->jobId == 50003018537

                if ($feedSubmissionInfo->isSetFeedType())
                    $feedType = $feedSubmissionInfo->getFeedType();     // _POST_FLAT_FILE_INVLOADER_DATA_      // _POST_FLAT_FILE_LISTINGS_DATA_

                if ($feedSubmissionInfo->isSetFeedProcessingStatus()) {
                    $feedProcessingStatus = $feedSubmissionInfo->getFeedProcessingStatus();     // _DONE_  --> GetFeedSubmissionResult

                    if ($feedProcessingStatus == '_DONE_') {

                        $jobResult[] = [
                            'job'       => $feedSubmissionId ?? $shopJob->jobId ?? null,
                            'type'      => $feedType ?? null,
                            'status'    => $feedProcessingStatus,
                            'result'    => $this->GetFeedSubmissionResult($shopJob),

                        ];
                    }

                    $jobResult[] = [
                        'job'       => $feedSubmissionId ?? $shopJob->jobId ?? null,
                        'type'      => $feedType ?? null,
                        'status'    => $feedProcessingStatus
                    ];
                }
            }

            Storage::append($this->shop_dir. __FUNCTION__.'/' .date('Y-m-d'). '.json', json_encode($jobResult));

            return $jobResult;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$feedSubmissionInfoList, $shopJob]);
        }
    }


    private function amznGetFeedSubmissionListByNextToken($nextToken)
    {
        try {
            $req = new MarketplaceWebService_Model_GetFeedSubmissionListByNextTokenRequest();
            $req->setMerchant($this->sellerId);
            $req->setMarketplace($this->marketplaceId);
            $req->setMWSAuthToken($this->mwsAuthToken);   // Optional
            $req->setNextToken($nextToken);

            $service = $this->getService();

            return $service->getFeedSubmissionListByNextToken($req);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $nextToken);
        }
    }


    private function getFeedSubmissionListByNextToken($nextToken, ShopJob $shopJob)
    {
        try {
            $jobResult = [];
            $res = $this->amznGetFeedSubmissionListByNextToken($nextToken);

            $this->requestIdStorage($res, __FUNCTION__);
            Storage::put($this->shop_dir. __FUNCTION__.'/' .date('Y-m-d_H-i-s'). '.json', json_encode($res->toXML()));

            if ($res->isSetGetFeedSubmissionListByNextTokenResult()) {
                $getFeedSubmissionListByNextTokenResult = $res->getGetFeedSubmissionListByNextTokenResult();

                if ($getFeedSubmissionListByNextTokenResult->isSetFeedSubmissionInfo()) {
                    $feedSubmissionInfoList = $getFeedSubmissionListByNextTokenResult->getFeedSubmissionInfoList();
                    $jobResult[] = $this->handleFeedSubmissionInfoList($feedSubmissionInfoList, $shopJob);
                }

                if ($getFeedSubmissionListByNextTokenResult->isSetNextToken()) {
                    $jobResult[] = $this->getFeedSubmissionListByNextToken($getFeedSubmissionListByNextTokenResult->NextToken(), $shopJob);
                }
            }

            return $jobResult;

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->nullWithErrors($ex, __METHOD__, [$nextToken, $shopJob]);
        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$nextToken, $shopJob]);
        }
    }


    private function amznGetFeedSubmissionList(ShopJob $shopJob)
    {
        try {
            $jobResult = [];
            $jobResult[$shopJob->jobId]['operation'] = $shopJob->operation;

            $req = new MarketplaceWebService_Model_GetFeedSubmissionListRequest();
            $req->setMerchant($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);   // Optional
            $id_list = new MarketplaceWebServiceProducts_Model_IdListType();
            $id_list->setId($shopJob->jobId);
            $req->setFeedSubmissionIdList($id_list);
            $service = $this->getService();

            return $service->getFeedSubmissionList($req);

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->nullWithErrors($ex, __METHOD__, $shopJob);
        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shopJob);
        }
    }


    private function handleProductFeedResult(ShopJob $shopJob, $contents)
    {
        try {
            $resp = null;

            $xml = simplexml_load_string($contents);
            $messageType = $xml->MessageType;        // ProcessingReport
            $message = $xml->Message;
            $processingReport = $message->ProcessingReport;
            $documentTransactionID = $processingReport->DocumentTransactionID;
            $statusCode = $processingReport->StatusCode;    // Complete

            $processingSummary = $processingReport->ProcessingSummary;
            $messagesProcessed = $processingSummary->MessagesProcessed;
            $messagesSuccessful = $processingSummary->MessagesSuccessful;   // MessagesProcessed - MessagesWithError - MessagesWithWarning

            /* $result = $processingReport->Result;
            $messageID = $result->MessageID;                    // 0
            $resultCode = $result->ResultCode;                  // Error
            $resultMessageCode = $result->ResultMessageCode;    // 5001
            $resultDescription = $result->ResultDescription;    // XML Parsing Fatal Error at Line -1, Column -1: Premature end of file. Premature end of file.
            $resp = [
                'code'          => (string)$resultCode,
                'message'       => (string)$resultMessageCode,
                'description'   => (string)$resultDescription,
            ]; */
            foreach ($processingReport->Result as $result) {
                $messageID = $result->MessageID;                    // 0
                $resultCode = $result->ResultCode;                  // Error
                $resultMessageCode = $result->ResultMessageCode;    // 5001
                $resultDescription = $result->ResultDescription;    // XML Parsing Fatal Error at Line -1, Column -1: Premature end of file. Premature end of file.
                $resp[] = [
                    'code'          => (string)$resultCode,
                    'message'       => (string)$resultMessageCode,
                    'description'   => (string)$resultDescription,
                ];
            }

            // Update Job
            $shopJob->total_count = $messagesProcessed;
            $shopJob->success_count = $messagesSuccessful;
            $shopJob->save();

            return $resp;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shopJob, $contents);
        }
    }


    /************** PRIVATE FUNCTIONS - AMAZON ORDERS ***************/


    private function amznListOrders()
    {
        try {
            $req = new MarketplaceWebServiceOrders_Model_ListOrdersRequest();
            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);   // Optional
            $req->setMarketplaceId($this->marketplaceId);
            // now()->format(DateTime::ISO8601)     2020-10-05T07:22:37+00:00
            // now()->toIso8601String()             2020-10-05T07:22:37+0000
            // now()->format('Y-m-d\TH:i:s')        2020-10-05T07:22:37
            //$req->setLastUpdatedBefore(now()->format('Y-m-d\TH:i:s'));
            $req->setLastUpdatedAfter(now()->addDays(-30)->format('Y-m-d\TH:i:s'));
            $service = $this->getServiceOrder();

            return $service->listOrders($req);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function amznGetOrder($amazonOrderIds)
    {
        try {
            $req = new MarketplaceWebServiceOrders_Model_GetOrderRequest();
            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);   // Optional
            $req->setAmazonOrderId($amazonOrderIds);

            $service = $this->getServiceOrder();

            return $service->getOrder($req);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function listOrdersByNextToken($nextToken)
    {
        $ordersCount = 0;

        try {
            $req = new MarketplaceWebServiceOrders_Model_ListOrdersByNextTokenRequest();
            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);   // Optional
            $req->setNextToken($nextToken);

            $service = $this->getServiceOrder();
            $res = $service->listOrdersByNextToken($req);
            $this->requestIdStorage($res, __FUNCTION__);

            Storage::append($this->shop_dir. 'test/' .date('Y-m-d_H-i'). '_listOrdersByNextToken.txt', json_encode($res));

            if ($res->isSetListOrdersByNextTokenResult()) {
                $listOrdersByNextTokenResult = $res->getListOrdersByNextTokenResult();

                if ($listOrdersByNextTokenResult->isSetOrders()) {
                    $listOrders = $listOrdersByNextTokenResult->getOrders();
                    $ordersCount = $this->handleListOrders($listOrders);
                }

                if ($listOrdersByNextTokenResult->isSetNextToken()) {
                    $ordersCount += $this->listOrdersByNextToken($listOrdersByNextTokenResult->getNextToken());
                }
            }

        } catch (MarketplaceWebService_Exception $ex) {
            return $this->throwableArray($ex, __FUNCTION__);
        } catch (Throwable $th) {
            return $this->throwableArray($th, __FUNCTION__);
        }

        return $ordersCount;
    }


    private function listOrderItems(Order $order)
    {
        try {
            $req = new MarketplaceWebServiceOrders_Model_ListOrderItemsRequest();
            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            $req->setAmazonOrderId($order->marketOrderId);

            $service = $this->getServiceOrder();
            $res = $service->listOrderItems($req);
            $this->requestIdStorage($res, __FUNCTION__);

            if ($res->isSetListOrderItemsResult()) {
                if ($listOrderItemsResult = $res->getListOrderItemsResult()) {
                    if ($listOrderItemsResult->isSetOrderItems()) {
                        $amznOrderItems = $listOrderItemsResult->getOrderItems();

                        foreach ($amznOrderItems as $amznOrderItem) {
                            $this->updateOrCreateOrderItem($order, $amznOrderItem);
                        }
                    }

                    return true;
                }
            }

            return false;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $order);
        }
    }


    private function handleListOrders($listOrders)
    {
        try {
            $orders = [];
            foreach ($listOrders as $amznOrder) {
                if ($order = $this->updateOrCreateOrder($amznOrder)) {
                    $order_item = $this->listOrderItems($order);
                    $orders[] = [$order, $order_item];
                }
            }


            /* $amazonOrderIds = [];
            $orders = [];
            foreach ($listOrders as $order) {
                $amazonOrderIds[] = $order->getAmazonOrderId();
                if (count($amazonOrderIds) >= 50) break;
            }

            if (count($amazonOrderIds)) {
                if ($amznOrdersInfo = $this->amznGetOrder($amazonOrderIds)) {
                    if ($amznOrdersInfo->isSetGetOrderResult()) {
                        $orderResult = $amznOrdersInfo->getGetOrderResult();
                        if ($orderResult->isSetOrders()) {

                            $amznOrders = $orderResult->Orders;
                            foreach ($amznOrders as $amznOrder) {
                                if ($order = $this->updateOrCreateOrder($amznOrder)) {
                                    $order_item = $this->listOrderItems($order);
                                    $orders[] = [$order, $order_item];
                                }
                            }
                        }
                    }
                }
            } */

            return count($orders);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $orders);
        }
    }


    /************** PRIVATE FUNCTIONS - UPDATE or CREATE MODELS ***************/



    private function updateOrCreateOrder(MarketplaceWebServiceOrders_Model_Order $amznOrder)
    {
        try {
            if ($amznOrder->isSetAmazonOrderId())
                $amazonOrderId = $amznOrder->getAmazonOrderId();

            if ($amznOrder->isSetShippingAddress()) {
                $shippingAddress = $amznOrder->getShippingAddress();

                if ($shippingAddress->isSetCountryCode()) {
                    $countryCode = $shippingAddress->getCountryCode();
                    $country = Country::firstOrCreate([
                        'code'      => $countryCode,
                    ],[]);
                }

                $address = Address::updateOrCreate([
                    'country_id'            => $country->id ?? null,
                    'market_id'             => $this->market->id,
                    'marketBuyerId'         => null,
                    'name'                  => $shippingAddress->getName() ?? ($shippingAddress->getPostalCode().' '.$shippingAddress->getCity()),
                    'address1'              => $shippingAddress->getAddressLine1(),
                ],[
                    'address2'              => $shippingAddress->getAddressLine2(),
                    'address3'              => $shippingAddress->getAddressLine3(),
                    'city'                  => $shippingAddress->getCity(),
                    'state'                 => $shippingAddress->getStateOrRegion(),
                    'zipcode'               => $shippingAddress->getPostalCode(),
                    'district'              => $shippingAddress->getDistrict(),
                    'municipality'          => $shippingAddress->getCounty(),
                    'phone'                 => $shippingAddress->getPhone(),
                ]);
            }

            if ($amznOrder->isSetBuyerTaxInfo()) {
                $buyerTaxInfo = $amznOrder->getBuyerTaxInfo();
                $companyLegalName = $buyerTaxInfo->getCompanyLegalName();
                if ($buyerTaxInfo->isSetTaxClassifications()) {
                    $taxingRegion = $buyerTaxInfo->getTaxingRegion();
                    $taxClassifications = $buyerTaxInfo->getTaxClassifications();
                    foreach ($taxClassifications as $taxClassification) {
                        $taxClassificationName = $taxClassification->getName();
                        $taxClassificationValue = $taxClassification->getValue();
                        break;
                    }
                }
            }

            $buyer = Buyer::firstOrCreate([
                'market_id'             => $this->market->id,
                'name'                  => $amznOrder->getBuyerName() ?? $amznOrder->getBuyerEmail(),
                'marketBuyerId'         => null,
            ],[
                'shipping_address_id'   => $address->id ?? null,
                'billing_address_id'    => null,
                'phone'                 => $address->phone ?? null,
                'email'                 => $amznOrder->getBuyerEmail(),
                'company_name'          => $companyLegalName ?? null,
                'tax_region'            => $taxingRegion ?? null,
                'tax_name'              => $taxClassificationName ?? null,
                'tax_value'             => $taxClassificationValue ?? null,
            ]);

            $status = Status::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketStatusName'      => $amznOrder->getOrderStatus(),        // $order->getTFMShipmentStatus()
                'type'                  => 'order',
            ],[]);


            if ($amznOrder->isSetOrderTotal()) {
                $orderTotal = $amznOrder->getOrderTotal();
                if ($orderTotal->isSetCurrencyCode()) {


                    $currencyCode = $orderTotal->getCurrencyCode();

                    $currency = Currency::firstOrCreate([
                        'code'             => $currencyCode,
                    ],[]);
                }
            }

            //    'marketOrderId',
            // 'SellerId', 'SellerOrderId', 'info', 'price', 'tax', 'shipping_price', 'shipping_tax',
            // 'notified', 'notified_updated'
            $order = Order::whereMarketId($this->market->id)->whereShopId($this->shop->id)->where('marketOrderId', $amazonOrderId)->first();
            $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $order = Order::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'marketOrderId'         => $amazonOrderId,
            ],[
                'buyer_id'              => $buyer->id ?? null,
                'shipping_address_id'   => $address->id ?? null,
                'billing_address_id'    => null,
                'currency_id'           => $currency->id ?? null,
                'status_id'             => $status->id ?? null,
                'type_id'               => null,
                'SellerId'              => null,
                'SellerOrderId'         => $amznOrder->getSellerOrderId(),
                'info'                  => $amznOrder->getOrderType(),
                'price'                 => $orderTotal->getAmount() ?? 0,
                'tax'                   => $taxClassificationValue ?? 21,
                'shipping_price'        => 0,
                'shipping_tax'          => 0,
                'notified'              => $notified,
                'notified_updated'      => $notified_updated,
            ]);

            $order->created_at = Carbon::createFromFormat('Y-m-d\TH:i:s.uZ', $amznOrder->getPurchaseDate())->format('Y-m-d H:i:s');
            if (!$updated_at = Carbon::createFromFormat('Y-m-d\TH:i:sZ', $amznOrder->getLatestDeliveryDate())->format('Y-m-d H:i:s'))
                if (!$updated_at = Carbon::createFromFormat('Y-m-d\TH:i:sZ', $amznOrder->getLatestShipDate())->format('Y-m-d H:i:s'))
                    $updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s.uZ', $amznOrder->getLastUpdateDate())->format('Y-m-d H:i:s');

            $order->updated_at = $updated_at;
            $order->save();

            return $order;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$amznOrder, $shippingAddress ?? null, $buyerTaxInfo ?? null, $order ?? null]);
        }
    }


    private function updateOrCreateOrderItem(Order $order, MarketplaceWebServiceOrders_Model_OrderItem $amznOrderItem)
    {
        try {
            // Price
            if ($amznOrderItem->isSetItemPrice()) {
                if ($itemPrice = $amznOrderItem->getItemPrice())
                    $amount = $itemPrice->Amount;
            }

            // Tax
            if ($amznOrderItem->isSetItemTax()) {
                if ($itemTax = $amznOrderItem->getItemTax())
                    $amountTax = $itemTax->Amount;
            }

            // ShippingPrice
            if ($amznOrderItem->isSetShippingPrice()) {
                if ($shippingPrice = $amznOrderItem->getShippingPrice())
                    $amountShipping = $shippingPrice->Amount;
            }

            // ShippingTax
            if ($amznOrderItem->isSetShippingTax()) {
                if ($shippingTax = $amznOrderItem->getShippingTax())
                    $amountShippingTax = $shippingTax->Amount;
            }

            $order_item = $order->updateOrCreateOrderItem(
                $amznOrderItem->getOrderItemId(),
                $amznOrderItem->getSellerSKU(),
                $amznOrderItem->getASIN(),
                $amznOrderItem->getTitle(),
                $amznOrderItem->getQuantityOrdered(),
                $amount ?? 0,
                $amountTax ?? 0,
                $amountShipping ?? 0,
                $amountShippingTax ?? 0,
                null);

            return $order_item;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$order, $amznOrderItem]);
        }
    }



    /************** PRIVATE FUNCTIONS - CATEGORIES ***************/


    private function updateOrCreateCategory($node)
    {
        try {
            $englishRootBrowseNodeIds = ["10068518031", "1661658031", "248878031", "60032031", "117333031", "17453414031", "1025612", "5866055031",
                "520920", "83451031", "340832031", "77925031", "79904031", "560800", "72954031", "1571305031", "344155031", "9699255031", "66280031",
                "193717031", "341678031", "908799031", "213078031", "2454167031", "340838031", "245408031", "1025616", "340841031", "3010086031",
                "362350011", "1025614", "319530011", "192414031", "328229011", "17161752031"];

            $browsePathByIdArray = explode(',', $node->browsePathById);
            // $node IS $ROOT
            if (count($browsePathByIdArray) == 2) {
                if (!in_array($node->browseNodeId, $englishRootBrowseNodeIds)) {
                    $root_category = RootCategory::updateOrCreate([
                        'market_id'         => $this->market->id,
                        'marketCategoryId'  => $node->browseNodeId,
                    ], [
                        'name'              => $node->browseNodeName,
                    ]);

                    return $root_category->marketCategoryId;
                }
            }
            // $node IS CHILD
            else {
                $rootBrowseNodeId = $browsePathByIdArray[1];    // 0 ???
                $rootCategory = $this->market->root_categories()->where('marketCategoryId', $rootBrowseNodeId)->first();
                $parentBrowseNodeId = $browsePathByIdArray[count($browsePathByIdArray)-2];
                if ($rootBrowseNodeId == $parentBrowseNodeId)
                    $path = $rootCategory->name ?? null;
                else {
                    $parentMarketCategory = $this->market->market_categories()->where('marketCategoryId', $parentBrowseNodeId)->first();
                    $path = isset($parentMarketCategory) ? $parentMarketCategory->path .' / '. $parentMarketCategory->name : null;
                }

                if (!in_array($rootBrowseNodeId, $englishRootBrowseNodeIds)) {
                    $market_category = MarketCategory::updateOrCreate(
                        [
                            'market_id'         => $this->market->id,
                            'marketCategoryId'  => $node->browseNodeId,
                        ],
                        [
                            'name'              => $node->browseNodeName,
                            'parent_id'         => $parentMarketCategory->id ?? null,
                            'path'              => $path,
                            'root_category_id'  => $rootCategory->id ?? null,
                        ]
                    );

                    return $market_category->marketCategoryId;
                }
            }

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $node);
        }
    }


    private function getChildCategories($marketCategoryId = null)
    {
        try {
            $res = [];
            $reportType = '_GET_XML_BROWSE_TREE_DATA_';
            $count = 0;

            $reportOptions = 'MarketplaceId='.$this->marketplaceId;
            if ($marketCategoryId) $reportOptions .= ',BrowseNodeId='.$marketCategoryId;
            $reportRequestId = $this->amznRequestReport('_GET_XML_BROWSE_TREE_DATA_', 'MarketplaceId='.$this->marketplaceId);  //, 'RootNodesOnly=true');
            sleep(20);
            //$reportRequestId = '50048018752';
            // 50047018752 all categories
            // 50048018752 only (6198073031) Alimentación y bebidas

            $generatedReportId = $this->amznGetReportRequestList($reportRequestId);
            if (!is_numeric($generatedReportId)) return $generatedReportId;     // _CANCELLED_, _DONE_NO_DATA_

            $report_contents = $this->amznGetReport($generatedReportId, $reportType);
            if (isset($report_contents) && !in_array($report_contents, ['_CANCELLED_', '_DONE_NO_DATA_'])) {

                $amzn_categories = simplexml_load_string($report_contents);
                // <Result><Node>
                foreach ($amzn_categories->Node as $node) {

                    if ($marketCategoryId = $this->updateOrCreateCategory($node)) {
                        $res['categories'][] = $marketCategoryId;
                    } else {
                        $res['ERRORS'][] = $node;
                    }
                }
            }

            return $res;


            $reportResults = null;
            $reportRequestList = $this->amznGetReportRequestList($reportRequestId);
            foreach ($reportRequestList as $reportRequest) {
                if ($reportRequest['reportProcessingStatus'] != '_DONE_') {
                    $reportResults[] = $reportRequest;
                } else {
                    // Returns: [
                    //    'reportRequestId'   => "50025018541",
                    //    'reportType'        => "_GET_XML_BROWSE_TREE_DATA_",
                    //    'reportId'          => "24675112193018541"];
                    /* $reportList = $this->getReportList($reportRequest['reportRequestId'], $reportType);
                    foreach ($reportList as $report) {                       // 24663702585018540
                        $reportResults[] = $this->getReport($report['reportId'], $report['reportType']);
                    }

                    foreach ($reportResults as $reportResult) {
                        foreach ($reportResult->Node as $node) {
                            if ($this->updateOrCreateCategory($node)) {
                                //$es[] = $node;
                                $count++;
                            }
                        }
                        break;
                    } */
                }
            }

            return $count;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketCategoryId);
        }
    }


    /************** PRIVATE FUNCTIONS - XML BUILDERS ***************/



    private function buildAmazonEnvelope($messageType)
    {
        try {
            // amzn-envelope.xsd
            $envelope = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><AmazonEnvelope></AmazonEnvelope>');
            $envelope->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $envelope->addAttribute('xsi:xsi:noNamespaceSchemaLocation', 'amzn-envelope.xsd');

            // amzn-header.xsd
            $Header = $envelope->addChild('Header');
            $Header->addChild('DocumentVersion', $this->documentVersion);
            $Header->addChild('MerchantIdentifier', $this->sellerId);

            // Image, Inventory, Override, Price, Product, ProductImage, ....
            $envelope->addChild('MessageType', $messageType);

            return $envelope;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $messageType);
        }
    }


    private function setAmazonPriceStock(ShopProduct $shop_product)
    {
        $shop_product->cost = $shop_product->getCost();
        $shop_product->save();

        // Accesorios de: 124: Electrónica; 130: Informática;
        // 15% <= 100€ + 8% > 100€
        if (in_array($shop_product->market_category->root_category_id, [124, 130]) &&
            strpos($shop_product->market_category->name, 'Accesorios') !== false) {
                if ($shop_product->cost < 100)
                    $shop_product->param_mp_fee = 15;
                else {
                    $percentage = (((($shop_product->cost - 100) * 0.08) + 15) / $shop_product->cost) * 100;
                    $shop_product->param_mp_fee = $percentage;
                }

                $shop_product->save();
        }
        // Consolas de: 148: Videojuegos -> 8%
        elseif ($shop_product->market_category->root_category_id == 148 &&
            strpos($shop_product->market_category->name, 'Consolas') !== false) {
                $shop_product->param_mp_fee = 8;
                $shop_product->save();
        }
        // Relojes
        // 15% <= 250€ + 5% > 100€
        elseif (strpos($shop_product->market_category->name, 'Relojes') !== false ||
            strpos($shop_product->market_category->name, 'Smartwatch') !== false) {
                if ($shop_product->cost < 250)
                    $shop_product->param_mp_fee = 15;
                else {
                    $percentage = (((($shop_product->cost - 250) * 0.05) + 15) / $shop_product->cost) * 100;
                    $shop_product->param_mp_fee = $percentage;
                }

                $shop_product->save();
        }
        if (strpos($shop_product->market_category->name, 'Accesorios') !== false) {
                $shop_product->param_mp_fee = 15;
                $shop_product->save();
        }

        $shop_product->setPriceStock();
        $shop_product->refresh();

        return $shop_product;
    }


    private function buildPriceFeed(SimpleXMLElement &$feedContent, ShopProduct $shop_product)
    {
        // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/Price.xsd
        $operation_product_type = 'Update';
        $shop_product = $this->setAmazonPriceStock($shop_product);

        // Message: Price
        $Message = $feedContent->addChild('Message');
        $Message->addChild('MessageID', hexdec(uniqid()));       // d{1,20}     uniqid() -> 13d HEX
        // Update, Delete, PartialUpdate
        //$Message->addChild('OperationType', $operation_product_type);

        $Price = $Message->addChild('Price');
        $Price->addChild('SKU', mb_substr($shop_product->mps_sku, 0, 40));      //$shop_product->getMPSSku(40));
        $standardPrice = $Price->addChild('StandardPrice', $shop_product->price);        //number_format($shop_product->price, 2, ",", ""));
        $standardPrice->addAttribute('currency', $shop_product->currency->code);

        //$Message->addChild('BaseCurrencyCodeWithDefault', $shop_product->currency->code);

        return true;
    }


    private function buildProductFeed(SimpleXMLElement &$feedContent, ShopProduct $shop_product)
    {
        try {
            // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/Product.xsd

            $market_category = $shop_product->market_category;
            if (!$market_category) return null;

            //$shop_product = $this->setAmazonPriceStock($shop_product);
            $shop_product->setPriceStock();

            $marketCategoryId = $market_category->marketCategoryId;
            // Los títulos no deben contener caracteres "de decoración", como ~ ! * $ ? _ ~ { } # < > | * ; ^ ¬ ¦
            $title = $shop_product->buildTitle(128);        // mb_substr($shop_product->product->buildTitle(), 0, 128);
            $description = $shop_product->buildDescription4Mobile(2000);    //mb_substr($shop_product->product->buildDescription4Html(), 0, 2000);
            $mpeSku = mb_substr($shop_product->mps_sku, 0, 40);      //$shop_product->getMPSSku(40);

            //$message_product_id = random_int(111,99999999); //microtime(); //uniqid();
            //$operation_product_type = 'Update';
            $brand = $shop_product->product->brand->name;
            //$keywords = $shop_product->product->keywords ?? $title;
            //$description = stripslashes($shop_product->product->buildDescription4Html());

            // SearchTerms
            $ean = $shop_product->product->ean;
            $pn = $shop_product->product->pn;
            $marketCategoryName = $market_category->name;
            $rootCategoryName = $market_category->root_category->name;

            //$market_category = $shop_product->market_category;
            //$images = $shop_product->product->getAllUrlImages()->toArray();

            // Message: Product
            //$feedContent->addChild('PurgeAndReplace', false);
            $Message = $feedContent->addChild('Message');
            $Message->addChild('MessageID', hexdec(uniqid()));
            // Update, Delete, PartialUpdate
            $Message->addChild('OperationType', 'Update');

            $product = $Message->addChild('Product');
            $product->addChild('SKU', $mpeSku);

            /* if (isset($shop_product->marketProductSku) &&
                !empty($shop_product->marketProductSku) &&
                ($shop_product->marketProductSku != 'ERROR')) {

                $StandardProductID = $product->addChild('StandardProductID', '');
                $StandardProductID->addChild('Type', 'ASIN');
                $StandardProductID->addChild('Value', $shop_product->marketProductSku);
            } */

            //$product->addChild('GtinExemptionReason', '');
            //$product->addChild('ProductTaxCode', '');       // Not used in Canada, Europe, or Japan.

            $status = $shop_product->product->status;
            if ($status) {
                $condition = $product->addChild('Condition');
                if ($status->name == 'Nuevo') $condition->addChild('ConditionType', 'New');  // New
                elseif ($status->name == 'Remanufacturado') $condition->addChild('ConditionType', 'Refurbished');  // Refurbished
                else $condition->addChild('ConditionType', 'NewOpenBox');  // Usado: NewOpenBox
            }

            $product->addChild('ItemPackageQuantity', 1);
            $DescriptionData = $product->addChild('DescriptionData');
            // Chars prohibits TITLE: ~ ! * $ ? _ ~ { } # < > | * ; ^ ¬ ¦
            // https://sellercentral-europe.amazon.com/gp/help/help.html?itemID=YTR6SYGFA5E3EQC&language=es_ES&ref=ag_YTR6SYGFA5E3EQC_cont_51
            $DescriptionData->addChild('Title', $title);

            // https://sellercentral-europe.amazon.com/gp/help/200390640?language=es_ES&ref=ag_200390640_cont_200216070
            // No incluyas HTML, DHTML, Java, secuencias de comandos ni otro tipo de fichero ejecutable en tus páginas de detalles.
            $DescriptionData->addChild('Description', $description);

            $DescriptionData->addChild('IsGiftWrapAvailable', false);
            $DescriptionData->addChild('IsGiftMessageAvailable', false);
            $DescriptionData->addChild('IsDiscontinuedByManufacturer', false);

            // SearchTerms: maxOccurs="5"
            $DescriptionData->addChild('SearchTerms', $brand);
            $DescriptionData->addChild('SearchTerms', $ean);
            $DescriptionData->addChild('SearchTerms', $marketCategoryName);
            $DescriptionData->addChild('SearchTerms', $rootCategoryName);
            if (isset($pn)) $DescriptionData->addChild('SearchTerms', $pn);

            if (isset($shop_product->product->shortdesc)) {
                $bullet_points = explode("\n", $shop_product->product->shortdesc, 5);
                foreach ($bullet_points as $bullet_point)
                    if (isset($bullet_point) && !empty((string)$bullet_point))
                        $DescriptionData->addChild('BulletPoint', $bullet_point);
            }

            $DescriptionData->addChild('Brand', $brand);
            $DescriptionData->addChild('Manufacturer', $brand);
            if ($shop_product->product->pn)
                $DescriptionData->addChild('MfrPartNumber', $shop_product->product->pn);


            //$DescriptionData->addChild(postPayloadPlatinumKeywords', $keywords);
            //$DescriptionData->addChild('RecommendedBrowseNode', $marketCategoryId);

            // Category Attributes
            //$product->addChild('ProductData', 'Computers');
            //$product->addChild('ProductType', 'NotebookComputer');
            //$product->addChild('Color', '');

            // UsedFor, ItemType,  OtherItemAttributes, TargetAudience, and SubjectContent.
            //$DescriptionData->addChild('UsedFor', $keywords);         // Not used in Canada, Europe, or Japan

            //$DescriptionData->addChild('ItemType', $keywords);
            //$DescriptionData->addChild('OtherItemAttributes', $keywords);
            //$DescriptionData->addChild('TargetAudience', $keywords);
            //$DescriptionData->addChild('SubjectContent', $keywords);

            /* $productData = $product->addChild('ProductData');
            $computers = $productData->addChild('Computers');
            $productType = $computers->addChild('ProductType');
            $notebookComputer = $productType->addChild('NotebookComputer'); */


            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $feedContent, $shop_product);
        }
    }


    private function buildInventoryFeed(SimpleXMLElement &$feedContent, ShopProduct $shop_product)
    {
        // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/Inventory.xsd
        try {
            //$message_id = uniqid();
            $operation_product_type = 'Update';
            $sku_code = mb_substr($shop_product->mps_sku, 0, 40);   //$shop_product->getMPSSku(40);
            $inventory = $shop_product->stock;

            // Message: Inventory
            $Message = $feedContent->addChild('Message');
            $Message->addChild('MessageID', hexdec(uniqid()));
            $Message->addChild('OperationType', $operation_product_type);
            $Inventory = $Message->addChild('Inventory');
            $Inventory->addChild('SKU', $sku_code);
            $Inventory->addChild('Quantity', $inventory);

            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$feedContent, $shop_product]);
        }
    }


    private function buildImageFeed(SimpleXMLElement &$feedContent, ShopProduct $shop_product)
    {
        try {
            // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/ProductImage.xsd
            // https://sellercentral-europe.amazon.com/gp/help/200216080?language=es_ES&ref=ag_200216080_cont_200216070
            // Puedes proporcionar una imagen de producto principal y hasta ocho imágenes alternativas para tus listings de productos en Amazon
            //$message_id = uniqid();
            $operation_type = 'Update';
            $sku_code = mb_substr($shop_product->mps_sku, 0, 40);   //$shop_product->getMPSSku(40);
            $images = $shop_product->product->getAllUrlImages(9)->toArray();     // NO https, USE http

            // Message: Price
            $count_message = 1;
            for ($i=0; $i<count($images); $i++) {
                $Message = $feedContent->addChild('Message');
                $Message->addChild('MessageID', $count_message);
                // Update, Delete, PartialUpdate
                $Message->addChild('OperationType', $operation_type);

                $ProductImage = $Message->addChild('ProductImage');
                $ProductImage->addChild('SKU', $sku_code);

                $type = ($i == 0) ? 'Main' : ('PT' .$i);
                $ProductImage->addChild('ImageType', $type);
                $ProductImage->addChild('ImageLocation', str_replace('https', 'http', $images[$i]));
            }


            /* $Message = $feedContent->addChild('Message');
            $Message->addChild('MessageID', hexdec(uniqid()));
            // Update, Delete, PartialUpdate
            $Message->addChild('OperationType', $operation_type);

            $ProductImage = $Message->addChild('ProductImage');
            $ProductImage->addChild('SKU', $sku_code);
            for ($i=0; $i<count($images); $i++) {
                $type = ($i == 0) ? 'Main' : ('PT' .$i);
                $ProductImage->addChild('ImageType', $type);
                $ProductImage->addChild('ImageLocation', str_replace('https', 'http', $images[$i]));
            } */

            /* <Message>
                <MessageID>1</MessageID>
                <OperationType>Update</OperationType>
                <ProductImage>
                    <SKU>KOS-LS-ROSEWATER-parent</SKU>
                    <ImageType>Main</ImageType>
                    <ImageLocation>http://myurl.com/KOS-LS-DARKROOM/retina/full_width/KOS-LS-DARKROOM-main.jpg</ImageLocation>
                </ProductImage>
            </Message>
 */
            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$feedContent, $shop_product]);
        }
    }


    private function buildItemFeed(ShopProduct $shop_product)
    {
        try {
            $productFeedContent = $this->buildAmazonEnvelope('Product');
            $InventoryFeedContent = $this->buildAmazonEnvelope('Inventory');
            $priceFeedContent = $this->buildAmazonEnvelope('Price');
            $imageFeedContent = $this->buildAmazonEnvelope('ProductImage');

            $this->buildProductFeed($productFeedContent, $shop_product);
            $this->buildInventoryFeed($InventoryFeedContent, $shop_product);
            $this->buildPriceFeed($priceFeedContent, $shop_product);
            $this->buildImageFeed($imageFeedContent, $shop_product);

            return [$productFeedContent, $InventoryFeedContent, $priceFeedContent, $imageFeedContent];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildCSVColumns($feedType = '_POST_FLAT_FILE_INVLOADER_DATA_')      // _POST_FLAT_FILE_INVLOADER_DATA_ // _POST_FLAT_FILE_LISTINGS_DATA_
    {
        try {
            // 47 columns
            if ($feedType == '_POST_FLAT_FILE_INVLOADER_DATA_')
                return ['sku', 'product-id', 'product-id-type', 'price', 'minimum-seller-allowed-price', 'maximum-seller-allowed-price', 'item-condition',
                    'quantity', 'add-delete', 'will-ship-internationally', 'expedited-shipping', 'item-note', 'merchant-shipping-group-name', 'product_tax_code',
                    'fulfillment_center_id', 'handling-time', 'batteries_required', 'are_batteries_included', 'battery_cell_composition', 'battery_type',
                    'number_of_batteries', 'battery_weight', 'battery_weight_unit_of_measure', 'number_of_lithium_ion_cells', 'number_of_lithium_metal_cells',
                    'lithium_battery_packaging', 'lithium_battery_energy_content', 'lithium_battery_energy_content_unit_of_measure', 'lithium_battery_weight',
                    'lithium_battery_weight_unit_of_measure', 'supplier_declared_dg_hz_regulation1', 'supplier_declared_dg_hz_regulation2', 'supplier_declared_dg_hz_regulation3',
                    'supplier_declared_dg_hz_regulation4', 'supplier_declared_dg_hz_regulation5', 'hazmat_united_nations_regulatory_id', 'safety_data_sheet_url',
                    'item_weight', 'item_weight_unit_of_measure', 'item_volume', 'item_volume_unit_of_measure', 'flash_point', 'ghs_classification_class1',
                    'ghs_classification_class2', 'ghs_classification_class3', 'list_price', 'uvp_list_price'];

            elseif ($feedType == '_POST_FLAT_FILE_LISTINGS_DATA_')
                return ['sku', 'product-id', 'product-id-type', 'price', 'minimum-seller-allowed-price', 'maximum-seller-allowed-price', 'item-condition',
                    'quantity', 'add-delete', 'will-ship-internationally', 'expedited-shipping', 'item-note', 'merchant-shipping-group-name', 'product_tax_code',
                    'fulfillment_center_id', 'handling-time', 'batteries_required', 'are_batteries_included', 'battery_cell_composition', 'battery_type',
                    'number_of_batteries', 'battery_weight', 'battery_weight_unit_of_measure', 'number_of_lithium_ion_cells', 'number_of_lithium_metal_cells',
                    'lithium_battery_packaging', 'lithium_battery_energy_content', 'lithium_battery_energy_content_unit_of_measure', 'lithium_battery_weight',
                    'lithium_battery_weight_unit_of_measure', 'supplier_declared_dg_hz_regulation1', 'supplier_declared_dg_hz_regulation2', 'supplier_declared_dg_hz_regulation3',
                    'supplier_declared_dg_hz_regulation4', 'supplier_declared_dg_hz_regulation5', 'hazmat_united_nations_regulatory_id', 'safety_data_sheet_url',
                    'item_weight', 'item_weight_unit_of_measure', 'item_volume', 'item_volume_unit_of_measure', 'flash_point', 'ghs_classification_class1',
                    'ghs_classification_class2', 'ghs_classification_class3', 'list_price', 'uvp_list_price'];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $feedType);
        }
    }


    private function buildCSVInvloaderDataItemRemoveLastProduct(ShopProduct $shop_product, Product $product)
    {
        try {
            $shop_product->setPriceStock();

            return [
                mb_substr($shop_product->mps_sku, 0, 40),       //$product->getMPSSku(40),                            // mps sku
                $shop_product->marketProductSku,                    // product-id: ASIN
                1,                                                  // product-id-type: 1 -> ASIN
                number_format($shop_product->price, 2, ",", ""),                  // 'price'  DECIMAL POINT: ,
                '',                                                 // 'minimum-seller-allowed-price'       // eliminar
                '',                                                 // 'maximum-seller-allowed-price'       // eliminar
                11,                                                 // 'item-condition'  11 -> Nuevo
                $shop_product->stock,                               // 'quantity'
                'x',                                                // 'add-delete'
                27,                                                 // 'will-ship-internationally'  27 -> España solo
                27,                                                 // 'expedited-shipping', 27 -> España solo
                '',                                                 // 'item-note'
                'Plantilla de Amazon',                              // 'merchant-shipping-group-name'       // España peninsular
                '',                                                 // 'product_tax_code'
                '',                                                 // 'fulfillment_center_id'
                2,                                                  // 'handling-time'                      // 2
                '','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',''
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_product, $product]);
        }
    }


    private function buildCSVInvloaderDataItem(ShopProduct $shop_product, $delete = false, array $offers = [])
    {
        try {
            //$price = $this->getReprice($shop_product, $offers);
            $shop_product->setPriceStock();
            //$shop_product->buildTitle(128)
            //$shop_product->buildDescription4Mobile($length = 10000, $new_line_char = "\n");

            return [
                mb_substr($shop_product->mps_sku, 0, 40),           //$shop_product->getMPSSku(40),      // mps sku
                $shop_product->marketProductSku,                    // product-id: ASIN
                1,                                                  // product-id-type: 1 -> ASIN
                number_format($shop_product->price, 2, ",", ""),    // 'price'  DECIMAL POINT: ,
                '',                                                 // 'minimum-seller-allowed-price'       // eliminar
                '',                                                 // 'maximum-seller-allowed-price'       // eliminar
                11,                                                 // 'item-condition'  11 -> Nuevo
                $shop_product->stock,                               // 'quantity'
                $delete ? 'x' : 'a',                                // 'add-delete'
                27,                                                 // 'will-ship-internationally'  27 -> España solo
                27,                                                 // 'expedited-shipping', 27 -> España solo
                '',                                                 // 'item-note'
                'Plantilla de Amazon',                              // 'merchant-shipping-group-name'       // España peninsular
                '',                                                 // 'product_tax_code'
                '',                                                 // 'fulfillment_center_id'
                2,                                                  // 'handling-time'                      // 2
                '','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',''
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_product, $delete, $offers]);
        }
    }


    private function buildCSVListingsDataFile()
    {
        try {
            $directory = ($this->shop_dir. 'flatfiles');
            Storage::makeDirectory($directory);

            $file_name = 'flatfiles/' .date('Y-m-d_H-i-s'). '.txt';
            $file_path = storage_path('app/' .$this->shop_dir.$file_name);
            $fp = fopen($file_path, 'w');
            $columns = $this->buildCSVColumns('_POST_FLAT_FILE_INVLOADER_DATA_');   // _POST_FLAT_FILE_INVLOADER_DATA_  // _POST_FLAT_FILE_LISTINGS_DATA_
            fputcsv($fp, $columns, $this->delimiter);

            return [$file_name, $fp];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function buildCSVListingsData(Collection $shop_products, $asins)
    {
        try {
            [$file_name, $fp] = $this->buildCSVListingsDataFile();
            $offers = $this->getBestAmazonOffers($asins);
            $count = 0;
            foreach ($shop_products as $shop_product) {

                if (isset($shop_product->marketProductSku)) {

                    // REMOVE LAST PRODUCT ?
                    /* if (isset($shop_product->last_product_id)) {
                        $item = $this->buildCSVInvloaderDataItemRemoveLastProduct($shop_product, $shop_product->last_product);
                        fputcsv($fp, $item, $this->delimiter);
                    } */

                    $item = $this->buildCSVInvloaderDataItem($shop_product, false, $offers);
                    fputcsv($fp, $item, $this->delimiter);
                    $count++;
                }
                else {
                    $shop_product->marketProductSku = 'NO PRODUCT';
                    $shop_product->save();
                }
            }
            fclose($fp);

            if ($count)
                return Storage::get($this->shop_dir.$file_name);

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_products, $asins]);
        }
    }


    private function buildCSVListingsDataRemoveOffer(ShopProduct $shop_product)
    {
        try {
            [$file_name, $fp] = $this->buildCSVListingsDataFile();
            $item = $this->buildCSVInvloaderDataItem($shop_product, true);
            fputcsv($fp, $item, $this->delimiter);
            fclose($fp);

            return Storage::get($this->shop_dir.$file_name);

        } catch (Throwable $th) {
            return $this->throwableArray($th, __FUNCTION__);
        }
    }


    /************** PRIVATE FUNCTIONS - OFFERS & PRODUCTS ***************/


    private function getBestAmazonOffers($asins)
    {
        try {
            if (empty($asins)) return [];

            $offers = [];
            $chunks = array_chunk($asins, 20);
            foreach ($chunks as $chunk)
                $offers = array_merge($offers, $this->getBestOfferListings($chunk));

            return $offers;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $asins);
        }
    }


    private function getASINSById(Collection $shop_products, $idType = 'EAN')
    {
        try {
            $asins = [];
            $req = $this->amznGetMatchingProductForIdRequest();

                $market_params = $this->market->market_params;
                foreach ($shop_products as $shop_product) {
                    if (isset($shop_product->ean)) {

                        if (!$res = $this->amznGetMatchingProductForId($shop_product->ean, $req)) continue;
                        if ($res->isSetGetMatchingProductForIdResult() && $GetMatchingProductForIdResult = $res->getGetMatchingProductForIdResult()) {

                            foreach ($GetMatchingProductForIdResult as $MatchingProduct) {
                                if ($MatchingProduct->isSetId() && $IdType_Id = $MatchingProduct->getId()) {        // EAN
                                    if ($MatchingProduct->isSetProducts() && $Products = $MatchingProduct->getProducts()) {
                                        if ($Products->isSetProduct() && $Product = $Products->getProduct()) {

                                            foreach ($Product as $p) {

                                                if ($p->isSetSalesRankings() && $salesRankings = $p->getSalesRankings()) {
                                                    if ($salesRankings->isSetSalesRank() && $salesRank = $salesRankings->getSalesRank()) {

                                                        foreach ($salesRank as $salesRankType) {
                                                            if ($salesRankType->isSetProductCategoryId() && $productCategoryId = $salesRankType->getProductCategoryId()) {
                                                                if (is_numeric($productCategoryId)) {
                                                                    if ($productCategoryId != $shop_product->market_category->marketCategoryId) {
                                                                        if ($new_market_category = $this->market->market_categories()->firstWhere('marketCategoryId', $productCategoryId)) {
                                                                            $shop_product->market_category_id = $new_market_category->id;
                                                                            $shop_product->save();
                                                                            $shop_product->setMarketParams($market_params);
                                                                        }
                                                                        $this->nullAndStorage(__METHOD__, ['shop_product_id' => $shop_product->id, 'new_marketCategoryId' => $productCategoryId]);
                                                                    }
                                                                }
                                                                else
                                                                    $this->nullAndStorage(__METHOD__, ['shop_product_id' => $shop_product->id, 'NO_numeric_marketCategoryId' => $productCategoryId]);
                                                            }
                                                        }
                                                    }
                                                }
                                                else
                                                    $this->nullAndStorage(__METHOD__, ['shop_product_id' => $shop_product->id, 'NO_sales_ranking_EAN' => $IdType_Id]);

                                                if ($p->isSetIdentifiers() && $Identifiers = $p->getIdentifiers()) {
                                                    if ($Identifiers->isSetMarketplaceASIN() && $MarketplaceASIN = $Identifiers->getMarketplaceASIN()) {
                                                        if ($MarketplaceASIN->isSetASIN() && $asin = $MarketplaceASIN->getASIN())
                                                            $asins[$IdType_Id] = $asin;
                                                    }
                                                }
                                            }

                                        }
                                    }
                                    else {
                                        $shop_product->marketProductSku = 'NO PRODUCT';
                                        $shop_product->save();
                                    }
                                }
                            }

                        }
                    }

                }

            return $asins;

        } catch (MarketplaceWebServiceProducts_Exception $ex) {
            return $this->nullWithErrors($ex, __METHOD__, [$shop_products, $idType]);
        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_products, $idType]);
        }
    }


    private function postNewProductsOffers(Collection $shop_products, $delete = false)
    {
        try {
            // Save ASINs to ShopProducts
            $asins = $this->getASINSById($shop_products, 'EAN');

            if (!isset($asins) || !is_array($asins) || !count($asins))
                return $asins;

            foreach ($shop_products as $shop_product) {
                if (isset($shop_product->ean) && isset($asins[$shop_product->ean])) {
                    $shop_product->marketProductSku = $asins[$shop_product->product->ean];
                    $shop_product->save();
                }
            }

            if ($listingsDataContent = $this->buildCSVListingsData($shop_products, $asins))
                return $this->amznSubmitFeed($listingsDataContent, '_POST_FLAT_FILE_INVLOADER_DATA_');      // _POST_FLAT_FILE_LISTINGS_DATA_

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_products, $delete]);
        }
    }


    private function postPricesStocksOffers(Collection $shop_products)
    {
        try {
            $asins = $shop_products->pluck('marketProductSku')->all();
            $listingsDataContent = $this->buildCSVListingsData($shop_products, $asins);
            if (isset($listingsDataContent['code']))
                return $listingsDataContent;

            return $this->amznSubmitFeed($listingsDataContent, '_POST_FLAT_FILE_INVLOADER_DATA_');      // _POST_FLAT_FILE_LISTINGS_DATA_

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products);
        }
    }


    private function postRemoveOffer(ShopProduct $shop_product)
    {
        try {
            $listingsDataContent = $this->buildCSVListingsDataRemoveOffer($shop_product);
            if (isset($listingsDataContent['code']))
                return $listingsDataContent;

            $shop_product->delete();

            return $this->amznSubmitFeed($listingsDataContent, '_POST_FLAT_FILE_INVLOADER_DATA_');      // _POST_FLAT_FILE_LISTINGS_DATA_

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function postPayload(Collection $shop_products, $type = 'new')
    {
        try {
            $products_result = [];
            $products_result['products_count'] = $shop_products->count();
            $count = 0;
            $productFeedContent = $this->buildAmazonEnvelope('Product');
            $inventoryFeedContent = $this->buildAmazonEnvelope('Inventory');
            $priceFeedContent = $this->buildAmazonEnvelope('Price');
            $imageFeedContent = $this->buildAmazonEnvelope('ProductImage');

            foreach ($shop_products as $shop_product) {

                // ONLY Post NEW Products WITHOUT ASIN, BUT with marketCategory && Images
                if (!isset($shop_product->marketProductSku) && isset($shop_product->market_category_id) && $shop_product->product->images()->count()) {
                    $count++;
                    $this->buildProductFeed($productFeedContent, $shop_product);
                    $this->buildInventoryFeed($inventoryFeedContent, $shop_product);
                    $this->buildPriceFeed($priceFeedContent, $shop_product);
                    $this->buildImageFeed($imageFeedContent, $shop_product);
                }
            }

            // Submit Feeds
            if ($count > 0) {

                Storage::append($this->shop_dir. __FUNCTION__.'/' .date('Y-m-d_H-i-s'). '_productFeedContent.xml', $productFeedContent->asXML());
                Storage::append($this->shop_dir. __FUNCTION__.'/' .date('Y-m-d_H-i-s'). '_inventoryFeedContent.xml', $inventoryFeedContent->asXML());
                Storage::append($this->shop_dir. __FUNCTION__.'/' .date('Y-m-d_H-i-s'). '_priceFeedContent.xml', $priceFeedContent->asXML());
                Storage::append($this->shop_dir. __FUNCTION__.'/' .date('Y-m-d_H-i-s'). '_imageFeedContent.xml', $imageFeedContent->asXML());

                $products_result['job']['Product'] = $this->amznSubmitFeed($productFeedContent, ($type == 'new') ? '_POST_PRODUCT_DATA_' : '_POST_PRODUCT_OVERRIDES_DATA_');
                $products_result['job']['Inventory'] = $this->amznSubmitFeed($inventoryFeedContent, ($type == 'new') ? '_POST_INVENTORY_AVAILABILITY_DATA_' : '_POST_PRODUCT_OVERRIDES_DATA_');
                $products_result['job']['Price'] = $this->amznSubmitFeed($priceFeedContent, '_POST_PRODUCT_PRICING_DATA_');
                $products_result['job']['ProductImage'] = $this->amznSubmitFeed($imageFeedContent, '_POST_PRODUCT_IMAGE_DATA_');
            }

            $products_result['feeds_count'] = $count;

            return $products_result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_products, $type]);
        }
    }



    /************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getBrands()
    {
        return false;
    }


    public function getCategories($marketCategoryId = null)
    {
        return $this->getChildCategories($marketCategoryId);
    }

    // https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_4_1/amzn-base.xsd
    // Battery type
    // Buyer price type
    // Currency amount type
    // Carrier Code element -> Empreses de Transport
    // Related Product ID element -> UPC, EAN, GTIN
    // Condition Info -> New, UsedLikeNew, ..
    // Computer Platform -> windows, mac, linux
    // Color and ColorMap -> neige, black, ...
    // Unit of Measure Types
    // CE and CameraPhoto type definitions
    public function getAttributes(Collection $market_categories)
    {
        return false;
    }


    public function getFeed(ShopProduct $shop_product)
    {
        try {
            $res = [];
            $res['offer'] = $this->buildCSVInvloaderDataItem($shop_product);
            $res['product'] = $this->buildItemFeed($shop_product);

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function getJobs()
    {
        try {
            $jobsResult = [];
            $shopJobs = $this->shop->shop_jobs()->whereNull('total_count')->get();
            $jobsResult['jobs'] = $shopJobs->count();
            foreach ($shopJobs as $shopJob) {

                $res = $this->amznGetFeedSubmissionList($shopJob);
                if ($res->isSetGetFeedSubmissionListResult() && $feedSubmissionListResult = $res->getGetFeedSubmissionListResult()) {
                    if ($feedSubmissionListResult->isSetFeedSubmissionInfo() && $feedSubmissionInfoList = $feedSubmissionListResult->getFeedSubmissionInfoList())
                        $jobsResult['responses'][$shopJob->jobId][] = $this->handleFeedSubmissionInfoList($feedSubmissionInfoList, $shopJob);

                    if ($feedSubmissionListResult->isSetNextToken())
                        $jobsResult['responses'][$shopJob->jobId][] = $this->getFeedSubmissionListByNextToken($feedSubmissionListResult->NextToken(), $shopJob);
                }
            }

            return $jobsResult;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function getOrders()
    {
        try {
            $res = $this->amznListOrders();

            Storage::append($this->shop_dir. 'test/' .date('Y-m-d_H-i'). '_getOrders.txt', json_encode($res));

            if ($res->isSetListOrdersResult() && $listOrdersResult = $res->getListOrdersResult()) {

                Storage::append($this->shop_dir. 'test/' .date('Y-m-d_H-i'). '_listOrdersResult.txt', json_encode($listOrdersResult));

                if ($listOrdersResult->isSetOrders() && $listOrders = $listOrdersResult->getOrders())
                    $ordersCount = $this->handleListOrders($listOrders);

                if ($listOrdersResult->isSetNextToken())
                    $ordersCount += $this->listOrdersByNextToken($listOrdersResult->getNextToken());

                return 'Creados o actualizados '.$ordersCount. ' pedidos.';
            }

            return null;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
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
            $res = [];
            $shop_products = $this->getShopProducts4Create($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos nuevos en esta Tienda';

            $res['products_with_ean'] = $this->postNewProductsOffers($shop_products);


            // Post Offers x ShopProducts withOUT ASIN
            $shop_products = $this->getShopProducts4Create($shop_products);
            $res['products_without_ean'] = $this->postPayload($shop_products, 'new');

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

            return $this->postPricesStocksOffers($shop_products);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postPricesStocks($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            return $this->postPricesStocksOffers($shop_products);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postGroups($shop_products = null)
    {
        return false;
    }


    public function removeProduct($marketProductSku = null)
    {
        try {
            if (isset($marketProductSku) && $shop_product = $this->shop->shop_products()->where('marketProductSku', $marketProductSku)->first()) {
                $res = $this->postRemoveOffer($shop_product);
                $shop_product->deleteSecure();

                return $res;
            }

            return null;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketProductSku);
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
        try {
            $res = [
                'total_products'        => 0,
                'not_found_products'    => [],
                'new_products'          => [],
                'new_marketProductSkus' => [],
            ];

            $amazon_products = $this->getAmazonProducts();
            if (!is_array($amazon_products)) {
                sleep(10);
                $amazon_products = $this->getAmazonProducts($amazon_products);  // $amazon_products == $reportRequestId
            }

            // [$columns, $values]
            if (is_array($amazon_products) && isset($amazon_products[1])) {

                // $amazon_products[0] == header
                foreach ($amazon_products[1] as $amzn_product) {
                    $res['total_products']++;

                    // MPS SKU is Found
                    if (isset($amzn_product[3])) {
                        $shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $amzn_product[3]);
                        if (!isset($shop_product)) {
                            // Search Product
                            $product_id = FacadesMpe::getIdFromMPSSku($amzn_product[3]);
                            $product = Product::find($product_id);
                            if (!isset($product))
                                $res['not_found_products'][] = $product_id;
                            // Create Shop Product
                            else {
                                $res['new_products'][] = $shop_product->product_id;
                                $shop_product = $this->shop->updateOrCreateShopProduct($product);
                            }
                        }

                        // Amazon ASIN is Found
                        if (isset($amzn_product[16]) && isset($shop_product)) {
                            if ($shop_product->marketProductSku != $amzn_product[16]) {
                                $res['new_marketProductSkus'][] = $amzn_product[16];
                                $shop_product->marketProductSku = $amzn_product[16];
                                $shop_product->save();
                            }
                        }
                    }
                }
            }

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    protected function syncCategories()
    {
        try {
            $changes = [];
            $mp_products = $this->getAmazonProducts();
            if (!is_array($mp_products)) {
                sleep(10);
                $mp_products = $this->getAmazonProducts($mp_products);  // $amazon_products == $reportRequestId
            }

            dd($mp_products);

            $market_params = $this->market->market_params;
            foreach ($mp_products as $mp_product) {

                /* if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $offer['product']['sku'])) {
                    $new_category_code = $offer['product']['category']['code'];
                    $shop_product_category_code = $shop_product->market_category->marketCategoryId;

                    if ($new_category_code != $shop_product_category_code) {
                        $shop_product->longdesc = utf8_encode($shop_product->longdesc);
                        $changes['CATEGORY CHANGES'][$new_category_code][] = [
                            'old_code' => $shop_product_category_code,
                            'mp_sku' => $shop_product->marketProductSku,
                            'shop_product' => 'shop_product' => [
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
                    $changes['NOT FOUND'][] = $offer; */

            }

            Storage::append($this->shop_dir. 'categories/' .date('Y-m-d'). '_syncCategories.txt', json_encode($changes));
            return $changes;
        }
        catch (Throwable $th) {
           return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function removeWithoutStock()
    {
        /* try {
            $res = [];
            foreach ($this->shop->shop_products as $shop_product) {
                $shop_product->setPriceStock(null, $this->cost_is_price);
                if ($shop_product->stock == 0) {
                    if (!$shop_product->isUpgradeable())
                        $shop_product->deleteSecure();
                    else {
                        if ($shop_product->deleteSecure())
                            $res['DELETE_ONLINE'][] = [$shop_product->getMPSSku(), $shop_product->ean];
                    }
                }
            }

            // REMOVE ONLINE OFFERS
            if (isset($res['DELETE_ONLINE'])) {
                $offers = $this->buildRemoveItems($res['DELETE_ONLINE']);
                $res['POST_DELETES'] = $this->postMiraklOffers($offers);
            }

            return $res;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        } */
    }


    /************* REQUEST FUNCTIONS *********************/


    public function getProduct($id)
    {
        try {
            $res = [
                'ASIN'                  => [],
                'SKU'                   => [],
                'salesRankType'         => [],
                'attributeSets'         => [],
                'relationships'         => [],
                'competitivePricing'    => [],
                'lowestOfferListings'   => [],
                'offers'                => [],
            ];

            $req = $this->amznGetMatchingProductForIdRequest('ASIN');   // EAN
            $product_response = $this->amznGetMatchingProductForId($id, $req);

            if ($product_response->isSetGetMatchingProductForIdResult() && $GetMatchingProductForIdResult = $product_response->getGetMatchingProductForIdResult())

                foreach ($GetMatchingProductForIdResult as $MatchingProduct) {

                    if ($MatchingProduct->isSetId() && $IdType_Id = $MatchingProduct->getId())         // asin, ean
                        if ($MatchingProduct->isSetProducts() && $Products = $MatchingProduct->getProducts())
                            if ($Products->isSetProduct() && $Product = $Products->getProduct())

                                foreach ($Product as $p) {

                                    if ($p->isSetIdentifiers() && $identifiers = $p->getIdentifiers()) {
                                        if ($identifiers->isSetMarketplaceASIN() && $marketplaceASIN = $identifiers->getMarketplaceASIN()) {
                                            if ($marketplaceASIN->isSetASIN()) {
                                                $ASIN = $marketplaceASIN->getASIN();
                                                $res['ASIN'][] = $ASIN;
                                            }
                                        }
                                        if ($identifiers->isSetSKUIdentifier() && $SKUIdentifier = $identifiers->getSKUIdentifier()) {
                                            $res['SKU'][] = $SKUIdentifier;
                                            /* if ($SKUIdentifier->isSetSKU()) {
                                                $SKU = $SKUIdentifier->getSKU();
                                                $res['SKU'][] = $SKU;
                                            } */
                                        }
                                    }

                                    if ($p->isSetSalesRankings() && $salesRankings = $p->getSalesRankings()) {
                                        if ($salesRankings->isSetSalesRank() && $salesRank = $salesRankings->getSalesRank())
                                            foreach ($salesRank as $salesRankType)
                                                $res['salesRankType'][] = $salesRankType;
                                        else
                                            $res['salesRankType'][] = $salesRankings;
                                    }

                                    if ($p->isSetAttributeSets() && $attributeSets = $p->getAttributeSets()) {
                                        if ($attributeSets->isSetAny() && $any = $attributeSets->getAny())
                                            foreach ($any as $a) {
                                                $res['attributeSets'][] = $a;
                                                $res['attributeSets'][] = $this->get_inner_html($a);
                                                $res['attributeSets'][] = $this->getInnerHTML($a);
                                            }
                                        else
                                            $res['attributeSets'][] = $attributeSets;
                                    }

                                    if ($p->isSetRelationships() && $relationships = $p->getRelationships()) {
                                        if ($relationships->isSetAny() && $any = $relationships->getAny())
                                            foreach ($any as $a) {
                                                $res['relationships'][] = $a;
                                                $res['relationships'][] = $this->get_inner_html($a);
                                                $res['relationships'][] = $this->getInnerHTML($a);
                                            }
                                        else
                                            $res['relationships'][] = $relationships;
                                    }

                                    if ($p->isSetCompetitivePricing() && $competitivePricing = $p->getCompetitivePricing())
                                        $res['competitivePricing'][] = $competitivePricing;

                                    if ($p->isSetLowestOfferListings() && $lowestOfferListings = $p->getLowestOfferListings())
                                        $res['lowestOfferListings'][] = $lowestOfferListings;

                                    if ($p->isSetOffers() && $offers = $p->getOffers())
                                        $res['offers'][] = $offers;
                                }
                }


            $myFeesEstimate = $this->getMyFeesEstimateRequest(2832.41, $id, 'ASIN');

            dd($res, $myFeesEstimate);

        } catch (Throwable $th) {
            dd($th, $id, $res);
        }
    }


    public function getAllProducts()
    {
        // RequestReport(_GET_MERCHANT_LISTINGS_DATA_)      --> ReportRequestId
        // GetReportRequestList(OPCIONAL: ReportRequestId)  --> _DONE_ --> GeneratedReportId
        //      getReportListByNextToken
        // OPCIONAL???: GetReportList   -> Llista de ReportId(= GeneratedReportId) i ReportRequestId
        // GetReport(ReportId = GeneratedReportId)
        try {
            $mp_products = $this->getAmazonProducts('50046018752');
            /* if (!is_array($mp_products)) {
                sleep(10);
                $mp_products = $this->getAmazonProducts($mp_products);  // $mp_products == $reportRequestId
            } */

            dd($mp_products);

            // $header = $amazon_products[0];
            // 0 => "Título del producto",
            // 1 => "Descripción del producto", ('')
            // 2 => "Identificador del listing", (1104XMNRUJU)
            // 3 => "SKU del vendedor", (37896_9S7-154114-054_4719072718152)
            // 4 => "Precio", (2832.41)
            // 5 => "Cantidad", (43)
            // 6 => "Fecha de creación" (04/11/2020 14:36:37 MET)
            // "Columna en desuso",
            // 8 => "Producto para vender", (y)
            // 9 => "Tipo de identificador de producto", (1)
            // "Columna en desuso", "Nota sobre el producto",
            // 12 => "Estado del producto" (11)
            // "Columna en desuso", "Columna en desuso", "Columna en desuso",
            // 16 => "ASIN 1", (B086Z61N2V)
            // "Columna en desuso", "Columna en desuso", "Envío internacional",
            // "Envío urgente", "Columna en desuso",
            // 22 => "Identificador del producto", (B086Z61N2V)
            // "Columna en desuso", "Añadir o eliminar",
            // 25 => "Cantidad pendiente", (0)
            // 26 => "Canal de gestión logística", (DEFAULT)
            // "Precio de negocio", "Tipo de precio por cantidad", "Límite inferior 1 por cantidad", "Precio 1 por cantidad",
            // "Límite inferior 2 por cantidad", "Precio 2 por cantidad", "Límite inferior 3 por cantidad", "Precio 3 por cantidad", "Límite inferior 4 por cantidad"
            // "Precio 4 por cantidad", "Límite inferior 5 por cantidad", "Precio 5 por cantidad",
            // 39 => "merchant-shipping-group", (Plantilla de Amazon)
            // "Tipo de precio progresivo" "Límite inferior progresivo 1", "Precio progresivo 1", "Límite inferior progresivo 2",
            // "Precio progresivo 2", "Límite inferior progresivo 3", "Precio progresivo 3"

        }
        catch (Throwable $th) {
            dd($th, $mp_products ?? '');
        }
    }


    public function test()
    {
        try {
            // GetServiceStatus
            $service = $this->getServiceProduct();
            $req = new MarketplaceWebServiceProducts_Model_GetServiceStatusRequest();

            $req->setSellerId($this->sellerId);
            $req->setMWSAuthToken($this->mwsAuthToken);
            //$req->setMarketplaceId($this->marketplaceId);

            $getMyFeesEstimateResponse = $service->getServiceStatus($req);
            //$this->storageResponse('getServiceStatus', $getMyFeesEstimateResponse);
            $serviceStatusResult = $getMyFeesEstimateResponse->getGetServiceStatusResult();

            // GREEN
            dd($serviceStatusResult->Status);

        } catch (Throwable $th) {
            dd($th);
        }
    }


    public function getReportRequestType($reportType)
    {
        try {
            // _GET_MERCHANT_LISTINGS_DATA_
            $reportResults = null;
            $reportRequestId = $this->amznRequestReport($reportType);
            sleep(20);

            // Test if it's _DONE_
            // Returns: [][
            //    'reportProcessingStatus'=> "_DONE_",
            //    'reportRequestId'       => "50025018541",
            //    'generatedReportId'     => "24549957718018536",
            //    'reportType'            => "_GET_MERCHANT_LISTINGS_DATA_"]
            $reportRequestList = $this->amznGetReportRequestList($reportRequestId);
            //$this->storageResponse('getReportRequestList', $reportType);

            foreach ($reportRequestList as $reportRequest) {
                if ($reportRequest['reportProcessingStatus'] != '_DONE_') {
                    $reportResults[] = $reportRequest;
                } else {
                    // Returns: [
                    //    'reportRequestId'   => "50025018541",
                    //    'reportType'        => "_GET_MERCHANT_LISTINGS_DATA_",
                    //    'reportId'          => "24675112193018541"];
                    /* $reportList = $this->getReportList($reportRequest['reportRequestId'], $reportType);
                    $this->storageResponse('getReportList', $reportList);

                    foreach ($reportList as $report)                       // 24663702585018540
                        $reportResults[] = $this->getReport($report['reportId'], $report['reportType']); */
                }
            }

            dd($reportResults);

            return $reportResults;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $reportType);
        }
    }


    private function get_inner_html($node) {
        $innerHTML= '';
        $children = $node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $child->ownerDocument->saveXML( $child );
        }

        return $innerHTML;
    }


    private function getInnerHTML($node)
    {
        $Body = $node->ownerDocument->documentElement->firstChild->firstChild;
        $Document = new DOMDocument();
        $Document->appendChild($Document->importNode($Body,true));
        return $Document->saveHTML();
    }


    public function getFeesEstimates($shop_products = null)
    {
        $shop_products = $this->getShopProducts4Update($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos para analizar';

        $res = [];
        $count = 0;
        foreach ($shop_products as $shop_product) {

            $myFeesEstimate = $this->getMyFeesEstimateRequest($shop_product->price, $shop_product->marketProductSku, 'ASIN');
            if (isset($myFeesEstimate) && $shop_product->param_mp_fee != round(($myFeesEstimate / $shop_product->price)*100, 1))
                $res[] = [$shop_product->marketProductSku, round($shop_product->param_mp_fee, 0), round(($myFeesEstimate / $shop_product->price)*100, 0)];

            $count++;
            if ($count = 10) {
                sleep(1);
                $count = 0;
            }
        }

        dd($res);
    }



    // No channel information: 20 asins x call
    public function getCompetitivePricing($asins)
    {
        dd($this->getCompetitivePricingForASINRequest($asins));
    }


    // No includes Amazon: 20 asins x call
    public function getLowestOffer($asins)
    {
        dd($this->getBestOfferListings($asins));
    }


    // Get BuyBox Winner. Includes Amazon Or Not: 1 asin x call
    public function getLowestPrice($asin)
    {
        dd($this->getLowestPricedOffersForASINRequest($asin));
    }



}
