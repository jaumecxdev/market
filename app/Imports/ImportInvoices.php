<?php

namespace App\Imports;


use App\Market;
use App\Order;
use App\OrderPayment;
use Carbon\Carbon;
use Facades\App\Facades\Mpe as FacadesMpe;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Throwable;



class ImportInvoices
{
    // IMPORT FACTUSOL INVOICE NUMBERS TO ORDER PAYMENTS
    // ON FACTUSOL, SELECT INVOICES + COPY TO EXCEL -> SAVE TO FACTURAS/CLIENTES
    const FUNCTIONS = ['importInvoices'];

    const FORMATS = [
        'invoices'    => [
            'columns'       => 11,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const IMPORT_TEXT = "<b>Importación de cobros:</b> Importación de Copy Paste de Factusol.";


    public function __construct()
    {

    }




    public function importInvoices(array $uploaded_files)
    {
        $not_imported_rows = [];
        $count = 0;
        try {
            $uploaded_file = $uploaded_files[0];
            //$filename = $uploaded_file->getClientOriginalName();
            //$file_rows = $this->getFileRowsExcel($uploaded_file, self::FORMATS['invoices']['header_rows']);
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['invoices']['header_rows']);

            // test array
            if (!is_array($file_rows)) return $file_rows;
            if (!count($file_rows)) return 'No hay filas para importar.';
            if (count($file_rows[0]) != self::FORMATS['invoices']['columns'])
                return 'No tiene '.self::FORMATS['invoices']['columns']. ' columnas. Tiene '.count($file_rows[0]);

            if (count($file_rows)) {

                $info = [];
                foreach ($file_rows as $invoice_info) {

                    if ($orders = Order::where('marketOrderId', $invoice_info['E'])->get()) {

                        if (!$orders->count() || $orders->count() > 1) $not_imported_rows[] = $invoice_info;
                        else {
                            $order = $orders->first();
                            if ($order_payments = OrderPayment::whereOrderId($order->id)->get()) {
                                foreach ($order_payments as $order_payment) {
                                    $order_payment->invoice_mpe = $invoice_info['A'];
                                    // invoice_mpe_price` = 1,542.58
                                    $order_payment->invoice_mpe_price = FacadesMpe::roundFloatEsToEn($invoice_info['H']);
                                    $order_payment->save();

                                    //dd($order_payment, $order_payments, $invoice_info);
                                }
                            }
                            else
                                $not_imported_rows[] = $invoice_info;
                        }
                    }
                }

                //dd($invoice_info, $invoice_info['E'], $orders->count(), $order, $orders->first(), $file_rows);
            }

            return 'Importadas '.$count. ' facturas. Filas no importadas: '.json_encode($not_imported_rows);

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


}
