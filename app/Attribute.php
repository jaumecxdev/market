<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Attribute
 *
 * @property int $id
 * @property int|null $category_id
 * @property int|null $type_id
 * @property string $name
 * @property string|null $code
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\AttributeMarketAttribute[] $attribute_market_attributes
 * @property-read int|null $attribute_market_attributes_count
 * @property-read \App\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProductAttribute[] $product_attributes
 * @property-read int|null $product_attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @property-read int|null $products_count
 * @property-read \App\Type|null $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Attribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Attribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Attribute query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Attribute whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Attribute whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Attribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Attribute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Attribute whereTypeId($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketAttribute[] $market_attributes
 * @property-read int|null $market_attributes_count
 */
class Attribute extends Model
{
    protected $table = 'attributes';

    public $timestamps = false;

    protected $fillable = [
        'category_id', 'type_id', 'name', 'code'
    ];


    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function type()
    {
        return $this->belongsTo('App\Type');
    }


    // MANY

    public function attribute_market_attributes()
    {
        return $this->hasMany('App\AttributeMarketAttribute');
    }

    public function product_attributes()
    {
        return $this->hasMany('App\ProductAttribute');
    }


    // MANY TO MANY

    public function market_attributes()
    {
        return $this->belongsToMany('App\MarketAttribute', 'attribute_market_attributes',
            'attribute_id', 'market_attribute_id');
    }

    public function products()
    {
        return $this->belongsToMany('App\Product', 'product_attributes',
            'attribute_id', 'product_id');
    }


    // CUSTOM


    public function market_category(Market $market)
    {
        return $this->category->market_category($market->id)->first() ?? null;
    }


}
