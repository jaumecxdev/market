<?php

namespace App\Imports;

use App\Brand;
use App\Category;
use App\Product;
use App\Supplier;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Facades\App\Facades\Mpe as FacadesMpe;
use Throwable;



class ImportTone
{
    const FUNCTIONS = ['importProducts', 'importDescriptions', 'importImages'];

    const FORMATS = [
        'products'    => [
            'columns'       => 5,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'descriptions'    => [
            'columns'       => 2,
            'header_rows'   => 6,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']
        ],
    ];

    const IMPORT_TEXT = "<b>Importación de productos:</b> Los nombres de los ficheros Excel deben coincidir con la Marca.<br>
    <b>Importación de Imágenes:</b> Los nombres de los ficheros de imágenes deben empezar con el CÓDIGO del producto.";


    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'tonewatch'
            ],
            [
                'name'          => 'Tone Watch',
                'type_import'   => 'file'
            ]
        );
    }


    public function importProducts(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            //$file_rows = $this->getFileRowsExcel($uploaded_file, self::FORMATS['products']['header_rows']);
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['products']['header_rows']);

            // test array
            if (!count($file_rows)) return 'No hay filas para importar.';
            if (count($file_rows[0]) != self::FORMATS['products']['columns']) return 'No tiene '.self::FORMATS['products']['columns']. ' columnas.';

            $category = Category::firstWhere('name', 'Relojes de pulsera y de bolsillo');
            $brand_name = FacadesMpe::getFilenameWithoutExt($uploaded_file->getClientOriginalName());
            $brand = Brand::firstWhere('name', $brand_name);
            if (!isset($category) || !isset($brand)) return 'No category Or Brand: Relojes de pulsera y de bolsillo OR '.$brand_name;

            foreach($file_rows as $row) {

                $cost = $row['E'] / 1.21;   // $row E includes IVA & ports
                $pn = str_replace(['+', '.'], '-', $row['A']);
                if ($brand_name == 'Smarty')
                    $name = ucwords( strtolower( FacadesMpe::getString($row['B']) ) ). ' ' .$row['A'];
                else
                    $name = FacadesMpe::getString($brand_name. ' ' .FacadesMpe::ucwords_dot('-', FacadesMpe::strtolower_utf8( $row['B'])));

                Product::updateOrCreate(
                    [
                        'supplier_id'   => $this->supplier->id,
                        'pn'            => $pn,
                        'ean'           => $row['C'],
                    ],
                    [
                        'supplierSku'   => $row['A'],
                        'brand_id'      => $brand->id,
                        'supplier_category_id'   => null,
                        'category_id'   => $category->id,       // 4783, Relojes de pulsera y de bolsillo
                        'status_id'     => 1,                   // Nuevo
                        'currency_id'   => 1,                   // EUR - Euro
                        'name'          => $name,
                        //'ready'         => 1,
                        'cost'          => $cost,
                        'tax'           => 21,
                        'stock'         => $row['D'],
                    ]
                );
            }

            return 'Importados '.count($file_rows). ' productos.';

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importDescriptions(array $uploaded_files)
    {
        try {
            $uploaded_file = $uploaded_files[0];
            //$file_rows = $this->getFileRowsExcel($uploaded_file, self::FORMATS['descriptions']['header_rows']);
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['descriptions']['header_rows']);

            // test array
            if (!count($file_rows)) return 'No hay filas para importar.';
            if (count($file_rows[0]) != self::FORMATS['descriptions']['columns']) return 'No tiene '.self::FORMATS['descriptions']['columns']. ' columnas.';

            foreach($file_rows as $row) {
                $longdesc = FacadesMpe::ucwords_dot('-', FacadesMpe::strtolower_utf8( FacadesMpe::getText($row['B'])));
                Product::updateOrCreate(
                    [
                        'supplier_id'   => $this->supplier->id,
                        'pn'            => $row['A'],
                        //'ean'           => $row['C'],
                    ],
                    [
                        'supplierSku'   => $row['A'],
                        'longdesc'      => $longdesc,
                    ]
                );
            }

            return 'Importadas '.count($file_rows). ' descripciones.';

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    private function removeLastChars($string, $chars_count)
    {
        return substr($string, 0, strlen($string)-$chars_count);
    }


    public function importImages(array $uploaded_files)
    {
        // Imáge names
        // FILA: 10 digits                  2n - 3d - 3d            38-023-003.jpg -> PN: FILA38-023-003 (Only 1 image x PN)
        // MUNICH: 9 digits + type          MU - 3d - 2d            MU-101-2A-LAT.jpg
        // PITLANE: 9 digits + type         PL - 4n - 1n            PL-1003-2-TRA.jpg
        // SMARTY: 6 || 7 digits + type     SW + 2||3d + 2d         SW008A-TRAS.jpg
        $not_found = [];
        $count = 0;
        try {
            foreach ($uploaded_files as $uploaded_file) {

                if ($pn = FacadesMpe::getFilenameWithoutExt($uploaded_file->getClientOriginalName())) {
                    if (strlen($pn) > 9) {

                        if (is_numeric(substr($pn, 0, 1))) $pn = 'FILA'.$pn;                // Fila
                        elseif (strtoupper(substr($pn, 0, 2)) == 'SW') {
                            if (strlen($pn) > 7) $pn = substr($pn, 0, strpos($pn, '-'));    // Smarty
                        }
                        else $pn = substr($pn, 0, 9);                                       // Munich || Pitlane
                    }

                    if ($product = Product::whereSupplierId($this->supplier->id)->firstWhere('pn', $pn)) {
                        $count++;
                        if ($product->images()->count() < 4)
                            $product->updateOrStoreImage($uploaded_file);
                    }
                    else
                        $not_found[] = $uploaded_file->getClientOriginalName();
                }
            }

            return 'Importadas '.$count. ' imágenes. No encontradas: '.json_encode($not_found);

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


}
