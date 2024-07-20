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


class ImportMegasur
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 36,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 36,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'https://www.megasur.es/download/file-rate?file=csv-prestashop&u=302133&hash=ee250da3278921d93d5137feed610f11';
    const URI_OFFERS = 'https://www.megasur.es/download/file-rate?file=csv-prestashop&u=302133&hash=ee250da3278921d93d5137feed610f11';

    const IMPORT_TEXT = "<b>Importación de productos</b> Fichero CSV con formato Prestashop.";

    const REJECTED_CATEGORIES = [
        '1005',     //	Servidores
        '1007',     //	Accesorios ordenadores
        '1018',     //	Limpieza y mantenimiento
        '16009',    // Software / Licencias s.o. servidores
        '16002',    // Software / Paquetes integrados
        '35005',
        '24028',
        '24032',
        '35018',
        '35019',
        '35001',
        '35007',
        '8027',
        '16001',
        '16007',
        '3009',
        '2006',
        '22009',
        '23005',
        '35006',
        '20002',
        '21005',
        '16005',
        '32002',
        '26036',
        '35008',
        '20009',
        '26047',
        '26019',
        '26036',
        '20002',
        '3016',
        '28002',
        '10008',
        '9004',
        '17001',
        '15002',
        '15001',
        '22006',
        '8010',
        '8012',
        '35017',
        '29003',
        '2003',
        '2004',
        '16004',
        '15014',
        '19003',
        '28001',
        '1003',
        '28005',
        '15008',
        '6012',
        '13008'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'megasur'
            ],
            [
                'name'          => 'Megasur',
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
                if (!$row['Z'] || !$row['AA']) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['AH']);
                $cost = FacadesMpe::roundFloatEsToEn($row['N']);

                $name = FacadesMpe::getString($row['K']);
                if (!isset($name) || $name == '') continue;

                $status_id = 1;
                if (strpos($name, 'reacondicionado')) $status_id = 3;

                $supplierCategoryId = self::getCategoryId($row);
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
                $canon = FacadesMpe::roundFloatEsToEn($row['AG']);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');

                $supplierSku = $row['H'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['J'], $row['I']);
                //$pn = FacadesMpe::getPn($row['J']);
                //$ean = FacadesMpe::getEAN($row['I']);
                $longdesc = FacadesMpe::getText($row['M']);
                $weight = FacadesMpe::roundFloatEsToEn($row['X']);

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
                        $brand->id, $supplier_category->id, $supplier_category->category_id ?? null, $status_id, 1,
                        $name, $longdesc, $cost, 21, $stock, $weight,
                        null, null, null, null, null, null, null, null, null, null, null, null, null, null);

                    if (isset($row['AB']) && $row['AB'] != '')
                        $product->updateOrCreateExternalImage($row['AB']);
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

                // Available ?
                if (!$row['Z'] || !$row['AA']) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['AH']);
                $cost = FacadesMpe::roundFloatEsToEn($row['N']);
                [$pn, $ean] = FacadesMpe::getPnEan($row['J'], $row['I']);
                //$pn = FacadesMpe::getPn($row['J']);
                //$ean = FacadesMpe::getEAN($row['I']);

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


    static function getCategoryId(array $row)
    {
        try {
            return strval(intval($row['C']));

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function getCategoryName(array $row)
    {
        try {
            $cat_name = $row['E'] ?? (isset($row['B']) && isset($row['D'])) ?
                ($row['B'].' / '.$row['D']) : (isset($row['B']) ? $row['B'] : null);

            return $cat_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['F'];
            if ($brand_name == '') return null;
            if ($brand_name == 'Reware') return null;       // 2a
            //if ($brand_name == 0) continue;

            if (in_array($brand_name, ['Tp - link', 'D - link', 'L - link', 'Club - 3d'])) $brand_name = str_replace(' ', '', $brand_name);
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
            if ($brand_name == 'Sp grupo sage') $brand_name = 'Sage sp';
            $brand_name = ucwords($brand_name);

            if ($brand_name == 'Creative') $brand_name = 'Creative Labs';

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
                $brand_name = $row['F'];
                if ($brand_name == '') continue;
                //if ($brand_name == 0) continue;

                if (in_array($brand_name, ['Tp - link', 'D - link', 'L - link', 'Club - 3d'])) $brand_name = str_replace(' ', '', $brand_name);
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
                if ($brand_name == 'Sp grupo sage') $brand_name = 'Sage sp';
                $brand_name = ucwords($brand_name);

                $supplier_brands[$brand_name] = null;
            }


            return array_keys($supplier_brands);

        } catch (Throwable $th) {
            return null;
        }
    }

}
