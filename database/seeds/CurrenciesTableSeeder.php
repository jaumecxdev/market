<?php

use Illuminate\Database\Seeder;

class CurrenciesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('currencies')->insert(array (
            'code' => 'EUR',
            'name' => 'Euro'
        ));

        \DB::table('currencies')->insert(array (
            'code' => 'USD',
            'name' => 'United States dollar'
        ));
    }
}
