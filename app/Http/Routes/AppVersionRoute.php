<?php

namespace App\Http\Routes;

use Illuminate\Contracts\Routing\Registrar;

class AppVersionRoute
{
    public function map(Registrar $router)
    {
        // APP版本管理API路由
        $router->group([
            'prefix' => 'app-version'
        ], function ($router) {
            // 获取所有平台版本信息
            $router->get('', 'Api\\AppVersionController@getVersions');
            
            // 获取指定平台版本信息
            $router->get('{platform}', 'Api\\AppVersionController@getVersionByPlatform');
            
            // 检查更新接口
            $router->post('check-update', 'Api\\AppVersionController@checkUpdate');
        });
    }
}