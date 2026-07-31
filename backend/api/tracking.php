<?php

// api/tracking.php  –  GPS tracking endpoints

// Routes:
//   POST ?action=update              – rider posts current GPS  [auth]
//   GET  ?action=location&rider_id=N – latest location for a rider
//   GET  ?action=history&rider_id=N  – GPS trail (last N points)
//   GET  ?action=order_location&order_id=N – rider location for an order


require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/response.php';

setCorsHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {

        // ── RIDER posts their current GPS coords 
        case 'update':
            requireAuth();
            if ($method !== 'POST') errorResponse('POST required.', 405);

            $body    = getJsonBody();
            $riderId = (int)($body['rider_id']  ?? 0);
            $lat     = (float)($body['latitude']  ?? 0);
            $lng     = (float)($body['longitude'] ?? 0);

            if (!$riderId)            errorResponse('rider_id required.');
            if ($lat === 0.0 || $lng === 0.0) errorResponse('latitude and longitude required.');

            // Validate coordinate ranges
            if ($lat < -90 || $lat > 90)   errorResponse('latitude out of range.');
            if ($lng < -180 || $lng > 180) errorResponse('longitude out of range.');

            $orderId = isset($body['order_id']) ? (int)$body['order_id'] : null;
            $speed   = (float)($body['speed_kmh'] ?? 0);
            $heading = (float)($body['heading']   ?? 0);

            // Insert GPS point
            $stmt = $pdo->prepare(
                "INSERT INTO gps_tracking
                    (rider_id, order_id, latitude, longitude, speed_kmh, heading)
                 VALUES
                    (:rid, :oid, :lat, :lng, :spd, :hdg)"
            );
            $stmt->execute([
                ':rid' => $riderId,
                ':oid' => $orderId,
                ':lat' => $lat,
                ':lng' => $lng,
                ':spd' => $speed,
                ':hdg' => $heading,
            ]);

            // Prune old points – keep only last 500 per rider to avoid table bloat
            $pdo->prepare(
                "DELETE FROM gps_tracking
                 WHERE rider_id = :rid
                   AND id NOT IN (
                       SELECT id FROM (
                           SELECT id FROM gps_tracking
                           WHERE rider_id = :rid2
                           ORDER BY recorded_at DESC
                           LIMIT 500
                       ) t
                   )"
            )->execute([':rid' => $riderId, ':rid2' => $riderId]);

            jsonResponse(['message' => 'Location updated.', 'recorded_at' => date('c')]);

        // ── LATEST location for a rider
        case 'location':
            $riderId = (int)($_GET['rider_id'] ?? 0);
            if (!$riderId) errorResponse('rider_id required.');

            $stmt = $pdo->prepare(
                "SELECT g.latitude, g.longitude, g.speed_kmh,
                        g.heading, g.recorded_at,
                        r.name AS rider_name, r.status AS rider_status,
                        r.avatar_url
                 FROM gps_tracking g
                 JOIN riders r ON r.id = g.rider_id
                 WHERE g.rider_id = :rid
                 ORDER BY g.recorded_at DESC
                 LIMIT 1"
            );
            $stmt->execute([':rid' => $riderId]);
            $loc = $stmt->fetch();

            if (!$loc) {
                // Return rider info even if no GPS yet
                $rStmt = $pdo->prepare(
                    "SELECT name AS rider_name, status AS rider_status,
                            avatar_url, NULL AS latitude, NULL AS longitude
                     FROM riders WHERE id = :rid"
                );
                $rStmt->execute([':rid' => $riderId]);
                $loc = $rStmt->fetch();
            }

            if (!$loc) errorResponse('Rider not found.', 404);
            jsonResponse($loc);

        // ── GPS HISTORY trail for a rider 
        case 'history':
            $riderId = (int)($_GET['rider_id'] ?? 0);
            $limit   = min((int)($_GET['limit'] ?? 50), 200); // cap at 200
            if (!$riderId) errorResponse('rider_id required.');

            $stmt = $pdo->prepare(
                "SELECT latitude, longitude, speed_kmh, heading, recorded_at
                 FROM gps_tracking
                 WHERE rider_id = :rid
                 ORDER BY recorded_at DESC
                 LIMIT :lim"
            );
            $stmt->bindValue(':rid', $riderId, PDO::PARAM_INT);
            $stmt->bindValue(':lim', $limit,   PDO::PARAM_INT);
            $stmt->execute();

            // Return in chronological order (oldest first for drawing a trail)
            $points = array_reverse($stmt->fetchAll());
            jsonResponse($points);

        // ── LOCATION for an order's assigned rider 
        case 'order_location':
            $orderId = (int)($_GET['order_id'] ?? 0);
            if (!$orderId) errorResponse('order_id required.');

            // Get the rider assigned to this order
            $oStmt = $pdo->prepare(
                "SELECT o.rider_id, o.status AS order_status,
                        o.dropoff_lat, o.dropoff_lng,
                        o.restaurant_lat, o.restaurant_lng
                 FROM orders o WHERE o.id = :oid"
            );
            $oStmt->execute([':oid' => $orderId]);
            $order = $oStmt->fetch();

            if (!$order)              errorResponse('Order not found.', 404);
            if (!$order['rider_id'])  errorResponse('No rider assigned yet.', 404);

            // Fetch latest GPS
            $gStmt = $pdo->prepare(
                "SELECT g.latitude, g.longitude, g.heading, g.speed_kmh, g.recorded_at,
                        r.name AS rider_name, r.phone AS rider_phone, r.avatar_url
                 FROM gps_tracking g
                 JOIN riders r ON r.id = g.rider_id
                 WHERE g.rider_id = :rid
                 ORDER BY g.recorded_at DESC
                 LIMIT 1"
            );
            $gStmt->execute([':rid' => $order['rider_id']]);
            $gps = $gStmt->fetch();

            jsonResponse([
                'order_status'   => $order['order_status'],
                'dropoff_lat'    => (float)$order['dropoff_lat'],
                'dropoff_lng'    => (float)$order['dropoff_lng'],
                'restaurant_lat' => (float)$order['restaurant_lat'],
                'restaurant_lng' => (float)$order['restaurant_lng'],
                'rider'          => $gps ?: null,
            ]);

        default:
            errorResponse("Unknown action '$action'.", 404);
    }

} catch (RuntimeException $e) {
    errorResponse($e->getMessage(), 503);
} catch (PDOException $e) {
    error_log('[tracking.php] ' . $e->getMessage());
    errorResponse('Database error.', 500);
}
