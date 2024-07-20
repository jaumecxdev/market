<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\ShopJob
 *
 * @property int $id
 * @property int|null $shop_id
 * @property string $jobId
 * @property string|null $operation
 * @property int|null $total_count
 * @property int|null $success_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Shop|null $shop
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob whereOperation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob whereSuccessCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob whereTotalCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\ShopJob whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ShopJob extends Model
{
    protected $table = 'shop_jobs';

    protected $fillable = [
        'shop_id', 'jobId', 'operation', 'total_count', 'success_count'
    ];


    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }

}
