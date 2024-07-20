<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\Prestashop
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prestashop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prestashop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prestashop query()
 * @mixin \Eloquent
 */
class PrestashopCategory extends Model
{
    /* const CREATED_AT = 'date_add';
    const UPDATED_AT = 'date_upd'; */

    protected $table = 'ps_category';
    protected $primaryKey = 'id_category';
    public $timestamps = false;

    //protected $connection = 'prestashop';


   /*  protected $fillable = [
        'id_product_supplier', 'id_importador', 'id_manufacturer', 'id_category_default', 'id_tax',
        'on_sale', 'ean13', 'quantity', 'wholesale_price', 'rapell_price', 'rapell_price_noshipping', 'minimun_price',
        'price', 'reduction_price', 'reduction_from', 'reduction_to', 'supplier_reference', 'weight', 'image',
        'reference', 'name', 'description', 'description_short', 'upd_supplier', 'date_add', 'date_upd'
    ]; */



    /* public function initialize($database){
        //$this->connection = 'prestashop_'.strtolower($connection);
        $this->setConnection('prestashop_'.strtolower($database));
    } */

}
