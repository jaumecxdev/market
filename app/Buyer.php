<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Buyer
 *
 * @property int $id
 * @property int|null $shipping_address_id
 * @property int|null $billing_address_id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $company_name
 * @property string|null $tax_region
 * @property string|null $tax_name
 * @property string|null $tax_value
 * @property int|null $market_id
 * @property string|null $marketBuyerId
 * @property-read \App\Address|null $billing_address
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \App\Address|null $shipping_address
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereBillingAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereMarketBuyerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereShippingAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereTaxName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereTaxRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Buyer whereTaxValue($value)
 * @mixin \Eloquent
 */
class Buyer extends Model
{
    protected $table = 'buyers';

    public $timestamps = false;

    protected $fillable = [
        'shipping_address_id', 'billing_address_id', 'market_id', 'marketBuyerId', 'name', 'email', 'phone',
        'company_name', 'tax_region', 'tax_name', 'tax_value'
    ];


    public function shipping_address()
    {
        return $this->belongsTo('App\Address');
    }

    public function billing_address()
    {
        return $this->belongsTo('App\Address');
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
}
