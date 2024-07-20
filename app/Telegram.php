<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Telegram
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $telegram_invite_code
 * @property string|null $telegram_user_id
 * @property string|null $telegram_chat_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Receiver[] $receivers
 * @property-read int|null $receivers_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram whereInviteCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram whereUserId($value)
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram whereTelegramChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram whereTelegramInviteCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Telegram whereTelegramUserId($value)
 * @property string|null $invite_code
 * @property string|null $user_id
 * @property string|null $chat_id
 */
class Telegram extends Model
{
    protected $table = 'telegrams';

    public $timestamps = false;

    protected $fillable = [
        'name', 'invite_code', 'user_id', 'chat_id'
    ];


    // MANY

    public function receivers()
    {
        return $this->hasMany('App\Receiver');
    }


}
