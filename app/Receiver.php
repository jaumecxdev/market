<?php

namespace App;

use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * App\Notification
 *
 * @property int $id
 * @property int|null $supplier_id
 * @property string|null $via
 * @property string|null $class
 * @property string|null $name
 * @property string|null $email
 * @property string|null $telegram_user_invite
 * @property string|null $telegram_user_id
 * @property string|null $telegram_chat_id
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Supplier|null $supplier
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereTelegramChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereTelegramUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereTelegramUserInvite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereVia($value)
 * @mixin \Eloquent
 * @property string|null $telegram_invite_code
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereTelegramInviteCode($value)
 * @property int|null $telegram_id
 * @property-read \App\Telegram|null $telegram
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver filter($params)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereTelegramId($value)
 * @property int|null $twitter_id
 * @property-read \App\Twitter|null $twitter
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Receiver whereTwitterId($value)
 */
class Receiver extends Model
{
    use Notifiable;

    protected $table = 'receivers';

    public $timestamps = false;

    protected $fillable = [
        'supplier_id', 'telegram_id', 'twitter_id', 'class', 'name', 'email', 'token'
    ];


    public function supplier()
    {
        return $this->belongsTo('App\Supplier');
    }

    public function telegram()
    {
        return $this->belongsTo('App\Telegram');
    }

    public function twitter()
    {
        return $this->belongsTo('App\Twitter');
    }


    // CUSTOM

    public function is_notificable()
    {
        return ( ($this->class == 'MailOrderNotification' && isset($this->email)) ||
            ($this->class == 'TelegramOrderNotification' && isset($this->telegram->user_id)) ||
            ($this->class == 'TwitterOrderNotification' && isset($this->twitter->user_id)) );
    }


    // ALTER: RoutesNotifications::notify
    public function notify($instance)
    {
        app(Dispatcher::class)->send($this, $instance);
        LogNotification::create(
            [
                'supplier_id'   => $this->supplier_id,
                'class'         => $this->class,
                'name'          => $this->name,
                'target'        => $this->email ?? $this->telegram->user_id ?? $this->twitter->user_id ?? null,
                'type'          => isset($instance->order_item) ? 'Order' : null,
                'type_id'       => $instance->order_item->order->id ?? null,
                'item'          => $instance->order_item->marketOrderId ?? null
            ]
        );
    }


    // SCOPES


    public function scopeFilter(Builder $query, $params)
    {
        $query->select(['receivers.*', 'suppliers.name as supplier_name'])
            ->leftJoin('suppliers', 'receivers.supplier_id', '=', 'suppliers.id')
            ->leftJoin('telegrams', 'receivers.telegram_id', '=', 'telegrams.id')
            ->leftJoin('twitters', 'receivers.twitter_id', '=', 'twitters.id');

        if ( isset($params['supplier_id']) && $params['supplier_id'] != null) {
            $query->where('receivers.supplier_id', $params['supplier_id']);
        }

        if ( isset($params['class']) && $params['class'] != null) {
            $query->where('receivers.class', $params['class']);
        }

        if ( isset($params['name']) && $params['name'] != null) {
            $query->where('receivers.name', $params['name']);
        }

        if ( isset($params['email']) && $params['email'] != null) {
            $query->where('receivers.email', $params['email']);
        }

        if ( isset($params['telegram_invite_code']) && $params['telegram_invite_code'] != null) {
            $query->where('telegrams.invite_code', $params['telegram_invite_code']);
        }

        if ( isset($params['telegram_user_id']) && $params['telegram_user_id'] != null) {
            $query->where('telegrams.user_id', $params['telegram_user_id']);
        }

        if ( isset($params['telegram_chat_id']) && $params['telegram_chat_id'] != null) {
            $query->where('telegrams.chat_id', $params['telegram_chat_id']);
        }

        if ( isset($params['twitter_user_id']) && $params['twitter_user_id'] != null) {
            $query->where('twitters.user_id', $params['twitter_user_id']);
        }

        // ORDER BY
        if ( isset($params['order_by']) && $params['order_by'] != null) {
            $query->orderBy($params['order_by'], $params['order']);
        }

        return $query;
    }


}
