<?php

// api/riders.php  –  Rider management & assignment

// Routes (method + ?action=):
//   GET    ?action=list              – all riders (+ optional ?status=online)
//   GET    ?action=get&id=N          – single rider
//   POST   ?action=create            – create rider         [auth]
//   PATCH  ?action=update&id=N       – update fields        [auth]
//   PATCH  ?action=toggle_status&id=N– online/offline/busy  [auth]
//   DELETE ?action=delete&id=N       – soft-delete          [auth]
//   GET    ?action=available          – riders with no active order
//   POST   ?action=assign             – assign rider to order[auth]


require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/response.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {

        // ── LIST all riders 
        case 'list':
            $where  = '';
            $params = [];
            if (!empty($_GET['status'])) {
                $where    = 'WHERE status = :status';
                $params[':status'] = $_GET['status'];
            }
            $stmt = $pdo->prepare(
                "SELECT id, rider_code, name, phone, email,
                        avatar_url, status, rating, vehicle_type,
                        created_at
                 FROM riders $where
                 ORDER BY name ASC"
            );
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());

        // ── GET single rider 
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) errorResponse('Missing rider id.');

            $stmt = $pdo->prepare(
                "SELECT r.*,
                        COUNT(o.id) AS total_orders,
                        SUM(o.status = 'delivered') AS completed_orders
                 FROM riders r
                 LEFT JOIN orders o ON o.rider_id = r.id
                 WHERE r.id = :id
                 GROUP BY r.id"
            );
            $stmt->execute([':id' => $id]);
            $rider = $stmt->fetch();
            if (!$rider) errorResponse('Rider not found.', 404);
            jsonResponse($rider);

        // ── CREATE rider 
        case 'create':
            requireAuth();
            if ($method !== 'POST') errorResponse('POST required.', 405);

            $body = getJsonBody();
            $required = ['name','phone','email','password','rider_code'];
            foreach ($required as $f) {
                if (empty($body[$f])) errorResponse("Field '$f' is required.");
            }

            // Validate e-mail
            if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
                errorResponse('Invalid email address.');
            }

            $hash = password_hash($body['password'], PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $pdo->prepare(
                "INSERT INTO riders
                    (rider_code, name, phone, email, password_hash, vehicle_type)
                 VALUES
                    (:code, :name, :phone, :email, :hash, :vehicle)"
            );
            $stmt->execute([
                ':code'    => strtoupper(trim($body['rider_code'])),
                ':name'    => trim($body['name']),
                ':phone'   => trim($body['phone']),
                ':email'   => strtolower(trim($body['email'])),
                ':hash'    => $hash,
                ':vehicle' => $body['vehicle_type'] ?? 'motorcycle',
            ]);
            $newId = (int)$pdo->lastInsertId();
            jsonResponse(['id' => $newId, 'message' => 'Rider created.'], 201);

        // ── UPDATE rider fields 
        case 'update':
            requireAuth();
            if (!in_array($method, ['POST','PATCH'])) errorResponse('POST/PATCH required.', 405);

            $id   = (int)($_GET['id'] ?? 0);
            if (!$id) errorResponse('Missing rider id.');
            $body = getJsonBody();

            $allowed = ['name','phone','email','avatar_url','vehicle_type','rating'];
            $sets    = [];
            $params  = [':id' => $id];
            foreach ($allowed as $col) {
                if (isset($body[$col])) {
                    $sets[]         = "$col = :$col";
                    $params[":$col"] = $body[$col];
                }
            }
            if (empty($sets)) errorResponse('Nothing to update.');

            $pdo->prepare("UPDATE riders SET " . implode(', ', $sets) . " WHERE id = :id")
                ->execute($params);
            jsonResponse(['message' => 'Rider updated.']);

        // ── TOGGLE online / offline / busy 
        case 'toggle_status':
            requireAuth();
            if (!in_array($method, ['POST','PATCH'])) errorResponse('POST/PATCH required.', 405);

            $id   = (int)($_GET['id'] ?? 0);
            if (!$id) errorResponse('Missing rider id.');
            $body = getJsonBody();

            $newStatus = $body['status'] ?? '';
            if (!in_array($newStatus, ['online','offline','busy'])) {
                errorResponse("status must be one of: online, offline, busy.");
            }

            $pdo->prepare("UPDATE riders SET status = :s WHERE id = :id")
                ->execute([':s' => $newStatus, ':id' => $id]);
            jsonResponse(['message' => "Rider status set to '$newStatus'."]);

        // ── LIST available riders (online + no active delivery) ─
        case 'available':
            $stmt = $pdo->prepare(
                "SELECT r.id, r.rider_code, r.name, r.phone,
                        r.avatar_url, r.rating, r.vehicle_type
                 FROM riders r
                 WHERE r.status = 'online'
                   AND r.id NOT IN (
                       SELECT DISTINCT rider_id FROM orders
                       WHERE status IN ('assigned','preparing','on_the_way','out_for_delivery')
                         AND rider_id IS NOT NULL
                   )
                 ORDER BY r.rating DESC"
            );
            $stmt->execute();
            jsonResponse($stmt->fetchAll());

        // ── ASSIGN rider to order 
        case 'assign':
            requireAuth();
            if ($method !== 'POST') errorResponse('POST required.', 405);

            $body     = getJsonBody();
            $riderId  = (int)($body['rider_id']  ?? 0);
            $orderId  = (int)($body['order_id']  ?? 0);
            if (!$riderId || !$orderId) errorResponse('rider_id and order_id required.');

            // Verify rider exists and is online
            $rStmt = $pdo->prepare("SELECT id, status FROM riders WHERE id = :id");
            $rStmt->execute([':id' => $riderId]);
            $rider = $rStmt->fetch();
            if (!$rider) errorResponse('Rider not found.', 404);
            if ($rider['status'] === 'offline') errorResponse('Rider is offline.');

            // Verify order exists and is pending/preparing
            $oStmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = :id");
            $oStmt->execute([':id' => $orderId]);
            $order = $oStmt->fetch();
            if (!$order) errorResponse('Order not found.', 404);
            if (!in_array($order['status'], ['pending','preparing'])) {
                errorResponse("Order cannot be assigned in status '{$order['status']}'.");
            }

            $pdo->beginTransaction();
            // Update order
            $pdo->prepare(
                "UPDATE orders
                 SET rider_id = :rid, status = 'assigned', assigned_at = NOW()
                 WHERE id = :oid"
            )->execute([':rid' => $riderId, ':oid' => $orderId]);

            // Log status change
            $pdo->prepare(
                "INSERT INTO order_status_log (order_id, status, changed_by)
                 VALUES (:oid, 'assigned', 'system')"
            )->execute([':oid' => $orderId]);

            // Mark rider as busy
            $pdo->prepare("UPDATE riders SET status = 'busy' WHERE id = :id")
                ->execute([':id' => $riderId]);

            $pdo->commit();
            jsonResponse(['message' => 'Rider assigned successfully.']);

        // ── DELETE rider 
        case 'delete':
            requireAuth();
            if ($method !== 'DELETE') errorResponse('DELETE required.', 405);

            $id = (int)($_GET['id'] ?? 0);
            if (!$id) errorResponse('Missing rider id.');

            $pdo->prepare("DELETE FROM riders WHERE id = :id")->execute([':id' => $id]);
            jsonResponse(['message' => 'Rider deleted.']);

        default:
            errorResponse("Unknown action '$action'.", 404);
    }

} catch (RuntimeException $e) {
    errorResponse($e->getMessage(), 503);
} catch (PDOException $e) {
    error_log('[riders.php] ' . $e->getMessage());
    errorResponse('Database error.', 500);
}
