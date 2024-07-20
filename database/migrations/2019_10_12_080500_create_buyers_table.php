<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuyersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buyers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->foreign('shipping_address_id')
                ->references('id')
                ->on('addresses');
            $table->unsignedBigInteger('billing_address_id')->nullable();
            $table->foreign('billing_address_id')
                ->references('id')
                ->on('addresses');
            $table->unsignedBigInteger('market_id')->nullable();
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');

            $table->string('marketBuyerId', 64)->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('company_name')->nullable();
            $table->string('tax_region')->nullable();
            $table->string('tax_name')->nullable();
            $table->string('tax_value')->nullable();

            $table->index('marketBuyerId');
            $table->index('name');
            $table->index('email');
            $table->index('phone');
            $table->index('company_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('buyers');
    }
}
