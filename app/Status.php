<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Status
 *
 * @property int $id
 * @property int|null $supplier_id
 * @property int|null $market_id
 * @property string $name
 * @property string|null $supplierStatusName
 * @property string|null $marketStatusName
 * @property string|null $type
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\ShopFilter[] $shop_filters
 * @property-read int|null $shop_filters_count
 * @property-read \App\Supplier|null $supplier
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\SupplierFilter[] $supplier_filters
 * @property-read int|null $supplier_filters_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status whereMarketStatusName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status whereSupplierStatusName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Status whereType($value)
 * @mixin \Eloquent
 */
class Status extends Model
{
    protected $table = 'statuses';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id', 'market_id', 'name', 'supplierStatusName', 'marketStatusName', 'type'
    ];


    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function market()
    {
        return $this->belongsTo('App\Market');
    }


    // MANY

    public function orders()
    {
        return $this->hasMany('App\Order');
    }

    public function products()
    {
        return $this->hasMany('App\Product');
    }

    public function shop_filters()
    {
        return $this->hasMany('App\ShopFilter');
    }

    public function supplier_filters()
    {
        return $this->hasMany('App\SupplierFilter');
    }

}
