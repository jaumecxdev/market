<?php


namespace App\Commands\Telegram;


use App\Receiver;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Commands\Command;

class HelpCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'help';

    /**
     * @var array Command Aliases
     */
    protected $aliases = ['listcommands'];

    /**
     * @var string Command Description
     */
    protected $description = 'Obtener lista de comandos.';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $update = $this->getUpdate();
        Storage::append('api/telegram/' .date('Y-m-d'). '_HelpCommand_UPDATE.json', $update->toJson());

        $user_is_notifiable = false;
        $receiver = Receiver::join('telegrams', 'receivers.telegram_id', '=', 'telegrams.id')
            ->where('telegrams.user_id', strval($update->message->from->id))
            ->where('chat_id', strval($update->message->chat->id))
            ->first();

        if ($receiver) $user_is_notifiable = true;
        $commands = $this->telegram->getCommands();

        $text = '';
        foreach ($commands as $name => $handler) {
            /* @var Command $handler */
            if ($name != 'orders' || $user_is_notifiable)
                $text .= sprintf('/%s - %s'.PHP_EOL, $name, $handler->getDescription());
        }

        $this->replyWithMessage(compact('text'));
    }
}
