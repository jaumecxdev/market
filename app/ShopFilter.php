<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\ShopFilter
 *
 * @property int $id
 * @property int|null $shop_id
 * @property int|null $product_id
 * @property int|null $brand_id
 * @property int|null $category_id
 * @property int|null $supplier_id
 * @property int|null $type_id
 * @property int|null $status_id
 * @property float|null $cost_min
 * @property float|null $cost_max
 * @property int|null $stock_min
 * @property int|null $stock_max
 * @property int|null $limit_products
 * @property-read \App\Status|null $Status
 * @property-read \App\Brand|null $brand
 * @property-read \App\Category|null $category
 * @property-read \App\Product|null $product
 * @property-read \App\Shop|null $shop
 * @property-read \App\Supplier|null $supplier
 * @property-read \App\Type|null $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereCostMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereCostMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereLimitProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereStockMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereStockMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopFilter whereTypeId($value)
 * @mixin \Eloquent
 * @method static Builder|ShopFilter filter($params)
 */
class ShopFilter extends Model
{
    protected $table = 'shop_filters';

    public $timestamps = false;

    protected $fillable = [
        'shop_id', 'product_id', 'supplier_brand_id', 'brand_id', 'supplier_category_id', 'category_id',
        'supplier_id', 'type_id', 'status_id',
        'cost_min', 'cost_max', 'stock_min', 'stock_max', 'limit_products'
    ];

    public function shop()
    {
        return $this->belongsTo('App\Shop');
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

    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function type()
    {
        return $this->belongsTo('App\Type');
    }

    public function Status()
    {
        return $this->belongsTo('App\Status');
    }



    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        $query->select('shop_filters.*',
            'suppliers.name as supplier_name',
            'supplier_categories.name as supplier_category_name',
            'categories.name as category_name',
            'supplier_brands.name as supplier_brand_name',
            'brands.name as brand_name',
            'products.name as product_name'
        )
        ->leftJoin('suppliers', 'suppliers.id', '=', 'shop_filters.supplier_id')
        ->leftJoin('supplier_categories', 'supplier_categories.id', '=', 'shop_filters.supplier_category_id')
        ->leftJoin('categories', 'categories.id', '=', 'shop_filters.category_id')
        ->leftJoin('supplier_brands', 'supplier_brands.id', '=', 'shop_filters.supplier_brand_id')
        ->leftJoin('brands', 'brands.id', '=', 'shop_filters.brand_id')
        ->leftJoin('products', 'products.id', '=', 'shop_filters.product_id')
        ->orderBy('categories.name')
        ->orderBy('brands.name')
        ->orderBy('products.name')
        ->orderBy('suppliers.name');
      /*   ->groupBy('products.id')
        ->groupBy('images.id'); */

        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }


}
