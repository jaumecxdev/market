<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuestServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('guest_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');
            $table->unsignedBigInteger('shop_id')->nullable();
                $table->foreign('shop_id')
                    ->references('id')
                    ->on('shops');

            /* $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->string('class', 64)->nullable();
            $table->string('params')->nullable(); */

            $table->text('token')->nullable(false);
            $table->text('refresh')->nullable();

            $table->timestamps();

            /* $table->index('name');
            $table->index('type');
            $table->index('class'); */
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('guest_services');
    }
}
