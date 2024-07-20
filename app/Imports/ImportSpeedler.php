<?php

namespace App\Imports;

use App\Brand;
use App\Image;
use App\Supplier;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;


class ImportSpeedler
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 14,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 14,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'https://www.speedler.es/module_actions/call_module/marketplaces/download/6/secure:2c80ba37b3268884a30c305b320b3274ff392618';
    const URI_OFFERS = 'https://www.speedler.es/module_actions/call_module/marketplaces/download/6/secure:2c80ba37b3268884a30c305b320b3274ff392618';

    const IMPORT_TEXT = "<b>Importación de productos y ofertas</b> Mismo CSV.";

    const REJECTED_CATEGORIES = [
        'CABLES FIBRA ÓPTICA',
        'CABLES PC USB',
        'CABLES RJ-45',
        'BOBINAS',
        'GAMA ECO / OFFICE',
        'GAMA PRO / GAMING',
        'IMPRESORAS DE TICKETS - TPV',
        'IMPRESORAS MATRICIALES',
        'IMPRESORAS PARA TPV',
        'LECTORES DE CÓDIGO DE BARRAS',
        'LECTORES DE MOSTRADOR',
        'NÁUTICA',
        'SISTEMAS OPERATIVOS PARA SERVIDORES',
        'SOPORTES PARA RACKS & ARMARIOS',
        'SWITCH GESTIONADO',
        'TPV COMPACTO ALL IN ONE',
        'TPV DETECTOR DE BILLETES',
        'PANTALLAS TPV INFORMACIÓN',
        'ACCESORIOS TORQUEEDO',
        'BATERIA Y CARGADORES TORQUEEDO',
        'CABLES - ADAPTADORES',
        'SOPORTES PARA RACKS - ARMARIOS',
        'AURICULARES PARA TELÉFONOS CONVENCIONALES',
        'OTROS'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'speedler'
            ],
            [
                'name'          => 'Speedler',
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
                return 'No tiene '.self::FORMATS['products']['columns']. ' columnas.';

            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Stock
                if (!$row['H'] || $row['H'] == 0) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $name = utf8_decode(FacadesMpe::getString($row['B']));
                if (!isset($name) || $name == '') continue;

                $stock = intval($row['H']);
                $cost = FacadesMpe::roundFloatEsToEn($row['C']);
                $cost = $cost / 1.21;

                $supplierCategoryName = utf8_decode($row['D']);
                if (!$supplierCategoryName || in_array($supplierCategoryName, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryName,
                ],[
                    'name'                  => $supplierCategoryName,
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->name] = false;

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['G'], $row['E']);
                //$pn = FacadesMpe::getPn($row['J']);
                //$ean = FacadesMpe::getEAN($row['I']);
                $longdesc = utf8_decode(FacadesMpe::getText($row['J']));
                $weight = FacadesMpe::roundFloatEsToEn($row['K']);

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
                        $name, $longdesc, $cost, 21, $stock, $weight,
                        null, null, null, null, null, null, null, null, null, null, null, null, null, null);

                    if (isset($row['I']) && $row['I'] != '') {
                        if (strpos($row['I'], '.jpg'))
                            $product->updateOrCreateExternalImage($row['I']);
                        else {
                            try {
                                $contents = file_get_contents($row['I']);
                            } catch (Throwable $th) {
                                $contents = null;
                            }

                            if ($contents) {
                                $directory = ('public/img/' . $product->id . '/');
                                $type = $product->getNextImageType();
                                $imageName = strval($type). '.jpg';

                                Storage::put($directory.$imageName, $contents);

                                $image = Image::updateOrCreate(
                                    [
                                        'product_id'    => $product->id,
                                        'src'           => $imageName,
                                        'type'          => $type,
                                    ]
                                );
                            }
                        }
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
                return 'No tiene '.self::FORMATS['offers']['columns']. ' columnas.';

            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Stock
                if (!$row['H'] || $row['H'] == 0) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['H']);
                $cost = FacadesMpe::roundFloatEsToEn($row['C']);
                $cost = $cost / 1.21;

                [$pn, $ean] = FacadesMpe::getPnEan($row['G'], $row['E']);
                $product = $supplier->getSimilarProduct(1, $brand->id, $pn, $ean);

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


    /* static function getCategoryId(array $row)
    {
       try {
            return strval(intval($row['D']));

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    } */


    /* static function getSupplierCategoryName(array $row)
    {
        try {
            $cat_name = $row['E'] ?? (isset($row['B']) && isset($row['D'])) ?
                ($row['B'].' / '.$row['D']) : (isset($row['B']) ? $row['B'] : null);

            return $cat_name;

        } catch (Throwable $th) {
            return null;
        }
    }
 */

    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['F'];
            if ($brand_name == '') return null;

            $brand_name = str_replace(['!', '.'], '', $brand_name);
            $brand_name = ucwords(strtolower($brand_name));

            if ($brand_name == 'Crucial Technology') $brand_name = 'Crucial';
            if ($brand_name == 'Raspberry') $brand_name = 'Raspberry Pi';
            if ($brand_name == 'Thermaltak') $brand_name = 'Thermaltake';

            return $brand_name;

        } catch (Throwable $th) {
            return null;
        }
    }


   /*  private function getCategories(array $file_rows)
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
 */
}
