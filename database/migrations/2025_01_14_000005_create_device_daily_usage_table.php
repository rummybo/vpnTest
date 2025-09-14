<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceDailyUsageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_daily_usage', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('device_id', 128)->comment('设备ID');
            $table->date('usage_date')->comment('使用日期');
            $table->integer('total_duration')->default(0)->comment('当日总使用时长(秒)');
            $table->integer('session_count')->default(0)->comment('当日会话次数');
            $table->timestamps();
            
            $table->unique(['device_id', 'usage_date']);
            $table->index('device_id');
            $table->index('usage_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_daily_usage');
    }
}