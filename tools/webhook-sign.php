#!/usr/bin/env php
<?php
/**
 * Local HMAC signer for jitsi-meet-cloud-8x8 webhook testing.
 *
 * Mirrors controllers/WebhookController.php::verifySignature():
 *   Header:  X-Jaas-Signature: t=<unix_seconds>,v1=<base64(hmac_sha256)>
 *   Signed:  <timestamp> . <raw_json_body>
 *   Algo:    hash_hmac('sha256', ..., $secret, true) then base64_encode
 *   Drift:   abs(now - t) must be <= jaasWebhookDriftTolerance (default 300s)
 *
 * Usage:
 *   php tools/webhook-sign.php --payload=payload.json --secret=devsecret \
 *     [--url=http://localhost:9091/jitsi-meet-cloud-8x8/webhook] \
 *     [--tamper] [--stale] [--dry-run]
 */

declare(strict_types=1);

function usage(): void
{
    $msg = <<<TXT
Usage: php tools/webhook-sign.php --payload=<file.json> --secret=<webhook-secret> [options]

Options:
  --url=<url>     Webhook endpoint (default: http://localhost:9091/jitsi-meet-cloud-8x8/webhook)
  --tamper        Corrupt the signature so verifySignature() fails
  --stale         Set timestamp outside the 5-minute drift window
  --dry-run       Print headers and body; do not POST
  --help          Show this help

TXT;
    fwrite(STDERR, $msg);
}

$opts = getopt('', [
    'payload:',
    'secret:',
    'url::',
    'tamper',
    'stale',
    'dry-run',
    'help',
]);

if (isset($opts['help']) || !isset($opts['payload']) || !isset($opts['secret'])) {
    usage();
    exit(isset($opts['help']) ? 0 : 1);
}

$payloadPath = $opts['payload'];
if (!is_readable($payloadPath)) {
    fwrite(STDERR, "Payload file not readable: {$payloadPath}\n");
    exit(1);
}

$rawBody = file_get_contents($payloadPath);
if ($rawBody === false || $rawBody === '') {
    fwrite(STDERR, "Payload file empty or unreadable: {$payloadPath}\n");
    exit(1);
}

// Validate JSON so we fail early with a clear error (module also requires eventType).
$decoded = json_decode($rawBody, true);
if (!is_array($decoded)) {
    fwrite(STDERR, "Payload is not valid JSON\n");
    exit(1);
}

$secret = (string) $opts['secret'];
$url = $opts['url'] ?? 'http://localhost:9091/jitsi-meet-cloud-8x8/webhook';

$timestamp = time();
if (isset($opts['stale'])) {
    // Default drift tolerance in verifySignature() is 300 seconds.
    $timestamp = time() - 301;
}

$signedPayload = $timestamp . '.' . $rawBody;
$signature = base64_encode(hash_hmac('sha256', $signedPayload, $secret, true));

if (isset($opts['tamper'])) {
    // Flip last character of the base64 signature without changing length.
    $last = substr($signature, -1);
    $signature = substr($signature, 0, -1) . ($last === 'A' ? 'B' : 'A');
}

$headerValue = 't=' . $timestamp . ',v1=' . $signature;

if (isset($opts['dry-run'])) {
    echo "URL: {$url}\n";
    echo "X-Jaas-Signature: {$headerValue}\n";
    echo "Body bytes: " . strlen($rawBody) . "\n";
    echo "Flags: tamper=" . (isset($opts['tamper']) ? '1' : '0')
        . " stale=" . (isset($opts['stale']) ? '1' : '0') . "\n";
    exit(0);
}

$ch = curl_init($url);
if ($ch === false) {
    fwrite(STDERR, "curl_init failed\n");
    exit(1);
}

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $rawBody,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Jaas-Signature: ' . $headerValue,
    ],
    CURLOPT_TIMEOUT => 30,
]);

$responseBody = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($errno !== 0) {
    fwrite(STDERR, "Request failed: {$error}\n");
    exit(1);
}

echo "HTTP {$status}\n";
echo $responseBody === false ? '' : $responseBody;
echo "\n";

exit($status >= 200 && $status < 300 ? 0 : 2);
