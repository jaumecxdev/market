<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\AttributeMarketAttribute
 *
 * @property int $id
 * @property int|null $market_id
 * @property int|null $attribute_id
 * @property int|null $market_attribute_id
 * @property int|null $property_id
 * @property int|null $fixed
 * @property string|null $fixed_value
 * @property string|null $pattern
 * @property string|null $mapping
 * @property string|null $if_exists
 * @property string|null $if_exists_value
 * @property-read \App\Attribute|null $attribute
 * @property-read \App\Market|null $market
 * @property-read \App\MarketAttribute|null $market_attribute
 * @property-read \App\Property|null $property
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereFixed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereFixedValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereIfExists($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereIfExistsValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereMapping($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereMarketAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute wherePattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute wherePropertyId($value)
 * @mixin \Eloquent
 * @property string|null $field
 * @method static \Illuminate\Database\Eloquent\Builder|\App\AttributeMarketAttribute whereField($value)
 */
class AttributeMarketAttribute extends Model
{
    protected $table = 'attribute_market_attributes';

    public $timestamps = false;

    protected $fillable = [
        'market_id', 'attribute_id', 'market_attribute_id', 'property_id', 'field', 'fixed', 'fixed_value', 'pattern', 'mapping',
        'if_exists', 'if_exists_value'
    ];



    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function attribute()
    {
        return $this->belongsTo('App\Attribute');
    }

    public function market_attribute()
    {
        return $this->belongsTo('App\MarketAttribute');
    }

    public function property()
    {
        return $this->belongsTo('App\Property');
    }


}
