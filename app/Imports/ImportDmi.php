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


class ImportDmi
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 28,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 28,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    // Una consulta cada 60 minuts
    const URI_PRODUCTS = 'https://www.dmi.es/catalogo.aspx?u=CT084841&p=suhkhaqk';
    const URI_OFFERS = 'https://www.dmi.es/catalogo.aspx?u=CT084841&p=suhkhaqk';

    const IMPORT_TEXT = "<b>Importación de productos i ofertas</b> Mismo fichero.";

    const REJECTED_CATEGORIES = [
        '0-1170',
        '999958-1848',
        '999939-274',
        '999933-1869',
        '999994-229',
        '999941-7',
        '999994-1675',
        '830-883',
        '225-377',
        '999939-159',
        '225-1010',
        '999951-156',
        '7414-1653',
        '999933-920',
        '999980-3054',
        '242-832',
        '999934-7188',
        '830-953',
        '999982-1347',
        '225-905',
        '999970-381',
        '999969-12',
        '999960-2674',
        '999994-1727',
        '999940-62',
        '242-833',
        '225-375',
        '999960-1297',
        '999934-240',
        '999987-1532',
        '999937-1008',
        '2868-1668',
        '999953-1399',
        '242-4982',
        '830-2918',
        '150-5632'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'dmi'
            ],
            [
                'name'          => 'Dmi',
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

            if ($productsCollect && $productsCollect->count() == 1) return implode(' ', $productsCollect->first());

            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Available ?
                //if (in_array(trim($row['N']), ['48 horas', 'agotado'])) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['M']);
                $cost = floatval($row['G']);
                if ($stock < 5 || $cost < 50) continue;

                $name = FacadesMpe::getString($row['K']);
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
                $canon = floatval($row['E']);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['N'], $row['Q']);
                $longdesc = FacadesMpe::getText($row['P']);

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

                    if ($product->name == '' && isset($name) && $name != '') {
                        $product->name = $name;
                        $product->save();
                    }

                }
                // Create new product
                else {

                    $product = $supplier->updateOrCreateProduct($pn, $ean, null, null, null, $supplierSku,
                        $brand->id, $supplier_category->id, $supplier_category->category_id ?? null, 1, 1,
                        $name, $longdesc, $cost, 21, $stock, null,
                        null, null, null, null, null, null, null, null, null, null, null, null, null, null);

                    if (isset($row['Z']) && trim($row['Z']) != '')
                        $product->updateOrCreateExternalImage(trim($row['Z']));
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

            //$no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Available ?
                //if (in_array(trim($row['N']), ['48 horas', 'agotado'])) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['M']);
                $cost = floatval($row['G']);
                if ($stock < 5 || $cost < 50) continue;

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['N'], $row['Q']);

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
            //if ($no_stock != 0) $msg .= ' Productos sin stock: '.$no_stock;
            //if ($no_cost != 0) $msg .= ' Productos con coste inferior: '.$no_cost;
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['J'];

            if ($brand_name == '') return null;
            //if (in_array($brand_name, ['Sin marca'])) return null;

            return $brand_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getSupplierCategoryId(array $row)
    {
        try {
            return strval($row['R']).'-'.strval($row['S']);

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getSupplierCategoryName(array $row)
    {
        try {
            $scname = strval($row['I']).' / '.strval($row['T']);

            return $scname;

        } catch (Throwable $th) {
            return null;
        }
    }


}
