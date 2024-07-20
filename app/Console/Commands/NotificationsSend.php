<?php

namespace App\Console\Commands;

use App\LogSchedule;
use App\Order;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationsSend extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía notificaciones pendientes de pedidos vía Email, Telegram y Twitter';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            Log::channel('commands')->info('START: send:notifications - Enviando notificaciones pendientes de pedidos.');
            $log_schedule = LogSchedule::create(['type' => 'send:notifications', 'name' => 'send:notifications']);

            $orders = Order::where('notified', '=', 0)->orWhere('notified_updated', '=', 0)->get();
            $res = 0;
            foreach ($orders as $order) {
                $notified_type = $order->notified ? 'MODIFICADO ' : 'NUEVO ';
                $res += $order->sendNotifications($notified_type);
            }

            Log::channel('commands')->info('END send:notifications. ' .$res. ' notifications sent.');
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'NotificationsSend', null);
        }
    }
}
