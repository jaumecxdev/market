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

class OrdersGet extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:orders {market_code} {shop_code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene los Pedidos del Marketplace {market_code} de la Tienda {shop_code}';

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
            Log::channel('commands')->info('START get:orders ' .$market_code. ' ' .$shop_code);
            $log_schedule = LogSchedule::create(['type' => 'get:orders', 'name' => 'get:orders ' .$market_code. ' ' .$shop_code]);

            $res = [];
            $market = Market::where('code', $market_code)->first();
            if (!$market) {
                $res = 'ERROR get:orders ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado el Marketplace';
                Log::channel('commands')->error($res);
                $log_schedule->update(['info' => $res]);
                return $this->nullAndStorage('OrdersGet', $market_code);
            }

            $shop = Shop::where('market_id', $market->id)->where('code', $shop_code)->first();
            if (!$shop) {
                $res = 'ERROR get:orders ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado la Tienda';
                Log::channel('commands')->error($res);
                $log_schedule->update(['info' => $res]);
                return $this->nullAndStorage('OrdersGet', $shop_code);
            }

            $log_schedule->update(['market_id' => $market->id, 'shop_id' => $shop->id]);

            Log::channel('commands')->info('get:orders ' .$market_code. ' ' .$shop_code. ' - Descargando los pedidos de la tienda');
            $ws = MarketWS::getMarketWS($shop);
            if ($ws) {
                $orders_result = $ws->getOrders();
                $res = 'get:orders ' .$market_code. ' ' .$shop_code. ' - ' .json_encode($orders_result);
                Log::channel('commands')->info($res);
            }
            else {
                $res = 'get:orders ' .$market_code. ' ' .$shop_code. ' - ERROR NO WS';
                Log::channel('commands')->alert($res);
                return $this->nullAndStorage('OrdersGet', [$market_code, $shop_code]);
            }

            Log::channel('commands')->info('END get:orders ' .$market_code. ' ' .$shop_code);
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'OrdersGet', [$market_code, $shop_code]);
        }
    }
}
