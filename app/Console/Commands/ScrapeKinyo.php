<?php

namespace App\Console\Commands;

use App\LogSchedule;
use App\Product;
use App\Scrapes\KinyoScrape;
use App\Traits\HelperTrait;
use Illuminate\Console\Command;
use Throwable;

class ScrapeKinyo extends Command
{
    use HelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:kinyo {reference}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape Kinyo con Reference de producto y obtiene textos e imágenes.';

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
            // Artisan::call("scrape:kinyo", ['reference' => '20NN0026SP']);
            $reference = $this->argument('reference');
            $log_schedule = LogSchedule::create(['type' => 'scrape:kinyo', 'name' => 'scrape:kinyo '.$reference]);

            $product = Product::wherePn($reference)->first();
            $kinyo = new KinyoScrape($product);
            $kinyo_data = $kinyo->scrape($reference);

            print_r('Kinyo data: '.json_encode($kinyo_data));
            $log_schedule->update(['ends_at' => now(), 'info' => null]);

            return $kinyo_data;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, 'ScrapeKinyo', [$reference, $log_schedule]);
        }
    }

}
