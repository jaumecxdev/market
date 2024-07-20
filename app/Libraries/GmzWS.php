<?php

namespace App\Libraries;

use App\Product;
use App\Supplier;


class GmzWS extends SupplierCsvWS implements SupplierWSInterface
{

    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
        /* $this->storage_dir .= $supplier->code.'/';
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir);
 */

        $this->disk = 'ftp_gmz';
        $this->filename = 'Precios/gmz.csv';
        $this->delimiter = ',';

        $this->images_import_type = 'ftp';
        $this->images_folder = 'Imágenes/';
        $this->images_folder_field = 'supplierSku_field';

        $this->conditions = [
            'headers'   => 'true',
            'fields'    => [
                [
                    'name'      => 'status',
                    'function'  => 'fixed',
                    'param'     => 'Nuevo',
                ],
               /* [
                    'name'      => 'pn',
                    'function'  => 'substr',
                    'param'     => '_',
                ],
                [
                    'name'      => 'category',
                    'function'  => 'change',
                    'param'     => 'Tablets',
                    'value'     => 'Tablet'
                ],
                 [
                    'name'      => 'images',
                    'function'  => 'change',
                    'param'     => [Image::getNoImageFullUrl('logo_locura.png')],
                    'value'     => null
                ], */
            ],
        ];
        //$this->helper = new IhelperWS();
    }


    /************** IMPLEMENTED FATHER FUNCTIONS ***************/


    /* public function getImageByEan($ean)
    {
        return $this->helper->getImageByEan($ean);
    }

    */


    /************** PUBLIC FUNCTIONS ***************/


    public function getProduct(Product $product)
    {
        $product = parent::getProduct($product);

        return $product;
    }


    public function getProducts()
    {
        $count = parent::getProducts();

        return $count;
    }


    public function getPricesStocks()
    {
        return parent::getPricesStocks();
    }

}
