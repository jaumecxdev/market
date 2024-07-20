<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\MarketAttribute
 *
 * @property int $id
 * @property int|null $market_id
 * @property int|null $market_category_id
 * @property int|null $type_id
 * @property string $name
 * @property string|null $code
 * @property string|null $datatype
 * @property int|null $required
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\AttributeMarketAttribute[] $attribute_market_attributes
 * @property-read int|null $attribute_market_attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Attribute[] $attributes
 * @property-read int|null $attributes_count
 * @property-read \App\Market|null $market
 * @property-read \App\MarketCategory|null $market_category
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Property[] $properties
 * @property-read int|null $properties_count
 * @property-read \App\Type|null $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute whereDatatype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute whereMarketCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketAttribute whereTypeId($value)
 * @mixin \Eloquent
 */
class MarketAttribute extends Model
{
    protected $table = 'market_attributes';

    public $timestamps = false;

    protected $fillable = [
        'market_id', 'market_category_id', 'type_id', 'name', 'code', 'datatype', 'required'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function market_category()
    {
        return $this->belongsTo('App\MarketCategory');
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


    public function properties($hasSkuField = null)
    {
        if (!$hasSkuField)
            return $this->hasMany('App\Property');

        return $this->hasMany('App\Property')->whereNotNull('sku_field');
    }


    // MANY TO MANY

    public function attributes()
    {
        return $this->belongsToMany('App\Attribute', 'attribute_market_attributes',
            'market_attribute_id', 'attribute_id')
            ->wherePivot('market_id', $this->market_id);
    }


}
