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


class ImportGlobomatik
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 19,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 7,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'http://multimedia.globomatik.net/csv/import.php?username=36343&password=01802156&mode=all&type=default';
    const URI_OFFERS = 'http://multimedia.globomatik.net/csv/import.php?username=36343&password=01802156&filter=stockprecio';

    const IMPORT_TEXT = "<b>Importación de productos</b> Fichero CSV con todos los datos e imagenes.<br>
    <b>Importación de Ofertas:</b> Fichero CSV";


    const REJECTED_CATEGORIES = [
        'Pcs Integración / Cabinas y Rack / Cabinas y Rack',
        'Pcs Integración / Placas Base / Socket TR4',
        'Consolas / Videoconsolas / Juegos Sony PS5',
        'Periféricos / Consumibles / Cartuchos Originales',
        'TPV-POS / Lectores / Lector Código de Barras',
        'Periféricos / Consumibles / Tóner Original',
        'Software / Suites / Suite Ofimática',
        'Periféricos / Seguridad y videovigilancia / Videograbadores',
        'Conectividad / Adaptadores / Adaptadores de Corriente',
        'Movilidad / Telefonía / Teléfonos Fijos DEC',
        'Conectividad / Cables / Cables de Red',
        'Software / Sistemas Operativos / 64 BIT',
        'Periféricos / Accesorios / Soportes',
        'TPV-POS / Monitores Táctiles / Monitores TFT',
        'Pcs Integración / Accesorios / Herramientas',
        'Imagen y Sonido / Accesorios / Soportes TV',
        'Periféricos / Seguridad y videovigilancia / Videograbadores',
        'Movilidad / Accesorios Portátiles / Cables de Red',
        'Periféricos / Sonido / Auriculares Telefonía Fija',
        'Conectividad / NAS / Servidores NAS con HDD',
        'Conectividad / NAS / Servidores NAS sin HDD',
        'Consolas / Videoconsolas / Juegos Nintendo Switch',
        'Conectividad / Cabinas y Rack / Cabinas y Rack',
        'TPV-POS / Impresoras Tickets / Impresoras',
        'Electrodomésticos / Climatización / Aire Acondicionado Fijo',
        'TPV-POS / Software Empresarial / Software TPV',

        'Conectividad / Cabinas y Rack / Accesoros Cabinas y Rack',
        'Conectividad / Cables / Cables USB',
        'Consolas / Videoconsolas / Juegos Sony PS4',
        'Imagen y Sonido / Videoconsolas / Juegos Nintendo Switch',
        'Software / Antivirus / Antivirus',

        'Conectividad / Videoconsolas / Juegos Sony PS4',
        'Consolas / Videoconsolas / Juegos Microsoft Xbox',
        'TPV-POS / Monitores / Táctiles'
    ];

    private $supplier;



    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'globomatik'
            ],
            [
                'name'          => 'Globomatik',
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

            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Brand
                if (!$brand_name = $row['E']) continue;

                if ($brand_name == 'Raspberry') $brand_name = 'Raspberry Pi';
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $cost = str_replace('€', '', $row['G']);
                $cost = FacadesMpe::roundFloatEsToEn($cost);

                $stock = intval($row['H']);

                $name = FacadesMpe::getString($row['B']);
                if (!isset($name) || $name == '') continue;

                $supplierCategoryId = self::getCategory($row);
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::updateOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => mb_substr($supplierCategoryId, -64),
                ],[
                    'name'                  => mb_substr($supplierCategoryId, 0, 255)
                ]);
                if (!isset($supplier_category->category_id)) {
                    $unmapped_categories[$supplier_category->supplierCategoryId] = $supplier_category->name;
                    //continue;
                }

                // Canon
                $canon = str_replace('€', '', $row['P']);
                $canon = FacadesMpe::roundFloatEsToEn($canon);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['F'], $row['I']);
                //$pn = FacadesMpe::getPn($row['F']);
                //$ean = FacadesMpe::getEAN($row['I']);
                $shortdesc = FacadesMpe::getText($row['K']);
                $longdesc = FacadesMpe::getText($row['L']);

                $weight = $row['S'];
                $attributes = $row['R'];

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
                        null, null, null, null, null, null, null, null, $shortdesc, null, null, null, null, null);

                    if (isset($row['M']) && $row['M'] != '')
                        $product->updateOrCreateExternalImage($row['M']);
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

                $stock = intval($row['E']);
                if ($stock == 0) {
                    $no_stock++;
                    continue;
                }

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['C'], $row['F']);
                $cost = str_replace('€', '', $row['D']);
                $cost = FacadesMpe::roundFloatEsToEn($cost);

                $product = $supplier->getSimilarProduct(1, null, $pn, $ean);

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

            $msg = 'Importados '.$imported_count. ' precios y stocks.';
            if ($no_stock != 0) $msg .= ' Productos sin stock: '.$no_stock;
            if ($no_cost != 0) $msg .= ' Productos con coste inferior: '.$no_cost;

            /* if (count($unmapped_categories)) $msg .= ' Categorias sin mapear: '.json_encode($unmapped_categories); */
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }



    static function getCategory(array $row)
    {
        try {
            $macrofamilia = $row['Q'];
            $familia = $row['C'];
            $subfamilia = $row['D'];

            return utf8_decode($macrofamilia.' / '.$familia.' / '.$subfamilia);

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function getCategories(array $file_rows)
    {
        try {
            $supplier_categories = [];
            foreach($file_rows as $row) {

                /* $familia = $row['C'];
                $subfamilia = $row['D'];
                $macrofamilia = $row['Q']; */

                $supplier_categories[self::getCategory($row)] = null;
            }

            return array_keys($supplier_categories);

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getBrands(array $file_rows)
    {
        try {
            $supplier_brands = [];
            foreach($file_rows as $row) {

                $marca = $row['E'];
                $supplier_brands[$marca] = null;
            }

            return array_keys($supplier_brands);

        } catch (Throwable $th) {
            return null;
        }
    }

}
