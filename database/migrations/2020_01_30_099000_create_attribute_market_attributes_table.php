<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttributeMarketAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attribute_market_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id')->nullable();
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');
            $table->unsignedBigInteger('attribute_id')->nullable();
            $table->foreign('attribute_id')
                ->references('id')
                ->on('attributes');
            $table->unsignedBigInteger('market_attribute_id')->nullable();
            $table->foreign('market_attribute_id')
                ->references('id')
                ->on('market_attributes');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->foreign('property_id')
                ->references('id')
                ->on('properties');

            $table->string('field', 64)->nullable();        // product field
            $table->boolean('fixed')->nullable();      // true | false (if attribute value is fixed, no variable)
            $table->string('fixed_value')->nullable();
            $table->string('pattern')->nullable();      // /[^a-zA-Z0-9]/  |  /[^0-9\.,]/
            $table->string('mapping')->nullable();      // equal | strpos
            $table->string('if_exists')->nullable();
            $table->string('if_exists_value')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attribute_market_attributes');
    }
}
