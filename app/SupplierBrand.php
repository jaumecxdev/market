<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\SupplierBrand
 *
 * @property int $id
 * @property int|null $supplier_id
 * @property string $name
 * @property string|null $supplierBrandId
 * @property-read \App\Brand $brand
 * @property-read \App\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierBrand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierBrand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierBrand query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierBrand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierBrand whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierBrand whereSupplierBrandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierBrand whereSupplierId($value)
 * @mixin \Eloquent
 */
class SupplierBrand extends Model
{
    protected $table = 'supplier_brands';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id', 'name', 'supplierBrandId'
    ];


    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }


    public function brand()
    {
        return $this->belongsTo('App\Brand');
    }

}
