<?php

use Illuminate\Database\Seeder;

class StatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('statuses')->insert(array (
            'name' => 'Nuevo',
            'type' => 'product'
        ));

        \DB::table('statuses')->insert(array (
            'name' => 'Usado',
            'type' => 'product'
        ));

        \DB::table('statuses')->insert(array (
            'name' => 'Remanufacturado',
            'type' => 'product'
        ));

        \DB::table('statuses')->insert(array (
            'name' => 'Pendiente',
            'type' => 'order'
        ));

        \DB::table('statuses')->insert(array (
            'name' => 'Preparado',
            'type' => 'order'
        ));

        \DB::table('statuses')->insert(array (
            'name' => 'Enviado',
            'type' => 'order'
        ));

        \DB::table('statuses')->insert(array (
            'name' => 'Parcial',
            'type' => 'order'
        ));

        \DB::table('statuses')->insert(array (
            'name' => 'Finalizado',
            'type' => 'order'
        ));

        \DB::table('statuses')->insert(array (
            'name' => 'Cancelado',
            'type' => 'order'
        ));

    }
}
