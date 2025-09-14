<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Admin\UserFetch;
use App\Http\Requests\Admin\UserGenerate;
use App\Http\Requests\Admin\UserSendMail;
use App\Http\Requests\Admin\UserUpdate;
use App\Jobs\SendEmailJob;
use App\Services\AuthService;
use App\Services\UserService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use function App\Http\Controllers\Admin\abort;
use function App\Http\Controllers\Admin\config;
use function App\Http\Controllers\Admin\response;

class ShareController extends Controller
{
    /**
     * 生成推广链接
     */
    public function create(Request $request)
    {
        $request->validate([
                               'device_id' => 'required|string|max:128'
                           ]);

        $deviceId = $request->input('device_id');
        $shareUrl = url('/register?ref_device=' . $deviceId);

        return response()->json([
                                    'device_id' => $deviceId,
                                    'share_url' => $shareUrl
                                ]);
    }

    /**
     * 查询推广统计
     */
    public function stats(Request $request)
    {
        $request->validate([
                               'device_id' => 'required|string|max:128'
                           ]);

        $deviceId = $request->input('device_id');
        $stat = DB::table('invite_stats')->where('device_id', $deviceId)->first();

        return response()->json([
                                    'device_id' => $deviceId,
                                    'register_count' => $stat->register_count ?? 0,
                                    'paid_count' => $stat->paid_count ?? 0,
                                    'last_update' => $stat->last_update ?? null,
                                ]);
    }
}
