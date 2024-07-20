<?php

namespace App\Libraries;


use App\Address;
use App\AttributeMarketAttribute;
use App\Buyer;
use App\Category;
use App\Country;
use App\Currency;
use Facades\App\Facades\ShopProductsExcel as FacadesShopProductsExcel;
use App\MarketAttribute;
use App\MarketCategory;
use App\Order;
use App\Property;
use App\PropertyValue;
use App\RootCategory;
use App\Shop;
use App\ShopJob;
use App\ShopProduct;
use App\Status;
use App\Traits\HelperTrait;
use App\Type;
use App\MarketCarrier;
use App\Product;
use App\ShopFilter;
use App\Supplier;
use App\SupplierCategory;
use Facades\App\Facades\Mpe as FacadesMpe;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MCI\Shop\Client\ShopApiClient as ShopApiClientCatalog;
use Mirakl\MMP\Shop\Client\ShopApiClient as ShopApiClientProducts;
use Mirakl\MCI\Shop\Request\Attribute\GetAttributesRequest;
use Mirakl\MCI\Shop\Request\Hierarchy\GetHierarchiesRequest;
use Mirakl\MCI\Shop\Request\Product\DownloadProductImportErrorReportRequest;
use Mirakl\MCI\Shop\Request\Product\DownloadProductImportNewProductsReportRequest;
use Mirakl\MCI\Shop\Request\Product\DownloadProductImportTransformationErrorReportRequest;
use Mirakl\MCI\Shop\Request\Product\DownloadProductImportTransformedFileRequest;
use Mirakl\MCI\Shop\Request\Product\ProductImportRequest;
use Mirakl\MCI\Shop\Request\Product\ProductImportStatusesRequest;
use Mirakl\MCI\Shop\Request\Product\ProductImportStatusRequest;
use Mirakl\MCI\Shop\Request\ValueList\GetValueListsItemsRequest;
use Mirakl\MMP\Common\Domain\Discount;
use Mirakl\MMP\Common\Domain\Offer\Price\OfferPricing;
use Mirakl\MMP\Common\Domain\Offer\Price\VolumePrice;
use Mirakl\MMP\OperatorShop\Domain\Collection\Offer\UpdateOfferPricesCollection;
use Mirakl\MMP\OperatorShop\Domain\Collection\Offer\UpdateOfferCollection;
use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\OfferImportResult;
use Mirakl\MMP\OperatorShop\Domain\Offer\UpdateOffer;
use Mirakl\MMP\Shop\Domain\Collection\Order\ShopOrderCollection;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use Mirakl\MMP\Shop\Domain\Order\ShopOrderCustomer;
use Mirakl\MMP\Shop\Request\Offer\GetOfferRequest;
use Mirakl\MMP\Shop\Request\Offer\GetOffersRequest;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportErrorReportRequest;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportReportRequest;
use Mirakl\MMP\Shop\Request\Offer\UpdateOffersRequest;
use Mirakl\MMP\Shop\Request\Order\Accept\AcceptOrderRequest;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Mirakl\MMP\Shop\Request\Order\Tracking\UpdateOrderTrackingInfoRequest;
use Mirakl\MMP\Shop\Request\Order\Ship\ShipOrderRequest;
use Mirakl\MMP\Shop\Request\Product\GetProductsRequest;
use Mirakl\MMP\Shop\Request\Shipping\GetShippingCarriersRequest;
use Mirakl\MMP\Common\Domain\Order\Accept\AcceptOrderLine;
use Mirakl\MMP\Shop\Request\AdditionalField\GetAdditionalFieldRequest;
use Mirakl\MMP\Shop\Request\Channel\GetChannelsRequest;
use Mirakl\MMP\Shop\Request\Document\GetDocumentsConfigurationRequest;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportRequest;
use Mirakl\MMP\Shop\Request\Offer\State\GetOfferStateListRequest;
use Mirakl\MMP\Shop\Request\Payment\Invoice\GetInvoicesRequest;
use Mirakl\MMP\Shop\Request\Product\Offer\GetOffersOnProductsRequest;
use Mirakl\MMP\Shop\Request\Shipment\GetShipmentsRequest;
use Mirakl\MMP\Shop\Request\Shipping\GetLogisticClassRequest;
use Mirakl\MMP\Shop\Request\Shipping\GetShippingZonesRequest;
use SplFileObject;
use Throwable;


class MiraklWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    protected $apiUrl = null;
    protected $apiKey = null;
    protected $shopId = null;

    protected $csv = false;
    protected $locale = 'es_ES';            // 'pt_PT'
    protected $all_categories_are_root = false;
    protected $logistic_classes = 'freedelivery';
    protected $state_codes = ['New' => 11];
    protected $offer_desc = 'Producto 100% nuevo, a estrenar, con entrega en domicilio a pie de calle.';
    protected $offer_desc_used = 'Producto Caja Abierta en perfecto estado y con la garantía total del fabricante.';
    protected $offer_desc_refurbished = 'Producto Caja Abierta en perfecto estado y con la garantía total del fabricante.';
    protected $tax_rate = 21.00;
    protected $reprice = false;

    const DEFAULT_CONFIG = [
        // MarketWS
        'header' => null,
        'header_offer' => null,
        'header_rows' => 2,
        'order_status_ignored' => [
            'WAITING_ACCEPTANCE', 'WAITING_DEBIT_PAYMENT', 'SHIPPED', 'RECEIVED', 'STAGING', 'CLOSED', 'AUTO_RECEIVED'
        ],
        'order_status_auto_response' => [
            'WAITING_ACCEPTANCE'
        ],
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

        // MiraklWS
        'csv' => false,
        'all_categories_are_root' => false,
        'logistic_classes' => null,
        'state_codes'        => ['New' => '11'],
        'shipping_zones'    => [],
    ];


    function __construct(Shop $shop)
    {
        parent::__construct($shop);

        $this->config = json_decode($shop->config);
        if (isset($this->config)) {
            if (isset($this->config->csv))
                $this->csv = $this->config->csv;

            if (isset($this->config->all_categories_are_root))
                $this->all_categories_are_root = $this->config->all_categories_are_root;

            if (isset($this->config->logistic_classes))
                $this->logistic_classes = $this->config->logistic_classes;



            if (isset($this->config->state_codes))
                $this->state_codes = $this->config->state_codes;

            if (isset($this->config->offer_desc))
                $this->offer_desc = $this->config->offer_desc;

            if (isset($this->config->offer_desc_used))
                $this->offer_desc_used = $this->config->offer_desc_used;

            if (isset($this->config->offer_desc_refurbished))
                $this->offer_desc_refurbished = $this->config->offer_desc_refurbished;

            if (isset($this->config->tax_rate))
                $this->tax_rate = $this->config->tax_rate;

            if (isset($this->config->reprice))
                $this->reprice = $this->config->reprice;
        }

        $this->apiUrl = $shop->endpoint;
        $this->apiKey = $shop->token;
        $this->shopId = $shop->marketShopId;

        if (!isset($this->order_status_auto_response)) $this->order_status_auto_response = ['WAITING_ACCEPTANCE'];
    }



    /************** PRIVATE FUNCTIONS MIRAKL ***************/


    private function getMiraklChannels()
    {
        // Worten: 'WRT_PT_ONLINE'
        // Carrefour:
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            // \Mirakl\MMP\Shop\Request\Channel\GetChannelsRequest $request
            $request = new GetChannelsRequest();
            $response = $api->getChannels($request);    //CH11

            Storage::append($this->shop_dir. 'channels/' .date('Y-m-d'). '_getMiraklChannels.json', json_encode($response->toArray()));
            return $response;

        } catch (Throwable $th) {
            dd($th);
        }

        return null;
    }


    private function getMiraklListAccounting()
    {
        try {
            $request = new GetInvoicesRequest();        //IV01
            /* $request->setPaginate(true);
            $request->setMax($max);
            $request->setOffset($offset); */
            //$start_updated_date = now()->addDays($days)->toDateTime();
            //$request->setStartUpdateDate($start_updated_date);
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $response = $api->getInvoices($request);

            Storage::put($this->shop_dir. 'invoices/' .date('Y-m-d ').'.json', json_encode($response->toArray()));

            foreach ($response as $invoice) {
                $invoiceId = $invoice->getInvoiceId();
                //$startDate = $invoice->getStartDate();
                $startDateCarbon = new Carbon($invoice->getStartDate());
                //$endDate = $invoice->getEndDate();
                $endDateCarbon = new Carbon($invoice->getEndDate());
                //$payment = $invoice->getPayment();
                $payment_state = $invoice->getPayment()->getState();      // PAID
                $summary = $invoice->getSummary();

                $totalPayableOrdersInclTax = $summary->getTotalPayableOrdersInclTax();  // price pvp (inclos iva)
                $totalCommissionsExclTax = $summary->getTotalCommissionsExclTax();      // mp_bfit (sense iva)
                $totalCommissionsInclTax = $summary->getTotalCommissionsInclTax();      // mp_bif (iva inclos)
                $amountTransferred = $summary->getAmountTransferred();                  // Total cobrado = $totalPayableOrdersInclTax - $totalCommissionsInclTax
                $totalOperatorPaidShippingChargesInclTax = $summary->getTotalOperatorPaidShippingChargesInclTax(); // shipping_price ports (iva inclos)
            }

            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getTransactionInfo(&$info, $json_res)
    {
        try {
            foreach ($json_res->data as $transaction) {

                $basic_data = [
                    'amount'            => $transaction->amount,
                    'amount_debited'    => $transaction->amount_debited,
                    'state'             => $transaction->payment_state,
                    'type'              => $transaction->type,
                    'updated'           => Carbon::createFromFormat('Y-m-d\TH:i:s.v\Z', $transaction->last_updated)->format('Y-m-d H:i:s'),
                    'data'              => json_decode(json_encode($transaction), true)
                ];

                if (isset($transaction->accounting_document_number))
                    $basic_data['invoice'] = $transaction->accounting_document_number;

                if (isset($transaction->entities->domain)) {

                    if ($transaction->entities->domain == 'PRODUCT') {

                        if (!isset($info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id])) {
                            $info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id] = $basic_data;
                            $info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id]['charget'] = $transaction->payment_state == 'PAID';
                        }

                        if (in_array($transaction->type, ['ORDER_AMOUNT_TAX', 'ORDER_AMOUNT'])) {
                            $price = $info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id]['price'] ?? null;
                            if (isset($price)) {
                                $price += $transaction->amount;
                                $info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id]['price_good'] = true;
                            } else {
                                $price = $transaction->amount;
                            }
                            $info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id]['price'] = $price;

                        } elseif (in_array($transaction->type, ['ORDER_SHIPPING_AMOUNT', 'ORDER_SHIPPING_AMOUNT_TAX'])) {
                            $shipping_price = $info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id]['shipping_price'] ?? null;
                            if (isset($shipping_price)) {
                                $shipping_price += $transaction->amount_debited;
                                $info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id]['shipping_price_good'] = true;
                            } else {
                                $shipping_price = $transaction->amount_debited;
                            }
                            $info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id]['shipping_price'] = $shipping_price;

                        } elseif ($transaction->type == 'COMMISSION_FEE') {

                            $info['ORDER'][$transaction->entities->order->id][$transaction->entities->order_line->id]['mp_bfit'] = $transaction->amount_debited;
                        }

                    }
                    else
                        $info[$transaction->entities->domain][] = $basic_data;
                }
                elseif (isset($transaction->accounting_document_number))
                    $info['INVOICE'][$transaction->accounting_document_number][] = $basic_data;
                else
                    $info[] = $basic_data;
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$info, $json_res]);
        }
    }


    private function getMiraklListTransactions($page_token = null)
    {
        try {
            $client = new Client();     //['base_uri' => $this->apiUrl]);
            $query = [
                'shop_id'   => $this->shopId,
                //'payment_state' => 'PAID'
                //'page_token'   => ''
            ];
            if (isset($page_token)) $query['page_token'] = $page_token;

            $response = $client->get($this->apiUrl.'/sellerpayment/transactions_logs', [
                'headers' => [
                    "User-Agent" => "Mirakl-PHP-SDK/1.11.1 GuzzleHttp/6.5.5 curl/7.70.0 PHP/7.3.22",
                    "Authorization" => $this->apiKey,
                    "Accept" => "application/json"
                ],
                'query' => $query,
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->shop_dir. 'transactions/' .date('Y-m-d'). '_transactions.json', $contents);
                $json_res = json_decode($contents);

                return $json_res;
            }

            return $this->nullAndStorage(__METHOD__, $page_token);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $page_token);
        }
    }


    private function getMiraklLocaleCodes()
    {
        // Worten: 'WRT_PT_ONLINE'
        // Carrefour:
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            //$request = new getChannels LogisticClassRequest();
            $response = $api->getLocales();    // L01

            Storage::append($this->shop_dir. 'locales/' .date('Y-m-d'). '_getMiraklLocaleCodes.json', json_encode($response->toArray()));
            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getMiraklListCustomFields()
    {
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            // \Mirakl\MMP\Shop\Request\AdditionalField\GetAdditionalFieldRequest
            $request = new GetAdditionalFieldRequest();
            $request->setLocale($this->shop->locale ?? 'es_ES');
            $response = $api->getAdditionalFields($request);    // AF01

            Storage::append($this->shop_dir. 'custom_fields/' .date('Y-m-d'). '_getMiraklListCustomFields.json', json_encode($response->toArray()));
            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getMiraklListShipments($marketOrderId)
    {
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            // \Mirakl\MMP\Shop\Request\AdditionalField\GetAdditionalFieldRequest
            $request = new GetShipmentsRequest();
            $request->setOrderIds([$marketOrderId]);
            $response = $api->getShipments($request);    // ST11

            Storage::append($this->shop_dir. 'custom_fields/' .date('Y-m-d'). '_getMiraklListCustomFields.json', json_encode($response->getCollection()->toArray()));
            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getMiraklOfferStateList()
    {
        // Worten: "code" => "11", "label" => "Nuevo"
        // Carrefour: ["code" => "11", "label" => "Nuevo"], ["code" => "10", "label" => "Renovado"]
        // PCCompo: "code" => "11", "label" => "Nuevo"
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new GetOfferStateListRequest();
            $response = $api->getOfferStateList($request); //OF61

            Storage::append($this->shop_dir. 'states/' .date('Y-m-d'). '_getMiraklOfferStateList.json', json_encode($response->toArray()));
            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getMiraklShippingZones()
    {
        // Worten: portugal | madeira | azores | spain | balearics
        // Carrefour: plusPeninsula | plusBaleares
        // PcCompo: espana-peninsular | baleares
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new GetShippingZonesRequest();
            $request->setLocale($this->shop->locale ?? 'es_ES');
            $response = $api->getShippingZones($request);    //SH11

            Storage::append($this->shop_dir. 'shipping/' .date('Y-m-d'). '_getMiraklShippingZones.json', json_encode($response->toArray()));
            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getMiraklLogisticClasses()
    {
        // Worten: smallnonheavy | verysmallnonheavy | midheavy | heavy | verylargeheavy | freedelivery | customlogisticclass
        // Carrefour: A B C D E F G H I J K L
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new GetLogisticClassRequest();
            $response = $api->getLogisticClasses($this->shop->locale ?? 'es_ES');    //SH31

            Storage::append($this->shop_dir. 'shipping/' .date('Y-m-d'). '_getMiraklLogisticClasses.json', json_encode($response->toArray()));
            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getMiraklCarriers()
    {
        try {
            $req = new GetShippingCarriersRequest();//new GetLogisticClassRequest(); //
            $req->setOptions([
                //'headers' => ['Accept' => 'application/json'],
                'locale' => $this->shop->locale ?? 'es_ES'
            ]);

            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $res = $api->getShippingCarriers($req);    //getLogisticClasses($req); //        // SH21 List all carriers
            Storage::put($this->shop_dir. 'carriers/' .date('Y-m-d'). '_getMiraklCarriers.json', json_encode($res));

            return $res;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    private function getMiraklDocuments()
    {
        try {
            $req = new GetDocumentsConfigurationRequest();
            $req->setOptions([
                //'headers' => ['Accept' => 'application/json'],
                'locale' => $this->shop->locale ?? 'es_ES'
            ]);

            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $res = $api->getDocumentsConfiguration($req);      // DO01 - List all document types
            Storage::put($this->shop_dir. 'docs/' .date('Y-m-d'). '_getMiraklDocuments.json', json_encode($res));

            return $res;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    private function getMiraklProductOffers($productIds)
    {
        // Util for Reprice
        try {
            $prices = [];
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new GetOffersOnProductsRequest();
            $request->setProductIds($productIds);
            $request->setAllOffers(false);  // false: Only active offers. true: All Offers
            $response = $api->getOffersOnProducts($request); //P11

            //Storage::append($this->shop_dir. 'states/' .date('Y-m-d'). '_getMiraklProductOffers.json', json_encode($response->toArray()));

            if ($response->count()) {
                foreach ($response->getItems() as $product_offers) {
                    $data = $product_offers->getData();
                    $product = $data['product']->getData();
                    $references = $product['references'];
                    $category = $product['category']->getData();

                    $ref = [];
                    foreach ($references as $reference) {
                        $ref[$reference->getType()] = $reference->getValue(); // MPN, EAN
                    }

                    $buybox = null;
                    $offers = $data['offers'];
                    foreach ($offers as $offer) {
                        // Get only best Offer Buy Box
                        $offer_data = $offer->getData();
                        $buybox = $offer_data['total_price'];    // price + min_shipping_price
                        if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $product['sku'])) {
                            if ($buybox && $shop_product->price != $buybox) break;
                        }
                        //break;
                    }

                    $prices[strval($product['sku'])] = [
                        'media_url'         => $data['product_media']['media_url'] ?? null,
                        'category_code'     => $category['code'] ?? null,
                        'marketProductSku'  => $product['sku'],
                        'references'        => $ref,    // EAN
                        'buybox'            => $buybox
                    ];
                }
            }

            Storage::append($this->shop_dir. 'prices/' .date('Y-m-d'). '_getMiraklProductOffers.json', json_encode($prices));

            /* foreach ($res->getItems() as $item) {
                $carrier = $item->getData();
                MarketCarrier::updateOrCreate([
                    'market_id'     => $this->market->id,
                    'code'          => $carrier['code'], */

            return $prices;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getShopProductsBestOffers()
    {
        try {
            $shop_products = $this->getShopProducts4Update();
            if (!$shop_products->count()) return 'No se han encontrado productos para analizar en esta Tienda';

            $prices = [];
            $chunks = $shop_products->chunk(100);
            foreach ($chunks as $chunk) {
                $productIds = $chunk->pluck('marketProductSku')->toArray();
                if ($res = $this->getMiraklProductOffers($productIds)) {
                    //$prices = array_merge($prices, $res);
                    $prices += $res;
                }

                //break;  // FAKE
            }

            return $prices;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }

    }


    private function getMiraklMessages()
    {
        // getCarriers
        try {
            $client = new Client(['base_uri' => $this->shop->endpoint]);
            $response = $client->get('/api/inbox/threads', [
                'headers' => [
                    'Authorization' => $this->shop->token,      //'Bearer ' .$this->shop->token,
                    //'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json'
                ],
                'query' => [
                    'shop_id'   => $this->shop->marketShopId,
                    'paginate'  => 'true',
                    'max'       => 100,
                    'offset'    => 0
                ]
            ]);

            // locale

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();

                Storage::append($this->shop_dir. 'messages/' .date('Y-m-d'). '_get.json', $contents);
                $json_res = json_decode($contents);


                // success
                if ($json_res->code == 0)
                    if (isset($json_res->data->merchant_id))
                        return true;
            }
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }



    private function getMiraklCategories($marketCategoryId)
    {
        // Worten
        // CVJ, Videojuegos y Gaming
        // ELC, Electrodomésticos
        // ESC, Oficina y Papelería
        // FOT, Fotografía y Vídeo
        // INF, Informática
        // JOG, Juguetes y juegos
        // PCS, Repuestos
        // SMH, Smart Home
        // TEL, Móviles y Smartphones
        // TVS, TV, Proyectores, Sonido y Reproductores Multimedia

        $res = [];
        try {
            $api = new ShopApiClientCatalog($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new GetHierarchiesRequest();
            $response = $api->getHierarchies($request);     // H11
            Storage::put($this->shop_dir. 'categories/all.json', json_encode($response->toArray()));

            foreach ($response->getItems() as $item) {

                $miraklCode = substr($item->getCode(), 0, 64);
                $miraklParentCode = substr($item->getParentCode(), 0, 64);
                foreach ($item->getLabelTranslations()->getItems() as $translation) {
                    if (in_array($translation->getLocale(), [$this->shop->locale ?? 'es_ES', 'es'])) {
                        $translation_value = $translation->getValue();

                        if ($item->getLevel() == 1 && !$this->all_categories_are_root) {

                            $res['roots'][] = $miraklCode;
                            $root_category = RootCategory::firstOrCreate([
                                'market_id'         => $this->market->id,
                                'name'              => $translation_value,
                                'marketCategoryId'  => $miraklCode,
                            ],[]);

                        }
                        else {
                            $parent = MarketCategory::where('marketCategoryId', $miraklParentCode)->get() ?? null;
                            $parent_id = $parent->count() ? $parent->pluck('id')->first() : null;

                            $root_category_id = null;
                            $root_category_name = null;
                            $root_category = RootCategory::where('marketCategoryId', $miraklParentCode)->get() ?? null;
                            if ($root_category->count()) {
                                $root_category_id = $root_category->first()->id;
                                $root_category_name = $root_category->first()->name;
                            }
                            elseif ($parent->count()) {
                                $root_category_id = $parent->first()->root_category_id;
                                $root_category_name = $parent->first()->root_category->name;
                            }

                            if (!isset($marketCategoryId) || $marketCategoryId == $root_category_name) {
                                $path = null;
                                if ($parent->count()) {
                                    $parent_first = $parent->first();
                                    $path = $parent_first->path. '/' .$parent_first->name;
                                }
                                elseif ($root_category->count()) {
                                    $path = $root_category->first()->name;
                                }

                                $res['categories'][] = $miraklCode;
                                MarketCategory::updateOrCreate(
                                    [
                                        'market_id'         => $this->market->id,
                                        'marketCategoryId'  => $miraklCode,
                                    ],
                                    [
                                        'name'              => $translation_value,
                                        'path'              => $path,
                                        'parent_id'         => $parent_id,
                                        'root_category_id'  => $root_category_id,
                                    ]
                                );
                            }
                        }

                        break;
                    }
                }
            }

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $marketCategoryId);
        }
    }


    private function getMiraklAttributes($refresh = true)
    {
        try {
            if ($refresh) {
                // GET ATTRIBUTES
                $api = new ShopApiClientCatalog($this->apiUrl, $this->apiKey, $this->shopId);
                $request = new GetAttributesRequest();
                $response = $api->getAttributes($request);
                Storage::put($this->shop_dir. 'attributes/all.json', json_encode($response->toArray()));
            }

            // CREATE ATTRIBUTES ARRAY
            $response_json = Storage::get($this->shop_dir. 'attributes/all.json');
            $response = json_decode($response_json);
            $attributes = [];
            foreach ($response as $attribute) {
                $attributes[] = $attribute;
            }

            return $attributes;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $refresh);
        }
    }


    private function getMiraklAttributesValues($refresh = true)
    {
        try {
            if ($refresh) {
                // GET ATTRIBUTE VALUES
                $api = new ShopApiClientCatalog($this->apiUrl, $this->apiKey, $this->shopId);
                $request = new GetValueListsItemsRequest();
                $request->setOptions(['locale' => $this->shop->locale ?? 'es_ES']);
                $response = $api->getValueLists($request);  // VL11
                Storage::put($this->shop_dir. 'attributes/allvalues.json', json_encode($response->toArray()));
            }

            // CREATE ATTRIBUTE VALUES ARRAY
            $response_json = Storage::get($this->shop_dir. 'attributes/allvalues.json');
            $response = json_decode($response_json);
            $attributes_values = [];
            foreach ($response->value_lists as $attribute_value) {
                $attributes_values[$attribute_value->code] = $attribute_value;
            }

            return $attributes_values;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $refresh);
        }
    }


    private function getMiraklProducts($product_references)
    {
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new GetProductsRequest($product_references);
            $request->setLocale($this->shop->locale ?? 'es_ES');
            $response = $api->getProducts($request);    //P31 -> Maximum 100 products

            Storage::append($this->shop_dir. 'products/' .date('Y-m-d'). '_postProductsMatching.json', json_encode($response->toArray()));
            return $response;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $product_references);
        }
    }


    private function getMiraklProductJob(MiraklObject $import_result, $job_id)
    {
        try {
            $reports = null;
            $api = new ShopApiClientCatalog($this->apiUrl, $this->apiKey, $this->shopId);
            if ($import_result->getErrorReport()) {
                $request = new DownloadProductImportErrorReportRequest($job_id);    // P44
                $file_wrapper = $api->downloadProductImportErrorReport($request);
                $reports['bad'] = $this->StorageFileWrapper($file_wrapper, 'ProductsImportError');
            }

            if ($import_result->getNewProductReport()) {
                $request = new DownloadProductImportNewProductsReportRequest($job_id);  // P45
                $file_wrapper = $api->downloadProductImportNewProductsReport($request);
                $this->StorageFileWrapper($file_wrapper);
                $reports['good'] = 'NewProductReport';
            }

            if ($import_result->getTransformedFile()) {
                $request = new DownloadProductImportTransformedFileRequest($job_id);    // P46
                $file_wrapper = $api->downloadProductImportTransformedFile($request);
                $this->StorageFileWrapper($file_wrapper);
                $reports['good'] = 'TransformedFile';
            }

            if ($import_result->getTransformationErrorReport()) {
                $request = new DownloadProductImportTransformationErrorReportRequest($job_id);  // P47
                $file_wrapper = $api->downloadProductImportTransformationErrorReport($request);
                $reports['bad'] = $this->StorageFileWrapper($file_wrapper, 'ProductsTransformationError');
            }

            return $reports;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$import_result, $job_id]);
        }
    }


    private function getMiraklOfferJob(OfferImportResult $import_result, $job_id)
    {
        try {
            $report = null;
            $file_wrappers = null;
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            if ($import_result->getErrorReport()) {
                $request = new OfferImportErrorReportRequest($job_id);   // OF03
                $file_wrapper = $api->getOffersImportErrorReport($request);
                $report = $this->StorageFileWrapper($file_wrapper, 'OffersImportError');
                $report['bad'] = true;

                $mps_skus_4_delete_online = [];
                if ($count_array = count($report[0]))
                    foreach ($report as $report_info) {
                        // First $report_info IS column names -> NO Numeric $report_info[$count_array - 2]
                        // error-line: $report_info[$count_array - 2]
                        // error-message: $report_info[$count_array - 1]
                        // The product linked to the new offer is different from the product linked to the existing offer.
                        //      mps_sku -> Online | ean -> local
                        // The product does not exist
                        //      mps_sku -> Online | ean -> local
                        if (isset($report_info[$count_array - 2]) && is_numeric($report_info[$count_array - 2])) {

                            $mps_sku = $report_info[0];     // Remove online
                            $ean = $report_info[1];         // marketProductSku = null

                            $mps_skus_4_delete_online[] = ['mps_sku'   => $mps_sku];
                            if ($shop_product = $this->shop->shop_products()
                                //->leftjoin('products', 'products.id', '=', 'shop_products.product_id')
                                ->whereMpsSku($mps_sku)->first()) {

                                $shop_product->marketProductSku = null;
                                $shop_product->mps_sku = $shop_product->product->getMPSSku();
                                $shop_product->save();
                            }

                            /* if ($shop_product = $this->shop->shop_products()->where('mps_sku', $report_info[0])->first()) {
                                $shop_product->marketProductSku = null;
                                $shop_product->mps_sku = $shop_product->product->getMPSSku();
                                //$shop_product->market_category_id = null;
                                $shop_product->save();
                            } */
                        }
                    }

                if (count($mps_skus_4_delete_online)) {
                    $offers = $this->buildRemoveItems($mps_skus_4_delete_online);
                    $res = $this->postMiraklOffers($offers);
                    $report['mps_skus_4_delete_online'] = $mps_skus_4_delete_online;
                    $report['delete_online_res'] = $res;
                }
            }
            else
                $report['good'] = true;

            return $report;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$job_id, $import_result, $report ?? null, $report_info ?? null]);
        }
    }


    private function getMiraklProductImportStatus(ShopJob $shop_job)
    {
        try {
            $api = new ShopApiClientCatalog($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new ProductImportStatusRequest($shop_job->jobId);        // P42
            $import_result = $api->getProductImportStatus($request);

            Storage::append($this->shop_dir. 'jobs/' .date('Y-m-d'). '_' .$shop_job->jobId. '.json', $import_result->toJSON());
            return $import_result;

        } catch (Throwable $th) {
            if ($th->getCode() == '404') {
                $shop_job->total_count = 0;
                $shop_job->save();
            }
            return $this->nullWithErrors($th, __METHOD__, $shop_job);
            // Import with identifier [$shop_job->jobId] not found
            /* if ($th->getCode() == '404') {
                return '404';
            } */
        }
    }


    private function getMiraklOfferImportStatus(ShopJob $shop_job)
    {
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
                $request = new OfferImportReportRequest($shop_job->jobId);  // OF02
                $import_result = $api->getOffersImportResult($request);

            Storage::append($this->shop_dir. 'jobs/' .date('Y-m-d'). '_' .$shop_job->jobId. '.json', $import_result->toJSON());
            return $import_result;

        } catch (Throwable $th) {
            if ($th->getCode() == '404') {
                $shop_job->total_count = 0;
                $shop_job->save();
            }
            return $this->nullWithErrors($th, __METHOD__, $shop_job);
            // Import with identifier [$shop_job->jobId] not found
            /* if ($th->getCode() == '404') {
                $shop_job->total_count = 0;
                $shop_job->save();
            } */
        }
    }


    private function getMiraklOrders($max, $offset, $days = 7)
    {
        try {
            $request = new GetOrdersRequest();
            $request->setPaginate(true);
            $request->setMax($max);
            $request->setOffset($offset);
            //$start_updated_date = now()->addDays($days)->toDateTime();
            //$request->setStartUpdateDate($start_updated_date);
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $response = $api->getOrders($request);      // OR11 return shopordercollection
            Storage::put($this->shop_dir. 'orders/' .date('Y-m-d '). '_' .$offset. '.json', json_encode($response->toArray()));

            return $response;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$max, $offset, $days]);
            //cURL error 7: Failed to connect to pccomponentes-prod.mirakl.net port 443: Connection timed out (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)
        }
    }


    private function getMiraklOffer($offerId)
    {
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new GetOfferRequest($offerId);

            return $api->getOffer($request);
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $offerId);
        }
    }


    private function getMiraklOffers()
    {
        $res = [];

        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $page = 0;
            $max = 100;
            $offset = 0;

            do {
                $request = new GetOffersRequest($this->shopId);
                $request->setPaginate(true);
                $request->setMax($max);
                $request->setOffset($offset);
                $response = $api->getOffers($request);

                $count = $response->getTotalCount();
                $page++;
                $offset = $page * $max;
                $res[] = $response->toArray();

            } while (($offset < $count) && ($offset < 10000));

            return $res;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function postMiraklOffers(UpdateOfferCollection $offers, $import_mode = 'REPLACE')
    {
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new UpdateOffersRequest();
            $request->setOffers($offers);
            $request->setData('import_mode', $import_mode);    // 'NORMAL' || 'PARTIAL_UPDATE' || 'REPLACE';

            $response = $api->updateOffers($request);    // OF24
            if ($response->getImportId()) {
                ShopJob::create([
                    'shop_id'       => $this->shop->id,
                    'jobId'         => $response->getImportId(),
                    'operation'     => 'OfferImport',
                ]);
            }

            Storage::append($this->shop_dir. 'offers/' .date('Y-m-d'). '_postMiraklOffers.json', $response->toJSON());
            return $response->toArray();

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $offers);
        }
    }


    private function postMiraklOffersByCsv(array $offers, $import_mode = 'REPLACE')
    {
        try {
            $count = 0;
            $job = null;

            $filename = storage_path('app/' .$this->shop_dir. 'offers/' .date('Y-m-d_H-i-s'). '_postMiraklOffersByCsv.csv');
            $fp = fopen($filename, 'w');

            // header_offer
            $header_rows = count($this->header_offer);
            fputcsv($fp, $this->header_offer[$header_rows - 1], ';');

            foreach ($offers as $offer) {
                fputcsv($fp, $offer, ';');
                $count++;
            }

            $api = new ShopApiClientCatalog($this->apiUrl, $this->apiKey, $this->shopId);
            $spl_file = new SplFileObject($filename);
            $request = new OfferImportRequest($spl_file);

            //$import_mode = (count($offers) > 1)
            //$request->setImportMode('NORMAL');  // 'NORMAL' || 'PARTIAL_UPDATE' || 'REPLACE';
            $request->setImportMode($import_mode);

            $response = $api->importOffers($request);   // OF01
            if ($response->getImportId()) {
                ShopJob::create([
                    'shop_id'       => $this->shop->id,
                    'jobId'         => $response->getImportId(),
                    'operation'     => 'OfferImport',
                ]);
            }

            $job = $response->getImportId();

            return ['products' => $count, 'job' => $job];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $offers);
        }
    }


    private function postMiraklProductsByCsv(Collection $shop_products)
    {
        try {
            $count = 0;
            $jobs = null;
            $product_items = $this->buildProductsByCsv($shop_products);
            foreach ($product_items as $marketCategoryId => $category_items) {

                $filename = storage_path('app/' .$this->shop_dir. 'products/' .date('Y-m-d_H-i-s'). '_postMiraklProductsByCsv_' .$marketCategoryId. '.csv');
                $fp = fopen($filename, 'w');
                fputcsv($fp, $category_items[0]['keys'], ';');
                foreach($category_items as $category_item) {
                    fputcsv($fp, $category_item['items_values'], ';');
                    $count++;
                }
                fclose($fp);

                $api = new ShopApiClientCatalog($this->apiUrl, $this->apiKey, $this->shopId);
                $spl_file = new \SplFileObject($filename);
                $request = new ProductImportRequest($spl_file);

                $response = $api->importProducts($request);     // P41
                if ($response->getImportId()) {
                    ShopJob::create([
                        'shop_id'       => $this->shop->id,
                        'jobId'         => $response->getImportId(),
                        'operation'     => 'ProductImport',
                    ]);
                }

                $jobs[] = $response->getImportId();
            }

            return ['products' => $count, 'jobs' => $jobs];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products);
        }
    }


    /* private function postMiraklAcceptOrderLine($marketOrderId, $marketItemId, $accepted = true)
    {
        try {
            $accept_order_lines = [];
            $accept_order_lines[] = new AcceptOrderLine(['id' => $marketItemId, 'accepted' => $accepted]);
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $req = new AcceptOrderRequest($marketOrderId, $accept_order_lines);
            $api->acceptOrder($req);  // OR21

            return true;
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$marketOrderId, $marketItemId, $accepted]);
        }
    } */


    private function postMiraklAcceptOrder(Order $order, $shipment_data)
    {
        // !isset($shipment_data) --> ACCEPT BY STOCK
        // $shipment_data['full'] == 1 --> ACCEPT
        // $shipment_data['full'] == 0 --> ABORT
        try {
            $accept_order_lines = [];
            foreach ($order->order_items as $order_item) {
                // ACCEPTED ALL || ITEM
                if (!isset($shipment_data)) {
                    if ($order_item->product->stock > 0)
                        $accept_order_lines[] = new AcceptOrderLine(['id' => $order_item->marketItemId, 'accepted' => true]);
                    else {
                        // BEFORE REFUSE THE ORDER ITEM -> SEARCH OTHER SUPPLIERS WITH SAME PRODUCT & SIMILAR PRICE
                        if ($similar_products = $order_item->product->getSimilarProducts()->where('stock', '>', 0)->sortBy('cost')) {
                            $first_cost_product = $similar_products->first();
                            if ($order_item->product->cost / $first_cost_product->cost > 0.95) {
                                $accept_order_lines[] = new AcceptOrderLine(['id' => $order_item->marketItemId, 'accepted' => true]);
                                // Change order item: product_id, MpsSku, cost, bfit, mps_bfit
                                $order_item->MpsSku = $first_cost_product->getMPSSku();
                                $order_item->cost = $first_cost_product->cost;
                                $order_item->product_id = $first_cost_product->id;
                                $order_item->bfit = 0;      // Change after manually
                                $order_item->mps_bfit = 0;  // Change after manually
                                $order_item->save();
                            }
                            else
                                $accept_order_lines[] = new AcceptOrderLine(['id' => $order_item->marketItemId, 'accepted' => false]);
                        }
                        else
                            $accept_order_lines[] = new AcceptOrderLine(['id' => $order_item->marketItemId, 'accepted' => false]);
                    }
                }
                elseif ($shipment_data['full']) {
                    // ACCEPTED ALL ITEMS || ACCEPTED ONLY ONE ITEM
                    //if (!isset($shipment_data['order_item_id']) || $shipment_data['order_item_id'] == $order_item->id)
                    $accept_order_lines[] = new AcceptOrderLine(['id' => $order_item->marketItemId, 'accepted' => true]);
                    //else DO NOTHING
                }
                // ABORT, NO ACCEPT ALL || ITEM
                else {
                    // ABORT, NO ACCEPT ALL ITEMS || ABORT, NO ACCEPT ONLY ONE ITEM
                    //if (!isset($shipment_data['order_item_id']) || $shipment_data['order_item_id'] == $order_item->id)
                    $accept_order_lines[] = new AcceptOrderLine(['id' => $order_item->marketItemId, 'accepted' => false]);
                    // else DO NOTHING
                }

                /* $accepted = ($shipment_data['full'] || $shipment_data['order_item_id'] == $order_item->id) ? true : false;
                $accept_order_lines[] = new AcceptOrderLine(['id' => $order_item->marketItemId, 'accepted' => $accepted]);*/
            }

            if (count($accept_order_lines)) {
                $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
                $req = new AcceptOrderRequest($order->marketOrderId, $accept_order_lines);
                $api->acceptOrder($req);  // OR21
                return true;
            }

            return json_encode(['status' => -1, 'message' => 'No order lines found.']);
        }
        catch (Throwable $th) {
            $order->shop->config = null;
            return $this->msgWithErrors($th, __METHOD__, [$order, $shipment_data]);
        }
    }


    private function postMiraklTrackings(Order $order, $shipment_data)
    {
        try {
            $market_carrier = MarketCarrier::find($shipment_data['market_carrier_id']);
            $req = new UpdateOrderTrackingInfoRequest($order->marketOrderId,
                [
                    'carrier_code'       => $market_carrier->code,
                    'carrier_name'       => $market_carrier->name,
                    'carrier_url'        => $market_carrier->url,
                    'tracking_number'    => $shipment_data['tracking'],
                ]);


            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $api->updateOrderTrackingInfo($req);  // OR23

            return true;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$order, $shipment_data]);
        }
    }


    private function postMiraklValidateShipment(Order $order)
    {
        try {
            $req = new ShipOrderRequest($order->marketOrderId);
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $api->shipOrder($req);  // OR24

            return true;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$order->marketOrderId, $order->shop->code]);
        }
    }



    /************** PRIVATE FUNCTIONS ***************/



    private function getMarketCategoryAttributes(MarketCategory $market_category, $all_attributes, $all_attributes_values)
    {
        try {
            // $mp_attribute->hierarchy_code == '' Categoria, SKU, Título, EAN, Marca, ...
            $mp_category_attributes = [];
            foreach ($all_attributes as $mp_attribute) {
                if ($mp_attribute->hierarchy_code != '') {

                    if (
                        substr($market_category->marketCategoryId, 0, 3) == $mp_attribute->hierarchy_code ||
                        substr($market_category->marketCategoryId, 0, 5) == $mp_attribute->hierarchy_code ||
                        $market_category->marketCategoryId == $mp_attribute->hierarchy_code
                    )
                        $mp_category_attributes[] = $mp_attribute;
                }
            }

            $filename = $market_category->marketCategoryId.'-'.str_replace(' ', '', $market_category->name);
            Storage::put($this->shop_dir. 'attributes/'.$filename.'.json', json_encode($mp_category_attributes));

            $this->saveMarketCategoryAttributes($market_category->id, $mp_category_attributes, $all_attributes_values);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$market_category, $all_attributes, $all_attributes_values]);
        }
    }


    private function getMatchings($product_references, Collection $ean_shop_products)
    {
        $matches = [];
        try {
            if ($response = $this->getMiraklProducts($product_references)) {
                foreach ($response as $mp_product) {

                    if ($ean_shop_product = $ean_shop_products->where('ean', $mp_product->getId())->first()) {
                        $ean_shop_product->marketProductSku = $mp_product->getSku();
                        $ean_shop_product->save();
                    }

                    $mp_product_id = $mp_product->getId();
                    if ($mp_product_id)
                        $matches[$mp_product_id] = $mp_product->getSku();
                }
            }

            return $matches;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $product_references);
        }
    }


    private function getProductsMatchings(Collection $shop_products)
    {
        try {
            $matches = [];
            $chunks = $shop_products->chunk(100);
            /* $chunks = $shop_products = $this->shop->shop_products()
                ->leftjoin('products', 'products.id', '=', 'shop_products.product_id')
                ->where('enabled', 1)
                ->where('products.stock', '>', 0)
                ->where('is_sku_child', false)
                ->where(function (Builder $query) {
                    return $query
                        ->whereNull('marketProductSku')
                        ->orWhere('marketProductSku', 'NO PRODUCT');
                })
                ->whereNotNull('products.ean')
                ->get()->chunk(100); */

            foreach ($chunks as $chunk) {

                $product_references = [];
                $ean_shop_products = new Collection();
                foreach ($chunk as $shop_product) {
                    if ($shop_product->product->ean) {
                        $product_references['EAN'][] = $shop_product->product->ean;
                        $ean_shop_products->add($shop_product);
                    }
                }

                if (count($product_references))
                    $matches += $this->getMatchings($product_references, $ean_shop_products);
            }

            return $matches;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_products);
        }
    }


    private function StorageFileWrapper(FileWrapper $file_wrapper, $report_type = null)
    {
        try {
            $report = [];
            $file_contents = $file_wrapper->getFile();
            $filename = storage_path('app/' .$this->shop_dir. 'jobs/' .date('Y-m-d_H-i-s'). '_' .$file_wrapper->getFileName());
            $fp = fopen($filename, 'w');
            while ($csv_array = $file_contents->fgetcsv( ';')) {
                fputcsv($fp, $csv_array, ';');

                if ($report_type == 'OffersImportError' || $report_type == 'ProductsImportError' || $report_type == 'ProductsTransformationError') {

                    $report[] = $csv_array;

                    /* $result_column = $report_type == 'OffersImportError' ? 30 : 26;
                    $report[$report_type][] = [
                        'mps_sku'   => $csv_array[0],
                        'mp_sku'    => $csv_array[1],
                        'result'    => $csv_array[$result_column],
                    ];

                    $this->shop->shop_products()
                        ->where('product_id', $this->getIdFromMPSSku($csv_array[0]))
                        //->whereNull('marketProductSku')
                        ->update(['marketProductSku' => 'ERROR']); */
                }
            }
            fclose($fp);

            return $report;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$file_wrapper, $report_type]);
        }
    }



    /************** PRIVATE FUNCTIONS - BUILDERS ***************/


    private function getOfferEan($offer)
    {
        try {
            // EAN, MPN
            foreach ($offer['product']['references'] as $reference)
                if ($reference['type'] == 'EAN')
                    return $reference['value'];

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $offer);
        }
    }


    private function buildItemRemove(array $remove_item)
    {
        try {
            $item = new UpdateOffer();
            $item->setUpdateDelete('delete');
            $item->setShopSku($remove_item['mps_sku']);
            if (isset($remove_item['ean'])) {
                $item->setProductId($remove_item['ean']);
                $item->setProductIdType('EAN');     // EAN | MPN
            }

            return $item;

            //$item->setDescription('');
            //$item->setInternalDescription('');
            //$item->setPrice(0);
            //$item->setQuantity(0);
            //$item->setStateCode($shop_product->product->status->marketStatusName);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $remove_item);
        }
    }


    private function buildRemoveItems(array $remove_items)
    {
        try {
            $offers = new UpdateOfferCollection();
            foreach ($remove_items as $remove_item)
                $offers->add($this->buildItemRemove($remove_item));

            return $offers;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $remove_item);
        }
    }


    private function buildOfferDiscount(ShopProduct $shop_product)
    {
        try {
            $discount = new Discount();

            $discount->setDiscountPrice($shop_product->param_discount_price);
            $discount->setStartDate($shop_product->param_starts_at->format('Y-m-d').'T00:00:00Z');
            $discount->setEndDate($shop_product->param_ends_at->format('Y-m-d').'T00:00:00Z');
            $discount->setPrice($shop_product->param_discount_price);       //$shop_product->price);
            $discount->setOriginPrice($shop_product->price);

            return $discount;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildOfferPricing($price, $stock, $channel = false, $ean = null)
    {
        try {
            $op = new OfferPricing();
            if ($channel) $op->setChannelCode($channel);

            $op->setPrice($price);
            $op->setUnitOriginPrice($price);

            $vpc = [];
            $vp = new VolumePrice();
            $vp->setPrice($price);
            $vp->setQuantityThreshold(1);
            $vp->setUnitOriginPrice($price);
            $vpc[] = $vp;

            /* if ($this->publish_packs->enabled) {
                foreach ($this->publish_packs->values as $pack_value) {
                    if ($pack_value <= $stock) {
                        $vp = new VolumePrice();
                        $volume_price = (float)$price - 3.0 + 3.0/(float)$pack_value;
                        $vp->setPrice($volume_price);
                        $vp->setQuantityThreshold($pack_value);
                        $vp->setUnitOriginPrice($price);
                        $vpc[] = $vp;
                    }
                }
            } */

            $op->setVolumePrices($vpc);

            return $op;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$price, $stock, $channel]);
        }
    }


    private function buildItemOffer(ShopProduct $shop_product, $only_stocks = false, $buybox_price = 0, $product_ids_today_orders = null)
    {
        // PCCompo
        // The mandatory additional field \"canon\" is missing
        // The mandatory additional field \"tipo-iva\" is missing

        // Worten
        // // The field value \"max-order-quantity\" must be greater than or equal to 1

        try {
            $shop_product->setPriceStock(null, $this->cost_is_price, $product_ids_today_orders);

            // STOP REPRICE ?
            if ($this->reprice && $shop_product->buybox_price != 0)
                $shop_product->setReprice();

            $item = new UpdateOffer();
            $item->setLeadtimeToShip($this->shop->preparation ?? 3);

            // Carrefour: A | Worten: freedelivery | PC Compo: (supplier_shippings) gratuito o libre
            $logistic_class = $this->shop->shipping;    //  is_array($this->logistic_classes) ? $this->logistic_classes[0] : $this->logistic_classes;
            if (!$logistic_class && $this->supplier_shippings)
                $logistic_class = $this->supplier_shippings[$shop_product->product->supplier_id] ?? $this->supplier_shippings[0];

            $item->setLogisticClass($logistic_class);


            $max_order_quantity = ($shop_product->stock == 0) ? 1 : $shop_product->stock;
            $item->setMaxOrderQuantity($max_order_quantity);
            $item->setMinOrderQuantity(1);

            // WORTEN
            // price	discount-price	discount-start-date discount-end-date
            // price[channel=WRT_ES_ONLINE]	discount-price[channel=WRT_ES_ONLINE]	discount-start-date[channel=WRT_ES_ONLINE]	discount-end-date[channel=WRT_ES_ONLINE]
            // price[channel=WRT_PT_ONLINE]	discount-price[channel=WRT_PT_ONLINE]	discount-start-date[channel=WRT_PT_ONLINE]	discount-end-date[channel=WRT_PT_ONLINE]
            // description-es	description-pt

            // CARREFOUR
            // price	discount-price	discount-start-date	discount-end-date

            // PCCOMPO
            // price	discount-price	discount-start-date	discount-end-date


            $item->setPrice($shop_product->price);

            // RegalaSexo NO Update Pricing -> ONLY WHEN UPDATE -> WHEN NEW PRODUCTS POST STOCKS & PRICES
            if (!$only_stocks) {

                $offer_princings = [];
                if (isset($this->channels)) {

                    if ($this->shop->market->code == 'worten')
                        $this->channels = $this->supplier_shippings[$shop_product->product->supplier_id] ??
                            $this->supplier_shippings[0] ?? ["WRT_ES_ONLINE", "WRT_PT_ONLINE"];

                    foreach ($this->channels as $channel)
                        $offer_princings[] = $this->buildOfferPricing($shop_product->price,
                            $shop_product->enabled ? $shop_product->stock : 0,
                            $channel,
                            $shop_product->product->ean);
                }
                else
                    $offer_princings[] = $this->buildOfferPricing($shop_product->price,
                        $shop_product->enabled ? $shop_product->stock : 0,
                        false,
                        $shop_product->product->ean);

                $prices = new UpdateOfferPricesCollection();
                $prices->setItems($offer_princings);
                $item->setAllPrices($prices);

                if ($shop_product->param_discount_price != 0 &&
                    $shop_product->param_starts_at &&
                    $shop_product->param_ends_at &&
                    $shop_product->param_starts_at->lte(now())
                    && $shop_product->param_ends_at->gte(now())) {

                    $item->setDiscount($this->buildOfferDiscount($shop_product));
                }
            }

            $item->setProductId($shop_product->product->ean);
            // EAN($mp_product->getId()) Or SHOP_SKU($mp_product->getShopSku()) Or SKU($mp_product->getSku()) ???
            $item->setProductIdType('EAN');
            $item->setQuantity($shop_product->enabled ? $shop_product->stock : 0);
            $item->setShopSku($shop_product->mps_sku);      //$shop_product->getMPSSku());

            switch ($shop_product->product->status->marketStatusName) {
                case 'New':
                    $item->setDescription($this->offer_desc);
                    $item->setInternalDescription($this->offer_desc);
                    break;

                case 'Used':
                    $item->setDescription($this->offer_desc_used);
                    $item->setInternalDescription($this->offer_desc_used);
                    $item->setPriceAdditionalInfo($this->offer_desc_used);
                    break;

                case 'Refurbished':
                    $item->setDescription($this->offer_desc_refurbished);
                    $item->setInternalDescription($this->offer_desc_refurbished);
                    $item->setPriceAdditionalInfo($this->offer_desc_used);
                    break;
            }

            $state = $this->state_codes->{$shop_product->product->status->marketStatusName} ?? '11';
            $item->setStateCode($state);      // 11 -> New, 13->Refurbished Grade A, 14->Refurbished Grade B
            $item->setUpdateDelete('');     // 'delete' | ''

            return $item;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_product, $only_stocks]);
        }
    }


    private function buildOffers(Collection $shop_products, $only_stocks = false)
    {
        try {
            $offers = new UpdateOfferCollection();
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            foreach ($shop_products as $shop_product) {

                if ($shop_product->isUpgradeable() && $item = $this->buildItemOffer($shop_product, $only_stocks, null, $product_ids_today_orders)) {
                    $offers->add($item);
                }
                elseif (!isset($shop_product->marketProductSku)) {
                    $shop_product->marketProductSku = 'NO PRODUCT';
                    $shop_product->save();
                }
            }

            Storage::append($this->shop_dir. 'offers/' .date('Y-m-d_H-i'). '_buildOffers.json', json_encode($offers->toArray()));

            return $offers;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_products, $only_stocks]);
        }
    }


    private function buildOffersByCsv(Collection $shop_products, $only_stocks = false)
    {
        try {
            $offers = [];
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            foreach ($shop_products as $shop_product) {

                if ($shop_product->isUpgradeable() && $item = $this->getItemRowOffer($shop_product,
                        ['only_stocks' => $only_stocks, null], $product_ids_today_orders)) {
                    $offers[] = $item;
                }
                elseif (!isset($shop_product->marketProductSku)) {
                    $shop_product->marketProductSku = 'NO PRODUCT';
                    $shop_product->save();
                }
            }

            return $offers;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_products, $only_stocks]);
        }
    }


    private function buildPropertyFeed(AttributeMarketAttribute $attribute_market_attribute,
                                       Property $property,
                                       $value)
    {
        try {

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

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$attribute_market_attribute, $property, $value]);
        }
    }


    private function buildPropertyFeedByField(AttributeMarketAttribute $attribute_market_attribute,
                                              Property $property,
                                              $field_value)
    {
        try {
            $property_feed = null;
            $value = is_object($field_value) ? $field_value->name : $field_value;
            $property_feed = $this->buildPropertyFeed($attribute_market_attribute, $property, $value);

            return $property_feed;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$attribute_market_attribute, $property, $value]);
        }
    }


    private function builtItemAttributesFeed(ShopProduct $shop_product)
    {
        try {
            if (!$market_category = $shop_product->market_category) return [];

            $item_attributes = null;
            $market_attributes = $shop_product->market_category->market_attributes('type_category')->get();
            foreach ($market_attributes as $market_attribute) {

                $property_feed = null;
                foreach ($market_attribute->attribute_market_attributes as $attribute_market_attribute) {
                    $property = $attribute_market_attribute->property;

                    // ATTRIBUTE FIXED
                    if ($attribute_market_attribute->fixed && $attribute_market_attribute->fixed_value) {
                        $property_feed = $attribute_market_attribute->fixed_value;
                    }
                    // PRODUCT FIELD
                    elseif ($product_field = $attribute_market_attribute->field)
                        $property_feed = $this->buildPropertyFeedByField($attribute_market_attribute, $property, $shop_product->product->{$product_field});
                    // PRODUCT ATTRIBUTE
                    elseif ($attribute_market_attribute->attribute_id) {
                        $attribute = $attribute_market_attribute->attribute;
                        $product_attributes = $shop_product->product->product_attributes->where('attribute_id', $attribute->id);

                        if ($product_attribute = $product_attributes->first()) {
                            $property_feed = $this->buildPropertyFeed($attribute_market_attribute, $property, $product_attribute->value);
                        }
                    }
                }

                if ($property_feed) $item_attributes[$market_attribute->code] = $property_feed;
                else $item_attributes[$market_attribute->code] = '';
            }

            return $item_attributes;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildItemProductByCsv(ShopProduct $shop_product)
    {
        try {
            $images = $shop_product->product->getAllUrlImages(5)->toArray();
            $market_category = $shop_product->market_category;
            $item = [
                'mp_category'               => $market_category ? $market_category->marketCategoryId : '',
                'product_id'                => $shop_product->mps_sku,  //$shop_product->getMPSSku(),
                'product_name_pt_PT'        => FacadesMpe::buildString($shop_product->buildTitle()),
                'product_name_es_ES'        => FacadesMpe::buildString($shop_product->buildTitle()),
                'ean'                       => $shop_product->product->ean,
                'image1'                    => $images[0],
                'product_description_es_ES' => FacadesMpe::buildText($shop_product->buildDescription4Mobile(2000)),
                'product_description_pt_PT' => FacadesMpe::buildText($shop_product->buildDescription4Mobile(2000)),
                'image2'                    => $images[1] ?? '',
                'image3'                    => $images[2] ?? '',
                'image4'                    => $images[3] ?? '',
                'image5'                    => $images[4] ?? '',
            ];

            $item_attributes = $this->builtItemAttributesFeed($shop_product);
            $item_attributes = $item_attributes ?? [];

            return array_merge($item, $item_attributes);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    private function buildProductsByCsv(Collection $shop_products)
    {
        try {
            $product_items = null;
            foreach ($shop_products as $shop_product) {
                if ($shop_product->product->images->count() && !isset($shop_product->marketProductSku)) {

                    $item = $this->buildItemProductByCsv($shop_product);
                    $product_items[$shop_product->market_category->marketCategoryId][] = [
                        'keys'          => array_keys($item),
                        'items_values'  => array_values($item)
                    ];
                    $shop_product->update(['marketProductSku' => 'NO PRODUCT']);
                }
            }

            return $product_items;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $shop_product);
        }
    }


    /************** PRIVATE FUNCTIONS ***************/


    private function saveMarketCategoryAttributes($market_category_id, $mp_category_attributes, $all_attributes_values)
    {
        try {
            foreach ($mp_category_attributes as $mp_attribute) {

                $type = Type::firstOrCreate(
                    [
                        'market_id' => $this->market->id,
                        'name' => ($mp_attribute->variant == false) ? 'type_category' : 'type_sku',
                        'type' => 'market_attribute',
                    ],
                    []
                );

                $market_attribute = MarketAttribute::firstOrCreate(
                    [
                        'market_id' => $this->market->id,
                        'market_category_id' => $market_category_id,
                        'type_id' => $type->id,
                        'name' => $mp_attribute->description ?? $mp_attribute->label_translations[0]->value,
                        'code' => $mp_attribute->code,
                    ],
                    [
                        'datatype' => $mp_attribute->type,    // TEXT | LIST
                        'required' => ($mp_attribute->requirement_level == 'REQUIRED'), // OPTIONAL, REQUIRED, RECOMMENDED, DISABLED
                    ]
                );

                $attribute_property = Property::firstOrCreate(
                    [
                        'market_attribute_id' => $market_attribute->id,
                        'name' => null,
                    ],
                    [
                        'datatype' => $mp_attribute->type,    // TEXT | LIST
                        'required' => ($mp_attribute->requirement_level == 'REQUIRED'), // OPTIONAL, REQUIRED, RECOMMENDED, DISABLED
                        'custom' => !isset($mp_attribute->type_parameter),
                        'custom_value' => null,
                        'custom_value_field' => null,
                    ]
                );

                if (isset($mp_attribute->type_parameter) && isset($all_attributes_values[$mp_attribute->type_parameter])) {
                    foreach ($all_attributes_values[$mp_attribute->type_parameter]->items as $item_value) {
                        PropertyValue::firstOrCreate(
                            [
                                'property_id' => $attribute_property->id,
                                'name' => isset($item_value->label_translations[0]) ? $item_value->label_translations[0]->value : $item_value->label,
                                'value' => $item_value->code,
                            ],
                            []
                        );
                    }
                }
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$market_category_id, $mp_category_attributes, $all_attributes_values]);
        }

    }


    private function postOffers(Collection $shop_products, $import_mode = 'REPLACE', $only_stocks = false)
    {
        try {
            $res = [];

            if ($this->csv) {
                if ($offers = $this->buildOffersByCsv($shop_products, $only_stocks))
                    if (count($offers)) $res = $this->postMiraklOffersByCsv($offers, $import_mode);
            }
            else {
                if ($offers = $this->buildOffers($shop_products, $only_stocks)) {
                    if ($offers->count()) $res = $this->postMiraklOffers($offers, $import_mode);
                }
            }

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_products, $only_stocks]);
        }
    }


    private function getJobRequest(ShopJob $shop_job)
    {
        try {
            $job_result = null;
            $job_result[$shop_job->jobId]['operation'] = $shop_job->operation;

            if ($shop_job->operation == 'ProductImport') {

                $import_result = $this->getMiraklProductImportStatus($shop_job);
                if (!$import_result) return false;
                if ($import_result == '404') {
                    $shop_job->total_count = 0;
                    $shop_job->save();
                }

                $job_status = $import_result->getImportStatus();
                // TRANSFORMATION_WAITING, TRANSFORMATION_QUEUED, TRANSFORMATION_RUNNING, TRANSFORMATION_FAILED,
                // WAITING, QUEUED, RUNNING, SENT, COMPLETE, CANCELLED, FAILED
                $job_result[$shop_job->jobId]['status'] = $job_status;

                if ($job_status == 'COMPLETE') {

                    $job_result[$shop_job->jobId]['total_count'] = $import_result->getTransformedLinesRead();
                    $job_result[$shop_job->jobId]['success_count'] = $import_result->getTransformedLinesInSuccess();
                    $job_result[$shop_job->jobId]['errors_count'] = $import_result->getTransformedLinesInError();
                    $job_result[$shop_job->jobId]['warnings_count'] = $import_result->getTransformedLinesWithWarning();

                    // Update Job
                    $shop_job->total_count = $import_result->getTransformedLinesRead();
                    $shop_job->success_count = $import_result->getTransformedLinesInSuccess();
                    $shop_job->save();

                    $report = $this->getMiraklProductJob($import_result, $shop_job->jobId);
                    $job_result[$shop_job->jobId]['report'] = $report;
                }
            }
            // Create, update || delete Offers
            elseif ($shop_job->operation == 'OfferImport') {

                $import_result = $this->getMiraklOfferImportStatus($shop_job);
                if (!$import_result) return false;

                $job_status = $import_result->getStatus();
                // WAITING_SYNCHRONIZATION_PRODUCT, WAITING, RUNNING, COMPLETE, FAILED, QUEUED
                $job_result[$shop_job->jobId]['status'] = $job_status;

                if ($job_status == 'COMPLETE') {

                    //$count_errors = $offer_import_result->getLinesInError();
                    $job_result[$shop_job->jobId]['total_count'] = $import_result->getLinesRead();
                    $job_result[$shop_job->jobId]['success_count'] = $import_result->getLinesInSuccess();
                    $job_result[$shop_job->jobId]['deleted'] = $import_result->getOfferDeleted();
                    $job_result[$shop_job->jobId]['inserted'] = $import_result->getOfferInserted();
                    $job_result[$shop_job->jobId]['updated'] = $import_result->getOfferUpdated();

                    // Update Job
                    $shop_job->total_count = $import_result->getLinesRead();
                    $shop_job->success_count = $import_result->getLinesInSuccess();
                    $shop_job->save();

                    $report = $this->getMiraklOfferJob($import_result, $shop_job->jobId);
                    $job_result[$shop_job->jobId]['report'] = $report;
                }
            }

            Storage::append($this->shop_dir. 'jobs/' .date('Y-m-d'). '_getJobRequest.json', json_encode([$shop_job, $job_result]));
            return $job_result;

        } catch (Throwable $th) {
            // Import with identifier [$shop_job->jobId] not found
            if ($th->getCode() == '404') {
                $shop_job->total_count = 0;
                $shop_job->save();
            }
            return $this->nullWithErrors($th, __METHOD__, $shop_job);
        }
    }


    private function firstOrCreateAddress(ShopOrderCustomer $customer, $type = 'shipping')
    {
        try {
            $mp_address = ($type == 'shipping') ? $customer->getShippingAddress() : $customer->getBillingAddress();
            if (!$mp_address) $mp_address = $customer;

            $country = null;
            $country_name = $mp_address->getCountry();
            if (strlen($country_name) == 2)
                $country = Country::firstOrCreate([
                    'code'      => $country_name,
                ],[
                    'name'      => $country_name,
                ]);
            elseif (strlen($country_name) > 2)
                $country = Country::firstOrCreate([
                    'name'      => $country_name,
                ],[
                    'code'      => $country_name,
                ]);

            // id, country_id, fixed, name, address1, address2, address3, city, municipality, district, state, zipcode, phone
            $name = $mp_address->getFirstname(). ' ' .$mp_address->getLastname();
            $name = (!empty($mp_address->getCompany())) ? '(' .$mp_address->getCompany(). ') ' .$name : $name;

            $address = Address::updateOrCreate([
                'country_id'            => $country->id ?? null,
                'market_id'             => $this->market->id,
                'name'                  => $name,
                'marketBuyerId'         => $customer->getCustomerId(),
                'address1'              => $mp_address->getStreet1(),
                'type'                  => $type
            ],[
                'address2'              => $mp_address->getStreet2(),
                'city'                  => $mp_address->getCity(),
                'state'                 => $mp_address->getState(),
                'zipcode'               => $mp_address->getZipCode(),
                'phone'                 => $mp_address->getPhone(),
            ]);

            return $address;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$customer, $type]);
        }
    }


    private function updateOrCreateOrder(ShopOrder $mp_order)
    {
        try {
            $customer = $mp_order->getCustomer();

            $shipping_address = $this->firstOrCreateAddress($customer, 'shipping');
            $billing_address = $this->firstOrCreateAddress($customer, 'billing');

            // id, shipping_address_id, billing_address_id, name, email, phone, company_name, tax_region, tax_name, tax_value
            // market_id, marketBuyerId
            $buyer = Buyer::updateOrCreate([
                'market_id'             => $this->market->id,
                'marketBuyerId'         => $mp_order->getCustomer()->getCustomerId(),
            ],[
                // ES shopper OR Real name
                'name'                  => $mp_order->getCustomer()->getFirstname(). ' ' .$mp_order->getCustomer()->getLastname(),
                'shipping_address_id'   => $shipping_address->id ?? null,
                'billing_address_id'    => $billing_address->id ?? null,
                'email'                 => null,
                'phone'                 => $shipping_address->phone ?? null,
                'company_name'          => ($customer->getBillingAddress() !== null) ? $customer->getBillingAddress()->getCompany() : null,
                'tax_region'            => null,
                'tax_name'              => null,
                'tax_value'             => null,
            ]);

            // id, supplier_id, market_id, name, supplierStatusName, marketStatusName, type
            $status = Status::firstOrCreate([
                'market_id'             => $this->market->id,
                'marketStatusName'      => $mp_order->getStatus()->getReason() ? $mp_order->getStatus()->getReason()->getCode() : $mp_order->getStatus()->getState(),
                'type'                  => 'order',
            ],[
                'name'                  => $mp_order->getStatus()->getReason() ? $mp_order->getStatus()->getReason()->getCode() : $mp_order->getStatus()->getState(),
            ]);

            $currency = Currency::firstOrCreate([
                'code'             => $mp_order->getCurrencyIsoCode(),
            ],[]);

            //  Order::whereMarketId($this->market->id)->whereShopId($this->shop->id)->where('marketOrderId', $mp_order->getId())->first();
            $order = $this->shop->orders()->where('marketOrderId', $mp_order->getId())->first();
            $notified = (!isset($order) && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;
            $notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, $this->order_status_ignored)) ? false : true;

            Storage::append($this->shop_dir. 'orders/' .date('Y-m-d'). '_CONTROL_NOTIFICATIONS.json',
                json_encode([
                    'isset_order'       => isset($order),
                    'order_status_id'   => isset($order) ? $order->status_id : null,
                    'new_status_id'     => $status->id,
                    'in_array_ignored'  => in_array($status->marketStatusName, $this->order_status_ignored),
                    'notified'          => $notified,
                    'notified_updated'  => $notified_updated,
                    ])
            );

            $order_tax_mode = $mp_order->getOrderTaxMode();
            $price = $mp_order->getTotalPrice();
            $shipping_price = $mp_order->getShipping()->getPrice();
            if ($order_tax_mode == 'TAX_EXCLUDED') {
                $price = $price * (1 + $this->tax_rate / 100);
                $shipping_price = $shipping_price * (1 + $this->tax_rate / 100);
            }

            $order = Order::updateOrCreate([
                'market_id'             => $this->market->id,
                'shop_id'               => $this->shop->id,
                'marketOrderId'         => $mp_order->getId(),
            ],[
                'buyer_id'              => $buyer->id ?? null,
                'shipping_address_id'   => $shipping_address->id ?? null,
                'billing_address_id'    => $billing_address->id ?? null,
                'currency_id'           => $currency->id,
                'status_id'             => $status->id,
                'type_id'               => null,
                'SellerId'              => $mp_order->getCustomer()->getCustomerId(),
                'SellerOrderId'         => null,
                'info'                  => $mp_order->getCommercialId(). ' ' .$mp_order->getPaymentType(). ' '
                    .$mp_order->getPaymentWorkflow(). ' ' . $mp_order->getQuoteId(),
                'price'                 => $price,
                'tax'                   => $this->tax_rate,
                'shipping_price'        => $shipping_price,
                'shipping_tax'          => $this->tax_rate,
                'notified'              => $notified,
                'notified_updated'      => $notified_updated,
            ]);

            $order->created_at = Carbon::instance($mp_order->getCreatedDate())->addHours(1)->format('Y-m-d H:i:s');
            $order->updated_at = Carbon::instance($mp_order->getLastUpdatedDate())->addHours(1)->format('Y-m-d H:i:s');
            $order->save();

            foreach ($mp_order->getOrderLines() as $mp_order_item) {

                if (!$mp_order_item->getOffer()) {
                    $this->nullAndStorage(__METHOD__, $mp_order);
                    continue;
                }

                $item_tax_rate = $shipping_tax_rate = $this->tax_rate;
                $item_price = $mp_order_item->getTotalPrice() ?? 0;
                $item_shipping_price = $mp_order_item->getShippingPrice() ?? 0;
                $commission = $mp_order_item->getCommission()->getFee() ?? 0;
                if (in_array($this->market->code, ['carrefour', 'pccompo'])) $commission *= 1.21;

                if ($order_tax_mode == 'TAX_EXCLUDED') {
                    $tax_amount = 0;
                    $taxes = $mp_order_item->getTaxes();
                    foreach ($taxes as $tax) {
                        $tax_amount += $tax->getAmount();
                    }
                    if ($item_price) $item_tax_rate = 100 * ($tax_amount / $item_price);
                    $item_price += $tax_amount;

                    $tax_amount = 0;
                    $shipping_taxes = $mp_order_item->getShippingTaxes();
                    foreach ($shipping_taxes as $shipping_tax) {
                        $tax_amount += $shipping_tax->getAmount();
                    }
                    if ($item_shipping_price) $shipping_tax_rate = 100 * ($tax_amount / $item_shipping_price);
                    $item_shipping_price += $tax_amount;

                    //$item_price = $item_price * (1 + $this->tax_rate / 100);
                    //$item_shipping_price = $item_shipping_price * (1 + $this->tax_rate / 100);
                }

                $order_item = $order->updateOrCreateOrderItem(
                    $mp_order_item->getId(),
                    $mp_order_item->getOffer()->getSku(),
                    $mp_order_item->getOffer()->getProduct()->getSku(),
                    $mp_order_item->getOffer()->getProduct()->getTitle(),
                    $mp_order_item->getQuantity(),

                    $item_price,
                    $item_tax_rate,        //$mp_order_item->getTaxes()->first() ? $mp_order_item->getTaxes()->first()->getAmount() : 0,
                    $item_shipping_price,   //$mp_order_item->getShippingPrice() ?? 0,
                    $shipping_tax_rate,        //$mp_order_item->getShippingTaxes()->first() ? $mp_order_item->getShippingTaxes()->first()->getAmount() : 0,
                    null,
                    ['mp_bfit' => $commission]
                );
            }

            if (in_array($status->marketStatusName, $this->order_status_auto_response)) {
                $this->postMiraklAcceptOrder($order, null);


                /* $shipment_data = [];
                foreach ($order->order_items as $order_item) {

                    if ($order_item->product->stock > 0) {
                        // ACCEPT ITEM
                        $this->postMiraklAcceptOrderLine($order->marketOrderId, $order_item->marketItemId, true);
                    }
                    else {
                        // NO ACCEPT ITEM
                        $this->postMiraklAcceptOrderLine($order->marketOrderId, $order_item->marketItemId, false);
                    }
                }*/
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $mp_order);
        }
    }


    private function updateOrCreateOrders(ShopOrderCollection $response)
    {
        try {
            $count_orders = 0;
            foreach ($response->getItems() as $mp_order) {

                $this->updateOrCreateOrder($mp_order);
                $count_orders++;
            }

            return $count_orders;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $response);
        }
    }


    /* private function deleteStorage4Remove()
    {
        try {
            $collection_4_remove = $this->shop->getStorage4Remove();
            if (isset($collection_4_remove) && $collection_4_remove->count()) {

                $items = $collection_4_remove->map(function($item){
                    return Arr::only($item, ['marketProductSku', 'ean']);
                });

                $offers = $this->buildRemoveItems($items->toArray());

                return $this->postMiraklOffers($offers);
            }

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$collection_4_remove ?? null, $this]);
        }
    } */


    /************** PUBLIC FUNCTIONS - GETTERS DEV IN DESCENDANT ***************/


    public function getItemRowProduct(ShopProduct $shop_product, $product_ids_today_orders = null)
    {
        // Develop in descendant
    }


    public function getItemRowOffer(ShopProduct $shop_product, $extra_data = ['only_stocks' => false, 'buybox_price' => null], $product_ids_today_orders)
    {
        // Develop in descendant
    }


    public function getItemRowPromo(ShopProduct $shop_product, $extra_data)
    {
        // Develop in descendant
    }


    /************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getBrands()
    {
        return 'Mirakl no tiene marcas.';
    }


    public function getCategories($marketCategoryId = null)
    {
        return $this->getMiraklCategories($marketCategoryId);
    }


    public function getAttributes(Collection $market_categories)
    {
        try {
            $all_attributes = $this->getMiraklAttributes(true);
            $all_attributes_values = $this->getMiraklAttributesValues(true);

            foreach ($market_categories as $market_category) {
                $this->getMarketCategoryAttributes($market_category, $all_attributes, $all_attributes_values);
            }

            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $market_categories);
        }
    }


    public function getFeed(ShopProduct $shop_product)
    {
        dd($this->buildItemProductByCsv($shop_product),
            $this->buildItemOffer($shop_product),
            $this->getItemRowOffer($shop_product, ['only_stocks' => false, null], []));


    }


    public function getJobs()
    {
        try {

            // GET JOBS
            $jobs_result = [];
            $shop_jobs = $this->shop->shop_jobs()->whereNull('total_count')->get();
            $jobs_result['jobs_count'] = $shop_jobs->count();
            foreach ($shop_jobs as $shop_job)  {

                $job_result = $this->getJobRequest($shop_job);
                $jobs_result['jobs'][] = $job_result;
            }

            $this->syncCategories();

            return $jobs_result;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function getOrders()
    {
        try {
            $page = 0;
            $max = 100;
            $offset = 0;
            $count = 0;
            $responses = [];
            $count_orders = 0;
            do {
                if ($response = $this->getMiraklOrders($max, $offset, 14)) {
                    $count_orders += $this->updateOrCreateOrders($response);
                    $count = $response->getTotalCount();
                    $page++;
                    $offset = $page * $max;
                    $responses[] = $response;
                }

            } while ($offset < $count && $offset < 10000 && $response);

            return $count_orders;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function getGroups()
    {
        return 'Mirakl no tiene grupos de categorías.';
    }


    public function getCarriers()
    {
        try {
            $res = $this->getMiraklCarriers();
            if (!is_string($res)) {
                foreach ($res->getItems() as $item) {
                    $carrier = $item->getData();
                    MarketCarrier::updateOrCreate([
                        'market_id'     => $this->market->id,
                        'code'          => $carrier['code'],
                    ], [
                        'name'          => $carrier['label'],
                        'url'           => $carrier['tracking_url'] ?? null,
                    ]);
                }

                return true;
            }

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    public function getOrderComments(Order $order)
    {
        return 'Mirakl no tiene comentarios de pedidos.';
    }


    public function getMessages()
    {
        return $this->getMiraklMessages();
        /*if (!is_string($res)) {

            foreach ($res->getItems() as $item) {
                $carrier = $item->getData();
                MarketCarrier::updateOrCreate([
                    'market_id'     => $this->market->id,
                    'code'          => $carrier['code'],
                ], [
                    'name'          => $carrier['label'],
                    'url'           => $carrier['tracking_url'],
                ]);
            }

            return true;
        }*/

    }


    public function getPayments()
    {
        //$res = $this->getMiraklListAccounting();
        try {
            $count = 0;
            $info = [];
            $page_token = null;
            do {
                $json_res = $this->getMiraklListTransactions($page_token);
                $this->getTransactionInfo($info, $json_res);

                $page_token = $json_res->next_page_token ?? null;
                $count++;

                Storage::append($this->shop_dir. 'transactions/' .date('Y-m-d'). '_tokens.json', json_encode([$count, $page_token]));

            } while (isset($page_token) && $count < 100);

            Storage::append($this->shop_dir. 'transactions/' .date('Y-m-d'). '_info.json', json_encode($info));

            $count_payments = 0;
            if (count($info) && isset($info['ORDER'])) {
                foreach ($info['ORDER'] as $marketOrderId => $order_items_info) {
                    foreach ($order_items_info as $marketItemId => $payment_info) {

                        /* $order = $this->shop->orders()->firstWhere('marketOrderId', $marketOrderId);
                        $order_item = $order->order_items()->firstWhere('marketItemId', $marketItemId);
                        $order_payment = $order_item->order_payments()->first();
                         */

                        if ($order = $this->shop->orders()->firstWhere('marketOrderId', $marketOrderId))
                            if ($order_item = $order->order_items()->firstWhere('marketItemId', $marketItemId))
                                if ($order_payment = $order_item->order_payments()->first()) {

                                    if (in_array($this->market->code, ['carrefour', 'pccompo'])) $payment_info['mp_bfit'] *= 1.21;

                                    $order_payment->fixed = ($payment_info['charget'] == true) ? 1 : 0;
                                    $order_payment->charget = ($payment_info['charget'] == true) ? 1 : 0;
                                    $order_payment->price = $payment_info['price'] ?? $order_payment->price;
                                    $order_payment->shipping_price = $payment_info['shipping_price'] ?? $order_payment->shipping_price;
                                    $order_payment->mp_bfit = $payment_info['mp_bfit'] ?? $order_payment->mp_bfit;
                                    $order_payment->invoice = $payment_info['invoice'] ?? $order_payment->invoice;
                                    $order_payment->payment_at = $payment_info['updated'] ?? $order_payment->payment_at;
                                    $order_payment->save();

                                    $count_payments++;
                                }
                    }
                }
            }

            return 'Actualizados '.$count_payments. ' pagos.';

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }



    /************ PUBLIC FUNCTIONS - POSTS *******************/


    public function postNewProduct(ShopProduct $shop_product)
    {
        try {
            $res = null;
            $shop_products = new Collection([$shop_product]);
            $this->getProductsMatchings($shop_products);

            $res[] = $this->postOffers($shop_products);

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function postUpdatedProduct(ShopProduct $shop_product)
    {
        return 'Mirakl no tiene actualización completa de un producto.';
    }


    public function postPriceProduct(ShopProduct $shop_product)
    {
        try {
            $shop_products = new Collection([$shop_product]);

            // // 'NORMAL' || 'PARTIAL_UPDATE' || 'REPLACE';
            return $this->postOffers($shop_products, 'PARTIAL_UPDATE', $this->only_stocks);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_product);
        }
    }


    public function postNewProducts($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Create($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos nuevos en esta Tienda';

            $res = null;
            $this->getProductsMatchings($shop_products);

            $shop_products = $this->getShopProducts4Update();
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            $res[] = $this->postOffers($shop_products);

            if ($null_shop_products = $this->shop->shop_products()->whereNull('marketProductSku')->get())
                foreach ($null_shop_products as $null_shop_product) {
                    if ($null_shop_product->stock == 0)
                        $null_shop_product->deleteSecure();
                    else {
                        $null_shop_product->marketProductSku = 'NO PRODUCT';
                        $null_shop_product->save();
                    }
                }

            return $res ?? 'No hay ofertas pendientes o no hay fichas';

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postUpdatedProducts($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            return $this->postOffers($shop_products);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postPricesStocks($shop_products = null)
    {
        try {
            $shop_products = $this->getShopProducts4Update($shop_products);
            if (!$shop_products->count()) return 'No se han encontrado productos para actualizar en esta Tienda';

            return $this->postOffers($shop_products, 'REPLACE', $this->only_stocks);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $shop_products);
        }
    }


    public function postGroups($shop_products = null)
    {
        return 'Mirakl no tiene grupos de categorías.';
    }


    public function removeProduct($marketProductSku = null)
    {
        try {
            if (isset($marketProductSku))
                if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku)) {
                    //return $this->postOffer($shop_product, $marketProductSku, true);
                    $remove_item = [
                        'mps_sku'   => $shop_product->mps_sku,      //$shop_product->getMPSSku(),
                        'ean'       => $shop_product->ean
                    ];
                    $offers = $this->buildRemoveItems([$remove_item]);
                    $res = $this->postMiraklOffers($offers);
                    $shop_product->deleteSecure();

                    return $marketProductSku;
                }

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $marketProductSku);
        }
    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        try {
            // Accept Order
            if (!isset($order->shipping_address->address1))
                return $this->postMiraklAcceptOrder($order, $shipment_data);
            // Confirm Order
            else {
                if (isset($shipment_data['tracking'])) {
                    $res = $this->postMiraklTrackings($order, $shipment_data);
                    if ($res != true)
                        return false;
                }

                $res = $this->postMiraklValidateShipment($order);
                if ($res == true)
                    return true;
            }

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$order, $shipment_data]);
        }
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return 'Mirakl no tiene comentarios de pedidos.';
    }


    public function synchronize()
    {
        try {
            $res = [];

            $offer_pages = $this->getMiraklOffers();
            foreach ($offer_pages as $offer_page) {
                foreach ($offer_page as $offer) {

                    $mps_sku = $offer['sku'];
                    $marketProductSku = $offer['product']['sku'];
                    $ean = $offer['product']['references'][0]['value'];
                    $res['ONLINE OFFERS'][$marketProductSku] = $marketProductSku;

                    $marketCategoryId = $offer['product']['category']['code'];
                    $cat_label = $offer['product']['category']['label'];
                    $res['SKUS'][$ean][] = $mps_sku;        // Duplicateds ?

                    $shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku);
                    /* if (!isset($shop_product))
                        $shop_product = $this->shop->shop_products()->firstWhere('mps_sku', $mps_sku); */

                    if (!isset($shop_product)) {

                        if ($shop_product = $this->shop->shop_products()->filter($this->shop, [])->firstWhere('products.ean', $ean)) {
                            $shop_product->marketProductSku = $marketProductSku;
                            $shop_product->save();

                            $res['UPDATED SERVER MARKET SKU '][] = [
                                'marketProductSku'  => $marketProductSku,
                                'ean'               => $ean
                            ];
                        }
                        else {
                            $res['ONLINE NOT FOUND'][] = [
                                'marketProductSku'  => $marketProductSku,
                                'ean'               => $ean
                            ];
                        }
                    }

                    //dd($ean, $mps_sku, $marketProductSku, $offer, $shop_product, $res);

                    if (isset($shop_product)) {

                        $res['CATEGORIES'][$marketCategoryId][] = [
                            'supplier_category_id'  => $shop_product->product->supplier_category_id,
                            'supplier_id'           => $shop_product->product->supplier_id
                        ];

                        $supplier_category = SupplierCategory::find($shop_product->product->supplier_category_id);
                        $market_category = MarketCategory::where('marketCategoryId', $marketCategoryId)->whereMarketId($this->market->id)->first();

                        // update $shop_product->market_category_id ?
                        if ($market_category && !$shop_product->market_category_id) {
                            $shop_product->market_category_id = $market_category->id;
                            $shop_product->save();
                        }

                        // Add new Category Mapping ?
                        if ($supplier_category && $market_category && !$supplier_category->market_categories()->wherePivot('market_id', $this->market->id)->count()) {
                            $supplier_category->market_categories()->attach($market_category->id,
                            [
                                'supplier_id' => $shop_product->product->supplier_id,
                                'market_id' => $this->market->id
                            ]);
                        }

                        if ($offer['active']) {
                            if (!$shop_product->enabled && $shop_product->stock != 0) {
                                $res['ENABLED'][] = $marketProductSku;
                                $shop_product->enabled = true;
                                $shop_product->save();
                            }
                            if ($shop_product->mps_sku != $mps_sku) {
                                $res['UPDATED SERVER MPS SKU'][] = $marketProductSku;
                                $shop_product->mps_sku = $mps_sku;
                                $shop_product->save();
                            }
                        }
                        else {
                            if ($shop_product->stock == 0) {
                                $shop_product->deleteSecure();
                            }
                            else {
                                $res['NO AUTH'][] = $marketProductSku;
                                $shop_product->marketProductSku = 'NO AUTH';
                                $shop_product->save();
                                /* if ($shop_product->enabled) {
                                    $res['DISABLED'][] = $marketProductSku;
                                    $shop_product->enabled = false;
                                } */
                            }

                            $res['DELETED ONLINE'][] = [
                                'mps_sku'   => $mps_sku,
                                'ean'       => $ean
                            ];
                        }
                    }

                    //if ($shop_product && $offer['active'])
                      //  dd($ean, $mps_sku, $marketProductSku, $offer, $shop_product, $res);

                }
            }

            // ADDS CATEGORY MAPPINGS
            /* if (isset($res['CATEGORIES'])) {
                foreach ($res['CATEGORIES'] as $marketCategoryId => $supplier_categories_info) {
                    foreach ($supplier_categories_info as $supplier_category_info) {

                        // Add new Category Mapping ?
                        $supplier_category = SupplierCategory::find($supplier_category_info['supplier_category_id']);
                        $market_category = MarketCategory::where('marketCategoryId', $marketCategoryId)->whereMarketId($this->market->id)->first();
                        if ($supplier_category && $market_category && !$supplier_category->market_categories()->wherePivot('market_id', $this->market->id)->count()) {
                            $supplier_category->market_categories()->attach($market_category->id,
                            [
                                'supplier_id' => $supplier_category_info['supplier_id'],
                                'market_id' => $this->market->id
                            ]);
                        }
                    }
                }
            } */

            // RESETS LOCAL OFFERS THAT NOT EXIST IN ONLINE
            if (isset($res['ONLINE OFFERS'])) {
                $shop_products_marketProductSku_list = $this->getShopProducts4Update()->pluck('marketProductSku');
                $res['RESETS'] = $shop_products_marketProductSku_list->diff($res['ONLINE OFFERS']);
                foreach ($res['RESETS'] as $marketProductSku) {
                    if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku)) {
                        $shop_product->marketProductSku = null;
                        $shop_product->save();

                        if ($shop_product->stock == 0)
                            $shop_product->deleteSecure();
                    }
                }
            }

            // REMOVE DUPLICATEDS
            if (isset($res['SKUS'])) {
                foreach ($res['SKUS'] as $ean => $mps_skus) {
                    if (is_array($mps_skus) && count($mps_skus) > 1) {
                        foreach ($mps_skus as $mps_sku) {
                            $res['DELETE ONLINE'][] = [
                                'mps_sku'   => $mps_sku,
                                'ean'       => $ean,
                            ];
                        }
                    }
                    else {
                        unset($res['SKUS'][$ean]);
                    }
                }
            }

            // CHANGE FUNCTION FOR REMOVE MPS_SKU + MARKETPRODUCTSKU (POTSER HI HA 2 OFERTES AMB MATEIX MARKETPRODUCTSKU)
            // REMOVE ONLINE OFFERS THAT NOT IN SERVER
            /* if (isset($res['DELETED ONLINE'])) {
                $offers = $this->buildRemoveItems($res['DELETE_ONLINE']);
                $res['POST_DELETES'] = $this->postMiraklOffers($offers);
            } */

            // REMOVE WITHOUT STOCK
            foreach ($this->shop->shop_products as $shop_product)
                if ($shop_product->stock == 0 && !$shop_product->isUpgradeable() && $shop_product->marketProductSku != 'NO AUTH')
                    $shop_product->deleteSecure();

            $filename = date('Y-m-d_H-i-s'). '_products.json';
            $json_res = json_encode($res);
            Storage::append($this->shop_dir. 'sync/'.$filename, $json_res);

            //unset($res['ONLINE_OFFERS']);
            unset($res['DELETED ONLINE']);

            /* header('Cache-Control: max-age=0');
            header('Content-disposition: attachment; filename="'.urlencode($filename).'"');
            header('Content-type: application/json');
            echo $json_res;
            exit(); */

            /* $r = [];
            foreach ($res as $key => $value)
                $r[$key] = count($value);

            return $r; */

            $msg = 'Ofertas sincronizadas! Hay '.count($res['ONLINE OFFERS']). ' ofertas en '.$this->market->name;
            if (isset($res['NO AUTH'])) $msg .= '. Hay '.count($res['NO AUTH']). ' sin activar.';
            if (isset($res['ONLINE NOT FOUND'])) $msg .= ' Hay '.count($res['ONLINE NOT FOUND']). ' ofertas no encontradas en el servidor.';

            return $msg;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    public function syncCategories()
    {
        try {
            $changes = [];
            $market_params = $this->market->market_params;
            $offer_pages = $this->getMiraklOffers();
            foreach ($offer_pages as $offer_page) {
                foreach ($offer_page as $offer) {

                    if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $offer['product']['sku'])) {
                        $new_category_code = $offer['product']['category']['code'];
                        $shop_product_category_code = $shop_product->market_category->marketCategoryId ?? null;

                        if ($new_category_code != $shop_product_category_code) {
                            $shop_product->longdesc = mb_substr($shop_product->longdesc, 0, 65535); //substr(utf8_encode($shop_product->longdesc), 0, 65535);
                            $changes['CATEGORY CHANGES'][$new_category_code][] = [
                                'old_code' => $shop_product_category_code,
                                'mp_sku' => $shop_product->marketProductSku,
                                'shop_product' => [
                                    'id'                    => $shop_product->id,
                                    'mps_sku'               => $shop_product->mps_sku,
                                    'product_id'            => $shop_product->product_id,
                                    'market_code'           => $shop_product->market->code,
                                    'shop_code'             => $shop_product->shop->code,
                                    'market_category_id'    => $shop_product->market_category_id,
                                    'market_category_name'  => $shop_product->market_category->name ?? null,
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
                            else {
                                $changes['NO MARKET_CATEGORIES FOUND'][] = $new_category_code;
                                $this->nullAndStorage(__METHOD__, ['NO MARKET_CATEGORIES FOUND', $this->shop->code, $new_category_code]);
                            }

                        }
                    }
                    else {
                        $changes['SHOP_PRODUCT NOT FOUND'][] = [
                            'shop'  => $this->shop,
                            'offer' => $offer
                        ];
                        //$this->nullAndStorage(__METHOD__, ['SHOP_PRODUCT NOT FOUND', $this->shop->code, $offer]);
                    }
                }
            }

            if (count($changes))
                Storage::append($this->shop_dir. 'categories/' .date('Y-m-d'). '_info.json', json_encode($changes));

            return $changes;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$offer_pages, $changes]);
        }
    }


    public function removeWithoutStock()
    {
        try {
            $res = [];
            $product_ids_today_orders = Order::getProductIdsTodayOrders();
            foreach ($this->shop->shop_products as $shop_product) {
                $shop_product->setPriceStock(null, $this->cost_is_price, $product_ids_today_orders);
                if ($shop_product->stock == 0) {
                    if (!$shop_product->isUpgradeable())
                        $shop_product->deleteSecure();
                    else {
                        $mps_sku = $shop_product->mps_sku;      //$shop_product->getMPSSku();
                        $ean = $shop_product->ean;
                        if ($shop_product->deleteSecure())
                            $res['DELETE_ONLINE'][] = [
                                'mps_sku'   => $mps_sku,
                                'ean'       => $ean
                            ];
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
        }
    }


    public function getSomeBuyboxPrices()
    {
        try {
            $marketProductSkus = $this->shop->shop_products()->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)
                ->where('stock', '>', 0)
                ->orderBy('buybox_updated_at', 'ASC')
                ->limit(500)
                ->pluck('marketProductSku')
                ->toArray();

            $prices = [];
            $chunks = array_chunk($marketProductSkus, 100);
            foreach ($chunks as $chunk) {
                if ($res = $this->getMiraklProductOffers($chunk)) {
                    $prices += $res;
                }

                //break;  // FAKE
            }

            $count = 0;
            if (count($prices)) {
                foreach ($prices as $marketProductSku => $offer_info) {
                    if ($shop_product = $this->shop->shop_products()->firstWhere('marketProductSku', $marketProductSku)) {
                        $shop_product->setBuyBoxPrice($offer_info['buybox']);
                        $count++;
                    }
                }

                return ['ok' => $count, 'prices' => $prices];
            }

            return $this->nullAndStorage(__METHOD__, [$this->shop->code, $prices ?? null, $marketProductSkus ?? null]);
        }
        catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$this->shop->code, $prices ?? null, $marketProductSkus ?? null]);
        }
    }



    /************* UTIL FUNCTIONS *********************/


    public function extractCompetitorPrice($stringPrice)
    {
        try {
            $competitor_price = trim($stringPrice);
            $length = (strpos($competitor_price, ' ') === false) ? strlen($competitor_price) : strpos($competitor_price, ' ');
            $competitor_price = substr($competitor_price, 0, $length);
            $competitor_price = str_replace('€', '', $competitor_price);

            return FacadesMpe::roundFloatEsToEn($competitor_price);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $stringPrice);
        }
    }


    public function getScrapeField()
    {
        return 'products.ean';
    }


    /************* REQUEST FUNCTIONS *********************/



    public function getProduct($code)
    {
        try {

            $matches = [];
            $count = $count_ok = 0;
            $chunks = Product::whereSupplierId(41)->get()->chunk(100);
            foreach ($chunks as $chunk) {
                $product_references = ['EAN' => $chunk->pluck('ean')->toArray()];
                if ($response = $this->getMiraklProducts($product_references)) {
                    foreach ($response as $mp_product) {
                        if ($mp_product_id = $mp_product->getId()) {

                            $matches[$mp_product->getCategory()->getCode(). ' - '.$mp_product->getCategory()->getLabel()][] = [$mp_product_id, $mp_product->getSku()];
                            $count_ok++;
                        }
                    }
                }
            }

            Storage::append('errors/grutinet_'.$this->shop->code.'_asins.json', json_encode([$count, $count_ok, $matches]));
            dd($count, $count_ok, $chunks, $matches);





            $mps_sku = '83923_ST3000VX009_8719706002929';
            $remove_item = [
                'mps_sku'   => $mps_sku,
                //'ean'       => $shop_product->ean
            ];
            $offers = $this->buildRemoveItems([$remove_item]);
            $res = $this->postMiraklOffers($offers);
            dd($mps_sku, $remove_item, $res);






            $chunk = ['0195697015731'];
            $res = $this->getMiraklProductOffers($chunk);
            dd($res);

           //$res = $this->getMiraklProducts(['EAN' => $code]);
            //$res = $this->getMiraklOffer($code);
            $res = $this->getMiraklShippingZones();
            dd($res);

            /* $best_price = $best_price_5 = $best_price_10 = $best_price_15 = $best_price_20 = $best_price_25 = [];
            $bad_price = [];
            $prices = $this->getShopProductsBestOffers();
            foreach ($prices as $price) {
                if ($shop_product = $this->shop->shop_products()->where('marketProductSku', $price['marketProductSku'])->first()) {
                    if ($shop_product->price <= $price['buybox']) $best_price[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+5 && $shop_product->cost >= 100) $best_price_5[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+10 && $shop_product->cost >= 200) $best_price_10[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+15 && $shop_product->cost >= 400) $best_price_15[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+20 && $shop_product->cost >= 600) $best_price_20[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+25 && $shop_product->cost >= 800) $best_price_25[] = $price;
                    else $bad_price[] = $price;
                }
            }

            dd($best_price, $best_price_5, $best_price_10, $best_price_15, $best_price_20, $best_price_25, $bad_price, $prices); */

        } catch (Throwable $th) {
            dd($th);
        }

        dd('FI');
    }


    public function getAllProducts()
    {
        dd($this->getOrders());
        dd($this->getMiraklOrders(100, 0, 14));


        $responses = $this->getMiraklOffers();
        //dd($responses);

        // Update ItemIDs
        $ok = null;
        $no_sku = null;
        $no_product = null;
        foreach ($responses as $response) {

            dd($response[0]['product'], $response[0], $response, $responses);

            foreach ($response as $item) {
                /* $this->shop->shop_products()
                    ->where('product_id', $this->getIdFromMPSSku($item['sku']))
                    ->whereNull('marketProductSku')
                    // $item['sku']               SKU MPS
                    // $item['offer_id']         OFFER ID
                    // $item['product']['sku']      SKU PRODUCT WORTEN / MIRAKL
                    ->update(['marketProductSku' => $item['offer_id']]); */

                $shop_product = $this->shop->shop_products()
                    ->where('mps_sku', $item['sku'])
                    ->first();

                if ($shop_product) {
                    if (isset($shop_product->marketProductSku)) $ok[] = $shop_product;
                    else $no_sku[] = $shop_product;
                }
                else
                    $no_product[] = $item;
            }
        }

        dd($responses, $no_sku, $no_product, $ok);
    }


    public function removeAllProducts()
    {
        $responses = $this->getMiraklOffers();

        $remove_items = [];
        $no_product = null;
        foreach ($responses as $response) {

            foreach ($response as $item) {
                $remove_items[] = ['mps_sku' => $item['sku']];

                $shop_product = $this->shop->shop_products()
                    ->where('mps_sku', $item['sku'])
                    ->first();

                if ($shop_product)
                    $shop_product->deleteSecure();
                else
                    $no_product[] = $item;
            }
        }

        if (count($remove_items)) {
            $offers = $this->buildRemoveItems($remove_items);
            $res = $this->postMiraklOffers($offers);
        }

        if ($this->shop->shop_products->count()) {
            $shop_products = $this->shop->shop_products;
            foreach ($shop_products as $shop_product) {
                $shop_product->deleteSecure();
            }
        }

        dd($responses, $no_product, $remove_items, $offers ?? null, $res ?? null, $shop_products ?? null);
    }


    public function getShopConfig()
    {
        /* $product_offers = $this->getMiraklProductOffers(['227977']);
        dd($product_offers); */

        $channels = $this->getMiraklChannels();
        $locales = $this->getMiraklLocaleCodes();
        $states = $this->getMiraklOfferStateList();
        $logistic_classes = $this->getMiraklLogisticClasses();
        $shipping_zones = $this->getMiraklShippingZones();
        $custom_fields = $this->getMiraklListCustomFields();

        dd($channels, $locales, $states, $logistic_classes, $shipping_zones, $custom_fields);


        $this->getCarriers();
        $header = FacadesShopProductsExcel::getHeader($this, 'products.xlsx');
        $header_rows = is_array($header) ? count($header) : 'no array';

        //dd($locales, $states, $logistic_classes, $shipping_zones, $header, $header_rows);

        if (!isset($this->shop->config)) {
            $this->shop->config = json_encode($this->DEFAULT_CONFIG);
            $this->shop->save();
        }

        $locales_array = [];
        if (isset($locales)) {
            foreach ($locales as $locale) {
                $locales_array[] = $locale->getCode();
            }
        }

        $states_array = [];
        if (isset($states)) {
            foreach ($states as $state) {
                $states_array[$state->getLabel()] = $state->getCode();
            }
        }

        $logistic_array = [];
        if (isset($logistic_classes)) {
            foreach ($logistic_classes as $logistic_classe) {
                $logistic_array[] = $logistic_classe->getCode();
            }
        }

        $shipping_array = [];
        if (isset($shipping_zones)) {
            foreach ($shipping_zones as $shipping_zone) {
                $shipping_array[] = $shipping_zone->getCode();
            }
        }

        $config = json_decode($this->shop->config, true);
        $config['locale'] = $locales_array;
        $config['state_codes'] = $states_array;
        $config['logistic_classes'] = $logistic_array;
        $config['shipping_zones'] = $shipping_array;

        dd($config);

        $this->shop->config = json_encode($config);
        $this->shop->save();

        return $this->shop;
    }


    public function getBuyBoxPrices()
    {
        try {
            $best_price = $best_price_5 = $best_price_10 = $best_price_15 = $best_price_20 = $best_price_25 = [];
            $bad_price = [];
            $prices = $this->getShopProductsBestOffers();
            foreach ($prices as $price) {
                if ($shop_product = $this->shop->shop_products()->where('marketProductSku', $price['marketProductSku'])->first()) {
                    if ($shop_product->price <= $price['buybox']) $best_price[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+5 && $shop_product->cost >= 100) $best_price_5[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+10 && $shop_product->cost >= 200) $best_price_10[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+15 && $shop_product->cost >= 400) $best_price_15[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+20 && $shop_product->cost >= 600) $best_price_20[] = $price;
                    elseif ($shop_product->price <= $price['buybox']+25 && $shop_product->cost >= 800) $best_price_25[] = $price;
                    else $bad_price[] = $price;
                }
            }

            dd($best_price, $best_price_5, $best_price_10, $best_price_15, $best_price_20, $best_price_25, $bad_price, $prices);

        } catch (Throwable $th) {
            dd($th, $prices ?? null);
        }
    }


    public function getJob($jobId, $operation = null)
    {
        try {
            $shop_job = $this->shop->shop_jobs->firstWhere('jobId', $jobId);
            $import_result = null;
            $report = null;
            if ($operation == 'ProductImport') {
                $import_result = $this->getMiraklProductImportStatus($shop_job);
                if ($import_result)
                    $report = $this->getMiraklProductJob($import_result, $jobId);
            }
            elseif ($shop_job->operation == 'OfferImport') {
                $import_result = $this->getMiraklOfferImportStatus($shop_job);
                if ($import_result)
                    $report = $this->getMiraklOfferJob($import_result, $jobId);
            }

            dd($jobId, $operation, $shop_job, $import_result, $report);

        } catch (Throwable $th) {
            dd($th);
        }

        dd($jobId, $operation);
    }


    public function getAllJobs()
    {
        try {
            $api = new ShopApiClientCatalog($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new ProductImportStatusesRequest();      // P51
            //$request->setStatus('SENT');    // CANCELLED, WAITING, QUEUED, RUNNING, SENT, COMPLETE, FAILED
            $response = $api->getProductImportStatuses($request);
            Storage::put($this->shop_dir. 'jobs/' .date('Y-m-d_H-i-s'). '_alljobs.json', json_encode($response->toArray()));
            dd($response);

        } catch (Throwable $th) {
            // An exception is thrown if object requested is not found or if an error occurs
            dd($th);
        }

    }


    public function getOffer($offer_id)
    {
        // ean: 0193808723483   sku: 7e6a5f93-d8c0-4813-8100-446dd3d4e649   pn: 6MR14EA
        // ean: 4710180518405
        // $offer_id = 13078438;
        try {
            $api = new ShopApiClientProducts($this->apiUrl, $this->apiKey, $this->shopId);
            $request = new GetOfferRequest($offer_id);
            $response = $api->getOffer($request);
            Storage::append($this->shop_dir. 'offers/' .date('Y-m-d'). '_getOffer.json', $response->toJSON());
            dd($response);
        } catch (Throwable $th) {
            Storage::append($this->shop_dir. 'offers/' .date('Y-m-d'). '_getOffer.json', json_encode($th->getMessage()));
            dd($th->getMessage());
        }
    }


    public function setDefaultShopFilters()
    {
        // SPEEDLER: NO INTEGRAR ENLLOC
        // PCCOMPO: INTEGRAR A TOTHOM. OLD: NO INTEGRAR 30 Esprinet, 22 Depau. NO Tarjetas de vídeo.
        // WORTEN: NO PORTUGAL, ONLY SPAIN: Esprinet, Depau, Aseuropa, Desyman. NO: Blanes
        // BLANES: Only Carrefour i Fnac

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
        // Esprinet: 13, 30

        // NO PORTUGAL: 14-27 desyman, 13-30 Esprinet, 22 Depau, 37 Aseuropa
        // 1 Blanes 14-27 desyman, 13-30 Esprinet, 35 Techdata NO ACTUALITZA
        $supplier_ids = [8, 10, 11, 13, 14, 16, 24, 27, 29, 30, 31, 36];
        // 22 Depau, 23 Megasur, 26 SCE, 37 Aseuropa, 39 Infortisa
        $own_suppliers = [22, 23, 26, 37];
        // 1 Blanes
        //if ($this->market->code == 'carrefour') $supplier_ids[] = 1;
        //$supplier_ids = array_merge($supplier_ids, $own_suppliers);

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
                'Accesorios para piscinas y jacuzzis','Dispositivos biométricos']     /*CARREFOUR*/
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

        //dd($categories_id_100, $categories_id_300, $categories_id_500, $categories_id_1000);

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

                //dd($cost_max, $category_ids, $filter_group, $supplier_id);

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



    public function cleanErrors()
    {
        $count = $this->shop->shop_products()->where('marketProductSku', 'NO PRODUCT')->update(['marketProductSku' => null]);
        dd('Cleaned', $count);
    }

}
