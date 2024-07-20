<?php

namespace App\Libraries;

use App\Product;
use App\Supplier;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Illuminate\Support\Facades\Storage;
use Throwable;


class SupplierImportWS extends SupplierWS
{
    use HelperTrait;


    public function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
    }




    /************** PUBLIC FUNCTIONS ***************/


    public function getProduct(Product $product)
    {
        return null;
        //return $this->getProducts();
    }


    public function getProducts()
    {
        try {
            $importSupplierClass = 'App\\Imports\\Import'.ucwords($this->supplier->code);
            $uri = $importSupplierClass::URI_PRODUCTS;
            $header_rows = $importSupplierClass::FORMATS['products']['header_rows'];
            $directory = $this->supplier->storage_dir.'products/';
            $filename = date('Y-m-d_H'). '_products.csv';
            $file_rows = FacadesMpeImport::getRowsUri($uri, $header_rows, $directory, $filename);
            Storage::append($this->supplier->storage_dir.'import/'.date('Y-m-d_H-i').'_products.json', json_encode($file_rows));

            if (!is_array($file_rows))
                return $this->nullAndStorage(__METHOD__, [$this->supplier->code, $file_rows]);

            $productsCollect = collect($file_rows)->keyBy((string)$this->supplier->supplierSku_field);
            unset($file_rows);

            if ($productsCollect = $this->supplier->filterProducts($productsCollect))
                return $importSupplierClass::products($this->supplier, $productsCollect);

            return 'Error obteniendo los productos.';

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this->supplier);
        }
    }


    public function getPricesStocks()
    {
        try {
            $importSupplierClass = 'App\\Imports\\Import'.ucwords($this->supplier->code);
            $uri = $importSupplierClass::URI_OFFERS;
            $header_rows = $importSupplierClass::FORMATS['offers']['header_rows'];
            $directory = $this->supplier->storage_dir.'offers/';
            $filename = date('Y-m-d_H'). '_offers.csv';
            $file_rows = FacadesMpeImport::getRowsUri($uri, $header_rows, $directory, $filename);
            Storage::append($this->supplier->storage_dir.'import/'.date('Y-m-d_H-i').'_offers.json', json_encode($file_rows));

            if (!is_array($file_rows))
                return $this->nullAndStorage(__METHOD__, $this->supplier);

            $productsCollect = collect($file_rows)->keyBy((string)$this->supplier->supplierSku_field);
            unset($file_rows);

            return $importSupplierClass::offers($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this->supplier);
        }
    }


}
