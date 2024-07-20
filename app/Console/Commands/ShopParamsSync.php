<?php

namespace App\Console\Commands;

use App\LogSchedule;
use App\Shop;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShopParamsSync extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:shopparams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los parámetros de todos los productos de todas las tiendas.';

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
            Log::channel('commands')->info('START sync:shopparams');
            $log_schedule = LogSchedule::create(['type' => 'sync:shopparams', 'name' => 'sync:shopparams']);

            $res = [];
            $shops = Shop::whereEnabled(1)->get();
            foreach ($shops as $shop)
                $res[] = $shop->syncParams();

            Log::channel('commands')->info('END sync:shopparams: '.json_encode($res));
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'ShopParamsSync', null);
        }
    }

}
