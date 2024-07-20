<?php


namespace App\Commands\Telegram;


use App\Receiver;
use App\Telegram;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Update;

class StartCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = "start";

    /**
     * @var string Command Description
     */
    protected $description = "Iniciar el chat con el Bot.";

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $update = $this->getUpdate();
        Storage::append('api/telegram/' .date('Y-m-d'). '_StartCommand_UPDATE.json', $update->toJson());
        $user_is_notifiable = false;
        $text_params = explode(' ', $update->message->text);
        Storage::append('api/telegram/' .date('Y-m-d'). '_StartCommand_TEXT_PARAMS.json', json_encode($text_params));
        if (isset($text_params[1])) {
            $count = Telegram::whereInviteCode($text_params[1])
                ->update([
                    'name'         => $update->message->from->firstName ?? null,
                    'user_id'      => $update->message->from->id ?? null,
                    'chat_id'      => $update->message->chat->id ?? null,
                    'invite_code'  => null,
                ]);

            if ($count) $user_is_notifiable = true;
        }

        // This will send a message using `sendMessage` method behind the scenes to
        // the user/chat id who triggered this command.
        // `replyWith<Message|Photo|Audio|Video|Voice|Document|Sticker|Location|ChatAction>()` all the available methods are dynamically
        // handled when you replace `send<Method>` with `replyWith` and use the same parameters - except chat_id does NOT need to be included in the array.
        $this->replyWithMessage(['text' => "Hola ".$update->message->from->firstName."! Bienvenido a nuestro bot.\nEstas son las ordenes disponibles:"]);

        // This will update the chat status to typing...
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        // This will prepare a list of available commands and send the user.
        // First, Get an array of all registered commands
        // They'll be in 'command-name' => 'Command Handler Class' format.
        $commands = $this->getTelegram()->getCommands();

        // Build the list
        $response = '';
        foreach ($commands as $name => $command) {
            if ($name != 'orders' || $user_is_notifiable)
                $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
        }

        // Reply with the commands list
        $this->replyWithMessage(['text' => $response]);

        /*$arguments = $this->getArguments();
        Storage::append('api/telegram/' .date('Y-m-d'). '_StartCommand_ARGUMENTS.json', json_encode($arguments));
        $pattern = $this->getPattern();
        Storage::append('api/telegram/' .date('Y-m-d'). '_StartCommand_PATTERN.json', $pattern);
        $bus = $this->getCommandBus();
        Storage::append('api/telegram/' .date('Y-m-d'). '_StartCommand_BUS.json', json_encode($bus));*/

        // Trigger another command dynamically from within this command
        // When you want to chain multiple commands within one or process the request further.
        // The method supports second parameter arguments which you can optionally pass, By default
        // it'll pass the same arguments that are received for this command originally.
        //$this->triggerCommand('subscribe');
    }
}
