<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEanToSupplierParams extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('supplier_params', function (Blueprint $table) {

            $table->string('gtin', 64)->after('supplierSku')->nullable();
            $table->string('isbn', 64)->after('supplierSku')->nullable();
            $table->string('upc', 64)->after('supplierSku')->nullable();
            $table->string('ean', 64)->after('supplierSku')->nullable();
            $table->string('pn', 64)->after('supplierSku')->nullable();

            $table->unsignedBigInteger('product_id')->after('supplierSku')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');

            $table->index('pn');
            $table->index('ean');
            $table->index('upc');
            $table->index('isbn');
            $table->index('gtin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('supplier_params', function (Blueprint $table) {
            //
        });
    }
}
