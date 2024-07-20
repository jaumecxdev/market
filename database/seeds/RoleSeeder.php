<?php

use Illuminate\Database\Seeder;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role_admin = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $role_owner = \Spatie\Permission\Models\Role::create(['name' => 'owner']);
        $role_user = \Spatie\Permission\Models\Role::create(['name' => 'user']);

        $user = \App\User::findOrFail(1);
        $user->assignRole('admin');

        $user = \App\User::firstOrCreate([
            'name' => 'owner',
            'email' => 'owner@owner.com',
        ],[
            'password' => \Hash::make(''),
            'created_at' => Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'email_verified_at' => Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        $user->assignRole('owner');

        $user = \App\User::firstOrCreate([
            'name' => 'user',
            'email' => 'user@user.com',
        ],[
            'password' => \Hash::make(''),
            'created_at' => Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'email_verified_at' => Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        $user->assignRole('user');

    }
}
