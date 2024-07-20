<?php

namespace App\Console\Commands;


use App\LogSchedule;
use App\Supplier;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupplierParamsSync extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:supplierparams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza los parámetros de todos los proveedores en todos los parámetros de todas las tiendas.';

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
            Log::channel('commands')->info('START sync:supplierparams');
            $log_schedule = LogSchedule::create(['type' => 'sync:supplierparams', 'name' => 'sync:supplierparams']);

            $res = [];
            $suppliers = Supplier::all();
            foreach ($suppliers as $supplier)
                $res[] = $supplier->syncParams();

            Log::channel('commands')->info('END sync:supplierparams: '.json_encode($res));
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'SupplierParamsSync', null);
        }
    }

}
