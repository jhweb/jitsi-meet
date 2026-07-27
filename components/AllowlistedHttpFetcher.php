<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\components;

use Yii;

/**
 * Fetches remote content over HTTPS with host allowlisting and SSRF protections.
 */
class AllowlistedHttpFetcher
{
    public const SETTING_KEY = 'outboundFetchAllowedHosts';

    public const DEFAULT_MAX_BYTES = 5242880; // 5 MiB

    public const DEFAULT_TIMEOUT_SECONDS = 5;

    /**
     * Default 8x8/JaaS hosts. Suffix entries start with a dot.
     *
     * @var string[]
     */
    public const DEFAULT_ALLOWED_HOSTS = [
        '8x8.vc',
        '.8x8.vc',
        '.oraclecloud.com',
    ];

    /**
     * @return string[] normalized host/suffix entries (lowercase)
     */
    public static function getAllowedHosts(): array
    {
        $hosts = self::DEFAULT_ALLOWED_HOSTS;

        $module = Yii::$app->getModule('jitsi-meet-cloud-8x8');
        if ($module !== null) {
            $jaasDomain = trim((string) $module->settings->get('jaasDomain', ''));
            if ($jaasDomain !== '' && !in_array(strtolower($jaasDomain), $hosts, true)) {
                $hosts[] = strtolower($jaasDomain);
            }

            $custom = trim((string) $module->settings->get(self::SETTING_KEY, ''));
            if ($custom !== '') {
                foreach (preg_split('/[\s,]+/', $custom, -1, PREG_SPLIT_NO_EMPTY) as $entry) {
                    $entry = strtolower(trim($entry));
                    if ($entry !== '' && !in_array($entry, $hosts, true)) {
                        $hosts[] = $entry;
                    }
                }
            }
        }

        return $hosts;
    }

    /**
     * Fetch URL content when the host passes SSRF checks.
     *
     * @return array{ok: bool, content: string|null, host: string|null, reason: string|null}
     */
    public static function fetch(
        string $url,
        int $maxBytes = self::DEFAULT_MAX_BYTES,
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS
    ): array {
        $validation = self::validateUrl($url, self::getAllowedHosts());
        if (!$validation['ok']) {
            return [
                'ok' => false,
                'content' => null,
                'host' => $validation['host'],
                'reason' => $validation['reason'],
            ];
        }

        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'content' => null,
                'host' => $validation['host'],
                'reason' => 'curl_unavailable',
            ];
        }

        $handle = curl_init($url);
        if ($handle === false) {
            return [
                'ok' => false,
                'content' => null,
                'host' => $validation['host'],
                'reason' => 'curl_init_failed',
            ];
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_MAXFILESIZE => $maxBytes,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'HumHub-JitsiMeetCloud8x8/1.0',
        ]);

        $content = curl_exec($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);

        if ($content === false) {
            return [
                'ok' => false,
                'content' => null,
                'host' => $validation['host'],
                'reason' => $curlError !== '' ? 'curl_error' : 'fetch_failed',
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'ok' => false,
                'content' => null,
                'host' => $validation['host'],
                'reason' => 'http_' . $httpCode,
            ];
        }

        if (strlen($content) > $maxBytes) {
            return [
                'ok' => false,
                'content' => null,
                'host' => $validation['host'],
                'reason' => 'response_too_large',
            ];
        }

        return [
            'ok' => true,
            'content' => $content,
            'host' => $validation['host'],
            'reason' => null,
        ];
    }

    /**
     * @param string[] $allowedHosts
     * @return array{ok: bool, host: string|null, reason: string|null}
     */
    public static function validateUrl(string $url, array $allowedHosts): array
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return ['ok' => false, 'host' => null, 'reason' => 'invalid_url'];
        }

        if (strtolower($parts['scheme']) !== 'https') {
            return ['ok' => false, 'host' => strtolower($parts['host']), 'reason' => 'https_required'];
        }

        $host = strtolower($parts['host']);

        if (self::isIpLiteralHost($host)) {
            return ['ok' => false, 'host' => $host, 'reason' => 'ip_literal'];
        }

        if (self::isPrivateOrLinkLocalHost($host)) {
            return ['ok' => false, 'host' => $host, 'reason' => 'private_host'];
        }

        if (!self::isHostAllowed($host, $allowedHosts)) {
            return ['ok' => false, 'host' => $host, 'reason' => 'host_not_allowed'];
        }

        return ['ok' => true, 'host' => $host, 'reason' => null];
    }

    private static function isIpLiteralHost(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if ($host[0] === '[' && substr($host, -1) === ']') {
            $inner = substr($host, 1, -1);
            return filter_var($inner, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }

        return false;
    }

    private static function isPrivateOrLinkLocalHost(string $host): bool
    {
        $ip = $host;
        if ($host[0] === '[' && substr($host, -1) === ']') {
            $ip = substr($host, 1, -1);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return self::isPrivateOrLinkLocalIpv4($ip);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return self::isPrivateOrLinkLocalIpv6($ip);
        }

        return false;
    }

    private static function isPrivateOrLinkLocalIpv4(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) {
            return true;
        }

        $ranges = [
            ['0.0.0.0', '0.255.255.255'],       // 0/8
            ['10.0.0.0', '10.255.255.255'],     // 10/8
            ['127.0.0.0', '127.255.255.255'],   // 127/8
            ['169.254.0.0', '169.254.255.255'], // 169.254/16
            ['172.16.0.0', '172.31.255.255'],   // 172.16/12
            ['192.168.0.0', '192.168.255.255'], // 192.168/16
        ];

        foreach ($ranges as [$start, $end]) {
            $startLong = ip2long($start);
            $endLong = ip2long($end);
            if ($startLong !== false && $endLong !== false && $long >= $startLong && $long <= $endLong) {
                return true;
            }
        }

        return false;
    }

    private static function isPrivateOrLinkLocalIpv6(string $ip): bool
    {
        $normalized = inet_pton($ip);
        if ($normalized === false) {
            return true;
        }

        if ($normalized === inet_pton('::1')) {
            return true;
        }

        $first = ord($normalized[0]);
        if (($first & 0xfe) === 0xfc) {
            return true; // fc00::/7 unique local
        }

        if (($first & 0xff) === 0xfe && (ord($normalized[1]) & 0xc0) === 0x80) {
            return true; // fe80::/10 link-local
        }

        return false;
    }

    /**
     * @param string[] $allowedHosts
     */
    private static function isHostAllowed(string $host, array $allowedHosts): bool
    {
        foreach ($allowedHosts as $entry) {
            $entry = strtolower(trim($entry));
            if ($entry === '') {
                continue;
            }

            if ($entry[0] === '.') {
                $suffix = substr($entry, 1);
                if ($host === $suffix || self::endsWith($host, $entry)) {
                    return true;
                }
                continue;
            }

            if ($host === $entry) {
                return true;
            }
        }

        return false;
    }

    private static function endsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        $needleLength = strlen($needle);
        if ($needleLength > strlen($haystack)) {
            return false;
        }

        return substr($haystack, -$needleLength) === $needle;
    }
}
