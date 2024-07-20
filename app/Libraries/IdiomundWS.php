<?php

namespace App\Libraries;

use App\Brand;
use App\Models\Iproduct;
use App\Product;
use App\Supplier;
use App\SupplierBrand;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\Mpe as FacadesMpe;
use Throwable;


class IdiomundWS extends SupplierWS implements SupplierWSInterface
{
    use HelperTrait;

    protected $idiomund_supplier = null;        // Supplier X Filters, Categories & Brands
    protected $supplier_brands = null;
    protected $supplier_images_online = null;   // Array of Collections {id_product, id_manufacturer, reference, id_image}

    // BLANES PRICES
    // price = wholesale_price (without canon & without ports 3,31+iva) -> La majoria porten Canon SENSE Ports
    // canon = rapell_price - rapell_price_noshipping (condiciones sin portes)
    // wholesale_price == rapell_price_noshipping -> CONFIRMAT
    // rapell_price (con condiciones + sin portes)

    // INGRAM & OTHER PRICES
    // price = wholesale_price (without canon & ports included) -> La majoria ho porten TOT Canon + Ports
    // canon+ports = rapell_price - rapell_price_noshipping (condiciones sin portes)
    // rapell_price (con condiciones + portes)
    // BLANES, INGRAM, VINZEO i ESPRINET: wholesale_price NO inclou Canon
    // TECHDATA i DESYMAN: wholesale_price SI inclou Canon

    // ALL SUPPLIERS
    // rapell_price = con condiciones + portes si es de fuera

    // 20210928 ESTUDI DELS PREUS DE COST
    // $cost = wholesale_price (Fa falta treure el CANON de TECHDATA i DESYMAN)
    // ESTUDI DE PREUS
    // BLANES: wholesale_price == rapell_price_noshipping (sense canon ni ports)
    // INGRAM: wholesale_price == preu web (sense canon ni ports)
    // VINZEO: wholesale_price == preu web (sense canon ni ports)
    // ESPRINET: wholesale_price == preu web (sense canon ni ports)
    // TECHDATA: wholesale_price == preu web (canon inclos i sense ports)
    // DESYMAN: wholesale_price == rapell_price_noshipping && wholesale_price == preu web (canon inclos i sense ports)

    // 20210928 ESTUDI DELS PORTS
    // BLANES: 3.31 + IVA
    // INGRAM: Portes gratis
    // VINZEO: 3€ (< 300€)
    // ESPRINET: 4€ (< 200€ ???)  OLD: Portes gratis
    // TECHDATA: Portes gratis
    // DESYMAN: 6€ (< 400€)

    // 20210928 CONCLUSIÓ
    // $cost == wholesale_price
    // TECHDATA i DESYMAN --> TRURE EL CANON DE wholesale_price
    // AFEGIR PORTS A SUPPLIER_PARAMS
    // REVISAR PORTS I CANONS DE SHOP_PARAMS

    protected $ports_blanes = 4;    // 4 € + IVA

    // id_importador => supplier_id
    protected $supplier_ids = [
        1   => 1,  // Blanes
        2   => 7,  // Almacen Madrid
        //11  => 2,  // Almacen Outlet
        //50  => 5,  // Almacen Outlet Web

        3   => 8,  // Ingram Micro
        4   => 10,  // Vinzeo -> 10
        6   => 11, // Tech Data -> 11
        //7   => 12, // Valorista
        12  => 13, // Esprinet -> 13
        19  => 14, // Desyman -> 14

        30  => 38,


        //9,  // Almacen Andorra
        //16, // Almacen Canarias
        //22, // Almacen Blanes TV
        //27, // Almacen Web Andorra
        //31, // Almacen Externo Blanes
        //42, // Almacen Tienda
    ];


    protected $wholesale_price_with_canon_id_importadors = [
        6,  // Tech Data -> 11
        19, // Desyman -> 14
    ];


