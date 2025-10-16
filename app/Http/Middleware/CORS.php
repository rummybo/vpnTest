<?php

namespace App\Http\Middleware;

use Closure;

class CORS
{
    public function handle($request, Closure $next)
    {
        $origin = $request->header('origin');
        if (empty($origin)) {
            $referer = $request->header('referer');
            if (!empty($referer) && preg_match("/^((https|http):\/\/)?([^\/]+)/i", $referer, $matches)) {
                $origin = $matches[0];
            }
        }
        $response = $next($request);
        
        // 检查响应对象是否有header方法，避免文件下载响应的兼容性问题
        if (method_exists($response, 'header')) {
            $response->header('Access-Control-Allow-Origin', trim($origin, '/'));
            $response->header('Access-Control-Allow-Methods', 'GET,POST,OPTIONS,HEAD');
            $response->header('Access-Control-Allow-Headers', 'Origin,Content-Type,Accept,Authorization,X-Request-With');
            $response->header('Access-Control-Allow-Credentials', 'true');
            $response->header('Access-Control-Max-Age', 10080);
        } elseif (isset($response->headers)) {
            // 使用headers属性设置响应头（适用于文件下载等特殊响应）
            $response->headers->set('Access-Control-Allow-Origin', trim($origin, '/'));
            $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,OPTIONS,HEAD');
            $response->headers->set('Access-Control-Allow-Headers', 'Origin,Content-Type,Accept,Authorization,X-Request-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', 10080);
        }

        return $response;
    }
}
