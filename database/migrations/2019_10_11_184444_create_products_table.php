<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')
                ->references('id')
                ->on('products');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->foreign('brand_id')
                ->references('id')
                ->on('brands');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')
                ->references('id')
                ->on('categories');
            $table->unsignedBigInteger('type_id')->nullable();
            $table->foreign('type_id')
                ->references('id')
                ->on('types');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->foreign('status_id')
                ->references('id')
                ->on('statuses');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies');

            // provider_id                          // Added in create_providers

            $table->string('name')->nullable();
            $table->string('keywords')->nullable();
            $table->string('pn', 64)->nullable();
            $table->string('ean', 64)->nullable();
            $table->string('upc', 64)->nullable();
            $table->string('isbn', 64)->nullable();
            $table->string('gtin', 64)->nullable();

            $table->text('shortdesc')->nullable();
            $table->text('longdesc')->nullable();

            $table->string('weight', 64)->nullable();       // g
            $table->string('length', 64)->nullable();       // largo x ancho x alto, en mm
            $table->string('width', 64)->nullable();
            $table->string('height', 64)->nullable();

            $table->boolean('ready')->default(1);
            $table->string('supplierSku', 64)->nullable();
            $table->string('model', 64)->nullable();
            $table->float('cost')->default(0);
            /* $table->float('canon')->default(0); */
            /* $table->float('rappel')->default(0); */
            /* $table->float('ports')->default(0); */
            $table->float('tax')->default(0);
            $table->unsignedBigInteger('stock')->default(0);
            $table->string('size', 64)->nullable();
            $table->string('color', 64)->nullable();
            $table->string('material', 64)->nullable();
            $table->string('style', 64)->nullable();
            $table->string('gender', 64)->nullable();

            $table->boolean('fix_text')->default(0);

            $table->timestamps();

            $table->index('name');
            $table->index('pn');
            $table->index('ean');
            $table->index('upc');
            $table->index('isbn');
            $table->index('gtin');

            $table->index('weight');
            $table->index('length');
            $table->index('width');
            $table->index('height');

            $table->index('ready');
            $table->index('supplierSku');
            $table->index('model');
            $table->index('cost');
            $table->index('tax');
            $table->index('stock');
            $table->index('size');
            $table->index('color');
            $table->index('material');
            $table->index('style');
            $table->index('gender');

            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