    // Change this category
    protected $icategories_group = [
        16      => 31,      // proyectores
        45      => 11,      // monitores
        20      => 61,      // televisores
        92      => 93,      // escaneres
        97      => 98,      // sai
        143     => 77,      // altavoces
        241     => 127,     // tpv
        269     => 212,     // terminales
        280     => 219,     // adaptadores
        307     => 78,      // auriculares
        310     => 114,     // mp3 / mp4
        314     => 200,     // bluetooth
        315     => 48,      // ratones
        316     => 77,      // altavoces
        317     => 306,     // audio / sonido
        321     => 78,      // auriculares
        322     => 80,      // webcams
        324     => 77,      // altavoces
        326     => 304,     // redes
        431     => 176,     // cables
        432     => 176,     // cables
        433     => 319,     // gaming

        126     => 75,      // Accesorios impresora
        136     => 106,     // firewall
    ];


    // Change this Brands
    protected $ibrands_group = [
        13217   => 276,     // coolbox
        13219   => 41,      // cisco
        13300   => 26,      // microsoft
        13308   => 66,      // lenovo
        13315   => 141,     // oki
        13356   => 138,     // kingston
        13357   => 138,     // kingston
        13358   => 138,     // kingston
        13365   => 2575,    // google
        13417   => 78,      // brother
        13444   => 66,      // lenovo
        13449   => 3299,    // hpe
        13451   => 258,     // crucial technology
        13453   => 6624,    // Thomson computing
        317     => 306,     // audio / sonido
        321     => 78,      // auriculares
        322     => 80,      // webcams
        324     => 77,      // altavoces
        326     => 304,     // redes
        431     => 176,     // cables
        432     => 176,     // cables
        433     => 319,     // gaming
        111     => 126,     // Ingram: MMD -> Philips
        3597    => 601,     // Inram: Gn audio germany gmbh -> Jabra
        13304   => 136,     // Adaptec
        3139    => 10,      // Asus
        3478    => 331,     // Avm
        13633   => 13293,   // Dinabook
        13601   => 498,     // Tp-Link
    ];
    // 13304

