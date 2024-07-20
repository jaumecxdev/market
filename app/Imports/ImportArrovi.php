<?php

namespace App\Imports;

use App\Brand;
use App\Product;
use App\Supplier;
use App\SupplierCategory;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Facades\App\Facades\Mpe as FacadesMpe;
use Throwable;



class ImportArrovi
{
    const FUNCTIONS = ['importProducts', 'importDescriptions', 'importImages'];

    const FORMATS = [
        'products'    => [
            'columns'       => 10,
            'header_rows'   => 2,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'descriptions'    => [
            'columns'       => 2,
            'header_rows'   => 1,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']
        ],
    ];

    const IMPORT_TEXT = "<b>Importación de productos:</b> <br>
    <b>Importación de Imágenes:</b> Los nombres de los ficheros de imágenes deben empezar con el CÓDIGO del producto.";


    private $supplier;


    public function __construct()
    {
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'arrovi'
            ],
            [
                'name'          => 'Arrovi Bodas',
                'type_import'   => 'file'
            ]
        );
    }


    public function importProducts(array $uploaded_files)
    {
        $not_imported_rows = [];
        $count = 0;
        try {
            $uploaded_file = $uploaded_files[0];
            //$file_rows = $this->getFileRowsExcel($uploaded_file, self::FORMATS['products']['header_rows']);
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['products']['header_rows']);

            // test array
            if (!count($file_rows)) return 'No hay filas para importar.';
            if (count($file_rows[0]) != self::FORMATS['products']['columns']) return 'No tiene '.self::FORMATS['products']['columns']. ' columnas.';
            $brand = Brand::firstOrCreate(['name' => 'Arrovi'], []);

            foreach($file_rows as $row_number => $row) {

                if (is_numeric($row['A']) && isset($row['B']) && isset($row['C']) && isset($row['D']) && isset($row['E'])) {

                    $count++;
                    $pn = $supplierSku = $row['A'];
                    $name = FacadesMpe::trim_ucfirst(FacadesMpe::strtolower_utf8( FacadesMpe::getText($row['B'])));
                    if (substr($name, strlen($name)-1, 1) == '.') $name = substr($name, 0, strlen($name)-1);
                    $name = str_replace('"', '', $name);
                    // Replace numbers comma to numbers dot
                    $name = preg_replace('/(\d),(\d)/', '$1.$2', $name);

                    $cost = $row['C'];
                    if ($row['D'] > 1) {
                        $cost *= $row['D'];
                        $name .= ' Pack '.$row['D'];
                    }
                    $stock = $row['F'];
                    if ($stock == '') $stock = 0;
                    elseif ($row['D'] > 1) $stock = intdiv($stock, $row['D']);
                    $supplier_category = SupplierCategory::firstOrCreate(
                        [
                            'supplier_id'           => $this->supplier->id,
                            'supplierCategoryId'    => $row['E']
                        ],
                        [
                            'name'                  => $row['E']
                        ]
                    );

                    $colors = explode(',', $row['G']);
                    if (count($colors) == 1)
                        $name .= ' '.$colors[0];

                    $product = Product::updateOrCreate(
                        [
                            'supplier_id'   => $this->supplier->id,
                            'pn'            => $pn,
                            'parent_id'     => null,
                            'color'         => null
                        ],
                        [
                            'supplierSku'   => $supplierSku,
                            'brand_id'      => $brand->id,
                            'supplier_category_id'   => $supplier_category->id,
                            'category_id'   => $supplier_category->category_id ?? null,
                            'name'          => $name,
                            'cost'          => $cost,
                            'tax'           => 21,
                            'stock'         => $stock,
                            'status_id'     => 1,                   // Nuevo
                            'currency_id'   => 1,                   // EUR - Euro
                            //'ready'         => 1,
                        ]
                    );

                    // Variants
                    if (count($colors) > 1) {
                        foreach($colors as $color) {

                            Product::updateOrCreate(
                                [
                                    'supplier_id'   => $this->supplier->id,
                                    'pn'            => $pn,
                                    'parent_id'     => $product->id,
                                    'color'         => $color
                                ],
                                [
                                    'supplierSku'   => $supplierSku,
                                    'brand_id'      => $brand->id,
                                    'category_id'   => $supplier_category->category_id ?? null,
                                    'name'          => null,
                                    'cost'          => $cost,
                                    'tax'           => 21,
                                    'stock'         => $stock,
                                    'status_id'     => 1,                   // Nuevo
                                    'currency_id'   => 1,                   // EUR - Euro
                                    //'ready'         => 1,
                                ]
                            );

                        }
                    }
                }
                else
                    $not_imported_rows[] = $row_number + self::FORMATS['products']['header_rows'] + 1;
            }

            return 'Importados '.$count. ' productos. Filas no importadas: '.json_encode($not_imported_rows);

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    public function importDescriptions(array $uploaded_files)
    {
        $not_imported_rows = [];
        $count = 0;
        try {
            $uploaded_file = $uploaded_files[0];
            $file_rows = FacadesMpeImport::getRowsUploaded($uploaded_file, self::FORMATS['descriptions']['header_rows']);
            //$file_rows = $this->getFileRowsExcel($uploaded_file, self::FORMATS['descriptions']['header_rows']);

            // test array
            if (!count($file_rows)) return 'No hay filas para importar.';
            if (count($file_rows[0]) != self::FORMATS['descriptions']['columns']) return 'No tiene '.self::FORMATS['descriptions']['columns']. ' columnas.';

            foreach($file_rows as $row_number => $row) {

                if (is_numeric($row['A']) && isset($row['B'])) {

                    $count++;
                    $pn = $supplierSku = $row['A'];
                    $longdesc = FacadesMpe::getText($row['B']);
                    $product = Product::updateOrCreate(
                        [
                            'supplier_id'   => $this->supplier->id,
                            'pn'            => $pn,
                            'parent_id'     => null,
                            'color'         => null
                        ],
                        [
                            //'supplierSku'   => $row['A'],
                            'longdesc'      => $longdesc,
                        ]
                    );
                }
                else
                    $not_imported_rows[] = $row_number + self::FORMATS['descriptions']['header_rows'] + 1;

            }

            return 'Importadas '.$count. ' descripciones. Filas no importadas: '.json_encode($not_imported_rows);

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
        // Imáge names: pn == SupplierSku
        // pn + 'jpg'
        $not_found = [];
        $count = 0;
        try {
            foreach ($uploaded_files as $uploaded_file) {

                if ($pn = FacadesMpe::getFilenameWithoutExt($uploaded_file->getClientOriginalName())) {
                    if (strpos($pn, '-')) $pn = substr($pn, 0, strpos($pn, '-'));
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
