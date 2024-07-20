<?php

namespace App\Imports;

use App\Brand;
use App\Product;
use App\Supplier;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;


class ImportMGSol
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 7,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 7,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'https://mayoristagafasdesol.com/exports/clients/xml/basic/exports_csv_basics_free.xml';
    const URI_OFFERS = 'https://mayoristagafasdesol.com/exports/clients/xml/basic/exports_csv_basics_free.xml';


    const IMPORT_TEXT = "<b>Importación de productos y ofertas</b> Fichero XML.";

    const REJECTED_CATEGORIES = [
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'mgsol'
            ],
            [
                'name'          => 'Mayoristas Gasfas de Sol',
                'type_import'   => 'xml',
                'ws'            => 'SupplierImportXmlWS'
            ]
        );
    }



    public function importProducts(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            //$file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['products']['header_rows']);

            $path = Storage::putFile('supplier/mgsol/products', $uploaded_file->getPathname());
            $contents = Storage::get($path);
            if ($xml = simplexml_load_string($contents))
                return self::products($this->supplier, $xml);   // products($this->supplier, $xml);

            return $this->nullAndStorage(__METHOD__, $uploaded_files);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    /* name": SimpleXMLElement {#1760}
  +"reference": SimpleXMLElement {#1759}
  +"ean13": SimpleXMLElement {#1758}
  +"final_price_with_tax": SimpleXMLElement {#1757}
  +"categories_names": SimpleXMLElement {#1756}
  +"quantity": SimpleXMLElement {#1755}
  +"images": SimpleXMLElement {#1754}
 */

    // BRANDS: Dsquared2, Emilio Pucci, Polaroid,
    // NAME: MARCA + "Sunglasses"
    // NAME: "Bolle Goggle" + MODEL_NUMBER + MODEL  -> Ulleres de Sol -> Marca: Bolle (Eliminar Google)
    // NAME:  Excepcions que comencen x: Hackett

    static function products(Supplier $supplier, $xml)
    {
       try {
           $res = [];
            foreach ($xml->product as $mgsol_product) {

                $name = (String)$mgsol_product->name;
                if ($strpos_sunglasses = strpos($name, 'Sunglasses')) {
                    $brand_name = mb_substr($name, 0, $strpos_sunglasses-1);

                    $res[] = [$name, $brand_name];
                }
                // Victoria's Secret Pink Fashion Accessory PK0001 72T 00
                elseif ($strpos_pink = strpos($name, 'Pink')) {

                    $brand_name = mb_substr($name, 0, $strpos_pink-1);
                    $res[] = [$name, $brand_name];
                }
                else {

                    /* $brand_name = mb_substr($name, 0, strpos($name, $strpos_pink)-1);
                    $res[] = [$name, $brand_name]; */
                }
            }


            if (!isset($xml))
                return 'No hay filas para importar.';
            /* if (count($xml->ArticulosD->ArticuloD->children()) != self::FORMATS['products']['columns'])
                return 'No tiene '.self::FORMATS['products']['columns']. ' columnas. Tiene '.count($xml->ArticulosD->ArticuloD->children());
 */

            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($xml->ArticulosD->ArticuloD as $field) {

                // Stock ?
                $stock = (Integer)$field->stock_disponible;
                if ($stock < 2) {
                    $no_stock++;
                    continue;
                }

                $cost = FacadesMpe::roundFloat((Float)$field->precio_comercio_a);
                if ($cost < 40) {
                    $no_cost++;
                    continue;
                }

                $tax = FacadesMpe::roundFloat((Float)$field->iva);

                // Brand
                if (!$brand_name = self::getBrand($field)) continue;
                if ($brand_name == '') continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);


                $name = FacadesMpe::getString((String)$field->descripcionori);
                if (!isset($name) || $name == '') continue;

                $supplierCategoryNames = (Array)$field->categorias->string;
                if (!isset($supplierCategoryNames) || !count($supplierCategoryNames)) continue;
                $supplierCategoryName = $supplierCategoryNames[0];
                if (in_array($supplierCategoryName, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => mb_substr($supplierCategoryName, -64),
                ],[
                    'name'                  => $supplierCategoryName,
                ]);

                if (!isset($supplier_category->category_id)) {
                    $unmapped_categories[$supplier_category->name] = false;
                }

                // Canon -> Supplier Params
                // 'supplier_id', 'brand_id', 'category_id', 'supplierSku', 'canon', 'rappel', 'ports'
                /* $canon = FacadesMpe::roundFloatEsToEn($row['K']);
                if ($canon > 0 && isset($supplier_category->category_id))
                    $supplier_category->category->firstOrCreateCanon($canon, 'es'); */

                $supplierSku = (String)$field->codigo;
                [$pn, $ean] = FacadesMpe::getPnEan(null, (String)$field->ean);
                if ($ean == '') continue;
                $pn = null;

                $longdesc = FacadesMpe::getText((String)$field->explicacion_texto);
                $weight = 0;        //FacadesMpe::roundFloatEsToEn($row['I']);
                $size = (String)$field->talla;
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

                    if ($product = $supplier->updateOrCreateProduct($pn, $ean, null, null, null, $supplierSku,
                        $brand->id, $supplier_category->id, $supplier_category->category_id ?? null, 1, 1,
                        $name, $longdesc, $cost, $tax, $stock, $weight,
                        null, null, null, null, null, null, null, null, null, $size, null, null, null, null)) {

                        $images = (Array)$field->imagenes_lg->string;
                        if (isset($images) && is_array($images))
                            foreach ($images as $image) {
                                if (isset($image) && $image != '')
                                    $product->updateOrCreateExternalImage($image);
                            }
                    }
                }

                if (isset($product)) {
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


    public function importOffers(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $path = Storage::putFile('supplier/mgsol/offers', $uploaded_file->getPathname());
            $contents = Storage::get($path);
            if ($xml = simplexml_load_string($contents))
                return self::offers($this->supplier, $xml);

            return $this->nullAndStorage(__METHOD__, $uploaded_files);


            /* $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['offers']['header_rows']);

            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows)->keyBy((string)$this->supplier->supplierSku_field);

            return self::offers($this->supplier, $productsCollect); */

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }



    static function offers(Supplier $supplier, $xml)
    {
       try {
            if (!isset($xml))
                return 'No hay filas para importar.';
            if (count($xml->Art->Art->children()) != self::FORMATS['offers']['columns'])
                return 'No tiene '.self::FORMATS['offers']['columns']. ' columnas. Tiene '.count($xml->Art->Art->children());

            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($xml->Art->Art as $field) {

                // Stock ?
                $stock = (Integer)$field->Stock;
                if ($stock <= 0) {
                    $no_stock++;
                    continue;
                }

                $cost = FacadesMpe::roundFloat((Float)$field->PrA);

                $supplierSku = (String)$field->Cod;
                [$pn, $ean] = FacadesMpe::getPnEan(null, (String)$field->EAN);
                if ($ean == '') continue;
                $pn = null;

                $product = $supplier->getSimilarProduct(1, null, null, $ean);

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


    static function getBrand($field)
    {
        try {
            $brand_name = (String)$field->fabricante;
            $brand_name = ucwords(strtolower($brand_name));

            return $brand_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    static function getCategories($xml)
    {
       try {
            $cats = [];
            foreach ($xml->ArticulosD->ArticuloD as $field) {

                // Stock ?
                $stock = (Integer)$field->stock_disponible;
                if ($stock < 2) continue;
                $cost = FacadesMpe::roundFloat((Float)$field->precio_comercio_a);
                if ($cost < 40) continue;

                //$supplierCategoryName = (String)$field->familia;
                $supplierCategoryNames = (Array)$field->categorias->string;
                if (!isset($supplierCategoryNames) || !count($supplierCategoryNames)) continue;
                $supplierCategoryName = $supplierCategoryNames[0];
                if (in_array($supplierCategoryName, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => 41,
                    'supplierCategoryId'    => mb_substr($supplierCategoryName, -64),
                ],[
                    'name'                  => $supplierCategoryName,
                ]);

                $cats[$supplierCategoryName] = true;
            }


        } catch (Throwable $th) {
            return null;
        }
    }


    static function getBrands($xml)
    {
        try {
            $supplier_brands = [];
            foreach ($xml->ArticulosD->ArticuloD as $field) {

                // Stock ?
                $stock = (Integer)$field->stock_disponible;
                if ($stock < 2) continue;
                $cost = FacadesMpe::roundFloat((Float)$field->precio_comercio_a);
                if ($cost < 40) continue;

                // Brand
                if (!$brand_name = self::getBrand($field)) continue;
                if ($brand_name == '') continue;
                $supplier_brands[$brand_name] = true;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);
            }

            return array_keys($supplier_brands);

        } catch (Throwable $th) {
            return null;
        }
    }

}
