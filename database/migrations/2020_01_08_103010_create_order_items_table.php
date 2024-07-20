<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')
                ->references('id')
                ->on('orders');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
            $table->unsignedBigInteger('shop_product_id')->nullable();      // NO USE -> USE product_id
                $table->foreign('shop_product_id')
                    ->references('id')
                    ->on('shop_products');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies');

            $table->string('marketSku', 64)->nullable();
            $table->string('marketProductSku', 64)->nullable();
            $table->string('marketOrderId', 64)->nullable();
            $table->string('marketItemId', 64)->nullable();

            $table->string('title')->nullable();
            $table->string('info')->nullable();

            $table->integer('quantity')->default(0);

            $table->float('cost')->default(0);
            $table->float('price')->default(0);
            $table->float('tax')->default(0);
            $table->float('shipping_price')->default(0);
            $table->float('shipping_tax')->default(0);
            $table->float('bfit')->default(0);
            $table->float('mps_bfit')->default(0);
            $table->float('mp_bfit')->default(0);

            $table->index('marketSku');
            $table->index('marketProductSku');
            $table->index('marketOrderId');
            $table->index('bfit');
            $table->index('mps_bfit');
            $table->index('mp_bfit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
