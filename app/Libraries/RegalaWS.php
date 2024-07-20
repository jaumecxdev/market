<?php

namespace App\Libraries;

use App\Supplier;


class RegalaWS extends SupplierFileWS implements SupplierWSInterface
{

    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
        //$this->storage_dir .= 'thehpshop/';
        $this->currency_code = 'EUR';

        $this->file_type = 'xml';
        $this->file_cdata = true;
        //$this->file_name = 'flux-es.xml';     // TEST FILE
        $this->file_url = 'https://regalasexo.net/modules/qashops/export/prestashop/flux-es.xml';
        $this->file_child = 'product';

        $this->images_type = 'array';       // array | string
        $this->images_child = null;

        $this->subcategory = null;
        $this->longdesc_type = 'text';
        $this->longdesc_extra = null;
        $this->status_id = 1;   // Nuevo

        $this->parses = [];
    }



    /************** PUBLIC FUNCTIONS ***************/


    /* public function getProduct(Product $product)
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
        $count = parent::getPricesStocks();

        return $count;
    } */

}
