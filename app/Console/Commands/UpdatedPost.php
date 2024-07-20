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

class UpdatedPost extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:updated {market_code} {shop_code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza todas las fichas de los productos del Marketplace {market_code} en la Tienda {shop_code}';

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
            $market_code = $this->argument('market_code');
            $shop_code = $this->argument('shop_code');
            Log::channel('commands')->info('START post:updated ' .$market_code. ' ' .$shop_code);
            $log_schedule = LogSchedule::create(['type' => 'post:updated', 'name' => 'post:updated ' .$market_code. ' ' .$shop_code]);

            $res = [];
            $market = Market::where('code', $market_code)->first();
            if (!$market) {
                $res = 'ERROR post:updated ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado el Marketplace';
                $log_schedule->update(['info' => $res]);
                Log::channel('commands')->error($res);
                return $this->nullAndStorage('UpdatedPost', $market_code);
            }

            $shop = Shop::where('market_id', $market->id)->where('code', $shop_code)->first();
            if (!$shop) {
                $res = 'ERROR post:updated ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado la Tienda';
                $log_schedule->update(['info' => $res]);
                Log::channel('commands')->error($res);
                return $this->nullAndStorage('UpdatedPost', $shop_code);
            }

            $log_schedule->update(['market_id' => $market->id, 'shop_id' => $shop->id]);

            Log::channel('commands')->info('post:updated ' .$market_code. ' ' .$shop_code. ' - Obteniendo Jobs y actualizando productos en la tienda');
            $ws = MarketWS::getMarketWS($shop);
            if ($ws) {
                // Get Jobs
                $count_jobs = $ws->getJobs();
                Log::channel('commands')->info('get jobs ' .$market_code. ' ' .$shop_code. ' - Result: ' .json_encode($count_jobs));
                $res[] = $count_jobs;

                // Post Prices & Stocks
                $count = $ws->postUpdatedProducts();
                Log::channel('commands')->info('post:updated ' .$market_code. ' ' .$shop_code. ' - Result: ' .json_encode($count));
                $res[] = $count;

                // Set Groups (Only on Aliexpress)
                if ($market->code == 'ae') {
                    $count_errors = $ws->postGroups();
                    Log::channel('commands')->info('post groups ' .$market_code. ' ' .$shop_code. ' - Result: ' .json_encode($count_errors));
                    $res[] = $count_errors;
                }
            }
            else {
                $res = 'ERROR NO WS';
                Log::channel('commands')->alert('post:updated ' .$market_code. ' ' .$shop_code. ' - '.$res);
            }


            Log::channel('commands')->info('END post:updated ' .$market_code. ' ' .$shop_code);
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'UpdatedPost', [$market_code, $shop_code]);
        }
    }
}
