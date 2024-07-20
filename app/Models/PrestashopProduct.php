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
 * @property int|null $id_supplier
 * @property int|null $id_manufacturer
 * @property int|null $id_category_default
 * @property int $id_shop_default
 * @property int $id_tax_rules_group
 * @property int $on_sale
 * @property int $online_only
 * @property string|null $ean13
 * @property string|null $isbn
 * @property string|null $upc
 * @property string $ecotax
 * @property int $quantity
 * @property int $minimal_quantity
 * @property int|null $low_stock_threshold
 * @property int $low_stock_alert
 * @property string $price
 * @property string $wholesale_price
 * @property string|null $unity
 * @property string $unit_price_ratio
 * @property string $additional_shipping_cost
 * @property string|null $reference
 * @property string|null $supplier_reference
 * @property string|null $location
 * @property string $width
 * @property string $height
 * @property string $depth
 * @property string $weight
 * @property int $out_of_stock
 * @property int $additional_delivery_times
 * @property int|null $quantity_discount
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
 * @property int $cache_is_pack
 * @property int $cache_has_attachments
 * @property int $is_virtual
 * @property int|null $cache_default_attribute
 * @property string $date_add
 * @property string $date_upd
 * @property int $advanced_stock_management
 * @property int $pack_stock_type
 * @property int $state
 * @method static Builder|PrestashopProduct whereActive($value)
 * @method static Builder|PrestashopProduct whereAdditionalDeliveryTimes($value)
 * @method static Builder|PrestashopProduct whereAdditionalShippingCost($value)
 * @method static Builder|PrestashopProduct whereAdvancedStockManagement($value)
 * @method static Builder|PrestashopProduct whereAvailableDate($value)
 * @method static Builder|PrestashopProduct whereAvailableForOrder($value)
 * @method static Builder|PrestashopProduct whereCacheDefaultAttribute($value)
 * @method static Builder|PrestashopProduct whereCacheHasAttachments($value)
 * @method static Builder|PrestashopProduct whereCacheIsPack($value)
 * @method static Builder|PrestashopProduct whereCondition($value)
 * @method static Builder|PrestashopProduct whereCustomizable($value)
 * @method static Builder|PrestashopProduct whereDateAdd($value)
 * @method static Builder|PrestashopProduct whereDateUpd($value)
 * @method static Builder|PrestashopProduct whereDepth($value)
 * @method static Builder|PrestashopProduct whereEan13($value)
 * @method static Builder|PrestashopProduct whereEcotax($value)
 * @method static Builder|PrestashopProduct whereHeight($value)
 * @method static Builder|PrestashopProduct whereIdCategoryDefault($value)
 * @method static Builder|PrestashopProduct whereIdManufacturer($value)
 * @method static Builder|PrestashopProduct whereIdProduct($value)
 * @method static Builder|PrestashopProduct whereIdShopDefault($value)
 * @method static Builder|PrestashopProduct whereIdSupplier($value)
 * @method static Builder|PrestashopProduct whereIdTaxRulesGroup($value)
 * @method static Builder|PrestashopProduct whereIdTypeRedirected($value)
 * @method static Builder|PrestashopProduct whereIndexed($value)
 * @method static Builder|PrestashopProduct whereIsVirtual($value)
 * @method static Builder|PrestashopProduct whereIsbn($value)
 * @method static Builder|PrestashopProduct whereLocation($value)
 * @method static Builder|PrestashopProduct whereLowStockAlert($value)
 * @method static Builder|PrestashopProduct whereLowStockThreshold($value)
 * @method static Builder|PrestashopProduct whereMinimalQuantity($value)
 * @method static Builder|PrestashopProduct whereOnSale($value)
 * @method static Builder|PrestashopProduct whereOnlineOnly($value)
 * @method static Builder|PrestashopProduct whereOutOfStock($value)
 * @method static Builder|PrestashopProduct wherePackStockType($value)
 * @method static Builder|PrestashopProduct wherePrice($value)
 * @method static Builder|PrestashopProduct whereQuantity($value)
 * @method static Builder|PrestashopProduct whereQuantityDiscount($value)
 * @method static Builder|PrestashopProduct whereRedirectType($value)
 * @method static Builder|PrestashopProduct whereReference($value)
 * @method static Builder|PrestashopProduct whereShowCondition($value)
 * @method static Builder|PrestashopProduct whereShowPrice($value)
 * @method static Builder|PrestashopProduct whereState($value)
 * @method static Builder|PrestashopProduct whereSupplierReference($value)
 * @method static Builder|PrestashopProduct whereTextFields($value)
 * @method static Builder|PrestashopProduct whereUnitPriceRatio($value)
 * @method static Builder|PrestashopProduct whereUnity($value)
 * @method static Builder|PrestashopProduct whereUpc($value)
 * @method static Builder|PrestashopProduct whereUploadableFiles($value)
 * @method static Builder|PrestashopProduct whereVisibility($value)
 * @method static Builder|PrestashopProduct whereWeight($value)
 * @method static Builder|PrestashopProduct whereWholesalePrice($value)
 * @method static Builder|PrestashopProduct whereWidth($value)
 */
class PrestashopProduct extends Model
{
    /* const CREATED_AT = 'date_add';
    const UPDATED_AT = 'date_upd'; */

    protected $table = 'ps_product';
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
