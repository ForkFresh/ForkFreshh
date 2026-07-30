<?php

// api/notifications.php  –  Push notification trigger logic

// Routes:
//   POST ?action=send_order_update  – push a status update     [auth]
//   POST ?action=send_custom        – custom push to a sub     [auth]
//   POST ?action=send_rider_alert   – alert a rider            [auth]
//   GET  ?action=log                – fetch notification log


require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/push.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {

        // ── Push order status update to all customer subs ─────
        case 'send_order_update':
            requireAuth();
            if ($method !== 'POST') errorResponse('POST required.', 405);

            $body    = getJsonBody();
            $orderId = (int)($body['order_id'] ?? 0);
            if (!$orderId) errorResponse('order_id required.');

            // Fetch order
            $stmt = $pdo->prepare("SELECT order_number, status FROM orders WHERE id = :id");
            $stmt->execute([':id' => $orderId]);
            $order = $stmt->fetch();
            if (!$order) errorResponse('Order not found.', 404);

            $label = STATUS_LABELS[$order['status']]
                  ?? ucfirst(str_replace('_', ' ', $order['status']));

            $results = dispatchPush(
                $pdo,
                'customer',
                PUSH_TITLE,
                "Order #{$order['order_number']}: $label",
                $orderId,
                null,
                ['url' => "/ForkFresh/track-order/index.html?order={$order['order_number']}"]
            );

            jsonResponse(['sent' => $results['sent'], 'failed' => $results['failed']]);

        //  Push a custom message 
        case 'send_custom':
            requireAuth();
            if ($method !== 'POST') errorResponse('POST required.', 405);

            $body  = getJsonBody();
            $title = trim($body['title'] ?? '');
            $msg   = trim($body['body']  ?? '');
            $type  = $body['subscriber_type'] ?? 'customer';

            if (!$title || !$msg) errorResponse('title and body required.');

            $results = dispatchPush($pdo, $type, $title, $msg,
                                    $body['order_id']  ?? null,
                                    $body['rider_id']  ?? null,
                                    $body['extra']     ?? []);
            jsonResponse(['sent' => $results['sent'], 'failed' => $results['failed']]);

        //  Alert a specific rider 
        case 'send_rider_alert':
            requireAuth();
            if ($method !== 'POST') errorResponse('POST required.', 405);

            $body    = getJsonBody();
            $riderId = (int)($body['rider_id'] ?? 0);
            $msg     = trim($body['message']   ?? 'You have a new delivery!');
            if (!$riderId) errorResponse('rider_id required.');

            // Find rider subscriptions
            $stmt = $pdo->prepare(
                "SELECT * FROM push_subscriptions
                 WHERE subscriber_type = 'rider' AND subscriber_id = :rid"
            );
            $stmt->execute([':rid' => $riderId]);
            $subs = $stmt->fetchAll();

            if (empty($subs)) {
                jsonResponse(['message' => 'No push subscriptions for this rider.',
                              'sent' => 0]);
            }

            $sent = 0; $failed = 0;
            foreach ($subs as $sub) {
                $result = sendWebPush($sub, PUSH_TITLE, $msg, [
                    'url' => '/ForkFresh/rider-dashboard/index.html',
                ]);
                logPushAttempt($pdo, $sub['id'], null, $riderId,
                               PUSH_TITLE, $msg, $result);
                $result['ok'] ? $sent++ : $failed++;
            }
            jsonResponse(['sent' => $sent, 'failed' => $failed]);

        // ── Fetch notification log 
        case 'log':
            $limit   = min((int)($_GET['limit'] ?? 50), 200);
            $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

            $where  = $orderId ? 'WHERE n.order_id = :oid' : '';
            $params = $orderId ? [':oid' => $orderId] : [];

            $stmt = $pdo->prepare(
                "SELECT n.id, n.title, n.body, n.status,
                        n.sent_at, n.error_msg, n.created_at,
                        n.order_id, n.rider_id
                 FROM push_notifications n
                 $where
                 ORDER BY n.created_at DESC
                 LIMIT $limit"
            );
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());

        default:
            errorResponse("Unknown action '$action'.", 404);
    }

} catch (RuntimeException $e) {
    errorResponse($e->getMessage(), 503);
} catch (PDOException $e) {
    error_log('[notifications.php] ' . $e->getMessage());
    errorResponse('Database error.', 500);
}

// ── Dispatch push to all subs of a given type 
function dispatchPush(
    PDO $pdo,
    string $subscriberType,
    string $title,
    string $body,
    ?int $orderId,
    ?int $riderId,
    array $extra = []
): array {
    $stmt = $pdo->prepare(
        "SELECT * FROM push_subscriptions
         WHERE subscriber_type = :type
         LIMIT 100"
    );
    $stmt->execute([':type' => $subscriberType]);
    $subs = $stmt->fetchAll();

    $sent = 0; $failed = 0;
    foreach ($subs as $sub) {
        $result = sendWebPush($sub, $title, $body, $extra);
        logPushAttempt($pdo, $sub['id'], $orderId, $riderId,
                       $title, $body, $result);

        if ($result['ok']) {
            $sent++;
        } else {
            $failed++;
            if (str_contains($result['error'], 'expired')) {
                $pdo->prepare("DELETE FROM push_subscriptions WHERE id = :id")
                    ->execute([':id' => $sub['id']]);
            }
        }
    }

    return ['sent' => $sent, 'failed' => $failed];
}

function logPushAttempt(
    PDO $pdo,
    int $subId,
    ?int $orderId,
    ?int $riderId,
    string $title,
    string $body,
    array $result
): void {
    $pdo->prepare(
        "INSERT INTO push_notifications
            (subscription_id, order_id, rider_id, title, body, status, sent_at, error_msg)
         VALUES
            (:sid, :oid, :rid, :title, :body, :status, NOW(), :err)"
    )->execute([
        ':sid'    => $subId,
        ':oid'    => $orderId,
        ':rid'    => $riderId,
        ':title'  => $title,
        ':body'   => $body,
        ':status' => $result['ok'] ? 'sent' : 'failed',
        ':err'    => $result['ok'] ? null : $result['error'],
    ]);
}
