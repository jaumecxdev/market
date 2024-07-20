<?php

namespace App;

use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\Mpe as FacadesMpe;
use Throwable;

/**
 * App\ShopProduct
 *
 * @property int $id
 * @property int|null $market_id
 * @property int|null $shop_id
 * @property int|null $product_id
 * @property string|null $marketProductSku
 * @property float $price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $set_group
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Price[] $log_prices
 * @property-read int|null $log_prices_count
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $order_items
 * @property-read int|null $order_items_count
 * @property-read \App\Product|null $product
 * @property-read \App\Shop|null $shop
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopFilter[] $shop_filters
 * @property-read int|null $shop_filters_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct filter(\App\Shop $shop, $params)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereMarketProductSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereSetGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property float $fee_mps
 * @property float $fee_mp
 * @property float $benefit_mps
 * @property float $cost
 * @property int $fixed
 * @property int $stock
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereBenefitMps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereFeeMp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereFeeMps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereFixed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereStock($value)
 * @property int $is_sku_child
 * @property int|null $currency_id
 * @property int|null $market_category_id
 * @property float $tax
 * @property-read \App\Currency|null $currency
 * @property-read \App\MarketCategory|null $market_category
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereIsSkuChild($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereMarketCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereTax($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketParam[] $market_params
 * @property-read int|null $market_params_count
 * @property float $param_fee
 * @property float $param_mp_fee
 * @property float $param_bfit
 * @property float $param_mp_fee_addon
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Price[] $prices
 * @property-read int|null $prices_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereMpFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereMpFeeAddon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereParamBfit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereParamFee($value)
 * @property float $param_bfit_min
 * @property float $param_price
 * @property float $param_stock_min
 * @property float $param_stock_max
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereParamBfitMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereParamPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereParamStockMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereParamStockMin($value)
 * @property float $param_stock
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopProduct whereParamStock($value)
 * @property-read \App\Provider $provider
 * @property int|null $provider_id
 * @method static Builder|ShopProduct whereProviderId($value)
 * @property int|null $last_product_id
 * @property-read \App\Product|null $last_product
 * @method static Builder|ShopProduct whereLastProductId($value)
 * @property int $enabled
 * @method static Builder|ShopProduct whereEnabled($value)
 * @property float $param_mps_fee
 * @method static Builder|ShopProduct whereParamMpsFee($value)
 * @property string|null $name
 * @property string|null $longdesc
 * @property mixed|null $attributes
 * @method static Builder|ShopProduct whereAttributes($value)
 * @method static Builder|ShopProduct whereLongdesc($value)
 * @method static Builder|ShopProduct whereName($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Promo[] $promos
 * @property-read int|null $promos_count
 * @property float $param_canon
 * @property float $param_rappel
 * @property float $param_ports
 * @method static Builder|ShopProduct whereParamCanon($value)
 * @method static Builder|ShopProduct whereParamPorts($value)
 * @method static Builder|ShopProduct whereParamRappel($value)
 * @property string|null $mps_sku
 * @property int|null $provider_category_id
 * @property string|null $code
 * @property-read mixed $brand_id
 * @property-read mixed $category_id
 * @property-read mixed $ean
 * @property-read mixed $pn
 * @property-read mixed $root_category_id
 * @property-read mixed $supplier_id
 * @property-read mixed $supplier_sku
 * @property-read mixed $upc
 * @property-read \App\ProviderCategory|null $provider_category
 * @method static Builder|ShopProduct whereCode($value)
 * @method static Builder|ShopProduct whereMpsSku($value)
 * @method static Builder|ShopProduct whereProviderCategoryId($value)
 * @property float $param_mp_lot
 * @property float $param_mp_lot_fee
 * @property float $param_mp_bfit_min
 * @property float $param_discount_price
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property float $param_reprice_fee_min
 * @property float $buybox_price
 * @property string|null $buybox_updated_at
 * @property float $bfit
 * @property float $mps_bfit
 * @property float $mp_bfit
 * @method static Builder|ShopProduct whereBfit($value)
 * @method static Builder|ShopProduct whereBuyboxPrice($value)
 * @method static Builder|ShopProduct whereBuyboxUpdatedAt($value)
 * @method static Builder|ShopProduct whereDiscountPrice($value)
 * @method static Builder|ShopProduct whereEndsAt($value)
 * @method static Builder|ShopProduct whereMpBfit($value)
 * @method static Builder|ShopProduct whereMpsBfit($value)
 * @method static Builder|ShopProduct whereParamMpBfitMin($value)
 * @method static Builder|ShopProduct whereParamMpFee($value)
 * @method static Builder|ShopProduct whereParamMpFeeAddon($value)
 * @method static Builder|ShopProduct whereParamMpLot($value)
 * @method static Builder|ShopProduct whereParamMpLotFee($value)
 * @method static Builder|ShopProduct whereParamRepriceFeeMin($value)
 * @method static Builder|ShopProduct whereStartsAt($value)
 * @property Carbon|null $param_starts_at
 * @property Carbon|null $param_ends_at
 * @property int $repriced
 * @method static Builder|ShopProduct whereParamDiscountPrice($value)
 * @method static Builder|ShopProduct whereParamEndsAt($value)
 * @method static Builder|ShopProduct whereParamStartsAt($value)
 * @method static Builder|ShopProduct whereRepriced($value)
 */
class ShopProduct extends Model
{
    use HelperTrait;

    protected $table = 'shop_products';

    protected $dates = [
        'param_starts_at',
        'param_ends_at',
    ];

