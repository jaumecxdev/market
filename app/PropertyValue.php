<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\PropertyValue
 *
 * @property int $id
 * @property int|null $property_id
 * @property string|null $name
 * @property string|null $value
 * @property-read \App\Property|null $property
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PropertyValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PropertyValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PropertyValue query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PropertyValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PropertyValue whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PropertyValue wherePropertyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\PropertyValue whereValue($value)
 * @mixin \Eloquent
 */
class PropertyValue extends Model
{
    protected $table = 'property_values';

    public $timestamps = false;

    protected $fillable = [
        'property_id', 'name', 'value'
    ];


    public function property()
    {
        return $this->belongsTo('App\Property');
    }
}
