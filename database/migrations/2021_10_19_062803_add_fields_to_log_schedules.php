<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToLogSchedules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_schedules', function (Blueprint $table) {

            $table->string('type', 64)->after('id')->nullable();

            $table->unsignedBigInteger('shop_id')->after('id')->nullable();
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops');

            $table->unsignedBigInteger('market_id')->after('id')->nullable();
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');

            $table->unsignedBigInteger('supplier_id')->after('id')->nullable();
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_schedules', function (Blueprint $table) {
            //
        });
    }
}
