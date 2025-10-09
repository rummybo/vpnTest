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

// v2board风格API路由 (管理后台)
Route::prefix('api/v1/{secure_path}')->group(function () {
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
});
