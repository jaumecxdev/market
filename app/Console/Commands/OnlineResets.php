<?php

namespace App\Console\Commands;

use App\Libraries\MarketWS;
use App\LogSchedule;
use App\Market;
use App\Shop;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class OnlineResets extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resets:online';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resetea stocks a 0 de productos no disponibles en todos los Marketplaces';

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
            Log::channel('commands')->info('START: resets:online - Reseteando stocks a 0 de productos no disponibles.');
            $log_schedule = LogSchedule::create(['type' => 'resets:online', 'name' => 'resets:online']);

            $res = [];
            $resets = [
                ['market_code' => 'ae',             'shop_code' => 'locura'],
                ['market_code' => 'ae',             'shop_code' => 'gmz'],
                ['market_code' => 'ebay',           'shop_code' => 'locura'],
                ['market_code' => 'worten',         'shop_code' => 'locura'],
                ['market_code' => 'pceducacion',    'shop_code' => 'pcedu'],
                ['market_code' => 'udg',            'shop_code' => 'udg'],
            ];

            foreach ($resets as $reset) {
                $market = Market::whereCode($reset['market_code'])->first();
                if ($market) {
                    $shop = Shop::whereMarketId($market->id)->whereCode($reset['shop_code'])->first();
                    if ($shop) {
                        $ws = MarketWS::getMarketWS($shop);
                        $count = $ws->resetOnline();
                        $msg = 'resets:online Productos reseteados: ' .json_encode($count);
                        Log::channel('commands')->info($msg);
                        $res[] = $msg;
                    }
                    else {
                        $msg = 'ERROR resets:online - No se ha encontrado la Tienda: '.$reset['shop_code'];
                        Log::channel('commands')->error($msg);
                        $res[] = $msg;
                    }
                }
                else {
                    $msg = 'ERROR resets:online - No se ha encontrado el Market: '.$reset['market_code'];
                    Log::channel('commands')->error($msg);
                    $res[] = $msg;
                }
            }

            Log::channel('commands')->info('END resets:online');
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'OnlineResets', null);
        }
    }
}
