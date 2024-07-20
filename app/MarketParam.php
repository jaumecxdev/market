<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\MarketParam
 *
 * @property-read \App\Brand $brand
 * @property-read \App\Market $market
 * @property-read \App\MarketCategory $market_category
 * @property-read \App\RootCategory $root_category
 * @property-read \App\ShopProduct $shop_product
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam query()
 * @mixin \Eloquent
 * @property int $id
 * @property int|null $market_id
 * @property int|null $brand_id
 * @property int|null $market_category_id
 * @property int|null $root_category_id
 * @property float $fee
 * @property float $fee_addon
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam whereFeeAddon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam whereMarketCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam whereMarketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\MarketParam whereRootCategoryId($value)
 * @property float $lot
 * @property float $lot_fee
 * @property float $bfit_min
 * @method static \Illuminate\Database\Eloquent\Builder|MarketParam whereBfitMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketParam whereLot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MarketParam whereLotFee($value)
 */
class MarketParam extends Model
{
    protected $table = 'market_params';

    public $timestamps = false;

    // fee: Tarifa normal MP
    // fee_addon: Afegit en € a la tarifa normal. PayPal +0,35 €
    // lot: Tram de diferents tarifes. Amazon: 100 €
    // lot_fee: Tarifa del Tram superior, normalment mes baixa. Amazon 8,24 %
    //          Si PVP <= 100€ -> lot=0, lot_fee = tarifa normal o formula v1.0
    // bfit_min: Mínim bci pel MP. Amazon 0,30 €
    protected $fillable = [
        'market_id', 'brand_id', 'market_category_id', 'root_category_id',
        'fee', 'fee_addon', 'lot', 'lot_fee', 'bfit_min'
    ];


    public function market()
    {
        return $this->belongsTo('App\Market');
    }

    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

    public function market_category()
    {
        return $this->belongsTo('App\MarketCategory');
    }

    public function root_category()
    {
        return $this->belongsTo('App\RootCategory');
    }

}
