<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewParamsToShopProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_products', function (Blueprint $table) {
            // 'param_mps_fee',
            $table->float('param_mps_fee')->default(0);
            $table->index('param_mps_fee');
        });

        Schema::table('shop_params', function (Blueprint $table) {
            // 'market_category_id', 'root_category_id', mps_fee
            $table->unsignedBigInteger('root_category_id')->nullable();
            $table->foreign('root_category_id')
                ->references('id')
                ->on('root_categories');

            $table->unsignedBigInteger('market_category_id')->nullable();
            $table->foreign('market_category_id')
                ->references('id')
                ->on('market_categories');

            $table->float('mps_fee')->default(0);
            $table->index('mps_fee');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // mps_bfit
            $table->float('mps_bfit')->default(0);
            $table->index('mps_bfit');
        });

        Schema::table('prices', function (Blueprint $table) {
            // mps_bfit
            $table->float('mps_bfit')->default(0);
            $table->index('mps_bfit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_products', function (Blueprint $table) {
            //
        });
    }
}
