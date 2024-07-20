<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelegramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegrams', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name')->nullable();
            $table->string('invite_code')->nullable();
            $table->string('user_id', 64)->nullable();
            $table->string('chat_id', 64)->nullable();

            $table->index('name');
            $table->index('invite_code');
            $table->index('user_id');
            $table->index('chat_id');
        });

        Schema::table('receivers',function (Blueprint $table) {

            $table->unsignedBigInteger('telegram_id')->nullable();
            $table->foreign('telegram_id')
                ->references('id')
                ->on('telegrams');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telegrams');
    }
}
