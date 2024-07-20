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

class PricesPost extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:prices {market_code} {shop_code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los precios y stocks del Marketplace {market_code} en la Tienda {shop_code}';

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
            Log::channel('commands')->info('START post:prices ' .$market_code. ' ' .$shop_code);
            $log_schedule = LogSchedule::create(['type' => 'post:prices', 'name' => 'post:prices ' .$market_code. ' ' .$shop_code]);

            $res = [];
            $market = Market::where('code', $market_code)->first();
            if (!$market) {
                $msg = 'ERROR post:prices ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado el Marketplace';
                Log::channel('commands')->error($msg);
                $res[] = $msg;
                $log_schedule->update(['info' => $res]);
                return $this->nullAndStorage('PricesPost', $market_code);
            }

            $shop = Shop::where('market_id', $market->id)->where('code', $shop_code)->first();
            if (!$shop) {
                $msg = 'ERROR post:prices ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado la Tienda';
                Log::channel('commands')->error($msg);
                $res[] = $msg;
                $log_schedule->update(['info' => $res]);
                return $this->nullAndStorage('PricesPost', $shop_code);
            }

            $log_schedule->update(['market_id' => $market->id, 'shop_id' => $shop->id]);

            Log::channel('commands')->info('post:prices ' .$market_code. ' ' .$shop_code. ' - Obteniendo Jobs y Actualizando precios y stocks en la tienda');
            $ws = MarketWS::getMarketWS($shop);
            if ($ws) {
                // Get Jobs
                $count_jobs = $ws->getJobs();
                $msg = 'get:jobs ' .$market_code. ' ' .$shop_code. ' - Result: ' .json_encode($count_jobs);
                Log::channel('commands')->info($msg);
                $res[] = $msg;

                // Post Prices & Stocks
                $count = $ws->postPricesStocks();
                $msg = 'post:prices ' .$market_code. ' ' .$shop_code. ' - Result: ' .json_encode($count);
                Log::channel('commands')->info($msg);
                $res[] = $msg;

                // Set Groups (Only on Aliexpress)
                if ($market->code == 'ae') {
                    $count_errors = $ws->postGroups();
                    $msg = 'set:groups ' .$market_code. ' ' .$shop_code. ' - Result: ' .json_encode($count_errors);
                    Log::channel('commands')->info($msg);
                    $res[] = $msg;
                }
            }
            else {
                $msg = 'post:prices ' .$market_code. ' ' .$shop_code. ' - ERROR NO WS';
                Log::channel('commands')->alert($msg);
                $res[] = $msg;
                return $this->nullAndStorage('PricesPost', [$market_code, $shop_code]);
            }

            Log::channel('commands')->info('END post:prices ' .$market_code. ' ' .$shop_code);
            $log_schedule->update(['ends_at' => now(), 'info' => mb_substr(implode(',', $res), 0, 65535)]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'PricesPost', [$market_code, $shop_code, $res ?? null]);
        }
    }
}
