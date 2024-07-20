<?php

namespace App\Console\Commands;

use App\LogSchedule;
use App\Shop;
use App\Traits\HelperTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class Repricing extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repricing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebaja los margenes de bcio de las tiendas MPe constantemente durante el dia y por la madrugada resetea al inicial.';

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

        // 2,6,8,10,12,14,16,18,20,22,23
        $this->hour_fees_hot = [
            2   => 2,
            6   => 1.6,     // Les 6h del server son les 8h a Cat
            7   => 1.6,
            8   => 1.5,
            9   => 1.5,
            10   => 1.4,
            11   => 1.4,
            12   => 1.3,
            13   => 1.3,
            14   => 2.5,    // Les 14h del server son les 16h
            15   => 2.5,
            16   => 1.8,
            17   => 1.8,
            18   => 1.7,
            19   => 1.7,
            20   => 1.6,
            21   => 1.6,
            22   => 1.5,
            23   => 1.4
        ];

        $this->hour_fees = [
            2   => 3,
            6   => 2.5,     // Les 6h del server son les 8h
            7   => 2.5,
            8   => 2.4,
            9   => 2.4,
            10   => 2.3,
            11   => 2.3,
            12   => 2.2,
            13   => 2.2,
            14   => 3,    // Les 14h del server son les 16h
            15   => 3,
            16   => 2.6,
            17   => 2.6,
            18   => 2.5,
            19   => 2.5,
            20   => 2.4,
            21   => 2.4,
            22   => 2.3,
            23   => 2.2
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        try {
            Log::channel('commands')->info('START repricing');
            $log_schedule = LogSchedule::create(['type' => 'repricing', 'name' => 'repricing']);

            // 2,6,8,10,12,14,16,18,20,22,23

            $res = [];
            $hour = Carbon::now()->hour;

            // HOT FEES - PC COMPO, AMAZON
            $shop_codes = ['mpepccompo'];       // , 'mpeamazon'
            foreach ($shop_codes as $shop_code) {
                $shop = Shop::whereCode($shop_code)->first();
                if ($shop_param = $shop->shop_params()->where('mps_fee', '>', 1)->whereNull('supplier_id')->first()) {

                    if (isset($this->hour_fees_hot[$hour])) $fee = $this->hour_fees_hot[$hour];
                    else $fee = 3;

                    // IF LAST MONTH DAY -> ALL STOCK 0
                    if (now()->addDay()->day == 1 && now()->hour >= 18) $shop_param->stock_min = 99999999;
                    elseif (now()->day == 1 && now()->hour <= 8) $shop_param->stock_min = 99999999;
                    else $shop_param->stock_min = 5;

                    $shop_param->mps_fee = $fee;
                    $shop_param->save();
                    $shop->syncParams();
                    $res[] = ['shop_code' => $shop_code, 'new_mps_fee' => $shop_param->mps_fee];
                }
            }


            /* $shop_pccompo = Shop::whereCode('mpepccompo')->first();
            $shop_param_pccompo = $shop_pccompo->shop_params()->where('mps_fee', '>', 1)->whereNull('supplier_id')->first();
            if (isset($this->hour_fees_hot[$hour])) $fee = $this->hour_fees_hot[$hour];
            else $fee = 3;

            // IF LAST MONTH DAY -> ALL STOCK 0
            if (now()->addDay()->day == 1 && now()->hour >= 18) $shop_param_pccompo->stock_min = 99999999;
            elseif (now()->day == 1 && now()->hour <= 8) $shop_param_pccompo->stock_min = 99999999;
            else $shop_param_pccompo->stock_min = 5;

            $shop_param_pccompo->mps_fee = $fee;
            $shop_param_pccompo->save();
            $shop_pccompo->syncParams();
            $res[] = ['shop_code' => $shop_pccompo->code, 'new_mps_fee' => $shop_param_pccompo->mps_fee]; */


            // NORMAL FEES
            // CARREFOUR, WORTEN, ALIEXPRESS
            $shop_codes = ['mpeworten', 'mpecarrefour'];        // 'mpeae',
            foreach ($shop_codes as $shop_code) {
                $shop = Shop::whereCode($shop_code)->first();
                if ($shop_param = $shop->shop_params()->where('mps_fee', '>', 1)->whereNull('supplier_id')->first()) {

                    if (isset($this->hour_fees[$hour])) $fee = $this->hour_fees[$hour];
                    else $fee = 3;

                    // IF LAST MONTH DAY -> ALL STOCK 0
                    if (now()->addDay()->day == 1 && now()->hour >= 18) $shop_param->stock_min = 99999999;
                    elseif (now()->day == 1 && now()->hour <= 8) $shop_param->stock_min = 99999999;
                    else $shop_param->stock_min = 5;

                    $shop_param->mps_fee = $fee;
                    $shop_param->save();
                    $shop->syncParams();
                    $res[] = ['shop_code' => $shop_code, 'new_mps_fee' => $shop_param->mps_fee];
                }
            }

            Log::channel('commands')->info('END repricing: '.json_encode($res));
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'Repricing', null);
        }
    }

}
