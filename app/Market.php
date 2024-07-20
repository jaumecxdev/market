<?php

namespace App;

use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * App\Market
 *
 * @property int $id
 * @property string|null $code
 * @property string $name
 * @property string|null $product_url
 * @property string|null $order_url
 * @property string|null $ws
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\AttributeMarketAttribute[] $attribute_market_attributes
 * @property-read int|null $attribute_market_attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Group[] $groups
 * @property-read int|null $groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketAttribute[] $market_attributes
 * @property-read int|null $market_attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketCategory[] $market_categories
 * @property-read int|null $market_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketFee[] $market_fees
 * @property-read int|null $market_fees_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\RootCategory[] $root_categories
 * @property-read int|null $root_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopProduct[] $shop_products
 * @property-read int|null $shop_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Shop[] $shops
 * @property-read int|null $shops_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Status[] $statuses
 * @property-read int|null $statuses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Type[] $types
 * @property-read int|null $types_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market whereOrderUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market whereProductUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market whereWs($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketParam[] $market_params
 * @property-read int|null $market_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierParam[] $supplier_params
 * @property-read int|null $supplier_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\FeeParam[] $fee_params
 * @property-read int|null $fee_params_count
 * @property int $market_category_required
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Market whereMarketCategoryRequired($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketBrand[] $market_brands
 * @property-read int|null $market_brands_count
 * @property int $pn_required
 * @property int $ean_required
 * @property int $images_required
 * @property int $attributes_required
 * @method static \Illuminate\Database\Eloquent\Builder|Market whereAttributesRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Market whereEanRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Market whereImagesRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Market wherePnRequired($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketCarrier[] $market_carriers
 * @property-read int|null $market_carriers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderShipment[] $order_shipments
 * @property-read int|null $shipments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderComment[] $order_comments
 * @property-read int|null $order_comments_count
 * @property-read int|null $order_shipments_count
 * @property mixed|null $config
 * @method static \Illuminate\Database\Eloquent\Builder|Market whereConfig($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopMessage[] $shop_messages
 * @property-read int|null $shop_messages_count
 * @property int $name_required
 * @method static \Illuminate\Database\Eloquent\Builder|Market whereNameRequired($value)
 */
class Market extends Model
{
    use HelperTrait;

    protected $table = 'markets';

    public $timestamps = false;

    protected $fillable = [
        'code', 'name', 'product_url', 'order_url', 'ws',
        'pn_required', 'ean_required', 'market_category_required', 'images_required', 'attributes_required',
        'name_required',
        'config'
    ];

    // MANY

    public function attribute_market_attributes()
    {
        return $this->hasMany('App\AttributeMarketAttribute');
    }

    public function groups()
    {
        return $this->hasMany('App\Group');
    }

    public function market_attributes()
    {
        return $this->hasMany('App\MarketAttribute');
    }

    public function market_brands()
    {
        return $this->hasMany('App\MarketBrand');
    }

    public function market_categories()
    {
        return $this->hasMany('App\MarketCategory');
    }

    public function market_carriers()
    {
        return $this->hasMany('App\MarketCarrier');
    }

    public function market_params()
    {
        return $this->hasMany('App\MarketParam');
    }

    public function root_categories()
    {
        return $this->hasMany('App\RootCategory');
    }

    public function order_comments()
    {
        return $this->hasMany('App\OrderComment');
    }

    public function order_shipments()
    {
        return $this->hasMany('App\OrderShipment');
    }

    public function shops()
    {
        return $this->hasMany('App\Shop');
    }

    public function shop_messages()
    {
        return $this->hasMany('App\ShopMessage');
    }

    public function shop_products()
    {
        return $this->hasMany('App\ShopProduct');
    }

    public function statuses()
    {
        return $this->hasMany('App\Status');
    }

    public function types()
    {
        return $this->hasMany('App\Type');
    }



    public function syncParams()
    {
        try {
            $market_params = $this->market_params;
            foreach ($this->shop_products as $shop_product) {
                $shop_product->setMarketParams($market_params);
            }

            return 'Parámetros del Marketplace '.$this->name.' sincronizados con  '.$this->shop_products->count().' productos.';

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $this);
        }
    }


    // CUSTOM

    /* public function getShopProductMarketParams(ShopProduct $shop_product)
    {
        // 2.- brand_id && market_category_id
        // 3.- brand_id && root_category_id
        // 4.- brand_id
        // 5.- market_category_id
        // 6.- root_category_id
        // 7.- GENERAL market_id: ALL null

        $market_params = $this->market_params;

        // If NO Marketplace Params (as PCEducacion), all FEEs = 0
        if (!$market_params->count()) {
            return [
                'mp_fee'       => 0,
                'mp_fee_addon' => 0,
            ];
        };

        // BRAND_ID & MARKET_CATEGORY_ID
        $market_param = $market_params
            ->where('brand_id', $shop_product->product->brand_id)
            ->firstWhere('market_category_id', $shop_product->market_category_id);

        // BRAND_ID & ROOT_CATEGORY_ID
        if (!isset($market_param) && isset($shop_product->market_category_id))
            $market_param = $market_params
                ->where('brand_id', $shop_product->product->brand_id)
                ->firstWhere('root_category_id', $shop_product->market_category->root_category_id);

        // BRAND_ID
        if (!isset($market_param))
            $market_param = $market_params
                ->firstWhere('brand_id', $shop_product->product->brand_id);

        // MARKET_CATEGORY_ID
        if (!isset($market_param))
            $market_param = $market_params
                ->firstWhere('market_category_id', $shop_product->market_category_id);

        // ROOT_CATEGORY_ID
        if (!isset($market_param) && isset($shop_product->market_category_id))
            $market_param = $market_params
                ->firstWhere('root_category_id', $shop_product->market_category->root_category_id);

        // GENERAL: MARKET_ID
        if (!isset($market_param))
            $market_param = $market_params
                ->whereNull('brand_id')
                ->whereNull('market_category_id')
                ->whereNull('root_category_id')
                ->first();

        if (!isset($market_param) || ($market_param->fee == 0 && $market_param->fee_addon == 0))
            return [
                'mp_fee'       => 50,
                'mp_fee_addon' => 0,
            ];

        return [
            'mp_fee'       => $market_param->fee ?? 0,
            'mp_fee_addon' => $market_param->fee_addon ?? 0,
        ];
    } */

}
