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
     * phone: 手机号
     * type: 短信类型 (register|reset_password)
     */
    public function send(Request $request): \Illuminate\Http\JsonResponse
    {
        $mobile = $request->input('phone');
        $type = $request->input('type', 'register'); // 默认为注册类型

        if (!$mobile) {
            return response()->json([
                'code' => 0,
                'msg'  => '缺少必要参数 phone'
            ]);
        }

        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            return response()->json([
                'code' => 0,
                'msg'  => '手机号格式不正确'
            ]);
        }

        // 验证短信类型
        if (!in_array($type, ['register', 'reset_password', 'login'])) {
            return response()->json([
                'code' => 0,
                'msg'  => '短信类型不正确'
            ]);
        }

        // 如果是重置密码或登录，需要验证手机号是否已注册
        if (in_array($type, ['reset_password', 'login'])) {
            $user = \App\Models\User::where('phone', $mobile)->first();
            if (!$user) {
                return response()->json([
                    'code' => 0,
                    'msg'  => '该手机号未注册'
                ]);
            }
        }

        // 检查发送频率限制（同一手机号1分钟内只能发送一次）
        $rateLimitKey = 'sms_rate_limit:' . $mobile;
        if (Cache::get($rateLimitKey)) {
            return response()->json([
                'code' => 0,
                'msg'  => '发送过于频繁，请稍后再试'
            ]);
        }

        // 生成 6 位随机验证码
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // 根据类型设置不同的缓存键和有效期
        $cacheKey = 'sms_code:' . $type . ':' . $mobile;
        $expireTime = $type === 'reset_password' ? 300 : 600; // 重置密码5分钟，注册10分钟

        // 保存到缓存
        Cache::set($cacheKey, $code, $expireTime);
        Cache::set($rateLimitKey, true, 60); // 设置发送频率限制

        // 根据类型设置不同的短信内容
        $content = $this->getSmsContent($type, $code);

        $smstype = 'notify';
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
        
        return response()->json([
            'code' => 1, 
            'msg'  => '短信发送成功', 
            'data' => [
                'type' => $type,
                'expire_time' => $expireTime,
                'sms_result' => $rs
            ]
        ]);
    }

    /**
     * 根据短信类型获取短信内容
     */
    private function getSmsContent($type, $code)
    {
        // 统一使用简洁的短信内容格式
        return sprintf('您的验证码：%s，如非本人操作，请忽略本短信!', $code);
    }

    /**
     * 验证验证码接口
     * POST /api/sms/verify
     * 参数：
     * phone: 手机号
     * code: 验证码
     * type: 短信类型 (register|reset_password)
     */
    public function verify(Request $request): \Illuminate\Http\JsonResponse
    {
        $mobile = $request->input('phone', '');
        $code   = $request->input('code', '');
        $type   = $request->input('type', 'register');

        if (!$mobile || !$code) {
            return response()->json(['code' => 0, 'msg' => '手机号或验证码不能为空']);
        }

        // 验证短信类型
        if (!in_array($type, ['register', 'reset_password', 'login'])) {
            return response()->json(['code' => 0, 'msg' => '短信类型不正确']);
        }

        // 根据类型获取对应的缓存键
        $cacheKey = 'sms_code:' . $type . ':' . $mobile;
        $cacheCode = Cache::get($cacheKey);
        
        if (!$cacheCode) {
            return response()->json(['code' => 0, 'msg' => '验证码已过期']);
        }

        if ($cacheCode !== $code) {
            return response()->json(['code' => 0, 'msg' => '验证码错误']);
        }

        return response()->json([
            'code' => 1, 
            'msg' => '验证码验证成功',
            'data' => [
                'type' => $type,
                'phone' => $mobile
            ]
        ]);
    }
}