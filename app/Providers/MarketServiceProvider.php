<?php

namespace App\Providers;

use App\Libraries\EbayWS;
use App\Libraries\MarketWSInterface;
use App\Shop;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;


class MarketServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
//        $this->app->bind(MarketWSInterface::class, EbayWS::class);

        /*$this->app->singleton(MarketWSInterface::class, function ($app) use (Request $request) {

            //return new EbayWS(config('riak'));
        });*/

        //$this->app->call([$this, 'registerMarket']);
        /*$this->app->singleton(MarketWSInterface::class, function ($app) {


        });*/
    }


    /* public function registerMarket(Request $request)
    {
        $this->app->singleton(MarketWSInterface::class, function ($app) use ($request) {
            $ws = 'App\Libraries\\' .$request->shop->market->ws;
            return new $ws($request->shop);
              // $app->url->full()
            // I use $app->make so processor classes dependancies gets resolved and injected.
//            if ($request->type == 'typeone') return $app->make(TypeOneProcessor::class);
//            if ($request->type == 'typetwo') return $app->make(TypeTwoProcessor::class);

        });
    }
 */


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