    // price: Preu al MP
    // stock: Stock al MP
    // param_discount_price: Preu rebaixat al MP. Preu normal Taxat
    // param_starts_at: Data d'inici en que es són vàlides les condicions
    // param_ends_at: Data de fi en que es són vàlides les condicions
    // param_canon: Canon per afegir al cost
    // param_rappel: Rappel per treure al cost
    // param_ports: Ports per afegir al cost
    // param_fee: Marge % del proveïdor
    // param_mps_fee: Marge % o intermediacio de MPe
    // param_bfit_min: Benefici mínim del proveïdor. Si param_fee=0 -> Benefici mínim de MPe
    // param_price: Preu fixat al MP
    // param_stock: Stock fixat al MP
    // param_stock_min: No se publicará en la Tienda si tiene menos de STOCK MÍNIMO.
    // param_stock_max: Si Stock > STOCK MÁXIMO luego Stock Tienda = STOCK MÁXIMO.

    // param_mp_fee: Tarifa normal MP
    // param_mp_fee_addon: Afegit en € a la tarifa normal. PayPal +0,35 €
    // param_mp_lot: Tram de diferents tarifes. Amazon: 100 €
    // param_mp_lot_fee: Tarifa del Tram superior, normalment mes baixa. Amazon 8,24 %
    //          Si PVP <= 100€ -> lot=0, lot_fee = tarifa normal o formula v1.0
    // param_mp_bfit_min: Mínim bci pel MP. Amazon 0,30 €

    // param_reprice_fee_min: Marge % mínim del proveïdor al recalcular RePrice. Si param_fee=0 -> Marge mínim de MPe x RePrice
    // buybox_price: Preu mes economic del MP
    // buybox_updated_at: Data en que s'ha actualizat el buybox_price

    // bfit: Benefici del proveïdor. Si param_fee=0, bfit=0
    // mps_bfit: Benefici de intermediacio de MPe
    // mp_bfit: Benefici del MP
    protected $fillable = [
        'enabled', 'market_id', 'shop_id', 'product_id', 'last_product_id', 'market_category_id', 'currency_id',
        'provider_id', 'provider_category_id',
        'mps_sku', 'is_sku_child', 'marketProductSku', 'set_group',

        'cost', 'price', 'tax', 'stock',
        'bfit', 'mps_bfit', 'mp_bfit',
        'name', 'longdesc', 'attributes',

        'param_discount_price', 'param_starts_at', 'param_ends_at',
        'param_canon', 'param_rappel', 'param_ports',
        'param_fee', 'param_bfit_min', 'param_price', 'param_stock', 'param_stock_min', 'param_stock_max',
        'param_mps_fee',
        'param_reprice_fee_min', 'buybox_price', 'buybox_updated_at', 'repriced',

        'param_mp_fee', 'param_mp_fee_addon',
        'param_mp_lot', 'param_mp_lot_fee', 'param_mp_bfit_min'
    ];

    const SKU_ERROR = ['', 'ERROR', 'NO BRAND', 'NO PRODUCT', 'ORDER', 'ONLY MP', 'NO AUTH'];

    // Cuidadin: Mateix order que ShopParam::VALUE_FIELDS
    const VALUE_PARAM_FIELDS = ['param_fee', 'param_mps_fee', 'param_bfit_min', 'param_reprice_fee_min',
        'param_price', 'param_stock', 'param_stock_min', 'param_stock_max',
        /* 'param_canon', */ 'param_rappel', 'param_ports',
        'param_discount_price', 'param_starts_at', 'param_ends_at'
    ];

