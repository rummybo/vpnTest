<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionUsageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v2_promotion_usage', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('promotion_code', 32)->comment('推广码');
            $table->string('user_device_id', 128)->comment('使用者设备ID');
            $table->integer('daily_duration')->comment('每日可用时长(秒)');
            $table->integer('single_duration')->comment('单次可用时长(秒)，-1表示无限');
            $table->integer('used_today')->default(0)->comment('今日已用时长');
            $table->date('last_used_date')->nullable()->comment('最后使用日期');
            $table->boolean('is_unlimited')->default(false)->comment('是否无限制');
            $table->timestamps();
            
            $table->index('promotion_code');
            $table->index('user_device_id');
            $table->unique(['promotion_code', 'user_device_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promotion_usage');
    }
}