<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Lang
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Lang newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Lang newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Lang query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Lang whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Lang whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Lang whereName($value)
 * @mixin \Eloquent
 */
class Lang extends Model
{
    protected $table = 'langs';

    public $timestamps = false;

    protected $fillable = [
        'code', 'name'
    ];


}
