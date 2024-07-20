<?php

namespace App\Console\Commands;


use App\LogSchedule;
use App\Supplier;
use App\Traits\HelperTrait;
use Facades\App\Facades\MpeImport as FacadesMpeImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;


class SupplierImport extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:supplier {supplier_code} {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene los productos del proveedor {supplier_code} {type}.';

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
            $type = $this->argument('type');
            $msg = 'START import:supplier ' .$supplier_code.' '.$type;
            $this->info($msg);
            Log::channel('commands')->info($msg);
            $log_schedule = LogSchedule::create(['type' => 'import:supplier', 'name' => 'import:supplier '.$supplier_code.' '.$type]);

            $supplier = Supplier::where('code', $supplier_code)->first();
            if (!$supplier) {
                $msg = 'ERROR import:supplier ' .$supplier_code. ' - No se ha encontrado el proveedor';
                $this->error($msg);
                Log::channel('commands')->error($msg);
                $log_schedule->update(['info' => $msg]);
                return $this->nullAndStorage('SupplierImport', $supplier_code);
            }

            $log_schedule->update(['supplier_id' => $supplier->id]);

            $msg = 'import:supplier ' .$supplier_code.' '.$type. ' - Obteniendo.';
            $this->info($msg);
            Log::channel('commands')->info($msg);
            $log_schedule->update(['info' => $msg]);

            // 'https://www.megasur.es/download/file-rate?file=csv-prestashop&u=302133&hash=ee250da3278921d93d5137feed610f11';
            $importSupplierClass = 'App\\Imports\\Import'.ucwords($supplier_code);      // ImportMegasur
            $this->info('Class: ' .$importSupplierClass);

            $uri = ($type == 'products') ? $importSupplierClass::URI_PRODUCTS : $importSupplierClass::URI_OFFERS;
            $this->info('URI: ' .$uri);

            $header_rows = $importSupplierClass::FORMATS[$type]['header_rows'];
            $directory = 'supplier/'.$supplier_code.'/'.$type.'/';                       // 'supplier/megasur/products/';
            $this->info('Dir: ' .$directory);

            $filename = date('Y-m-d_H'). '_'.$type.'.csv';
            $file_rows = FacadesMpeImport::getRowsUri($uri, $header_rows, $directory, $filename);

            $this->info('END import:supplier ' .$supplier_code.' '.$type.' - '.json_encode($file_rows));
            Log::channel('commands')->info('END import:supplier ' .$supplier_code.' '.$type.' - '.json_encode($file_rows));
            return $this->nullAndStorage('SupplierImport', $supplier_code);

            $res = $importSupplierClass::$type($supplier, $file_rows);

            $msg = 'END import:supplier ' .$supplier_code.' '.$type;
            $this->info($msg);
            Log::channel('commands')->info($msg);
            $log_schedule->update(['ends_at' => now(), 'info' => $res]);

            return $res;

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, 'GoogleImport', $msg);
        }
    }





}
