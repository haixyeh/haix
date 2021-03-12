<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RebackOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rebackOrder', function (Blueprint $table) {
            $table->increments('id');
            $table->string('orderId');
            $table->text('goodsIndo')->nullable();
            $table->string('orderNumber');
            $table->char('rebackStatus', 1)->default('N')->comment('N: 未接收申請, Y: 已接收申請, F: 完成退貨程序');
            $table->char('rebackAll', 1)->default('N');
            $table->string('reason', 256)->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rebackOrder');
    }
}
