<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v2_promotion_devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('device_id', 128)->unique()->comment('设备唯一标识');
            $table->string('promotion_code', 32)->unique()->comment('推广码');
            $table->integer('total_referrals')->default(0)->comment('总推广人数');
            $table->integer('coins')->default(0)->comment('金币数量');
            $table->boolean('is_vip')->default(false)->comment('是否黄金会员');
            $table->timestamps();
            
            $table->index('device_id');
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
        Schema::dropIfExists('promotion_devices');
    }
}