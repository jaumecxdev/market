<?php

use Illuminate\Database\Seeder;


class NewUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = \App\User::where('email', '')->firstOrFail();
        $user->assignRole('user');

    }
}
