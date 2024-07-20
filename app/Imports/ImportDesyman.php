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


class ImportDesyman
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 38,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 19,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'https://app.desyman.com/web/tarifas/csv20/?q=WVdOMGFXOXVQWFJoY21sbVlYTW1ZejB4TXpnek1RPT0';
    const URI_OFFERS = 'https://app.desyman.com/web/tarifas/csvreducido/?q=WVdOMGFXOXVQWFJoY21sbVlYTW1ZejB4TXpnek1RPT0';

    const IMPORT_TEXT = "<b>Importación de productos y ofertas</b> Fichero CSV.";

    const REJECTED_CATEGORIES = [
        '23',
        '28',
        '114',
        '113',
        '117',
        '116',
        '25',
        '22',
        '115',
        '156',
        '160',
        '132',
        '24',
        '21',
        '27',
        '19',
        '148'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'desyman'
            ],
            [
                'name'          => 'Desyman',
                'type_import'   => 'file',
                'ws'            => 'SupplierImportWS'
            ]
        );
    }



    public function importProducts(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['products']['header_rows']);

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
            if (count($productsCollect->first()) != self::FORMATS['products']['columns'])
                return 'No tiene '.self::FORMATS['products']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Available ?
                //if (!$row['Z'] || !$row['AA']) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['D']);
                $cost = FacadesMpe::roundFloat($row['J']);

                $name = FacadesMpe::getString($row['E']);
                if (!isset($name) || $name == '') continue;

                $supplierCategoryId = self::getSupplierCategoryId($row);
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => self::getCategoryName($row) ?? $supplierCategoryId,
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = $supplier_category->name;

                // Canon -> Supplier Params
                // 'supplier_id', 'brand_id', 'category_id', 'supplierSku', 'canon', 'rappel', 'ports'
                //$canon = floatval($row['S']);
                $canon = FacadesMpe::roundFloat($row['S']);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');

                //$tax = floatval($row['AK']);
                $tax = FacadesMpe::roundFloat($row['AK']);

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['C'], $row['B']);
                //$pn = FacadesMpe::getPn($row['J']);
                //$ean = FacadesMpe::getEAN($row['I']);
                $longdesc = FacadesMpe::getText($row['F']);
                //$weight = floatval($row['I']);
                $weight = FacadesMpe::roundFloat($row['I']);

                $product = $supplier->getSimilarProduct(1, $brand->id, $pn, $ean);

                // Update cost & stock
                if (isset($product)) {

                    $product->updateCostStock($supplierSku,
                        $cost,
                        $tax,
                        1,
                        $stock,
                        $brand->id,
                        $supplier_category->id,
                        $supplier_category->category_id ?? null,
                        1
                    );

                    if ($product->name == '' && isset($name) && $name != '') {
                        $product->name = $name;
                        $product->save();
                    }

                }
                // Create new product
                else {

                    $product = $supplier->updateOrCreateProduct($pn, $ean, null, null, null, $supplierSku,
                        $brand->id, $supplier_category->id, $supplier_category->category_id ?? null, 1, 1,
                        $name, $longdesc, $cost, $tax, $stock, $weight,
                        null, null, null, null, null, null, null, null, null, null, null, null, null, null);

                    if (isset($row['R']) && $row['R'] != '') {
                        $product->updateOrCreateExternalImage($row['R']);
                        if (isset($row['AG']) && $row['AG'] != '')
                            $product->updateOrCreateExternalImage($row['AG']);
                        if (isset($row['AH']) && $row['AH'] != '')
                            $product->updateOrCreateExternalImage($row['AH']);
                        if (isset($row['AI']) && $row['AI'] != '')
                            $product->updateOrCreateExternalImage($row['AI']);
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
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['offers']['header_rows']);

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
            if (count($productsCollect->first()) != self::FORMATS['offers']['columns'])
                return 'No tiene '.self::FORMATS['offers']['columns']. ' columnas. Tiene '.count($productsCollect->first());

                $no_stock = $no_cost = 0;
                $imported_count = 0;
                $imported = [];
                foreach($productsCollect as $row) {

                    // Stock
                    if (!$row['D'] || $row['D'] == 0) continue;

                    $stock = intval($row['D']);
                    $cost = FacadesMpe::roundFloat($row['H']);

                    $supplierSku = $row['A'];
                    [$pn, $ean] = FacadesMpe::getPnEan($row['C'], $row['B']);

                    if ($ean && $ean != '')
                        $product = $supplier->getSimilarProduct(1, null, null, $ean);
                    elseif ($pn && $pn != '')
                        $product = $supplier->products()->where('supplierSku', $supplierSku)->wherePn($pn)->first();

                    // Update cost & stock
                    if (isset($product)) {

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
                return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function getSupplierCategoryId(array $row)
    {
        try {
            return strval(intval($row['M']));

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function getCategoryName(array $row)
    {
        try {
            /* $cat_name = $row['E'] ?? (isset($row['B']) && isset($row['D'])) ?
                ($row['B'].' / '.$row['D']) : (isset($row['B']) ? $row['B'] : null); */

            $cat_name = ($row['P'].' / '.$row['N']);

            return $cat_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['H'];
            if ($brand_name == '') return null;

            $brand_name = ucwords(strtolower($brand_name));

            if ($brand_name == 'Asus.') $brand_name = 'Asus';

            return $brand_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    private function getCategories(array $file_rows)
    {
        try {
            $supplier_categories = [];
            foreach($file_rows as $row) {
                //$row = str_getcsv($row, ';');

                $cat_code = strval(intval($row['C']));
                $cat_name = $row['E'] ?? (isset($row['B']) && isset($row['D'])) ? ($row['B'].' / '.$row['D']) : null;
                if ($cat_name) {
                    $supplier_categories[$cat_code] = $row['B'].' / '.$row['D'];

                    $supplier_category = SupplierCategory::updateOrCreate([
                        'supplier_id'           => $this->supplier->id,
                        'supplierCategoryId'    => mb_substr($cat_code, -64),
                    ],[
                        'name'                  => mb_substr(($row['B'].' / '.$row['D']), 0, 255)
                    ]);
                }
            }

            return $supplier_categories;

        } catch (Throwable $th) {
            return null;
        }
    }


    private function getBrands(array $file_rows)
    {
        try {
            $supplier_brands = [];
            foreach($file_rows as $row) {
                $brand_name = $row['H'];
                if ($brand_name == '') continue;
                //if ($brand_name == 0) continue;

                /* if (in_array($brand_name, ['Tp - link', 'D - link', 'L - link', 'Club - 3d'])) $brand_name = str_replace(' ', '', $brand_name);
                if ($brand_name == 'Western digital wd') $brand_name = 'Western Digital';
                if ($brand_name == 'The g - lab') $brand_name = 'The G-Lab';
                if ($brand_name == 'Hpe hewlett packard enterprise') $brand_name = 'Hpe';
                if ($brand_name == 'Be quiet !') $brand_name = 'Be quiet';
                if ($brand_name == 'Metrologic - honeywell') $brand_name = 'Honeywell';
                if ($brand_name == 'Atrisoft soluciones d movilidad') $brand_name = 'Atrisoft';
                if ($brand_name == 'Motorola - symbol') $brand_name = 'Motorola';
                if ($brand_name == 'Epson 2') $brand_name = 'Epson';
                if ($brand_name == 'Apple 2') $brand_name = 'Apple';
                if ($brand_name == 'Samsung 2') $brand_name = 'Samsung';
                if ($brand_name == 'Sp grupo sage') $brand_name = 'Sage sp'; */
                $brand_name = ucwords(strtolower($brand_name));

                $supplier_brands[$brand_name] = null;
            }


            return array_keys($supplier_brands);

        } catch (Throwable $th) {
            return null;
        }
    }

}
