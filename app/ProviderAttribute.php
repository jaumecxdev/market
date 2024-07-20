<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProviderAttribute
 *
 * @property int $id
 * @property int|null $provider_id
 * @property int|null $provider_category_id
 * @property string|null $attributeId
 * @property string|null $attributeName
 * @property string|null $name
 * @property int|null $display_order
 * @property int $enabled
 * @property-read \App\Provider|null $provider
 * @property-read \App\ProviderCategory|null $provider_category
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute whereAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute whereAttributeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute whereProviderCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttribute whereProviderId($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ProviderAttributeValue[] $provider_attribute_values
 * @property-read int|null $provider_attribute_values_count
 */
class ProviderAttribute extends Model
{
    protected $table = 'provider_attributes';

    public $timestamps = false;

    protected $fillable = [
        'provider_id', 'provider_category_id',
        'attributeId', 'attributeName', 'name', 'display_order', 'enabled'
    ];


    public function provider()
    {
        return $this->belongsTo('App\Provider');
    }

    public function provider_category()
    {
        return $this->belongsTo('App\ProviderCategory');
    }

    // MANY

    public function provider_attribute_values()
    {
        return $this->hasMany('App\ProviderAttributeValue');
    }


}
