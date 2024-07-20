<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 16)->nullable();
            $table->string('name', 64)->nullable(false);
            $table->string('product_url')->nullable();
            $table->string('order_url')->nullable();
            $table->string('ws', 64)->nullable();

            $table->boolean('pn_required')->default(1);
            $table->boolean('ean_required')->default(1);
            $table->boolean('market_category_required')->default(1);
            $table->boolean('images_required')->default(1);
            $table->boolean('attributes_required')->default(1);
            $table->json('config')->nullable();

            $table->index('code');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('markets');
    }
}
