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
                'name' => 'US-瓦工',
                'server' => '23.106.157.77',
                'port' => 21591,
                'uuid' => '35759465-561c-4365-ac41-158f8248649c',
                'servername' => 'dl.google.com',
                'pbk' => 'F2vMYJfwgzxEZ4snj54KZ_2ol-Gad3Nkh8mfpJLkjnE',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => 'JPP',
                'server' => '151.242.164.31',
                'port' => 31122,
                'uuid' => '8ca57d9c-545f-4417-b65f-1ca9692e9ee5',
                'servername' => 'aod.itunes.apple.com',
                'pbk' => 'SMhrERlTCqtbZqS9H6oa5jzieaAnV5HvTwPgFw7V-1c',
                'sid' => '6ba85179e30d4fc2',
                'fp' => 'chrome'
            ],
            [
                'name' => 'US-4837',
                'server' => '23.144.12.20',
                'port' => 18370,
                'uuid' => 'c310c80e-949c-4bf4-a584-488230b7192a',
                'servername' => 'dl.google.com',
                'pbk' => 'nMIa9DYD9L6B7XZLB1sLZ_ExytFPdz9ILcJc6Jwegg4',
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
