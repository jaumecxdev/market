<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderAttributeToProviderProductAttributes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('provider_product_attributes', function (Blueprint $table) {

            $table->unsignedBigInteger('provider_attribute_id')->after('product_id')->nullable();
            $table->foreign('provider_attribute_id')
                ->references('id')
                ->on('provider_attributes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('provider_product_attributes', function (Blueprint $table) {
            //
        });
    }
}
