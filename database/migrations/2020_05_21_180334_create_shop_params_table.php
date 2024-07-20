<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopParamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_params', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->foreign('brand_id')
                ->references('id')
                ->on('brands');
            $table->unsignedBigInteger('root_category_id')->nullable();
            $table->foreign('root_category_id')
                ->references('id')
                ->on('root_categories');

            $table->unsignedBigInteger('market_category_id')->nullable();
            $table->foreign('market_category_id')
                ->references('id')
                ->on('market_categories');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')
                ->references('id')
                ->on('categories');

            $table->string('supplierSku', 64)->nullable();
            $table->string('pn', 64)->nullable();
            $table->string('ean', 64)->nullable();
            $table->string('upc', 64)->nullable();
            $table->string('isbn', 64)->nullable();
            $table->string('gtin', 64)->nullable();

            $table->float('canon')->default(0);
            $table->float('rappel')->default(0);
            $table->float('ports')->default(0);

            $table->float('fee')->default(0);
            $table->float('bfit_min')->default(0);
            $table->float('mps_fee')->default(0);
            $table->float('price')->default(0);
            $table->float('stock')->default(0);
            $table->unsignedBigInteger('stock_min')->default(0);
            $table->unsignedBigInteger('stock_max')->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->index('supplierSku');
            $table->index('pn');
            $table->index('ean');
            $table->index('upc');
            $table->index('isbn');
            $table->index('gtin');

            $table->index('canon');
            $table->index('rappel');
            $table->index('ports');

            $table->index('fee');
            $table->index('bfit_min');
            $table->index('mps_fee');
            $table->index('price');
            $table->index('stock');
            $table->index('stock_min');
            $table->index('stock_max');

            $table->index('starts_at');
            $table->index('ends_at');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_params');
    }
}
