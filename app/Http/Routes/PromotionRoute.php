<?php
namespace App\Http\Routes;

use Illuminate\Contracts\Routing\Registrar;

class PromotionRoute
{
    public function map(Registrar $router)
    {
        // 推广系统 API 路由
        $router->group([
            'prefix' => 'share'
        ], function ($router) {
            $router->post('/generate', 'Api\\ShareController@generate');
            $router->post('/bind', 'Api\\ShareController@bind');
            $router->get('/stats/{promotion_code}', 'Api\\ShareController@stats');
            $router->post('/exchange-vip', 'Api\\ShareController@exchangeVip');
            $router->get('/promoter/{device_id}', 'Api\\ShareController@promoterInfo');
            $router->get('/reward-status/{device_id}', 'Api\\ShareController@rewardStatus');
        });

        // 设备管理接口
        $router->group([
            'prefix' => 'device'
        ], function ($router) {
            $router->get('/permission/{device_id}', 'Api\\DeviceController@permission');
            $router->post('/session/start', 'Api\\DeviceController@startSession');
            $router->put('/session/update', 'Api\\DeviceController@updateSession');
            $router->post('/session/end', 'Api\\DeviceController@endSession');
            $router->get('/usage-history/{device_id}', 'Api\\DeviceController@usageHistory');
            $router->get('/session-history/{device_id}', 'Api\\DeviceController@sessionHistory');
            $router->get('/active-session/{device_id}', 'Api\\DeviceController@activeSession');
            $router->post('/session/force-end', 'Api\\DeviceController@forceEndSession');
        });

        // 后台管理 API 路由
        $router->group([
            'prefix' => 'admin/promotion',
            //'middleware' => 'admin'
        ], function ($router) {
            $router->get('/list', 'Admin\\PromotionController@promotionList');
            $router->get('/device-list', 'Admin\\PromotionController@deviceList');
            $router->post('/force-end-session', 'Admin\\PromotionController@forceEndDeviceSession');
            $router->get('/stats-overview', 'Admin\\PromotionController@statsOverview');
            $router->get('/ranking', 'Admin\\PromotionController@promotionRanking');
            $router->post('/cleanup-sessions', 'Admin\\PromotionController@cleanupSessions');
            $router->post('/reset-device-usage', 'Admin\\PromotionController@resetDeviceUsage');
        });
    }
}