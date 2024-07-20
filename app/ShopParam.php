<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\FeeParam
 *
 * @property-read \App\Brand $brand
 * @property-read \App\Category $category_id
 * @property-read \App\Market $market
 * @property-read \App\Product $product
 * @property-read \App\Supplier $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam query()
 * @mixin \Eloquent
 * @property int $id
 * @property int|null $supplier_id
 * @property int|null $market_id
 * @property int|null $product_id
 * @property int|null $brand_id
 * @property float $fee
 * @property float $bfit_min
 * @property float $rappel
 * @property int $stock_min
 * @property int $stock_max
 * @property float $price
 * @property-read \App\Category|null $category
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereBfitMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereRappel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereStockMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereStockMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereSupplierId($value)
 * @property float $ports
 * @property float $canon
 * @property string|null $supplierSku
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereCanon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam wherePorts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereSupplierSku($value)
 * @property float $stock
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereStock($value)
 * @property int|null $shop_id
 * @property-read \App\Shop|null $shop
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FeeParam whereShopId($value)
 * @property string|null $product_name
 * @property string|null $pn
 * @property string|null $ean
 * @property string|null $upc
 * @property string|null $isbn
 * @property string|null $gtin
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FeeParam whereEan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FeeParam whereGtin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FeeParam whereIsbn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FeeParam wherePn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FeeParam whereProductName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\FeeParam whereUpc($value)
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopParam whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopParam whereStartsAt($value)
 * @property int|null $root_category_id
 * @property int|null $market_category_id
 * @property float $mps_fee
 * @property-read \App\MarketCategory|null $market_category
 * @property-read \App\RootCategory|null $root_category
 * @method static \Illuminate\Database\Eloquent\Builder|ShopParam whereMarketCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopParam whereMpsFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopParam whereRootCategoryId($value)
 * @property float $discount_price
 * @property float|null $cost_min
 * @property float|null $cost_max
 * @property float $reprice_fee_min
 * @method static \Illuminate\Database\Eloquent\Builder|ShopParam whereCostMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopParam whereCostMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopParam whereDiscountPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShopParam whereRepriceFeeMin($value)
 */
class ShopParam extends Model
{
    protected $table = 'shop_params';

    public $timestamps = false;

    protected $dates = [
        'starts_at',
        'ends_at',
    ];

    // fee: Marge % del proveidor
    // mps_fee: Marge % de MPe o intermediació
    // bfit_min: Benefici mínim € del proveïdor. Si fee=0 -> Benefici mínim de MPe
    // reprice_fee_min: Marge % mínim del proveïdor al recalcular RePrice. Si fee=0 -> Marge mínim de MPe x RePrice
    // price: Preu fixat al MP
    // stock: Stock fixat al MP
    // stock_min: No se publicará en la Tienda si tiene menos de STOCK MÍNIMO.
    // stock_max: Si Stock > STOCK MÁXIMO luego Stock Tienda = STOCK MÁXIMO.
    // discount_price: Preu rebaixat al MP. Preu normal Taxat
    // starts_at: Data d'inici en que es són vàlides les condicions
    // ends_at: Data de fi en que es són vàlides les condicions
    protected $fillable = [
        'shop_id', 'supplier_id', 'product_id', 'supplier_brand_id', 'brand_id', 'supplier_category_id', 'category_id',
        'market_category_id', 'root_category_id',
        'supplierSku', 'pn', 'ean', 'upc', 'isbn', 'gtin',
        'cost_min', 'cost_max',
        'canon', 'rappel', 'ports',
        'fee', 'bfit_min', 'mps_fee', 'reprice_fee_min', 'price', 'stock', 'stock_min', 'stock_max',
        'discount_price', 'starts_at', 'ends_at'
    ];

    const CONDITION_FIELDS = ['product_id', 'supplierSku', 'ean', 'pn', 'upc',
        'supplier_id', 'supplier_brand_id', 'brand_id', 'supplier_category_id', 'category_id',
        'market_category_id', 'root_category_id'];

    // Cuidadin: Mateix order que ShopProduct::VALUE_PARAM_FIELDS
    const VALUE_FIELDS = ['fee', 'mps_fee', 'bfit_min', 'reprice_fee_min',
        'price', 'stock', 'stock_min', 'stock_max',
        /* 'canon', */ 'rappel', 'ports',
        'discount_price', 'starts_at', 'ends_at'];

    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    public function supplier_brand()
    {
        return $this->belongsTo('App\SupplierBrand');
    }

    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

    public function supplier_category()
    {
        return $this->belongsTo('App\SupplierCategory');
    }

    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function market_category()
    {
        return $this->belongsTo('App\MarketCategory');
    }

    public function root_category()
    {
        return $this->belongsTo('App\RootCategory');
    }



}
