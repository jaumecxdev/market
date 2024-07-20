<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\SupplierParam
 *
 * @property int $id
 * @property int|null $supplier_id
 * @property int|null $brand_id
 * @property int|null $category_id
 * @property string|null $supplierSku
 * @property float $canon
 * @property float $rappel
 * @property float $ports
 * @property-read \App\Brand|null $brand
 * @property-read \App\Category|null $category
 * @property-read \App\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereCanon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam wherePorts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereRappel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierParam whereSupplierSku($value)
 * @mixin \Eloquent
 * @property float|null $cost_min
 * @property float|null $cost_max
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property float $price
 * @property float $discount_price
 * @property int $stock
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierParam whereCostMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierParam whereCostMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierParam whereDiscountPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierParam whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierParam wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierParam whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SupplierParam whereStock($value)
 */
class SupplierParam extends Model
{
    protected $table = 'supplier_params';

    public $timestamps = false;

    protected $dates = [
        'starts_at',
        'ends_at',
    ];

    protected $fillable = [
        'supplier_id', 'brand_id', 'category_id', 'product_id',
        'supplierSku', 'pn', 'ean', 'upc', 'isbn', 'gtin',
        'cost_min', 'cost_max', 'starts_at', 'ends_at',
        /* 'canon', */ 'rappel', 'ports',
        'price', 'discount_price', 'stock'
    ];


    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function product()
    {
        return $this->belongsTo('App\Product');
    }

}
