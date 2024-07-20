<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id')->nullable();
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');

            $table->string('code', 16)->nullable();
            $table->string('name', 64)->nullable(false);
            //$table->boolean('enabled')->default(1);       // Added in create_providers
            $table->string('store_url')->nullable();
            $table->string('header_url')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('marketShopId', 64)->nullable();
            $table->string('marketSellerId', 64)->nullable();
            $table->string('country', 64)->nullable();
            $table->string('site', 64)->nullable();
            $table->string('endpoint')->nullable();
            $table->string('app_name')->nullable();
            $table->string('app_version')->nullable();

            $table->text('client_id')->nullable();      // Client ID (Application ID)
            $table->text('client_secret')->nullable();  // Client Secret (Certification ID)
            $table->text('dev_id')->nullable();         // Developer ID
            $table->text('token')->nullable();
            $table->text('refresh')->nullable();

            $table->string('payment', 64)->nullable();      // payment profile
            $table->string('preparation', 64)->nullable();  // shipping preparation
            $table->string('shipping', 64)->nullable();     // shipping profile
            $table->string('return', 64)->nullable();       // return or service profile
            $table->string('channel', 64)->nullable();      // channel code
            $table->json('config')->nullable();

            $table->index('code');
            $table->index('name');
            $table->index('marketShopId');
            $table->index('marketSellerId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shops');
    }
}
