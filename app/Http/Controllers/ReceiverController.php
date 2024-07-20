<?php

namespace App\Http\Controllers;

use App\Receiver;
use App\Supplier;
use App\Telegram;
use App\Twitter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ReceiverController extends Controller
{
    private $classes = [
        'MailOrderNotification',
        'TelegramOrderNotification',
        'TwitterOrderNotification'
    ];


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $classes = $this->classes;
        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'receivers.name';
            $params['order'] = 'asc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $receivers = Receiver::filter($params)->paginate(50);

        return view('receiver.index', compact(['suppliers', 'classes', 'params', 'order_params', 'receivers']));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $classes = $this->classes;

        return view('receiver.create-edit', compact(['suppliers', 'classes']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'class'                 => 'required|in:'.implode(',', $this->classes),
            'name'                  => 'nullable|max:255',
            'email'                 => 'required_if:class,MailOrderNotification|max:255',
            'telegram_invite_code'  => 'nullable|max:64',
            'telegram_user_id'      => 'nullable|max:64',
            'telegram_chat_id'      => 'nullable|max:64',
            'twitter_user_id'       => 'nullable|max:64',
        ]);

        $receiverData = [
            'supplier_id'   => $validatedData['supplier_id'],
            'class'         => $validatedData['class'],
            'name'          => $validatedData['name'],
            'email'         => $validatedData['email'],
        ];
        $receiver = Receiver::create($receiverData);

        // TELEGRAM
        if (isset($validatedData['telegram_invite_code']) || isset($validatedData['telegram_user_id']) || isset($validatedData['telegram_chat_id'])) {
            $telegram = Telegram::create([
                'name'          => $validatedData['name'],
                'invite_code'   => $validatedData['telegram_invite_code'],
                'user_id'       => $validatedData['telegram_user_id'],
                'chat_id'       => $validatedData['telegram_chat_id'],
            ]);

            $receiver->telegram_id = $telegram->id;
            $receiver->save();
        }

        // TWITTER
        if (isset($validatedData['twitter_user_id'])) {
            $twitter = Twitter::create([
                'name'          => $validatedData['name'],
                'user_id'       => $validatedData['twitter_user_id'],
            ]);

            $receiver->twitter_id = $twitter->id;
            $receiver->save();
        }

        return redirect()->route('receivers.index')->with('status', 'Notificable creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Receiver  $receiver
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Receiver $receiver)
    {
        return $this->edit($receiver);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Receiver  $receiver
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Receiver $receiver)
    {
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $classes = $this->classes;

        return view('receiver.create-edit', compact(['receiver', 'suppliers', 'classes']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Receiver  $receiver
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Receiver $receiver)
    {
        $validatedData = $request->validate([
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'class'                 => 'required|in:'.implode(',', $this->classes),
            'name'                  => 'nullable|max:255',
            'email'                 => 'required_if:class,MailOrderNotification|max:255',
            'telegram_invite_code'  => 'nullable|max:64',
            'telegram_user_id'      => 'nullable|max:64',
            'telegram_chat_id'      => 'nullable|max:64',
            'twitter_user_id'       => 'nullable|max:64',
        ]);

        $receiverData = [
            'supplier_id'   => $validatedData['supplier_id'],
            'class'         => $validatedData['class'],
            'name'          => $validatedData['name'],
            'email'         => $validatedData['email'],
        ];
        Receiver::whereId($receiver->id)->update($receiverData);

        // TELEGRAM
        if (isset($validatedData['telegram_invite_code']) || isset($validatedData['telegram_user_id']) || isset($validatedData['telegram_chat_id'])) {
            $telegramData = [
                'name'          => $validatedData['name'],
                'invite_code'   => $validatedData['telegram_invite_code'],
                'user_id'       => $validatedData['telegram_user_id'],
                'chat_id'       => $validatedData['telegram_chat_id'],
            ];
            if (isset($receiver->telegram_id))
                Telegram::whereId($receiver->telegram_id)->update($telegramData);
            else {
                $telegram = Telegram::create($telegramData);
                $receiver->telegram_id = $telegram->id;
                $receiver->save();
            }
        }

        // TWITTER
        if (isset($validatedData['twitter_user_id'])) {
            $twitterData = [
                'name'          => $validatedData['name'],
                'user_id'       => $validatedData['twitter_user_id'],
            ];
            if (isset($receiver->twitter_id))
                Twitter::whereId($receiver->twitter_id)->update($twitterData);
            else {
                $twitter = Twitter::create($twitterData);
                $receiver->twitter_id = $twitter->id;
                $receiver->save();
            }
        }

        return redirect()->route('receivers.index')->with('status', 'Notificable modificado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Receiver  $receiver
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Receiver $receiver)
    {
        try {
            if ($receiver->telegram_id) $receiver->telegram->delete();
            $receiver->delete();
        } catch (QueryException $e) {
            return redirect()->route('receivers.index')->with('status', $e->getMessage());
        }

        return redirect()->route('receivers.index')->with('status', 'Notificable eliminado.');
    }
}
