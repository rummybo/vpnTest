<?php

namespace App\Http\Routes;

use Illuminate\Contracts\Routing\Registrar;

class NavLinkRoute
{
    public function map(Registrar $router)
    {
        // 后台管理路由
        $router->group([
            'prefix' => 'nav-links'
        ], function ($router) {
            $router->get ('', 'Admin\\NavLinkController@index');
            $router->get ('create', 'Admin\\NavLinkController@create');
            $router->post('', 'Admin\\NavLinkController@store');
            $router->get ('{navLink}/edit', 'Admin\\NavLinkController@edit');
            $router->put ('{navLink}', 'Admin\\NavLinkController@update');
            $router->delete('{navLink}', 'Admin\\NavLinkController@destroy');
        });

        // 前端API路由
        $router->group([
            'prefix' => 'api/nav-links',
            'middleware' => 'api'
        ], function ($router) {
            $router->get('', 'Api\\NavLinkController@index');
            $router->get('{id}', 'Api\\NavLinkController@show');
            $router->get('grouped', 'Api\\NavLinkController@grouped');
        });
    }
}