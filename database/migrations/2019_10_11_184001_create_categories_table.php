<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories');

            $table->string('code', 64)->nullable();
            $table->string('parent_code', 64)->nullable();
            $table->string('name')->nullable(false);
            $table->string('seo_name')->nullable();
            $table->string('path', 1024)->nullable();
            $table->integer('level')->nullable();
            $table->boolean('leaf')->default(0);

            $table->index('name');
            $table->index('code');
            $table->index('parent_code');
            $table->index('level');
            $table->index('leaf');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
}
