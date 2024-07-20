<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProviderSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provider_sheets', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('sku', 64)->nullable();
            $table->string('ean', 64)->nullable();
            $table->string('pn', 64)->nullable();
            $table->string('brand')->nullable();
            $table->boolean('available')->default(0);

            $table->timestamps();

            $table->index('sku');
            $table->index('ean');
            $table->index('pn');
            $table->index('brand');
            $table->index('available');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('provider_sheets');
    }
}
