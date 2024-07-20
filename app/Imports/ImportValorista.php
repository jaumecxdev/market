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



class ImportValorista
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts'];

    const FORMATS = [
        'products'    => [
            'columns'       => 15,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
       /*  'descriptions'    => [
            'columns'       => 2,
            'header_rows'   => 6,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']
        ], */
    ];

    // IMPORTACIO MANUAL
    //const URI_PRODUCTS = '';
    //const IMPORT_TEXT = '';

    const IMPORT_TEXT = "<b>Importación de productos</b>"; /* Los nombres de los ficheros Excel deben coincidir con la Marca.<br>
    <b>Importación de Imágenes:</b> Los nombres de los ficheros de imágenes deben empezar con el CÓDIGO del producto."; */


    private $supplier;

    const REJECTED_CATEGORIES = [];


    const SUPPLIER_BRANDS = [
        "AP" => "Apple",
        "ST" => "StarTech",
        "UAG" => "UAG",
        "MI" => "Microsoft",
        "KE" => "Kensington",
        "TA" => "Targus",
        "BE" => "Belkin",
        "CY" => "Celly",
        "DL" => "D-Link",
        "DE" => "Dell",
        "TH" => "Toshiba",
        "HP" => "HP",
        "AC" => "Acer",
        "LE" => "Lenovo",
        "PN" => "Panasonic",
        "AK" => "Anker",
        "AS" => "Asus",
        "LK" => "Linksys",
        "PT" => "Polycom",
        "CK" => "CheckPoint",
        //"OT" => "ServiceDesk",
        "VG" => "Vogel's",
        "VL" => "Vestel",
        "MOB" => "Mobilis",
        "CR" => "Crucial",
        //"" => "Kingston",
        "LO" => "Logitech",
        "KI" => "Kingston",
        "SE" => "Seagate",
        "ASTOR" => "Asustor",
        "BR" => "Brother",
        "HPIPG" => "HP",
        //"GC/" => "RENEWED", Garantia Apple GENCARE
        "SVSA" => "Lenovo",
        "CSK" => "CashKeeper",
        "VAL" => "Regalo",
        "MSI" => "MSI",
        //"SRV" => "Valorista", Servicios
        "RE" => "Riello",
        "MO" => "Motorola",
        "BN" => "Bruneau",
        "NOB" => "Nobo",    // Acco/Nobo
        //"LER" => "RENEWED", Lenovo
        //"HPR" => "RENEWED", HP
        //"DER" => "RENEWED", Dell
        "BA" => "Barco",
        "SL" => "Salicru",
        "KP" => "Kaspersky",
        "PD" => "Panda",
        "MIOFF" => "Microsoft", // Office
        "MIWIN" => "Microsoft", // Windows
        "HU" => "Huawei",
        "SA" => "Samsung"
    ];


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'valorista'
            ],
            [
                'name'          => 'Valorista',
                'type_import'   => 'file',
                'ws'            => ''
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

            $discarded_brands = [];
            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // Brand
                $brand_code = $row['D'];        // cat
                if (!isset(self::SUPPLIER_BRANDS[$brand_code])) {
                    $discarded_brands[$brand_code] = null;
                    continue;
                }
                $brand = Brand::firstOrCreate([
                    'name'  => self::SUPPLIER_BRANDS[$brand_code]
                ],[]);

                $stock = intval($row['H']);     // disponible
                $cost = str_replace('€', '', $row['F']);   // pvd
                $cost = FacadesMpe::roundFloatEsToEn($cost);

                $name = FacadesMpe::getString($row['C']);
                if (!isset($name) || $name == '') continue;

                $supplierCategoryId = strval($row['E']);
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::updateOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => mb_substr($name, 0, 255) //mb_convert_encoding('ISO-8859-1') //utf8_decode(utf8_encode(substr($name, 0, 255)))
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = $supplier_category->name;

                // Canon -> Supplier Params
                $canon = $row['N'] ?? 0;
                $canon = str_replace('€', '', $canon);
                $canon = FacadesMpe::roundFloatEsToEn($canon);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es');

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['B'], $row['L']);
                //$pn = FacadesMpe::getPn($row['B']);
                //$ean = FacadesMpe::getEAN($row['L']);
                $longdesc = null;
                $weight = null;

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
            if (count($discarded_brands)) $msg .= ' Marcas descartadas: '.json_encode(array_keys($discarded_brands));
            if (count($unmapped_categories)) $msg .= ' Categorias sin mapear: '.json_encode($unmapped_categories);
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }

}
