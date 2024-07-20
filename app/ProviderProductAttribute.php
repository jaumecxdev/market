<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProviderProductAttribute
 *
 * @property int $id
 * @property int|null $provider_id
 * @property int|null $product_id
 * @property int|null $provider_attribute_value_id
 * @property-read \App\Product|null $product
 * @property-read \App\Provider|null $provider
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderProductAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderProductAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderProductAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderProductAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderProductAttribute whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderProductAttribute whereProviderAttributeValueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderProductAttribute whereProviderId($value)
 * @mixin \Eloquent
 * @property-read \App\ProviderAttributeValue|null $provider_attribute_value
 * @property int|null $provider_attribute_id
 * @property-read \App\ProviderAttribute|null $provider_attribute
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderProductAttribute whereProviderAttributeId($value)
 */
class ProviderProductAttribute extends Model
{
    protected $table = 'provider_product_attributes';

    public $timestamps = false;

    protected $fillable = [
        'provider_id', 'product_id', 'provider_attribute_id', 'provider_attribute_value_id'
    ];


    public function provider()
    {
        return $this->belongsTo('App\Provider');
    }

    public function product()
    {
        return $this->belongsTo('App\Product');
    }

    public function provider_attribute()
    {
        return $this->belongsTo('App\ProviderAttribute');
    }

    public function provider_attribute_value()
    {
        return $this->belongsTo('App\ProviderAttributeValue');
    }

}
