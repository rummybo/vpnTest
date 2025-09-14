<?php

namespace App\Http\Routes;

use Illuminate\Contracts\Routing\Registrar;

class SmsRoute
{
    public function map(Registrar $router)
    {
        $router->group([
            'prefix' => 'sms'
        ], function ($router) {
            $router->post('/send', 'Api\\SmsController@send');
            $router->post('/verify', 'Api\\SmsController@verify');
        });
    }
}