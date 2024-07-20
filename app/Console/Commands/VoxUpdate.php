<?php

namespace App\Console\Commands;

use App\Libraries\Ivox66WS;
use App\LogSchedule;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class VoxUpdate extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:vox';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza las fichas de los productos con datos de Vox66 y los de las Tiendas de los Marketplaces.';

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
            Log::channel('commands')->info('START update:vox');
            $log_schedule = LogSchedule::create(['type' => 'update:vox', 'name' => 'update:vox']);

            //$ws = new Ivox66WS();
            //$res = $ws->update();

            //Log::channel('commands')->info('END update:vox ' .$res. ' products updated.');
            //$log_schedule->update(['ends_at' => now(), 'info' => $res]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'VoxUpdate', null);
        }
    }
}