    const DEFAULT_HEADER = [
        [
            'product_id',
            'supplier_name',
            'market_name',
            'shop_name',
            'supplier_brand_name',
            'supplier_category_name',
            'pn',
            'ean',
            'supplierSku',
            'mps_sku',
            'marketProductSku',
            'stock',
            'tax',
            'cost',
            'param_canon',
            'param_ports',
            'price',
            'buybox_price',
            'name',
            'image',
            'param_fee',
            'bfit',
            'param_mp_fee',
            'param_mp_lot_fee',
            'mp_bfit',
            'longdesc'
        ]
    ];

    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }

    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id', 'id');
    }

    public function last_product()
    {
        return $this->belongsTo('App\Product', 'last_product_id', 'id');
    }

    public function market_category()
    {
        return $this->belongsTo('App\MarketCategory');
    }

    public function currency()
    {
        return $this->belongsTo('App\Currency');
    }

    public function provider()
    {
        return $this->belongsTo('App\Provider');
    }

    public function provider_category()
    {
        return $this->belongsTo('App\ProviderCategory');
    }


    // MANY

    public function order_items()
    {
        return $this->hasMany('App\OrderItem');
    }

    public function prices()
    {
        return $this->hasMany('App\Price');
    }

    public function promos()
    {
        return $this->hasMany('App\Promo');
    }



    // CUSTOM ATTRIBUTES


    public function setCostAttribute($value)
    {
        $this->attributes['cost'] = number_format((float)round($value, 2), 2, '.', '');
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = number_format((float)round($value, 2), 2, '.', '');
    }


    public function getNameAttribute($value)
    {
        return $value ?? FacadesMpe::buildString($this->product->buildTitle());
    }


    public function getLongdescAttribute($value)
    {
        return $value ?? $this->product->buildDescriptionLong4Excel();
    }


    public function getSupplierSkuAttribute()
    {
        return $this->product->supplierSku;
    }

    public function getEanAttribute()
    {
        return $this->product->ean;
    }

    public function getPnAttribute()
    {
        return $this->product->pn;
    }

    public function getUpcAttribute()
    {
        return $this->product->upc;
    }

    public function getSupplierIdAttribute()
    {
        return $this->product->supplier_id;
    }

    public function getBrandIdAttribute()
    {
        return $this->product->brand_id;
    }

    public function getCategoryIdAttribute()
    {
        return $this->product->category_id;
    }

    public function getRootCategoryIdAttribute()
    {
        return $this->market_category->root_category_id;
    }

    public function getMarketNameAttribute()
    {
        return $this->market->name;
    }

    public function getShopNameAttribute()
    {
        return $this->shop->name;
    }

    public function getImageAttribute()
    {
        return $this->product->public_url_image(0);      //getFirstImageFullUrl();
    }

    public function getSupplierBrandNameAttribute()
    {
        return $this->product->supplier_brand->name;
    }

    public function getSupplierCategoryNameAttribute()
    {
        return $this->product->supplier_category->name;
    }



    // CUSTOM

     public function getMPSSku($length = 128)
    {
        return $this->product->getMPSSku($length);
    }


    public function buildTitle($length = 255)
    {
        try {
            return mb_substr(stripslashes(strip_tags($this->product->name)), 0, $length);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$length, $this]);
        }
    }


    public function getImageFullUrl($image_src)
    {
        return Storage::url ('img/' .$this->product_id. '/' .$image_src);
    }


    public function getFirstImageFullUrl()
    {
        // ONLY WORKS with Product::filter($params)
        if (!isset($this->image_src)) return '';

        return Storage::url ('img/' .$this->product_id. '/' .$this->image_src);
    }


    public function getStoragePathImage()
    {
        return storage_path('app/public/img/' .$this->product_id. '/' .$this->image_src);
    }


    public function buildDescription4Mobile($length = 10000, $new_line_char = "\n")
    {
        return $this->product->buildDescription4Mobile($length, $new_line_char);
    }


    public function buildDescription4Html($length = 10000)
    {
        return $this->product->buildDescription4Html($length);
    }


    /* public function buildDescription4HtmlWithHeader()
    {
        return $this->product->buildDescription4HtmlWithHeader($this->shop->name, $this->shop->store_url, $this->shop->header_url);
    }
 */

    public function isUpgradeable()
    {
        return (isset($this->marketProductSku) && !in_array($this->marketProductSku, $this::SKU_ERROR) && $this->is_sku_child == false);
    }


    public function deleteSecure()
    {
        try {
            if ($this->prices->count()) $this->prices()->delete();
            if ($this->promos->count()) $this->promos()->delete();
            $this->delete();
            return true;

        } catch (Throwable $th) {
            // OrderItems
            $this->marketProductSku = 'NOT_DELETED';
            $this->save();
            return $this->nullWithErrors($th, __METHOD__, $this);
        };
    }



    /***** PRICES & STOCKS *****/


    public function getClientBfit($price, $bfit_min = 0)
    {
        // GET Benefit
        $bfit = 0;
        // Client exist ? -> $bfit_min == $client_bfit_min
        if ($this->param_fee != 0) {
            $bfit = $this->param_fee/100 * $price / (1 + $this->tax/100);
            if ($bfit < $bfit_min) $bfit = $bfit_min;     // $bfit_min == 0 -> Get Real Bfit
        }

        return round($bfit, 2);
    }


    public function setClientBfit($price, $bfit_min = 0)
    {
        $bfit = $this->getClientBfit($price, $bfit_min);

        $this->bfit = $bfit;
        $this->save();

        return $bfit;
    }


    public function getMpsBfit($price, $bfit_min = 0)
    {
        $mps_bfit = $this->param_mps_fee/100 * $price;
        // Client NO exist ? -> $bfit_min == $mps_bfit_min
        if ($this->param_fee == 0 && $mps_bfit < $bfit_min) $mps_bfit = $bfit_min;    // $bfit_min == 0 -> Get Real Mps_Bfit

        return round($mps_bfit, 2);
    }


    public function setMpsBfit($price, $bfit_min = 0)
    {
        $mps_bfit = $this->getMpsBfit($price, $bfit_min);

        $this->mps_bfit = $mps_bfit;
        $this->save();

        return $mps_bfit;
    }


    public function getMarketBfit($price, $mp_bfit_min = 0)
    {
        // GET Marketplace Benefit: OLD FORMULA V1.0
        if ($this->param_mp_lot == 0 || ($price <= $this->param_mp_lot))
            $mp_bfit = $this->param_mp_fee / 100 * $price + $this->param_mp_fee_addon;
        // GET Marketplace Benefit: NEW LOT FORMULA V2.0
        else {
            $mp_bfit = $this->param_mp_fee / 100 * $this->param_mp_lot + $this->param_mp_fee_addon;
            $mp_bfit += $this->param_mp_lot_fee / 100 * ($price - $this->param_mp_lot);
        }

        if ($mp_bfit < $mp_bfit_min) $mp_bfit = $mp_bfit_min;

        return round($mp_bfit, 2);
    }


    public function setMarketBfit($price, $mp_bfit_min = 0)
    {
        $mp_bfit = $this->getMarketBfit($price, $mp_bfit_min);

        $this->mp_bfit = $mp_bfit;
        $this->save();

        return $mp_bfit;
    }


    public function getMPPriceByClientBfitMin($cost, $mp_fee, $mp_lot = 0, $mp_lot_fee = 0, $bfit_min = 0)
    {
        // PVP = 1,21 * (TRAMO*MP_FEE_1 - TRAMO*MP_FEE_2 + COST + BFIT_MIN + MP_BFIT_ADDON) / (1 - 1,21*MP_FEE_2)
        return (1 + $this->tax/100) * ($mp_lot * $mp_fee/100 -  $mp_lot * $mp_lot_fee/100 + $cost + $bfit_min + $this->param_mp_fee_addon) /
            (1 - (1 + $this->tax/100) * $mp_lot_fee/100);
    }


    public function getMPPrice($cost, $client_fee, $mps_fee, $mp_fee, $mp_lot = 0, $mp_lot_fee = 0, $bfit_min = 0, $mp_bfit_min = 0)
    {
        try {
            $mp_fee += $mps_fee;
            $mp_lot_fee += $mps_fee;
            // lot == 0 -> OLD FORMULA V1.0
            if ($mp_lot == 0) $mp_lot_fee = $mp_fee;

            // FORMULA V2.0
            // PVP = 1,21 * (TRAMO*MP_FEE_1 - TRAMO*MP_FEE_2 + COST + MP_BFIT_ADDON) / (1 - FEE - 1,21*MP_FEE_2)
            $price = (1 + $this->tax/100) * ($mp_lot * $mp_fee/100 - $mp_lot * $mp_lot_fee/100 + $cost + $this->param_mp_fee_addon) /
                (1 - $client_fee/100 - (1 + $this->tax/100) * $mp_lot_fee/100);

            // $price <= $mp_lot ? -> OLD FORMULA V1.0
            // PVP = 1,21 * (COST + MP_BFIT_ADDON) / (1 - FEE - 1,21*MP_FEE)
            if ($price <= $mp_lot && $mp_lot != 0) {
                $mp_lot = 0;
                return $this->getMPPrice($cost, $client_fee, $mps_fee, $mp_fee, $mp_lot, $mp_lot_fee, $bfit_min, $mp_bfit_min);
            }

            $client_bfit = $this->setClientBfit($price, 0);
            $mps_bfit = $this->setMpsBfit($price, 0);

            // bfit_min ?
            if ($bfit_min > 0) {
                // Client exist ? -> $bfit_min == $client_bfit_min
                if ($client_fee != 0) {
                    if ($client_bfit < $bfit_min)
                        $price = $this->getMPPriceByClientBfitMin($cost, $mp_fee, $mp_lot, $mp_lot_fee, $bfit_min);
                }
                // Client NO exist ? -> $bfit_min == $mps_bfit_min
                else {
                    if ($mps_bfit < $bfit_min) {
                        $mp_bfit = $this->setMarketBfit($price, $mp_bfit_min);
                        // $bfit_min = ($price / (1 + $this->tax/100)) - $mp_bfit - $cost;
                        $price = (1 + $this->tax/100) * ($bfit_min + $mp_bfit + $cost);
                        $mps_bfit = $this->setMpsBfit($price, $bfit_min);

                        // ReCALC New $mp_bfit && $price
                        $mp_bfit = $this->setMarketBfit($price, $mp_bfit_min);
                        $price = (1 + $this->tax/100) * ($bfit_min + $mp_bfit + $cost);
                    }
                }
            }

            // mp_bfit_min ? Amazon 0.30 €
            $mp_bfit = $this->setMarketBfit($price, 0);
            if ($mp_bfit < $mp_bfit_min) {
                $price = (1 + $this->tax/100) * ($mp_bfit_min + $mps_bfit + $client_bfit + $cost);
                $mp_bfit = $this->setMarketBfit($price, $mp_bfit_min);
            }

            // Client exist ? -> $bfit_min == $client_bfit_min
            if ($client_fee != 0) {
                $this->setClientBfit($price, $bfit_min);
                $this->setMpsBfit($price, 0);
            }
            // Client NO exist ? -> $bfit_min == $mps_bfit_min
            else {
                $this->setClientBfit($price, 0);
                $this->setMpsBfit($price, $bfit_min);
            }

            return round($price, 2);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$cost, $client_fee, $mps_fee, $mp_fee, $mp_lot, $mp_lot_fee, $bfit_min, $mp_bfit_min, $this]);
        }
    }


    public function logPrice()
    {
        try {
            Price::updateOrcreate([
                'product_id'        => $this->product->id,
                'marketProductSku'  => $this->marketProductSku,
                'shop_id'           => $this->shop_id,
                'name'              => $this->marketProductSku ? 'Update Market Product' : 'New Market Product',
                'cost'              => $this->cost,
                'price'             => $this->price,
                'stock'             => $this->stock,
                'bfit'              => FacadesMpe::getClientBfit($this->price, $this->param_fee, $this->param_bfit_min, $this->tax),
                'mps_bfit'          => FacadesMpe::getMpsBfit($this->price, $this->param_mps_fee),
                'mp_bfit'           => $this->getMarketBfit($this->price, $this->param_mp_fee, $this->param_mp_fee_addon, $this->tax),
            ],[]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function getCost()
    {
        try {
            return round(
                (floatval($this->product->cost) + $this->param_canon) * (1 - $this->param_rappel / 100) + $this->param_ports,
                2);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $this);
        }
    }


    public function setPriceStock($tax = null, $cost_is_price = false, $product_ids_today_orders = null)
    {
        try {
            $test = [];
            $test['shop_product'] = $this;
            $test['product'] = $this->product;

            // Then When update prices TO Marketplaces, Disable Product BY Stock = 0
            if (!$this->enabled || !$this->product->ready || !$this->shop->enabled) {

                if ($this->stock != 0) {
                    $this->stock = 0;
                    $this->save();
                    $this->refresh();
                }

                return $this;
            }

            $cost = $this->getCost();      //  $this->product->cost;

            $tax = $tax ?? $this->product->tax;
            if ($tax != $this->tax) {
                $this->tax = $tax;
                //$this->save();
                //$this->refresh();
            }

            // Fixed Param Price
            $price = $this->param_price;
            if ($price == 0) {
                if ($cost_is_price) $price = $cost;
                else {
                    $price = $this->getMPPrice($cost, $this->param_fee, $this->param_mps_fee, $this->param_mp_fee,
                        $this->param_mp_lot, $this->param_mp_lot_fee, $this->param_bfit_min, $this->param_mp_bfit_min);
                    /* $price = FacadesMpe::getMPPrice($cost, $this->param_fee, $this->param_mps_fee, $this->param_mp_fee, $this->param_mp_fee_addon, $tax);

                    if ($this->param_bfit_min > 0) {
                        if (($this->param_fee > 0 && FacadesMpe::getClientBfit($price, $this->param_fee, 0, $tax) < $this->param_bfit_min) ||
                            ($this->param_mps_fee > 0 && FacadesMpe::getMpsBfit($price, $this->param_mps_fee) < $this->param_bfit_min)) {

                            $price = FacadesMpe::getMPPriceByBfitMin($cost, $this->param_bfit_min, $this->param_mp_fee, $this->param_mp_fee_addon, $tax);
                        }
                    } */
                }
            }

            // Fixed Param Stock
            $stock = $this->param_stock;
            if ($stock == 0) {
                $stock = $this->product->stock;
                if ($this->param_stock_min != 0 && $stock < $this->param_stock_min) $stock = 0;
                if ($this->param_stock_max != 0 && $stock > $this->param_stock_max) $stock = $this->param_stock_max;

                // Orders Today ?
                if (!$product_ids_today_orders)
                    $product_ids_today_orders = Order::getProductIdsTodayOrders();

                if (in_array($this->product_id, $product_ids_today_orders)) {
                    $product_id_today_orders_count = Order::getProductIdTodayOrdersCount($this->product_id);
                    $stock -= intval($product_id_today_orders_count);
                    if ($stock < 0) $stock = 0;
                }
            }

            if ($this->wasChanged() ||
                $this->cost != $this->product->cost ||
                $this->price != $price ||
                $this->tax != $tax ||
                $this->stock != $stock) {

                $this->cost = $this->product->cost;
                $this->price = $price;
                $this->tax = $tax;
                $this->stock = $stock;
                $this->repriced = ($this->buybox_price == 0 || $this->buybox_price < $price) ? 0 : 1;

                $this->save();
                $this->refresh();
                //$this->logPrice();
            }

            // TEMPORAL
            $this->repriced = ($this->buybox_price == 0 || $this->buybox_price < $price) ? 0 : 1;
            $this->save();
            $this->refresh();

            return $this;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$tax, $cost_is_price, $this]);
        }
    }


    public function setBuyBoxPrice($buybox_price)
    {
        if ($buybox_price) $this->buybox_price = $buybox_price;
        $this->buybox_updated_at = Carbon::now();
        $this->repriced = ($this->price <= $this->buybox_price) ? 1 : 0;
        $this->save();
        $this->refresh();
    }


    public function setReprice()
    {
        try {
            $reprice = 0;
            $subtract_buybox_price = 0.05;
            $reprice_fee_min = $this->param_reprice_fee_min ?? 1;
            $reprice_bfit_min = $this->param_bfit_min ?? 5;

            // Growth Benefit
            if ($this->price < $this->buybox_price - 1 &&
                $this->price > $this->buybox_price * 0.95) {

                $competitive_price = $this->buybox_price - 0.50;
                $reprice = 1;


                // CALC BFITS
                /* $mp_bfit = $this->getMarketBfit($competitive_price, $this->param_mp_bfit_min);
                $bfit = ($competitive_price / (1 + $this->tax/100)) - $mp_bfit - $this->getCost();

                // $bfit == $mps_bfit && $fee == $mps_fee
                // $bfit == client_bfit && $fee == $client_fee && $mps_bfit_min == 0
                if ($this->param_fee != 0) {
                    $mps_bfit = $this->getMpsBfit($competitive_price, 0);
                    $bfit -= $mps_bfit;
                    //$fee = 100 * ($bfit / $competitive_price);
                    $client_bfit = $bfit;
                } else {
                    $client_bfit = 0;
                    $mps_bfit = $bfit;
                }

                $fee = 100 * ($bfit / $competitive_price);

                $this->price = $competitive_price;
                $this->bfit = $client_bfit;
                $this->mps_bfit = $mps_bfit;
                $this->mp_bfit = $mp_bfit;
                $this->repriced = 1;
                $this->save();
                $this->refresh(); */


            }
            // Normal RePrice
            elseif ($this->price != $this->buybox_price &&
                $this->price > $this->buybox_price - $subtract_buybox_price &&
                $this->param_discount_price == 0 && $this->param_price == 0) {

                $competitive_price = $this->buybox_price - $subtract_buybox_price;
                $reprice = 2;

                // CALC BFITS
                /*$mp_bfit = $this->getMarketBfit($competitive_price, $this->param_mp_bfit_min);
                $bfit = ($competitive_price / (1 + $this->tax/100)) - $mp_bfit - $this->getCost();

                // $bfit == $mps_bfit && $fee == $mps_fee
                // $bfit == client_bfit && $fee == $client_fee && $mps_bfit_min == 0
                 if ($this->param_fee != 0) {
                    $mps_bfit = $this->getMpsBfit($competitive_price, 0);
                    $bfit -= $mps_bfit;
                    //$fee = 100 * ($bfit / $competitive_price);
                    $client_bfit = $bfit;
                } else {
                    $client_bfit = 0;
                    $mps_bfit = $bfit;
                }

                $fee = 100 * ($bfit / $competitive_price);

                // Reprice IS possible
                if ($fee >= $reprice_fee_min && $bfit >= $reprice_bfit_min) {
                    $this->price = $competitive_price;
                    $this->bfit = $client_bfit;
                    $this->mps_bfit = $mps_bfit;
                    $this->mp_bfit = $mp_bfit;
                    $this->repriced = 1;
                    $this->save();
                    $this->refresh();
                } */
            }

            if ($reprice) {
                // CALC BFITS
                $mp_bfit = $this->getMarketBfit($competitive_price, $this->param_mp_bfit_min);
                $bfit = ($competitive_price / (1 + $this->tax/100)) - $mp_bfit - $this->getCost();

                // $bfit == $mps_bfit && $fee == $mps_fee
                // $bfit == client_bfit && $fee == $client_fee && $mps_bfit_min == 0
                if ($this->param_fee != 0) {
                    $mps_bfit = $this->getMpsBfit($competitive_price, 0);
                    $bfit -= $mps_bfit;
                    //$fee = 100 * ($bfit / $competitive_price);
                    $client_bfit = $bfit;
                } else {
                    $client_bfit = 0;
                    $mps_bfit = $bfit;
                }

                $fee = 100 * ($bfit / $competitive_price);

                if ($reprice == 1 ||
                    ($fee >= $reprice_fee_min && $bfit >= $reprice_bfit_min)) {

                    $this->price = $competitive_price;
                    $this->bfit = $client_bfit;
                    $this->mps_bfit = $mps_bfit;
                    $this->mp_bfit = $mp_bfit;
                    $this->repriced = 1;
                    $this->save();
                    $this->refresh();
                }
            }

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$competitive_price, $reprice_fee_min, $reprice_bfit_min, $this]);
        }
    }


    public function setCanon()
    {
        if ($this->product_id && $this->product->category_id) {
            // IDIOMUND BLANES 1, INGRAM 8, VINZEO 10, ESPRINET 13: No inclou Canon
            // IDIOMUND TECHDATA 11 i DESYMAN 14: SI inclou Canon
            if (!in_array($this->product->supplier_id, [11, 14])) {
                $this->param_canon = $this->product->category->getCanon($this->shop->locale ?? 'es');
                $this->save();
            }
            else {
                $this->param_canon = 0;
                $this->save();
            }
        }
    }


    public function setShopParams($fields_shop_params_queries = null)
    {
        try {

            //if ($this->supplier_sku != 'TV06224154') return true;

            if (!isset($fields_shop_params_queries))
                $fields_shop_params_queries = $this->shop->getFieldsShopParamsQueries();

            //$param_canon = $this->getShopParam('canon', $fields_shop_params_queries['canon']);
            $param_rappel = $this->getShopParam('rappel', $fields_shop_params_queries['rappel']);
            $param_ports = $this->getShopParam('ports', $fields_shop_params_queries['ports']);

            $param_fee = $this->getShopParam('fee', $fields_shop_params_queries['fee']);
            $param_bfit_min = $this->getShopParam('bfit_min', $fields_shop_params_queries['bfit_min']);
            $param_mps_fee = $this->getShopParam('mps_fee', $fields_shop_params_queries['mps_fee']);
            $param_price = $this->getShopParam('price', $fields_shop_params_queries['price']);
            $param_stock = $this->getShopParam('stock', $fields_shop_params_queries['stock']);
            $param_stock_min = $this->getShopParam('stock_min', $fields_shop_params_queries['stock_min']);
            $param_stock_max = $this->getShopParam('stock_max', $fields_shop_params_queries['stock_max']);

            $param_discount_price = $this->getShopParam('discount_price', $fields_shop_params_queries['discount_price']);
            $param_starts_at = $this->getShopParam('starts_at', $fields_shop_params_queries['starts_at']);
            $param_ends_at = $this->getShopParam('ends_at', $fields_shop_params_queries['ends_at']);

            $param_reprice_fee_min = $this->getShopParam('reprice_fee_min', $fields_shop_params_queries['reprice_fee_min']);

            $this->update([
                //'param_canon'     => $param_canon ?? 0,
                'param_rappel'    => $param_rappel ?? 0,
                'param_ports'     => $param_ports ?? 0,

                'param_fee'       => $param_fee ?? 0,
                'param_bfit_min'  => $param_bfit_min ?? 0,
                'param_mps_fee'   => $param_mps_fee ?? 0,
                'param_price'     => $param_price ?? 0,
                'param_stock'     => $param_stock ?? 0,
                'param_stock_min' => $param_stock_min ?? 0,
                'param_stock_max' => $param_stock_max ?? 0,

                'param_discount_price'  => $param_discount_price ?? 0,
                'param_starts_at'       => $param_starts_at ?? null,
                'param_ends_at'         => $param_ends_at ?? null,

                'param_reprice_fee_min' => $param_reprice_fee_min ?? 0
            ]);

            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$fields_shop_params_queries, $this]);
        }
    }


    public function getShopParam($field, $field_params_query)
    {
        try {
            if (!isset($field_params_query) || !$field_params_query->count())
                return null;

            $shop_product_cost = $this->cost;
            $shop_product_field_params_query = clone $field_params_query;
            $shop_product_field_params_query->where(function (Builder $query) use ($shop_product_cost) {
                return $query->where(function (Builder $subquery) use ($shop_product_cost) {
                    return $subquery->whereNull('shop_params.cost_min')->orWhere('shop_params.cost_min', '<=', $shop_product_cost);
                })->where(function (Builder $subquery) use ($shop_product_cost) {
                    return $subquery->whereNull('shop_params.cost_max')->orWhere('shop_params.cost_max', '>=', $shop_product_cost);
                });
            });

            if (!$shop_product_field_params_query->count())
                return null;

            foreach ($shop_product_field_params_query->get() as $shop_param) {
                $count_issets = 0;
                $count_match = 0;

                /* const CONDITION_FIELDS = ['product_id', 'supplierSku', 'ean', 'pn', 'upc',
                    'supplier_id', 'brand_id', 'category_id', 'market_category_id', 'root_category_id']; */

                foreach (ShopParam::CONDITION_FIELDS as $condition_field) {

                    if (isset($shop_param->$condition_field) &&
                        (!is_numeric($shop_param->$condition_field) || $shop_param->$condition_field != 0)) {

                        $count_issets++;
                        $test[$condition_field]['shop_param'] = $shop_param->$condition_field;
                        if ($shop_param->$condition_field == $this->$condition_field) {
                            $count_match++;
                            $test[$condition_field]['shop_product'] = $this->$condition_field;
                        }
                    }
                }

                // FIRST ShopParam(s) THAT Match WITH ShopProduct
                if ($count_issets && $count_match && $count_issets == $count_match) {
                    return $shop_param->$field;
                }
            }

            // IF All condition_fields ARE Null, GET GENERAL Param
            if (!$count_issets)     // $count_match
                return $shop_param->$field;     // $shop_params->get()->last()

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$field, $field_params_query, $this]);
        }
    }


    public function setMarketParams($market_params = null)
    {
        try {
            // 2.- brand_id && market_category_id
            // 3.- brand_id && root_category_id
            // 4.- brand_id
            // 5.- market_category_id
            // 6.- root_category_id
            // 7.- GENERAL market_id: ALL null

            if (!isset($market_params))
                $market_params = $this->market->market_params;

            // If NO Marketplace Params (as PCEducacion), all FEEs = 0
            if (!$market_params->count()) {
                $this->update([
                    'param_mp_fee'          => 0,
                    'param_mp_fee_addon'    => 0,
                    'param_mp_lot'          => 0,
                    'param_mp_lot_fee'      => 0,
                    'param_mp_bfit_min'     => 0
                ]);

                return true;
            };

            // BRAND_ID & MARKET_CATEGORY_ID || PARENTS
            $market_param = $market_params
                ->where('brand_id', $this->product->brand_id)
                ->firstWhere('market_category_id', $this->market_category_id);

            if (!isset($market_param) && isset($this->market_category_id)) {
                $market_category = $this->market_category;
                while (!isset($market_param) && isset($market_category->parent_id)) {
                    $market_category = $market_category->parent;
                    if (!isset($market_category)) break;
                    $market_param = $market_params
                        ->where('brand_id', $this->product->brand_id)
                        ->firstWhere('market_category_id', $market_category->id);
                }
            }

            // BRAND_ID & ROOT_CATEGORY_ID
            if (!isset($market_param) && isset($this->market_category_id) && isset($this->market_category->root_category_id))
                $market_param = $market_params
                    ->where('brand_id', $this->product->brand_id)
                    ->firstWhere('root_category_id', $this->market_category->root_category_id);

            // BRAND_ID
            if (!isset($market_param))
                $market_param = $market_params
                    ->firstWhere('brand_id', $this->product->brand_id);

            // MARKET_CATEGORY_ID || PARENTS
            if (!isset($market_param)) {
                $market_param = $market_params
                    ->firstWhere('market_category_id', $this->market_category_id);
            }

            if (!isset($market_param) && isset($this->market_category_id)) {
                $market_category = $this->market_category;
                while (!isset($market_param) && isset($market_category->parent_id)) {
                    $market_category = $market_category->parent;
                    if (!isset($market_category)) break;
                    $market_param = $market_params
                        ->firstWhere('market_category_id', $market_category->id);
                }
            }

            // ROOT_CATEGORY_ID
            if (!isset($market_param) && isset($this->market_category_id) && isset($this->market_category->root_category_id))
                $market_param = $market_params
                    ->firstWhere('root_category_id', $this->market_category->root_category_id);

            // GENERAL: MARKET_ID
            if (!isset($market_param))
                $market_param = $market_params
                    ->whereNull('brand_id')
                    ->whereNull('market_category_id')
                    ->whereNull('root_category_id')
                    ->first();

            if (!isset($market_param) || ($market_param->fee == 0 && $market_param->fee_addon == 0)) {
                $this->update([
                    'param_mp_fee'       => 50,
                    'param_mp_fee_addon' => 5,
                    'param_mp_lot'       => 100,
                    'param_mp_lot_fee'   => 50,
                    'param_mp_bfit_min'  => 100
                ]);

                return true;
            }

            $this->update([
                'param_mp_fee'       => $market_param->fee ?? 0,
                'param_mp_fee_addon' => $market_param->fee_addon ?? 0,
                'param_mp_lot'       => $market_param->lot ?? 0,
                'param_mp_lot_fee'   => $market_param->lot_fee ?? 0,
                'param_mp_bfit_min'  => $market_param->bfit_min ?? 0,
            ]);

            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$market_params, $this]);
        }
    }



    // SCOPES


    public function scopeFilter(Builder $query, Shop $shop, $params)
    {
        try {
            $query->select('shop_products.*',
                    'markets.product_url as market_product_url',
                    'products.ready as product_ready',
                    'products.supplier_id as supplier_id',
                    'products.brand_id as brand_id',
                    'products.category_id as category_id',
                    'categories.name as category_name',
                    'products.supplierSku as supplierSku',
                    'products.pn as pn',
                    'products.ean as ean',
                    'products.upc as upc',
                    'products.isbn as isbn',
                    'products.provider_id as provider_id',
                    'suppliers.name as supplier_name',
                    'brands.name as brand_name',
                    'categories.name as category_name',
                    'images.src as image_src'
                    //'products.name as name',
                    //DB::raw('count(product_attributes.id) as product_attributes_count')
                )
                ->leftJoin('markets', 'shop_products.market_id', '=', 'markets.id')
                //->where('shop_products.market_id', $shop->market_id)
                //->leftJoin('product_attributes', 'products.id', '=', 'product_attributes.product_id')     // Slow 1 minut
                ->leftJoin('products', 'shop_products.product_id', '=', 'products.id')
                ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                //->leftJoin('supplier_categories', 'categories.id', '=', 'supplier_categories.category_id')
                ->leftJoin('supplier_categories', 'supplier_categories.id', '=', 'products.supplier_category_id')
                ->leftJoin('images', 'images.id', '=', DB::raw('(SELECT id FROM images WHERE images.product_id = products.id LIMIT 1)'))
                ->where('shop_products.shop_id', $shop->id)
                ->groupBy('shop_products.id')
                ->groupBy('images.id');

            if (!isset($params['supplier_category_name'])) $params['supplier_category_id'] = null;
            if ( isset($params['supplier_category_id']) && $params['supplier_category_id'] != null) {
                $query->where('supplier_categories.id', $params['supplier_category_id']);
            }

            if (!isset($params['category_name'])) $params['category_id'] = null;
            if (isset($params['category_id']) && $params['category_id'] != null) {
                $query->where('products.category_id', '=', $params['category_id']);
            }

            if (!isset($params['market_category_name'])) $params['market_category_id'] = null;
            if (isset($params['market_category_id']) && $params['market_category_id'] != null) {
                $query->where('shop_products.market_category_id', '=', $params['market_category_id']);
            }

            if (!isset($params['brand_name'])) $params['brand_id'] = null;
            if (isset($params['brand_id']) && $params['brand_id'] != null && isset($params['brand_name'])) {
                $query->where('products.brand_id', '=', $params['brand_id']);
            }

            if (isset($params['supplier_id']) && $params['supplier_id'] != null) {
                $query->where('products.supplier_id', $params['supplier_id']);
            }

            if (isset($params['cost_min']) && $params['cost_min'] != null) {
                $query-> where('shop_products.cost', '>=', floatval($params['cost_min']));
            }
            if (isset($params['cost_max']) && $params['cost_max'] != null) {
                $query-> where('shop_products.cost', '<=', floatval($params['cost_max']));
            }

            if (isset($params['price_min']) && $params['price_min'] != null) {
                $query-> where('shop_products.price', '>=', floatval($params['price_min']));
            }
            if (isset($params['price_max']) && $params['price_max'] != null) {
                $query-> where('shop_products.price', '<=', floatval($params['price_max']));
            }

            if (isset($params['stock_min']) && $params['stock_min'] != null) {
                $query-> where('shop_products.stock', '>=', intval($params['stock_min']));
            }
            if (isset($params['stock_max']) && $params['stock_max'] != null) {
                $query-> where('shop_products.stock', '<=', intval($params['stock_max']));
            }

            if (isset($params['repriced']) && $params['repriced'] != null) {
                $query->where('shop_products.repriced', $params['repriced']);
            }

            if (!isset($params['item_reference'])) $params['product_id'] = null;
            if (isset($params['item_reference']) && $params['item_reference'] != null) {
                if ($params['item_reference'] == 'null') $params['item_reference'] = null;

                if ($params['item_select'] == 'id')
                    $query->where('products.id', $params['item_reference']);
                elseif ($params['item_select'] == 'supplierSku')
                    $query->where('products.supplierSku', $params['item_reference']);
                elseif ($params['item_select'] == 'pn')
                    $query->where('products.pn', $params['item_reference']);
                elseif ($params['item_select'] == 'ean')
                    $query->where('products.ean', $params['item_reference']);
                elseif ($params['item_select'] == 'upc')
                    $query->where('products.upc', $params['item_reference']);
                elseif ($params['item_select'] == 'isbn')
                    $query->where('products.isbn', $params['item_reference']);
                elseif ($params['item_select'] == 'gtin')
                    $query->where('products.gtin', $params['item_reference']);
                elseif ($params['item_select'] == 'name')
                    if (isset($params['product_id']) && $params['product_id'] != null)
                        $query->where('products.id', $params['product_id']);
                    else
                        $query->where('products.name', 'LIKE', '%' .$params['item_reference']. '%');
            }

            if (isset($params['supplierSku']) && $params['supplierSku'] != null) {
                if ($params['supplierSku'] == 'null') $params['markesupplierSkutProductSku'] = null;
                $query->where('products.supplierSku', $params['supplierSku']);
            }

            if (isset($params['MPSSku']) && $params['MPSSku'] != null) {
                if ($params['MPSSku'] == 'null') $query->whereNull('shop_products.product_id');
                else $query->where('shop_products.mps_sku', 'LIKE', '%' .$params['MPSSku']. '%');
            }

            if (isset($params['marketProductSku']) && $params['marketProductSku'] != null) {
                if ($params['marketProductSku'] == 'nonull')
                    $query->whereNotNull('marketProductSku');
                elseif ($params['marketProductSku'] == 'skuok')
                    $query->whereNotIn('marketProductSku', $this::SKU_ERROR);
                elseif ($params['marketProductSku'] == 'skuerror')
                    $query->whereIn('marketProductSku', $this::SKU_ERROR);
                else {
                    if ($params['marketProductSku'] == 'null') $params['marketProductSku'] = null;
                    $query->where('marketProductSku', $params['marketProductSku']);
                }
            }

            // ORDER BY
            if ( isset($params['order_by']) && $params['order_by'] != null) {
                $query->orderBy($params['order_by'], $params['order']);
            }

            return $query;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop, $params]);
        }
    }

}
