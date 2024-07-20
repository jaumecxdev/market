<?php

namespace App\Libraries;


use App\Shop;

class PrestathehpshopWS extends PrestaWS implements MarketWSInterface
{
    function __construct(Shop $shop)
    {
        parent::__construct($shop);
        $this->rate_standard_id = 7;        // id_tax_rules_group
    }

}
