<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParamPortsToShopProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->float('param_canon')->after('set_group')->default(0);
            $table->float('param_rappel')->after('param_canon')->default(0);
            $table->float('param_ports')->after('param_rappel')->default(0);

            $table->index('param_canon');
            $table->index('param_rappel');
            $table->index('param_ports');
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
