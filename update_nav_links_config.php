<?php
/**
 * 福利导航配置更新脚本
 * 为v2board添加福利导航的默认配置
 */

require_once __DIR__ . '/vendor/autoload.php';

// 加载Laravel应用
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== 福利导航配置更新脚本 ===\n\n";

try {
    // 检查配置文件是否存在
    $configPath = base_path('config/v2board.php');
    
    if (file_exists($configPath)) {
        echo "1. 发现现有配置文件，正在更新...\n";
        
        // 读取现有配置
        $config = include $configPath;
        
        // 添加福利导航配置（如果不存在）
        if (!isset($config['nav_links_enable'])) {
            $config['nav_links_enable'] = 1;
            echo "   - 添加 nav_links_enable = 1\n";
        } else {
            echo "   - nav_links_enable 配置已存在: " . $config['nav_links_enable'] . "\n";
        }
        
        // 写回配置文件
        $configContent = "<?php\n return " . var_export($config, true) . " ;";
        file_put_contents($configPath, $configContent);
        
        echo "2. 配置文件更新完成\n";
        
    } else {
        echo "1. 配置文件不存在，创建默认配置...\n";
        
        // 创建基本配置
        $defaultConfig = [
            'nav_links_enable' => 1,
            'app_name' => 'V2Board',
            'app_description' => 'V2Board is best!'
        ];
        
        // 确保目录存在
        if (!is_dir(dirname($configPath))) {
            mkdir(dirname($configPath), 0755, true);
        }
        
        // 写入配置文件
        $configContent = "<?php\n return " . var_export($defaultConfig, true) . " ;";
        file_put_contents($configPath, $configContent);
        
        echo "2. 默认配置文件创建完成\n";
    }
    
    // 清除配置缓存
    echo "3. 清除配置缓存...\n";
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    echo "\n✅ 福利导航配置更新成功！\n";
    echo "福利导航功能已启用，请访问后台管理面板查看。\n\n";
    
} catch (Exception $e) {
    echo "❌ 配置更新失败: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== 更新完成 ===\n";
echo "下一步操作：\n";
echo "1. 运行: php artisan config:clear\n";
echo "2. 运行: php artisan cache:clear\n";
echo "3. 访问后台管理面板查看福利导航菜单\n";