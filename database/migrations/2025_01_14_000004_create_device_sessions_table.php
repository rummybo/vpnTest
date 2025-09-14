<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v2_device_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('device_id', 128)->comment('设备ID');
            $table->string('session_id', 64)->unique()->comment('会话唯一标识');
            $table->timestamp('start_time')->comment('开始使用时间');
            $table->timestamp('end_time')->nullable()->comment('结束使用时间');
            $table->integer('duration')->default(0)->comment('实际使用时长(秒)');
            $table->boolean('is_active')->default(true)->comment('是否活跃会话');
            $table->enum('session_type', ['promotion', 'vip'])->default('promotion')->comment('会话类型');
            $table->timestamps();
            
            $table->index('device_id');
            $table->index('session_id');
            $table->index('is_active');
            $table->index('start_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_sessions');
    }
}