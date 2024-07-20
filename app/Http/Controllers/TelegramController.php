<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except('api');
        $this->middleware('APIToken')->only('api');
    }


    public function api(Request $request)
    {
        $bot = 'mpspecialistbot';   // config('telegram.default');
        $telegram_bot_token = config('telegram.bots.' . $bot . '.token');
        try {
            $telegram = new Api($telegram_bot_token);

            $telegram->addCommands(config('telegram.bots.' . $bot . '.commands'));
            $update = $telegram->commandsHandler(true);
            //$update = \Telegram\Bot\Laravel\Facades\Telegram::commandsHandler(true);

            Storage::append('api/telegram/' . date('Y-m-d') . '_WEBHOOK_UPDATE.json', json_encode($update));

        } catch (TelegramSDKException $e) {
            Storage::append('api/telegram/' . date('Y-m-d') . '_SDKEXCEPTION.json', $e->getMessage());
            return json_encode(['ok' => true]);
        }

        return json_encode(['ok' => true]);
    }


    public function index()
    {
        return view('telegram.index');
    }


    public function webhook(Request $request)
    {
        $bot = $request->input('bot');
        if (!isset($bot)) $response = 'NO BOT';
        else $response = 'BOT ISSET';

        switch ($request->input('action')) {
            case 'set':
                $response = $this->set($bot);
                break;
            case 'get':
                $response = $this->get($bot);
                break;
            case 'delete':
                $response = $this->delete($bot);
                break;
            case 'getme':
                $response = $this->getMe($bot);
                break;
            case 'sendmessage':
                $params = [
                    'chat_id' => $request->input('chat_id'),
                    'text' => $request->input('text'),
                ];
                $response = $this->sendMessage($bot, $params);
                break;
            case 'invite':
                $invite_code = $request->input('invite_code');
                return $this->invite($bot, $invite_code);
//                $url_invite = 'https://t.me/'.$bot.'?start='.config('telegram.bots.'.$bot.'.invite');
//                return redirect()->to($url_invite);
                break;
        }

        return redirect()->route('telegram.index')->with('status', json_encode($response));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return string
     */
    public function set($bot)
    {
        // TELEGRAM: setWebhook
        $telegram_bot_token = config('telegram.bots.'.$bot.'.token');   // 1176678210:AAHs9opwxT36by6KD7hIlmWZ6g1dnIe3RAI
        $token = config('auth.api_access_token');                       // 68BD8DD6789914DD5BC322F5638DE
        $client = new Client(['base_uri' => 'https://api.telegram.org/bot'.$telegram_bot_token.'/']);
        $response = $client->get('setWebhook', [
            'query' => [
                            // https://app.mpespecialist.com/api/telegram/68BD8DD6789914DD5BC322F5638DE
                'url' => 'https://app.mpespecialist.com/api/telegram/'.$token,
            ]
        ]);

        if ($response->getStatusCode() == '200') {
            $contents = $response->getBody()->getContents();
            // {"ok":true,"result":true,"description":"Webhook was set"}
            Storage::append('api/telegram/' .date('Y-m-d'). '_setWebhook.json', $contents);
            return $contents;
        }

        return false;
    }


    public function get($bot)
    {
        // TELEGRAM: getWebhookInfo
        $telegram_bot_token = config('telegram.bots.'.$bot.'.token');   // 1176678210:AAHs9opwxT36by6KD7hIlmWZ6g1dnIe3RAI
        $client = new Client(['base_uri' => 'https://api.telegram.org/bot'.$telegram_bot_token.'/']);
        $response = $client->get('getWebhookInfo');

        if ($response->getStatusCode() == '200') {
            $contents = $response->getBody()->getContents();
            // {\"ok\":true,\"result\":{\"url\":\"https:\/\/app.mpespecialist.com\/api\/telegram\/123qweasd\",\"has_custom_certificate\":false,
            // \"pending_update_count\":7,\"last_error_date\":1590664945,\"last_error_message\":\"Wrong response from the webhook: 500 Internal Server Error\",
            // \"max_connections\":40}}
            Storage::append('api/telegram/' .date('Y-m-d'). '_getWebhookInfo.json', $contents);
            return $contents;
        }

        return false;
    }


    public function delete($bot)
    {
        // TELEGRAM: deleteWebhook
        $telegram_bot_token = config('telegram.bots.'.$bot.'.token');
        $client = new Client(['base_uri' => 'https://api.telegram.org/bot'.$telegram_bot_token.'/']);
        $response = $client->get('deleteWebhook');

        if ($response->getStatusCode() == '200') {
            $contents = $response->getBody()->getContents();
            // {"ok":true,"result":true,"description":"Webhook was set"}
            Storage::append('api/telegram/' .date('Y-m-d'). '_deleteWebhook.json', $contents);
            return $contents;
        }

        return false;
    }


    public function getMe($bot)
    {
        $telegram_bot_token = config('telegram.bots.'.$bot.'.token');   // 1176678210:AAHs9opwxT36by6KD7hIlmWZ6g1dnIe3RAI
        try {
            $telegram = new Api($telegram_bot_token);
            $response = $telegram->getMe();
                /*$botId = $response->getId();
                $firstName = $response->getFirstName();
                $username = $response->getUsername();*/

            // {"id":1176678210,"is_bot":true,"first_name":"Marketplace","username":"MPSpecialistBot","can_join_groups":true,"can_read_all_group_messages":false,
            // "supports_inline_queries":false}
            return $response;

        } catch (TelegramSDKException $e) {
            Storage::append('api/telegram/' .date('Y-m-d'). '_api_ERROR.json', json_encode($e));
            return $e;
        }
    }


    public function invite($bot, $invite_code)
    {
        // https://t.me/mpspecialistbot?start=9E3D4B623CBF2
        $url_invite = 'https://t.me/'.$bot.'?start='.$invite_code;

        return redirect()->to($url_invite);
    }


    public function sendMessage($bot, $params)
    {
        $telegram_bot_token = config('telegram.bots.'.$bot.'.token');   // 1176678210:AAHs9opwxT36by6KD7hIlmWZ6g1dnIe3RAI
        try {
            $telegram = new Api($telegram_bot_token);
            $response = $telegram->sendMessage($params);
            $messageId = $response->getMessageId();

            return $response;

        } catch (TelegramSDKException $e) {
            Storage::append('api/telegram/' .date('Y-m-d'). '_api_ERROR.json', json_encode($e));
            return $e;
        }
    }






}
