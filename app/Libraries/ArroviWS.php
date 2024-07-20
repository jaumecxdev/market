<?php

namespace App\Libraries;

use App\Supplier;

class ArroviWS extends SupplierFileWS implements SupplierWSInterface
{
    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);

        $this->currency_code = 'EUR';
        $this->file_type = 'xlsx';
        $this->file_name = 'products.xlsx';
        $this->header_rows = 1;
        $this->status_id = 1;   // Nuevo

        $this->parses = [
            'fields' => [
                [
                    'name'      => 'G',             // color
                    'function'  => 'explode',
                    'param'     => ','
                ],
                [
                    'name'      => 'XX',             // brand
                    'function'  => 'fixed',
                    'param'     => 'Arrovi'
                ]
            ],
            'variants' => [
                'color'
            ]

            /*'header'   => true,
            'fields'    => [
                [
                    'name'      => 'status',
                    'function'  => 'fixed',
                    'param'     => 'Nuevo',
                ],
                [
                    'name'      => 'pn',
                    'function'  => 'substr',
                    'param'     => '_',
                ],
                [
                    'name'      => 'category',
                    'function'  => 'change',
                    'value'     => 'Tablet',
                    'param'     => 'Tablets'
                ],
                 [
                    'name'      => 'images',
                    'function'  => 'change',
                    'param'     => [Image::getNoImageFullUrl('logo_locura.png')],
                    'value'     => null
                ],
            ],*/
        ];
    }

}
