<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\SupplierCategory
 *
 * @property-read \App\Category $category
 * @property-read \App\Supplier $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierCategory filter($params)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierCategory query()
 * @mixin \Eloquent
 * @property int $id
 * @property int|null $supplier_id
 * @property int|null $category_id
 * @property string $name
 * @property string|null $supplierCategoryId
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierCategory whereSupplierCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierCategory whereSupplierId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @property-read int|null $products_count
 */
class SupplierCategory extends Model
{
    protected $table = 'supplier_categories';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id', 'category_id', 'name', 'supplierCategoryId'
    ];


    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }


    public function category()
    {
        return $this->belongsTo('App\Category');
    }


    // MANY

    public function products()
    {
        return $this->hasMany('App\Product');
    }


    // MANY TO MANY

    public function market_categories()
    {
        return $this->belongsToMany(MarketCategory::class, 'supplier_category_market_category')->withPivot('supplier_id', 'market_id');
    }


    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        $query->select('supplier_categories.*',
            'suppliers.name as supplier_name',
            'categories.name as category_name',
            DB::raw('count(products.id) as products_count')
        )
            ->leftJoin('suppliers', 'supplier_categories.supplier_id', '=', 'suppliers.id')
            ->leftJoin('categories', 'supplier_categories.category_id', '=', 'categories.id')
            ->leftJoin('products', 'supplier_categories.id', '=', 'products.supplier_category_id');

        if (isset($params['supplier_id']) && $params['supplier_id'] != null) {
            $query->where('supplier_categories.supplier_id', $params['supplier_id']);
        }

        if (!isset($params['supplier_category_name'])) $params['supplier_category_id'] = null;
        if (isset($params['supplier_category_id']) && $params['supplier_category_id'] != null) {
            $query->where('supplier_categories.id', $params['supplier_category_id']);
        }

        if (!isset($params['category_name'])) $params['category_id'] = null;
        if (isset($params['category_id']) && $params['category_id'] != null) {
            $query->where('supplier_categories.category_id', $params['category_id']);
        }

        if (isset($params['supplierCategoryId']) && $params['supplierCategoryId'] != null) {
            $query->where('supplier_categories.supplierCategoryId', $params['supplierCategoryId']);
        }

        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        $query->groupBy('supplier_categories.id');

        return $query;
    }


}
