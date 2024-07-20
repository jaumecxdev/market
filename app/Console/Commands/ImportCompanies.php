<?php

/* namespace App\Console\Commands;

use App\Imports\CompanyDDBBImport;
use App\Price;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportCompanies extends Command
{

    protected $signature = 'companies:import';


    protected $description = 'Divide la BBDD de compañias por sectores.';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::channel('commands')->info('START companies:import - Dividiendo BBDD');
        Excel::import(new CompanyDDBBImport(), 'imports/BBDDSPAIN 2020 MINI2 DISTRI.xlsx', null, \Maatwebsite\Excel\Excel::XLSX);
        Log::channel('commands')->info('END companies:import');
    }
}*/
