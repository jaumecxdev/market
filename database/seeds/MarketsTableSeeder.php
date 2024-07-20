<?php

use Illuminate\Database\Seeder;

class MarketsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $market_id = \DB::table('markets')->insertGetId(array (
            'code'  => 'amazon',
            'name'  => 'Amazon',
        ));
        \DB::table('shops')->insert(array (
            'market_id'  => $market_id,
            'name'  => 'Locura',
        ));

        $market2_id = \DB::table('markets')->insertGetId(array (
            'code'  => 'ae',
            'name'  => 'Aliexpress',
        ));
        \DB::table('shops')->insert(array (
            'market_id'  => $market2_id,
            'name'  => 'Locura',
        ));

        $market3_id = \DB::table('markets')->insertGetId(array (
            'code'  => 'ebay',
            'name'  => 'Ebay',
        ));
        \DB::table('shops')->insert(array (
            'market_id'  => $market3_id,
            'name'  => 'Locura',
        ));

        $market4_id = \DB::table('markets')->insertGetId(array (
            'code'  => 'worten',
            'name'  => 'Worten',
        ));
        \DB::table('shops')->insert(array (
            'market_id'  => $market4_id,
            'name'  => 'Locura',
        ));



    }
}
