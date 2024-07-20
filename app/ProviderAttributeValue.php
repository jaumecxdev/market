<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProviderAttributeValue
 *
 * @property int $id
 * @property int|null $provider_id
 * @property int|null $provider_category_id
 * @property int|null $provider_attribute_id
 * @property string|null $valueId
 * @property string|null $valueName
 * @property string|null $name
 * @property int|null $display_order
 * @property int $enabled
 * @property-read \App\Provider|null $provider
 * @property-read \App\ProviderAttribute|null $provider_attribute
 * @property-read \App\ProviderCategory|null $provider_category
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue whereProviderAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue whereProviderCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue whereValueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderAttributeValue whereValueName($value)
 * @mixin \Eloquent
 */
class ProviderAttributeValue extends Model
{
    protected $table = 'provider_attribute_values';

    public $timestamps = false;

    protected $fillable = [
        'provider_id', 'provider_category_id', 'provider_attribute_id',
        'valueId', 'valueName', 'name', 'display_order', 'enabled'
    ];


    public function provider()
    {
        return $this->belongsTo('App\Provider');
    }

    public function provider_category()
    {
        return $this->belongsTo('App\ProviderCategory');
    }

    public function provider_attribute()
    {
        return $this->belongsTo('App\ProviderAttribute');
    }

}
