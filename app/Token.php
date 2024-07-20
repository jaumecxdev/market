<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Token
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Token newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Token newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Token query()
 * @mixin \Eloquent
 * @property int $id
 * @property string|null $type
 * @property string|null $params
 * @property string $token
 * @property string|null $refresh
 * @method static \Illuminate\Database\Eloquent\Builder|Token whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Token whereParams($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Token whereRefresh($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Token whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Token whereType($value)
 */
class Token extends Model
{
    protected $table = 'tokens';

    public $timestamps = false;

    protected $fillable = [
        'type', 'params', 'token', 'refresh'
    ];

}
