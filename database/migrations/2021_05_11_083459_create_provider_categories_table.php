<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProviderCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provider_categories', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('provider_id')->nullable();
            $table->foreign('provider_id')
                ->references('id')
                ->on('providers');

            $table->string('categoryId', 64)->nullable();
            $table->string('categoryL1')->nullable();
            $table->string('categoryL2')->nullable();
            $table->string('categoryL3')->nullable();
            $table->string('categoryL4')->nullable();
            $table->string('categoryL5')->nullable();
            $table->string('name')->nullable();
            $table->integer('display_order')->nullable();
            $table->boolean('enabled')->default(0);

            $table->index('categoryId');
            $table->index('categoryL1');
            $table->index('categoryL2');
            $table->index('categoryL3');
            $table->index('categoryL4');
            $table->index('categoryL5');
            $table->index('display_order');
            $table->index('enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('provider_categories');
    }
}
