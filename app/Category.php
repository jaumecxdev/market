<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * App\Category
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $path
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Attribute[] $attributes
 * @property-read int|null $attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Category[] $childs
 * @property-read int|null $childs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketCategory[] $market_categories
 * @property-read int|null $market_categories_count
 * @property-read \App\Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierFilter[] $supplier_filters
 * @property-read int|null $supplier_filters_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category wherePath($value)
 * @mixin \Eloquent
 * @property string|null $seo_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierCategory[] $supplier_categories
 * @property-read int|null $supplier_categories_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category filter($params)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereSeoName($value)
 * @property string|null $code
 * @property string|null $parent_code
 * @property int|null $level
 * @property int $leaf
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereLeaf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Category whereParentCode($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierParam[] $supplier_params
 * @property-read int|null $supplier_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\FeeParam[] $fee_params
 * @property-read int|null $fee_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopParam[] $shop_params
 * @property-read int|null $shop_params_count
 */
class Category extends Model
{
    protected $table = 'categories';

    public $timestamps = false;

    protected $fillable = [
        'parent_id', 'name', 'seo_name', 'path', 'code', 'parent_code', 'level', 'leaf'
    ];


    public function parent()
    {
        return $this->belongsTo('App\Category', 'parent_id', 'id');
    }


    // MANY


    public function attributes()
    {
        return $this->hasMany('App\Attribute');
    }

    public function childs()
    {
        return $this->hasMany('App\Category', 'parent_id', 'id');
    }

    public function products()
    {
        return $this->hasMany('App\Product');
    }

    public function shop_params()
    {
        return $this->hasMany('App\ShopParam');
    }

    public function supplier_categories()
    {
        return $this->hasMany('App\SupplierCategory');
    }

    public function supplier_filters()
    {
        return $this->hasMany('App\SupplierFilter');
    }

    public function supplier_params()
    {
        return $this->hasMany('App\SupplierParam');
    }



    // MANY TO MANY

    public function market_categories()
    {
        return $this->belongsToMany('App\MarketCategory');
    }


    public function market_category($market_id = null)
    {
        return $this->belongsToMany('App\MarketCategory')->where('market_id', $market_id);
    }


    // CUSTOM

    public function firstOrCreateCanon($canon, $locale = 'es')
    {
        return true;

        try {
            if (!in_array($this->id, CategoryCanon::EXCLUDED_CATEGORIES))
                return CategoryCanon::firstOrCreate([
                    'category_id'   => $this->id,
                    'locale'        => $locale,
                ],[
                    'canon'         => $canon,
                ]);

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$canon, $locale]);
        }
    }


    public function getCanon($locale = 'es')
    {
        try {
            if ($category_canon = CategoryCanon::whereLocale($locale)->whereCategoryId($this->id)->first())
                return $category_canon->canon;

            return 0;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $locale);
        }
    }


    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        // 'parent_id', 'name', 'seo_name', 'path', 'code', 'parent_code', 'level', 'leaf'
        $query->select('categories.*'
            //'supplier_categories.name as supplier_categories_name'
            //DB::raw('count(shop_products.id) as shop_products_count')
        )
        ->where('id', '>', '71');       // NO old categories
            // ONLY MAPED CATEGORIES
            //->join('supplier_categories', 'supplier_categories.category_id', '=', 'categories.id');

            //->groupBy('categories.id');
            //->leftJoin('products', 'products.category_id', '=', 'categories.id')

        /*if (!isset($params['supplier_category_name'])) $params['supplier_category_id'] = null;
        if ( isset($params['supplier_category_id']) && $params['supplier_category_id'] != null) {
            $query->where('supplier_categories.id', $params['supplier_category_id']);
        }*/

        if ( isset($params['parent_id']) && $params['parent_id'] != null) {
            $query->where('categories.parent_id', $params['parent_id']);
        }

        if (!isset($params['category_name'])) $params['category_id'] = null;
        if ( isset($params['category_id']) && $params['category_id'] != null) {
            $query->where('categories.id', $params['category_id']);
        }

        if ( isset($params['code']) && $params['code'] != null) {
            $query->where('categories.code', $params['code'] );
        }

        if ( isset($params['parent_code']) && $params['parent_code'] != null) {
            $query->where('categories.parent_code', $params['parent_code']);
        }

        if ( isset($params['level']) && $params['level'] != null) {
            $query->where('categories.level', $params['level']);
        }

        if ( isset($params['leaf']) && $params['leaf'] != null) {
            $query->where('categories.leaf', $params['leaf']);
        }

        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }

}
