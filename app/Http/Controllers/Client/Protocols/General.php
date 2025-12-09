<?php

namespace App\Http\Controllers\Client\Protocols;


use App\Utils\Helper;

class General
{
    public $flag = 'general';
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
        $uri = '';

        foreach ($servers as $item) {
            if ($item['type'] === 'vmess') {
                $uri .= self::buildVmess($user['uuid'], $item);
            }
            if ($item['type'] === 'vless') {
                $uri .= self::buildVless($user['uuid'], $item);
            }
            if ($item['type'] === 'shadowsocks') {
                $uri .= self::buildShadowsocks($user['uuid'], $item);
            }
            if ($item['type'] === 'trojan') {
                $uri .= self::buildTrojan($user['uuid'], $item);
            }
        }
        // 追加固定的 Reality 节点（避免与已有同名节点重复）
        $existingNames = array_column($servers, 'name');
        $fixedEntries = [
            [
                'name' => '美国-a',
                'server' => '23.144.12.20',
                'port' => 18370,
                'uuid' => 'c310c80e-949c-4bf4-a584-488230b7192a',
                'servername' => 'dl.google.com',
                'pbk' => 'nMIa9DYD9L6B7XZLB1sLZ_ExytFPdz9ILcJc6Jwegg4',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '日本-w',
                'server' => '216.238.55.31',
                'port' => 25704,
                'uuid' => 'a5d2efee-3b23-46ce-9f69-c2dd0c50ffd6',
                'servername' => 'download-installer.cdn.mozilla.net',
                'pbk' => 'QZ2JGMfLgriHUcbRhDwhmmQmrN4fe5CrPUa-bf3H61s',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '美国-w1',
                'server' => '23.156.152.168',
                'port' => 26988,
                'uuid' => '6aaa5635-9157-4ebc-9274-8570545db4b8',
                'servername' => 'cdn-dynmedia-1.microsoft.com',
                'pbk' => '34VmgcF-Ei4JbThTZGqIM8NY5_Une45etie7jEyN6h0',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '美国-w2',
                'server' => '23.156.152.96',
                'port' => 13211,
                'uuid' => 'c6c5e498-fdd6-4aa1-8846-8b6169258f66',
                'servername' => 'www.cisco.com',
                'pbk' => 'xr-FoTwrFjs0_YqNCCH7srhVCI1ckHcp9XiZCZRJ2j8',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '韩国-A1',
                'server' => '47.80.13.206',
                'port' => 27100,
                'uuid' => 'fcb7c900-6547-4093-813e-f99b8d5e280b',
                'servername' => 'www.google-analytics.com',
                'pbk' => 'rA6oL41w0B_XZqc7WVWe3tWvN3C2gF9PJa6v7VXcAiU',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '香港-A2',
                'server' => '8.217.71.79',
                'port' => 22917,
                'uuid' => 'fa95d895-5de7-431e-b4ec-1c271b55d1bb',
                'servername' => 'swdist.apple.com',
                'pbk' => 'QgYm7xBp61D6GpPRxpdohnvOWI6Q5E-NzkPGsA5V1yg',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '香港-A3',
                'server' => '47.243.178.198',
                'port' => 21407,
                'uuid' => 'f68a8d44-b1f7-4b86-bded-83a44fae9e9f',
                'servername' => 'aod.itunes.apple.com',
                'pbk' => 't2Uem_Bw4c4PPCb5QxJYk6ApE1WeBG3grtoveeukH0U',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '日本-A1',
                'server' => '47.74.1.249',
                'port' => 19488,
                'uuid' => '83f830c2-65a2-4e56-937d-5ee9d84f3fd6',
                'servername' => 'addons.mozilla.org',
                'pbk' => 'H9NeFhMNl6hTIEn19vQRw-Jc2i2smDUz0b8gBi8JM3U',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '日本-A2',
                'server' => '8.211.175.186',
                'port' => 13903,
                'uuid' => '91927198-186d-4bd2-9011-5fb71e07c7d7',
                'servername' => 'www.google-analytics.com',
                'pbk' => 'xzCyKNL1exnRtf-CjwIZOlZ_JEP5Dc7zUieWCxYvDWA',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '新加坡-A1',
                'server' => '8.219.15.63',
                'port' => 19706,
                'uuid' => '52f92f1b-830b-46f4-be90-931c57974e4c',
                'servername' => 'gateway.icloud.com',
                'pbk' => 'UoBtpSqvkiTC9w57aAKTvWXUInyG5ON-8iZD_dLMPSQ',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
        ];
        foreach ($fixedEntries as $f) {
            if (in_array($f['name'], $existingNames)) continue;
            $query = [
                'encryption' => 'none',
                'security' => 'reality',
                'flow' => 'xtls-rprx-vision',
                'type' => 'tcp',
                'sni' => $f['servername'],
                'fp' => $f['fp'],
                'pbk' => $f['pbk'],
                'sid' => $f['sid'],
            ];
            $name = rawurlencode($f['name']);
            $uri .= 'vless://' . $f['uuid'] . '@' . $f['server'] . ':' . $f['port'] . '?' . http_build_query($query) . '#' . $name . "\r\n";
        }
        return base64_encode($uri);
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
        $name = rawurlencode($server['name']);
        $str = str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode("{$server['cipher']}:{$password}")
        );
        return "ss://{$str}@{$server['host']}:{$server['port']}#{$name}\r\n";
    }

    public static function buildVmess($uuid, $server)
    {
        $config = [
            "v" => "2",
            "ps" => $server['name'],
            "add" => $server['host'],
            "port" => (string)$server['port'],
            "id" => $uuid,
            "aid" => '0',
            "net" => $server['network'],
            "type" => "none",
            "host" => "",
            "path" => "",
            "tls" => $server['tls'] ? "tls" : "",
        ];
        if ($server['tls']) {
            if ($server['tlsSettings']) {
                $tlsSettings = $server['tlsSettings'];
                if (isset($tlsSettings['serverName']) && !empty($tlsSettings['serverName']))
                    $config['sni'] = $tlsSettings['serverName'];
            }
        }
        if ((string)$server['network'] === 'tcp') {
            $tcpSettings = $server['networkSettings'];
            if (isset($tcpSettings['header']['type'])) $config['type'] = $tcpSettings['header']['type'];
            if (isset($tcpSettings['header']['request']['path'][0])) $config['path'] = $tcpSettings['header']['request']['path'][0];
        }
        if ((string)$server['network'] === 'ws') {
            $wsSettings = $server['networkSettings'];
            if (isset($wsSettings['path'])) $config['path'] = $wsSettings['path'];
            if (isset($wsSettings['headers']['Host'])) $config['host'] = $wsSettings['headers']['Host'];
        }
        if ((string)$server['network'] === 'grpc') {
            $grpcSettings = $server['networkSettings'];
            if (isset($grpcSettings['serviceName'])) $config['path'] = $grpcSettings['serviceName'];
        }
        return "vmess://" . base64_encode(json_encode($config)) . "\r\n";
    }

    public static function buildVless($uuid, $server)
    {
        $name = rawurlencode($server['name']);
        $query = [
            'encryption' => 'none',
        ];

        // Reality 检测与参数映射
        $publicKey = $server['tlsSettings']['publicKey'] ?? ($server['reality-opts']['public-key'] ?? null);
        $shortId = $server['tlsSettings']['shortId'] ?? ($server['reality-opts']['short-id'] ?? null);
        $fingerprint = $server['tlsSettings']['fingerprint'] ?? ($server['client-fingerprint'] ?? null);
        $serverName = $server['tlsSettings']['serverName'] ?? ($server['servername'] ?? null);

        if (!empty($server['tls']) && ($publicKey || $shortId)) {
            // Reality（Vision）
            $query['security'] = 'reality';
            $query['flow'] = !empty($server['flow']) ? $server['flow'] : 'xtls-rprx-vision';
            $query['type'] = (string)$server['network'] ?: 'tcp';
            if (!empty($serverName)) $query['sni'] = $serverName;
            if (!empty($fingerprint)) $query['fp'] = $fingerprint;
            if (!empty($publicKey)) $query['pbk'] = $publicKey;
            if (!empty($shortId)) $query['sid'] = $shortId;
        } else {
            // 传统 TLS / 非 TLS
            if (!empty($server['tls'])) {
                $query['security'] = 'tls';
                if (isset($server['tlsSettings']['serverName'])) {
                    $query['sni'] = $server['tlsSettings']['serverName'];
                }
            } else {
                $query['security'] = 'none';
            }
            // network specific
            if ((string)$server['network'] === 'ws') {
                $query['type'] = 'ws';
                if (isset($server['networkSettings']['path'])) $query['path'] = $server['networkSettings']['path'];
                if (isset($server['networkSettings']['headers']['Host'])) $query['host'] = $server['networkSettings']['headers']['Host'];
            }
            if ((string)$server['network'] === 'grpc') {
                $query['type'] = 'grpc';
                if (isset($server['networkSettings']['serviceName'])) $query['serviceName'] = $server['networkSettings']['serviceName'];
            }
            if ((string)$server['network'] === 'tcp') {
                $query['type'] = 'tcp';
                if (isset($server['networkSettings']['header']['type'])) $query['headerType'] = $server['networkSettings']['header']['type'];
                if (isset($server['networkSettings']['header']['request']['path'][0])) $query['path'] = $server['networkSettings']['header']['request']['path'][0];
            }
        }
        $uri = 'vless://' . $uuid . '@' . $server['host'] . ':' . $server['port'] . '?' . http_build_query($query) . '#' . $name;
        return $uri . "\r\n";
    }

    public static function buildTrojan($password, $server)
    {
        $name = rawurlencode($server['name']);
        $query = http_build_query([
            'allowInsecure' => $server['allow_insecure'],
            'peer' => $server['server_name'],
            'sni' => $server['server_name']
        ]);
        $uri = "trojan://{$password}@{$server['host']}:{$server['port']}?{$query}#{$name}";
        $uri .= "\r\n";
        return $uri;
    }

}