    // NO import this Idiomund Categories
    //rejected_categories
    protected $rejected_categories = [
        34,     // "Garantía para Proyectores",
        46,     // "Garantía para Monitores",
        72,     // "Garantías para Impresoras",
        96,     // "Garantías para Cámaras",
        100,    // "Garantía para SAI",
        223,    // "Garantías para Ordenadores",
        224,    // "Servicios y Garantías",
        225,    // "Garantías para Escáneres",
        233,    // "Garantías para Softwares",
        235,    // "Garantías para Almacenamiento",
        236,    // "Garantías para Redes",
        267,    // "Garantías para GPS",
        268,    // "Garantías para Móviles / Smartphones",
        270,    // "Garantías para Terminales",

        273,    // Outlet

        237,    // Transceptores / Convertidores
        183,    // Cables SCSI
        179,    // Cables Telefónicos
        220,    // Bridges
        213,    // Servidores VoIP
        187,    // Cables Paralelos
        145,    // Papel Fotográfico
        137,    // Telecomunicaciones
        332,    // Control de accesos
        227,    // Consumibles para Fax
        159,    // Kits Limpieza
        148,    // Transparencias
        146,    // Papel para Plotter
        190,    // Cables de Par Trenzado
        243,    // Repuestos
        242,    // Material de Oficina
        196,    // Controladoras de Discos
        279,    // Baterías
        153,    // Cintas
        162,    // Baterías / Pilas
        248,    // Otros Cables
        206,    // RAID
        157,    // Cintas de Datos
        177,    // Cables de Almacenamiento
        149,    // Papel para Impresoras
        218,    // Dispositivos de Seguridad
        184,    // Cables de Serie
        230,    // Kits de Montaje
        160,    // Tambores / Fusores
        249,    // Cables para Monitores
        186,    // Cables USB / FireWire
        185,    // Cables de Red
        147,    // Etiquetas
        106,    // Firewalls
        180,    // Cables de Audio / Video
        152,    // Tóners
        151,    // Cartuchos

        205,    // Accesorios para Redes
        38,     // Accesorios para Servidores
        99,     // Accesorios para SAI
        75,     // Accesorios para Impresoras
        102,    // Accesorios para Workstation
        234,    // Accesorios para TPV
        33,     // Accesorios para Proyectores
        39,     // Accesorios para Ordenadores
        40,     // Accesorios para Sobremesas
        41,     // Accesorios para Tablets
        319,    // Funas y accesorios para tablets
        192,    // Accesorios para Almacenamiento
        91,     // Accesorios para Teléfonos
        26,     // Accesorios para Cámaras
        222,    // Accesorios para Domótica
        288,    // Accesorios camaras digitales
        208,    // Accesorios para Oficina
        52,     // Accesorios para Periféricos
        332,    // Control de accesos
        94,     // Accesorios para Escáneres

        28,     // Videojuegos
        138,    // Ofimática / Utilities
        132,    // Antivirus
        130,    // Sistemas Operativos
        251,    // Softwares Varios
        133,    // Creatividad y Diseño
        131,    // Softwares para Servidores
        140,    // Programación
        141,    // Ocio y Vida Práctica
        135,    // Juegos
    ];



    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);

        // Supplier X Filters, Categories & Brands
        $this->idiomund_supplier = Supplier::firstWhere('code', 'idiomund');
        $this->supplier_brands = $this->getSupplierBrands();

        // NO import this Idiomund Categories
        //rejected_categories
        /* $this->rejected_categories = [
            34,     // "Garantía para Proyectores",
            46,     // "Garantía para Monitores",
            72,     // "Garantías para Impresoras",
            96,     // "Garantías para Cámaras",
            100,    // "Garantía para SAI",
            223,    // "Garantías para Ordenadores",
            224,    // "Servicios y Garantías",
            225,    // "Garantías para Escáneres",
            233,    // "Garantías para Softwares",
            235,    // "Garantías para Almacenamiento",
            236,    // "Garantías para Redes",
            267,    // "Garantías para GPS",
            268,    // "Garantías para Móviles / Smartphones",
            270,    // "Garantías para Terminales",

            273,    // Outlet
        ]; */
    }


    /************** PRIVATE FUNCTIONS ***************/


    private function getSupplierImagesOnline($id_manufacturer = null, $reference = null)
    {
        try {
            //return "http://www.locurainformatica.com/img/p/" . $supplier_product->id_product . "-" . $supplier_product->id_image . ".jpg";
            //$products_no_image = Product::doesntHave('images')->get();
            $supplier_images_online = DB::connection('mysql_idiomund')->table('sil_product as p')
                ->select(
                    'p.id_product as id_product',
                    'p.id_manufacturer as id_manufacturer',
                    'p.reference as reference',
                    'i.id_image as id_image')
                ->join('sil_image as i', 'p.id_product', '=', 'i.id_product')
                ->where('p.quantity', '>', 0);

            if (isset($id_manufacturer)) $supplier_images_online = $supplier_images_online->where('id_manufacturer', $id_manufacturer);
            if (isset($reference)) $supplier_images_online = $supplier_images_online->where('reference', $reference);

            return $supplier_images_online->get();

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$id_manufacturer, $reference]);
        }
    }


    private function getSupplierImageOnline(Product $product, $id_manufacturer, $reference)
    {
        try {
            $supplier_product_images = $this->supplier_images_online
                ->filter(function ($spi) use ($id_manufacturer, $reference) {
                    return (
                        ($spi->id_manufacturer == $id_manufacturer && $spi->reference == $reference)
                    );
                });

            // Insert Images Found
            if (isset($supplier_product_images) && $supplier_product_images->count()) {
                $id_product = null;
                foreach ($supplier_product_images as $supplier_product_image) {
                    // Prevent insert duplicate images OF duplicates products OF diferent id_products
                    if (!isset($id_product)) $id_product = $supplier_product_image->id_product;
                    if ($supplier_product_image->id_product == $id_product) {
                        $image_url = "http://www.locurainformatica.com/img/p/" . $id_product . "-" . $supplier_product_image->id_image . ".jpg";
                        $product->updateOrCreateExternalImage($image_url);
                    }
                }

                return true;
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product, $id_manufacturer, $reference]);
        }
    }


    private function getSupplierBrands()
    {
        try {
            $supplier_brands = $this->idiomund_supplier->supplier_brands;
            $supplier_brands = $supplier_brands->mapWithKeys(function ($supplier_brand) {
                return [$supplier_brand->supplierBrandId => $supplier_brand->name];
            });

            return $supplier_brands->toArray();

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getSupplierCategory($supplier_product_id_category_default)
    {
        try {
            $id_category_default = $this->icategories_group[$supplier_product_id_category_default] ?? $supplier_product_id_category_default;
            // 'supplier_id', 'category_id', 'name', 'supplierCategoryId'
            $supplier_category = SupplierCategory::firstOrCreate([
                'supplier_id'           => $this->idiomund_supplier->id,
                'supplierCategoryId'    => $id_category_default
            ],[
                'name'                  => '',
                //'category_id'       => null,
            ]);

            if ($supplier_category->name == '') {
                if ($sil_category = DB::connection('mysql_idiomund')->table('sil_category_lang as cl')
                    ->select('cl.name as category_name')
                    ->where('cl.id_category', '=', $supplier_category->supplierCategoryId)
                    ->where('cl.id_lang', '=', 3)
                    ->first()) {

                    $supplier_category->name = $sil_category->category_name;
                    $supplier_category->save();
                }
            }

            if (!isset($supplier_category->category_id))
                $this->nullAndStorage(__METHOD__, [$supplier_product_id_category_default, $supplier_category]);

            return $supplier_category;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product_id_category_default);
        }
    }


    private function getBrandName($id_manufacturer)
    {
        try {
            $sil_manufacturer = DB::connection('mysql_idiomund')->table('sil_manufacturer as m')
                ->select('m.name as manufacturer_name')
                ->where('m.id_manufacturer', '=', $id_manufacturer)->first();

            if ($sil_manufacturer) {
                $manufacturer_name = $sil_manufacturer->manufacturer_name;
                if ($manufacturer_name == 'Giga-Byte') $manufacturer_name = 'Gigabyte';
                $manufacturer_name = str_replace('.', '', $manufacturer_name);

                return $manufacturer_name;
            }

            return $this->nullAndStorage(__METHOD__, $id_manufacturer);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $id_manufacturer);
        }
    }


    private function getBrandId($supplier_product_id_manufacturer)
    {
        try {
            $id_manufacturer = $this->ibrands_group[$supplier_product_id_manufacturer] ?? $supplier_product_id_manufacturer;

            // Add new supplier brand
            if (!isset($this->supplier_brands[$id_manufacturer])) {
                if ($manufacturer_name = $this->getBrandName($id_manufacturer)) {

                    SupplierBrand::create([
                        'supplier_id'       => $this->idiomund_supplier->id,
                        'name'              => $manufacturer_name,
                        'supplierBrandId'   => $supplier_product_id_manufacturer
                    ]);

                    $this->supplier_brands[$id_manufacturer] = $manufacturer_name;
                }
            }

            $brand = Brand::firstOrCreate(
                ['name' => $this->supplier_brands[$id_manufacturer] ?? $id_manufacturer],
                []
            );

            if (!isset($this->supplier_brands[$id_manufacturer]))
                return $this->nullAndStorage(__METHOD__, $supplier_product_id_manufacturer);

            return $brand->id;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product_id_manufacturer);
        }
    }


    private function getSupplier($id_importador)
    {
        try {
            if ($supplier_id = $this->supplier_ids[$id_importador] ?? null)
                if ($supplier = Supplier::find($supplier_id))
                    return $supplier;

            return $this->nullAndStorage(__METHOD__, $id_importador);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $id_importador);
        }
    }


    private function getStatusId($id_importador)
    {
        // 11 => Almacen Outlet & 50 => Almacen Outlet Web
        return (in_array($id_importador, [11,50])) ? 3 : 1;     // Remanufacturado | Nuevo
    }


    private function getUpdateProductFields($supplier_product)
    {
        try {
            // BRAND
            $brand_id = $this->getBrandId($supplier_product->id_manufacturer);

            // STATUS
            $status_id = $this->getStatusId($supplier_product->id_importador);

            // CATEGORY
            $supplier_category = $this->getSupplierCategory($supplier_product->id_category_default);

            // CURRENCY
            $currency_id = 1;   // EUR - Euro

            // SUPPLIER PARAMS
            //$cost = FacadesMpe::roundFloat($supplier_product->rapell_price_noshipping);
            $cost = FacadesMpe::roundFloat($supplier_product->wholesale_price);
            // Remove Canon from Techdata & Desyman
            if (in_array($supplier_product->id_importador, $this->wholesale_price_with_canon_id_importadors)) {
                if ($supplier_category->category_id && $canon = $supplier_category->category->getCanon())
                    $cost -= $canon;
            }

            // Add Blanes Ports 4€ -> Afegit a Supplier Params
            //if ($supplier_product->id_importador == 1) $cost += $this->ports_blanes;

            $canon = floatval($supplier_product->rapell_price) - floatval($supplier_product->rapell_price_noshipping);
            if ($canon > 0)  $canon = FacadesMpe::roundFloat($canon);
            else $canon = 0;

            $ean = ($supplier_product->ean13 == '') ? null : $supplier_product->ean13;
            [$pn, $ean] = FacadesMpe::getPnEan($supplier_product->reference, $ean);

            return [
                'cost'                  => $cost,
                'canon'                 => $canon,
                'tax'                   => $this->tax_max,      // 21
                'stock'                 => $supplier_product->quantity,
                'currency_id'           => $currency_id,
                'supplierSku'           => $supplier_product->id_product_supplier,
                'supplier_category_id'  => $supplier_category->id ?? null,
                'category_id'           => $supplier_category->category_id ?? null,

                'brand_id'              => $brand_id,
                'pn'                    => $pn,
                'ean'                   => $ean,
                'status_id'             => $status_id,
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function getCreateProductFields($supplier_product)
    {
        try {
            $name = FacadesMpe::getString($supplier_product->name);
            $longdesc = FacadesMpe::getText($supplier_product->description);
            $shortdesc = FacadesMpe::getText($supplier_product->description_short);
            $keywords = null;
            if (!$name || $name == '') $name = $shortdesc;

            return [
                'name'          => $name,
                'shortdesc'     => $shortdesc,
                'longdesc'      => $longdesc,
                'keywords'      => $keywords,
                'parent_id'     => null,

                'upc'           => null,
                'isbn'          => null,
                'gtin'          => null,
                'model'         => null,

                'weight'        => $supplier_product->weight,
                'length'        => null,
                'width'         => null,
                'height'        => null,
                'size'          => null,
                'color'         => null,
                'material'      => null,
                'style'         => null,
                'gender'        => null,
            ];

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function createProduct($supplier_product, array $update_fields, $supplier)
    {
        try {
            if (!$create_fields = $this->getCreateProductFields($supplier_product))
                return $this->nullAndStorage(__METHOD__, $supplier_product);

            if ($product = $supplier->updateOrCreateProduct(
                $update_fields['pn'], $update_fields['ean'], $create_fields['upc'], $create_fields['isbn'], $create_fields['gtin'],
                $update_fields['supplierSku'], $update_fields['brand_id'], $update_fields['supplier_category_id'],
                $update_fields['category_id'], $update_fields['status_id'], $update_fields['currency_id'],
                $create_fields['name'], $create_fields['longdesc'], $update_fields['cost'], $update_fields['tax'], $update_fields['stock'],
                $create_fields['weight'], $create_fields['length'], $create_fields['width'], $create_fields['height'],
                $create_fields['parent_id'], null, null, $create_fields['model'], $create_fields['keywords'], $create_fields['shortdesc'],
                $create_fields['size'], $create_fields['color'], $create_fields['material'], $create_fields['style'], $create_fields['gender'])) {

                // Update Category Canon (ONLY Blanes)
                if ($update_fields['canon'] > 0 && isset($product->category_id) && $supplier->id == 1)
                    $product->category->firstOrCreateCanon($update_fields['canon'], 'es');

                // Images
                $res_img = null;
                if (isset($supplier_product->image) && trim($supplier_product->image) != '')
                    $res_img = $product->updateOrCreateExternalImage($supplier_product->image, 0);

                $res_img2 = null;
                if (!isset($res_img))
                    $res_img2 = $product->getMPEProductImages();

                if (!$res_img2)
                    $this->getSupplierImageOnline($product, $supplier_product->id_manufacturer, $supplier_product->reference);

                //$product->logPrice(true);

                return $product;
            }

            return $this->msgAndStorage(__METHOD__,
                'Error creando actualizando producto de proveedor ID: '.$supplier_product[$this->supplier->sku_src_field],
                [$supplier_product, $update_fields, $supplier]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $supplier_product);
        }
    }


    private function updateProduct(Product $product, $supplier_product, array $update_fields, $supplier, $full_update_old_products = false)
    {
        try {
            if ($product->cost != $update_fields['cost'] ||
                $product->stock != $update_fields['stock'] ||
                $product->supplier_category_id != $update_fields['supplier_category_id'] ||
                $product->category_id != $update_fields['category_id'] ||
                $product->status_id != $update_fields['status_id'])

                    $product->updateCostStock(
                        $update_fields['supplierSku'],
                        $update_fields['cost'],
                        $update_fields['tax'],
                        $update_fields['currency_id'],
                        $update_fields['stock'],
                        $update_fields['brand_id'],
                        $update_fields['supplier_category_id'],
                        $update_fields['category_id'],
                        $update_fields['status_id']
                    );

            if ($product->name == '' && isset($create_fields['name']) && $create_fields['name'] != '') {
                $product->name = $create_fields['name'];
                $product->save();
            }

            if ($full_update_old_products) {

                if (!$create_fields = $this->getCreateProductFields($supplier_product))
                    return $this->nullAndStorage(__METHOD__, [$product, $supplier_product, $full_update_old_products]);

                if ($product = $supplier->updateOrCreateProduct(
                    $update_fields['pn'], $update_fields['ean'], $create_fields['upc'], $create_fields['isbn'], $create_fields['gtin'],
                    $update_fields['supplierSku'], $update_fields['brand_id'], $update_fields['supplier_category_id'],
                    $update_fields['category_id'], $update_fields['status_id'], $update_fields['currency_id'],
                    $create_fields['name'], $create_fields['longdesc'], $update_fields['cost'], $update_fields['tax'], $update_fields['stock'],
                    $create_fields['weight'], $create_fields['length'], $create_fields['width'], $create_fields['height'],
                    $create_fields['parent_id'], null, null, $create_fields['model'], $create_fields['keywords'], $create_fields['shortdesc'],
                    $create_fields['size'], $create_fields['color'], $create_fields['material'], $create_fields['style'], $create_fields['gender'])) {

                    // IMAGES
                    $product->deleteAllImages();
                    //$this->createProductImages($product, $supplier_product);

                    // Images
                    $res_img = null;
                    if (isset($supplier_product->image) && $supplier_product->image != '')
                        $res_img = $product->updateOrCreateExternalImage($supplier_product->image, 0);

                    $res_img2 = null;
                    if (!isset($res_img))
                        $res_img2 = $product->getMPEProductImages();

                    if (!$res_img2)
                        $this->getSupplierImageOnline($product, $supplier_product->id_manufacturer, $supplier_product->reference);
                }
                else
                    return $this->msgAndStorage(__METHOD__,
                        'Error creando actualizando producto ID: '.$product->id,
                        [$product, $supplier_product, $update_fields, $supplier, $full_update_old_products]);
            }

            //if ($product->wasChanged()) $product->logPrice(false);

            return $product;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product, $supplier_product, $full_update_old_products]);
        }
    }


    private function getFilteredSupplierProducts()
    {
        try {
            $idiomund_supplier_filters = $this->idiomund_supplier->supplier_filters;
            if (!$idiomund_supplier_filters->count())
                return $this->nullAndStorage(__METHOD__, $idiomund_supplier_filters);

            $query = null;
            foreach ($idiomund_supplier_filters as $supplier_filter) {

                $new_query = Iproduct::filter()
                    ->whereIn('id_importador', array_keys($this->supplier_ids))
                    ->whereNotIn('id_category_default', $this->rejected_categories)
                    ->where('quantity', '>', 0);

                if ($supplier_filter->brand_name) {
                    $new_query->where($this->idiomund_supplier->brand_field, intval($supplier_filter->brand_name));  // id_manufacturer
                }
                if ($supplier_filter->category_name) {
                    $new_query->where($this->idiomund_supplier->category_field, intval($supplier_filter->category_name));    // id_category_default
                }
                if ($supplier_filter->type_name) {
                    $new_query->where($this->idiomund_supplier->type_field, $supplier_filter->type_name);
                }
                if ($supplier_filter->status_name) {
                    $new_query->where($this->idiomund_supplier->status_field, $supplier_filter->status_name);
                }
                if ($supplier_filter->name) {
                    $new_query->where($this->idiomund_supplier->name_field, 'like', '%'.$supplier_filter->name.'%');
                }
                if ($supplier_filter->model) {
                    $new_query->where($this->idiomund_supplier->model_field, $supplier_filter->model);
                }
                if ($supplier_filter->supplierSku) {
                    $new_query->where($this->idiomund_supplier->supplierSku_field, $supplier_filter->supplierSku);
                }
                if ($supplier_filter->pn) {
                    $new_query->where($this->idiomund_supplier->pn_field, $supplier_filter->pn);
                }
                if ($supplier_filter->ean) {
                    $new_query->where($this->idiomund_supplier->ean_field, $supplier_filter->ean);
                }
                if ($supplier_filter->upc) {
                    $new_query->where($this->idiomund_supplier->upc_field, $supplier_filter->upc);
                }
                if ($supplier_filter->isbn) {
                    $new_query->where($this->idiomund_supplier->isbn_field, $supplier_filter->isbn);
                }
                if ($supplier_filter->cost_min) {
                    $new_query->where($this->idiomund_supplier->cost_field, '>=', $supplier_filter->cost_min);
                }
                if ($supplier_filter->cost_max) {
                    $new_query->where($this->idiomund_supplier->cost_field, '<=', $supplier_filter->cost_max);
                }
                if ($supplier_filter->stock_min) {
                    $new_query->where($this->idiomund_supplier->stock_field, '>=', $supplier_filter->stock_min);
                }
                if ($supplier_filter->stock_max) {
                    $new_query->where($this->idiomund_supplier->stock_field, '<=', $supplier_filter->stock_max);
                }

                if ($supplier_filter->field_name && $supplier_filter->field_operator) {
                    if ($supplier_filter->field_string) {
                        $new_query->where($supplier_filter->field_name, $supplier_filter->field_operator, $supplier_filter->field_string);
                    }
                    if ($supplier_filter->field_integer) {
                        $new_query->where($supplier_filter->field_name, $supplier_filter->field_operator, $supplier_filter->field_integer);
                    }
                    if ($supplier_filter->field_float) {
                        $new_query->where($supplier_filter->field_name, $supplier_filter->field_operator, $supplier_filter->field_float);
                    }
                }

                if ($supplier_filter->limit_products) {
                    $new_query->take($supplier_filter->limit_products);
                }

                $query = isset($query) ? $query->union($new_query) : $new_query;
            }

            if (!isset($query))
                $query = Iproduct::filter()
                    ->whereIn('id_importador', array_keys($this->supplier_ids))
                    ->whereNotIn('id_category_default', $this->rejected_categories)
                    ->where('quantity', '>', 0);

            return $query->get();

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function updateOrCreateProducts($supplier_products, $create_new_products = false, $full_update_old_products = false)
    {
        try {
            $processed_ids = [];
            $res = [
                'updateds'      => 0,
                'news'          => 0,
                'no_updateds'   => 0,
                'no_news'       => 0,
            ];

            $res['starts_at'] = date('Y-m-d_H-i-s');
            $res['supplier_ids'] = array_values($this->supplier_ids);

            if ($supplier_products->count()) {

                $count = 0;
                foreach ($supplier_products as $supplier_product) {

                    if (!$supplier = $this->getSupplier($supplier_product->id_importador)) {
                        $res['importador Not Founds'][$supplier_product->id_importador] = null;
                        continue;
                    }
                    if (!$update_fields = $this->getUpdateProductFields($supplier_product)) continue;

                    $product = $supplier->getSimilarProduct(
                        $update_fields['status_id'],
                        $update_fields['brand_id'],
                        $update_fields['pn'],
                        $update_fields['ean']);

                    // Update cost & stock
                    if (isset($product)) {

                        if ($product->ready) {
                            if ($updated = $this->updateProduct($product, $supplier_product, $update_fields, $supplier, $full_update_old_products)) {
                                $res['updateds']++;
                                $processed_ids[] = $updated->id;
                            }
                            else
                                $res['no_updateds']++;
                        }
                    }
                    // Create new product
                    elseif ($create_new_products) {
                        if ($created = $this->createProduct($supplier_product, $update_fields, $supplier)) {
                            $res['news']++;
                            $processed_ids[] = $created->id;
                        }
                        else
                        $res['no_news']++;
                    }

                    $count++;
                }

                // Resets OLD NOT FOUND product stocks of current supplier
                if (count($processed_ids))
                    Product::whereIn('supplier_id', array_values($this->supplier_ids))
                        ->whereNotIn('id', $processed_ids)
                        ->update(['stock' => 0]);
            }

            $res['ends_at'] = date('Y-m-d_H-i-s');
            return $res;
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, [$create_new_products, $full_update_old_products, $res]);
        }
    }



    /************** PUBLIC FUNCTIONS ***************/


    public function getProduct(Product $product)
    {
        try {
            //$supplier_product = Iproduct::whereIdProductSupplier(intval($product->supplierSku))->first();
            if ($supplier_product = Iproduct::whereReference($product->pn)->whereEan13($product->ean)->first()) {
                Storage::append($this->storage_dir. 'products/' .date('Y-m-d_H-i'). '_' .$product->supplierSku. '.json', json_encode($supplier_product));
                // Get Supplier Images FROM Panel57.tienda
                $this->supplier_images_online = $this->getSupplierImagesOnline($supplier_product->id_manufacturer, $supplier_product->reference);

                if (!$supplier = $this->getSupplier($supplier_product->id_importador))
                    return $this->msgAndStorage(__METHOD__, 'Proveedor no encontrado.', $product);

                if ($update_fields = $this->getUpdateProductFields($supplier_product))
                    if ($this->updateProduct($product, $supplier_product, $update_fields, $supplier, false))
                        return 'Producto actualizado correctamente.';
            }

            return $this->msgAndStorage(__METHOD__, 'Error al obtener el producto del Proveedor.', $product->toArray());
        }
        catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $product->toArray());
        }

    }


    public function getProducts()
    {
        //return $this->getAllProducts();

        // Get ONLINE Supplier Products
        if ($supplier_products = $this->getFilteredSupplierProducts()) {
            Storage::put($this->storage_dir. 'products/' .date('Y-m-d_H-i'). '_getProducts.json', json_encode($supplier_products));
            // Get Supplier Images Online
            $this->supplier_images_online = $this->getSupplierImagesOnline();

            return $this->updateOrCreateProducts($supplier_products, true, false);
        }

        return 'Error obteniendo los productos.';
    }


    public function getPricesStocks()
    {
        //return $this->getAllProducts(true);

        if ($supplier_products = $this->getFilteredSupplierProducts()) {
            Storage::put($this->storage_dir. 'products/' .date('Y-m-d_H-i'). '_getPricesStocks.json', json_encode($supplier_products));
            // Get Supplier Images Online
            //$this->supplier_images_online = $this->getSupplierImagesOnline();

            return $this->updateOrCreateProducts($supplier_products, false, false);
        }

        return 'Error actualizando los productos.';
    }

}
