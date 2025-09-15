<?php
/**
 * 福利导航功能部署验证脚本
 * 检查并验证所有组件是否正确配置
 */

echo "=== v2board 福利导航功能部署验证 ===\n\n";

$checks = [];

// 1. 检查文件是否存在
echo "📁 检查文件完整性...\n";

$requiredFiles = [
    'app/Models/NavLink.php' => 'NavLink模型',
    'app/Http/Controllers/Admin/NavLinkController.php' => 'Admin控制器',
    'app/Http/Controllers/Api/NavLinkController.php' => 'API控制器',
    'app/Http/Routes/NavLinkRoute.php' => '路由文件',
    'database/migrations/2025_09_14_210000_create_fa_nav_links_table.php' => '数据库迁移',
    'public/assets/admin/nav-links-extension.js' => '前端扩展',
    'resources/views/admin.blade.php' => '后台模板'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ {$description}: {$file}\n";
        $checks['files'][] = true;
    } else {
        echo "   ❌ {$description}: {$file} (缺失)\n";
        $checks['files'][] = false;
    }
}

// 2. 检查配置文件修改
echo "\n⚙️  检查配置修改...\n";

$configFiles = [
    'app/Http/Requests/Admin/ConfigSave.php' => 'nav_links_enable',
    'app/Http/Controllers/Admin/ConfigController.php' => 'getMenuConfig'
];

foreach ($configFiles as $file => $searchText) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, $searchText) !== false) {
            echo "   ✅ {$file}: 包含 {$searchText}\n";
            $checks['config'][] = true;
        } else {
            echo "   ❌ {$file}: 缺少 {$searchText}\n";
            $checks['config'][] = false;
        }
    } else {
        echo "   ❌ {$file}: 文件不存在\n";
        $checks['config'][] = false;
    }
}

// 3. 检查路由配置
echo "\n🛣️  检查路由配置...\n";

$adminRouteFile = 'app/Http/Routes/AdminRoute.php';
if (file_exists($adminRouteFile)) {
    $content = file_get_contents($adminRouteFile);
    $routeChecks = [
        'nav_links/fetch' => '获取列表API',
        'nav_links/save' => '保存API',
        'menu/config' => '菜单配置API'
    ];
    
    foreach ($routeChecks as $route => $description) {
        if (strpos($content, $route) !== false) {
            echo "   ✅ {$description}: {$route}\n";
            $checks['routes'][] = true;
        } else {
            echo "   ❌ {$description}: {$route} (缺失)\n";
            $checks['routes'][] = false;
        }
    }
} else {
    echo "   ❌ AdminRoute.php 文件不存在\n";
    $checks['routes'] = [false];
}

// 4. 检查前端扩展
echo "\n🎨 检查前端扩展...\n";

$adminTemplate = 'resources/views/admin.blade.php';
if (file_exists($adminTemplate)) {
    $content = file_get_contents($adminTemplate);
    if (strpos($content, 'nav-links-extension.js') !== false) {
        echo "   ✅ 后台模板已引入扩展JavaScript\n";
        $checks['frontend'][] = true;
    } else {
        echo "   ❌ 后台模板未引入扩展JavaScript\n";
        $checks['frontend'][] = false;
    }
} else {
    echo "   ❌ 后台模板文件不存在\n";
    $checks['frontend'] = [false];
}

// 5. 统计结果
echo "\n📊 部署验证结果:\n";

$totalChecks = 0;
$passedChecks = 0;

foreach ($checks as $category => $results) {
    $categoryPassed = array_sum($results);
    $categoryTotal = count($results);
    $totalChecks += $categoryTotal;
    $passedChecks += $categoryPassed;
    
    $status = $categoryPassed === $categoryTotal ? '✅' : '❌';
    echo "   {$status} " . ucfirst($category) . ": {$categoryPassed}/{$categoryTotal}\n";
}

echo "\n总体进度: {$passedChecks}/{$totalChecks} (" . round(($passedChecks/$totalChecks)*100, 1) . "%)\n";

if ($passedChecks === $totalChecks) {
    echo "\n🎉 所有检查通过！福利导航功能已完整部署。\n";
    echo "\n📋 下一步操作:\n";
    echo "1. 运行配置更新: php update_nav_links_config.php\n";
    echo "2. 执行数据库迁移: php artisan migrate\n";
    echo "3. 清除缓存: php artisan cache:clear && php artisan config:clear\n";
    echo "4. 访问后台管理面板查看福利导航菜单\n";
} else {
    echo "\n⚠️  部分检查未通过，请检查上述标记为 ❌ 的项目。\n";
}

echo "\n=== 验证完成 ===\n";