<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /*******   WEEKLY  ********/

        // Elimina registros de la tabla prices. Deja los últimos 7 días - Cada Domingo a las 3:00
        $schedule->command('reset:logs')->weeklyOn(0, '1:00');


        /*******    DAILY  ********/

        //$schedule->command('telescope:prune --hours=48')->dailyAt('00:00');
        // Syncronize Daily Scheduled Shop Params
        //$schedule->command('sync:shopparams')->dailyAt('02:00');
        // Update Products by Vox66 data && Update online Marketplace Shops Products
        //$schedule->command('update:vox')->dailyAt('03:00');
        //$schedule->command('update:vox')->dailyAt('12:30');
        // Resets Stocks to 0 on Marketplaces on Products NOT in local
        //$schedule->command('resets:online')->dailyAt('04:00');

        // Syncs
        $schedule->command('update:voxapi')->dailyAt('05:00');
        $schedule->command('copy:attributes')->dailyAt('05:20');

        $schedule->command('upgrade:feeds')->dailyAt('7:00');
        $schedule->command('clean:feeds')->dailyAt('7:10');
        $schedule->command('sync:allparams')->dailyAt('8:00');


        /*******    CADA 5 MINUTS  ********/

        // Send Orders notifications TO suppliers & Admins
        $schedule->command('send:notifications')->cron('*/5 5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *');

        //$schedule->command('get:buybox amazonsp mpeamazon')->cron('*/5 5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *');
        //$schedule->command('get:buybox carrefour mpecarrefour')->cron('*/5 5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *');
        //$schedule->command('get:buybox pccompo mpepccompo')->cron('*/5 5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *');
        //$schedule->command('get:buybox worten mpeworten')->cron('*/5 5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *');
        $schedule->command('get:buybox perfumes regalaperfumes')->cron('5 5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *');


        /*** GET PRODUCTS FROM SUPPLIERS ***/


        // Update Prices & Stocks Supplier products - Cada hora desde 7h hasta 23h
        //$schedule->command('get:products idiomund')->cron('5 4,6,10 * * *');
        //$schedule->command('update:products idiomund')->cron('5 7,8,9,11,12,13,14,15,16,17,18 * * *');

        $schedule->command('get:products regalaperfumes')->cron('10 4,6,10 * * *');
        $schedule->command('update:products regalaperfumes')->cron('10 7,8,9,11,12,13,14,15,16,17,18 * * *');

        /* $schedule->command('get:products mcr')->cron('7 4,6,10 * * *');
        $schedule->command('update:products mcr')->cron('7 7,8,9,11,12,13,14,15,16,17,18 * * *');

        $schedule->command('get:products megasur')->cron('8 4,6,10 * * *');           // Cada 2h màxim
        $schedule->command('update:products megasur')->cron('8 8,12,14,16,18 * * *');
        $schedule->command('get:products depau')->cron('9 4,6,10 * * *');
        $schedule->command('update:products depau')->cron('9 7,8,9,11,12,13,14,15,16,17,18 * * *');
        $schedule->command('get:products globomatik')->cron('10 4,6,10 * * *');
        $schedule->command('update:products globomatik')->cron('10 7,8,9,11,12,13,14,15,16,17,18 * * *');
        $schedule->command('get:products sce')->cron('11 4,6,10 * * *');
        $schedule->command('update:products sce')->cron('11 7,8,9,11,12,13,14,15,16,17,18 * * *');
        $schedule->command('get:products desyman')->cron('12 4,6,10 * * *');
        $schedule->command('update:products desyman')->cron('12 7,8,9,11,12,13,14,15,16,17,18 * * *'); */
        // Vinzeo: FIRST Stocks & Prices THEN Products
        /* $schedule->command('update:products vinzeo')->cron('13 4,6,8,10,12,14,15,16,17,18 * * *');
        $schedule->command('get:products vinzeo')->cron('13 7,9,11,13 * * *');
        $schedule->command('get:products esprinet')->cron('14 4,6,10 * * *');
		$schedule->command('update:products esprinet')->cron('14 7,8,9,11,12,13,14,16,16,17,18 * * *');
        $schedule->command('get:products ingram')->cron('15 4,6,10 * * *');
        $schedule->command('update:products ingram')->cron('15 7,8,9,11,12,13,14,15,16,17,18 * * *');
        $schedule->command('get:products dmi')->cron('16 4,6,10 * * *');
        $schedule->command('update:products dmi')->cron('16 7,8,9,11,12,13,14,15,16,17,18 * * *');
        $schedule->command('get:products aseuropa')->cron('17 4,6,10 * * *');
        $schedule->command('update:products aseuropa')->cron('17 7,8,9,11,12,13,14,15,16,17,18 * * *'); */
        //$schedule->command('get:products grutinet')->cron('18 4,6,10 * * *');
        //$schedule->command('update:products grutinet')->cron('18 7,8,9,11,12,13,14,15,16,17,18 * * *');
        //$schedule->command('get:products infortisa')->cron('19 4,6,10 * * *');
        //$schedule->command('update:products infortisa')->cron('19 7,8,9,11,12,13,14,15,16,17,18 * * *');
        // NO PUBLISH ON MARKETPLACES
        //$schedule->command('get:products speedler')->cron('20 4,6,10 * * *');
        //$schedule->command('update:products speedler')->cron('20 7,8,9,11,12,13,14,15,16,17,18 * * *');

        //$schedule->command('get:products techdata')->cron('16 6,8,12 * * 1,2,3,4,5,6');           // NO SUNDAYS
        //$schedule->command('update:products techdata')->cron('16 7,9,10,11,13,14,15,16,17,18 * * 1,2,3,4,5,6'); // NO SUNDAYS
        //$schedule->command('get:products regalasexo')->cron('22 6,12 * * *');
        //$schedule->command('update:products regalasexo')->cron('22 14,18 * * *');
        //$schedule->command('get:products olahom')->cron('42 6,10 * * *');
        //$schedule->command('update:products olahom')->cron('42 14,18 * * *');


        /*** POST NEW PRODUCTS ***/

        /* $schedule->command('post:newproducts pceducacion pcedu')->cron('30 6,10,12 * * *');
        $schedule->command('post:newproducts pccompo mpepccompo')->cron('30 5,9,11 * * *');
        $schedule->command('post:newproducts worten mpeworten')->cron('35 5,9,11 * * *');
        $schedule->command('post:newproducts carrefour mpecarrefour')->cron('40 5,9,11 * * *');
        $schedule->command('post:newproducts amazonsp mpeamazon')->cron('45 5,9,11 * * *'); */


        /*** UPDATE PRICES & STOCKS TO MARKETPLACES SHOPS ***/

        // Obtiene jobs y Actualiza precios y stocks - Cada hora, desde las 7h hasta las 23h
        // Variatons in Ebay NO work with post:prices -> post:updated
        //$schedule->command('repricing')->cron('35 2,6,8,10,12,14,16,17,18,19,20,21,22,23 * * *');


        //$schedule->command('post:prices perfumes regalaperfumes')->cron('20 2,6,8,10,12,14,16,18,20,22,23 * * *');


        /* $schedule->command('post:prices pceducacion pcedu')->cron('40 9,11,13,15 * * *');
        $schedule->command('post:prices pccompo mpepccompo')->cron('41 2,6,8,10,12,14,16,18,20,22,23 * * *');
        $schedule->command('post:prices worten mpeworten')->cron('42 2,6,8,10,12,14,16,18,20,22,23 * * *');
        $schedule->command('post:prices carrefour mpecarrefour')->cron('43 2,6,8,10,12,14,16,18,20,22,23 * * *');
        $schedule->command('post:prices ae mpeae')->cron('44 2,6,8,10,12,14,16,18,20,22,23 * * *');
        $schedule->command('post:prices amazonsp mpeamazon')->cron('45 2,6,8,10,12,14,16,18,20,22,23 * * *'); */



        //$schedule->command('post:prices lbdc mpelbdc')->cron('45 2,6,8,10,12,14,16,18,20,22,23 * * *');
        //$schedule->command('post:prices allxyou mpeallxyou')->cron('46 2,6,8,10,12,14,16,18,20,22,23 * * *');
        //$schedule->command('post:prices ae locura')->cron('46 7,10,13,16 * * *');
        //$schedule->command('post:prices worten regalaworten')->cron('52 7,8,9,11,15,19 * * *');
        //$schedule->command('post:prices ae olaae')->cron('54 7,11,15,19 * * *');


        /*** GET ORDERS FROM MARKETPLACES SHOPS ***/


        // Get Orders - Cada media hora desde 7h hasta 23h


        //$schedule->command('get:orders perfumes regalaperfumes')->cron('0,15,30,45 5,8,9,10,11,12,13,14,15,16,18,21 * * *');


        /* $schedule->command('get:orders ae mpeae')->cron('0,15,30,45 5,8,9,10,11,12,13,14,15,16,18,21 * * *');
        $schedule->command('get:orders pccompo mpepccompo')->cron('1,16,31,46 5,8,9,10,11,12,13,14,15,16,18,21 * * *');
        $schedule->command('get:orders worten mpeworten')->cron('2,17,32,47 5,8,9,10,11,12,13,14,15,16,18,21 * * *');
        $schedule->command('get:orders carrefour mpecarrefour')->cron('3,18,33,48 5,8,9,10,11,12,13,14,15,16,18,21 * * *');
        $schedule->command('get:orders amazonsp mpeamazon')->cron('4,34 5,8,9,10,11,12,13,14,15,16,18,21 * * *');

        $schedule->command('get:orders ae donbustoae')->cron('6 7,11,15,19 * * *'); */

        //$schedule->command('get:orders ae locura')->cron('5,20,35,50 5,8,9,10,11,12,13,14,15,16,17,18,21 * * *');
        //$schedule->command('get:orders pceducacion pcedu')->cron('8 7,11,15,19 * * *');
        //$schedule->command('get:orders worten regalaworten')->cron('5 7,11,15,19 * * *');
        //$schedule->command('get:orders ae olaae')->cron('11 7,11,15,19 * * *');
        //$schedule->command('get:orders amazon donbustoamazon')->cron('13 7,11,15,19 * * *');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
