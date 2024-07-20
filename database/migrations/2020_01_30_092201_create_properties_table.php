<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_attribute_id')->nullable();
            $table->foreign('market_attribute_id')
                ->references('id')
                ->on('market_attributes');

            $table->string('name')->nullable();     // null | value | customValue | unit
            $table->string('datatype', 64)->nullable(); // string | array | integer | float
            $table->boolean('required')->nullable();

            $table->boolean('custom')->nullable();
            $table->string('custom_value')->nullable();                     // 4 | null
            $table->string('custom_value_field', 64)->nullable();   // 'customValue' | null,

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('properties');
    }
}
