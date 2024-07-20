<?php

namespace App\Http\Controllers;

use App\LogNotification;
use App\Supplier;
use Illuminate\Http\Request;

class LogNotificationController extends Controller
{
    private $classes = [
        'MailOrderNotification',
        'TelegramOrderNotification',
        'TwitterOrderNotification'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $classes = $this->classes;
        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'log_notifications.created_at';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $log_notifications = LogNotification::filter($params)->paginate(50);

        return view('receiver.log', compact(['suppliers', 'classes', 'params', 'order_params', 'log_notifications']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\LogNotification  $logNotification
     * @return \Illuminate\Http\Response
     */
    public function show(LogNotification $logNotification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\LogNotification  $logNotification
     * @return \Illuminate\Http\Response
     */
    public function edit(LogNotification $logNotification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\LogNotification  $logNotification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LogNotification $logNotification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\LogNotification  $logNotification
     * @return \Illuminate\Http\Response
     */
    public function destroy(LogNotification $logNotification)
    {
        //
    }
}
