<?php

use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('categories')->insert(array (
            'name' => 'Portátiles',
        ));

        \DB::table('categories')->insert(array (
            'name' => 'Monitores',
        ));

        \DB::table('categories')->insert(array (
            'name' => 'Tablets',
        ));

        \DB::table('categories')->insert(array (
            'name' => 'Ordenadores de Sobremesa',
        ));

        \DB::table('categories')->insert(array (
            'name' => 'Almacenamiento',
        ));

        \DB::table('categories')->insert(array (
            'name' => 'Red',
        ));

        \DB::table('categories')->insert(array (
            'name' => 'Componentes',
        ));

        \DB::table('categories')->insert(array (
            'name' => 'Accesorios',
        ));

        \DB::table('categories')->insert(array (
            'name' => 'Impresoras Multifuncionales',
        ));
    }
}
