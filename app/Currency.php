<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Currency
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\OrderItem[] $order_items
 * @property-read int|null $order_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read int|null $orders_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Currency whereName($value)
 * @mixin \Eloquent
 */
class Currency extends Model
{
    protected $table = 'currencies';

    public $timestamps = false;

    protected $fillable = [
        'code', 'name'
    ];


    // MANY

    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    public function order_items()
    {
        return $this->hasMany('App\OrderItem');
    }
}
