<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('market_id')->nullable();
            $table->foreign('market_id')
                ->references('id')
                ->on('markets');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')
                ->references('id')
                ->on('products');
            $table->unsignedBigInteger('last_product_id')->nullable();
            $table->foreign('last_product_id')
                ->references('id')
                ->on('products');
            $table->unsignedBigInteger('market_category_id')->nullable();
            $table->foreign('market_category_id')
                ->references('id')
                ->on('market_categories');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies');

            // provider_id                      // Added in create_providers

            //$table->integer('fixed')->default(0);
            $table->boolean('enabled')->default(1);
            $table->boolean('is_sku_child')->default(0);
            //$table->string('mps_sku', 64)->nullable(); Added in: add_mps_sku_to_shop_products migration
            $table->string('marketProductSku', 64)->nullable();
            $table->boolean('set_group')->default(0);
            $table->float('cost')->default(0);
            $table->float('price')->default(0);
            $table->float('tax')->default(0);
            $table->unsignedBigInteger('stock')->default(0);

            $table->float('param_fee')->default(0);
            $table->float('param_bfit_min')->default(0);
            $table->float('param_mps_fee')->default(0);
            $table->float('param_price')->default(0);
            $table->unsignedBigInteger('param_stock')->default(0);
            $table->unsignedBigInteger('param_stock_min')->default(0);
            $table->unsignedBigInteger('param_stock_max')->default(0);
            $table->float('mp_fee')->default(0);
            $table->float('mp_fee_addon')->default(0);

            $table->timestamps();

            $table->index('enabled');
            $table->index('marketProductSku');
            $table->index('cost');
            $table->index('price');
            $table->index('tax');
            $table->index('stock');

            $table->index('param_fee');
            $table->index('param_bfit_min');
            $table->index('param_mps_fee');
            $table->index('param_price');
            $table->index('param_stock');
            $table->index('param_stock_min');
            $table->index('param_stock_max');
            $table->index('mp_fee');
            $table->index('mp_fee_addon');

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
        Schema::dropIfExists('shop_products');
    }
}
