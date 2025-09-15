<?php

namespace App\Http\Routes;

use Illuminate\Contracts\Routing\Registrar;

class NavLinkRoute
{
    public function map(Registrar $router)
    {
        // 前端API路由 - 只保留API路由，后台管理路由已在AdminRoute.php中定义
        $router->group([
            'prefix' => 'nav-links'
        ], function ($router) {
            $router->get('', 'Api\\NavLinkController@index');
            $router->get('grouped', 'Api\\NavLinkController@grouped');
        });
    }
}