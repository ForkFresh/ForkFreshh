<?php

// api/orders.php  –  Order listing, assignment & status updates

// Routes (method + ?action=):
//   GET    ?action=list                  – all orders (filters: status, rider_id, date)
//   GET    ?action=get&id=N              – single order + status history
//   GET    ?action=rider_orders&rider_id=N – orders for a specific rider
//   POST   ?action=create                – create new order    [auth]
//   PATCH  ?action=update_status&id=N   – advance status       [auth]
//   DELETE ?action=cancel&id=N          – cancel order         [auth]


require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/push.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Valid forward-only status transitions
const STATUS_TRANSITIONS = [
    'pending'          => 'assigned',
    'assigned'         => 'preparing',
    'preparing'        => 'on_the_way',
    'on_the_way'       => 'out_for_delivery',
    'out_for_delivery' => 'delivered',
];

try {
    $pdo = getDB();

    switch ($action) {

        // ── LIST orders 
        case 'list':
            $where  = ['1=1'];
            $params = [];

            if (!empty($_GET['status'])) {
                $where[]            = 'o.status = :status';
                $params[':status']  = $_GET['status'];
            }
            if (!empty($_GET['rider_id'])) {
                $where[]               = 'o.rider_id = :rider_id';
                $params[':rider_id']   = (int)$_GET['rider_id'];
            }
            if (!empty($_GET['date'])) {
                $where[]          = 'DATE(o.placed_at) = :date';
                $params[':date']  = $_GET['date'];
            }

            $whereStr = implode(' AND ', $where);
            $stmt = $pdo->prepare(
                "SELECT o.*,
                        r.name AS rider_name, r.phone AS rider_phone,
                        r.avatar_url AS rider_avatar
                 FROM orders o
                 LEFT JOIN riders r ON r.id = o.rider_id
                 WHERE $whereStr
                 ORDER BY o.placed_at DESC
                 LIMIT 100"
            );
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());

        // ── GET single order + full status history 
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            // Also allow lookup by order_number
            $number = trim($_GET['number'] ?? '');

            if (!$id && !$number) errorResponse('Provide id or number.');

            if ($number) {
                $stmt = $pdo->prepare(
                    "SELECT o.*, r.name AS rider_name, r.phone AS rider_phone,
                             r.avatar_url AS rider_avatar, r.rating AS rider_rating
                     FROM orders o
                     LEFT JOIN riders r ON r.id = o.rider_id
                     WHERE o.order_number = :num"
                );
                $stmt->execute([':num' => strtoupper($number)]);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT o.*, r.name AS rider_name, r.phone AS rider_phone,
                             r.avatar_url AS rider_avatar, r.rating AS rider_rating
                     FROM orders o
                     LEFT JOIN riders r ON r.id = o.rider_id
                     WHERE o.id = :id"
                );
                $stmt->execute([':id' => $id]);
            }

            $order = $stmt->fetch();
            if (!$order) errorResponse('Order not found.', 404);

            // Fetch status history
            $logStmt = $pdo->prepare(
                "SELECT status, note, changed_by, changed_at
                 FROM order_status_log
                 WHERE order_id = :oid
                 ORDER BY changed_at ASC"
            );
            $logStmt->execute([':oid' => $order['id']]);
            $order['status_log'] = $logStmt->fetchAll();

            // Fetch latest rider GPS
            if ($order['rider_id']) {
                $gpsStmt = $pdo->prepare(
                    "SELECT latitude, longitude, heading, recorded_at
                     FROM gps_tracking
                     WHERE rider_id = :rid
                     ORDER BY recorded_at DESC
                     LIMIT 1"
                );
                $gpsStmt->execute([':rid' => $order['rider_id']]);
                $order['rider_location'] = $gpsStmt->fetch() ?: null;
            } else {
                $order['rider_location'] = null;
            }

            jsonResponse($order);

        // ── ORDERS for a specific rider 
        case 'rider_orders':
            $riderId = (int)($_GET['rider_id'] ?? 0);
            if (!$riderId) errorResponse('rider_id required.');

            $stmt = $pdo->prepare(
                "SELECT id, order_number, customer_name, customer_phone,
                        restaurant_name, dropoff_address,
                        status, total_amount, placed_at, delivered_at
                 FROM orders
                 WHERE rider_id = :rid
                 ORDER BY placed_at DESC
                 LIMIT 50"
            );
            $stmt->execute([':rid' => $riderId]);
            jsonResponse($stmt->fetchAll());

        // ── CREATE order 
        case 'create':
            requireAuth();
            if ($method !== 'POST') errorResponse('POST required.', 405);

            $body     = getJsonBody();
            $required = ['customer_name','customer_phone','restaurant_name',
                         'dropoff_address','total_amount'];
            foreach ($required as $f) {
                if (empty($body[$f])) errorResponse("Field '$f' is required.");
            }

            // Auto-generate order number: FF + 6-digit random
            $orderNum = 'FF' . str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare(
                "INSERT INTO orders
                    (order_number, customer_name, customer_phone,
                     restaurant_name, restaurant_lat, restaurant_lng,
                     dropoff_address, dropoff_lat, dropoff_lng,
                     total_amount, estimated_minutes)
                 VALUES
                    (:num, :cname, :cphone,
                     :rname, :rlat, :rlng,
                     :daddr, :dlat, :dlng,
                     :amount, :eta)"
            );
            $stmt->execute([
                ':num'    => $orderNum,
                ':cname'  => trim($body['customer_name']),
                ':cphone' => trim($body['customer_phone']),
                ':rname'  => trim($body['restaurant_name']),
                ':rlat'   => (float)($body['restaurant_lat'] ?? 0),
                ':rlng'   => (float)($body['restaurant_lng'] ?? 0),
                ':daddr'  => trim($body['dropoff_address']),
                ':dlat'   => (float)($body['dropoff_lat'] ?? 0),
                ':dlng'   => (float)($body['dropoff_lng'] ?? 0),
                ':amount' => (float)$body['total_amount'],
                ':eta'    => (int)($body['estimated_minutes'] ?? 30),
            ]);
            $newId = (int)$pdo->lastInsertId();

            // Log initial status
            $pdo->prepare(
                "INSERT INTO order_status_log (order_id, status, changed_by)
                 VALUES (:oid, 'pending', 'system')"
            )->execute([':oid' => $newId]);

            jsonResponse(['id' => $newId, 'order_number' => $orderNum], 201);

        // ── UPDATE order status 
        case 'update_status':
            requireAuth();
            if (!in_array($method, ['POST','PATCH'])) errorResponse('POST/PATCH required.', 405);

            $id   = (int)($_GET['id'] ?? 0);
            if (!$id) errorResponse('Missing order id.');
            $body = getJsonBody();

            $newStatus  = $body['status']     ?? '';
            $changedBy  = $body['changed_by'] ?? 'rider';
            $note       = $body['note']       ?? null;

            if (!in_array($newStatus, ORDER_STATUSES)) {
                errorResponse('Invalid status value.');
            }

            // Fetch current order
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $order = $stmt->fetch();
            if (!$order) errorResponse('Order not found.', 404);

            // Enforce transition rules (allow cancel from any non-terminal state)
            if ($newStatus !== 'cancelled') {
                $expected = STATUS_TRANSITIONS[$order['status']] ?? null;
                if ($expected !== $newStatus) {
                    errorResponse(
                        "Cannot transition from '{$order['status']}' to '$newStatus'. " .
                        "Expected next: " . ($expected ?? 'none') . "."
                    );
                }
            }

            $pdo->beginTransaction();

            $extra = '';
            if ($newStatus === 'delivered') {
                $extra = ", delivered_at = NOW()";
            }

            $pdo->prepare(
                "UPDATE orders SET status = :s, updated_at = NOW() $extra WHERE id = :id"
            )->execute([':s' => $newStatus, ':id' => $id]);

            // Log
            $pdo->prepare(
                "INSERT INTO order_status_log (order_id, status, note, changed_by)
                 VALUES (:oid, :s, :note, :by)"
            )->execute([
                ':oid'  => $id,
                ':s'    => $newStatus,
                ':note' => $note,
                ':by'   => $changedBy,
            ]);

            // Free the rider when order is delivered or cancelled
            if (in_array($newStatus, ['delivered','cancelled']) && $order['rider_id']) {
                $pdo->prepare("UPDATE riders SET status = 'online' WHERE id = :rid")
                    ->execute([':rid' => $order['rider_id']]);
            }

            $pdo->commit();

            // Trigger push notifications asynchronously (best-effort)
            triggerStatusPush((int)$id, $order['order_number'], $newStatus, $pdo);

            jsonResponse(['message' => "Order status updated to '$newStatus'."]);

        // ── CANCEL order 
        case 'cancel':
            requireAuth();
            if (!in_array($method, ['POST','DELETE','PATCH'])) {
                errorResponse('POST/DELETE/PATCH required.', 405);
            }
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) errorResponse('Missing order id.');

            $stmt = $pdo->prepare("SELECT status, rider_id FROM orders WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $order = $stmt->fetch();
            if (!$order) errorResponse('Order not found.', 404);
            if (in_array($order['status'], ['delivered','cancelled'])) {
                errorResponse("Cannot cancel an order that is already '{$order['status']}'.");
            }

            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = :id")
                ->execute([':id' => $id]);
            $pdo->prepare(
                "INSERT INTO order_status_log (order_id, status, changed_by)
                 VALUES (:oid, 'cancelled', 'system')"
            )->execute([':oid' => $id]);

            if ($order['rider_id']) {
                $pdo->prepare("UPDATE riders SET status = 'online' WHERE id = :rid")
                    ->execute([':rid' => $order['rider_id']]);
            }
            $pdo->commit();
            jsonResponse(['message' => 'Order cancelled.']);

        default:
            errorResponse("Unknown action '$action'.", 404);
    }

} catch (RuntimeException $e) {
    errorResponse($e->getMessage(), 503);
} catch (PDOException $e) {
    error_log('[orders.php] ' . $e->getMessage());
    errorResponse('Database error.', 500);
}

