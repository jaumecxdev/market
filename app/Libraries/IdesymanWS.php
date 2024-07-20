<?php

namespace App\Libraries;


use App\Supplier;

class IdesymanWS extends IdiomundWS implements SupplierWSInterface
{

    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
        $this->category_supplier_id = 1;        // Get categories From Idiomund Blanes
        $this->brand_supplier_id = 1;           // Get Brands names From Idiomund Blanes
        $this->filter_supplier_id = 1;          // Get Filters From Idiomund Blanes
    }

}
