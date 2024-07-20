<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProviderAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provider_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('provider_id')->nullable();
            $table->foreign('provider_id')
                ->references('id')
                ->on('providers');

            $table->unsignedBigInteger('provider_category_id')->nullable();
            $table->foreign('provider_category_id')
                ->references('id')
                ->on('provider_categories');

            $table->string('attributeId', 64)->nullable();
            $table->string('attributeName')->nullable();
            $table->string('name')->nullable();
            $table->integer('display_order')->nullable();
            $table->boolean('enabled')->default(0);

            $table->index('attributeId');
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
        Schema::dropIfExists('provider_attributes');
    }
}
