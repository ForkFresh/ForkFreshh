<?php

// helpers/push.php  –  Web Push / VAPID send helper

// Uses a pure-PHP VAPID implementation so you do NOT need
// Composer on a basic XAMPP setup.
// For production, swap in: composer require minishlink/web-push


require_once __DIR__ . '/../config/constants.php';

/**
 * Send a Web Push notification to a single subscription row.
 *
 * @param array  $subscription  Row from push_subscriptions table
 * @param string $title         Notification title
 * @param string $body          Notification body text
 * @param array  $extra         Optional extra payload fields (url, icon, …)
 * @return array{ok: bool, error: string}
 */
function sendWebPush(array $subscription, string $title, string $body, array $extra = []): array
{
    $payload = json_encode(array_merge([
        'title' => $title,
        'body'  => $body,
        'icon'  => '/ForkFresh/assets/icon-192.png',
        'badge' => '/ForkFresh/assets/badge-72.png',
        'url'   => '/ForkFresh/track-order/index.html',
    ], $extra), JSON_UNESCAPED_SLASHES);

    $endpoint = $subscription['endpoint'];
    $p256dh   = $subscription['p256dh'];
    $authKey  = $subscription['auth_key'];

    // ── Build VAPID JWT 
    $jwt = buildVapidJwt($endpoint);

    // ── Encrypt payload (ECDH + AES-128-GCM) 
    try {
        $encrypted = encryptPayload($payload, $p256dh, $authKey);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Encryption failed: ' . $e->getMessage()];
    }

    // ── HTTP request via cURL 
    $headers = [
        'Content-Type: application/octet-stream',
        'Content-Encoding: aes128gcm',
        'Authorization: vapid t=' . $jwt . ',k=' . urlSafeBase64Encode(vapidPublicKeyBytes()),
        'TTL: 86400',
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $encrypted['ciphertext'],
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response   = curl_exec($ch);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['ok' => false, 'error' => "cURL error: $curlError"];
    }

    // 201 Created or 200 OK = success; 410 Gone = subscription expired
    if ($httpCode === 410 || $httpCode === 404) {
        return ['ok' => false, 'error' => "Subscription expired (HTTP $httpCode)"];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => "Push server returned HTTP $httpCode: $response"];
    }

    return ['ok' => true, 'error' => ''];
}

// ── VAPID helpers 

function buildVapidJwt(string $endpoint): string
{
    $parsed   = parse_url($endpoint);
    $audience = $parsed['scheme'] . '://' . $parsed['host'];

    $header  = urlSafeBase64Encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $payload = urlSafeBase64Encode(json_encode([
        'aud' => $audience,
        'exp' => time() + 43200,   // 12 hours
        'sub' => VAPID_SUBJECT,
    ]));

    $sigInput  = $header . '.' . $payload;
    $privBytes = urlSafeBase64Decode(VAPID_PRIVATE_KEY);

    // Sign with ES256 (openssl)
    $privKey = openssl_pkey_get_private(
        "-----BEGIN EC PRIVATE KEY-----\n" .
        chunk_split(base64_encode("\x30\x77\x02\x01\x01\x04\x20" . $privBytes .
            "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\xa1\x44\x03\x42\x00\x04" .
            vapidPublicKeyBytes()), 64, "\n") .
        "-----END EC PRIVATE KEY-----"
    );

    openssl_sign($sigInput, $derSig, $privKey, 'SHA256');

    // DER → raw r||s (64 bytes)
    $rawSig = derToRaw($derSig);

    return $sigInput . '.' . urlSafeBase64Encode($rawSig);
}

function vapidPublicKeyBytes(): string
{
    return urlSafeBase64Decode(VAPID_PUBLIC_KEY);
}

function derToRaw(string $der): string
{
    // Parse DER SEQUENCE → two INTEGERs r, s
    $offset = 3; // skip 0x30, length, 0x02
    $rLen   = ord($der[$offset++]);
    $r      = substr($der, $offset, $rLen);
    $offset += $rLen + 1; // skip 0x02
    $sLen   = ord($der[$offset++]);
    $s      = substr($der, $offset, $sLen);

    // Pad / trim to 32 bytes each
    $r = ltrim($r, "\x00");
    $s = ltrim($s, "\x00");
    $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
    $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

    return $r . $s;
}

// ── Payload encryption (RFC 8291 / aes128gcm) 

function encryptPayload(string $payload, string $p256dh, string $auth): array
{
    // Decode receiver keys
    $receiverPub = urlSafeBase64Decode($p256dh);
    $authSecret  = urlSafeBase64Decode($auth);

    // Generate ephemeral ECDH key pair
    $ephemeral   = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    $ephDetails  = openssl_pkey_get_details($ephemeral);
    $senderPub   = "\x04" . $ephDetails['ec']['x'] . $ephDetails['ec']['y'];

    // Derive shared secret
    $receiverKey = openssl_pkey_get_public(ecPublicKeyToPem($receiverPub));
    openssl_dh_compute_key($sharedSecret, $receiverKey, $ephemeral); // placeholder approach

    // PRK via HKDF-SHA-256
    $salt   = random_bytes(16);
    $ikm    = hkdf('sha256', $sharedSecret, $authSecret,
                   'WebPush: info' . "\x00" . $receiverPub . $senderPub, 32);
    $cek    = hkdf('sha256', $ikm, $salt, 'Content-Encoding: aes128gcm' . "\x00", 16);
    $nonce  = hkdf('sha256', $ikm, $salt, 'Content-Encoding: nonce'     . "\x00", 12);

    // Encrypt (AES-128-GCM) – pad payload
    $padded     = $payload . "\x02"; // delimiter byte
    $tag        = '';
    $ciphertext = openssl_encrypt($padded, 'aes-128-gcm', $cek,
                                  OPENSSL_RAW_DATA, $nonce, $tag, '', 16);

    // RFC 8291 header: salt(16) + rs(4) + keyid_len(1) + keyid
    $rs         = pack('N', 4096);
    $header     = $salt . $rs . chr(strlen($senderPub)) . $senderPub;

    return ['ciphertext' => $header . $ciphertext . $tag];
}

function hkdf(string $algo, string $ikm, string $salt, string $info, int $length): string
{
    $prk = hash_hmac($algo, $ikm, $salt, true);
    $t   = '';
    $okm = '';
    for ($i = 1; strlen($okm) < $length; $i++) {
        $t    = hash_hmac($algo, $t . $info . chr($i), $prk, true);
        $okm .= $t;
    }
    return substr($okm, 0, $length);
}

function ecPublicKeyToPem(string $rawKey): string
{
    // RFC 5480 SubjectPublicKeyInfo header for prime256v1
    $header = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
            . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00";
    return "-----BEGIN PUBLIC KEY-----\n"
         . chunk_split(base64_encode($header . $rawKey), 64, "\n")
         . "-----END PUBLIC KEY-----";
}

function urlSafeBase64Encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function urlSafeBase64Decode(string $data): string
{
    return base64_decode(strtr($data, '-_', '+/'));
}
