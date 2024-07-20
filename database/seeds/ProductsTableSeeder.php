<?php

use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $product_id = \DB::table('products')->insertGetId(array (
            'ready' => true,
            'supplier_id' => \App\Supplier::find(1)->id,
            'brand_id' => \App\Brand::find(1)->id,
            'category_id' => \App\Category::find(1)->id,
            'name' => 'Portátil Acer i5',
            'keywords' => 'portatil acer i5',

            'cost' => 400,
            'price' => 450,
            'stock' => 35,

            'shortdesc' => 'Texto corto',
            'longdesc' => 'Texto largo',

            'created_at' => now(),
            'updated_at' => now(),
        ));

        \DB::table('images')->insert(array(
            'product_id' => $product_id,
            'type' => 1,
            'src' => 'acer1.jpg'
        ));
        \DB::table('images')->insert(array(
            'product_id' => $product_id,
            'type' => 1,
            'src' => 'acer2.jpg'
        ));
        \DB::table('images')->insert(array(
            'product_id' => $product_id,
            'type' => 1,
            'src' => 'acer3.jpg'
        ));


        $product_id = \DB::table('products')->insert(array (
            'ready' => true,
            'supplier_id' => \App\Supplier::find(2)->id,
            'brand_id' => \App\Brand::find(1)->id,
            'category_id' => \App\Category::find(2)->id,
            'name' => 'Monitor Acer i5',
            'keywords' => 'Monitor acer i5',

            'cost' => 400,
            'price' => 450,
            'stock' => 35,

            'shortdesc' => 'Texto corto',
            'longdesc' => 'Texto largo',

            'created_at' => now(),
            'updated_at' => now(),
        ));

        $product_id = \DB::table('products')->insert(array (
            'ready' => true,
            'supplier_id' => \App\Supplier::find(3)->id,
            'brand_id' => \App\Brand::find(1)->id,
            'category_id' => \App\Category::find(3)->id,
            'name' => 'PC Acer i5',
            'keywords' => 'PC acer i5',

            'cost' => 400,
            'price' => 450,
            'stock' => 35,

            'shortdesc' => 'Texto corto',
            'longdesc' => 'Texto largo',

            'created_at' => now(),
            'updated_at' => now(),
        ));


    }
}
