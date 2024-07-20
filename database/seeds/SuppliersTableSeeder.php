<?php

use Illuminate\Database\Seeder;

class SuppliersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('suppliers')->insert(array (
            'code'                  => 'idiomund',
            'name'                  => 'Idiomund',
            'type_import'           => 'api',
            'primary_key_field'     => 'id_product',
            'brand_field'           => 'manufacturer',
            'category_field'        => 'category_name',
            'type_field'            => null,
            'status_field'          => 'active',
            'name_field'            => 'name',
            'model_field'           => 'description_short',
            'supplierSku_field'     => 'id_product',
            'pn_field'              => 'reference',
            'ean_field'             => 'ean13',
            'upc_field'             => null,
            'isbn_field'            => null,
            'cost_field'            => 'wholesale_price',
            'stock_field'           => 'quantity'
        ));

        \DB::table('suppliers')->insert(array (
            'code'  => 'ingram',
            'name' => 'Ingrammicro',
            'type_import'   => 'file',
        ));

        \DB::table('suppliers')->insert(array (
            'code' => 'presta',
            'name' => 'Prestashop',
            'type_import'   => 'file',
        ));

    }
}
