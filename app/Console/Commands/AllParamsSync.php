<?php

namespace App\Console\Commands;


use App\LogSchedule;
use App\Market;
use App\Shop;
use App\Supplier;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;


class AllParamsSync extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:allparams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los parámetros de Proveedores, Tiendas y Marketplaces, de todos los productos de todas las tiendas.';

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
            Log::channel('commands')->info('START sync:allparams');
            $log_schedule = LogSchedule::create(['type' => 'sync:allparams', 'name' => 'sync:allparams']);
            $res = [];

            $suppliers = Supplier::all();
            foreach ($suppliers as $supplier)
                $res[] = $supplier->syncParams();

            $markets = Market::all();
            foreach ($markets as $market)
                $res[] = $market->syncParams();
                //if ($market->code != 'amazonzp') $res[] = $market->syncParams();

            $shops = Shop::whereEnabled(1)->get();
            foreach ($shops as $shop)
                $res[] = $shop->syncParams();

            Log::channel('commands')->info('END sync:allparams: '.json_encode($res));
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'AllParamsSync', null);
        }
    }

}
