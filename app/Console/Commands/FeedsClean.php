<?php

namespace App\Console\Commands;


use App\LogSchedule;
use App\Market;
use App\Product;
use App\Shop;
use App\Supplier;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;


class FeedsClean extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:feeds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina productos duplicados del mismo proveedor y elimina imagenes huerfanas sin producto.';

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
            Log::channel('commands')->info('START clean:feeds');
            $log_schedule = LogSchedule::create(['type' => 'clean:feeds', 'name' => 'clean:feeds']);
            $res = [];

            // REMOVE DUPLICATED PRODUCTS SAME SUPPLIER BY PN & BRAND
            $res['removed_duplicated_by_pn_brand_MANUAL'] = [];
            $duplicateds = Product::select(
                    'supplier_id',
                    'pn',
                    'brand_id',
                    DB::raw('count(pn) as pn_count'),
                    DB::raw('count(supplier_id) as supplier_id_count'),
                    DB::raw('count(brand_id) as brand_id_count')
                )
                ->whereNotNull('pn')
                ->groupBy(['supplier_id', 'pn', 'brand_id'])
                ->havingRaw('pn_count > 1')
                ->get();

            foreach ($duplicateds as $duplicated) {

                // Only remove one -> NEXT IF ALREADY DUPLICATED -> remove other one
                if ($to_remove = Product::whereSupplierId($duplicated->supplier_id)
                    ->wherePn($duplicated->pn)
                    ->whereBrandId($duplicated->brand_id)
                    ->whereStock(0)
                    ->first()) {

                    $res['removed_duplicated_by_pn_brand_MANUAL'][] = [$to_remove->id, $duplicated->supplier_id, $duplicated->pn, $duplicated->brand_id];
                    //$to_remove->deleteSecure();
                }
            }


            // REMOVE DUPLICATED PRODUCTS SAME SUPPLIER BY EAN
            $res['removed_duplicated_by_ean_MANUAL'] = [];
            $duplicateds = Product::select(
                'supplier_id',
                'ean',
                DB::raw('count(ean) as ean_count'),
                DB::raw('count(supplier_id) as supplier_id_count')
            )
            ->whereNotNull('ean')
            ->groupBy(['supplier_id', 'ean'])
            ->havingRaw('ean_count > 1')
            ->get();

            foreach ($duplicateds as $duplicated) {

                // Only remove one -> NEXT IF ALREADY DUPLICATED -> remove other one
                if ($to_remove = Product::whereSupplierId($duplicated->supplier_id)
                    ->whereEan($duplicated->ean)
                    ->whereStock(0)
                    ->first()) {

                    $res['removed_duplicated_by_ean_MANUAL'][] = [$to_remove->id, $duplicated->supplier_id, $duplicated->ean];
                    //$to_remove->deleteSecure();
                }
            }


            // REMOVE IMAGES WITH NO PRODUCT
            $images_url = Storage::directories('public/img/');
            $res['deleted_images'] = [];
            foreach ($images_url as $image_url) {
                $product_id = intval(substr($image_url, 11, strlen($image_url)));
                if (!$product = Product::find($product_id)) {
                    $res['deleted_images'][] = $image_url;
                    Storage::delete($image_url);
                }

            }


            // REMOVE PRODUCTS STOCK 0 WITH NOT ORDERS, SHOP_PRODUCTS OLDER THAN ONE YEAR
            $products = Product::doesntHave('order_items')
                ->doesntHave('shop_products')
                ->doesntHave('shop_filters')
                ->doesntHave('shop_params')
                ->whereStock(0)
                ->where('created_at', '<', now()->addDays(-100)->format('Y-m-d H:i:s'))
                ->get();

            $res['deleted_products_stock_0_MANUAL'] = [];
            foreach ($products as $product) {
                $product_id = $product->id;
                if ($product->deleteSecure())
                    $res['deleted_products_stock_0_MANUAL'][] = $product_id;
            }

            Log::channel('commands')->info('END clean:feeds: '.json_encode($res));
            $log_schedule->update(['ends_at' => now(), 'info' => $duplicateds->count() + count($images_url) + $products->count()]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'FeedsClean', null);
        }
    }

}
