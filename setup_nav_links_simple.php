<?php
/**
 * 福利导航功能简化设置脚本
 */

require_once __DIR__ . '/vendor/autoload.php';

// 启动Laravel应用
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== 福利导航功能简化设置 ===\n\n";

try {
    // 1. 检查数据库连接
    echo "1. 检查数据库连接...\n";
    DB::connection()->getPdo();
    echo "   ✅ 数据库连接正常\n\n";
    
    // 2. 执行数据库迁移
    echo "2. 执行数据库迁移...\n";
    if (!Schema::hasTable('v2_nav_links')) {
        Artisan::call('migrate', ['--path' => 'database/migrations/2025_09_14_210000_create_fa_nav_links_table.php']);
        echo "   ✅ v2_nav_links 表创建成功\n";
    } else {
        echo "   ✅ v2_nav_links 表已存在\n";
    }
    echo "\n";
    
    // 3. 设置默认配置
    echo "3. 设置默认配置...\n";
    
    // 检查配置表是否存在
    if (Schema::hasTable('v2_settings')) {
        // 启用福利导航功能
        DB::table('v2_settings')->updateOrInsert(
            ['key' => 'nav_links_enable'],
            ['value' => '1']
        );
        echo "   ✅ 福利导航功能已启用\n";
    } else {
        echo "   ⚠️  v2_settings 表不存在，将使用默认配置\n";
    }
    echo "\n";
    
    // 4. 添加示例数据
    echo "4. 添加示例福利导航数据...\n";
    $existingCount = DB::table('v2_nav_links')->count();
    
    if ($existingCount == 0) {
        $sampleData = [
            [
                'title' => '示例导航1',
                'link' => 'https://example1.com',
                'icon' => 'link',
                'sort' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'title' => '示例导航2', 
                'link' => 'https://example2.com',
                'icon' => 'star',
                'sort' => 2,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        
        DB::table('v2_nav_links')->insert($sampleData);
        echo "   ✅ 已添加 " . count($sampleData) . " 条示例数据\n";
    } else {
        echo "   ✅ 已存在 {$existingCount} 条福利导航数据\n";
    }
    echo "\n";
    
    // 5. 清除缓存
    echo "5. 清除缓存...\n";
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    echo "   ✅ 缓存已清除\n\n";
    
    echo "🎉 福利导航功能设置完成！\n\n";
    echo "📋 下一步操作：\n";
    echo "1. 重启Web服务器: sudo systemctl restart nginx && sudo systemctl restart php-fpm\n";
    echo "2. 访问后台管理面板，应该能看到'福利导航'菜单项\n";
    echo "3. 点击福利导航菜单进行管理\n\n";
    
    echo "🔗 访问地址：\n";
    $securePath = config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key'))));
    echo "   后台管理: http://你的域名/{$securePath}\n";
    echo "   福利导航: http://你的域名/{$securePath}#/nav_links\n\n";
    
} catch (Exception $e) {
    echo "❌ 设置过程中出现错误: " . $e->getMessage() . "\n";
    echo "请检查数据库连接和权限设置\n";
    exit(1);
}

echo "=== 设置完成 ===\n";