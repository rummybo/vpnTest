<?php

use App\Services\ThemeService;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (Request $request) {
    if (config('v2board.app_url') && config('v2board.safe_mode_enable', 0)) {
        if ($request->server('HTTP_HOST') !== parse_url(config('v2board.app_url'))['host']) {
            abort(403);
        }
    }
    $renderParams = [
        'title' => config('v2board.app_name', 'V2Board'),
        'theme' => config('v2board.frontend_theme', 'v2board'),
        'version' => config('app.version'),
        'description' => config('v2board.app_description', 'V2Board is best'),
        'logo' => config('v2board.logo')
    ];

    if (!config("theme.{$renderParams['theme']}")) {
        $themeService = new ThemeService($renderParams['theme']);
        $themeService->init();
    }

    $renderParams['theme_config'] = config('theme.' . config('v2board.frontend_theme', 'v2board'));
    return view('theme::' . config('v2board.frontend_theme', 'v2board') . '.dashboard', $renderParams);
});

//TODO:: 兼容
Route::get('/' . config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key')))), function () {
    return view('admin', [
        'title' => config('v2board.app_name', 'V2Board'),
        'theme_sidebar' => config('v2board.frontend_theme_sidebar', 'light'),
        'theme_header' => config('v2board.frontend_theme_header', 'dark'),
        'theme_color' => config('v2board.frontend_theme_color', 'default'),
        'background_url' => config('v2board.frontend_background_url'),
        'version' => config('app.version'),
        'logo' => config('v2board.logo'),
        'secure_path' => config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key'))))
    ]);
});

// 福利导航管理路由
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum'])->group(function () {
    Route::resource('nav_links', \App\Http\Controllers\Admin\NavLinkController::class);
    
    // API格式路由 (v2board后台使用)
    Route::get('nav_links/fetch', [\App\Http\Controllers\Admin\NavLinkController::class, 'fetch']);
    Route::post('nav_links/save', [\App\Http\Controllers\Admin\NavLinkController::class, 'save']);
    Route::post('nav_links/drop', [\App\Http\Controllers\Admin\NavLinkController::class, 'drop']);
    Route::post('nav_links/show', [\App\Http\Controllers\Admin\NavLinkController::class, 'show']);
    Route::post('nav_links/sort', [\App\Http\Controllers\Admin\NavLinkController::class, 'sort']);
});

// 常用导航管理路由
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum'])->group(function () {
    Route::resource('common_links', \App\Http\Controllers\Admin\CommonLinkController::class);
    
    // API格式路由 (v2board后台使用)
    Route::get('common_links/fetch', [\App\Http\Controllers\Admin\CommonLinkController::class, 'fetch']);
    Route::post('common_links/save', [\App\Http\Controllers\Admin\CommonLinkController::class, 'save']);
    Route::post('common_links/drop', [\App\Http\Controllers\Admin\CommonLinkController::class, 'drop']);
    Route::post('common_links/show', [\App\Http\Controllers\Admin\CommonLinkController::class, 'show']);
    Route::post('common_links/sort', [\App\Http\Controllers\Admin\CommonLinkController::class, 'sort']);
});

// 前端导航页管理路由
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum'])->group(function () {
    Route::resource('frontend_nav_pages', \App\Http\Controllers\Admin\FrontendNavPageController::class);
    
    // API格式路由 (v2board后台使用)
    Route::post('frontend_nav_pages/fetch', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'fetch']);
    Route::post('frontend_nav_pages/save', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'save']);
    Route::post('frontend_nav_pages/drop', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'drop']);
    Route::post('frontend_nav_pages/show', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'show']);
    Route::post('frontend_nav_pages/sort', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'sort']);
});

// 用户显示管理路由 (无认证中间件，在控制器内部处理)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('user_display', [\App\Http\Controllers\Admin\UserDisplayController::class, 'index'])->name('user_display.index');
    
    // API格式路由 (v2board后台使用)
    Route::get('user_display/fetch', [\App\Http\Controllers\Admin\UserDisplayController::class, 'fetch']);
});

// 用户显示导出路由 (单独配置，跳过CORS中间件)
Route::get('admin/user_display/export', [\App\Http\Controllers\Admin\UserDisplayController::class, 'export'])
    ->name('admin.user_display.export')
    ->withoutMiddleware([\App\Http\Middleware\CORS::class]);

// 系统配置管理路由 (移除认证中间件，在控制器内部处理)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('system_configs', \App\Http\Controllers\Admin\SystemConfigController::class);
    
    // API格式路由 (v2board后台使用)
    Route::get('system_configs/fetch', [\App\Http\Controllers\Admin\SystemConfigController::class, 'fetch']);
    Route::post('system_configs/save', [\App\Http\Controllers\Admin\SystemConfigController::class, 'save']);
    Route::post('system_configs/drop', [\App\Http\Controllers\Admin\SystemConfigController::class, 'drop']);
    Route::post('system_configs/toggle', [\App\Http\Controllers\Admin\SystemConfigController::class, 'toggle']);
    Route::post('system_configs/batch-update', [\App\Http\Controllers\Admin\SystemConfigController::class, 'batchUpdate']);
    Route::get('system_configs/groups', [\App\Http\Controllers\Admin\SystemConfigController::class, 'groups']);
    Route::post('system_configs/refresh-cache', [\App\Http\Controllers\Admin\SystemConfigController::class, 'refreshCache']);
});

