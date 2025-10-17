<?php

namespace App\Http\Controllers\Passport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Passport\AuthRegister;
use App\Http\Requests\Passport\AuthForget;
use App\Http\Requests\Passport\AuthLogin;
use App\Jobs\SendEmailJob;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Plan;
use App\Models\User;
use App\Models\InviteCode;
use App\Utils\Helper;
use App\Utils\Dict;
use App\Utils\CacheKey;
use ReCaptcha\ReCaptcha;

class AuthController extends Controller
{
    public function loginWithMailLink(Request $request)
    {
        if (!(int)config('v2board.login_with_mail_link_enable')) {
            abort(404);
        }
        $params = $request->validate([
                                         'email' => 'required|email:strict',
                                         'redirect' => 'nullable'
                                     ]);

        if (Cache::get(CacheKey::get('LAST_SEND_LOGIN_WITH_MAIL_LINK_TIMESTAMP', $params['email']))) {
            abort(500, __('Sending frequently, please try again later'));
        }

        $user = User::where('email', $params['email'])->first();
        if (!$user) {
            return response([
                                'data' => true
                            ]);
        }

        $code = Helper::guid();
        $key = CacheKey::get('TEMP_TOKEN', $code);
        Cache::put($key, $user->id, 300);
        Cache::put(CacheKey::get('LAST_SEND_LOGIN_WITH_MAIL_LINK_TIMESTAMP', $params['email']), time(), 60);


        $redirect = '/#/login?verify=' . $code . '&redirect=' . ($request->input('redirect') ? $request->input('redirect') : 'dashboard');
        if (config('v2board.app_url')) {
            $link = config('v2board.app_url') . $redirect;
        } else {
            $link = url($redirect);
        }

        SendEmailJob::dispatch([
                                   'email' => $user->email,
                                   'subject' => __('Login to :name', [
                                       'name' => config('v2board.app_name', 'V2Board')
                                   ]),
                                   'template_name' => 'login',
                                   'template_value' => [
                                       'name' => config('v2board.app_name', 'V2Board'),
                                       'link' => $link,
                                       'url' => config('v2board.app_url')
                                   ]
                               ]);

        return response([
                            'data' => $link
                        ]);

    }

