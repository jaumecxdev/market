<?php

namespace App\Imports;

use App\Brand;
use App\Supplier;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class ImportTechdata
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importPrices', 'importStocks', 'importImages', 'importCategories'];

    public static $formats = [
        'products'    => [
            'columns'       => 26,
            'header_rows'   => 1,
            'filename'      => 'TD_ES_773202_A_',         // TD_ES_773202_A_20210618.zip
            'zipped'        => true,
            'unzipped_filename' => 'GM_ES_C_Product',     // GM_ES_C_Product20210618.txt
        ],
        // STOCKS ONLY
        'offers'    => [
            'columns'       => 8,
            'header_rows'   => 1,
            'filename'      => 'TD_ES_773202_A_',         // TD_ES_773202_A_20210618.zip
            'zipped'        => true,
            'unzipped_filename' => 'GM_ES_C_Prices',
           /*  'columns'       => 5,
            'header_rows'   => 1,
            'filename'      => 'StockFile.txt',  */        // + Prices: GM_ES_C_Prices20210618.txt
            /* 'zipped'        => true,
            'unzipped_filename' => 'TOTESHRL.TXT', */

        ],

        'prices'    => [
            'columns'       => 8,
            'header_rows'   => 1,
            'filename'      => 'TD_ES_773202_A_',         // TD_ES_773202_A_20210618.zip
            'zipped'        => true,
            'unzipped_filename' => 'GM_ES_C_Prices',     // GM_ES_C_Prices20210618.txt

        ],
        'stocks'    => [
            'columns'       => 5,
            'header_rows'   => 1,
            'filename'      => 'StockFile.txt',         // + Prices: GM_ES_C_Prices20210618.txt
        ],
        'images'    => [
            'columns'       => 7,
            'header_rows'   => 1,
            'filename'      => 'Openicecat/TD_ES_mapping.txt',

        ],
        'categories'    => [                              // Same as Products
            'columns'       => 26,
            'header_rows'   => 1,
            'filename'      => 'TD_ES_773202_A_',         // TD_ES_773202_A_20210618.zip
            'zipped'        => true,
            'unzipped_filename' => 'GM_ES_C_Product',     // GM_ES_C_Product20210618.txt
        ],
    ];


    const FTP_DISK = 'ftp_tech';

    const IMPORT_TEXT = "<b>1o Productos, 2o Precios, 3o Stocks 4o Imagenes</b><br>".
        "<b>Importación de Productos</b> TD_ES_773202_A_.zip - Fichas<br>".
        "<b>Importación de precios</b> TD_ES_773202_A_.zip - Solo precios<br>".
        "<b>Importación de stocks</b> StockFile.txt - Importación SOLO de stocks.<br>".
        "<b>Importación de Imágenes</b> Openicecat/TD_ES_mapping.txt - 1 imagen y Datasheets XML y CSV.<br>".
        "<b>Importación de Categorías</b> TD_ES_773202_A_.zip - Listados de Fichas, SOLO extrae categorías.";

    const REJECTED_CATEGORIES = [
        'SOFTWARE / SOFTOS / SWOS',
        'COMPONENT / COMCABACC / CABLPRIN',
        'NETWORKNG / NWWIRELSS / WLANSWLIC',
        'PRINTING / PRILAFOR / LFPRIHEAD',
        'COMPUTERS / COMDESK / THINCLI',
        'AVIMAGE / AVICABLE / AVICABLE',
        'PERIPHER / PERSTOR / SSSTOR',
        'PRINTING / PRIPARMAI / PRINMAINT',
        'PRINTING / PRICAME / PRINWASTE',
        'PRINTING / PRIPARMAI / PRINTHEAD',
        'PRINTING / PRIPARMAI / PRINCLEAN',
        'COMPUTERS / COMSER / TOWERSER',
        'COMPONENT / COMCABACC / CABLINT',
        'SOFTWARE / SOFTNW / SWNW',
        'PRINTING / PRICAME / OTHCONS',
        'COMPUTERS / COMACCSER / SSCOMPUT',
        'PRINTING / PRICAME / INKSUPP',
        'COMPUTERS / COMSER / ACCSERVER',
        'SOFTWARE / SOFTDEVTO / SWDEVTOOL',
        'MOBILITY / IOTCONSUM / IOTINDUST',
        'COMPONENT / COMCABACC / CABLPWR',
        'SOFTWARE / SOFTSTOR / SWSTOR',
        'NETWORKNG / NWBACKUP / NWBCKACC',
        'COMPUTERS / COMSER / RACKSER',
        'COMPONENT / COMCABACC / CABLKVM',
        'PERIPHER / PERREMME / LTOMEDIA',
        'NETWORKNG / NWSECURE / NWSECACC',
        'COMPONENT / COMCABACC / CABLDISP',
        'SOFTWARE / SOFTGAMES / GAMES',
        'SOFTWARE / SOFTSERVA / SWSERVAP',
        'NETWORKNG / NWSS / SSNW',
        'COMPONENT / COMCABACC / CABLSTOR',
        'AVIMAGE / AVIDICAVI / CAMLENSES',
        'AVIMAGE / AVPROF / AVPROCABL',
        'PRINTING / PRICAME / PAPER',
        'SOFTWARE / SOFTSS / SWSS',
        'PERIPHER / PERSTOR / STORENARR',
        'PRINTING / PRIPARMAI / PRINDRUMS',
        'PERIPHER / PERREMME / SUPERDLT',
        'PERIPHER / PERSTOR / NAS',
        'NETWORKNG / NWBACKUP / TAPEDRIVE',
        'AVIMAGE / AVICONN / AVCOSPLI',
        'NETWORKNG / NWCABLES / NWCABSYST',
        'PERIPHER / PERUPS / UPSACC',
        'AVIMAGE / AVIMOUNT / MOUNTDESK',
        'PRINTING / PRICAME / LABELS',
        'NETWORKNG / NWRACKING / RACKING',
        'AVIMAGE / AVICONN / AVCOAMP',
        'NETWORKNG / NWACCESO / ACCESONW',
        'MOBILITY / TELACC / TELCHARG',
        'COMPUTERS / COMPORT / SCRFILTER',
        'SOFTWARE / SOFTCADAC / SWARCHIT',
        'COMPUTERS / POSSYSTEM / POSACC',
        'AVIMAGE / AVILFD / LFDCONN',
        'NETWORKNG / NWKVM / KVMSWI',
        'NETWORKNG / NWSTORA / NWSTORSAN',
        'PERIPHER / PERUPS / UPSSERV',
        'COMPUTERS / COMSER / BLADESER',
        'NETWORKNG / NWCABLES / NWCABKEY',
        'PERIPHER / PERSTOR / STORADAP',
        'AVIMAGE / AVIMOUNT / MOUNTRACK',
        'MOBILITY / TELMOBSW / MOBSW',
        'SOFTWARE / SOFTUTIL / SWUTILS',
        'NETWORKNG / NWLAN / SWITCHTRA',
        'PRINTING / PRICAME / LASERSUPP',
        'NETWORKNG / NWCABLES / NWCABCON',
        'PERIPHER / PERMONIT / SSOUTPUT',
        'MOBILITY / IOTINDDAT / IOTDATANA',
        'AVIMAGE / AVICABLE / AVICABAUD',
        'NETWORKNG / NWSTORA / NWSTORNAS',
        'NETWORKNG / NWCABLES / NWCABFIBR',
        'MOBILITY / TELMOBSW / MOBMAINT',
        'SOFTWARE / SOFTCADR / SWRETAIL',
        'MOBILITY / IOTINDSEN / IOTSENCIT',
        'PRINTING / PRIPARMAI / PRINAC',
        'PRINTING / PRICAME / LABELTAPE',
        'SOFTWARE / SOFTCADME / SWMEDIA',
        'NETWORKNG / NWSECURE / NWSECFILT',
        'NETWORKNG / NWSTORA / NWSTORARR',
        'PRINTING / PRISERSUP / SSPRIN',
        'COMPONENT / COMCABACC / CABLHDM',
        'PRINTING / PRILAFOR / LFCONSUM',
        'NETWORKNG / NWSECURE / NWSECAUTH',
        'COMPONENT / COMCABACC / CABLFIREW',
        'PRINTING / PRIPARMAI / PRINTRANS',
        'SOFTWARE / SOFTSEC / SWSECUR',
        'NETWORKNG / NWSECURE / NWSECIDS',
        'NETWORKNG / NWLAN / NWLANCONV',
        'PRINTING / PRICAME / RIBBONS',
        'NETWORKNG / NWCABLES / NWCABCOPP',
        'MOBILITY / IOTINDSEN / IOTSENMAN',
        'AVIMAGE / AVILFD / LFDMOUNT',
        'SOFTWARE / SOFTOTH / SWOTHER',
        'COMPONENT / COMCABACC / CABLUSB',
        'PRINTING / PRIPARMAI / PRINFUSER',
        'NETWORKNG / NWSECURE / NWSECFW',
        'AVIMAGE / AVIAUDIO / AUDCOACC',
        'AVIMAGE / AVICOLSOL / MEETROOM',
        'AVIMAGE / AVIDICAVI / DIGCAM',
        'AVIMAGE / AVIDICAVI / IPCAMAC',
        'AVIMAGE / AVIDICAVI / IPCAMERA',
        'AVIMAGE / AVILFD / LFDACC',
        'AVIMAGE / AVILFD / LFDMEDPLA',
        'AVIMAGE / AVICONN / AVCOSWI',
        'AVIMAGE / AVIINTER / INTERLFD',
        'AVIMAGE / AVIINTER / INWHIACC',
        'AVIMAGE / AVIINTER / INTERTAB',
        'AVIMAGE / AVIINTER / INTERWHI',
        'AVIMAGE / AVIINTER / INTERAIO',
        'AVIMAGE / AVIINTER / INTERPROJ',
        'AVIMAGE / AVIINTER / INTERTOOL',
        'AVIMAGE / AVIPROJEC / PROLAMP',
        'AVIMAGE / AVIPROJEC / PROMOUNT',
        'AVIMAGE / AVIMOUNT / MOUNTCART',
        'AVIMAGE / AVITELEV / TELEACC',
        'AVIMAGE / AVIVICONF / CONFACC',
        'COMPONENT / COMCABACC / CABLHDMI',
        'COMPONENT / COMCABACC / CABLACC',
        'COMPONENT / COMBATT / BATTEXT',
        'MOBILITY / IOTCONSUM / IOTHOMAUT',
        'MOBILITY / IOTINDNW / IOTNWSWI',
        'MOBILITY / IOTINDNW / IOTNWACPO',
        'MOBILITY / IOTINDNW / IOTNWGTW',
        'MOBILITY / TELACC / TELCABLE',
        'MOBILITY / TELACC / TELCARCHA',

        'AVIMAGE / AVPROF / AVPROBOX',
        'AVIMAGE / AVPROF / AVPROHEAD',
        'AVIMAGE / AVPROF / AVPROMAMP',
        'AVIMAGE / AVIPORT / AVPORTACC',
        'AVIMAGE / AVICONN / AVCOEXT',
        'AVIMAGE / AVICONN / AVCOPLA',
        'AVIMAGE / AVICONN / REMCONT',
        'AVIMAGE / AVIPROJEC / PROACC',
        'AVIMAGE / AVIPROJEC / PROSCREEN',
        'AVIMAGE / AVIVICONF / VIDEOCONF',
        'AVIMAGE / AVIVISUA / VISUALIZ',
        'MOBILITY / TELACC / TELCASE',
        'MOBILITY / TELACC / TELCHARGE',
        'MOBILITY / TELACC / TELOTHER',
        'MOBILITY / TELACC / TELSCRPRO',
        'MOBILITY / TELIP / TELIPACC',
        'COMPUTERS / COMPORT / PORTBATT',
        'COMPUTERS / COMPORT / PORTCASES',
        'COMPUTERS / COMPORT / PORTCHARG',
        'COMPUTERS / COMPORT / PORTSECUR',
        'COMPUTERS / COMDESK / ACCDESK',
        'COMPUTERS / POSSYSTEM / POSTERM',
        'COMPUTERS / COMHAND / TABAPPCES',
        'COMPUTERS / COMHAND / PADSACC',
        'COMPUTERS / COMHAND / TABSCRPRO',
        'COMPUTERS / COMHAND / TABSECACC',
        'COMPUTERS / COMHAND / TABHOLDER',
        'COMPUTERS / COMHAND / TABCHARGE',
        'HOUSEHOLD / HHLED / LEDLIGHT',
        'PERIPHER / PERREMME / REMOVCART',
        'PERIPHER / PERMONIT / MONACC',
        'PERIPHER / OTHPERIFS / OTHERACC',
        'PERIPHER / PERBARCO / BARCOREAD',
        'PERIPHER / KEYBMICE / POINTDEV',
        'PERIPHER / PERSTOR / STORACC',
        'PERIPHER / PERSTOR / DISKDRIVE',
        'PRINTING / PRIPARMAI / PRINACC',
        'PRINTING / PRISFD / FAX',
        'NETWORKNG / NWCABLES / NWCABADA',
        'NETWORKNG / NWCABLES / NWCABPAT',
        'NETWORKNG / NWWIRELSS / WLANADAPT',
        'NETWORKNG / NWLAN / NIC',
        'NETWORKNG / NWLAN / SWITCHMOD',
        'SOFTWARE / SOFTDESK / SWDESKTOP',
        'SOFTWARE / SOFTDESIG / SWDESIGN',
        'AVIMAGE / AVILFD / LFDDISP',

        'PERIPHER / PERUPS / UPS',
        'COMPUTERS / COMHAND / TABCASES',
        'PERIPHER / PERUPS / UPSSURPRO',
        'MOBILITY / TELIP / TELIPPHO',
        'PRINTING / PRISFD / PRINBC',
        'HOUSEHOLD / HHLED / LIGHTLAMP',
        'PERIPHER / PERUPS / UPSMONSW',
        'MOBILITY / TELACC / TELDOCKS',
        'AVIMAGE / AVIBD / HOMETHEA',
        'AVIMAGE / AVIDICAVI / IPCAMACC',
        'AVIMAGE / AVPROF / AVPROAMP',
        'AVIMAGE / AVPROF / AVPROMIC',

        'NETWORKNG / NWLAN / ROUTERACC',
        'NETWORKNG / NWLAN / SWITCHACC',
        'AVIMAGE / AVIACC / POWERDEV'
    ];

    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'techdata'
            ],
            [
                'name'          => 'Techdata',
                'type_import'   => 'file',
                'ws'            => 'SupplierImportFtpWS'
            ]
        );

        self::$formats['products']['filename'] .= date('Ymd').'.zip';
        self::$formats['products']['unzipped_filename'] .= date('Ymd').'.txt';

        self::$formats['offers']['filename'] .= date('Ymd').'.zip';
        self::$formats['offers']['unzipped_filename'] .= date('Ymd').'.txt';
        self::$formats['prices']['filename'] .= date('Ymd').'.zip';
        self::$formats['prices']['unzipped_filename'] .= date('Ymd').'.txt';

        self::$formats['categories']['filename'] .= date('Ymd').'.zip';
        self::$formats['categories']['unzipped_filename'] .= date('Ymd').'.txt';
    }



    static function getBrand(array $row)
    {
        try {
            $brand_name = $row['F'];
            if (!$brand_name || $brand_name == '') return null;

            return ucwords(strtolower($brand_name));

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
                $unzipped = $zip->extractTo(storage_path('app/supplier/techdata/'));
                $zip->close();
            }
            $inputFileName = storage_path('app/supplier/techdata/').self::$formats['products']['unzipped_filename'];
            $file_rows = FacadesMpeImport::getFileRows($inputFileName, 0);
            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows);

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

                // 1rst column -> header
                if ($row['A'] == 'matnr') continue;
                // Stock
                $stock = intval($row['L']);     // OLD STOCK, LAST DAY ???
                if ($stock < 5) continue;

                // Brand
                if (!$brand_name = self::getBrand($row)) continue;
                $brand = Brand::firstOrCreate([
                    'name'  => $brand_name,
                ],[]);

                $name = FacadesMpe::getString($row['B']);
                if (!isset($name) || $name == '') continue;

                $supplierCategoryId = $row['O'] ? $row['O'] : '';
                $supplierCategoryId .= $row['Q'] ? ' / '.$row['Q'] : '';
                $supplierCategoryId .= $row['S'] ? ' / '.$row['S'] : '';
                if ($supplierCategoryId == '' || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;

                $supplierCategoryName = $row['P'] ? $row['P'] : ($row['O'] ? $row['O'] : '');
                $supplierCategoryName .= $row['R'] ? ' / '.$row['R'] : ($row['Q'] ? ' / '.$row['Q'] : '');
                $supplierCategoryName .= $row['T'] ? ' / '.$row['T'] : ($row['S'] ? ' / '.$row['S'] : '');
                $supplier_category = SupplierCategory::updateOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => ($supplierCategoryName != '') ? $supplierCategoryName : $supplierCategoryId,
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = false;

                $supplierSku = (string)$row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan(trim($row['D']), trim($row['M']));

                $longdesc = FacadesMpe::getText($row['C']);
                $weight = floatval($row['N']);

                if ($product = $supplier->products()->firstWhere('supplierSku', $supplierSku)) {

                    $product->brand_id = $brand->id;
                    $product->supplier_category_id = $supplier_category->id;
                    if ($supplier_category->category_id) $product->category_id = $supplier_category->category_id;
                    $product->pn = $pn;
                    $product->ean = $ean;
                    $product->longdesc = $longdesc;
                    $product->weight = $weight;
                    $product->save();

                    $imported[] = $product->id;
                    $imported_count++;

                    if ($product->name == '' && isset($name) && $name != '') {
                        $product->name = $name;
                        $product->save();
                    }
                }
                else {
                    $product = $supplier->updateOrCreateProduct($pn, $ean, null, null, null, $supplierSku,
                        $brand->id, $supplier_category->id, $supplier_category->category_id ?? null, 1, 1,
                        $name, $longdesc, 0, 21, $stock, $weight,
                        null, null, null, null, null, null, null, null, null, null, null, null, null, null);

                    $imported_count++;
                }
            }

            unset($productsCollect);


            // IMPORT IMAGES
            $ftp_disk = self::FTP_DISK;
            $ftp_filename = self::$formats['images']['filename'];
            $header_rows = self::$formats['images']['header_rows'];

            $zipped = self::$formats['images']['zipped'] ?? false;
            $directory = $supplier->storage_dir.'images/';
            $filename = self::$formats['images']['unzipped_filename'] ?? date('Y-m-d_H'). '_images.csv';

            $res_images = null;
            if (Storage::disk($ftp_disk)->exists($ftp_filename)) {
                $file_rows = FacadesMpeImport::getRowsFtp($ftp_disk, $ftp_filename, $header_rows, $directory, $filename, $zipped);
                if (is_array($file_rows) && $imagesCollect = collect($file_rows)) {
                    unset($file_rows);
                    $res_images = self::images($supplier, $imagesCollect);
                }
                else $product->getMPEProductImages();
            }
            else
                $res_images = 'Storage Not exists '.$ftp_disk.' '.$ftp_filename;

            $msg = 'Importados '.$imported_count. ' productos.';
            if (count($unmapped_categories)) $msg .= ' Categorias sin mapear: '.json_encode($unmapped_categories);
            if ($res_images) $msg .= ' Imagenes: '.json_encode($res_images);

            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function offers(Supplier $supplier, Collection $productsCollect)
    {
        try {
            $mem = [];
            $mem[] = ['1' => [memory_get_usage(true), memory_get_peak_usage(true)]];
            //$res_prices = self::prices($supplier, $productsCollect);
            //$res_stocks = self::stocks($supplier, $productsCollect);

            // FIRST IMPORT PRICES
            $res_prices = self::prices($supplier, $productsCollect);
            unset($productsCollect);
            $mem[] = ['2' => [memory_get_usage(true), memory_get_peak_usage(true)]];

            // THEN IMPORT STOCK
            $res_stocks = null;
            $ftp_disk = self::FTP_DISK;
            $ftp_filename = self::$formats['stocks']['filename'];
            $header_rows = self::$formats['stocks']['header_rows'];

            $zipped = self::$formats['stocks']['zipped'] ?? false;
            $directory = $supplier->storage_dir.'stocks/';
            $filename = self::$formats['stocks']['unzipped_filename'] ?? date('Y-m-d_H'). '_stocks.csv';
            $mem[] = ['3' => [memory_get_usage(true), memory_get_peak_usage(true)]];

            $file_rows = FacadesMpeImport::getRowsFtp($ftp_disk, $ftp_filename, $header_rows, $directory, $filename, $zipped);
            $mem[] = ['4' => [memory_get_usage(true), memory_get_peak_usage(true)]];
            if (is_array($file_rows)) {
                if ($stocksCollect = collect($file_rows)) {
                    unset($file_rows);
                    $res_stocks = self::stocks($supplier, $stocksCollect);
                }

            }
            $mem[] = ['5' => [memory_get_usage(true), memory_get_peak_usage(true)]];

            return [$res_prices, $res_stocks, $mem];

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importPrices(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];

            $zip = new ZipArchive;
            if ($zip->open($uploaded_file) === TRUE) {
                $unzipped = $zip->extractTo(storage_path('app/supplier/techdata/'));
                $zip->close();
            }
            $inputFileName = storage_path('app/supplier/techdata/').self::$formats['prices']['unzipped_filename'];
            //$file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::$formats['products']['header_rows']);

            $file_rows = FacadesMpeImport::getFileRows($inputFileName, 0);

            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows); //->keyBy((string)$this->supplier->supplierSku_field);
            //$productsCollect = $this->supplier->filterProducts($productsCollect);

            return self::prices($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function prices(Supplier $supplier, Collection $productsCollect)
    {
       try {
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['prices']['columns'])
                return 'No tiene '.self::$formats['prices']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $imported_count = 0;
            $imported = [];

            foreach($productsCollect as $row) {

                // 1rst column -> header
                if ($row['A'] == 'Matnr') continue;

                $cost = FacadesMpe::roundFloatEsToEn($row['D']);
                $supplierSku = (string)$row['A'];
                $currency = $row['G'];  // EUR
                $canon = FacadesMpe::roundFloatEsToEn($row['E']);

                if ($product = $supplier->products()->where('supplierSku', $supplierSku)->first()) {

                    $product->cost = $cost;
                    $product->save();

                    if ($canon > 0 && isset($product->category_id))
                        $product->category->firstOrCreateCanon($canon, 'es');


                    $imported[] = $product->id;
                    $imported_count++;
                }
            }

            if ($imported_count > 0)
                    $supplier->products()->whereNotIn('products.id', $imported)->update(['stock' => 0]);

            $msg = 'Importados '.$imported_count. ' productos.';

            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importStocks(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];

            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::$formats['images']['header_rows']);
            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows);

            return self::stocks($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function stocks(Supplier $supplier, Collection $productsCollect)
    {
       try {
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['stocks']['columns'])
                return 'No tiene '.self::$formats['stocks']['columns']. ' columnas. Tiene '.count($productsCollect->first());

                $imported_count = 0;
                $imported = [];
                foreach($productsCollect as $row) {

                    $stock = intval($row['D']);
                    //if ($stock < 5) continue;
                    $supplierSku = $row['A'];
                    //$pn = FacadesMpe::getPn(trim($row['B']));

                    if ($product = $supplier->products()->firstWhere('supplierSku', $supplierSku)) {

                        $product->stock = $stock;
                        $product->status_id = 1;
                        $product->save();

                        $imported[] = $product->id;
                        $imported_count++;
                    }
                }

                if ($imported_count > 0)
                    $supplier->products()->whereNotIn('products.id', $imported)->update(['stock' => 0]);

                $msg = 'Importados stocks '.$imported_count. ' productos.';

                return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importImages(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::$formats['images']['header_rows']);
            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows);

            return self::images($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function images(Supplier $supplier, Collection $productsCollect)
    {
       try {
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['images']['columns'])
                return 'No tiene '.self::$formats['products']['images']. ' columnas. Tiene '.count($productsCollect->first());

            Storage::append('supplier/techdata/images.json', $productsCollect->toJson());

            $imported_count = 0;
            foreach ($productsCollect as $row) {
                $supplierSku = (string)$row['A'];
                $image_url = $row['C'];
                if ($supplierSku && $supplierSku != '')
                    if ($product = $supplier->products()->where('supplierSku', $supplierSku)->first())
                        if ($product->cost >= 30 && $product->stock >= 5 && !$product->images->count())
                            if ($image_url && $image_url != '') {
                                $product->updateOrCreateExternalImage($image_url);
                                $imported_count++;
                            }
                            else
                                $product->getMPEProductImages();
            }

            $msg = 'Importados '.$imported_count. ' imagenes.';

            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importCategories(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];

            $zip = new ZipArchive;
            if ($zip->open($uploaded_file) === TRUE) {
                $unzipped = $zip->extractTo(storage_path('app/supplier/techdata/'));
                $zip->close();
            }
            $inputFileName = storage_path('app/supplier/techdata/').self::$formats['categories']['unzipped_filename'];
            $file_rows = FacadesMpeImport::getFileRows($inputFileName, 0);
            if (!is_array($file_rows)) return $file_rows;
            $productsCollect = collect($file_rows);

            return self::categories($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, null);
        }
    }


    static function categories(Supplier $supplier, Collection $productsCollect)
    {
       try {
            if (!isset($productsCollect) || !$productsCollect->count())
                return 'No hay filas para importar.';
            if (count($productsCollect->first()) != self::$formats['categories']['columns'])
                return 'No tiene '.self::$formats['products']['categories']. ' columnas. Tiene '.count($productsCollect->first());

            $unmapped_categories = [];
            $imported_count = 0;
            foreach($productsCollect as $row) {

                // 1rst column -> header
                if ($row['A'] == 'matnr') continue;

                $supplierCategoryId = $row['O'] ? $row['O'] : '';
                $supplierCategoryId .= $row['Q'] ? ' / '.$row['Q'] : '';
                $supplierCategoryId .= $row['S'] ? ' / '.$row['S'] : '';
                if ($supplierCategoryId == '' || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;

                $supplierCategoryName = $row['P'] ? $row['P'] : ($row['O'] ? $row['O'] : '');
                $supplierCategoryName .= $row['R'] ? ' / '.$row['R'] : ($row['Q'] ? ' / '.$row['Q'] : '');
                $supplierCategoryName .= $row['T'] ? ' / '.$row['T'] : ($row['S'] ? ' / '.$row['S'] : '');
                $supplier_category = SupplierCategory::updateOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => ($supplierCategoryName != '') ? $supplierCategoryName : $supplierCategoryId,
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = '';

                $imported_count++;
            }

            $msg = 'Importados '.$imported_count. ' categorias.';
            if (count($unmapped_categories)) $msg .= ' Categorias sin mapear: '.json_encode($unmapped_categories);
            return $msg;

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }

}
