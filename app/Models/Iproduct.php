<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\Iproduct
 *
 * @property int $id_product_supplier
 * @property int $id_importador
 * @property int|null $id_manufacturer
 * @property int|null $id_category_default
 * @property int $id_tax
 * @property int $on_sale
 * @property float $ecotax
 * @property string|null $ean13
 * @property int $quantity
 * @property float $wholesale_price
 * @property float $rapell_price
 * @property float $rapell_price_noshipping
 * @property float $minimun_price
 * @property float $price
 * @property float|null $reduction_price
 * @property string $reduction_from
 * @property string $reduction_to
 * @property string $supplier_reference
 * @property string $location
 * @property float $weight
 * @property string $image
 * @property string|null $reference
 * @property string|null $name
 * @property string|null $description
 * @property string $description_short
 * @property int $home_thehpshop
 * @property int $winner
 * @property string|null $upd_supplier
 * @property string $date_add
 * @property string $date_upd
 * @property-read mixed $category_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct filter($params = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereDateAdd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereDateUpd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereDescriptionShort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereEan13($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereEcotax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereHomeThehpshop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereIdCategoryDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereIdImportador($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereIdManufacturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereIdProductSupplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereIdTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereMinimunPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereOnSale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereRapellPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereRapellPriceNoshipping($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereReductionFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereReductionPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereReductionTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereSupplierReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereUpdSupplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereWholesalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Iproduct whereWinner($value)
 * @mixin \Eloquent
 */
class Iproduct extends Model
{
    const CREATED_AT = 'date_add';
    const UPDATED_AT = 'date_upd';

    protected $table = 'imp_product_supplier';
    protected $primaryKey = 'id_product_supplier';
    public $timestamps = false;

    protected $connection = 'mysql_idiomund_importadores';

    protected $fillable = [
        'id_product_supplier', 'id_importador', 'id_manufacturer', 'id_category_default', 'id_tax',
        'on_sale', 'ean13', 'quantity', 'wholesale_price', 'rapell_price', 'rapell_price_noshipping', 'minimun_price',
        'price', 'reduction_price', 'reduction_from', 'reduction_to', 'supplier_reference', 'weight', 'image',
        'reference', 'name', 'description', 'description_short', 'upd_supplier', 'date_add', 'date_upd'
    ];


    protected $icategories = [
        3 => "Foto / Video",
        4 => "Imagen / Sonido",
        5 => "Telefonía / GPS",
        6 => "Oficina / TPV",
        7 => "Impresión / Escáneres",
        8 => "Consumibles",
        9 => "Portátiles",
        10 => "Ordenadores de Sobremesa",
        11 => "Monitores",
        12 => "Netbooks",
        13 => "Softwares",
        14 => "Consolas DJ",
        15 => "Periféricos",
        16 => "Proyectores",
        17 => "GPS",
        18 => "Componentes",
        19 => "Almacenamiento",
        20 => "Televisores NO SELECT",
        21 => "MP3",
        22 => "Redes y Comunicaciones",
        23 => "Cámaras Digitales",
        24 => "Videocámaras",
        25 => "Marcos Digitales",
        26 => "Accesorios para Cámaras",
        27 => "Home Cinema",
        28 => "Videojuegos",
        29 => "Reproductores MP3",
        30 => "Sintonizadores de TV / Audio",
        31 => "Proyectores",
        32 => "Pantallas de Proyección",
        33 => "Accesorios para Proyectores",
        34 => "Garantía para Proyectores",
        35 => "Ordenadores",
        36 => "Servidores",
        37 => "Tablets",
        38 => "Accesorios para Servidores",
        39 => "Accesorios para Ordenadores",
        40 => "Accesorios para Sobremesas",
        41 => "Accesorios para Tablets",
        42 => "Barebones",
        43 => "Fuentes de Alimentación",
        44 => "Carcasas",
        45 => "Monitores",
        46 => "Garantía para Monitores",
        47 => "Accesorios para Monitores",
        48 => "Ratones",
        49 => "Teclados",
        50 => "Tabletas Gráficas",
        51 => "Alfombrillas de Ratón",
        52 => "Accesorios para Periféricos",
        53 => "Refrigeración",
        54 => "Procesadores",
        55 => "Placas Base",
        56 => "Reproductores y Grabadores",
        57 => "Reproductores DVD",
        58 => "Reproductores Blu-Ray",
        59 => "Reproductores Video",
        60 => "Reproductores Portátiles",
        61 => "Televisores",
        62 => "Accesorios para Televisores",
        63 => "Soportes para Televisores",
        64 => "Impresoras Multifuncionales",
        65 => "Impresoras Láser",
        66 => "Impresoras Inyección de Tinta",
        67 => "Impresoras Matriciales",
        68 => "Plotters",
        69 => "Impresoras",
        70 => "Faxes",
        71 => "Fotocopiadoras",
        72 => "Garantías para Impresoras",
        73 => "Impresoras Fotográficas",
        74 => "Otras Impresoras",
        75 => "Accesorios para Impresoras",
        76 => "Impresoras de Tinta Sólida",
        77 => "Altavoces",
        78 => "Auriculares",
        79 => "Micrófonos",
        80 => "Webcams",
        81 => "Accesorios para Consolas",
        82 => "Consolas",
        83 => "MANDOS GÉNERICOS",
        84 => "Películas",
        85 => "Tarjetas PCI Express",
        86 => "Cajas Set-top",
        87 => "Teléfonos Fijos",
        88 => "Móviles / Smartphones",
        89 => "Accesorios para Móviles",
        90 => "Hubs",
        91 => "Accesorios para Teléfonos",
        92 => "Escáneres",
        93 => "Escáneres",
        94 => "Accesorios para Escáneres",
        95 => "Escáneres Código de Barras",
        96 => "Garantías para Cámaras",
        97 => "SAI",
        98 => "SAI",
        99 => "Accesorios para SAI",
        100 => "Garantía para SAI",
        101 => "Workstation",
        102 => "Accesorios para Workstation",
        103 => "Módems",
        104 => "Switches",
        105 => "Routers",
        106 => "Firewalls",
        107 => "KVM",
        108 => "Grabadores DVD",
        109 => "Grabadores Blu-Ray",
        110 => "Grabadores CD",
        111 => "Reproductores CD",
        112 => "HiFi",
        113 => "Car Audio",
        114 => "MP3 / MP4",
        115 => "Acondicionadores de Línea",
        116 => "Regletas",
        117 => "Lectores / Grabadores",
        118 => "Lectores / Grabadores Blu-Ray",
        119 => "Lectores / Grabadores DVD",
        120 => "Lectores / Grabadores CD",
        121 => "Lectores de Tarjetas",
        122 => "Impresoras de Etiquetas y Tickets",
        123 => "Lectores de Código de Barras",
        124 => "Calculadoras",
        125 => "Cajas Registradoras",
        126 => "Carpetas / Archivadores",
        127 => "TPV",
        128 => "RFID",
        129 => "Radios",
        130 => "Sistemas Operativos",
        131 => "Softwares para Servidores",
        132 => "Antivirus",
        133 => "Creatividad y Diseño",
        134 => "Contabilidad y Gestión",
        135 => "Juegos",
        136 => "Softwares de Seguridad",
        137 => "Telecomunicaciones",
        138 => "Ofimática / Utilities",
        139 => "Cultura y Educación",
        140 => "Programación",
        141 => "Ocio y Vida Práctica",
        142 => "Auriculares / Micrófonos",
        143 => "Altavoces",
        144 => "Papeles / Transparencias",
        145 => "Papel Fotográfico",
        146 => "Papel para Plotter",
        147 => "Etiquetas",
        148 => "Transparencias",
        149 => "Papel para Impresoras",
        150 => "Cartuchos / Tóners",
        151 => "Cartuchos",
        152 => "Tóners",
        153 => "Cintas",
        154 => "Discos / Backup",
        155 => "DVD",
        156 => "CD",
        157 => "Cintas de Datos",
        158 => "Blu-Ray",
        159 => "Kits Limpieza",
        160 => "Tambores / Fusores",
        161 => "Disquetes",
        162 => "Baterías / Pilas",
        163 => "Memorias",
        164 => "Memorias para Ordenadores",
        165 => "Tarjetas de Memoria",
        166 => "Memorias para Impresoras",
        167 => "Memorias para Servidores",
        168 => "Archivadores Multimedia",
        169 => "Tarjetas Gráficas",
        170 => "Tarjetas de Sonido",
        171 => "Memoria Flash y Discos duros",
        172 => "Discos Duros Internos",
        173 => "Discos Duros Externos",
        174 => "Accesorios para Discos Duros",
        175 => "Tarjetas Capturadoras de Vídeo",
        176 => "Cables",
        177 => "Cables de Almacenamiento",
        178 => "Herramientas",
        179 => "Cables Telefónicos",
        180 => "Cables de Audio / Video",
        181 => "Cables para Teclados / Ratones",
        182 => "Pequeño Electrodoméstico",
        183 => "Cables SCSI",
        184 => "Cables de Serie",
        185 => "Cables de Red",
        186 => "Cables USB / FireWire",
        187 => "Cables Paralelos",
        188 => "Cables de Antenas",
        189 => "Cables de Alimentación",
        190 => "Cables de Par Trenzado",
        191 => "Unidades de Cintas",
        192 => "Accesorios para Almacenamiento",
        193 => "Discos Ópticos",
        194 => "Tarjetas de Red",
        195 => "SSD",
        196 => "Controladoras de Discos",
        197 => "Memorias USB",
        198 => "E-book",
        199 => "Reproductores Multimedia",
        200 => "Bluetooth",
        201 => "Gateways",
        202 => "Powerline",
        203 => "Tarjetas de TV",
        204 => "Conmutadores",
        205 => "Accesorios para Redes",
        206 => "RAID",
        207 => "Soportes Ergonómicos",
        208 => "Accesorios para Oficina",
        209 => "Mobiliario",
        210 => "Dictáfonos",
        211 => "Teléfonos IP",
        212 => "Terminales",
        213 => "Servidores VoIP",
        214 => "Videoconferencia",
        215 => "Videovigilancia",
        216 => "Domótica",
        217 => "Detectores",
        218 => "Dispositivos de Seguridad",
        219 => "Adaptadores",
        220 => "Bridges",
        221 => "Carcasas para Discos Duros",
        222 => "Accesorios para Domótica",
        223 => "Garantías para Ordenadores",
        224 => "Servicios y Garantías",
        225 => "Garantías para Escáneres",
        226 => "Torres Duplicadoras de CD/DVD",
        227 => "Consumibles para Fax",
        228 => "Consumibles para Copiadoras",
        229 => "Barras de Tinta Sólida",
        230 => "Kits de Montaje",
        231 => "Accesorios para GPS",
        232 => "Consumibles para Audio / Video",
        233 => "Garantías para Softwares",
        234 => "Accesorios para TPV",
        235 => "Garantías para Almacenamiento",
        236 => "Garantías para Redes",
        237 => "Transceptores / Convertidores",
        238 => "Discos Duros Portátiles",
        239 => "Unidades de Disquetes",
        240 => "Librerías / Autocargadores",
        241 => "TPV NO SELECT",
        242 => "Material de Oficina",
        243 => "Repuestos",
        244 => "Memorias ROMS / PROMS / EPROMS",
        245 => "Puntos de Acceso Wireless",
        246 => "Digital Signage",
        247 => "Equipos de Grabación",
        248 => "Otros Cables",
        249 => "Cables para Monitores",
        250 => "Placas de Expansión de Memoria",
        251 => "Softwares Varios",
        252 => "Electrodomésticos",
        253 => "Cafeteras",
        254 => "Sandwhicheras",
        255 => "Aspiradoras",
        256 => "Licuadoras",
        257 => "Planchas de Vapor",
        258 => "Secadores",
        259 => "Afeitadoras",
        260 => "Trituradoras de Papel",
        261 => "Sobres",
        262 => "Laminadoras",
        263 => "Cuidado Personal",
        264 => "Aparatos de Cocina",
        265 => "Balanzas de Cocina",
        266 => "Walkie-talkies",
        267 => "Garantías para GPS",
        268 => "Garantías para Móviles / Smartphones",
        269 => "Terminales",
        270 => "Garantías para Terminales",
        271 => "Almacenamiento en Red",
        272 => "Grabadores Digitales",
        273 => "Outlet",
        274 => "Luminarias",
        276 => "Accesorios",
        279 => "Baterías",
        280 => "Adaptadores",
        281 => "Maletines / Bolsas / Fundas",
        282 => "Docking Station / Puerto Replicador",
        287 => "Detectores de Radar",
        291 => "Lavadoras",
        292 => "Frigoríficos",
        293 => "Ventiladores",
        294 => "Aire Acondicionados",
        295 => "Calefacción",
        296 => "Secadoras",
        297 => "Encimeras",
        298 => "Hornos",
        299 => "Microondas",
        300 => "Top 10",
        301 => "Vuelta al cole",
        303 => "LINEAL ACC. MIRO",
        304 => "Redes",
        305 => "Torres de sonido1",
        306 => "Audio / Sonido",
        307 => "Auriculares",
        308 => "Music BoX",
        309 => "Smartwatch",
        310 => "MP3 / MP4",
        311 => "Lineal Accesorios Electro",
        312 => "Torres de sonido",
        313 => "Patinetes",
        314 => "Bluetooth",
        315 => "Ratones",
        316 => "Altavoces",
        317 => "Audio / Sonido",
        318 => "Fundas y Maletines",
        319 => "Gaming",
        320 => "Teclados y Combos",
        321 => "Auriculares",
        322 => "Webcam NO SELECT",
        323 => "Cables y cargadores",
        324 => "Altavoces",
        325 => "Audio portátil",
        326 => "redes",
        327 => "Acc. telefonia",
        328 => "Telefonia",
        329 => "ENERGIA",
        335 => "Portatiles Gaming",
        336 => "Portatiles Freedos",
        410 => "All in One",
        430 => "Cargadores",
        431 => "Cables",
        432 => "Cables",
        433 => "Gaming",
    ];

    protected $icategories_rejected = [
        34,     // "Garantía para Proyectores",
        46,     // "Garantía para Monitores",
        72,     // "Garantías para Impresoras",
        96,     // "Garantías para Cámaras",
        100,    // "Garantía para SAI",
        223,    // "Garantías para Ordenadores",
        224,    // "Servicios y Garantías",
        225,    // "Garantías para Escáneres",
        233,    // "Garantías para Softwares",
        235,    // "Garantías para Almacenamiento",
        236,    // "Garantías para Redes",
        267,    // "Garantías para GPS",
        268,    // "Garantías para Móviles / Smartphones",
        270,    // "Garantías para Terminales",
    ];


    protected $icategories_group = [
        31      => 16,      // proyectores
        45      => 11,      // monitores
        61      => 20,      // televisores
        93      => 92,      // escaneres
        98      => 97,      // sai
        143     => 77,      // altavoces
        241     => 127,     // tpv
        269     => 212,     // terminales
        280     => 219,     // adaptadores
        307     => 78,      // auriculares
        310     => 114,     // mp3 / mp4
        314     => 200,     // bluetooth
        315     => 48,      // ratones
        316     => 77,      // altavoces
        317     => 306,     // audio / sonido
        321     => 78,      // auriculares
        322     => 80,      // webcams
        324     => 77,      // altavoces
        326     => 304,     // redes
        431     => 176,     // cables
        432     => 176,     // cables
        433     => 319,     // gaming
    ];


    /* protected $icanons = [
        197 => 0.24,    // Memoria USB
        88 => 1.1,      // Telefonia
        9 => 5.45,      // Portátiles
        10 => 5.45,     // Sobremesa
        172 => 5.45,    // Disco duro interno
        173 => 6.45,    // Disco duro
        37 => 3.15,     // Tablets
        64 => 5.25,     // Impresoras Multifuncionales
        195 => 5.45,    // SSD
        165 => 0.24,    // Tarjetas de memoria
    ]; */



    // ACCESSORS


    public function getIdProductSupplierAttribute($value)
    {
        return strval($value);
    }


    /* public function getRapellPriceAttribute($value)
    {
        return isset($icanons[$this->id_category_default]) ? $value + $icanons[$this->id_category_default] : $value;
    } */


    /* public function getReferenceAttribute($value)
    {
        // hp, hpe, hpc, hpm
        if ( in_array($this->id_manufacturer, [3, 3299, 3349, 11692]) &&
            ($this->id_category_default == 9 || $this->id_category_default == 10) )
                    return $value. '#ABE';

        return $value;
    } */


    public function getIdCategoryDefaultAttribute($value)
    {
        return $icategories_group[$value] ?? $value;
    }


    public function getCategoryNameAttribute()
    {
        return $icategories[$this->id_category_default] ?? $this->id_category_default;
    }


    // SCOPES


    public function scopeFilter(Builder $query, $params = null)
    {
        // Reject Guarantee & Services categories
        $query->whereNotIn('id_category_default', $this->icategories_rejected);

        /* if (isset($params['id_importador']) && $params['id_importador'] != null) {
            $query->where('id_importador', $params['id_importador']);
        }

        if (isset($params['quantity']) && $params['quantity'] != null) {
            $query->where('quantity', '>=', $params['quantity']);
        }
 */
        // ORDER BY
        /* if (isset($params['order_by'])) {
            $query->orderBy($params['order_by'], $params['order']);
        } */

        return $query;
    }

}
