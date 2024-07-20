<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\MarketCategory
 *
 * @property int $id
 * @property int|null $parent_id
 * @property int|null $market_id
 * @property int|null $root_category_id
 * @property string|null $marketCategoryId
 * @property string $name
 * @property string|null $path
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Category[] $categories
 * @property-read int|null $categories_count
 * @property-read \App\MarketCategory|null $childs
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketAttribute[] $market_attributes
 * @property-read int|null $market_attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketFee[] $market_fees
 * @property-read int|null $market_fees_count
 * @property-read \App\MarketCategory $parent
 * @property-read \App\RootCategory|null $root_category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopFilter[] $shop_filters
 * @property-read int|null $shop_filters_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopGroup[] $shop_groups
 * @property-read int|null $shop_groups_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopProduct[] $shop_products
 * @property-read int|null $shop_products_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory whereMarketCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketCategory whereRootCategoryId($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketParam[] $market_params
 * @property-read int|null $market_params_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\CategoryMarketCategory[] $category_market_categories
 * @property-read int|null $category_market_categories_count
 * @property string|null $type
 * @method static \Illuminate\Database\Eloquent\Builder|MarketCategory whereType($value)
 */
class MarketCategory extends Model
{
    protected $table = 'market_categories';

    public $timestamps = false;

    protected $fillable = [
        'parent_id', 'market_id', 'root_category_id', 'marketCategoryId', 'name', 'path', 'type'
    ];


    public function parent()
    {
        return $this->belongsTo('App\MarketCategory', 'parent_id', 'id');
    }

    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function root_category()
    {
        return $this->belongsTo('App\RootCategory');
    }


    // MANY


    public function childs()
    {
        return $this->belongsTo('App\MarketCategory', 'parent_id', 'id');
    }

    public function market_attributes($type_name = null)
    {
        if (!$type_name)
            return $this->hasMany('App\MarketAttribute');

        $type = Type::where('type', 'market_attribute')
            ->where('market_id', $this->market_id)
            ->where('name', $type_name)
            ->first();

        if (!isset($type)) $type_id = null;
        else $type_id = $type->id;

        return $this->hasMany('App\MarketAttribute')->where('type_id', $type_id);
    }

    public function market_params()
    {
        return $this->hasMany('App\MarketParam');
    }

    public function shop_filters()
    {
        return $this->hasMany('App\ShopFilter');
    }

    public function shop_groups()
    {
        return $this->hasMany('App\ShopGroup');
    }

    public function shop_products()
    {
        return $this->hasMany('App\ShopProduct');
    }


    // MANY TO MANY

    public function categories()
    {
        return $this->belongsToMany('App\Category');
    }

    public function category_market_categories()
    {
        return $this->belongsToMany('App\CategoryMarketCategory');
    }

    public function supplier_categories()
    {
        return $this->belongsToMany(SupplierCategory::class/* , 'supplier_category_market_category' */)->withPivot('supplier_id', 'market_id');
    }


    // CUSTOM

    /*public function getMarketAttributes($type_name = null)
    {
        $market_attributes = $this->market_attributes();

        if ($type_name) {
            $type = Type::where('type', 'market_attribute')
                ->where('market_id', $this->market_id)
                ->where('name', $type_name)
                ->first();

            $market_attributes = $market_attributes->where('type_id', $type->id);
        }

        return $market_attributes->get();
    }*/

}
