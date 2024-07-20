<?php

namespace App\Libraries;

use App\Supplier;


class McrWS extends SupplierFileWS implements SupplierWSInterface
{
    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
        /* $this->storage_dir .= $supplier->code.'/';
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir); */

        $this->currency_code = 'EUR';

        $this->file_type = 'xml';
        //$this->file_url = 'supplier/mcr/tarifas.xml';
        $this->file_url = 'https://www.openmcr.com.es/ver-tarifa/it/xml/stock/amNsYXJhQG1wZXNwZWNpYWxpc3QuY29tJiYkMnkkMTAkTFcwY2ZUL2t3dmlsVGtKeXpoa3V3ZUlMMExpblVEUFRJRTBGTVZwcXhpUUMyZTFUMS52TW0mJjIwMjAtMTEtMDQgMTI6NTk6MzA=';
        $this->file_child = 'elemento';

        $this->images_type = 'array';       // array | string
        $this->images_child = 'imagen';
        $this->subcategory = 'categoria2';
        $this->longdesc_type = 'html';
        $this->longdesc_extra = 'especificaciones';
        $this->status_id = 1;   // Nuevo

        $this->rejected_categories = [
            'Accesorios servidores / Frontales',
            'Accesorios servidores / Rack',
            'Adaptadores de Rack',
            'Armario Mural',
            'Armario Mural Doble Cuerpo',
            'Armario Rack Pie',
            'AV Pro / Multimedia Streaming Broadcast',
            'Bandejas',
            'Baterías',
            'Chasis / Pedestal M',
            'Controladoras RAID',
            'Controladoras RAID / Bater\u00edas',
            'Controladoras RAID / RPFK',
            'Controladoras RAID / SAS2 PCIe',
            'Controladoras RAID / SAS3 Expander',
            'Controladoras RAID / SAS3 SIO',
            'E5-1600V3',
            'Familia S2600',
            'Imagen y Sonido / Gestion de Se\u00f1al',
            'Management',
            'Licencias/Software',
            'Pedestal S',
            'Placas Base / Familia S2600',
            'Procesadores Servidor / E5-1600V3',
            'Rack',
            'Rack 2U',
            'RPFK',
            'Refrigeración armarios servidores',
            'SAS2 PCIe',
            'SAS3 Expander',
            'SAS3 PCIe',
            'SAS3 SIO',
            'Servidores / Controladoras RAID',
            'Servidores / Licencias/Software',
            'Servidores / Placas Base',
            'Sistemas',
            'Socket-FCLGA14',
            'Socket-LGA1151'
        ];


        $this->parses = [
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
                    'param'     => 'Tablets',
                    'value'     => 'Tablet'
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
