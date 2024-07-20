<?php

namespace App\Facades;

use App\ShopProduct;
use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;


class ShopProductsExcel
{
    use HelperTrait;

    protected $shop_products;
    protected $ws;
    protected $sheet;


    private function open($filename)
    {
        try {
            $inputFileName = storage_path('app/'.$this->ws->getShopDir().$filename);
            $inputFileType = IOFactory::identify($inputFileName);   // 'Xlsx'
            $reader = IOFactory::createReader($inputFileType);
            return $reader->load($inputFileName);
        } catch (Throwable $th) {
            return 'Error open: '.$th->getMessage();
        }
    }


    private function write($row_index, $row)
    {
        $i = 1;
        foreach ($row as $value) {
            try {
                $this->sheet->setCellValueExplicitByColumnAndRow($i, $row_index, $value, DataType::TYPE_STRING);
                $i++;
            } catch(Throwable  $th) {
                return $this->nullWithErrors($th, __METHOD__, [$i, $row_index, $value, $th]);
            }
        }
    }


    public function downloadShopProduct(Collection $shop_products, $ws)
    {
        $this->shop_products = $shop_products;
        $this->ws = $ws;

        try {
            if(!Storage::exists($ws->getShopDir()))
                Storage::makeDirectory($ws->getShopDir());

            $header = ShopProduct::DEFAULT_HEADER;
            $header_rows = count($header);

            $spreadsheet = new Spreadsheet();
            $this->sheet = $spreadsheet->getActiveSheet();
            // Write Header
            foreach ($header as $index => $header_row)
                $this->write($index+1, $header_row);

            // Child Shop Products
            if (!$ws->only_parents) {
                $shop_products = $ws->getShop()->shop_products()->where('is_sku_child', 1)->get();
            }

            $index = $header_rows+1;
            foreach ($shop_products->sortBy('market_category_id') as $shop_product) {
                $header_row = [];
                foreach ($header[0] as $row) {
                    $header_row[$row] = $shop_product->$row;
                }

                $this->write($index, $header_row);
                $index++;
            }

            $new_filename = date('Y-m-d').'-'.$ws->getMarket()->code.'-'.$this->ws->getShop()->code.'.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save(storage_path('app/'.$ws->getShopDir().$new_filename));

            header('Cache-Control: max-age=0');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'.urlencode($new_filename).'"');
            $writer->save('php://output');
            exit();

        } catch(Throwable  $th) {
            dd($th);
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    //public function download(Collection $shop_products, $ws, $filename = null, $func = 'getItemRow', $func_header = null, $form_data = null)
    public function download(Collection $shop_products, $ws, $excel_type = 'product', $extra_data = null)
    {
        $this->shop_products = $shop_products;
        $this->ws = $ws;

        try {
            if(!Storage::exists($ws->getShopDir()))
                Storage::makeDirectory($ws->getShopDir());

            $filename = $excel_type. 's.xlsx';
            $header_type = 'header_'.$excel_type;       // header_product | header_offer | header_promo
            $header = $ws->$header_type;

            if (isset($header)) {
                $header_rows = count($header);
                $spreadsheet = new Spreadsheet();
                $this->sheet = $spreadsheet->getActiveSheet();
                foreach ($header as $index => $header_row) {
                    $this->write($index+1, $header_row);
                }
            }
            elseif (Storage::exists($ws->getShopDir().$filename)) {
                $spreadsheet = $this->open($filename);
                //$header_rows = $ws->header_rows ?? 1;
                $header = $this->getHeader($ws, $filename);
                $header_rows = count($header);
                $this->sheet = $spreadsheet->getActiveSheet();
            }
            else
                return $this->nullAndStorage(__METHOD__, ['Header not found: '.$header_type]);

            if (!is_array($header)) {
                return $this->nullAndStorage(__METHOD__, ['Header is NOT array: '.$header_type]);
            }

            $item_row_func = 'getItemRow'.ucfirst($excel_type);
            $row_count = $header_rows + 1;

            // Child Shop Products
            if (!$ws->only_parents) {
                $shop_products = $ws->getShop()->shop_products()->where('is_sku_child', 1)->get();
            }

            foreach ($shop_products->sortBy('market_category_id') as $shop_product) {

                if ($item_row_product = $this->ws->$item_row_func($shop_product, $extra_data)) {
                    $this->write($row_count, $item_row_product);
                    $row_count++;
                }
            }

            $new_filename = date('Y-m-d').'-'.$ws->getMarket()->code.'-'.$this->ws->getShop()->code.'.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save(storage_path('app/'.$ws->getShopDir().$new_filename));

            header('Cache-Control: max-age=0');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'.urlencode($new_filename).'"');
            $writer->save('php://output');
            exit();

        } catch(Throwable  $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    public function getAttributes($ws, $excel_type = 'product')
    {
        try {
            if(!Storage::exists($ws->getShopDir()))
                Storage::makeDirectory($ws->getShopDir());

            $this->ws = $ws;
            $filename = $excel_type. 's.xlsx';

            if(!Storage::exists($this->ws->getShopDir().$filename)) return null;

            $spreadsheet = $this->open($filename);
            $attributes_sheet = $spreadsheet->getSheet(0);      // $spreadsheet->getSheet(1);
            $header_type = 'header_'.$excel_type;       // header_product | header_offer | header_promo

            return $attributes_sheet->toArray(null, true, true, true)[count($ws->$header_type)];      // [count($ws->$header_type)-1];

        } catch(Throwable  $th) {
            return $this->msgAndStorage(__METHOD__, '', [$th]);
        }
    }


    public function getHeader($ws, $filename)
    {
        try {
            if(!Storage::exists($ws->getShopDir()))
                Storage::makeDirectory($ws->getShopDir());

            $header = [];
            $this->ws = $ws;
            $spreadsheet = $this->open($filename);
            $sheet = $spreadsheet->getSheet(0);
            // max_header_rows = 3
            for($i = 1; $i<=3; $i++) {
                // $this->sheet->setCellValueExplicitByColumnAndRow($i, $row_index, $value, DataType::TYPE_STRING);

                if (!empty((string)$sheet->getCellByColumnAndRow(1, $i)->getValue()))
                    $header[] = array_values($sheet->toArray(null, true, true, true)[$i]);
            }

            return $header;

        } catch(Throwable  $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


}
