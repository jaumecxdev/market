<?php

namespace App\Libraries;


use App\Shop;


class PrestaaepWS extends PrestaWS implements MarketWSInterface
{
    function __construct(Shop $shop)
    {
        parent::__construct($shop);
        $this->apiUrl = $shop->endpoint;
        $this->apiKey = $shop->token;
    }

}
