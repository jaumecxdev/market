<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Twitter
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $user_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Receiver[] $receivers
 * @property-read int|null $receivers_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Twitter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Twitter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Twitter query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Twitter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Twitter whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Twitter whereUserId($value)
 * @mixin \Eloquent
 */
class Twitter extends Model
{
    protected $table = 'twitters';

    public $timestamps = false;

    protected $fillable = [
        'name', 'user_id'
    ];


    // MANY

    public function receivers()
    {
        return $this->hasMany('App\Receiver');
    }
}