// v2board风格API路由 (管理后台)
Route::prefix('api/v1/{secure_path}')->group(function () {
    // 用户显示管理API
    Route::get('user_display/fetch', [\App\Http\Controllers\Admin\UserDisplayController::class, 'fetch']);
    
    // 福利导航管理API
    Route::get('nav_links/fetch', [\App\Http\Controllers\Admin\NavLinkController::class, 'fetch']);
    Route::post('nav_links/save', [\App\Http\Controllers\Admin\NavLinkController::class, 'save']);
    Route::post('nav_links/drop', [\App\Http\Controllers\Admin\NavLinkController::class, 'drop']);
    Route::post('nav_links/show', [\App\Http\Controllers\Admin\NavLinkController::class, 'show']);
    Route::post('nav_links/sort', [\App\Http\Controllers\Admin\NavLinkController::class, 'sort']);
    
    // 常用导航管理API
    Route::get('common_links/fetch', [\App\Http\Controllers\Admin\CommonLinkController::class, 'fetch']);
    Route::post('common_links/save', [\App\Http\Controllers\Admin\CommonLinkController::class, 'save']);
    Route::post('common_links/drop', [\App\Http\Controllers\Admin\CommonLinkController::class, 'drop']);
    Route::post('common_links/show', [\App\Http\Controllers\Admin\CommonLinkController::class, 'show']);
    Route::post('common_links/sort', [\App\Http\Controllers\Admin\CommonLinkController::class, 'sort']);
    
    // 前端导航页管理API
    Route::get('frontend_nav_pages/fetch', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'fetch']);
    Route::post('frontend_nav_pages/save', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'save']);
    Route::post('frontend_nav_pages/drop', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'drop']);
    Route::post('frontend_nav_pages/show', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'show']);
    Route::post('frontend_nav_pages/sort', [\App\Http\Controllers\Admin\FrontendNavPageController::class, 'sort']);
    
    // 系统配置管理API
    Route::get('system_configs/fetch', [\App\Http\Controllers\Admin\SystemConfigController::class, 'fetch']);
    Route::post('system_configs/save', [\App\Http\Controllers\Admin\SystemConfigController::class, 'save']);
    Route::post('system_configs/drop', [\App\Http\Controllers\Admin\SystemConfigController::class, 'drop']);
    Route::post('system_configs/toggle', [\App\Http\Controllers\Admin\SystemConfigController::class, 'toggle']);
    Route::post('system_configs/batch-update', [\App\Http\Controllers\Admin\SystemConfigController::class, 'batchUpdate']);
    Route::get('system_configs/groups', [\App\Http\Controllers\Admin\SystemConfigController::class, 'groups']);
    Route::post('system_configs/refresh-cache', [\App\Http\Controllers\Admin\SystemConfigController::class, 'refreshCache']);
    
    // 图片上传路由
    Route::post('upload/image', [\App\Http\Controllers\Admin\UploadController::class, 'uploadImage']);
});

// 公开API路由 (前端访问)
Route::prefix('api/v1')->group(function () {
    // 福利导航API
    Route::get('nav-links', [\App\Http\Controllers\Api\NavLinkController::class, 'index']);
    Route::get('nav-links/grouped', [\App\Http\Controllers\Api\NavLinkController::class, 'grouped']);
    
    // 常用导航API
    Route::get('common-links', [\App\Http\Controllers\Api\CommonLinkController::class, 'index']);
    Route::get('common-links/grouped', [\App\Http\Controllers\Api\CommonLinkController::class, 'grouped']);
    
    // 前端导航页API
    Route::get('frontend-nav-pages', [\App\Http\Controllers\Api\FrontendNavPageController::class, 'index']);
    Route::get('frontend-nav-pages/grouped', [\App\Http\Controllers\Api\FrontendNavPageController::class, 'grouped']);
    Route::get('frontend-nav-pages/popular', [\App\Http\Controllers\Api\FrontendNavPageController::class, 'popular']);
    
    // APP配置API
    Route::get('app-config', [\App\Http\Controllers\Api\AppConfigController::class, 'index']);
    Route::get('app-config/detailed', [\App\Http\Controllers\Api\AppConfigController::class, 'detailed']);
    Route::get('app-config/check', [\App\Http\Controllers\Api\AppConfigController::class, 'check']);
    Route::post('app-config/batch-check', [\App\Http\Controllers\Api\AppConfigController::class, 'batchCheck']);
    Route::get('app-config/info', [\App\Http\Controllers\Api\AppConfigController::class, 'info']);
    
    // 系统配置管理API
    Route::get('system-configs', [\App\Http\Controllers\Api\SystemConfigController::class, 'index']);
    Route::get('system-configs/frontend', [\App\Http\Controllers\Api\SystemConfigController::class, 'frontend']);
    Route::get('system-configs/status', [\App\Http\Controllers\Api\SystemConfigController::class, 'status']);
});
