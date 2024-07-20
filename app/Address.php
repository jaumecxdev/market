<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Address
 *
 * @property int $id
 * @property int|null $country_id
 * @property int $fixed
 * @property string|null $name
 * @property string $address1
 * @property string|null $address2
 * @property string|null $address3
 * @property string|null $city
 * @property string|null $municipality
 * @property string|null $district
 * @property string|null $state
 * @property string|null $zipcode
 * @property string|null $phone
 * @property int|null $market_id
 * @property string|null $marketBuyerId
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Buyer[] $billing_buyers
 * @property-read int|null $billing_buyers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $billing_orders
 * @property-read int|null $billing_orders_count
 * @property-read \App\Country|null $country
 * @property-read \App\Market|null $market
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Buyer[] $shipping_buyers
 * @property-read int|null $shipping_buyers_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Order[] $shipping_orders
 * @property-read int|null $shipping_orders_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereAddress1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereAddress2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereAddress3($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereDistrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereFixed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereMarketBuyerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereMunicipality($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereState($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Address whereZipcode($value)
 */
class Address extends Model
{
    protected $table = 'addresses';

    public $timestamps = false;

    protected $fillable = [
        'country_id', 'market_id', 'marketBuyerId', 'fixed', 'name', 'address1', 'address2', 'address3', 'city',
        'municipality', 'district', 'state', 'zipcode', 'phone'
    ];


    public function country()
    {
        return $this->belongsTo('App\Country');
    }

    public function market()
    {
        return $this->belongsTo('App\Market');
    }


    // MANY

    public function billing_buyers()
    {
        return $this->hasMany('App\Buyer', 'billing_address_id', 'id');
    }

    public function shipping_buyers()
    {
        return $this->hasMany('App\Buyer', 'shipping_address_id', 'id');
    }

    public function billing_orders()
    {
        return $this->hasMany('App\Order', 'billing_address_id', 'id');
    }

    public function shipping_orders()
    {
        return $this->hasMany('App\Order', 'shipping_address_id', 'id');
    }


    // CUSTOM

    public function getHMTL()
    {
        $html = $this->name."<br>";
        $html .= $this->address1."<br>";
        $html .= isset($this->address2) ? ($this->address2."<br>") : '';
        $html .= isset($this->address3) ? ($this->address3."<br>") : '';
        $html .= $this->zipcode." ".$this->city."<br>";
        $html .= $this->state."<br>";
        $html .= $this->phone."<br>";

        return $html;
    }

}
