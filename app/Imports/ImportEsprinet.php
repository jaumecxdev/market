<?php

namespace App\Imports;

use App\Brand;
use App\Supplier;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Support\Collection;
use Throwable;


class ImportEsprinet
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers', 'importCategories'];

    public static $formats = [
        'products'    => [
            'columns'       => 31,
            'header_rows'   => 1,
            'filename'      => 'WD_Catalogo_K83389.csv',

        ],
        'offers'    => [
            'columns'       => 31,
            'header_rows'   => 1,
            'filename'      => 'WD_Catalogo_K83389.csv',

        ],
        'categories'    => [
            'columns'       => 31,
            'header_rows'   => 1,
            'filename'      => 'WD_Catalogo_K83389.csv',

        ],
    ];

    const FTP_DISK = 'ftp_esprinet';

    const IMPORT_TEXT = "<b>Importación de productos, ofertas y categorias</b> Mismo fichero FTP";

    const REJECTED_CATEGORIES = [
        'VFYC2', 'VFYF2', 'VFYR2', 'VFY82',                                 // SOFTWARE
        'ESE01', 'ESE02', 'ESE04', 'ESE30',                                 // EXTENSION GARANTIAS
        'ESSE3', 'ESSE5', 'ESSEF', 'ESSEG', 'ESSEC', 'ESSED', 'ESSE6',      // EXTENSION GARANTIAS
        '9A9A0',
        'MCN74', 'MCN69',        // PAPEL / CONSUMIBLES / Consumibles
        'AXAY3', 'AXAX8',        // SERVIDORES Y SISTEMAS / Accesorios Servidores / Accesorios Varios
        '3G3GA',            // Switch Fibre Channel
        '4A4A0',            // Accesorios seguridad
        '9A9A0', '9A9A1', '9A9A2', '9A9A3',            // Switch
        '9B9B1', '9B9B2',            // Switch
        '9C9C0', '9D9D0', '9D9D1',             // Switch
        '9E9', '9E90', '9E90000',             // Switch
        '9F9F1', '9F9F2', '9F9F4', '9F9F4',             // Switch
        '9G9G0', '9G9G1', '9G9G2', '9G9G3', '9G9G4',            // Switch
        '9J9J0',
        '9K9K0',
        '9L9L1',
        '9M9M0', '9M9M1', '9M9M5',            // Switch
        '9P9P0', '9P9P2',             // Switch
        '9Q9Q0',            // Switch

        'ADA0P', 'ADA0R', 'ADA56', 'ADA61', 'ADA63', 'ADA66', 'ADA68',             // Switch
        'ADA71', 'ADA72',            // Switch
        'ADA90', 'ADA91', 'ADA92', 'ADA94', 'ADA95',            // Switc
        'ADA9U', 'ADA9V', 'ADA9Y', 'ADA9Z',           // Switch
        'ADAD3',

        'AFAF1', 'AFAF7', 'AFAF8', 'AFAF9',            // Switch
        'AFAG1', 'AFAG2',            // Switch

        '9E900',
        'AXAT1', 'AXAX9',
        'BLBL1',
        'BVJG9', 'BVJN9', 'BVJX9', 'BXJE1',
        'BXJG1', 'BXY01', 'BXY61', 'BXYA1', 'BXYZ1',
        'EEEZ0',
        'ESE03', 'ESE07', 'ESSE7', 'ESSEA',
        'IKIK0',
        'NFNF3', 'NFNF5',
        'SASA5', 'SBSB8',
        'SES0Q', 'SES12', 'SEW0H', 'SEW0Z', 'SEW2H', 'SEW2I', 'SES05', 'SES0F', 'SEW3I',
        'STNSA', 'STSTA', 'SXSX0',
        'VFJ22', 'VFJ32', 'VFJ62', 'VFJB2', 'VFJE2', 'VFJF2', 'VFJG2', 'VFJK2', 'VFJL2', 'VFJM2', 'VFJN2',
        'VFJR2', 'VFJX2', 'VFY02', 'VFY62', 'VFYA2', 'VFYB2', 'VFYD2', 'VFYE2', 'VFYH2', 'VFYI2', 'VFYO2', 'VFYY2',
        'XCA46', 'XCA78', 'XHXHB',
        'YEY45',
        '$M$M9', '01003',
        '09431', '42422',
        '0A0A0', 'AVA44', 'AWAW8', 'BVJC9', 'BXJ01', 'BXJA1',
        'C3C3C', 'C3C3E', 'C6C6D', 'C6C6F',
        'EEEEA', 'EEEEE', 'EFEF5', 'EFEFA', 'EFEFB', 'EFEFD', 'EGEG0', 'EGEG1', 'EGEG2', 'EHEH0', 'EHEH1', 'EHEH2',
        'ENEN1', 'ESES4', 'GCGC1', 'GCGC2',
        'MCN64', 'MCN67', 'MCN72', 'MCN73', 'MCN79', 'MCN80', 'MCN81', 'MCN8B',
        'NNNN1', 'NNNN2', 'NRNR9', 'NSA15', 'NSNS8', 'NSNS9', 'SWD72',
        'VFYZ2', 'WTWT0', 'WTWT1', 'X0A0H', 'XDA36',
        'XGA24', 'XGA54', 'XGA59', 'XGA80',
        'XHA0I', 'XHXHD',
        'XTXTA',
        'Y3Y32',
        'Y8W1M',
        'YEAD4', 'YEW14', 'YEW15', 'YTW1F', 'YUYU1',
        '1P1PE', 'AAA10', 'AMAQ5',
        '02470', '04441', '2D2DC', 'AAA10', 'AEAE3', 'APA28', 'APA45', 'APA48', 'APA84', 'CCCD4', 'CCCDC', 'CSCSB',
        'CXCX1', 'HLHLA', 'HLHLB', 'HLHLE', 'MIMI9', 'MIMIO', 'MMMM7', 'TQTQ3', 'XOXOD',
        'AJA88', 'ANANE', 'PCPD1',
        'AMAQZ', 'FCFC0', 'TNTE4', 'TQTQ0', 'ANANA', 'AMAQJ', 'P1P1A', 'NZNZ3', 'YTW0N', 'QTTW2', '3E3', 'AKA9C', 'C4C4E',
        'AXAX1', 'SXSX9', 'VFJZ2', '3E',
        'GBGB2', 'YTW1E', 'XHXHA', 'ANAN4', 'YIW10', 'ESE09',
        '00001'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'esprinet'
            ],
            [
                'name'          => 'Esprinet',
                'type_import'   => 'file',
                'ws'            => 'SupplierImportFtpWS'
            ]
        );
    }


    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['F'];
            if ($brand_name == '') return null;

            if (strpos($brand_name, 'Reacondicionado') || $brand_name == 'Reacondicionado') return null;

            if ($brand_name == 'Toshiba Dynabook') $brand_name = 'Dynabook';
            if ($brand_name == 'Vogels') $brand_name = "Vogel's";

            return $brand_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getSupplierCategoryId(array $row)
    {
        try {
            if (in_array(strval($row['G']), ['3E', 'AK', 'C4', 'AX', 'SX', 'VF'])) return null;

            return strval($row['G']) . strval($row['I']);

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getSupplierCategoryName(array $row)
    {
        try {
            $scname = strval($row['AC']);
            if ($row['H']) $scname .= ' / ' .strval($row['H']);
            if ($row['J']) $scname .= ' / ' .strval($row['J']);

            return $scname;

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

                // DISPO
                if (!$row['K'] || $row['K'] == 0) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['K']);
                $cost = FacadesMpe::roundFloatEsToEn($row['X']);
                if (!$cost || $cost == '') $cost = FacadesMpe::roundFloatEsToEn($row['W']);
                if ($stock < 5 || $cost < 50) continue;

                $name = FacadesMpe::getString($row['E']);
                if (!isset($name) || $name == '') continue;

                $supplierCategoryId = self::getSupplierCategoryId($row);
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                $supplierCategoryName = self::getSupplierCategoryName($row);
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => $supplierCategoryName ?? $supplierCategoryId,
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = $supplier_category->name;

                // Canon -> Supplier Params
                $canon = FacadesMpe::roundFloatEsToEn($row['U']);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');


                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['B'], $row['C']);
                $longdesc = ($row['P'] != '') ? FacadesMpe::getText($row['P']) : FacadesMpe::getText($row['O']);
                $weight = FacadesMpe::roundFloatEsToEn($row['Y']);
                $length = floatval($row['AA']);
                $width = floatval($row['AB']);
                $height = floatval($row['Z']);

                $product = $supplier->getSimilarProduct(1, $brand->id, $pn, $ean);

                // Update cost & stock
                if (isset($product)) {

                    $product->updateCostStock($supplierSku,
                        $cost,
                        21,
                        1,
                        $stock,
                        $brand->id,
                        $supplier_category->id,
                        $supplier_category->category_id ?? null,
                        1
                    );

                    if (!$product->images->count() && isset($row['AE']) && $row['AE'] != '')
                        $product->updateOrCreateExternalImage($row['AE']);

                        if ($product->name == '' && isset($name) && $name != '') {
                            $product->name = $name;
                            $product->save();
                        }
                }
                // Create new product
                else {

                    $product = $supplier->updateOrCreateProduct($pn, $ean, null, null, null, $supplierSku,
                        $brand->id, $supplier_category->id, $supplier_category->category_id ?? null, 1, 1,
                        $name, $longdesc, $cost, 21, $stock, $weight,
                        $length, $width, $height, null, null, null, null, null, null, null, null, null, null, null);

                    if (isset($row['AE']) && $row['AE'] != '') {
                        $product->updateOrCreateExternalImage($row['AE']);
                    }
                    else
                        $product->getMPEProductImages();
                }

                $imported[] = $product->id;
                $imported_count++;
            }

            if (count($imported))
                $supplier->products()->whereNotIn('products.id', $imported)->update(['stock' => 0]);

            $msg = 'Importados '.$imported_count. ' productos.';
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

            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // DISPO
                if (!$row['K'] || $row['K'] == 0) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['K']);
                $cost = FacadesMpe::roundFloatEsToEn($row['X']);
                if (!$cost || $cost == '') $cost = FacadesMpe::roundFloatEsToEn($row['W']);
                if ($stock < 5 || $cost < 50) continue;

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['B'], $row['C']);

                if ($product = $supplier->getSimilarProduct(1, $brand->id, $pn, $ean)) {
                    $product->cost = $cost;
                    $product->stock = $stock;
                    $product->save();

                    $imported[] = $product->id;
                    $imported_count++;
                }
            }

            if (count($imported))
                $supplier->products()->whereNotIn('products.id', $imported)->update(['stock' => 0]);

            $msg = 'Importados '.$imported_count. ' productos.';
            if ($no_stock != 0) $msg .= ' Productos sin stock: '.$no_stock;
            if ($no_cost != 0) $msg .= ' Productos con coste inferior: '.$no_cost;
            if (count($unmapped_categories)) $msg .= ' Categorias sin mapear: '.json_encode($unmapped_categories);
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importCategories(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::$formats['products']['header_rows']);

            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows)->keyBy((string)$this->supplier->supplierSku_field);
            $productsCollect = $this->supplier->filterProducts($productsCollect);

            return self::categories($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function categories(Supplier $supplier, Collection $productsCollect)
    {
       try {
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['products']['columns'])
                return 'No tiene '.self::$formats['products']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $unmapped_categories = [];
            foreach($productsCollect as $row) {

                $supplierCategoryId = self::getSupplierCategoryId($row);
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                $supplierCategoryName = self::getSupplierCategoryName($row);
                $supplier_category = SupplierCategory::updateOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => $supplierCategoryName ?? $supplierCategoryId,
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = $supplier_category->name;
            }

            return 'Categorias importadas. UnMapped: '.json_encode($unmapped_categories);

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }

}
