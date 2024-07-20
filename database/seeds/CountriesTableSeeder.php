<?php

use Illuminate\Database\Seeder;

class CountriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('countries')->insert(array (
            'code' => 'ES',
            'name' => 'Spain'
        ));

        \DB::table('countries')->insert(array (
            'code' => 'US',
            'name' => 'United States America'
        ));

        \DB::table('countries')->insert(array (
            'code' => 'FR',
            'name' => 'France'
        ));
    }
}
