<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\RootCategory
 *
 * @property int $id
 * @property int|null $market_id
 * @property string $name
 * @property string|null $marketCategoryId
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Category[] $categories
 * @property-read int|null $categories_count
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketCategory[] $market_categories
 * @property-read int|null $market_categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketFee[] $market_fees
 * @property-read int|null $market_fees_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RootCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RootCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RootCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RootCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RootCategory whereMarketCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RootCategory whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\RootCategory whereName($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\MarketParam[] $market_params
 * @property-read int|null $market_params_count
 */
class RootCategory extends Model
{
    protected $table = 'root_categories';

    public $timestamps = false;

    protected $fillable = [
        'market_id', 'name', 'marketCategoryId'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }


    // MANY

    public function categories()
    {
        return $this->hasMany('App\Category');
    }

    public function market_categories()
    {
        return $this->hasMany('App\MarketCategory');
    }

    public function market_params()
    {
        return $this->hasMany('App\MarketParam');
    }


}
