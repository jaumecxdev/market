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


class ImportGrutinet
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers', 'importAttributes'];

    const FORMATS = [
        'products'    => [
            'columns'       => 35,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 36,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'attributes'    => [
            'columns'       => 4,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = 'https://media.grutinet.com/ficheros/ListaArticulosD.xml?username=pra4499&password=690ffb1774e16e056d46531a80860bb9';
    const URI_OFFERS = 'https://media.grutinet.com/ficheros/ListaStockD.xml?username=pra4499&password=690ffb1774e16e056d46531a80860bb9';
    const URI_ATTRIBUTES ="https://media.grutinet.com/ficheros/atributosD.xml";


    const IMPORT_TEXT = "<b>Importación de productos y ofertas</b> Fichero XML.";

    const REJECTED_CATEGORIES = [
        'APP>BRICOLAJE>ACCESORIOS',
        'HOGAR>TEXTIL>ROPA DE CAMA>COLCHAS',
        'BAZAR>FERRETERIA>ELECTRICIDAD>PILAS Y CARGADORES'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'grutinet'
            ],
            [
                'name'          => 'Grutinet',
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

            $path = Storage::putFile('supplier/grutinet/products', $uploaded_file->getPathname());
            $contents = Storage::get($path);
            if ($xml = simplexml_load_string($contents))
                return self::products($this->supplier, $xml);   // products($this->supplier, $xml);

            return $this->nullAndStorage(__METHOD__, $uploaded_files);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


   /*  "codigo": "10055"
  +"familia": "ACEITES Y LUBRICANTES"
  +"subfamilia": "LUBRICANTES"
  +"subfamilia2": "LUBRICANTES"
  +"subfamilia3": "LUBRICANTES"
  +"subfamilia4": "LUBRICANTES"
  +"ean": "827160110154"
  +"talla": "ST"
  +"hashtalla": "9b9e247d78ea400ecb5eb8c3fd63d1ed"
  +"descripcion": "PJUR COOL LUBRICANTE AGUA EFECTO FRIO 100 ML"
  +"descripcionori": "PJUR COOL REFRESHING MENTHOL WATERBASED LUBRICANT 100 ML"
  +"novedad": "false"
  +"promocion": "false"
  +"video": "false"
  +"reponer": "true"
  +"precio_recomendado": "20.1600"
  +"precio_comercio_a": "6.76"
  +"precio_comercio_b": "6.76"
  +"precio_comercio_c": "6.76"
  +"precio_comercio_d": "6.76"
  +"iva": "21.00"
  +"explicacion": """
    <p><strong>PJUR COOL REFRESHING MENTHOL WATERBASED LUBRICANT 100 ML</strong>.<br />
    <br />
    Los opuestos se atraen. Este lubricante con base de agua no es otro gel refrescante cualquiera. Es más bien el efecto estimulante del mentol lo que le da a tu e ▶
    <br />
    - Aumenta tus sentidos para una <strong>estimulación erótica</strong> única.<br />
    - <strong>pjur Refreshing Menthol</strong>: frío estimulante en lugar de sólo frío.<br />
    - <strong>Libre de olores y sabores</strong>, y apto para su uso con preservativos de látex.<br />
    <br />
    Ingredientes: Aqua (Water), Propylene Glycol, Glycerin (Glycerol), Ethoxydiglycol, Hydroxypropyl Guar, Hydroxypropyltrimonium Chloride, , Hydroxyethylcellulose, ▶
    <br />
    Envase de 100 ml.</p>
    """
  +"explicacion_texto": """
    PJUR COOL REFRESHING MENTHOL WATERBASED LUBRICANT 100 ML.

    Los opuestos se atraen. Este lubricante con base de agua no es otro gel refrescante cualquiera. Es más bien el efecto estimulante del mentol lo que le da a tu e ▶

    - Aumenta tus sentidos para una estimulación erótica única.
    - pjur Refreshing Menthol: frío estimulante en lugar de sólo frío.
    - Libre de olores y sabores, y apto para su uso con preservativos de látex.

    Ingredientes: Aqua (Water), Propylene Glycol, Glycerin (Glycerol), Ethoxydiglycol, Hydroxypropyl Guar, Hydroxypropyltrimonium Chloride, , Hydroxyethylcellulose, ▶

    Envase de 100 ml.
    """
  +"stock_disponible": "3"
  +"multiplo_venta": "1"
  +"fabricante": "PJUR"
  +"subfabricante": "SEX AND MASSAGE"
  +"imagen_bu": "http://media.grutinet.com/articulos/bu/bu_10055.jpg"
  +"imagen_gr": "http://media.grutinet.com/articulos/gr/gr_10055.jpg"
  +"imagenes_lg": SimpleXMLElement {#1760 ▶}
  +"imagenes_lp": SimpleXMLElement {#1759 ▶}
  +"categorias": SimpleXMLElement {#1758 ▶}
  +"fecha_actualizado": "2019-07-17T16:51:00"
  +"fecha_descatalogado": "0001-01-01T00:00:00"
  +"descatalogado": "false"
  +"grupo": "503" */

    static function products(Supplier $supplier, $xml)
    {
       try {
            if (!isset($xml))
                return 'No hay filas para importar.';
            if (count($xml->ArticulosD->ArticuloD->children()) != self::FORMATS['products']['columns'])
                return 'No tiene '.self::FORMATS['products']['columns']. ' columnas. Tiene '.count($xml->ArticulosD->ArticuloD->children());

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
            $path = Storage::putFile('supplier/grutinet/offers', $uploaded_file->getPathname());
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


  /*   "Grupo": SimpleXMLElement {#1758}
  +"Nombre": "PJUR COOL LUBRICANTE AGUA EFECTO FRIO 100 ML"
  +"EAN": "827160110154"
  +"Desc": """
    <p><strong>PJUR COOL REFRESHING MENTHOL WATERBASED LUBRICANT 100 ML</strong>.<br />
    <br />
    Los opuestos se atraen. Este lubricante con base de agua no es otro gel refrescante cualquiera. Es más bien el efecto estimulante del mentol lo que le da a tu e ▶
    <br />
    - Aumenta tus sentidos para una <strong>estimulación erótica</strong> única.<br />
    - <strong>pjur Refreshing Menthol</strong>: frío estimulante en lugar de sólo frío.<br />
    - <strong>Libre de olores y sabores</strong>, y apto para su uso con preservativos de látex.<br />
    <br />
    Ingredientes: Aqua (Water), Propylene Glycol, Glycerin (Glycerol), Ethoxydiglycol, Hydroxypropyl Guar, Hydroxypropyltrimonium Chloride, , Hydroxyethylcellulose, ▶
    <br />
    Envase de 100 ml.</p>
    """
  +"Cod": "10055"
  +"Stock": "3"
  +"Disponibilidad": "24h"
  +"IVA": "21.00"
  +"PrUsu": "0"
  +"PrUsuAcc": "0"
  +"PrA": "6.76"
  +"PrAAcc": "0"
  +"DtoAAcc": "0"
  +"PrB": "6.76"
  +"PrBAcc": "0"
  +"DtoBAcc": "0"
  +"PrC": "6.76"
  +"PrCAcc": "0"
  +"DtoCAcc": "0"
  +"PrD": "6.76"
  +"PrDAcc": "0"
  +"DtoDAcc": "0"
  +"PrTienda": "0"
  +"PrTiendaAcc": "0"
  +"DtoTiendaAcc": "0"
  +"PVPRec": "20.1600"
  +"PVPRecEsp": "20.1600"
  +"DtoPVPRecEsp": "0"
  +"Reponer": "true"
  +"HashTalla": "9b9e247d78ea400ecb5eb8c3fd63d1ed"
  +"PromoDesde": "0001-01-01T00:00:00"
  +"PromoHasta": "0001-01-01T00:00:00"
  +"fecha_descatalogado": "0001-01-01T00:00:00"
  +"descatalogado": "false"
  +"EstadoAcc": "NORMAL"
  +"precio_recomendado": "20.1600" */

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


    public function importAttributes(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            //$file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['products']['header_rows']);

            $path = Storage::putFile('supplier/grutinet/attributes', $uploaded_file->getPathname());
            $contents = Storage::get($path);
            if ($xml = simplexml_load_string($contents))
                return self::attributes($this->supplier, $xml);

            return $this->nullAndStorage(__METHOD__, $uploaded_files);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }

    /* $importSupplierClass = 'App\\Imports\\Import'.ucwords($this->supplier->code);
    $uri = $importSupplierClass::URI_PRODUCTS;
    $header_rows = $importSupplierClass::FORMATS['products']['header_rows'];
    $directory = $this->supplier->storage_dir.'products/';
    $filename = date('Y-m-d_H'). '_products.xml';
    if ($xml = FacadesMpeImport::getRowsUriXML($uri, $header_rows, $directory, $filename))
        return $importSupplierClass::products($this->supplier, $xml); */


    static function attributes(Supplier $supplier, $xml)
    {
       try {
            if (!isset($xml))
                return 'No hay filas para importar.';

            $count = 0;
            foreach($xml->Articulos->ArticuloAtributos as $field) {

                if (count($field->Atributos->Atributo) == 0) continue;

                $supplierSku = (String)$field->Articulo;
                if ($supplierSku != '' && $product = $supplier->products()->where('supplierSku', $supplierSku)->first()) {
                    $count++;
                    foreach ($field->Atributos->Atributo as $Atributo) {
                        if ((String)$Atributo->Codigo == 'PESO') $product->weight = (Float)$Atributo->Valor;
                        elseif ((String)$Atributo->Codigo == 'LARGO') $product->length = (Float)$Atributo->Valor;
                        elseif ((String)$Atributo->Codigo == 'ANCHO') $product->width = (Float)$Atributo->Valor;
                        elseif ((String)$Atributo->Codigo == 'ALTO') $product->height = (Float)$Atributo->Valor;
                    }

                    $product->save();
                }
            }

            return 'Importados '.$count. ' atributos.';


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
