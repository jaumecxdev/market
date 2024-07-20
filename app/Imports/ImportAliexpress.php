<?php

namespace App\Imports;


use App\Shop;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Throwable;



class ImportAliexpress
{
    // IMPORT ALIPAY EXCELS EXPORT: https://global.alipay.com/merchant/bizportal/bill/b-transaction-bill
    const FUNCTIONS = ['importPayments'];

    const FORMATS = [
        'payments'    => [
            'columns'       => 13,
            'header_rows'   => 3,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const IMPORT_TEXT = "<b>Importación de cobros:</b> Importación de CSVs de actividad de cuenta de Alipay.";


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


    public function importPayments(array $uploaded_files)
    {
        $not_imported_rows = [];
        $count = 0;
        try {
            $uploaded_file = $uploaded_files[0];
            //$filename = $uploaded_file->getClientOriginalName();

            $marketShopId = $this->getMarketShopId($uploaded_file);
            $shop = Shop::firstWhere('marketShopId', $marketShopId);
            //$file_rows = $this->getFileRowsExcel($uploaded_file, self::FORMATS['payments']['header_rows']);
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['payments']['header_rows']);

            // test array
            if (!count($file_rows)) return 'No hay filas para importar.';
            if (count($file_rows[0]) != self::FORMATS['payments']['columns']) return 'No tiene '.self::FORMATS['payments']['columns']. ' columnas.';

            if (count($file_rows)) {

                $payments = [];
                foreach ($file_rows as $payment_info) {

                    // NO Import: Transferencia | Tarifa
                    if (in_array($payment_info['B'], ['Deducción de comisión', 'Ingreso'])) {

                        if ($marketOrderId = explode("\t", $payment_info['H'])[3] ?? null) {
                            $marketOrderId = substr($marketOrderId, strpos($marketOrderId, 'orderId')+8, strlen($marketOrderId));
                            if (!isset($payments[$marketOrderId]))
                                $payments[$marketOrderId] = [
                                    'invoice'       => null,
                                    'price'         => 0,
                                    'mp_bfit'       => 0,
                                    'payment_at'    => null,
                                ];

                            $payments[$marketOrderId]['invoice'] = $payment_info['D'];
                            $payments[$marketOrderId]['payment_at'] = chop($payment_info['A'], "\t");
                            if ($payment_info['B'] == 'Deducción de comisión')
                                $payments[$marketOrderId]['mp_bfit'] += (-1)*$payment_info['E'];
                            elseif ($payment_info['B'] == 'Ingreso')
                                $payments[$marketOrderId]['price'] += $payment_info['E'];
                        }
                        else
                            $not_imported_rows[] = $payment_info;
                    }
                    else
                        $not_imported_rows[] = $payment_info;
               }

               if (count($payments))
                    foreach ($payments as $marketOrderId => $payment) {

                        if ($order = $shop->orders()->firstWhere('marketOrderId', $marketOrderId)) {
                            if ($order_payment = $order->order_payments()
                                ->firstWhere('order_id', $order->id)) {

                                $order_payment->fixed = true;
                                $order_payment->charget = 1;
                                $order_payment->invoice = $payment['invoice'];
                                $order_payment->payment_at = $payment['payment_at'];
                                $order_payment->mp_bfit = $payment['mp_bfit'] ?? 0;
                                $order_payment->price = $payment['price'] ?? 0;

                                $order_payment->save();
                                $count++;
                            } else
                                $not_imported_rows[] = [$marketOrderId, $payment];
                        }
                        else
                            $not_imported_rows[] = [$marketOrderId, $payment];
                    }
            }

            return 'Importadas '.$count. ' transacciones. Filas no importadas: '.json_encode($not_imported_rows);

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


}
