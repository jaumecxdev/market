<?php

namespace App\Console\Commands;

use App\LogSchedule;
use App\Scrapes\AmazonScrape;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;


class ScrapeAmazon extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:amazon {reference}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape Amazon con Reference de producto y obtiene el precio.';

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
            // Artisan::call("scrape:amazon", ['reference' => '0194850818370']);
            $reference = $this->argument('reference');
            $log_schedule = LogSchedule::create(['type' => 'scrape:amazon', 'name' => 'scrape:amazon '.$reference]);

            $url = 'https://www.amazon.es/s?k='.$reference;
            $amazon = new AmazonScrape();
            $amazon_data = $amazon->scrape($reference);

            print_r('Amazon data: '.json_encode($amazon_data));
            $log_schedule->update(['ends_at' => now(), 'info' => null]);

            return $amazon_data;

        } catch (\Throwable $th) {
            return $this->nullWithErrors($th, 'ScrapeAmazon', [$reference, $log_schedule]);
        }
    }

}
