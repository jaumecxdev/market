<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProductAttribute
 *
 * @property int $id
 * @property int|null $product_id
 * @property int|null $attribute_id
 * @property string|null $name
 * @property string|null $value
 * @property-read \App\Attribute|null $attribute
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketAttribute[] $market_attribute
 * @property-read int|null $market_attribute_count
 * @property-read \App\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute whereAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ProductAttribute whereValue($value)
 * @mixin \Eloquent
 */
class ProductAttribute extends Model
{
    protected $table = 'product_attributes';

    public $timestamps = false;

    protected $fillable = [
        'product_id', 'attribute_id', 'name', 'value'
    ];


    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    public function attribute()
    {
        return $this->belongsTo('App\Attribute');
    }


    // MANY

    public function market_attribute()
    {
        return $this->hasMany('App\MarketAttribute');
    }

}
