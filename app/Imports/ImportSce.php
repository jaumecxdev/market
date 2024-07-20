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


class ImportSce
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 15,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 6,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'http://webservice.sce.es/ws/tarifa?usuario=jclara@mpespecialist.com&clave=jcla7677&tipo=3';
    const URI_OFFERS = 'http://webservice.sce.es/ws/tarifa?usuario=jclara@mpespecialist.com&clave=jcla7677&tipo=4';

    const IMPORT_TEXT = "<b>Importación de productos y ofertas</b> Fichero CSV.";

    const REJECTED_CATEGORIES = [
        'ACCESORIOS IMAGEN',
        'TINTAS ORIGINALES',
        'TAMBORES',
        'S.O.',
        'TONER ORIGINALES'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'sce'
            ],
            [
                'name'          => 'SCE',
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

                // Stock ?
                if (intval($row['H']) == 0) {
                    $no_stock++;
                    continue;
                }

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['H']);
                $cost = FacadesMpe::roundFloatEsToEn($row['L']);

                $name = utf8_decode(FacadesMpe::getString($row['C']));
                if (!isset($name) || $name == '') continue;

                $supplierCategoryName = utf8_decode($row['D']);
                if (in_array($supplierCategoryName, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryName,
                ],[
                    'name'                  => $supplierCategoryName,
                ]);

                if (!isset($supplier_category->category_id)) {
                    $unmapped_categories[$supplier_category->name] = false;
                }

                // Canon -> Supplier Params
                // 'supplier_id', 'brand_id', 'category_id', 'supplierSku', 'canon', 'rappel', 'ports'
                $canon = FacadesMpe::roundFloatEsToEn($row['K']);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['F'], $row['B']);
                if ($pn == '' && $ean == '') continue;

                $longdesc = utf8_decode(FacadesMpe::getText($row['O']));
                $weight = FacadesMpe::roundFloatEsToEn($row['I']);
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

                    if (isset($row['N']) && $row['N'] != '') {
                        $images = explode('|', $row['N']);
                        foreach ($images as $image) {
                            $product->updateOrCreateExternalImage($image);
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
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::FORMATS['offers']['columns'])
                return 'No tiene '.self::FORMATS['offers']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Stock ?
                if (intval($row['F']) == 0) {
                    $no_stock++;
                    continue;
                }

                $stock = intval($row['F']);
                $cost = FacadesMpe::roundFloatEsToEn($row['E']);

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['C'], $row['B']);
                if ($pn == '' && $ean == '') continue;

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



    static function getCategoryId(array $row)
    {
        try {
            return mb_substr(strval($row['D']), -64);

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function getCategoryName(array $row)
    {
        try {
            return mb_substr(strval($row['D']), 0, 255);

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['E'];
            if (in_array($brand_name, ['', 'SIN MARCA'])) return null;
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
            if ($brand_name == 'Sp grupo sage') $brand_name = 'Sage sp';
             */

            $brand_name = ucwords(strtolower($brand_name));

            return $brand_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getCategories(array $file_rows)
    {
        try {
            $supplier_categories = [];
            foreach($file_rows as $row) {
                //$row = str_getcsv($row, ';');

                $cat_name = $row['D'];
                //$cat_name = $row['E'] ?? (isset($row['B']) && isset($row['D'])) ? ($row['B'].' / '.$row['D']) : null;
                if ($cat_name) {
                    $supplier_categories[$cat_name] = $cat_name;

                    /* $supplier_category = SupplierCategory::updateOrCreate([
                        'supplier_id'           => $this->supplier->id,
                        'supplierCategoryId'    => mb_substr(ucwords(strtolower($cat_name)), -64),
                    ],[
                        'name'                  => mb_substr(ucwords(strtolower($cat_name)), 0, 255)
                    ]); */
                }
            }

            return $supplier_categories;

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getBrands(array $file_rows)
    {
        try {
            $supplier_brands = [];
            foreach($file_rows as $row) {
                $brand_name = $row['E'];
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
                if ($brand_name == 'Sp grupo sage') $brand_name = 'Sage sp';
                $brand_name = ucwords($brand_name); */

                $supplier_brands[$brand_name] = null;
            }


            return array_keys($supplier_brands);

        } catch (Throwable $th) {
            return null;
        }
    }

}
