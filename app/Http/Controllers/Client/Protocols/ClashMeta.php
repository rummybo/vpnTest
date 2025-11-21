<?php

namespace App\Http\Controllers\Client\Protocols;

use App\Utils\Helper;
use Symfony\Component\Yaml\Yaml;

class ClashMeta
{
    public $flag = 'meta';
    private $servers;
    private $user;

    public function __construct($user, $servers)
    {
        $this->user = $user;
        $this->servers = $servers;
    }

    public function handle()
    {
        $servers = $this->servers;
        $user = $this->user;
        $appName = config('v2board.app_name', 'V2Board');
        header("subscription-userinfo: upload={$user['u']}; download={$user['d']}; total={$user['transfer_enable']}; expire={$user['expired_at']}");
        header('profile-update-interval: 24');
        header("content-disposition:attachment;filename*=UTF-8''".rawurlencode($appName));
        $defaultConfig = base_path() . '/resources/rules/default.clash.yaml';
        $customConfig = base_path() . '/resources/rules/custom.clash.yaml';
        if (\File::exists($customConfig)) {
            $config = Yaml::parseFile($customConfig);
        } else {
            $config = Yaml::parseFile($defaultConfig);
        }
        $proxy = [];
        $proxies = [];

        foreach ($servers as $item) {
            if ($item['type'] === 'shadowsocks') {
                array_push($proxy, self::buildShadowsocks($user['uuid'], $item));
                array_push($proxies, $item['name']);
            }
            if ($item['type'] === 'vmess') {
                array_push($proxy, self::buildVmess($user['uuid'], $item));
                array_push($proxies, $item['name']);
            }
            if ($item['type'] === 'vless') {
                array_push($proxy, self::buildVless($user['uuid'], $item));
                array_push($proxies, $item['name']);
            }
            if ($item['type'] === 'trojan') {
                array_push($proxy, self::buildTrojan($user['uuid'], $item));
                array_push($proxies, $item['name']);
            }
        }

        // 追加写死的 VLESS Reality 节点（3个，用户请求）
        // 这些节点与当前用户 UUID 无关，使用固定 uuid 与参数
        $fixedEntries = [
            [
                'name' => 'US-瓦工',
                'type' => 'vless',
                'server' => '23.106.157.77',
                'port' => 21591,
                'uuid' => '35759465-561c-4365-ac41-158f8248649c',
                'tls' => true,
                'network' => 'tcp',
                'flow' => 'xtls-rprx-vision',
                'servername' => 'dl.google.com',
                'reality-opts' => [
                    'public-key' => 'F2vMYJfwgzxEZ4snj54KZ_2ol-Gad3Nkh8mfpJLkjnE',
                    'short-id' => '6ba85179e30d4fc2'
                ],
                'client-fingerprint' => 'chrome',
                'skip-cert-verify' => false,
                'tfo' => false
            ],
            [
                'name' => 'JPP',
                'type' => 'vless',
                'server' => '151.242.164.31',
                'port' => 31122,
                'uuid' => '8ca57d9c-545f-4417-b65f-1ca9692e9ee5',
                'tls' => true,
                'network' => 'tcp',
                'flow' => 'xtls-rprx-vision',
                'servername' => 'aod.itunes.apple.com',
                'reality-opts' => [
                    'public-key' => 'SMhrERlTCqtbZqS9H6oa5jzieaAnV5HvTwPgFw7V-1c',
                    'short-id' => '6ba85179e30d4fc2'
                ],
                'client-fingerprint' => 'chrome',
                'skip-cert-verify' => false,
                'tfo' => false
            ],
            [
                'name' => 'US-4837',
                'type' => 'vless',
                'server' => '23.144.12.20',
                'port' => 18370,
                'uuid' => 'c310c80e-949c-4bf4-a584-488230b7192a',
                'tls' => true,
                'network' => 'tcp',
                'flow' => 'xtls-rprx-vision',
                'servername' => 'dl.google.com',
                'reality-opts' => [
                    'public-key' => 'nMIa9DYD9L6B7XZLB1sLZ_ExytFPdz9ILcJc6Jwegg4',
                    'short-id' => '6ba85179e30d4fc2'
                ],
                'client-fingerprint' => 'chrome',
                'skip-cert-verify' => false,
                'tfo' => false
            ],
            // 新增 5 个硬编码 Reality 节点（用户补充）
            [
                'name' => '53c2f063-vless_reality_vision',
                'type' => 'vless',
                'server' => '82.27.11.30',
                'port' => 26651,
                'uuid' => '53c2f063-c2e5-49c7-b9b8-c8e3019077d4',
                'tls' => true,
                'network' => 'tcp',
                'flow' => 'xtls-rprx-vision',
                'servername' => 'osxapps.itunes.apple.com',
                'reality-opts' => [
                    'public-key' => 'ICZUHkfKHIg9d_0BlZAMlNNLQV7UGC1qnT-IC-FuPnA',
                    'short-id' => '6ba85179e30d4fc2'
                ],
                'client-fingerprint' => 'chrome',
                'skip-cert-verify' => false,
                'tfo' => false
            ],
            [
                'name' => '3837424d-vless_reality_vision',
                'type' => 'vless',
                'server' => '103.238.129.181',
                'port' => 24187,
                'uuid' => '3837424d-e3da-4aee-8a85-6645a010b137',
                'tls' => true,
                'network' => 'tcp',
                'flow' => 'xtls-rprx-vision',
                'servername' => 'download-installer.cdn.mozilla.net',
                'reality-opts' => [
                    'public-key' => 'urTG2gj0lQ1XTAXYyxK_pqOzspBCEUHOao_jesG1JFk',
                    'short-id' => '6ba85179e30d4fc2'
                ],
                'client-fingerprint' => 'chrome',
                'skip-cert-verify' => false,
                'tfo' => false
            ],
            [
                'name' => 'a5d2efee-vless_reality_vision',
                'type' => 'vless',
                'server' => '216.238.55.31',
                'port' => 25704,
                'uuid' => 'a5d2efee-3b23-46ce-9f69-c2dd0c50ffd6',
                'tls' => true,
                'network' => 'tcp',
                'flow' => 'xtls-rprx-vision',
                'servername' => 'download-installer.cdn.mozilla.net',
                'reality-opts' => [
                    'public-key' => 'QZ2JGMfLgriHUcbRhDwhmmQmrN4fe5CrPUa-bf3H61s',
                    'short-id' => '6ba85179e30d4fc2'
                ],
                'client-fingerprint' => 'chrome',
                'skip-cert-verify' => false,
                'tfo' => false
            ],
            [
                'name' => '6aaa5635-vless_reality_vision',
                'type' => 'vless',
                'server' => '23.156.152.168',
                'port' => 26988,
                'uuid' => '6aaa5635-9157-4ebc-9274-8570545db4b8',
                'tls' => true,
                'network' => 'tcp',
                'flow' => 'xtls-rprx-vision',
                'servername' => 'cdn-dynmedia-1.microsoft.com',
                'reality-opts' => [
                    'public-key' => '34VmgcF-Ei4JbThTZGqIM8NY5_Une45etie7jEyN6h0',
                    'short-id' => '6ba85179e30d4fc2'
                ],
                'client-fingerprint' => 'chrome',
                'skip-cert-verify' => false,
                'tfo' => false
            ],
            [
                'name' => 'c6c5e498-vless_reality_vision',
                'type' => 'vless',
                'server' => '23.156.152.96',
                'port' => 13211,
                'uuid' => 'c6c5e498-fdd6-4aa1-8846-8b6169258f66',
                'tls' => true,
                'network' => 'tcp',
                'flow' => 'xtls-rprx-vision',
                'servername' => 'www.cisco.com',
                'reality-opts' => [
                    'public-key' => 'xr-FoTwrFjs0_YqNCCH7srhVCI1ckHcp9XiZCZRJ2j8',
                    'short-id' => '6ba85179e30d4fc2'
                ],
                'client-fingerprint' => 'chrome',
                'skip-cert-verify' => false,
                'tfo' => false
            ],
        ];

        foreach ($fixedEntries as $fixed) {
            if (!in_array($fixed['name'], $proxies)) {
                array_push($proxy, $fixed);
                array_push($proxies, $fixed['name']);
            }
        }

        $config['proxies'] = array_merge($config['proxies'] ? $config['proxies'] : [], $proxy);
        foreach ($config['proxy-groups'] as $k => $v) {
            if (!is_array($config['proxy-groups'][$k]['proxies'])) $config['proxy-groups'][$k]['proxies'] = [];
            $isFilter = false;
            foreach ($config['proxy-groups'][$k]['proxies'] as $src) {
                foreach ($proxies as $dst) {
                    if (!$this->isRegex($src)) continue;
                    $isFilter = true;
                    $config['proxy-groups'][$k]['proxies'] = array_values(array_diff($config['proxy-groups'][$k]['proxies'], [$src]));
                    if ($this->isMatch($src, $dst)) {
                        array_push($config['proxy-groups'][$k]['proxies'], $dst);
                    }
                }
                if ($isFilter) continue;
            }
            if ($isFilter) continue;
            $config['proxy-groups'][$k]['proxies'] = array_merge($config['proxy-groups'][$k]['proxies'], $proxies);
        }
        $config['proxy-groups'] = array_filter($config['proxy-groups'], function($group) {
            return $group['proxies'];
        });
        $config['proxy-groups'] = array_values($config['proxy-groups']);
        // Force the current subscription domain to be a direct rule
        $subsDomain = $_SERVER['HTTP_HOST'];
        if ($subsDomain) {
            array_unshift($config['rules'], "DOMAIN,{$subsDomain},DIRECT");
        }

        $yaml = Yaml::dump($config, 2, 4, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        $yaml = str_replace('$app_name', config('v2board.app_name', 'V2Board'), $yaml);
        return $yaml;
    }

    public static function buildShadowsocks($password, $server)
    {
        if ($server['cipher'] === '2022-blake3-aes-128-gcm') {
            $serverKey = Helper::getServerKey($server['created_at'], 16);
            $userKey = Helper::uuidToBase64($password, 16);
            $password = "{$serverKey}:{$userKey}";
        }
        if ($server['cipher'] === '2022-blake3-aes-256-gcm') {
            $serverKey = Helper::getServerKey($server['created_at'], 32);
            $userKey = Helper::uuidToBase64($password, 32);
            $password = "{$serverKey}:{$userKey}";
        }
        $array = [];
        $array['name'] = $server['name'];
        $array['type'] = 'ss';
        $array['server'] = $server['host'];
        $array['port'] = $server['port'];
        $array['cipher'] = $server['cipher'];
        $array['password'] = $password;
        $array['udp'] = true;
        return $array;
    }

    public static function buildVmess($uuid, $server)
    {
        $array = [];
        $array['name'] = $server['name'];
        $array['type'] = 'vmess';
        $array['server'] = $server['host'];
        $array['port'] = $server['port'];
        $array['uuid'] = $uuid;
        $array['alterId'] = 0;
        $array['cipher'] = 'auto';
        $array['udp'] = true;

        if ($server['tls']) {
            $array['tls'] = true;
            if ($server['tlsSettings']) {
                $tlsSettings = $server['tlsSettings'];
                if (isset($tlsSettings['allowInsecure']) && !empty($tlsSettings['allowInsecure']))
                    $array['skip-cert-verify'] = ($tlsSettings['allowInsecure'] ? true : false);
                if (isset($tlsSettings['serverName']) && !empty($tlsSettings['serverName']))
                    $array['servername'] = $tlsSettings['serverName'];
            }
        }
        if ($server['network'] === 'tcp') {
            $tcpSettings = $server['networkSettings'];
            if (isset($tcpSettings['header']['type'])) $array['network'] = $tcpSettings['header']['type'];
            if (isset($tcpSettings['header']['request']['path'][0])) $array['http-opts']['path'] = $tcpSettings['header']['request']['path'][0];
        }
        if ($server['network'] === 'ws') {
            $array['network'] = 'ws';
            if ($server['networkSettings']) {
                $wsSettings = $server['networkSettings'];
                $array['ws-opts'] = [];
                if (isset($wsSettings['path']) && !empty($wsSettings['path']))
                    $array['ws-opts']['path'] = $wsSettings['path'];
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']))
                    $array['ws-opts']['headers'] = ['Host' => $wsSettings['headers']['Host']];
                if (isset($wsSettings['path']) && !empty($wsSettings['path']))
                    $array['ws-path'] = $wsSettings['path'];
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']))
                    $array['ws-headers'] = ['Host' => $wsSettings['headers']['Host']];
            }
        }
        if ($server['network'] === 'grpc') {
            $array['network'] = 'grpc';
            if ($server['networkSettings']) {
                $grpcSettings = $server['networkSettings'];
                $array['grpc-opts'] = [];
                if (isset($grpcSettings['serviceName'])) $array['grpc-opts']['grpc-service-name'] = $grpcSettings['serviceName'];
            }
        }

        return $array;
    }

    public static function buildVless($uuid, $server)
    {
        $array = [];
        $array['name'] = $server['name'];
        $array['type'] = 'vless';
        $array['server'] = $server['host'];
        $array['port'] = $server['port'];
        $array['uuid'] = $uuid;
        $array['udp'] = true;

        // 明确 network（tcp/ws/grpc），避免客户端默认处理差异
        if (!empty($server['network'])) {
            $array['network'] = $server['network'];
        }

        if (!empty($server['tls'])) {
            $array['tls'] = true;
            if (isset($server['tlsSettings']['serverName'])) {
                $array['servername'] = $server['tlsSettings']['serverName'];
            }
            // Clash Meta 的 REALITY 支持，通过 reality-opts 传递参数
            // 管理端约定将 REALITY 的字段放在 tlsSettings 内：publicKey / shortId / fingerprint
            $realityOpts = [];
            if (isset($server['tlsSettings']['publicKey']) && !empty($server['tlsSettings']['publicKey'])) {
                $realityOpts['public-key'] = $server['tlsSettings']['publicKey'];
            }
            if (isset($server['tlsSettings']['shortId']) && !empty($server['tlsSettings']['shortId'])) {
                $realityOpts['short-id'] = $server['tlsSettings']['shortId'];
            }
            if (!empty($realityOpts)) {
                $array['reality-opts'] = $realityOpts;
            }
            if (isset($server['tlsSettings']['fingerprint']) && !empty($server['tlsSettings']['fingerprint'])) {
                $array['client-fingerprint'] = $server['tlsSettings']['fingerprint'];
            }
        }
        if ($server['network'] === 'ws') {
            $array['network'] = 'ws';
            if ($server['networkSettings']) {
                $wsSettings = $server['networkSettings'];
                $array['ws-opts'] = [];
                if (isset($wsSettings['path']) && !empty($wsSettings['path']))
                    $array['ws-opts']['path'] = $wsSettings['path'];
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']))
                    $array['ws-opts']['headers'] = ['Host' => $wsSettings['headers']['Host']];
            }
        }
        if ($server['network'] === 'grpc') {
            $array['network'] = 'grpc';
            if ($server['networkSettings']) {
                $grpcSettings = $server['networkSettings'];
                $array['grpc-opts'] = [];
                if (isset($grpcSettings['serviceName'])) $array['grpc-opts']['grpc-service-name'] = $grpcSettings['serviceName'];
            }
        }

        return $array;
    }

    public static function buildTrojan($password, $server)
    {
        $array = [];
        $array['name'] = $server['name'];
        $array['type'] = 'trojan';
        $array['server'] = $server['host'];
        $array['port'] = $server['port'];
        $array['password'] = $password;
        $array['udp'] = true;
        if (!empty($server['server_name'])) $array['sni'] = $server['server_name'];
        if (!empty($server['allow_insecure'])) $array['skip-cert-verify'] = ($server['allow_insecure'] ? true : false);
        return $array;
    }

    private function isMatch($exp, $str)
    {
        return @preg_match($exp, $str);
    }

    private function isRegex($exp)
    {
        return @preg_match($exp, null) !== false;
    }
}
