<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Protocols\General;
use App\Http\Controllers\Controller;
use App\Services\ServerService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use App\Services\UserService;

class ClientController extends Controller
{
    public function subscribe(Request $request)
    {
        // 获取 UA 或手动 flag 参数
        $flag = $request->input('flag') ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $flag = strtolower($flag);

        // 兼容 Clash / Clash Meta 安卓客户端自动识别
        if (strpos($flag, 'clash') !== false || strpos($flag, 'meta') !== false) {
            $flag = 'clash';
        }

        $user = $request->user;

        // 检查账户是否有效（未过期、未封禁）
        $userService = new UserService();
        if ($userService->isAvailable($user)) {

            $serverService = new ServerService();
            $servers = $serverService->getAvailableServers($user);

            $this->setSubscribeInfoToServers($servers, $user);

            // 遍历协议目录，找到匹配的类进行处理
            foreach (array_reverse(glob(app_path('Http/Controllers/Client/Protocols') . '/*.php')) as $file) {
                $file = 'App\\Http\\Controllers\\Client\\Protocols\\' . basename($file, '.php');
                $class = new $file($user, $servers);

                if (strpos($flag, $class->flag) !== false) {
                    die($class->handle());
                }
            }

            // 默认输出 General（兼容 vmess 订阅）
            $class = new General($user, $servers);
            die($class->handle());
        }
    }


    private function setSubscribeInfoToServers(&$servers, $user)
    {
        if (!isset($servers[0])) return;
        if (!(int)config('v2board.show_info_to_server_enable', 0)) return;
        $useTraffic = $user['u'] + $user['d'];
        $totalTraffic = $user['transfer_enable'];
        $remainingTraffic = Helper::trafficConvert($totalTraffic - $useTraffic);
        $expiredDate = $user['expired_at'] ? date('Y-m-d', $user['expired_at']) : '长期有效';
        $userService = new UserService();
        $resetDay = $userService->getResetDay($user);
        array_unshift($servers, array_merge($servers[0], [
            'name' => "套餐到期：{$expiredDate}",
        ]));
        if ($resetDay) {
            array_unshift($servers, array_merge($servers[0], [
                'name' => "距离下次重置剩余：{$resetDay} 天",
            ]));
        }
        array_unshift($servers, array_merge($servers[0], [
            'name' => "剩余流量：{$remainingTraffic}",
        ]));
    }
}
