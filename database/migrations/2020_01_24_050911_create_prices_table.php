<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
            /* $table->unsignedBigInteger('shop_product_id')->nullable();
            $table->foreign('shop_product_id')
                ->references('id')
                ->on('shop_products'); */
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops');

            $table->string('marketProductSku', 64)->nullable();
            $table->string('name')->nullable();         // operation description
            $table->float('cost')->default(0);
            $table->float('price')->default(0);
            $table->unsignedBigInteger('stock')->default(0);
            $table->float('bfit')->default(0);
            $table->float('mps_bfit')->default(0);
            $table->float('mp_bfit')->default(0);

            $table->index('marketProductSku');
            $table->index('name');
            $table->index('cost');
            $table->index('price');
            $table->index('stock');
            $table->index('bfit');
            $table->index('mps_bfit');
            $table->index('mp_bfit');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prices');
    }
}
