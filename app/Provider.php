<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Provider
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Provider newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Provider newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Provider query()
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopProduct[] $shop_products
 * @property-read int|null $shop_products_count
 * @property int $id
 * @property string|null $name
 * @method static \Illuminate\Database\Eloquent\Builder|Provider whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Provider whereName($value)
 */
class Provider extends Model
{
    protected $table = 'providers';

    public $timestamps = false;

    protected $fillable = [
        'name'
    ];



    // MANY

    public function products()
    {
        return $this->hasMany('App\Product');
    }

    public function shop_products()
    {
        return $this->hasMany('App\ShopProduct');
    }


}
