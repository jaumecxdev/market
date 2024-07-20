<?php

namespace App\Libraries;

use App\Product;
use App\Supplier;


class CustomWS extends SupplierWS implements SupplierWSInterface
{
    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
    }


    /************** PUBLIC FUNCTIONS ***************/


    public function getProduct(Product $product)
    {
        return 'No Code';
    }


    public function getProducts()
    {
        return 'No Code';
    }


    public function getPricesStocks()
    {
        return 'No Code';
    }

}
