<?php

namespace App\Libraries;


use App\Shop;


class PrestaudgWS extends PrestaWS implements MarketWSInterface
{
    function __construct(Shop $shop)
    {
        parent::__construct($shop);

        $this->languages = [1,5];
    }

}
