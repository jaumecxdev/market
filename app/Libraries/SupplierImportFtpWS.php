<?php

namespace App\Libraries;

use App\Product;
use App\Supplier;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Throwable;


class SupplierImportFtpWS extends SupplierWS
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
            $importSupplier = new $importSupplierClass();

            $ftp_disk = $importSupplier::FTP_DISK;
            $ftp_filename = $importSupplier::$formats['products']['filename'];

            $header_rows = $importSupplier::$formats['products']['header_rows'];
            $zipped = $importSupplier::$formats['products']['zipped'] ?? false;
            $directory = $this->supplier->storage_dir.'products/';
            $filename = $importSupplier::$formats['products']['unzipped_filename'] ?? date('Y-m-d_H'). '_products.csv';

            $file_rows = FacadesMpeImport::getRowsFtp($ftp_disk, $ftp_filename, $header_rows, $directory, $filename, $zipped);

            //Storage::append($this->supplier->storage_dir.'import/'.date('Y-m-d_H-i').'_products.json', json_encode($file_rows));

            if (!is_array($file_rows))
                return $this->nullAndStorage(__METHOD__, [$this->supplier->code, $file_rows]);

            $productsCollect = collect($file_rows);//->keyBy((string)$this->supplier->supplierSku_field);
            unset($file_rows);

            if ($filterProductsCollect = $this->supplier->filterProducts($productsCollect))
                return $importSupplierClass::products($this->supplier, $filterProductsCollect);

            return 'Error obteniendo los productos.';

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $this->supplier);
        }
    }


    public function getPricesStocks()
    {
        try {
            //$m = memory_get_usage();
            $importSupplierClass = 'App\\Imports\\Import'.ucwords($this->supplier->code);
            $importSupplier = new $importSupplierClass();

            $ftp_disk = $importSupplier::FTP_DISK;
            $ftp_filename = $importSupplier::$formats['offers']['filename'];

            $header_rows = $importSupplier::$formats['offers']['header_rows'];
            $zipped = $importSupplier::$formats['offers']['zipped'] ?? false;
            $directory = $this->supplier->storage_dir.'offers/';
            $filename = $importSupplier::$formats['offers']['unzipped_filename'] ?? date('Y-m-d_H'). '_offers.csv';

            $file_rows = FacadesMpeImport::getRowsFtp($ftp_disk, $ftp_filename, $header_rows, $directory, $filename, $zipped);
            if (!is_array($file_rows))
                return $this->nullAndStorage(__METHOD__, $this->supplier);

            $productsCollect = collect($file_rows);     //->keyBy((string)$this->supplier->supplierSku_field);
            unset($file_rows);

            //$mm = memory_get_usage();

            return $importSupplier::offers($this->supplier, $productsCollect);

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $this->supplier);
        }
    }


}
