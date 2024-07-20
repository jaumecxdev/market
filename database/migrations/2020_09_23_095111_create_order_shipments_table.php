<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderShipmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_shipments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id')->nullable();
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops');
            $table->unsignedBigInteger('market_carrier_id')->nullable();
            $table->foreign('market_carrier_id')
                ->references('id')
                ->on('market_carriers');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')
                ->references('id')
                ->on('orders');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->foreign('order_item_id')
                ->references('id')
                ->on('order_items');

            $table->boolean('full')->default(1);
            $table->unsignedBigInteger('quantity')->default(0);
            $table->string('tracking')->nullable();
            $table->string('desc')->nullable();

            $table->timestamps();

            $table->index('tracking');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_shipments');
    }
}
