<?php

namespace App\Imports;

use App\Brand;
use App\Image;
use App\Product;
use App\Supplier;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Facades\App\Facades\Mpe as FacadesMpe;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;


class ImportDisok
{
    use HelperTrait;

    const FUNCTIONS = ['importProducts', 'importOffers'];

    const FORMATS = [
        'products'    => [
            'columns'       => 11,
            'header_rows'   => 7,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
        'offers'    => [
            'columns'       => 11,
            'header_rows'   => 7,
            //'map'           => ['supplierSku', 'name', 'ean', 'stock', 'cost']

        ],
    ];

    const URI_PRODUCTS = '';
    const URI_OFFERS = '';

    const IMPORT_TEXT = "<b>Importación de productos</b> Fichero XLS manual.";

    const REJECTED_CATEGORIES = [
    ];

    private $supplier;


    public function __construct()
    {
        // IVA: 10%
        $this->supplier = Supplier::firstOrCreate(
            [
                'code'          => 'disok'
            ],
            [
                'name'          => 'Disok',
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

            return self::products($this->supplier, collect($file_rows));

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
                return 'No tiene '.self::FORMATS['products']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $unmapped_categories = [];
            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                if (!isset($row['A'])) continue;

                $cost = FacadesMpe::roundFloatEsToEn(str_replace('€', '', $row['F']));
                if ($cost < 28) continue;

                // Brand
                $brand = Brand::firstOrCreate([
                    'name'  => 'Desconocido',
                ],[]);

                $stock = 10;
                $tax = 10;

                $name = FacadesMpe::getString($row['C']);
                if (!isset($name) || $name == '') continue;
                $name = $name.' '.$row['E'];

                $status_id = 1;

                $supplierCategoryId = self::getCategoryId($row);
                if (!$supplierCategoryId || in_array($supplierCategoryId, self::REJECTED_CATEGORIES)) continue;
                $supplier_category = SupplierCategory::firstOrCreate([
                    'supplier_id'           => $supplier->id,
                    'supplierCategoryId'    => $supplierCategoryId,
                ],[
                    'name'                  => self::getCategoryName($row),
                ]);
                if (!isset($supplier_category->category_id))
                    $unmapped_categories[$supplier_category->supplierCategoryId] = $supplier_category->name;

                $supplierSku = $row['A'];
                [$pn, $ean] = FacadesMpe::getPnEan($row['A'], null);
                $longdesc = $name;

                $weight = 0;
                $row['E'] = strtoupper($row['E']);
                if (mb_strpos($row['E'], '-')) $weight = mb_substr($row['E'], 0, mb_strpos($row['E'], '-'));
                if (mb_strpos($row['E'], 'A')) $weight = mb_substr($row['E'], 0, mb_strpos($row['E'], 'A'));
                $weight = $row['E'];
                $weight = str_replace([' ', 'KG', 'GR'], '', $weight);
                $weight = FacadesMpe::roundFloatEsToEn($weight);
                if (mb_strpos($row['E'], 'KG')) $weight *= 1000;

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

                    /* if ($product->name == '' && isset($name) && $name != '') {
                        $product->name = $name;
                        $product->save();
                    } */

                    if (!$product->images->count())
                        self::updateOrCreateExternalImage($product, $row['K']);

                }
                // Create new product
                else {

                    $product = $supplier->updateOrCreateProduct($pn, $ean, null, null, null, $supplierSku,
                        $brand->id, $supplier_category->id, $supplier_category->category_id ?? null, $status_id, 1,
                        $name, $longdesc, $cost, $tax, $stock, $weight,
                        null, null, null, null, null, null, null, null, null, null, null, null, null, null);

                    $product->name = $product->name.' '.$row['E'];
                    $product->save();

                    self::updateOrCreateExternalImage($product, $row['K']);
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
                return 'No tiene '.self::FORMATS['offers']['columns']. ' columnas. Tiene '.count($productsCollect->first());

            $no_stock = $no_cost = 0;
            $imported_count = 0;
            $imported = [];
            foreach($productsCollect as $row) {

                if (!isset($row['A'])) continue;

                $cost = FacadesMpe::roundFloatEsToEn(str_replace('€', '', $row['F']));
                if ($cost < 28) continue;

                // Brand
                $brand = Brand::firstOrCreate([
                    'name'  => 'Desconocido',
                ],[]);

                $stock = 10;
                $tax = 10;
                [$pn, $ean] = FacadesMpe::getPnEan($row['A'], null);

                $product = $supplier->getSimilarProduct(1, $brand->id, $pn, $ean);

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
            return $row['B'];

        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }


    static function getCategoryName(array $row)
    {
        try {
            return $row['B'];

        } catch (Throwable $th) {
            return null;
        }
    }


    static function updateOrCreateExternalImage(Product $product, $image_url, $type = null)
    {
        try {
            if (!isset($type)) $type = $product->getNextImageType();

            // SOURCE: https://drive.google.com/file/d/1OOa57NXZP_yjbbqv2CbN6AuHGnxWySd4/view?usp=sharing
            // TARGET: https://drive.google.com/u/0/uc?id=1OOa57NXZP_yjbbqv2CbN6AuHGnxWySd4
            if (mb_strpos($image_url, 'drive.google.com')) {
                $start = mb_strpos($image_url, 'file')+7;
                $end = mb_strpos($image_url, 'view')+1;
                $img_id = mb_substr($image_url, $start, strlen($image_url)-$start-$end);

                $client = new Client(); //['base_uri' => 'https://www.google.com/']);
                $response = $client->get('https://drive.google.com/u/0/uc?id='.$img_id, []);
                if ($response->getStatusCode() == '200' && $contents = $response->getBody()->getContents()) {
                    $directory = ('public/img/' . $product->id . '/');         // upload path
                    $ext = 'png';
                    $imageName = strval($type). '.' .$ext;

                    // Create directory, If necessary && Remove & update image file
                    Storage::makeDirectory($directory);
                    if (Storage::exists($directory . $imageName)) {
                        Storage::delete($directory . $imageName);
                    }

                    Storage::put($directory.$imageName, $contents);

                    $image = Image::updateOrCreate(
                        [
                            'product_id'    => $product->id,
                            'src'           => $imageName,
                            'type'          => $type,
                        ]
                    );

                    return true;
                }
            }

            return false;
        }
        catch (Throwable $th) {
            Storage::append('errors/' .date('Y-m-d_H').'_cjamonera_updateOrCreateExternalImage.json', json_encode([$product->id, $image_url, $type]));
            return null;
        }
    }



}
