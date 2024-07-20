<?php

namespace App\Console\Commands;


use App\LogSchedule;
use App\Market;
use App\Product;
use App\Shop;
use App\Supplier;
use App\Traits\HelperTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;


class FeedsUpgrade extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:feeds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mejora de las fichas de productos, en especial EAN y PN.';

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
            Log::channel('commands')->info('START upgrade:feeds');
            $log_schedule = LogSchedule::create(['type' => 'upgrade:feeds', 'name' => 'upgrade:feeds']);
            $res = [];

            // EAN & PN EQUALS & LENGTH == 13
            /* $products = Product::whereNotNull('ean')->whereRaw('ean = pn')
                ->whereRaw('LENGTH(ean) = 13')
                ->get();

            $res['equals_pn_ean_13'] = [];
            $res['equals_pn_ean_13']['pn_null'] = [];
            $res['equals_pn_ean_13']['ean_null'] = [];
            foreach ($products as $product) {
                // is EAN
                if (is_numeric($product->ean)) {
                    //$product->pn = null;
                    $product->save();
                    $res['equals_pn_ean_13']['pn_null'][] = [$product->id, $product->ean];
                }
                // is PN
                else {
                    $product->ean = null;
                    $product->save();
                    $res['equals_pn_ean_13']['ean_null'][] = [$product->id, $product->pn];
                }
            } */


            // EAN & PN EQUALS
            /* $products = Product::whereNotNull('ean')->whereRaw('ean = pn')
                    ->get();

            $res['equals_pn_ean'] = [];
            $res['equals_pn_ean']['ean_add_0'] = [];
            $res['equals_pn_ean']['ean_null'] = [];
            foreach ($products as $product) {
                // is EAN
                if (is_numeric($product->ean) && strlen(trim($product->ean)) == 12) {
                    $product->ean = '0'.$product->ean;
                    $product->save();
                    $res['equals_pn_ean']['ean_add_0'][] = [$product->id, $product->ean];
                }
                // is PN
                else {
                    $product->ean = null;
                    $product->save();
                    $res['equals_pn_ean']['ean_null'][] = [$product->id, $product->pn];
                }
            } */


            // LENGTH EAN == 25 -> EAN12 + EAN13 & NUMERIC
            $products = Product::whereNotNull('ean')->whereRaw('LENGTH(ean) = 25')
                    ->get();

            $res['ean_25'] = [];
            foreach ($products as $product) {
                if (is_numeric($product->ean)) {
                    $product->ean = '0'.mb_substr($product->ean, 0, 12);
                    $product->save();
                    $res['ean_25'][] = [$product->id, $product->ean];
                }
            }


            // LENGTH EAN == 12 & NUMERIC
            $products = Product::whereNotNull('ean')->whereRaw('LENGTH(ean) = 12')
                    ->get();

            $res['ean_12'] = [];
            foreach ($products as $product) {
                if (is_numeric($product->ean)) {
                    $product->ean = '0'.$product->ean;
                    $product->save();
                    $res['ean_12'][] = [$product->id, $product->ean];
                }
            }


            // EAN == 000000000000000
            $products = Product::whereEan('000000000000000')
                ->get();

            $res['ean_000000000000000'] = [];
            foreach ($products as $product) {
                $product->ean = null;
                $product->save();
                $res['ean_000000000000000'][] = [$product->id, $product->pn];
            }


            // Wrong EAN
            $products = Product::whereNull('pn')->whereNotNull('ean')->whereRaw('LENGTH(ean) <> 13')
                ->get();

            $res['wrong_ean_MANUAL'] = [];
            foreach ($products as $product) {
                $res['wrong_ean_MANUAL'][] = [$product->id, $product->ean];
            }


            // PN is EAN 13
            $products = Product::whereNull('ean')->whereNotNull('pn')->whereRaw('LENGTH(pn) = 13')
                ->get();

            $res['pn_is_ean_13_MANUAL'] = [];
            foreach ($products as $product) {
                if (is_numeric($product->pn)) {
                    $res['pn_is_ean_13_MANUAL'][] = [$product->id, $product->pn];
                }
            }


            // PN is EAN 12
            $products = Product::whereNull('ean')->whereNotNull('pn')->whereRaw('LENGTH(pn) = 12')
                ->get();

            $res['pn_is_ean_12_MANUAL'] = [];
            foreach ($products as $product) {
                if (is_numeric($product->pn)) {
                    $res['pn_is_ean_12_MANUAL'][] = [$product->id, $product->pn];
                }
            }


            // EAN OK && PN null
            $products = Product::whereNull('pn')
                ->whereNotNull('ean')
                ->whereNotNull('brand_id')
                ->whereRaw('LENGTH(ean) = 13')
                ->where('created_at', '>', Carbon::now()->subDays(60)->format('Y-m-d 00:00:00'))
                ->get();

            $res['ean_ok_pn_null'] = [];
            foreach ($products as $product) {

                if ($product_pn = Product::whereEan($product->ean)
                    ->whereNotNull('pn')
                    ->whereBrandId($product->brand_id)
                    ->first()) {

                    $product->pn = $product_pn->pn;
                    $product->save();
                    $product->refresh();
                }

                $res['ean_ok_pn_null'][] = [$product->id, $product->ean, $product->pn];
            }


            // EAN NULL -> SEARCH EAN IN OTHER PRODUCTS
            $products = Product::whereNull('ean')->whereNotNull('pn')->whereNotNull('brand_id')->where('stock', '>', 0)->get();
            $res['ean_null_search_ean'] = [];
            foreach ($products as $product) {
                if ($product->getMPEProductEan())
                    $res['ean_null_search_ean'][] = [$product->id, $product->ean];
            }


            // PRODUCT NO IMAGES -> SEARCH IMAGES IN OTHER PRODUCTS
            $products = Product::doesntHave('images')
                ->where('stock', '>', 0)
                ->where('created_at', '>', Carbon::now()->subDays(60)->format('Y-m-d 00:00:00'))
                ->get();

            $res['products_no_image_search_image'] = [];
            foreach ($products as $product) {
                if ($product->getMPEProductImages())
                    $res['products_no_image_search_image'][] = ['Image Found', $product->id];
                else
                    $res['products_no_image_search_image'][] = ['Image NOT Found', $product->id];
            }

            //$this->nullAndStorage(__METHOD__, $res);

            Log::channel('commands')->info('END upgrade:feeds: '.json_encode($res));
            $log_schedule->update(['ends_at' => now(), 'info' => mb_substr(json_encode($res), 0, 65534)]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'FeedsUpgrade', null);
        }
    }

}
