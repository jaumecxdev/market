<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Variable
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Variable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Variable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Variable query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Variable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Variable whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Variable whereValue($value)
 * @mixin \Eloquent
 */
class Variable extends Model
{
    protected $table = 'variables';

    public $timestamps = false;

    protected $fillable = [
        'key', 'value'
    ];
}
