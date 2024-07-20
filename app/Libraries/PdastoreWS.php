<?php

namespace App\Libraries;

use App\Supplier;


class PdastoreWS extends SupplierMPSShopWS implements SupplierWSInterface
{

    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
    }


}
