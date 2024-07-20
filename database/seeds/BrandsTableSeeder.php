<?php

use Illuminate\Database\Seeder;

class BrandsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('brands')->insert(array (
            'name' => 'Apple',
        ));

        \DB::table('brands')->insert(array (
            'name' => 'HP',
        ));


    }
}