    public function register(Request $request)
    {
        if ((int)config('v2board.register_limit_by_ip_enable', 1)) {
            $registerCountByIP = Cache::get(CacheKey::get('REGISTER_IP_RATE_LIMIT', $request->ip())) ?? 0;
            if ((int)$registerCountByIP >= (int)config('v2board.register_limit_count', 5)) {
                abort(500, __('Register frequently, please try again after :minute minute', [
                    'minute' => config('v2board.register_limit_expire', 180)
                ]));
            }
        }
        if ((int)config('v2board.recaptcha_enable', 0)) {
            $recaptcha = new ReCaptcha(config('v2board.recaptcha_key'));
            $recaptchaResp = $recaptcha->verify($request->input('recaptcha_data'));
            if (!$recaptchaResp->isSuccess()) {
                abort(500, __('Invalid code is incorrect'));
            }
        }

        $email = $request->input('email');
        $password = $request->input('password');

        // 如果没有提供密码（邮箱或手机验证码注册），使用默认密码
        if (empty($password)) {
            $password = config('v2board.default_register_password', '123456789');
        }

        // 检查是否有username或phone字段，如果有则使用对应的注册方式
        $registerType = 'email';
        $identifier = $email;

        if ($request->has('username') && !empty($request->input('username'))) {
            $registerType = 'username';
            $identifier = $request->input('username');
        } elseif ($request->has('phone') && !empty($request->input('phone'))) {
            $registerType = 'phone';
            $identifier = $request->input('phone');
        }

        // 邮箱相关验证（如果是邮箱注册）
        if ($registerType === 'email') {
            if ((int)config('v2board.email_whitelist_enable', 0)) {
                if (!Helper::emailSuffixVerify(
                    $identifier,
                    config('v2board.email_whitelist_suffix', Dict::EMAIL_WHITELIST_SUFFIX_DEFAULT))
                ) {
                    abort(500, __('Email suffix is not in the Whitelist'));
                }
            }
            if ((int)config('v2board.email_gmail_limit_enable', 0)) {
                $prefix = explode('@', $identifier)[0];
                if (strpos($prefix, '.') !== false || strpos($prefix, '+') !== false) {
                    abort(500, __('Gmail alias is not supported'));
                }
            }
            if ((int)config('v2board.email_verify', 0)) {
                if (empty($request->input('email_code'))) {
                    abort(500, __('Email verification code cannot be empty'));
                }
                if ((string)Cache::get(CacheKey::get('EMAIL_VERIFY_CODE', $identifier)) !== (string)$request->input('email_code')) {
                    abort(500, __('Incorrect email verification code'));
                }
            }
        }

        if ((int)config('v2board.stop_register', 0)) {
            abort(500, __('Registration has closed'));
        }
        if ((int)config('v2board.invite_force', 0)) {
            if (empty($request->input('invite_code'))) {
                abort(500, __('You must use the invitation code to register'));
            }
        }

        // 检查用户是否已存在
        $exist = $this->checkUserExists($registerType, $identifier);
        if ($exist) {
            //$user = $exist;
            abort(500, __(ucfirst($registerType) . ' already exists'));
        }else{
            $user = new User();
            // 根据注册类型设置相应字段
            if ($registerType === 'email') {
                $user->email = $identifier;
            } elseif ($registerType === 'username') {
                $user->username = $identifier;
            } elseif ($registerType === 'phone') {
                $user->phone = $identifier;
            }

            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->uuid = Helper::guid(true);
            $user->token = Helper::guid();
            if ($request->input('invite_code')) {
                $inviteCode = InviteCode::where('code', $request->input('invite_code'))
                                        ->where('status', 0)
                                        ->first();
                if (!$inviteCode) {
                    if ((int)config('v2board.invite_force', 0)) {
                        abort(500, __('Invalid invitation code'));
                    }
                } else {
                    $user->invite_user_id = $inviteCode->user_id ? $inviteCode->user_id : null;
                    if (!(int)config('v2board.invite_never_expire', 0)) {
                        $inviteCode->status = 1;
                        $inviteCode->save();
                    }
                }
            }

            // try out
            if ((int)config('v2board.try_out_plan_id', 0)) {
                $plan = Plan::find(config('v2board.try_out_plan_id'));
                if ($plan) {
                    $user->transfer_enable = $plan->transfer_enable * 1073741824;
                    $user->plan_id = $plan->id;
                    $user->group_id = $plan->group_id;
                    $user->expired_at = time() + (config('v2board.try_out_hour', 1) * 3600);
                    $user->speed_limit = $plan->speed_limit;
                }
            }

            if (!$user->save()) {
                abort(500, __('Register failed'));
            }
        }
        // 清除邮箱验证码缓存（如果是邮箱注册）
        if ($registerType === 'email' && (int)config('v2board.email_verify', 0)) {
            Cache::forget(CacheKey::get('EMAIL_VERIFY_CODE', $identifier));
        }

        $user->last_login_at = time();
        $user->save();

        if ((int)config('v2board.register_limit_by_ip_enable', 0)) {
            Cache::put(
                CacheKey::get('REGISTER_IP_RATE_LIMIT', $request->ip()),
                (int)$registerCountByIP + 1,
                (int)config('v2board.register_limit_expire', 60) * 60
            );
        }

        $authService = new AuthService($user);

        return response()->json([
                                    'data' => $authService->generateAuthData($request)
                                ]);
    }

    public function login(Request $request)
    {
        $password = $request->input('password');
        $smsCode = $request->input('sms_code');

        // 使用getLoginType方法确定登录类型
        $loginType = $this->getLoginType($request);
        $identifier = $request->input($loginType);

        // 确保email字段有值（用于验证规则）
        if ($loginType === 'email') {
            $request->merge(['email' => $identifier]);
        }

        // 根据登录类型查找用户
        $user = $this->findUserByIdentifier($loginType, $identifier);
        if (!$user) {
            abort(500, __('Incorrect email or password'));
        }

        // 手机号登录支持验证码和密码两种方式
        if ($loginType === 'phone' && !empty($smsCode)) {
            // 使用短信验证码登录
            $cacheKey = 'sms_code:login:' . $identifier;
            $cacheCode = Cache::get($cacheKey);

            if (!$cacheCode) {
                abort(500, __('验证码已过期'));
            }

            if ($cacheCode !== $smsCode) {
                abort(500, __('验证码错误'));
            }

            // 验证码正确，清除缓存
            Cache::forget($cacheKey);
        } else {
            // 使用密码登录（用户名、邮箱、手机号密码登录）
            if (empty($password)) {
                abort(500, __('密码不能为空'));
            }

            if ((int)config('v2board.password_limit_enable', 1)) {
                $passwordErrorCount = (int)Cache::get(CacheKey::get('PASSWORD_ERROR_LIMIT', $identifier), 0);
                if ($passwordErrorCount >= (int)config('v2board.password_limit_count', 5)) {
                    abort(500, __('There are too many password errors, please try again after :minute minutes.', [
                        'minute' => config('v2board.password_limit_expire', 60)
                    ]));
                }
            }

            if (!Helper::multiPasswordVerify(
                $user->password_algo,
                $user->password_salt,
                $password,
                $user->password)
            ) {
                if ((int)config('v2board.password_limit_enable')) {
                    Cache::put(
                        CacheKey::get('PASSWORD_ERROR_LIMIT', $identifier),
                        (int)$passwordErrorCount + 1,
                        60 * (int)config('v2board.password_limit_expire', 60)
                    );
                }
                abort(500, __('Incorrect email or password'));
            }
        }

        if ($user->banned) {
            abort(500, __('Your account has been suspended'));
        }

        $authService = new AuthService($user);
        return response([
                            'data' => $authService->generateAuthData($request)
                        ]);
    }

