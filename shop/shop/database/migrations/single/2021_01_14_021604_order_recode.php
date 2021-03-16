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
            $table->string('account')->comment('訂購帳號');
            $table->string('orderNumber')->comment('訂單編號');
            $table->string('name', 30)->comment('收件人');
            $table->string('phone', 30)->comment('收件電話');
            $table->string('address', 100)->comment('收件地址');
            $table->integer('totalAmount')->comment('訂單總額(不扣除優惠)');
            $table->integer('promsPrice')->comment('優惠金額');
            $table->string('currentProms')->comment('當前優惠方案');
            $table->text('goodsIndo')->comment('訂單貨品');
            $table->char('status', 1)->default('N')
                    ->comment('N: 未確認訂單, Y: 已確定訂單, S: 出貨中, E: 已完成, R: 退貨中, T: 待確認退貨商品, F: 退貨完成');
            $table->char('cancelOrder', 1)->default('N');
            $table->integer('coupon')->default(0)->comment('使用購物金');
            $table->integer('payment')->comment('付費金額');
            $table->text('memo')->nullable()->comment('備註');
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
