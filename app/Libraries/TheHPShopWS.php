<?php

namespace App\Libraries;

use App\Supplier;


class TheHPShopWS extends SupplierPrestaWS implements SupplierWSInterface
{
    function __construct(Supplier $supplier)
    {
        $this->currency_code = 'EUR';

        $this->debug = true;
        $this->apiUrl = 'http://thehpshop.test:8080';      //https://www.thehpshop.com, http://thehpshop.test:8080, http://127.0.0.1:8080/thehpshop';
        $this->apiKey = 'YBPH28BXIJ63KV6PLWAJF1YD87CJRQ63';

        parent::__construct($supplier);
    }

}
