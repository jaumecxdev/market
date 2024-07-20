<?php

namespace App\Facades;

use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class MpeExcel
{
    use HelperTrait;

    const EXCEL_DIR = 'xls/';
    const EXCEL_HEADERS = [
        'OrderPayment'  => ['order_created_at', 'market_shop_name', 'marketOrderId', 'marketItemId',
            'cost', 'price', 'shipping_price',/*  'tax', */ 'bfit', 'mps_bfit', 'mp_bfit',
            'invoice_mpe', 'invoice_mpe_price', 'invoice_mpe_created_at',
            'charget', 'invoice', 'payment_at'],

        'Product'  => ['id', 'pn', 'ean', 'supplier_name', 'supplier_brand_name', 'supplier_category_name', 'brand_name', 'category_name', 'cost', 'stock', 'name']
        //'Product'  => ['ean', 'pn', 'brand_name', 'category_name']
    ];

    /* id" => 298
  "order_id" => 239
  "order_item_id" => 299
  "currency_id" => 1
  "fixed" => 1
  "cost" => 159.1
  "price" => 223.55
  "shipping_price" => 0.0
  "tax" => 21.0
  "bfit" => 11.18
  "mps_bfit" => 0.0
  "mp_bfit" => 15.65
  "charget" => 1
  "invoice" => "000000265437"
  "payment_at" => "2021-01-15 23:04:24"
  "created_at" => "2021-02-10 07:34:47"
  "updated_at" => "2021-02-10 07:37:46"
  "invoice_mpe" => null
  "invoice_mpe_price" => 0.0
  "invoice_mpe_created_at" => null
  "market_order_url" => null
  "marketOrderId" => "49406538-A"
  "marketItemId" => "49406538-A-1"
  "order_created_at" => "2020-12-21 13:14:52"
  "order_status_name" => "AUTO_RECEIVED"
  "currency_code" => "EUR"
  "buyer_name" => "Araceli García Jiménez"
  "market_shop_name" => "(Carrefour) Marketplace e-Specialist" */


    protected $mpe_collection;
    protected $sheet;
    protected $header;


    private function getRegisterRow($mpe_register)
    {
        $row = [];
        $register = $mpe_register->toArray();
        //$register = $mpe_register;

        foreach ($this->header as $column) {
            $row[] = $register[$column];
        }

        return $row;
    }


    private function write($row_index, $row)
    {
        try {
            $i = 1;
            foreach ($row as $value) {
                try {
                    $this->sheet->setCellValueExplicitByColumnAndRow($i, $row_index, $value, DataType::TYPE_STRING);
                    $i++;
                } catch(Throwable  $th) {
                    $this->nullAndStorage(__METHOD__, [$i, $row_index, $value, $th]);
                }
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$column ?? null, $row_index, $row]);
        }
    }


    public function download(Collection $mpe_collection, $extra_data = null)
    {
        $this->mpe_collection = $mpe_collection;
        //$excel_type = 'Product';
        if (!$excel_type = class_basename($mpe_collection->first()))
            return back()->status('No Collection Found.');     // OrderPayment     get_class()    App\OrderPayment

        try {
            if(!Storage::exists(self::EXCEL_DIR))
                Storage::makeDirectory(self::EXCEL_DIR);

            $this->header = self::EXCEL_HEADERS[$excel_type];
            $filename = $excel_type. '_'.date('Y-m-d_H-i-s').'.xlsx';
            $spreadsheet = new Spreadsheet();
            $this->sheet = $spreadsheet->getActiveSheet();
            $this->write(1, $this->header);

            $row_count = 2;
            foreach ($mpe_collection as $mpe_register) {
                $this->write($row_count, $this->getRegisterRow($mpe_register));
                $row_count++;
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save(storage_path('app/'.self::EXCEL_DIR.$filename));

            header('Cache-Control: max-age=0');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'.urlencode($filename).'"');
            $writer->save('php://output');
            exit();

        } catch(Throwable  $th) {
            return $this->nullWithErrors($th, __METHOD__, [$excel_type ?? null, self::EXCEL_HEADERS, $mpe_collection]);
        }
    }

}
