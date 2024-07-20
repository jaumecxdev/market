<?php

namespace App\Imports;

use App\Address;
use App\Buyer;
use App\Country;
use App\Shop;
use App\Status;
use App\Currency;
use App\Order;
use App\Traits\HelperTrait;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Throwable;



class ImportAmazonSP
{
    use HelperTrait;

    // SellerCentral | Pedidos | Informes de pedidos
    const FUNCTIONS = ['importOrders'];

    const FORMATS = [
        'orders'    => [
            'columns'       => 34,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const IMPORT_TEXT = "<b>Importación de pedidos:</b> Importación de TXT de los ultimos pedidos.";

    const ORDER_STATUS_IGNORED = ['Pending','Shipped'];

    public function __construct()
    {

    }


    private function getMarketShopId(UploadedFile $uploaded_file)
    {
        // $uploaded_file->getMimeType()        application/vnd.ms-excel
        // $uploaded_file->getPathname()        

        $inputFileType = IOFactory::identify($uploaded_file->getPathname());
        $reader = IOFactory::createReader($inputFileType);
        $spreadsheet = $reader->load($uploaded_file->getPathname());
        $sheet = $spreadsheet->getSheet(0);
        $file_rows = $sheet->toArray(null, true, true, true);

        return $file_rows[1]['A'] ?? null;
    }

    /* "A" => "order-id"
    "B" => "order-item-id"
    "C" => "purchase-date"
    "D" => "payments-date"
    "E" => "buyer-email"
    "F" => "buyer-name"
    "G" => "buyer-phone-number"
    "H" => "sku"
    "I" => "product-name"
    "J" => "quantity-purchased"
    "K" => "currency"
    "L" => "item-price"
    "M" => "item-tax"
    "N" => "shipping-price"
    "O" => "shipping-tax"
    "P" => "ship-service-level"
    "Q" => "recipient-name"
    "R" => "ship-address-1"
    "S" => "ship-address-2"
    "T" => "ship-address-3"
    "U" => "ship-city"
    "V" => "ship-state"
    "W" => "ship-postal-code"
    "X" => "ship-country"
    "Y" => "ship-phone-number"
    "Z" => "delivery-start-date"
    "AA" => "delivery-end-date"
    "AB" => "delivery-time-zone"
    "AC" => "delivery-Instructions"
    "AD" => "is-business-order"
    "AE" => "purchase-order-number"
    "AF" => "price-designation"
    "AG" => "is-sold-by-ab" */


    public function importOrders(array $uploaded_files)
    {
        $imported = [];
        $count = 0;
        try {
            $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['orders']['header_rows']);

            // test array
            if (!is_array($file_rows)) return $file_rows;
            if (!isset($file_rows) || !count($file_rows))
                return 'No hay filas para importar.';
            if (count($file_rows[0]) != self::FORMATS['orders']['columns'])
                return 'No tiene '.self::FORMATS['orders']['columns']. ' columnas. Tiene '.count($file_rows[1]);

            if (count($file_rows)) {

                $order_prices = [];

                if (config('app.env') == 'local') {
                    $market_id = 21;
                    $shop_id = 29;
                }
                else {
                    $market_id = 19;
                    $shop_id = 20;
                }

                $shop = Shop::find($shop_id);

                $amzn_orders = [];
                foreach ($file_rows as $order_info) {

                    $marketOrderId = $order_info['A'];
                    $marketItemId = $order_info['B'];
                    $created_at = Carbon::createFromFormat('Y-m-d\TH:i:s\+00:00', $order_info['C']);     // "2021-07-19T18:21:49+00:00"
                    $updated_at = Carbon::createFromFormat('Y-m-d\TH:i:s\+00:00', $order_info['D']);

                    $buyer_email = $order_info['E'];
                    $buyer_name = $order_info['F'];
                    $buyer_phone = $order_info['G'];

                    $mps_sku = $order_info['H'];
                    $product_name = $order_info['I'];
                    $quantity = $order_info['J'];
                    $currency_code = $order_info['K'];
                    $price = $order_info['L'];
                    $price_tax = $order_info['M'];
                    $shipping_price = $order_info['N'];
                    $shipping_price_tax = $order_info['O'];

                    $marketStatusName = $order_info['P'];

                    $ship_name = $order_info['Q'];
                    $ship_address_1 = $order_info['R'];
                    $ship_address_2 = $order_info['S'];
                    $ship_address_3 = $order_info['T'];
                    $ship_city = $order_info['U'];
                    $ship_state = $order_info['V'];
                    $ship_zipcode = $order_info['W'];
                    $ship_country_code = $order_info['X'];
                    $ship_phone = $order_info['Y'];

                    $sales_channel = $order_info['AD'];
                    $info = $sales_channel.' '.($order_info['AE'] == 'false') ? 'NO-is-business-order' : 'is-business-order';


                    $status = Status::firstOrCreate([
                        'market_id'             => $market_id,
                        'marketStatusName'      => $marketStatusName,
                        'type'                  => 'order',
                    ],[
                        'name'                  => $marketStatusName,
                    ]);

                    $currency = Currency::firstOrCreate([
                        'code'  => $currency_code,
                    ],[]);

                    $country = Country::firstOrCreate([
                        'code'  => $ship_country_code,
                    ],[]);

                    $shipping_address = Address::updateOrCreate([
                            'country_id'            => $country->id,
                            'market_id'             => $market_id,
                            'marketBuyerId'         => $buyer_email,
                        ],[
                            'name'                  => $ship_name,
                            'address1'              => $ship_address_1,
                            'address2'              => $ship_address_2,
                            'address3'              => $ship_address_3,
                            'city'                  => $ship_city,
                            'state'                 => $ship_state,
                            'zipcode'               => $ship_zipcode,
                            'phone'                 => $ship_phone,
                            'district'              => null,
                            'municipality'          => null,
                        ]);

                    $buyer = Buyer::updateOrCreate([
                        'market_id'             => $market_id,
                        'marketBuyerId'         => $buyer_email,
                    ],[
                        'name'                  => $ship_name ?? $buyer_name,
                        'email'                 => $buyer_email,
                        'shipping_address_id'   => $shipping_address->id,
                        //'billing_address_id'    => null,
                        'phone'                 => $buyer_phone,

                        //'company_name'          => null,
                        //'tax_region'            => null,
                        //'tax_name'              => null,
                        //'tax_value'             => null,
                    ]);

                    $order = $shop->orders()->where('marketOrderId', $marketOrderId)->first();
                    //$notified = (!isset($order) && !in_array($status->marketStatusName, self::ORDER_STATUS_IGNORED)) ? false : true;
                    //$notified_updated = (isset($order) && $order->status_id != $status->id && !in_array($status->marketStatusName, self::ORDER_STATUS_IGNORED)) ? false : true;

                    $order_prices[$marketOrderId][$marketItemId] = [$quantity, $price, $shipping_price];

                    $order = Order::updateOrCreate([
                        'market_id'             => $market_id,
                        'shop_id'               => $shop_id,
                        'marketOrderId'         => $marketOrderId,
                    ],[
                        'buyer_id'              => $buyer->id,
                        'shipping_address_id'   => $shipping_address->id,
                        'billing_address_id'    => null,
                        'currency_id'           => $currency->id ?? 1,
                        'status_id'             => $status->id,
                        'type_id'               => null,
                        'SellerId'              => null,
                        'SellerOrderId'         => null,
                        'info'                  => $info,
                        'price'                 => 0,
                        'tax'                   => $price_tax,
                        'shipping_price'        => 0,
                        'shipping_tax'          => $shipping_price_tax,
                        //'notified'              => $notified,
                        //'notified_updated'      => $notified_updated,
                    ]);

                    $order->created_at = $created_at;
                    $order->updated_at = $updated_at;
                    $order->save();

                    $shop_product = $shop->shop_products()->where('mps_sku', $mps_sku)->first();

                    $order_item = $order->updateOrCreateOrderItem(
                        $marketItemId,
                        $mps_sku,
                        $shop_product->marketProductSku ?? null,
                        $product_name,
                        $quantity,
                        $price,
                        $price_tax,
                        $shipping_price,
                        $shipping_price_tax,
                        null,
                        []
                    );

                    $imported[] = [$order_prices, $order, $order_item, $buyer, $shipping_address, $country, $status, $currency];

                    $count++;
                }

                foreach ($order_prices as $marketOrderId => $items) {
                    if ($order = $shop->orders()->where('marketOrderId', $marketOrderId)->first()) {

                        foreach ($items as $item) {
                            $order->price += ($item[0] * $item[1]);
                            $order->shipping_price += ($item[0] * $item[2]);
                        }

                        $order->save();
                    }
                }
            }

            return 'Importados '.$count. ' pedidos.';

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


}
