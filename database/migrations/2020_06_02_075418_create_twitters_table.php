<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTwittersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('twitters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('user_id', 64)->nullable();

            $table->index('name');
            $table->index('user_id');
        });

        Schema::table('receivers',function (Blueprint $table) {

            $table->unsignedBigInteger('twitter_id')->nullable();
            $table->foreign('twitter_id')
                ->references('id')
                ->on('twitters');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('twitters');
    }
}
