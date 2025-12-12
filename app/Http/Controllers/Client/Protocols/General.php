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
                'name' => '美国-a2',
                'server' => '23.144.20.135',
                'port' => 13041,
                'uuid' => '123b8dc0-cdf9-4030-b913-2eefa82775a6',
                'servername' => 'dl.google.com',
                'pbk' => '9-dH2LJiQ6z6j7MX8t69bWVuI0Lx3LdPyu3cokTxgC0',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '美国-a3',
                'server' => '23.144.20.33',
                'port' => 15526,
                'uuid' => 'b247f45f-1207-44be-a212-eaa09c8c63a8',
                'servername' => 'dl.google.com',
                'pbk' => 'FJY6rsLEdKJkTmTCps6gBZFkTAh_MGubvLS84yvtvWk',
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
                'name' => '日本-w2',
                'server' => '216.238.55.43',
                'port' => 14247,
                'uuid' => 'eb813b83-286c-4f24-a8a2-61309a00a313',
                'servername' => 'swcdn.apple.com',
                'pbk' => 'SIGFM4H-WBBrjbocBOqGcIJtFe1A_mG7U9BL3qYaw1Y',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '日本-w3',
                'server' => '216.238.55.44',
                'port' => 15375,
                'uuid' => '9d604987-a2e0-4d2b-969b-49bcd73e18e9',
                'servername' => 'www.google-analytics.com',
                'pbk' => 'haVWGowZWyKCqBp0wZBxj5AqUgFzySpL3sbZUmpqmls',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => '日本-w4',
                'server' => '216.238.55.45',
                'port' => 16346,
                'uuid' => 'fb8b1b17-eea3-4119-8ed5-579183859290',
                'servername' => 'academy.nvidia.com',
                'pbk' => 'CPtHLmSRv81nZb8kLVCLquBABfoxv4dN8u6K8X2BdXs',
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
