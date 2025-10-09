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
    Route::resource('nav_links', 'App\Http\Controllers\Admin\NavLinkController');
});

// 常用导航管理路由
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum'])->group(function () {
    Route::resource('common_links', 'App\Http\Controllers\Admin\CommonLinkController');
});

// 前端导航页管理路由
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum'])->group(function () {
    Route::resource('frontend_nav_pages', 'App\Http\Controllers\Admin\FrontendNavPageController');
    
    // API格式路由 (v2board后台使用)
    Route::post('frontend_nav_pages/fetch', 'App\Http\Controllers\Admin\FrontendNavPageController@fetch');
    Route::post('frontend_nav_pages/save', 'App\Http\Controllers\Admin\FrontendNavPageController@save');
    Route::post('frontend_nav_pages/drop', 'App\Http\Controllers\Admin\FrontendNavPageController@drop');
    Route::post('frontend_nav_pages/show', 'App\Http\Controllers\Admin\FrontendNavPageController@show');
    Route::post('frontend_nav_pages/sort', 'App\Http\Controllers\Admin\FrontendNavPageController@sort');
});

// 前端API路由
Route::prefix('api')->group(function () {
    Route::get('frontend-nav-pages', 'App\Http\Controllers\Api\FrontendNavPageController@index');
    Route::get('frontend-nav-pages/grouped', 'App\Http\Controllers\Api\FrontendNavPageController@grouped');
    Route::get('frontend-nav-pages/popular', 'App\Http\Controllers\Api\FrontendNavPageController@popular');
});
