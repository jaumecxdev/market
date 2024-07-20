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

class NewproductsPost extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:newproducts {market_code} {shop_code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sube las nuevas ofertas del Marketplace {market_code} en la Tienda {shop_code}';

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
            Log::channel('commands')->info('START post:newproducts ' .$market_code. ' ' .$shop_code);
            $log_schedule = LogSchedule::create(['type' => 'post:newproducts', 'name' => 'post:newproducts ' .$market_code. ' ' .$shop_code]);

            $res = [];
            $market = Market::where('code', $market_code)->first();
            if (!$market) {
                $msg = 'ERROR post:newproducts ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado el Marketplace';
                Log::channel('commands')->error($msg);
                $res[] = $msg;
                $log_schedule->update(['info' => $res]);
                return $this->nullAndStorage('NewproductsPost', $market_code);
            }

            $shop = Shop::where('market_id', $market->id)->where('code', $shop_code)->first();
            if (!$shop) {
                $msg = 'ERROR post:newproducts ' .$market_code. ' ' .$shop_code. ' - No se ha encontrado la Tienda';
                Log::channel('commands')->error($msg);
                $res[] = $msg;
                $log_schedule->update(['info' => $res]);
                return $this->nullAndStorage('NewproductsPost', $shop_code);
            }

            $log_schedule->update(['market_id' => $market->id, 'shop_id' => $shop->id]);

            Log::channel('commands')->info('post:newproducts ' .$market_code. ' ' .$shop_code. ' - Subiendo nuevas ofertas en la tienda');
            if ($ws = MarketWS::getMarketWS($shop)) {
                // Import New Products from Filters
                if ($query_filter = $shop->getProductsFilters()) {
                    $res_filter = $shop->importFilteredProducts($query_filter);
                    $msg = 'post:newproducts ' .$market_code. ' ' .$shop_code. ' - Import Filtered Products: ' .json_encode($res_filter);
                    Log::channel('commands')->info($msg);
                    $res[] = $msg;

                    // Post New Products
                    $res_post = $ws->postNewProducts();
                    $msg = 'post:newproducts ' .$market_code. ' ' .$shop_code. ' - Result: ' .json_encode($res_post);
                    Log::channel('commands')->info($msg);
                    $res[] = $msg;
                }
                else {
                    $shop->shop_products()->update(['enabled' => false, 'stock' => 0]);

                    $msg = 'post:newproducts ' .$market_code. ' ' .$shop_code. ' - No hay productos en los filtros, todos los productos anulados.';
                    Log::channel('commands')->alert($msg);
                    $res[] = $msg;
                    return $this->nullAndStorage('NewproductsPost', [$market_code, $shop_code]);
                }
            }
            else {
                $msg = 'post:newproducts ' .$market_code. ' ' .$shop_code. ' - ERROR NO WS';
                Log::channel('commands')->alert($msg);
                $res[] = $msg;
                return $this->nullAndStorage('NewproductsPost', [$market_code, $shop_code]);
            }

            Log::channel('commands')->info('END post:newproducts ' .$market_code. ' ' .$shop_code);
            $log_schedule->update(['ends_at' => now(), 'info' => mb_substr(implode(',', $res), 0, 65535)]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'NewproductsPost', [$market_code, $shop_code, $res ?? null]);
        }
    }
}
