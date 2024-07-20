<?php

use Illuminate\Database\Seeder;

class LangsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('langs')->insert(array (
            'code' => 'es',
            'name' => 'Spanish',
        ));

        \DB::table('langs')->insert(array (
            'code'  => 'en',
            'name' => 'English',
        ));


    }
}
