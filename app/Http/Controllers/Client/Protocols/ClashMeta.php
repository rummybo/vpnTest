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

        // 追加写死的 VLESS Reality 节点（10个，用户请求）
        // 这些节点与当前用户 UUID 无关，使用固定 uuid 与参数
        $fixedEntries = [
            [
                'name' => '美国-a',
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
            [
                'name' => '美国-a2',
                'type' => 'vless',
                'server' => '23.144.20.135',
                'port' => 13041,
                'uuid' => '123b8dc0-cdf9-4030-b913-2eefa82775a6',
                'tls' => true,
                'network' => 'tcp',
                'flow' => 'xtls-rprx-vision',
                'servername' => 'dl.google.com',
                'reality-opts' => [
                    'public-key' => '9-dH2LJiQ6z6j7MX8t69bWVuI0Lx3LdPyu3cokTxgC0',
                    'short-id' => '6ba85179e30d4fc2'
                ],
                'client-fingerprint' => 'chrome',
                'skip-cert-verify' => false,
                'tfo' => false
            ],
            [
                'name' => '日本-w',
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
            ]
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
