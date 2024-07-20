<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')
                ->references('id')
                ->on('orders');
            $table->unsignedBigInteger('order_item_id')->nullable();
                $table->foreign('order_item_id')
                    ->references('id')
                    ->on('order_items');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies');

            $table->boolean('fixed')->default(0);
            $table->float('cost')->default(0);
            $table->float('price')->default(0);
            $table->float('shipping_price')->default(0);
            $table->float('tax')->default(0);
            $table->float('bfit')->default(0);
            $table->float('mps_bfit')->default(0);
            $table->float('mp_bfit')->default(0);

            $table->boolean('charget')->default(0);
            $table->string('invoice', 64)->nullable();

            $table->timestamp('payment_at')->nullable();
            $table->timestamps();

            $table->index('cost');
            $table->index('price');
            $table->index('shipping_price');
            $table->index('bfit');
            $table->index('mps_bfit');
            $table->index('mp_bfit');
            $table->index('charget');
            $table->index('invoice');
            $table->index('payment_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_payments');
    }
}
