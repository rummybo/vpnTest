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

        // 自动识别 Clash / Meta / Mihomo / Stash / Windows 等 UA
        if (
            strpos($flag, 'clash') !== false ||
            strpos($flag, 'meta') !== false ||
            strpos($flag, 'mihomo') !== false ||
            strpos($flag, 'stash') !== false ||
            strpos($flag, 'windows') !== false ||  // ✅ 新增 Clash for Windows 识别
            strpos($flag, 'electron') !== false || // ✅ 一些 Clash GUI 的 UA 特征
            strpos($flag, 'cfw') !== false
        ) {
            $flag = 'clash';
        }

        $user = $request->user;

        // 检查账户是否有效
        $userService = new UserService();
        if ($userService->isAvailable($user)) {
            $serverService = new ServerService();
            $servers = $serverService->getAvailableServers($user);

            $this->setSubscribeInfoToServers($servers, $user);

            // 修正双斜杠路径
            foreach (array_reverse(glob(app_path('Http/Controllers/Client/Protocols') . '/*.php')) as $file) {
                $file = 'App\\Http\\Controllers\\Client\\Protocols\\' . basename($file, '.php');
                $class = new $file($user, $servers);

                // ✅ 匹配到 Clash 协议类就输出 YAML
                if (strpos($flag, $class->flag) !== false) {
                    die($class->handle());
                }
            }

            // 默认输出 Base64 (V2RayN / Shadowrocket)
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
