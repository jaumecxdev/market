<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('market_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id')->nullable();
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');
            /*$table->unsignedBigInteger('attribute_id')->nullable();
            $table->foreign('attribute_id')
                ->references('id')
                ->on('attributes');*/
            $table->unsignedBigInteger('market_category_id')->nullable();
            $table->foreign('market_category_id')
                ->references('id')
                ->on('market_categories');
            $table->unsignedBigInteger('type_id')->nullable();      // (category_attributes | sku_attributes)
            $table->foreign('type_id')
                ->references('id')
                ->on('types');

            $table->string('name')->nullable(false);     // (Hard Drive Type)
            $table->string('code')->nullable();                // (Hard Drive Type)
            $table->string('datatype', 64)->nullable(); // string | array | integer | float
            $table->boolean('required')->nullable();


            /*
            $table->string('field', 64)->nullable();    // value | customValue | unit

            // properties
            $table->boolean('custom')->nullable();
            $table->string('custom_value')->nullable();                     // 4 | null
            $table->string('custom_value_field', 64)->nullable();   // 'customValue' | null,

            // attribute_market_attributes
            $table->boolean('fixed')->nullable();      // true | false (if attribute value is fixed, no variable)
            $table->string('fixed_value')->nullable();
            $table->string('pattern')->nullable();      // /[^a-zA-Z0-9]/  |  /[^0-9\.,]/
            $table->string('mapping')->nullable();      // equal | strpos
            */


            $table->index('name');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('market_attributes');
    }
}
