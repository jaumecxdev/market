<?php

namespace App\Console\Commands;

use App\Libraries\MarketWS;
use App\LogSchedule;
use App\Market;
use App\Shop;
use App\Traits\HelperTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class BuyboxGet extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:buybox {market_code} {shop_code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene un paquete de precios BuyBox de esta tienda.';

    protected $hour_fees_hot;
    protected $hour_fees;


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
            Log::channel('commands')->info('START get:buybox ' .$market_code. ' ' .$shop_code);
            $log_schedule = LogSchedule::create(['type' => 'get:buybox ', 'name' => 'get:buybox ' .$market_code. ' ' .$shop_code]);

            $res = [];
            if (!$market = Market::whereCode($market_code)->first()) {
                $res = 'ERROR get:buybox ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado el Marketplace';
                Log::channel('commands')->error($res);
                $log_schedule->update(['info' => mb_substr($res, 0, 30000)]);
                return $this->nullAndStorage('BuyboxGet', $market_code);
            }

            if (!$shop = Shop::whereMarketId($market->id)->whereCode($shop_code)->first()) {
                $res = 'ERROR get:buybox ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado la Tienda';
                Log::channel('commands')->error($res);
                $log_schedule->update(['info' => mb_substr($res, 0, 30000)]);
                return $this->nullAndStorage('BuyboxGet', $shop_code);
            }

            $log_schedule->update(['market_id' => $market->id, 'shop_id' => $shop->id]);

            $ws = MarketWS::getMarketWS($shop);
            $res = $ws->getSomeBuyboxPrices();

            Log::channel('commands')->info('END get:buybox ' .$market_code. ' ' .$shop_code. ': ' .json_encode($res));
            $info = isset($res) ? (is_array($res) ? mb_substr(json_encode($res), 0, 30000) : mb_substr($res, 0, 30000)) : '';
            $log_schedule->update(['ends_at' => now(), 'info' => $info]);

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'get:buybox', [$market_code ?? null, $shop_code ?? null]);
        }
    }

}
