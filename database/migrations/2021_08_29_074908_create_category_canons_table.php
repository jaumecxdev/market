<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryCanonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_canons', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')
                ->references('id')
                ->on('categories');

            $table->string('locale', 64)->nullable();       // es, en, ..
            $table->float('canon')->default(0);

            $table->index('locale');
        });

        Schema::table('supplier_params', function (Blueprint $table) {

            $table->timestamp('ends_at')->after('supplierSku')->nullable();
            $table->timestamp('starts_at')->after('supplierSku')->nullable();
            $table->float('cost_max')->after('supplierSku')->nullable();
            $table->float('cost_min')->after('supplierSku')->nullable();

            $table->float('price')->default(0);
            $table->float('discount_price')->default(0);
            $table->unsignedBigInteger('stock')->default(0);

            $table->index('cost_min');
            $table->index('cost_max');
            $table->index('starts_at');
            $table->index('ends_at');
        });

        Schema::table('suppliers', function (Blueprint $table) {

            $table->string('locale', 64)->after('name')->nullable();        // es, en, ..
        });

        Schema::table('shops', function (Blueprint $table) {

            $table->string('locale', 64)->after('name')->nullable();        // es, en, ..
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_canons');
    }
}
