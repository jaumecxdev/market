<?php

namespace App\Libraries;

use App\Supplier;


class DavedansWS extends SupplierPrestaWS implements SupplierWSInterface
{
    function __construct(Supplier $supplier)
    {
        $this->currency_code = 'EUR';

        $this->debug = false;
        $this->apiUrl = 'https://moove.davedans.com';
        $this->apiKey = 'K2SY83P78X73JVSRJB5LL9EB939XZ21P';

        $this->fixed_brand = 'moove by davedans';
        $this->category_names_excluded = ['Home', 'Inicio'];
        //$this->category_ids_excluded = [2];

        $this->languages = [1, 3, 4];       // es, en, ca       getPrestaLanguages()
        $this->language = 1;

        $this->has_product_options = 1;
        $this->product_options = [                  // getPrestaProductOptions()
            1   => 'size',      // Talla
            3   => 'color',     // Tejido/Color
        ];

        $this->stock_min = 100;

        parent::__construct($supplier);
    }


}
