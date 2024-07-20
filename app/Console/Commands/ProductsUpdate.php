<?php

namespace App\Console\Commands;

use App\Libraries\SupplierWS;
use App\LogSchedule;
use App\Supplier;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductsUpdate extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:products {supplier_code=idiomund}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los precios y stocks de los productos del proveedor {supplier_code}';

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
            $supplier_code = $this->argument('supplier_code');
            Log::channel('commands')->info('START update:products ' .$supplier_code);
            $log_schedule = LogSchedule::create(['type' => 'update:products', 'name' => 'update:products '.$supplier_code]);

            $supplier = Supplier::where('code', $supplier_code)->first();
            if (!$supplier) {
                $res = 'ERROR update:products ' .$supplier_code. ' - No se ha encontrado el proveedor';
                $log_schedule->update(['info' => $res]);
                Log::channel('commands')->error($res);
                return $this->nullAndStorage('ProductsUpdate', $supplier_code);
            }

            $log_schedule->update(['supplier_id' => $supplier->id]);

            Log::channel('commands')->info('update:products ' .$supplier_code. ' - Obteniendo productos');
            $ws = SupplierWS::getSupplierWS($supplier);
            if ($ws) {
                $res = $ws->getPricesStocks();
                Log::channel('commands')->info('update:products ' .$supplier_code. ' - Result: ' .json_encode($res));
            }
            else {
                $res = 'ERROR NO WS';
                Log::channel('commands')->alert('update:products ' .$supplier_code. ' - '.$res);
            }

            Log::channel('commands')->info('END update:products ' .$supplier_code);
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'ProductsUpdate', $supplier_code);
        }
    }
}
