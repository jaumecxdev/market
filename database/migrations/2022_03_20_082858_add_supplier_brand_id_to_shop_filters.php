<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupplierBrandIdToShopFilters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_filters', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_brand_id')->after('product_id')->nullable();
            $table->foreign('supplier_brand_id')
                ->references('id')
                ->on('supplier_brands');

            $table->unsignedBigInteger('supplier_category_id')->after('brand_id')->nullable();
            $table->foreign('supplier_category_id')
                ->references('id')
                ->on('supplier_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_filters', function (Blueprint $table) {
            //
        });
    }
}
