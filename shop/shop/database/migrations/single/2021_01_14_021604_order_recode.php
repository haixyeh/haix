<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OrderRecode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orderList', function (Blueprint $table) {
            $table->increments('id');
            $table->string('account');
            $table->string('orderNumber');
            $table->string('name', 30);
            $table->string('phone', 30);
            $table->string('address', 100);
            $table->integer('totalAmount');
            $table->integer('promsPrice');
            $table->string('currentProms');
            $table->text('goodsIndo');
            $table->char('status', 1)->default('N')
                    ->comment('N: 未確認訂單, Y: 已確定訂單, S: 出貨中, E: 已完成, R: 退貨中, T: 待確認退貨商品, F: 退貨完成');
            $table->char('cancelOrder', 1)->default('N');
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
        Schema::dropIfExists('orderList');
    }
}
