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
 * @property int $id_stock_available
 * @property int $id_product
 * @property int $id_product_attribute
 * @property int $id_shop
 * @property int $id_shop_group
 * @property int $quantity
 * @property int $physical_quantity
 * @property int $reserved_quantity
 * @property int $depends_on_stock
 * @property int $out_of_stock
 * @method static Builder|PrestashopStockAvailable whereDependsOnStock($value)
 * @method static Builder|PrestashopStockAvailable whereIdProduct($value)
 * @method static Builder|PrestashopStockAvailable whereIdProductAttribute($value)
 * @method static Builder|PrestashopStockAvailable whereIdShop($value)
 * @method static Builder|PrestashopStockAvailable whereIdShopGroup($value)
 * @method static Builder|PrestashopStockAvailable whereIdStockAvailable($value)
 * @method static Builder|PrestashopStockAvailable whereOutOfStock($value)
 * @method static Builder|PrestashopStockAvailable wherePhysicalQuantity($value)
 * @method static Builder|PrestashopStockAvailable whereQuantity($value)
 * @method static Builder|PrestashopStockAvailable whereReservedQuantity($value)
 */
class PrestashopStockAvailable extends Model
{
    /* const CREATED_AT = 'date_add';
    const UPDATED_AT = 'date_upd'; */

    protected $table = 'ps_stock_available';
    protected $primaryKey = 'id_stock_available';
    public $timestamps = false;

    //protected $connection = 'prestashop';
    protected $connection = 'prestashop_thehpshop';

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
