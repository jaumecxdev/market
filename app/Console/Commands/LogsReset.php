<?php

namespace App\Console\Commands;

use App\LogSchedule;
use App\Price;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class LogsReset extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia los logs del sistema y deja los últimos 7 días';

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
            Log::channel('commands')->info('START reset:logs - Reseteando tabla prices');
            $log_schedule = LogSchedule::create(['type' => 'reset:logs', 'name' => 'reset:logs']);

            $res = Price::reset(7);

            Log::channel('commands')->info('END reset:logs - Result: ' .$res);
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'LogsReset', null);
        }
    }
}
