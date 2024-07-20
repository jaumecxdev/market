<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProviderUpdate
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderUpdate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderUpdate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderUpdate query()
 * @mixin \Eloquent
 * @property int $id
 * @property int|null $shop_id
 * @property int $products
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Shop|null $shop
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderUpdate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderUpdate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderUpdate whereProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderUpdate whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderUpdate whereUpdatedAt($value)
 */
class ProviderUpdate extends Model
{
    protected $table = 'provider_updates';

    protected $fillable = [
        'shop_id', 'products'
    ];


    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }
}
