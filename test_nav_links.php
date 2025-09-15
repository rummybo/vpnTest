<?php
/**
 * 福利导航功能测试脚本
 * 用于验证福利导航功能是否正常工作
 */

require_once __DIR__ . '/vendor/autoload.php';

// 加载Laravel应用
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== 福利导航功能测试 ===\n\n";

// 1. 检查数据库表是否存在
try {
    $tableExists = \Illuminate\Support\Facades\Schema::hasTable('v2_nav_links');
    echo "1. 数据库表检查: " . ($tableExists ? "✓ v2_nav_links表存在" : "✗ v2_nav_links表不存在") . "\n";
} catch (Exception $e) {
    echo "1. 数据库表检查: ✗ 数据库连接失败 - " . $e->getMessage() . "\n";
}

// 2. 检查模型是否存在
$modelExists = class_exists('App\Models\NavLink');
echo "2. 模型检查: " . ($modelExists ? "✓ NavLink模型存在" : "✗ NavLink模型不存在") . "\n";

// 3. 检查控制器是否存在
$controllerExists = class_exists('App\Http\Controllers\Admin\NavLinkController');
echo "3. 控制器检查: " . ($controllerExists ? "✓ NavLinkController控制器存在" : "✗ NavLinkController控制器不存在") . "\n";

// 4. 检查API控制器是否存在
$apiControllerExists = class_exists('App\Http\Controllers\Api\NavLinkController');
echo "4. API控制器检查: " . ($apiControllerExists ? "✓ Api\NavLinkController控制器存在" : "✗ Api\NavLinkController控制器不存在") . "\n";

// 5. 检查路由文件是否存在
$routeFileExists = file_exists(__DIR__ . '/app/Http/Routes/NavLinkRoute.php');
echo "5. 路由文件检查: " . ($routeFileExists ? "✓ NavLinkRoute.php存在" : "✗ NavLinkRoute.php不存在") . "\n";

// 6. 检查迁移文件是否存在
$migrationExists = !empty(glob(__DIR__ . '/database/migrations/*_create_fa_nav_links_table.php'));
echo "6. 迁移文件检查: " . ($migrationExists ? "✓ 迁移文件存在" : "✗ 迁移文件不存在") . "\n";

echo "\n=== 测试完成 ===\n";
echo "如果所有项目都显示 ✓，说明福利导航功能已正确配置。\n";
echo "如果有 ✗ 项目，请检查对应的文件或配置。\n\n";

echo "=== 下一步操作建议 ===\n";
echo "1. 运行数据库迁移: php artisan migrate\n";
echo "2. 清除缓存: php artisan cache:clear\n";
echo "3. 清除路由缓存: php artisan route:clear\n";
echo "4. 重新生成前端资源（如果有前端构建流程）\n";
echo "5. 访问后台管理面板查看福利导航功能\n";