<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LevelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('level', function (Blueprint $table) {
            $table->increments('id');
            $table->char('levelName', 30);
            $table->char('offer', 1)->defalut('N');
            $table->char('offerType', 1)->nullable();
            $table->integer('full')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('present')->nullable();
            $table->integer('upgradeAmount');
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
        //
        Schema::dropIfExists('level');
    }
}
