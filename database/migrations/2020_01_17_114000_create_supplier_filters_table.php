<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_filters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');

            $table->string('brand_name')->nullable();
            $table->string('category_name')->nullable();
            $table->string('type_name')->nullable();
            $table->string('status_name')->nullable();

            $table->string('name')->nullable();
            $table->string('model')->nullable();
            $table->string('supplierSku', 64)->nullable();
            $table->string('pn', 64)->nullable();
            $table->string('ean', 64)->nullable();
            $table->string('upc', 64)->nullable();
            $table->string('isbn', 64)->nullable();

            $table->float('cost_min')->nullable();
            $table->float('cost_max')->nullable();
            $table->unsignedBigInteger('stock_min')->nullable();
            $table->unsignedBigInteger('stock_max')->nullable();

            $table->string('field_name')->nullable();
            $table->string('field_operator', 3)->nullable();        // ==, <=, >=, !=, ...
            $table->string('field_string', 64)->nullable();
            $table->unsignedBigInteger('field_integer')->nullable();
            $table->float('field_float')->nullable();

            $table->unsignedBigInteger('limit_products')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_filters');
    }
}
