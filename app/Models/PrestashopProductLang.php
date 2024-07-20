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
 * @property int $id_product
 * @property int $id_shop
 * @property int $id_lang
 * @property string|null $description
 * @property string|null $description_short
 * @property string $link_rewrite
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $meta_title
 * @property string $name
 * @property string|null $available_now
 * @property string|null $available_later
 * @property string|null $delivery_in_stock
 * @property string|null $delivery_out_stock
 * @method static Builder|PrestashopProductLang whereAvailableLater($value)
 * @method static Builder|PrestashopProductLang whereAvailableNow($value)
 * @method static Builder|PrestashopProductLang whereDeliveryInStock($value)
 * @method static Builder|PrestashopProductLang whereDeliveryOutStock($value)
 * @method static Builder|PrestashopProductLang whereDescription($value)
 * @method static Builder|PrestashopProductLang whereDescriptionShort($value)
 * @method static Builder|PrestashopProductLang whereIdLang($value)
 * @method static Builder|PrestashopProductLang whereIdProduct($value)
 * @method static Builder|PrestashopProductLang whereIdShop($value)
 * @method static Builder|PrestashopProductLang whereLinkRewrite($value)
 * @method static Builder|PrestashopProductLang whereMetaDescription($value)
 * @method static Builder|PrestashopProductLang whereMetaKeywords($value)
 * @method static Builder|PrestashopProductLang whereMetaTitle($value)
 * @method static Builder|PrestashopProductLang whereName($value)
 */
class PrestashopProductLang extends Model
{
    /* const CREATED_AT = 'date_add';
    const UPDATED_AT = 'date_upd'; */

    protected $table = 'ps_product_lang';
    protected $primaryKey = 'id_product';
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
