<?php

namespace App\Imports;

use App\Brand;
use App\Product;
use App\Supplier;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Support\Collection;
use Throwable;


class ImportVinzeo
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers', 'importStocks', 'importCategories'];

    public static $formats = [
        'products'    => [
            'columns'       => 24,
            'header_rows'   => 1,
            'filename'      => 'Tarifas/ARTMAASC_V2.txt',

        ],
        'offers'    => [
            'columns'       => 11,
            'header_rows'   => 1,
            'filename'      => 'Tarifas/PRIASC.txt',

        ],
        'stocks'    => [
            'columns'       => 6,
            'header_rows'   => 1,
            'filename'      => 'Tarifas/STOCASC.txt',

        ],
        'categories'    => [
            'columns'       => 6,
            'header_rows'   => 1,
            'filename'      => 'Tarifas/PFPG.txt',

        ],
    ];

    const FTP_DISK = 'ftp_vinzeo';

    const IMPORT_TEXT = "<b>Importación de productos</b> 3 importaciones CSV TXT.<br>".
        "1o STOCASC.TXT Stocks(lo crea o no) 2o PRIASC.TXT Ofertas 3o ARTMAASC_V2.TXTProductos.<br>".
        "<b>Importación de categorias</b> PFPG";

    const REJECTED_CATEGORIES = [
       'COMCO01',
       'IMPIM06',
       'ORDPO07',
       'ORDPO02',
       'ORDPO10',
       'ORDTA03',
       'PERMO02',
       'REDCO07',
       'REDCO06',
       'REDSA03',
       'INFAL05',
       'INFNE02',
       'TELAC01',
       'TELAC02',
       'TELAC03',
       'TELAC04',
       'TELAC06',
       'TELAC05',
       'TELWE03',
       'ORDPC02',
       'ORDPO03',
       'IMGPR03',
       'IMGPR02',
       'COMCA01',
       'COMCA02',
       'COMCA03',
       'ORDPC03',
       'IMGTV04',
       'PERMO03',
       'REDSA04',
       'REDSA02',
       'REDSA06',
       'REDSA05',
       'REDAR01',
       'REDGA01',
       'INFOP05',
       'INFSE04',
       'INFSO03',
       'INFSO05',
       'INFOP02',
       'INFOP04',
       'INFNE01',
       'INFSO01',
       'INFSO02',
       'INFSE03',
       'INFSO04',
       'INFSE02',
       'REDCA02',
       'REDSA09',
       'REDSA07',
       'CONTI01',
       'CONTI02',
       'IMPIM07',
       'IMPES07',
       'IMPMF04',
       'IMPPO03',
       'IMPMF03',
       'INFSE01',
       'IMPPO02',
       'IMPSE01',
       'ORDPO08',
       'PERAL01',
       'CONPA02',
       'IMGGP03',

       'INFAL03',
       'INFAL02',
       'INFAL01',
       'INFAL04',

       'ORDTA02',
       'CONTO01',
       'INFOP01',
       'REDSA08',
       'IMPCO01',
       'IMPCO02',
       'IMPCO03',
       'IMPCO04',
       'IMPCO05',
       'PAEHE01',
       'PAECP01',
       'PAECO01',
       'PAEPU01',
       'PAEAS01',
       'PAEIL01'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'vinzeo'
            ],
            [
                'name'          => 'Vinzeo',
                'type_import'   => 'file',
                'ws'            => 'SupplierImportFtpWS'
            ]
        );
    }



    static function getSupplierCategoryId(array $row)
    {
        // G, H, I
        try {
            return strval($row['G']) . strval($row['H']) . strval($row['I']);

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['D'];
            if ($brand_name == '') return null;

            if ($brand_name == 'Toshiba-Dynabook') $brand_name = 'Dynabook';

            return $brand_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    public function importProducts(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::$formats['products']['header_rows']);

            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows)->keyBy((string)$this->supplier->supplierSku_field);
            $productsCollect = $this->supplier->filterProducts($productsCollect);
            unset($file_rows);

            return self::products($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function products(Supplier $supplier, Collection $productsCollect)
    {
       try {
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['products']['columns'])
                return 'No tiene '.self::$formats['products']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $name = FacadesMpe::getString($row['C']);
                if (!isset($name) || $name == '') continue;

                $supplierSku = $row['B'];
                if (strpos(strtoupper($name), 'PPLECARE')) {
                    if ($product = $supplier->products()->firstWhere('supplierSku', $supplierSku)) {
                        $product->ready = 0;
                        $product->save();
                    }

                    continue;
                }

                $supplierCategoryId = self::getSupplierCategoryId($row);
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => $supplierCategoryId,
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = false;

                $canon = FacadesMpe::roundFloat($row['O']);
                $canon = ($canon != 0) ? $canon / 1000 : 0;
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');


                [$pn, $ean] = FacadesMpe::getPnEan($row['E'], $row['F']);
                $longdesc = $name;
                $weight = floatval($row['K']);
                $length = floatval($row['V']);
                $width = floatval($row['W']);
                $height = floatval($row['X']);

                $product = $supplier->products()->firstWhere('supplierSku', $supplierSku);

                // Update cost & stock
                if (isset($product) &&
                    (!isset($product->ean) || !isset($product->pn) || !isset($product->brand_id)
                    || !isset($product->supplier_category_id) || !isset($product->category_id))) {

                    $product->pn = $pn;
                    $product->ean = $ean;
                    $product->brand_id = $brand->id ?? null;
                    $product->supplier_category_id = $supplier_category->id;
                    $product->category_id = $supplier_category->category_id ?? null;
                    $product->name = $name;
                    $product->longdesc = $longdesc;
                    $product->weight = $weight;
                    $product->length = $length;
                    $product->width = $width;
                    $product->height = $height;
                    $product->save();

                    $product->getMPEProductImages();

                    $imported[] = $product->id;
                    $imported_count++;
                }
            }

            if ($productsCollect->count())
                if ($products_4_delete = $supplier->products()->whereNull('pn')->whereNull('ean')->whereNull('brand_id')->whereNull('supplier_category_id')->get())
                foreach ($products_4_delete as $product_4_delete)
                    $product_4_delete->deleteSecure();
                //$supplier->products()->whereNull('pn')->whereNull('ean')->whereNull('brand_id')->whereNull('supplier_category_id')->delete();

            unset($productsCollect);
            $msg = 'PN EAN Marca categorias y nombres Importados '.$imported_count. ' productos.';
            if ($no_stock != 0) $msg .= ' Productos sin stock: '.$no_stock;
            if ($no_cost != 0) $msg .= ' Productos con coste inferior: '.$no_cost;
            if (count($unmapped_categories)) $msg .= ' Categorias sin mapear: '.json_encode($unmapped_categories);
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importOffers(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::$formats['offers']['header_rows']);

            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows)->keyBy((string)$this->supplier->supplierSku_field);
            unset($file_rows);

            return self::offers($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function offers(Supplier $supplier, Collection $productsCollect)
    {
        try {
            // test array
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['offers']['columns'])
                return 'No tiene '.self::$formats['offers']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            // FIRST IMPORT STOCKS
            $ftp_disk = self::FTP_DISK;
            $ftp_filename = self::$formats['stocks']['filename'];
            $header_rows = self::$formats['stocks']['header_rows'];
            $directory = $supplier->storage_dir.'stocks/';
            $filename = date('Y-m-d_H'). '_stocks.csv';

            $file_rows = FacadesMpeImport::getRowsFtp($ftp_disk, $ftp_filename, $header_rows, $directory, $filename);

            $res_stocks = null;
            if (is_array($file_rows)) {
                $stocksCollect = collect($file_rows)->keyBy((string)$supplier->supplierSku_field);
                unset($file_rows);
                $res_stocks = self::stocks($supplier, $stocksCollect);
                unset($stocksCollect);
            }

            // THEN IMPORT PRICES
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                $supplierSku = $row['B'];
                $cost = floatval($row['C']);
                $cost = ($cost != 0) ? $cost / 1000 : 0;

                $product = $supplier->products()->firstWhere('supplierSku', $supplierSku);

                if ($product) {
                    $product->cost = $cost;
                    $product->tax = 21;
                    $product->currency_id = 1;
                    $product->status_id = 1;
                    $product->save();

                    $imported[] = $product->id;
                    $imported_count++;
                }
            }

            unset($productsCollect);

            $deleteds = 0;
            $for_delete = $supplier->products()->where('cost', '<', 30)->get();
            foreach ($for_delete as $product) {
                $product->deleteSecure();
                $deleteds++;
            }

            $msg = 'Importados '.$imported_count. ' productos.';
            if ($no_stock != 0) $msg .= ' Productos sin stock: '.$no_stock;
            if ($no_cost != 0) $msg .= ' Productos con coste inferior: '.$no_cost;
            if ($res_stocks) $msg .= ' Resultado Stocks: '.$res_stocks;
            if ($deleteds) $msg .= ' Eliminados por precio < 30€: '.$deleteds;
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importStocks(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::$formats['stocks']['header_rows']);

            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows)->keyBy((string)$this->supplier->supplierSku_field);
            unset($file_rows);

            return self::stocks($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function stocks(Supplier $supplier, Collection $productsCollect)
    {
        try {
            // test array
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['stocks']['columns'])
                return 'No tiene '.self::$formats['stocks']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $no_stock = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                $supplierSku = $row['B'];
                $stock = floatval($row['E']);
                $stock = ($stock != 0) ? intval($stock / 100) : 0;
                if ($stock > 5) {

                    $product = $supplier->products()->firstWhere('supplierSku', $supplierSku);

                    // Update cost & stock
                    if (isset($product)) {

                        $product->stock = $stock;
                        $product->save();

                        $imported[] = $product->id;
                        $imported_count++;
                    }
                    // Create new product
                    elseif (strpos(trim($row['F']), 'Disposi')) {   // 'Libre Disposición'

                        $product = Product::create([
                            'supplier_id'   => $supplier->id,
                            'supplierSku'   => $supplierSku,
                            'name'          => $supplierSku,
                            'stock'         => $stock,
                            'status_id'     => 1,
                        ]);

                        $imported[] = $product->id;
                        $imported_count++;
                    }
                }
            }

            unset($productsCollect);

            if (count($imported))
                $supplier->products()->whereNotIn('products.id', $imported)->update(['stock' => 0]);

            $msg = 'Importados '.$imported_count. ' productos.';
            if ($no_stock != 0) $msg .= ' Productos sin stock: '.$no_stock;
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importCategories(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];

            if (!$file_rows = file($uploaded_file->getPathname()))
                return null;

            return self::categories($this->supplier, $file_rows);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function categories(Supplier $supplier, $file_rows)
    {
        try {
            // test array
            if (!isset($file_rows) || !count($file_rows))
                return 'No hay filas para importar.';

            $imported_count = 0;
            foreach ($file_rows as $line) {

                $row = str_getcsv($line, "\t");
                if (count($row) != self::$formats['categories']['columns'])
                    return 'No tiene '.self::$formats['categories']['columns']. ' columnas. Tiene '.count($row);

                if ($supplierCategoryId = mb_convert_encoding ($row[5], 'UTF-8', 'ISO-8859-1')) {
                    $suppliercategoryName = mb_convert_encoding ($row[0], 'UTF-8', 'ISO-8859-1').
                        ' / '. mb_convert_encoding ($row[2], 'UTF-8', 'ISO-8859-1').
                        ' / ' .mb_convert_encoding ($row[4], 'UTF-8', 'ISO-8859-1');

                    if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                    $supplier_category = SupplierCategory::updateOrCreate([
                        'supplier_id'           => $supplier->id,
                        'supplierCategoryId'    => $supplierCategoryId,
                    ],[
                        'name'                  => $suppliercategoryName,
                    ]);
                    if (!isset($supplier_category->category_id))
                        $unmapped_categories[$supplier_category->supplierCategoryId] = $suppliercategoryName;

                    $imported_count++;
                }
            }

            $msg = 'Actualizadas '.$imported_count. ' categorias.';

            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }

}
