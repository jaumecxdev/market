<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\CategoryMarketCategory
 *
 * @property int $id
 * @property int|null $category_id
 * @property int|null $market_category_id
 * @property-read \App\Category|null $category
 * @property-read \App\MarketCategory|null $market_category
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CategoryMarketCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CategoryMarketCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CategoryMarketCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CategoryMarketCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CategoryMarketCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\CategoryMarketCategory whereMarketCategoryId($value)
 * @mixin \Eloquent
 */
class CategoryMarketCategory extends Model
{
    protected $table = 'category_market_category';

    public $timestamps = false;

    protected $fillable = [
        'category_id', 'market_category_id'
    ];


    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function market_category()
    {
        return $this->belongsTo('App\MarketCategory');
    }


}
