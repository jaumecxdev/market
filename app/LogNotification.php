<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\LogNotification
 *
 * @property int $id
 * @property int|null $supplier_id
 * @property string|null $class
 * @property string|null $name
 * @property string|null $target
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogNotification whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $type
 * @property int|null $type_id
 * @property string|null $item
 * @method static Builder|LogNotification filter($params)
 * @method static Builder|LogNotification whereItem($value)
 * @method static Builder|LogNotification whereType($value)
 * @method static Builder|LogNotification whereTypeId($value)
 */
class LogNotification extends Model
{
    protected $table = 'log_notifications';

    protected $fillable = [
        'supplier_id', 'class', 'name', 'target', 'type', 'type_id', 'item'
    ];


    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }


    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        $query->select(['log_notifications.*', 'suppliers.name as supplier_name'])
            ->leftJoin('suppliers', 'log_notifications.supplier_id', '=', 'suppliers.id');

        if ( isset($params['supplier_id']) && $params['supplier_id'] != null) {
            $query->where('log_notifications.supplier_id', $params['supplier_id']);
        }

        if ( isset($params['class']) && $params['class'] != null) {
            $query->where('log_notifications.class', $params['class']);
        }

        if ( isset($params['name']) && $params['name'] != null) {
            $query->where('log_notifications.name', $params['name']);
        }

        if ( isset($params['target']) && $params['target'] != null) {
            $query->where('log_notifications.target', $params['target']);
        }

        if ( isset($params['type']) && $params['type'] != null) {
            $query->where('log_notifications.type', $params['type']);
        }

        if ( isset($params['type_id']) && $params['type_id'] != null) {
            $query->where('log_notifications.type_id', $params['type_id']);
        }

        if ( isset($params['item']) && $params['item'] != null) {
            $query->where('log_notifications.item', $params['item']);
        }


        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }


}
