<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user_id = \DB::table('users')->insertGetId(array (
            'name' => 'mercado',
            'email' => '',
            'password' => \Hash::make(''),
            'created_at' => Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'email_verified_at' => Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ));
    }
}
