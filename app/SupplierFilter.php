<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\SupplierFilter
 *
 * @property int $id
 * @property int|null $supplier_id
 * @property string|null $brand_name
 * @property string|null $category_name
 * @property string|null $type_name
 * @property string|null $status_name
 * @property string|null $name
 * @property string|null $model
 * @property string|null $supplierSku
 * @property string|null $pn
 * @property string|null $ean
 * @property string|null $upc
 * @property string|null $isbn
 * @property float|null $cost_min
 * @property float|null $cost_max
 * @property int|null $stock_min
 * @property int|null $stock_max
 * @property string|null $field_name
 * @property string|null $field_string
 * @property int|null $field_integer
 * @property float|null $field_float
 * @property int|null $limit_products
 * @property-read \App\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereBrandName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereCostMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereCostMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereEan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereFieldFloat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereFieldInteger($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereFieldName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereFieldString($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereIsbn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereLimitProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter wherePn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereStatusName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereStockMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereStockMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereSupplierSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereTypeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereUpc($value)
 * @mixin \Eloquent
 * @property string|null $field_operator
 * @method static \Illuminate\Database\Eloquent\Builder|\App\SupplierFilter whereFieldOperator($value)
 */
class SupplierFilter extends Model
{
    protected $table = 'supplier_filters';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id', 'status_name', 'brand_name', 'category_name', // NOT USE: 'type_name',
        'supplierSku', 'name', 'pn', 'ean', 'upc', 'isbn', 'model',
        'cost_min', 'cost_max', 'stock_min', 'stock_max',
        'field_name', 'field_operator', 'field_string', 'field_integer', 'field_float',
        'limit_products'
    ];

    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

}
