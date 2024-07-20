<?php

namespace App\Imports;

use App\Brand;
use App\Product;
use App\Supplier;
use App\SupplierBrand;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;


class ImportRegalaPerfumes
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
            'columns'       => 38,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'https://regalasexo.net/modules/qashops/export/prestashop/flux-es.xml';
    const URI_OFFERS = 'https://regalasexo.net/modules/qashops/export/prestashop/flux-es.xml';

    const IMPORT_TEXT = "<b>Importación de productos y ofertas</b> Fichero XML.";

    const REJECTED_CATEGORIES = [
        /* 'APP>BRICOLAJE>ACCESORIOS',
        'HOGAR>TEXTIL>ROPA DE CAMA>COLCHAS',
        'BAZAR>FERRETERIA>ELECTRICIDAD>PILAS Y CARGADORES' */
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'regalaperfumes'
            ],
            [
                'name'          => 'RegalaSexo Perfumes',
                'type_import'   => 'xml',
                'ws'            => 'SupplierImportXmlWS'
            ]
        );
    }



    public function importProducts(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $path = Storage::putFile('supplier/regalaperfumes/products', $uploaded_file->getPathname());
            $contents = Storage::get($path);
            if ($xml = simplexml_load_string($contents))
                return self::products($this->supplier, $xml);

            return $this->nullAndStorage(__METHOD__, $uploaded_files);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function products(Supplier $supplier, $xml)
    {
       try {
            if (!isset($xml))
                return 'No hay filas para importar.';

            if ($xml->product->children()->count() != self::FORMATS['products']['columns'])
                return 'No tiene '.self::FORMATS['products']['columns']. ' columnas. Tiene '.$xml->product->children()->count();

            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($xml->product as $field) {

                if (!(Boolean)$field->active) continue;

                // Stock ?
                $stock = (Integer)$field->quantity;
                if ($stock < 1) {
                    $no_stock++;
                    continue;
                }

                $cost = FacadesMpe::roundFloat((Float)$field->wholesale_price ?? (Float)$field->price_product);
                if ($cost < 0) {
                    $no_cost++;
                    continue;
                }

                $tax = FacadesMpe::roundFloat((Float)$field->tax_rate);

                // Brand
                if (!$brand_name = self::getBrand($field)) continue;
                if ($brand_name == '') continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $supplier_brand = SupplierBrand::firstOrCreate([
                    'supplier_id'   => $supplier->id,
                    'name'          => $brand_name,
                ],[]);

                $name = FacadesMpe::getString((String)$field->name);
                if (!isset($name) || $name == '') continue;

                $supplierCategoryName = (String)$field->category;
                if (in_array($supplierCategoryName, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => mb_substr($supplierCategoryName, -64),
                ],[
                    'name'                  => $supplierCategoryName,
                ]);

                $supplierSku = (String)$field->sku;
                [$pn, $ean] = FacadesMpe::getPnEan((String)$field->manufacturer_reference, (String)$field->ean);
                if ($ean == '') continue;

                $longdesc = FacadesMpe::getText((String)$field->description_short);
                $weight = 0;        //FacadesMpe::roundFloatEsToEn($row['I']);
                $size = null;
                if ((String)$field->information->tipo_de_talla_ != '')
                    $size = (String)$field->information->tipo_de_talla_ .' '. (String)$field->information->talla;

                $color = (String)$field->information->color;
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

                    $product->supplier_brand_id = $supplier_brand->id;

                    if ($product->name == '' && isset($name) && $name != '')
                        $product->name = $name;

                    if ((!isset($product->pn) || $product->pn == '') && isset($pn) && $pn != '')
                        $product->pn = $pn;

                    $product->save();
                }
                // Create new product
                else {

                    if ($product = $supplier->updateOrCreateProduct($pn, $ean, null, null, null, $supplierSku,
                        $brand->id, $supplier_category->id, null, 1, 1,
                        $name, $longdesc, $cost, $tax, $stock, $weight,
                        null, null, null, null, null, null, null, null, null, $size, $color, null, null, null)) {

                        $product->supplier_brand_id = $supplier_brand->id;
                        $product->save();

                        $image = (String)$field->images->image_0;
                        if (isset($image) && $image != '')
                                $product->updateOrCreateExternalImage($image);
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

            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importOffers(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $path = Storage::putFile('supplier/regalaperfumes/offers', $uploaded_file->getPathname());
            $contents = Storage::get($path);
            if ($xml = simplexml_load_string($contents))
                return self::offers($this->supplier, $xml);

            return $this->nullAndStorage(__METHOD__, $uploaded_files);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function offers(Supplier $supplier, $xml)
    {
       try {
            if (!isset($xml))
                return 'No hay filas para importar.';

            if ($xml->product->children()->count() != self::FORMATS['offers']['columns'])
                return 'No tiene '.self::FORMATS['offers']['columns']. ' columnas. Tiene '.$xml->product->children()->count();

            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($xml->product as $field) {

                if (!(Boolean)$field->active) continue;

                // Stock ?
                $stock = (Integer)$field->quantity;
                if ($stock < 1) {
                    $no_stock++;
                    continue;
                }

                $cost = FacadesMpe::roundFloat((Float)$field->wholesale_price ?? (Float)$field->price_product);
                if ($cost < 0) {
                    $no_cost++;
                    continue;
                }

                // Brand
                if (!$brand_name = self::getBrand($field)) continue;
                if ($brand_name == '') continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                [$pn, $ean] = FacadesMpe::getPnEan((String)$field->manufacturer_reference, (String)$field->ean);
                if ($ean == '') continue;

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
            $brand_name = (String)$field->brand;
            $brand_name = ucwords(mb_strtolower($brand_name));

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
