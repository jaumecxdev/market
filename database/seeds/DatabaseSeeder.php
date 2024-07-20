<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UsersTableSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(SuppliersTableSeeder::class);
        $this->call(MarketsTableSeeder::class);
        $this->call(CurrenciesTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(StatusesTableSeeder::class);
        $this->call(LangsTableSeeder::class);
        $this->call(DictionaryColorsTableSeeder::class);
    }
}
