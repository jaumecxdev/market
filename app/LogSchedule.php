<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\LogSchedule
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $info
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder|LogSchedule filter($params)
 * @method static Builder|LogSchedule newModelQuery()
 * @method static Builder|LogSchedule newQuery()
 * @method static Builder|LogSchedule query()
 * @method static Builder|LogSchedule whereCreatedAt($value)
 * @method static Builder|LogSchedule whereEndsAt($value)
 * @method static Builder|LogSchedule whereId($value)
 * @method static Builder|LogSchedule whereInfo($value)
 * @method static Builder|LogSchedule whereName($value)
 * @method static Builder|LogSchedule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LogSchedule extends Model
{
    protected $table = 'log_schedules';

    protected $dates = [
        'ends_at'
    ];

    protected $fillable = [
        'supplier_id', 'market_id', 'shop_id',
        'type', 'name', 'info', 'ends_at'
    ];


    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        $query->select('log_schedules.*');

        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }

}
