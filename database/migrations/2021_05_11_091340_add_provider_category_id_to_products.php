<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderCategoryIdToProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('provider_category_id')->nullable();
            $table->foreign('provider_category_id')
                ->references('id')
                ->on('provider_categories');
        });

        Schema::table('shop_products', function (Blueprint $table) {
            $table->unsignedBigInteger('provider_category_id')->nullable();
            $table->foreign('provider_category_id')
                ->references('id')
                ->on('provider_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
