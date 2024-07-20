<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 64)->nullable();
            $table->string('name', 64)->nullable(false);
            $table->string('type_import', 64)->nullable();
            $table->string('ws', 64)->nullable();

            $table->string('primary_key_field')->nullable();
            $table->string('parent_field')->nullable();
            $table->string('brand_field')->nullable();
            $table->string('category_field')->nullable();
            $table->string('category_id_field', 64)->nullable();
            $table->string('type_field')->nullable();
            $table->string('status_field')->nullable();
            $table->string('currency_field')->nullable();

            $table->string('ready_field')->nullable();
            $table->string('name_field')->nullable();
            $table->string('keys_field')->nullable();
            $table->string('pn_field')->nullable();
            $table->string('ean_field')->nullable();
            $table->string('upc_field')->nullable();
            $table->string('isbn_field')->nullable();
            $table->string('short_field')->nullable();
            $table->string('long_field')->nullable();

            $table->string('supplierSku_field')->nullable();
            $table->string('model_field')->nullable();
            $table->string('cost_field')->nullable();
            $table->string('canon_field')->nullable();
            $table->string('rappel_field')->nullable();
            $table->string('ports_field')->nullable();
            $table->string('tax_field')->nullable();
            $table->string('stock_field')->nullable();
            $table->string('size_field')->nullable();
            $table->string('color_field')->nullable();
            $table->string('material_field')->nullable();
            $table->string('style_field')->nullable();
            $table->string('gender_field')->nullable();
            $table->string('sku_src_field')->nullable();

            $table->string('weight_field', 64)->nullable();       // g
            $table->string('length_field', 64)->nullable();       // largo x ancho x alto, en mm
            $table->string('width_field', 64)->nullable();
            $table->string('height_field', 64)->nullable();

            $table->string('images_field')->nullable();
            $table->string('extra_field')->nullable();
            $table->json('config')->nullable();

            $table->index('code');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
}
