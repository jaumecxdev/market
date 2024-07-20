<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // AFEGIR MARKET_ID
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id')->nullable();
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops');
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->foreign('buyer_id')
                ->references('id')
                ->on('buyers');
            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->foreign('shipping_address_id')
                ->references('id')
                ->on('addresses');
            $table->unsignedBigInteger('billing_address_id')->nullable();
            $table->foreign('billing_address_id')
                ->references('id')
                ->on('addresses');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->foreign('status_id')
                ->references('id')
                ->on('statuses');
            $table->unsignedBigInteger('type_id')->nullable();
            $table->foreign('type_id')
                ->references('id')
                ->on('types');

            $table->string('marketOrderId', 64)->nullable();
            $table->string('SellerId', 64)->nullable();
            $table->string('SellerOrderId', 64)->nullable();
            $table->string('info')->nullable();

            $table->float('price')->default(0);
            $table->float('tax')->default(0);
            $table->float('shipping_price')->default(0);
            $table->float('shipping_tax')->default(0);
            $table->boolean('notified')->default(0);
            $table->boolean('notified_updated')->default(0);

            $table->timestamps();

            $table->index('marketOrderId');
            $table->index('SellerId');
            $table->index('SellerOrderId');
            $table->index('price');
            $table->index('updated_at');
            $table->index('notified');
            $table->index('notified_updated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
