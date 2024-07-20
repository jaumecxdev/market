<?php

namespace App\Libraries;

use App\Product;
use App\Supplier;

class SmartWS extends SupplierGoogleWS implements SupplierWSInterface
{
    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
        /* $this->storage_dir .= $supplier->code.'/';
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir); */

        // https://docs.google.com/spreadsheets/d/1pA_qf9FoHkP9jPbJBYXdAvN4_-Ee3Or-7K1MAS4sZDw/edit#gid=0
        // mpespecialist@gmail.com
        $this->ranges = 'A:L';
        $this->ranges_update = 'A:E';
        $this->spreadsheetId = '1pA_qf9FoHkP9jPbJBYXdAvN4_-Ee3Or-7K1MAS4sZDw';
        $this->parses = [
            'header'   => true,
            /*'fields'    => [
                [
                    'name'      => 'status',
                    'function'  => 'fixed',
                    'param'     => 'Nuevo',
                ],
                [
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
                ],
            ],*/
        ];
    }



    /************** PUBLIC FUNCTIONS ***************/


    public function getProduct(Product $product)
    {
        return parent::getProduct($product);
    }


    public function getProducts()
    {
        return parent::getProducts();
    }


    public function getPricesStocks()
    {
        return parent::getPricesStocks();
    }

}