// ── Helper: fire push to all subscriptions for this order 
function triggerStatusPush(int $orderId, string $orderNum, string $status, PDO $pdo): void
{
    $label = STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    $title = PUSH_TITLE;
    $body  = "Order #$orderNum: $label";

    // Find subscriptions tagged to this order's customer (subscriber_id = order's customer –
    // for this demo we notify ALL customer subscriptions; in production link by customer_id)
    $stmt = $pdo->prepare(
        "SELECT * FROM push_subscriptions WHERE subscriber_type = 'customer' LIMIT 50"
    );
    $stmt->execute();
    $subs = $stmt->fetchAll();

    foreach ($subs as $sub) {
        $result = sendWebPush($sub, $title, $body, [
            'url' => "/ForkFresh/track-order/index.html?order=$orderNum",
        ]);

        // Log the notification attempt
        $pdo->prepare(
            "INSERT INTO push_notifications
                (subscription_id, order_id, title, body, status, sent_at, error_msg)
             VALUES
                (:sid, :oid, :title, :body, :status, NOW(), :err)"
        )->execute([
            ':sid'    => $sub['id'],
            ':oid'    => $orderId,
            ':title'  => $title,
            ':body'   => $body,
            ':status' => $result['ok'] ? 'sent' : 'failed',
            ':err'    => $result['ok'] ? null : $result['error'],
        ]);

        // Remove expired subscriptions
        if (!$result['ok'] && str_contains($result['error'], 'expired')) {
            $pdo->prepare("DELETE FROM push_subscriptions WHERE id = :id")
                ->execute([':id' => $sub['id']]);
        }
    }
}
