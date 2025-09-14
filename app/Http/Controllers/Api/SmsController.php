<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SmsController extends Controller
{
    /**
     * 发送短信接口
     * POST /api/sms/send
     * 参数：
     * mobile: 手机号
     */
    public function send(Request $request): \Illuminate\Http\JsonResponse
    {
        $mobile = $request->input('mobile');
        if (!$mobile) {
            return response()->json([
                'code' => 0,
                'msg'  => '缺少必要参数 mobile'
            ]);
        }

        // 生成 6 位随机验证码
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // 保存到 Redis, 1分钟过期
        Cache::set('sms_code:' . $mobile, $code, 60);

        $smstype = 'notify';
        $content = sprintf('您的验证码：%s，如非本人操作，请忽略本短信!', $code);
        $encode  = "utf-8";
        $user    = 'hai96690BBB';
        $hash    = '63eb537a1c75d14b29651dde73626ff8';
        $url     = "http://www.huiyuandx.com/api/sms_send?";
        $url     .= "user=$user&hash=$hash&encode=$encode&smstype=$smstype";
        $url     .= "&mobile=" . $mobile . "&content=" . $content;

        $ctx = stream_context_create([
             'http' => [
                 'timeout' => 30,
                 'header'  => "User-Agent:Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1;YNSMS API v1.0;)"
             ]
         ]);

        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            return response()->json(['code' => 0, 'msg'  => '请求发送短信接口失败']);
        }

        $rs = json_decode($result, true);
        if (!$rs['result']) {
            return response()->json(['code' => 0, 'msg'  => '发送失败，错误代码: ' . $rs['errcode'] . '，信息: ' . $rs['msg']]);
        }
        
        return response()->json(['code' => 1, 'msg'  => '短信发送成功', 'data' => $rs]);
    }

    /**
     * 验证验证码接口
     * POST /api/sms/verify
     * 参数：
     * mobile: 手机号
     * code: 验证码
     */
    public function verify(Request $request): \Illuminate\Http\JsonResponse
    {
        $mobile = $request->input('mobile', '');
        $code   = $request->input('code', '');

        if (!$mobile || !$code) {
            return response()->json(['code' => 0, 'msg' => '手机号或验证码不能为空']);
        }

        $cacheCode = Cache::get('sms_code:' . $mobile);
        if (!$cacheCode) {
            return response()->json(['code' => 0, 'msg' => '验证码已过期']);
        }

        if ($cacheCode !== $code) {
            return response()->json(['code' => 0, 'msg' => '验证码错误']);
        }

        // 验证成功后删除缓存
        Cache::forget('sms_code:' . $mobile);

        return response()->json(['code' => 1, 'msg' => '验证码验证成功']);
    }
}