<?php

namespace App\Console\Commands;


use App\LogSchedule;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Facades\App\Facades\Vox66Api as FacadesVox66Api;
use Throwable;


class VoxapiUpdate extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:voxapi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza las fichas y atributos de los productos con datos de la API de Vox66.';

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
            Log::channel('commands')->info('START update:voxapi');
            $log_schedule = LogSchedule::create(['type' => 'update:voxapi', 'name' => 'update:voxapi']);

            $res = FacadesVox66Api::update();

            Log::channel('commands')->info('END update:voxapi ' .json_encode($res));
            $log_schedule->update(['ends_at' => now(), 'info' => json_encode($res)]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'VoxapiUpdate', null);
        }
    }
}
