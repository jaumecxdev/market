<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\CategoryCanon
 *
 * @property int $id
 * @property int|null $category_id
 * @property string|null $locale
 * @property float $canon
 * @property-read \App\Category|null $category
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCanon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCanon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCanon query()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCanon whereCanon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCanon whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCanon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCanon whereLocale($value)
 * @mixin \Eloquent
 */
class CategoryCanon extends Model
{
    protected $table = 'category_canons';

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'locale', 'canon'
    ];

    const EXCLUDED_CATEGORIES = [
        3038,   // Auriculares
        3193,   // Enrutadores inalámbricos
        2847,   // Estaciones de conexión para portátiles, Replicadores de Puertos y Docking Stations
        3187,   // Adaptadores y tarjetas de red
        2815,   // Administración de dinero
        3225,   // Monitores de ordenador
        4272,   // Artículos de oficina
        2920,   // Tabletas gráficas
        3196,   // Puntos de acceso inalámbrico, Amplificadores y Repetidores de Red
        2914,   // Lectores de códigos de barras
        4333,   // Calculadoras
        2911,   // Conmutadores KVM
        3188,   // Concentradores y conmutadores Switches Hubs
        3208,   // Pantallas para proyección
        2962,   // Cargadores y adaptadores de alimentación
        2817,   // Cajas registradoras
        3419,   // Bicicletas
        5506,   // Impresoras Fotográficas
        5520,   // Digital Signage
        2881,   // Componentes para ordenadores
        4395,   // Accesorios para mobiliario de oficina
        5528,   // Televisores Hotel
        3135,   // Teléfonos inalámbricos
        2575,   // Cámaras de vídeo
        2113,   // Purificadores de aire
        3235,   // Televisores
        3057,   // Transmisores inalámbricos
        2927,   // Altavoces de repuesto para tablets
        3124,   // Accesorios de telefonía
    ];


    public function category()
    {
        return $this->belongsTo('App\Category');
    }

}
