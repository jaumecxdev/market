<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Dictionary
 *
 * @property int $id
 * @property string|null $es
 * @property string|null $en
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Dictionary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Dictionary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Dictionary query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Dictionary whereEn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Dictionary whereEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Dictionary whereId($value)
 * @mixin \Eloquent
 */
class Dictionary extends Model
{
    protected $table = 'dictionaries';

    public $timestamps = false;

    protected $fillable = [
        'es', 'en'
    ];
}
