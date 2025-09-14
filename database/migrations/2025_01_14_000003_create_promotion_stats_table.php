<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('promotion_code', 32)->unique()->comment('推广码');
            $table->integer('total_uses')->default(0)->comment('总使用次数');
            $table->integer('active_users')->default(0)->comment('当前活跃用户数');
            $table->bigInteger('total_duration')->default(0)->comment('总共提供的时长(秒)');
            $table->timestamps();
            
            $table->index('promotion_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promotion_stats');
    }
}