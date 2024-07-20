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


class ImportAseuropa
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 24,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 24,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'https://www.aseuropa.com/descarga-tarifa?tipo=csv&user=008945&code=68de897b54bb24db4bc6fa987634fff0';
    const URI_OFFERS = 'https://www.aseuropa.com/descarga-tarifa?tipo=csv&user=008945&code=68de897b54bb24db4bc6fa987634fff0';

    const IMPORT_TEXT = "<b>Importación de productos</b> Fichero CSV con formato Prestashop.";

    const REJECTED_CATEGORIES = [
        '1100312154',
        '1100192186',
        '1100192183',
        '1500182044',
        '1800852296',
        '1500182188',
        '1600782041'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'aseuropa'
            ],
            [
                'name'          => 'Aseuropa',
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
            //$productsCollect = collect($file_rows)->keyBy((string)$this->supplier->supplierSku_field);
            $productsCollect = $this->supplier->filterProducts(collect($file_rows));

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

                // NO DROP -> ENVÍO DIRECTE DE FABRICANT
                if ($row['X'] == 'SI') continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['P']);
                if ($stock <= 0) continue;
                $cost = FacadesMpe::roundFloatEsToEn($row['M']);

                $name = FacadesMpe::getString($row['C']);
                if (!isset($name) || $name == '' || mb_strpos($name, 'reacondiciona')) continue;

                $supplierCategoryId = self::getSupplierCategoryId($row);
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => self::getSupplierCategoryName($row) ?? $supplierCategoryId,
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = $supplier_category->name;

                // Canon -> Supplier Params
                // 'supplier_id', 'brand_id', 'category_id', 'supplierSku', 'canon', 'rappel', 'ports'
                $canon = FacadesMpe::roundFloatEsToEn($row['N']);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');


                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['B'], $row['D']);
                //$pn = FacadesMpe::getPn($row['J']);
                //$ean = FacadesMpe::getEAN($row['I']);
                $longdesc = FacadesMpe::getText($row['U']);

                $weight = FacadesMpe::roundFloatEsToEn($row['Q']);

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

                    /* if ($product->name == '' && isset($name) && $name != '') {
                        $product->name = $name;
                        $product->save();
                    }
 */
                }
                // Create new product
                else {

                    $product = $supplier->updateOrCreateProduct($pn, $ean, null, null, null, $supplierSku,
                        $brand->id, $supplier_category->id, $supplier_category->category_id ?? null, 1, 1,
                        $name, $longdesc, $cost, 21, $stock, $weight,
                        null, null, null, null, null, null, null, null, null, null, null, null, null, null);

                    if (isset($row['S']) && $row['S'] != '')
                        $product->updateOrCreateExternalImage($row['S']);
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
            $productsCollect = collect($file_rows);     //->keyBy((string)$this->supplier->supplierSku_field);

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
                return 'No tiene '.self::FORMATS['offers']['columns']. ' columnas.';

            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Available ?
                //if (!$row['Z'] || !$row['AA']) continue;
                // NO DROP -> ENVÍO DIRECTE DE FABRICANT
                if ($row['X'] == 'SI') continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['P']);

                $cost = FacadesMpe::roundFloatEsToEn($row['M']);
                [$pn, $ean] = FacadesMpe::getPnEan($row['B'], $row['D']);

                $product = $supplier->getSimilarProduct(1, $brand->id, $pn, $ean);

                if ($product) {
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
            return strval(intval($row['I']));

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function getSupplierCategoryName(array $row)
    {
        try {
            $cat_name = $row['F'].' / '.$row['H'].' / '.$row['J'];

            return $cat_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['L'];
            if ($brand_name == '') return null;
            if ($brand_name == 'Reware') return null;       // 2a
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

            return $brand_name;

        } catch (Throwable $th) {
            return null;
        }
    }

}
