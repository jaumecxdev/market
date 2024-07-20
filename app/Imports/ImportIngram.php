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
use ZipArchive;

class ImportIngram
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers', 'importCanons', 'importCategories'];

    public static $formats = [
        'products'    => [
            'columns'       => 29,
            'header_rows'   => 0,
            'filename'      => 'PRICE.ZIP',
            'zipped'        => true,
            'unzipped_filename' => 'PRICE.TXT',

        ],
        // STOCKS ONLY
        'offers'    => [
            'columns'       => 5,
            'header_rows'   => 1,
            'filename'      => 'AVAIL/TOTESHRL.ZIP',
            'zipped'        => true,
            'unzipped_filename' => 'TOTESHRL.TXT',

        ],
        'canons'    => [
            'columns'       => 5,
            'header_rows'   => 1,
            'filename'      => 'ESSKUFEC.TXT',

        ],
        'categories'    => [
            'columns'       => 3,
            'header_rows'   => 1,
            'filename'      => '/users/integracion/Ficheros/catscat.txt',
        ],
    ];

    const FTP_DISK = 'ftp_ingram';

    const IMPORT_TEXT = "<b>Importación de productos</b> PRICE.ZIP - Precios y stocks noche anterior<br>".
        "<b>Importación de ofertas</b> AVAIL/TOTESHRL.ZIP - Importación SOLO de stocks.<br>".
        "<b>Importación de Canones</b> ESSKUFEC.TXT - Listados de Canones por cada SKU.<br>".
        "<b>Importación de Categorías</b> Ficheros/catscat.txt - Listados de Categorías.";

    const REJECTED_CATEGORIES = [
        '0066',     // Ordenadores / Garantias Ordenadores
        '0766',     // Impresoras/Multifunc/Copiadoras/Fax / Garantias Impresoras
        '1235',     // Cables / Hdmi Cable
        '0359',     // Monitores & TV / Kits de Montaje TV
        '0440',     // Networking/Comunicaciones / Transceptores y Convertidores.
        '0490',     // Networking/Comunicaciones / Switches Gestionables Layer 2
        '0402',     // Networking/Comunicaciones / Switches Gestionables Layer 3
        '0005',     // Ordenadores / Servidores
        '1230',     // Cables / Cables y Adaptadores Monitor
        '1541',     // Software / Software Sistemas Operativos
        '1206',     // Cables / Cables y Adaptadores USB
        '1204',     // Cables / Cables y Adaptadores Serie
        '0516',     // Accesorios Discos Duros/Opticos
        '0515',     // Discos Duros Serial Attached SCSI
        '1540',     // Software / Sistema Operativo para Servidor
        '0202',     // Almacenamiento / Controladoras RAID
        '0350',     // Monitores & TV / Accesorios Monitores
        '1238',     // Cables / Cables Transferencia Datos
        '0266',     // Almacenamiento / Garantias Almacenamiento
        '1116',     // Accesorios / KITS Montaje
        '1577',     // Software / Software Gestion Red/Utilidades
        '1120',     // Accesorios / Accesorios Rack
        '4706',     // 4706
        '0483',     // Networking/Comunicaciones / Networking Cableado
        '6030',     // DC/POS Consumibles y Accesorios / Fundas
        '1314',     // Proyectores y Pantallas / Accesorios Proyectores
        '0480',     // Networking/Comunicaciones / Modulos Expansion
        '0032',     // Ordenadores / Baterias de Portatil y Adaptadores
        '0441',     // Networking/Comunicaciones / Adaptadores Multiserie
        '0208',     // Almacenamiento / Servidores Almacenamiento
        '5062',     // 5062
        '0463',     // Networking/Comunicaciones / Routers/Gateways Broadband
        '2006',     // Servicio Configuraciones Ingram
        '1033',     // Consumibles / Fusor/Tambor Laser
        '0016',     // Ordenadores / Accesorios Tablet PC
        '1934',     // Security / Dispositivos de Seguridad PC
        '6022',     // DC/POS Consumibles y Accesorios /
        '0035',     // Ordenadores / Terminales
        '1057',     // Consumibles / Consumible Toners Laser Ccolor
        '1234',     // Cables / Cables Video y Audio
        '0892',     // Multimedia Hardware/Juegos / Cajas SETTOP
        '1923',     // Security / Wireless Videosecurity System
        '2102',     // Medical Equipments / Carts
        '1210',     // Cables / Cables y Adaptadores Fibra Optica
        '1218',     // Cables / Cables Corriente
        '1537',     // Software / Software Gestion Tiempo/Proyectos
        '1236',     // Cables / Cables y Adaptadores Firewire
        '0404',     // Networking/Comunicaciones / Switches Gestionables Layer 4-7
        '0407',     // Networking/Comunicaciones / Cables y Accesorios Sitch KVM
        '0418',     // Networking/Comunicaciones / Videoconferencia
        '1212',     // Cables / Cables y Adaptadores SCSI
        '1521',     // Software / Software Graficos/Presentaciones
        '1566',     // Software / Software Garantias
        '0671',     // Dispositivos entrada / Touch Pads
        '0481',     // Networking/Comunicaciones / Firewalls/Seguridad Red
        '1220',     // Cables / Cables y Conectores
        '0451',     // Networking/Comunicaciones / Terminal Servers
        '0466',     // Networking/Comunicaciones / Garantias Networking
        '6006',     // DC/POS Consumibles y Accesorios / Baterias de Terminales Portatiles

        '1766',     // Escáners/Cámaras / Garantias Camaras/Escaners
        '0416',     // Networking/Comunicaciones / Servidor Voz IP
        '0041',     // Ordenadores / Accesorios Portatiles/Notebooks

        '1926','1930','1902','1912','1928','1913','0292','0210','5703','5704','5909','5702','0443','5503','6004','6002','5701',
        '0494','5804','6024','1918','5907','6054','5603','5505','5805','1073','6043','5801','4910','6048','5502','0805','5504',
        '2104','6069','1803','1919','1114','5803','6050','5605','0121','1131','0704','0454','0492','1424','1801','6014','6063',
        '6012','6052','6010','6065','5802','5604','0281','6045','1038','6042','6034','6068','6059','6032','1112','0899','1805',
        '0042','6038','5908','1568','0039','5913','1050','1013','0718','1061','0141','0161','0166','1261','0293','0420','0450',
        '5904','1106','1030','0794','0412','0251','1058',

        '1254', '1567', '6203', '6057', '6202', '0030', '6040', '1062', '2103', '1507', '1584',
        '6008', '0701', '1208',
        '0215', '5912', '0046', '1246'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'ingram'
            ],
            [
                'name'          => 'Ingrammicro',
                'type_import'   => 'file',
                'ws'            => 'SupplierImportFtpWS'
            ]
        );
    }



    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['C'];
            if (!$brand_name || $brand_name == '') return null;

            $brand_name = ucwords(strtolower($brand_name));

            if ($brand_name == 'Elo Touch Systems') $brand_name = 'Elo Touch';
            if ($brand_name == 'Mmd - Philips') $brand_name = 'MMD';

            return $brand_name;

        } catch (Throwable $th) {
            return null;
        }
    }


    public function importProducts(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];

            $zip = new ZipArchive;
            if ($zip->open($uploaded_file) === TRUE) {
                $unzipped = $zip->extractTo(storage_path('app/supplier/ingram/'));
                $zip->close();
            }
            $inputFileName = storage_path('app/supplier/ingram/').'/'.self::$formats['products']['unzipped_filename'];
            //$file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::$formats['products']['header_rows']);

            $file_rows = FacadesMpeImport::getFileRows($inputFileName, 0);
            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows); //->keyBy((string)$this->supplier->supplierSku_field);
            unset($file_rows);
            //$productsCollect = $this->supplier->filterProducts($productsCollect);

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
            if (count($productsCollect->first()) != self::$formats['products']['columns'])
                return 'No tiene '.self::$formats['products']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $unmapped_categories = [];
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                // NO Activado, bajo pedido o reacondicionado
                if ($row['A'] != 'A' || in_array($row['M'], ['S', 'P'])) continue;
                $stock = intval($row['X']);
                /* if ($cost = floatval($row['Y']))
                    $cost = number_format($cost, 2, '.'); */
                $cost = number_format((float)round($row['Y'], 2), 2, '.', '');
                $currency = $row['AA'];
                if ($stock < 5 || $cost < 50) continue;   // || $currency == 'USD'

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $name = FacadesMpe::getString($row['G']).' '.FacadesMpe::getString($row['H']);
                if (!isset($name) || $name == '') continue;

                $supplierCategoryId = $row['F'];
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => $supplierCategoryId,
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = '';

                $supplierSku = trim($row['B']);
                [$pn, $ean] = FacadesMpe::getPnEan(trim($row['D']), trim($row['E']));
                $longdesc = $name;
                $weight = floatval($row['I']);
                $length = floatval($row['L']);
                $width = floatval($row['K']);
                $height = floatval($row['J']);

                $product = $supplier->getSimilarProduct(1, $brand->id, $pn, $ean);

                // Update cost & stock
                if (isset($product)) {

                    $product->updateCostStock($supplierSku,
                        $cost,
                        21,
                        1,
                        $product->stock,            // NO UPDATE STOCK -> STOCK LAST DAY
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
                        $length, $width, $height, null, null, null, null, null, null, null, null, null, null, null);
                }

                $imported[] = $product->id;
                $imported_count++;
            }

            /* if (count($imported))
                $supplier->products()->whereNotIn('products.id', $imported)->update(['stock' => 0]); */

            $msg = 'Importados '.$imported_count. ' productos.';
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

            $zip = new ZipArchive;
            if ($zip->open($uploaded_file) === TRUE) {
                $unzipped = $zip->extractTo(storage_path('app/supplier/ingram/'));
                $zip->close();
            }
            $inputFileName = storage_path('app/supplier/ingram/').'/'.self::$formats['offers']['unzipped_filename'];
            $file_rows = FacadesMpeImport::getFileRows($inputFileName, 0);

            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows);
            unset($file_rows);

            return self::offers($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function offers(Supplier $supplier, Collection $productsCollect)
    {
        try {
            //$m = memory_get_usage();
            // test array
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['offers']['columns'])
                return 'No tiene '.self::$formats['offers']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            // FIRST IMPORT STOCK
            $imported = [];
            $imported_count = 0;
            foreach($productsCollect as $row) {

                $supplierSku = $row['A'];
                $stock = intval($row['C']);
                if ($stock < 5) continue;

                $product = $supplier->products()->firstWhere('supplierSku', $supplierSku);
                if ($product) {

                    if (!$product->supplier_category_id || in_array($product->supplier_category->supplierCategoryId, self::REJECTED_CATEGORIES)) continue;

                    $product->stock = $stock;
                    $product->status_id = 1;
                    $product->save();

                    $imported[] = $product->id;
                    $imported_count++;
                }
            }

            unset($productsCollect);
            if (count($imported))
                $supplier->products()->whereNotIn('products.id', $imported)->update(['stock' => 0]);


            // THEN IMPORT PRICES
            $ftp_disk = self::FTP_DISK;
            $ftp_filename = self::$formats['products']['filename'];
            $header_rows = self::$formats['products']['header_rows'];

            $zipped = self::$formats['products']['zipped'] ?? false;
            $directory = $supplier->storage_dir.'products/';
            $filename = self::$formats['products']['unzipped_filename'] ?? date('Y-m-d_H'). '_products.csv';

            $file_rows = FacadesMpeImport::getRowsFtp($ftp_disk, $ftp_filename, $header_rows, $directory, $filename, $zipped);

            $res_prices = null;

            if (is_array($file_rows)) {
                if ($pricesCollect = collect($file_rows)) {
                    unset($file_rows);
                    $res_prices = self::offersCost($supplier, $pricesCollect);
                    unset($pricesCollect);
                }
            }

            //$mm = memory_get_usage();

            $msg = 'Importados stocks '.$imported_count. ' productos.';
            $msg .= ' Resultado precios: '.$res_prices;

            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function offersCost(Supplier $supplier, Collection $productsCollect)
    {
       try {
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['products']['columns'])
                return 'No tiene '.self::$formats['products']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $imported = [];
            $imported_count = 0;
            foreach ($productsCollect as $row) {

                // NO Activado, bajo pedido o reacondicionado
                if ($row['A'] != 'A' || in_array($row['M'], ['S', 'P'])) continue;
                $cost = number_format((float)round($row['Y'], 2), 2, '.', '');
                $supplierSku = trim($row['B']);

                $product = $supplier->products()->firstWhere('supplierSku', $supplierSku);

                // Update cost & stock
                if ($product && $product->supplier_category_id && !in_array($product->supplier_category->supplierCategoryId, self::REJECTED_CATEGORIES)) {
                    $product->cost = $cost;
                    $product->save();
                    $imported_count++;
                    $imported[] = $product->id;
                }
            }

            unset($productsCollect);
            if (count($imported)) {
                if ($products_4_delete = $supplier->products()->whereNotIn('products.id', $imported)->get())
                    foreach ($products_4_delete as $product_4_delete)
                        $product_4_delete->deleteSecure();

            }


            return 'Importados '.$imported_count. ' productos.';

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importCanons(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];

            if (!$file_rows = file($uploaded_file->getPathname()))
                return null;

            return self::canons($this->supplier, $file_rows);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function canons(Supplier $supplier, $file_rows)
    {
        try {
            // test array
            if (!isset($file_rows) || !count($file_rows))
                return 'No hay filas para importar.';

            $imported_count = 0;
            foreach ($file_rows as $line) {

                $row = str_getcsv($line, ",");
                if (count($row) != self::$formats['canons']['columns'])
                    return 'No tiene '.self::$formats['canons']['columns']. ' columnas. Tiene '.count($row);

                $supplierSku = trim($row[1]);
                $canon = number_format((float)round($row[3], 2), 2, '.', '');
                if ($canon < 0.01) continue;

                if ($product = $supplier->products()->where('supplierSku', $supplierSku)->first())
                    if ($product->category_id) {
                        $product->category->firstOrCreateCanon($canon, 'es');
                        $imported_count++;
                    }
            }

            $msg = 'Importados '.$imported_count. ' canones de productos.';
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importCategories(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::$formats['categories']['header_rows']);

            if (!is_array($file_rows)) return $file_rows;
            $categoriesCollect = collect($file_rows);

            return self::categories($this->supplier, $categoriesCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function categories(Supplier $supplier, $categoriesCollect)
    {
        try {
            if (!isset($categoriesCollect) || !$categoriesCollect->count())
                return 'No hay filas para importar.';
            if (count($categoriesCollect->first()) != self::$formats['categories']['columns'])
                return 'No tiene '.self::$formats['categories']['columns']. ' columnas. Tiene '.count($categoriesCollect->first());

            $unmapped_categories = [];
            $imported_count = 0;
            $imported = [];
            foreach($categoriesCollect as $row) {

                $supplierCategoryId = $row['A'];
                $supplierCategoryName = trim($row['B']). ' / ' .trim($row['C']);
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                if ($supplier_category = $supplier->supplier_categories()->where('supplierCategoryId', $supplierCategoryId)->first()) {
                    $supplier_category->name = $supplierCategoryName;
                    $supplier_category->save();

                    if (!isset($supplier_category->category_id))
                        $unmapped_categories[$supplier_category->supplierCategoryId] = '';

                    $imported[] = $supplier_category->id;
                    $imported_count++;
                }
               /*  $supplier_category = SupplierCategory::updateOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => $supplierCategoryName,
                ]); */

            }

            $msg = 'Importados '.$imported_count. ' categorías.';
            if (count($unmapped_categories)) $msg .= ' Categorias sin mapear: '.json_encode($unmapped_categories);
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }

}
