<?php

// api/subscribe.php  –  Save / remove browser push subscriptions

// Routes:
//   POST   ?action=save    – save a Web Push subscription
//   DELETE ?action=remove  – remove by endpoint
//   GET    ?action=vapid_key – return the public VAPID key


require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/response.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'save';

try {
    $pdo = getDB();

    switch ($action) {

        // ── Return the public VAPID key (needed by browser SW) ─
        case 'vapid_key':
            jsonResponse(['public_key' => VAPID_PUBLIC_KEY]);

        // ── Save / upsert a push subscription 
        case 'save':
            if ($method !== 'POST') errorResponse('POST required.', 405);

            $body = getJsonBody();

            $endpoint = trim($body['endpoint'] ?? '');
            $p256dh   = trim($body['keys']['p256dh'] ?? $body['p256dh'] ?? '');
            $authKey  = trim($body['keys']['auth']   ?? $body['auth']   ?? '');

            if (!$endpoint || !$p256dh || !$authKey) {
                errorResponse('endpoint, p256dh and auth are required.');
            }

            $type       = $body['subscriber_type'] ?? 'customer';
            $subId      = isset($body['subscriber_id']) ? (int)$body['subscriber_id'] : null;
            $userAgent  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

            // Upsert – update keys if endpoint already stored
            $stmt = $pdo->prepare(
                "INSERT INTO push_subscriptions
                    (subscriber_type, subscriber_id, endpoint, p256dh, auth_key, user_agent)
                 VALUES
                    (:type, :sid, :ep, :p256, :auth, :ua)
                 ON DUPLICATE KEY UPDATE
                    p256dh          = VALUES(p256dh),
                    auth_key        = VALUES(auth_key),
                    subscriber_type = VALUES(subscriber_type),
                    subscriber_id   = VALUES(subscriber_id)"
            );
            $stmt->execute([
                ':type' => $type,
                ':sid'  => $subId,
                ':ep'   => $endpoint,
                ':p256' => $p256dh,
                ':auth' => $authKey,
                ':ua'   => $userAgent,
            ]);

            jsonResponse(['message' => 'Subscription saved.'], 201);

        // ── Remove a push subscription 
        case 'remove':
            if (!in_array($method, ['POST','DELETE'])) errorResponse('POST/DELETE required.', 405);

            $body     = getJsonBody();
            $endpoint = trim($body['endpoint'] ?? '');
            if (!$endpoint) errorResponse('endpoint required.');

            $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = :ep")
                ->execute([':ep' => $endpoint]);

            jsonResponse(['message' => 'Subscription removed.']);

        default:
            errorResponse("Unknown action '$action'.", 404);
    }

} catch (RuntimeException $e) {
    errorResponse($e->getMessage(), 503);
} catch (PDOException $e) {
    error_log('[subscribe.php] ' . $e->getMessage());
    errorResponse('Database error.', 500);
}
