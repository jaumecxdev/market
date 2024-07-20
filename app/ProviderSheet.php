<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProviderSheet
 *
 * @property int $id
 * @property string|null $sku
 * @property string|null $ean
 * @property string|null $pn
 * @property string|null $brand
 * @property int $available
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet whereAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet whereEan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet wherePn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderSheet whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProviderSheet extends Model
{
    protected $table = 'provider_sheets';

    protected $fillable = [
        'sku', 'ean', 'pn', 'brand', 'available'
    ];

}
