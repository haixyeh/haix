<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('isRecommon');
            $table->char('name');
            $table->date('startDate');
            $table->date('endDate');
            $table->mediumInteger('amount');
            $table->mediumInteger('total');
            $table->string('goodsType');
            $table->string('info');
            $table->string('images');
            $table->string('forcedRemoval')->default('N');
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
        Schema::dropIfExists('goods');
    }
}
