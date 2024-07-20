<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRepriceToShopParams extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_params', function (Blueprint $table) {

            $table->float('reprice_fee_min')->default(0);
            $table->index('reprice_fee_min');
        });

        Schema::table('market_params', function (Blueprint $table) {

            $table->float('lot')->default(0);
            $table->float('lot_fee')->default(0);
            $table->float('bfit_min')->default(0);
            $table->index('lot');
            $table->index('lot_fee');
            $table->index('bfit_min');
        });

        Schema::table('shop_products', function (Blueprint $table) {

            /*
            RENOMBRAR MANUALMENT

            $table->renameColumn('mp_fee', 'param_mp_fee');
            $table->renameColumn('mp_fee_addon', 'param_mp_fee_addon');
            $table->renameColumn('discount_price', 'param_discount_price');
            $table->renameColumn('starts_at', 'param_starts_at');
            $table->renameColumn('ends_at', 'param_ends_at');

            $table->renameIndex('shop_products_fee_mp_index', 'shop_products_param_mp_fee_index');
            $table->renameIndex('shop_products_mp_fee_addon_index', 'shop_products_param_mp_fee_addon_index');*/

            $table->float('param_mp_bfit_min')->after('param_mp_fee')->default(0);
            $table->float('param_mp_lot_fee')->after('param_mp_fee')->default(0);
            $table->float('param_mp_lot')->after('param_mp_fee')->default(0);
            $table->index('param_mp_bfit_min');
            $table->index('param_mp_lot_fee');
            $table->index('param_mp_lot');

            $table->float('param_reprice_fee_min')->default(0);
            $table->index('param_reprice_fee_min');

            $table->float('buybox_price')->default(0);
            $table->timestamp('buybox_updated_at')->nullable();
            $table->index('buybox_price');
            $table->index('buybox_updated_at');
            $table->boolean('repriced')->default(0);

            $table->float('bfit')->default(0);
            $table->float('mps_bfit')->default(0);
            $table->float('mp_bfit')->default(0);
            $table->index('bfit');
            $table->index('mps_bfit');
            $table->index('mp_bfit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_params', function (Blueprint $table) {
            //
        });
    }
}
