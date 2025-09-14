<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('v2_coin_exchanges', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 128)->comment('设备ID');
            $table->integer('coins_used')->comment('使用的金币数量');
            $table->enum('exchange_type', ['vip'])->comment('兑换类型');
            $table->timestamps();
            
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v2_coin_exchanges');
    }
};