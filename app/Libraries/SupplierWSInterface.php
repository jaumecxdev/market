<?php

namespace App\Libraries;

use App\Product;

interface SupplierWSInterface
{
    /************** PUBLIC FUNCTIONS ***************/


    public function getProduct(Product $product);

    public function getProducts();

    public function getPricesStocks();

}
