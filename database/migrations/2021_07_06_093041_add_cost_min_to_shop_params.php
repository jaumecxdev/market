<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostMinToShopParams extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_params', function (Blueprint $table) {
            $table->float('cost_min')->nullable();
            $table->float('cost_max')->nullable();

            $table->index('cost_min');
            $table->index('cost_max');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_params', function (Blueprint $table) {
            //
        });
    }
}
