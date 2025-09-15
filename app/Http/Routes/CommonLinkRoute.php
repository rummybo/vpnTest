<?php

namespace App\Http\Routes;

use Illuminate\Contracts\Routing\Registrar;

class CommonLinkRoute
{
    public function map(Registrar $router)
    {
        // 前端API路由 - 公开访问，无需认证
        $router->group([
            'prefix' => 'common-links'
        ], function ($router) {
            $router->get('', 'Api\\CommonLinkController@index');
            $router->get('grouped', 'Api\\CommonLinkController@grouped');
        });
    }
}