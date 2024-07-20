<?php

namespace App\Imports;


use App\Market;
use App\Order;
use Carbon\Carbon;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Throwable;



class ImportWorten
{
    // IMPORT SONAE EXCELS EXPORT: https://www.sonaelink.com/ESB01802156/ES/financiero/cuenta-corriente
    // OPEN WITH MS EXCEL & SAVE AS
    const FUNCTIONS = ['importPayments'];

    const FORMATS = [
        'payments'    => [
            'columns'       => 12,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const IMPORT_TEXT = "<b>Importación de cobros:</b> Importación de CSVs de actividad de cuenta de SonaeLink.";


    public function __construct()
    {

    }


    /* private function getMarketShopId(UploadedFile $uploaded_file)
    {
        // $uploaded_file->getMimeType()        application/vnd.ms-excel
        // $uploaded_file->getPathname()        

        $inputFileType = IOFactory::identify($uploaded_file->getPathname());
        $reader = IOFactory::createReader($inputFileType);
        $spreadsheet = $reader->load($uploaded_file->getPathname());
        $sheet = $spreadsheet->getSheet(0);
        $file_rows = $sheet->toArray(null, true, true, true);

        return $file_rows[1]['A'] ?? null;
    } */


    public function importPayments(array $uploaded_files)
    {
        $not_imported_rows = [];
        $count = 0;
        try {
            $uploaded_file = $uploaded_files[0];
            //$filename = $uploaded_file->getClientOriginalName();
            //$file_rows = $this->getFileRowsExcel($uploaded_file, self::FORMATS['payments']['header_rows']);
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['payments']['header_rows']);

            // test array
            if (!is_array($file_rows)) return $file_rows;
            if (!count($file_rows)) return 'No hay filas para importar.';
            if (count($file_rows[0]) != self::FORMATS['payments']['columns']) return 'No tiene '.self::FORMATS['payments']['columns']. ' columnas.';

            if (count($file_rows)) {

                $info = [];
                foreach ($file_rows as $payment_info) {

                    // 18 TransferInvoice | 7A Order
                    if ($payment_info['B'] == '18') {
                        $info['INVOICE'][$payment_info['K']] = [
                            'transfer'  => $payment_info['A'],
                            'date'      => $payment_info['L']
                        ];
                    }
                    elseif ($payment_info['B'] == '7A') {
                        $info['ORDER'][$payment_info['A']] = [
                            'price'  => $payment_info['F'],
                            'invoice'  => $payment_info['K'],
                            'payment_at'  => $payment_info['L'],
                        ];
                    }
                }

                if (count($info) && count($info['ORDER']) && $worten_market = Market::whereCode('worten')->first()) {

                    foreach ($info['ORDER'] as $marketOrderId => $order_info) {

                        if ($order = Order::whereMarketId($worten_market->id)->firstWhere('marketOrderId', $marketOrderId)) {

                            if ($order_payment = $order->order_payments()->firstWhere('order_id', $order->id)) {
                                $price = str_replace(',', '.', $order_info['price']);
                                $payment_at = Carbon::createFromFormat('d/m/Y 0:00', $order_info['payment_at'])->format('Y-m-d 00:00:00');
                                $invoice = $order_info['invoice']. ' ';
                                $invoice .= $info['INVOICE'][$order_info['invoice']]['transfer'] ?? '';

                                $order_payment->fixed = true;
                                $order_payment->charget = 1;
                                $order_payment->invoice = $invoice;
                                $order_payment->payment_at = $payment_at;
                                $order_payment->price = $price;
                                $order_payment->save();
                                $count++;

                            } else {
                                $not_imported_rows[] = $payment_info;
                            }
                        }
                    }
                }
            }

            return 'Importadas '.$count. ' transacciones. Filas no importadas: '.json_encode($not_imported_rows);

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


}
