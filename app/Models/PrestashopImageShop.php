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
 * @property int|null $id_category_default
 * @property int $id_tax_rules_group
 * @property int $on_sale
 * @property int $online_only
 * @property string $ecotax
 * @property int $minimal_quantity
 * @property int|null $low_stock_threshold
 * @property int $low_stock_alert
 * @property string $price
 * @property string $wholesale_price
 * @property string|null $unity
 * @property string $unit_price_ratio
 * @property string $additional_shipping_cost
 * @property int $customizable
 * @property int $uploadable_files
 * @property int $text_fields
 * @property int $active
 * @property string $redirect_type
 * @property int $id_type_redirected
 * @property int $available_for_order
 * @property string|null $available_date
 * @property int $show_condition
 * @property string $condition
 * @property int $show_price
 * @property int $indexed
 * @property string $visibility
 * @property int|null $cache_default_attribute
 * @property int $advanced_stock_management
 * @property string $date_add
 * @property string $date_upd
 * @property int $pack_stock_type
 * @method static Builder|PrestashopProductShop whereActive($value)
 * @method static Builder|PrestashopProductShop whereAdditionalShippingCost($value)
 * @method static Builder|PrestashopProductShop whereAdvancedStockManagement($value)
 * @method static Builder|PrestashopProductShop whereAvailableDate($value)
 * @method static Builder|PrestashopProductShop whereAvailableForOrder($value)
 * @method static Builder|PrestashopProductShop whereCacheDefaultAttribute($value)
 * @method static Builder|PrestashopProductShop whereCondition($value)
 * @method static Builder|PrestashopProductShop whereCustomizable($value)
 * @method static Builder|PrestashopProductShop whereDateAdd($value)
 * @method static Builder|PrestashopProductShop whereDateUpd($value)
 * @method static Builder|PrestashopProductShop whereEcotax($value)
 * @method static Builder|PrestashopProductShop whereIdCategoryDefault($value)
 * @method static Builder|PrestashopProductShop whereIdProduct($value)
 * @method static Builder|PrestashopProductShop whereIdShop($value)
 * @method static Builder|PrestashopProductShop whereIdTaxRulesGroup($value)
 * @method static Builder|PrestashopProductShop whereIdTypeRedirected($value)
 * @method static Builder|PrestashopProductShop whereIndexed($value)
 * @method static Builder|PrestashopProductShop whereLowStockAlert($value)
 * @method static Builder|PrestashopProductShop whereLowStockThreshold($value)
 * @method static Builder|PrestashopProductShop whereMinimalQuantity($value)
 * @method static Builder|PrestashopProductShop whereOnSale($value)
 * @method static Builder|PrestashopProductShop whereOnlineOnly($value)
 * @method static Builder|PrestashopProductShop wherePackStockType($value)
 * @method static Builder|PrestashopProductShop wherePrice($value)
 * @method static Builder|PrestashopProductShop whereRedirectType($value)
 * @method static Builder|PrestashopProductShop whereShowCondition($value)
 * @method static Builder|PrestashopProductShop whereShowPrice($value)
 * @method static Builder|PrestashopProductShop whereTextFields($value)
 * @method static Builder|PrestashopProductShop whereUnitPriceRatio($value)
 * @method static Builder|PrestashopProductShop whereUnity($value)
 * @method static Builder|PrestashopProductShop whereUploadableFiles($value)
 * @method static Builder|PrestashopProductShop whereVisibility($value)
 * @method static Builder|PrestashopProductShop whereWholesalePrice($value)
 * @property int $id_image
 * @property int|null $cover
 * @method static Builder|PrestashopImageShop whereCover($value)
 * @method static Builder|PrestashopImageShop whereIdImage($value)
 */
class PrestashopImageShop extends Model
{
    protected $table = 'ps_image_shop';
    protected $primaryKey = null;
    public $incrementing = false;

    public $timestamps = false;

    //protected $connection = 'prestashop';
    protected $connection = 'prestashop_thehpshop';

    protected $fillable = [
        'id_product', 'id_image', 'id_shop', 'cover'
    ];

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
