<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * App\Property
 *
 * @property int $id
 * @property int|null $market_attribute_id
 * @property string|null $name
 * @property string|null $datatype
 * @property int|null $required
 * @property int|null $custom
 * @property string|null $custom_value
 * @property string|null $custom_value_field
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\AttributeMarketAttribute[] $attribute_market_attributes
 * @property-read int|null $attribute_market_attributes_count
 * @property-read \App\MarketAttribute|null $market_attribute
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\PropertyValue[] $property_values
 * @property-read int|null $property_values_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property filter(\App\Market $market, $params)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property whereCustom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property whereCustomValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property whereCustomValueField($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property whereDatatype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property whereMarketAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Property whereRequired($value)
 * @mixin \Eloquent
 */
class Property extends Model
{
    protected $table = 'properties';

    public $timestamps = false;

    protected $fillable = [
        'market_attribute_id', 'name', 'datatype', 'required', 'custom', 'custom_value', 'custom_value_field'
    ];


    public function market_attribute()
    {
        return $this->belongsTo('App\MarketAttribute');
    }


    // MANY

    public function attribute_market_attributes()
    {
        return $this->hasMany('App\AttributeMarketAttribute');
    }


    public function attribute_market_attributes_Extended()
    {
        return AttributeMarketAttribute::select('attribute_market_attributes.*',
            'attributes.name as attribute_name',
            'categories.name as category_name')
            ->leftJoin('attributes', 'attribute_market_attributes.attribute_id', '=', 'attributes.id')
            ->leftJoin('categories', 'attributes.category_id', '=', 'categories.id')
            ->where('attribute_market_attributes.property_id', $this->id)->get();
    }


    public function property_values()
    {
        return $this->hasMany('App\PropertyValue');
    }


    // SCOPES


    public function scopeFilter(Builder $query, Market $market, $params)
    {
        $query->select('properties.*',
            'market_attributes.id as market_attribute_id',
            'types.name as type_name',
            DB::raw("CONCAT(market_categories.id, ' (', market_categories.name, ')') AS market_category_string"),
            'market_attributes.id AS market_attribute_id',
            'market_attributes.name AS market_attribute_name',
            DB::raw("CONCAT('(', market_categories.name, ') ', market_attributes.name) AS market_category_attribute_name")
        )
            ->leftJoin('market_attributes', 'properties.market_attribute_id', '=', 'market_attributes.id')
            ->leftJoin('market_categories', 'market_attributes.market_category_id', '=', 'market_categories.id')
            ->leftJoin('types', 'market_attributes.type_id', '=', 'types.id')
            ->orderBy('market_categories.name', 'asc')
            ->orderBy('market_attributes.name', 'asc')
            ->where('market_attributes.market_id', $market->id)
            ->groupBy('market_attributes.id','properties.id');

        if (!isset($params['market_category_name'])) $params['market_category_id'] = null;
        if (isset($params['market_category_id']) && $params['market_category_id'] != null) {
            $query->where('market_categories.id', $params['market_category_id']);
        }

        if ( isset($params['market_attribute_name']) && $params['market_attribute_name'] != null) {
            $query->where('market_attributes.name', 'LIKE', '%' . $params['market_attribute_name'] . '%');
        }

        return $query;
    }

}