    public function token2Login(Request $request)
    {
        if ($request->input('token')) {
            $redirect = '/#/login?verify=' . $request->input('token') . '&redirect=' . ($request->input('redirect') ? $request->input('redirect') : 'dashboard');
            if (config('v2board.app_url')) {
                $location = config('v2board.app_url') . $redirect;
            } else {
                $location = url($redirect);
            }
            return redirect()->to($location)->send();
        }

        if ($request->input('verify')) {
            $key =  CacheKey::get('TEMP_TOKEN', $request->input('verify'));
            $userId = Cache::get($key);
            if (!$userId) {
                abort(500, __('Token error'));
            }
            $user = User::find($userId);
            if (!$user) {
                abort(500, __('The user does not '));
            }
            if ($user->banned) {
                abort(500, __('Your account has been suspended'));
            }
            Cache::forget($key);
            $authService = new AuthService($user);
            return response([
                                'data' => $authService->generateAuthData($request)
                            ]);
        }
    }

    public function getQuickLoginUrl(Request $request)
    {
        $authorization = $request->input('auth_data') ?? $request->header('authorization');
        if (!$authorization) abort(403, '未登录或登陆已过期');

        $user = AuthService::decryptAuthData($authorization);
        if (!$user) abort(403, '未登录或登陆已过期');

        $code = Helper::guid();
        $key = CacheKey::get('TEMP_TOKEN', $code);
        Cache::put($key, $user['id'], 60);
        $redirect = '/#/login?verify=' . $code . '&redirect=' . ($request->input('redirect') ? $request->input('redirect') : 'dashboard');
        if (config('v2board.app_url')) {
            $url = config('v2board.app_url') . $redirect;
        } else {
            $url = url($redirect);
        }
        return response([
                            'data' => $url
                        ]);
    }

    public function forget(AuthForget $request)
    {
        if ((string)Cache::get(CacheKey::get('EMAIL_VERIFY_CODE', $request->input('email'))) !== (string)$request->input('email_code')) {
            abort(500, __('Incorrect email verification code'));
        }
        $user = User::where('email', $request->input('email'))->first();
        if (!$user) {
            abort(500, __('This email is not registered in the system'));
        }
        $user->password = password_hash($request->input('password'), PASSWORD_DEFAULT);
        $user->password_algo = NULL;
        $user->password_salt = NULL;
        if (!$user->save()) {
            abort(500, __('Reset failed'));
        }
        Cache::forget(CacheKey::get('EMAIL_VERIFY_CODE', $request->input('email')));
        return response([
                            'data' => true
                        ]);
    }

    public function resetPasswordByPhone(Request $request)
    {
        $request->validate([
                               'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
                               'code' => 'required|string|size:6',
                               'password' => 'required|string|min:6|max:32'
                           ]);

        $phone = $request->input('phone');
        $code = $request->input('code');
        $password = $request->input('password');

        // 验证短信验证码
        $cacheKey = 'sms_code:reset_password:' . $phone;
        $cacheCode = Cache::get($cacheKey);

        if (!$cacheCode) {
            abort(500, __('验证码已过期'));
        }

        if ($cacheCode !== $code) {
            abort(500, __('验证码错误'));
        }

        // 查找用户
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            abort(500, __('密码重置失败'));
        }

        // 更新密码
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->password_algo = NULL;
        $user->password_salt = NULL;

