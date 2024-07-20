<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ProviderCategory
 *
 * @property int $id
 * @property int|null $provider_id
 * @property string|null $categoryId
 * @property string|null $categoryL1
 * @property string|null $categoryL2
 * @property string|null $categoryL3
 * @property string|null $categoryL4
 * @property string|null $categoryL5
 * @property string|null $name
 * @property int|null $display_order
 * @property int $enabled
 * @property-read \App\Provider|null $provider
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereCategoryL1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereCategoryL2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereCategoryL3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereCategoryL4($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereCategoryL5($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProviderCategory whereProviderId($value)
 * @mixin \Eloquent
 */
class ProviderCategory extends Model
{
    protected $table = 'provider_categories';

    public $timestamps = false;

    protected $fillable = [
        'provider_id',
        'categoryId', 'categoryL1', 'categoryL2', 'categoryL3', 'categoryL4', 'categoryL5',
        'name', 'display_order', 'enabled'
    ];


    public function provider()
    {
        return $this->belongsTo('App\Provider');
    }

}
