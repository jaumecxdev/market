<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceMpeToOrderPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_payments', function (Blueprint $table) {

            $table->string('invoice_mpe', 64)->nullable();
            $table->float('invoice_mpe_price')->default(0);
            $table->timestamp('invoice_mpe_created_at')->nullable();

            $table->index('invoice_mpe');
            $table->index('invoice_mpe_price');
            $table->index('invoice_mpe_created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_payments', function (Blueprint $table) {
            //
        });
    }
}
