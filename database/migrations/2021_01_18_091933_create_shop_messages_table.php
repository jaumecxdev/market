<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id')->nullable();
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')
                ->references('id')
                ->on('orders');
            $table->unsignedBigInteger('type_id')->nullable();
                $table->foreign('type_id')
                    ->references('id')
                    ->on('types');

            $table->string('messageId', 64)->nullable();
            $table->string('subject')->nullable();
            $table->string('message')->nullable(false);
            $table->string('seller', 64)->nullable();
            $table->string('buyer', 64)->nullable();

            $table->text('attachments')->nullable();

            $table->index('seller');
            $table->index('buyer');

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
        Schema::dropIfExists('shop_messages');
    }
}
