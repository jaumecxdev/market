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


class ImportDepau
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 23,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 23,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'https://www.depau.es/webservices/tarifa_completa/86c39206-144a-42e9-9ad4-5d5d2c1ff199/csv';
    const URI_OFFERS = 'https://www.depau.es/webservices/tarifa_completa/86c39206-144a-42e9-9ad4-5d5d2c1ff199/csv';

    const IMPORT_TEXT = "<b>Importación de productos</b> Fichero CSV con formato Tarifa General.";

    const REJECTED_CATEGORIES = [
        '510950',   // Accesorios Cargadores y Convertidores
        '60300',    // Accesorios de fotografia y video
        '30600',    // Accesorios de impresora, escaner y plotter
        '510440',   // Accesorios Instrumentación
        '150200',   // Accesorios SAIS
        '510530',   // Accesorios Sonda
        '440022',	// Accesorios Torqeedo
        '10307',    // Adaptadores de Disco duro
        '510620',   // Ais
        '80500',    // Ampliaciones de Garantia
        '510120',   // Componentes Pilotos
        '510430',   // Transductores Instrumentación
        '100100',   // Toner HP
        '100000',   // Consumibles HP
        '100104',   // Toner Brother
        '440020',   // Helices y Aletas Torqeedo
        '100006',
        '320125',
        '100102',
        '100101',
        '90001',
        '100004',
        '440021',
        '100108',
        '100001',
        '70340',
        '17',
        '440002',
        '440001',
        '510920',
        '440010',
        '510130',
        '70400',
        '110074',
        '100109',
        '80204',
        '100110',
        '470010',
        '390000',

        '110064',
        '160046',
        '391020',
        '80400'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'depau'
            ],
            [
                'name'          => 'Depau',
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
            // test array
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::FORMATS['products']['columns'])
                return 'No tiene '.self::FORMATS['products']['columns']. ' columnas.';

            //$discarded_brands = [];
            //$discarded_categories = [];
            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Activo ?
                //if (!$row['L']) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['I']);

                $cost = FacadesMpe::roundFloatEsToEn($row['C']);

                $name = FacadesMpe::getString($row['B']);
                if (!isset($name) || $name == '') continue;

                $supplierCategoryId = trim(strval(intval($row['G'])));
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                // 'supplier_id', 'category_id', 'name', 'supplierCategoryId'
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => strval($row['H']) ?? $supplierCategoryId,
                ]);
                if (!isset($supplier_category->category_id)) {
                    $unmapped_categories[$supplier_category->supplierCategoryId] = $supplier_category->name;
                    //continue;
                }

                // Canon -> Supplier Params
                // 'supplier_id', 'brand_id', 'category_id', 'supplierSku', 'canon', 'rappel', 'ports'
                $canon = FacadesMpe::roundFloatEsToEn($row['T']);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');


                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['J'], $row['M']);
                //$shortdesc = $row['K'];
                $longdesc = null;

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

                    if (isset($row['O']) && $row['O'] != '')
                        $product->updateOrCreateExternalImage($row['O']);
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

                // Activo ?
                //if (!$row['L']) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $stock = intval($row['I']);
                $cost = FacadesMpe::roundFloatEsToEn($row['C']);

                //$supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['J'], $row['M']);
                //$pn = FacadesMpe::getPn($row['J']);
                //$ean = FacadesMpe::getEAN($row['M']);               // ean

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


    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['P'];
            if ($brand_name == '') return null;
            if ($brand_name == 'Hewlett Packard Enterprise') $brand_name = 'HPe';
            if ($brand_name == 'Https://cdn2.depau.es/articulos/448/448/fixed/art_oxf-archiv%20400075372_1.jpg') $brand_name = 'oxford';
            if ($brand_name == 'Blackdecker') $brand_name = 'Black & Decker';

            return ucwords(strtolower($brand_name));

        } catch (Throwable $th) {
            return null;
        }
    }


    private function getCategories(array $file_rows)
    {
        try {
            $supplier_categories = [];
            foreach($file_rows as $row) {
                $cat_code = strval(intval($row['G']));
                $cat_name = strval(strval($row['H']));

                if ($cat_name) {
                    $supplier_categories[$cat_code] = $cat_name;

                    $supplier_category = SupplierCategory::updateOrCreate([
                        'supplier_id'           => $this->supplier->id,
                        'supplierCategoryId'    => mb_substr($cat_code, -64),
                    ],[
                        'name'                  => mb_substr($cat_name, 0, 255)
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
                $brand_name = $row['P'];
                if ($brand_name == '') continue;
                if ($brand_name == 'Hewlett Packard Enterprise') $brand_name = 'HPe';
                if ($brand_name == 'Https://cdn2.depau.es/articulos/448/448/fixed/art_oxf-archiv%20400075372_1.jpg') $brand_name = 'oxford';
                $brand_name = ucwords(strtolower($brand_name));

                $supplier_brands[$brand_name] = null;
            }

            return array_keys($supplier_brands);

        } catch (Throwable $th) {
            return null;
        }
    }


}
