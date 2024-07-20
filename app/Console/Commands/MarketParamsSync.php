<?php

namespace App\Console\Commands;

use App\LogSchedule;
use App\Market;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;


class MarketParamsSync extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:marketparams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los parámetros de Marketplace de todos los productos de todas las tiendas.';

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
            Log::channel('commands')->info('START sync:marketparams');
            $log_schedule = LogSchedule::create(['type' => 'sync:marketparams', 'name' => 'sync:marketparams']);

            $res = [];
            $markets = Market::all();
            foreach ($markets as $market)
                $res[] = $market->syncParams();

            Log::channel('commands')->info('END sync:marketparams: '.json_encode($res));
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'MarketParamsSync', null);
        }
    }

}
