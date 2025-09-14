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
        Schema::create('v2_nav_links', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->comment('导航标题');
            $table->string('link', 255)->comment('导航链接');
            $table->string('icon', 50)->nullable()->comment('导航图标');
            $table->integer('sort')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态：1显示 0隐藏');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('v2_nav_links');
    }
};