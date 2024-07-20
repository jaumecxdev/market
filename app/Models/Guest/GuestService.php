<?php

namespace App\Models\Guest;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Guest\GuestService
 *
 * @property int $id
 * @property int|null $supplier_id
 * @property int|null $shop_id
 * @property string|null $name
 * @property string|null $type
 * @property string|null $class
 * @property string|null $params
 * @property string $token
 * @property string|null $refresh
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Shop|null $shop
 * @property-read \App\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService query()
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereRefresh($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereShopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GuestService whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GuestService extends Model
{
    protected $table = 'guest_services';

    protected $fillable = [
        'supplier_id', 'shop_id', 'name', 'type', 'class', 'params', 'token', 'refresh'
    ];


    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }


    public function shop()
    {
        return $this->belongsTo('App\Shop');
    }


}
