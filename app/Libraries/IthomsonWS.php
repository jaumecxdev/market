<?php

namespace App\Libraries;

use App\Supplier;


class IthomsonWS extends SupplierCsvWS implements SupplierWSInterface
{

    function __construct(Supplier $supplier)        //, $canon = 0, $rappel = 0, $ports = 4/(1.21))
    {
        parent::__construct($supplier);     //, $canon, $rappel, $ports);
        /* $this->storage_dir .= $supplier->code.'/';
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir); */

        $this->url_csv = 'http://www.tiendasil.com/';
        $this->filename = 'listadostockzstockdepositothomson.txt';
        $this->delimiter = "\t";     // '\t';
        $this->conditions = [
            'headers'   => false,
            'fields'    => [
               /* [
                    'name'      => 'pn',
                    'function'  => 'substr',
                    'param'     => '_',
                ],*/
                /*[
                    'name'      => 'status',
                    'function'  => 'fixed',
                    'param'     => 'Remanufacturado',
                ],*/
               /* [
                    'name'      => 'category',
                    'function'  => 'change',
                    'param'     => 'Tablets',
                    'value'     => 'Tablet'
                ],*/
                /*[
                    'name'      => 'images',
                    'function'  => 'change',
                    'param'     => [Image::getNoImageFullUrl('logo_locura.png')],
                    'value'     => null
                ],*/
            ],
        ];
        //$this->helper = new IhelperWS();
    }

}