        if (!$user->save()) {
            abort(500, __('密码重置失败'));
        }

        // 清除验证码缓存
        Cache::forget($cacheKey);

        // 记录日志
        \Log::info('用户通过手机号重置密码', [
            'user_id' => $user->id,
            'phone' => $phone,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response([
                            'data' => true,
                            'message' => '密码重置成功'
                        ]);
    }

    /**
     * 获取注册类型
     */
    private function getRegisterType($request)
    {
        if ($request->has('username') && !empty($request->input('username'))) {
            return 'username';
        }
        if ($request->has('phone') && !empty($request->input('phone'))) {
            return 'phone';
        }
        return 'email';
    }

    /**
     * 获取登录类型
     */
    private function getLoginType($request)
    {
        if ($request->has('username') && !empty($request->input('username'))) {
            return 'username';
        }
        if ($request->has('phone') && !empty($request->input('phone'))) {
            return 'phone';
        }
        return 'email';
    }

    /**
     * 检查用户是否已存在
     */
    private function checkUserExists($type, $identifier)
    {
        switch ($type) {
            case 'username':
                return User::where('username', $identifier)->first();
            case 'phone':
                return User::where('phone', $identifier)->first();
            case 'email':
            default:
                return User::where('email', $identifier)->first();
        }
    }

    /**
     * 根据标识符查找用户
     */
    private function findUserByIdentifier($type, $identifier)
    {
        switch ($type) {
            case 'username':
                return User::where('username', $identifier)->first();
            case 'phone':
                return User::where('phone', $identifier)->first();
            case 'email':
            default:
                return User::where('email', $identifier)->first();
        }
    }

    /**
     * 修改密码
     * 支持三种登录方式：邮箱、用户名、手机号
     */
    public function changePassword(Request $request)
    {
        // 1. 参数验证
        $request->validate([
                               'identifier' => 'required|string', // 邮箱/用户名/手机号
                               'old_password' => 'required|string',
                               'new_password' => 'required|string|min:6|max:32',
                               'confirm_password' => 'required|string|same:new_password'
                           ]);

        $identifier = $request->input('identifier');
        $oldPassword = $request->input('old_password');
        $newPassword = $request->input('new_password');

        // 2. 判断登录类型并查找用户
        $loginType = $this->determineLoginType($identifier);
        $user = $this->findUserByIdentifier($loginType, $identifier);

        if (!$user) {
            abort(500, __('用户不存在'));
        }

        // 3. 验证原始密码
        // 使用 PHP 内置的 password_verify 函数验证原始密码是否正确
        if (!password_verify($oldPassword, $user->password)) {
            abort(500, __('原始密码错误'));
        }

        // 4. 更新为新密码
        $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->password_algo = NULL;
        $user->password_salt = NULL;

        if (!$user->save()) {
            abort(500, __('密码修改失败'));
        }

        // 5. 记录日志
        \Log::info('用户修改密码', [
            'user_id' => $user->id,
            'login_type' => $loginType,
            'identifier' => $identifier,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response([
                            'data' => true,
                            'message' => '密码修改成功'
                        ]);
    }

    /**
     * 判断登录类型
     */
    private function determineLoginType($identifier)
    {
        // 手机号正则：1开头，第二位3-9，总共11位
        if (preg_match('/^1[3-9]\d{9}$/', $identifier)) {
            return 'phone';
        }

        // 邮箱正则
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        // 默认为用户名
        return 'username';
    }

    /**
     * 获取系统用户订阅信息（暂停使用）
     */
    public function getSubscribe()
    {
        //获取系统用户
        $user = User::where('is_system', 1)
                    ->select([
                                 'plan_id',
                                 'token',
                                 'expired_at',
                                 'u',
                                 'd',
                                 'transfer_enable',
                                 'email',
                                 'uuid'
                             ])
                    ->first();
        if (!$user) {
            abort(500, __('The user does not exist'));
        }
        //新增加的   暂停没有登录的用户使用
        abort(500, __('The user does not exist'));
        if ($user->plan_id) {
            $user['plan'] = Plan::find($user->plan_id);
            if (!$user['plan']) {
                abort(500, __('Subscription plan does not exist'));
            }
        }
        $user['subscribe_url'] = Helper::getSubscribeUrl("/api/v1/client/subscribe?token={$user['token']}");

        $userService = new UserService();
        $user['reset_day'] = $userService->getResetDay($user);
        return response([
                            'data' => $user
                        ]);
    }

}
