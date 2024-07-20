<?php

namespace App\Libraries;

use App\Supplier;


class OlahomWS extends SupplierPrestaWS implements SupplierWSInterface
{
    function __construct(Supplier $supplier)
    {
        $this->currency_code = 'EUR';

        $this->debug = false;
        $this->apiUrl = 'https://www.olahom.com';      //'http://127.0.0.1:8080/thehpshop';
        $this->apiKey = 'EIGCBVDVR2UPXEHTW2TVCMUTPI43T5GM';

        $this->fixed_brand = 'Sukima Decor';
        $this->category_ids_excluded = [2];

        $this->languages = [1, 2, 3];       // es, ca, gl   only active id = 1
        $this->language = 1;

        parent::__construct($supplier);


    }

}
