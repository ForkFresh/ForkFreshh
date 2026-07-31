<?php
/**
 * ForkFresh – Subscription Handler API
 * Accepts JSON POST requests.
 *
 * Actions:
 *   subscribe  – create a new subscription from a template
 *   pause      – pause an active subscription
 *   resume     – resume a paused subscription
 *   cancel     – cancel a subscription
 */

declare(strict_types=1);
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();

/* ── Only accept POST ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Method not allowed.');
}

/* ── Parse JSON body ── */
$body   = file_get_contents('php://input');
$data   = json_decode($body, true);
$action = trim((string)($data['action'] ?? ''));

if (!$action) {
    jsonResponse(false, 'Missing action.');
}

$pdo    = getDB();
$userId = getCurrentUserId();

/* ============================================================
   ACTION: subscribe
   ============================================================ */
if ($action === 'subscribe') {

    $templateId = filter_var($data['template_id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$templateId) {
        jsonResponse(false, 'Invalid plan selected.');
    }

    /* Load template */
    $tplStmt = $pdo->prepare(
        'SELECT * FROM meal_plan_templates WHERE id = ? AND is_active = 1 LIMIT 1'
    );
    $tplStmt->execute([$templateId]);
    $tpl = $tplStmt->fetch();

    if (!$tpl) {
        jsonResponse(false, 'Meal plan not found.');
    }

    /* Cancel any existing active/paused subscription first */
    $pdo->prepare(
        'UPDATE subscriptions SET status = "cancelled", updated_at = NOW()
         WHERE user_id = ? AND status IN ("active","paused")'
    )->execute([$userId]);

    /* Compute billing dates */
    $startDate   = date('Y-m-d');
    $nextBilling = ($tpl['billing_cycle'] === 'month')
        ? date('Y-m-d', strtotime('+1 month'))
        : date('Y-m-d', strtotime('+1 week'));

    /* Insert subscription */
    $ins = $pdo->prepare(
        'INSERT INTO subscriptions
            (user_id, template_id, plan_name, price, billing_cycle,
             meals_per_day, days_per_week, meal_type, status,
             start_date, next_billing_date, total_spent, is_custom)
         VALUES (?,?,?,?,?,?,?,?,"active",?,?,?,0)'
    );
    $ins->execute([
        $userId,
        $templateId,
        $tpl['name'],
        $tpl['price'],
        $tpl['billing_cycle'],
        $tpl['meals_per_day'],
        $tpl['days_per_week'],
        $tpl['meal_type'],
        $startDate,
        $nextBilling,
        $tpl['price'],        // initial total_spent = first payment
    ]);

    $subId = (int)$pdo->lastInsertId();

    /* Seed upcoming deliveries (next 7 days) */
    seedDeliveries($pdo, $subId, $userId, (int)$tpl['days_per_week']);

    jsonResponse(true, 'Subscription activated!', ['sub_id' => $subId]);
}

/* ============================================================
   ACTION: pause
   ============================================================ */
if ($action === 'pause') {
    $subId = filter_var($data['sub_id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$subId) jsonResponse(false, 'Invalid subscription.');

    $upd = $pdo->prepare(
        'UPDATE subscriptions SET status = "paused", updated_at = NOW()
         WHERE id = ? AND user_id = ? AND status = "active"'
    );
    $upd->execute([$subId, $userId]);

    if ($upd->rowCount() === 0) {
        jsonResponse(false, 'Subscription not found or already paused.');
    }

    jsonResponse(true, 'Subscription paused. You can resume anytime.');
}

/* ============================================================
   ACTION: resume
   ============================================================ */
if ($action === 'resume') {
    $subId = filter_var($data['sub_id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$subId) jsonResponse(false, 'Invalid subscription.');

    /* Recalculate next billing from today */
    $cycleStmt = $pdo->prepare('SELECT billing_cycle FROM subscriptions WHERE id = ? AND user_id = ?');
    $cycleStmt->execute([$subId, $userId]);
    $row = $cycleStmt->fetch();

    if (!$row) jsonResponse(false, 'Subscription not found.');

    $nextBilling = ($row['billing_cycle'] === 'month')
        ? date('Y-m-d', strtotime('+1 month'))
        : date('Y-m-d', strtotime('+1 week'));

    $upd = $pdo->prepare(
        'UPDATE subscriptions
         SET status = "active", next_billing_date = ?, updated_at = NOW()
         WHERE id = ? AND user_id = ? AND status = "paused"'
    );
    $upd->execute([$nextBilling, $subId, $userId]);

    if ($upd->rowCount() === 0) {
        jsonResponse(false, 'Subscription not found or not paused.');
    }

    /* Re-seed deliveries from today */
    $cycleRow = $pdo->prepare('SELECT days_per_week FROM subscriptions WHERE id = ?');
    $cycleRow->execute([$subId]);
    $daysPerWeek = (int)($cycleRow->fetchColumn() ?: 7);

    /* Clear old scheduled deliveries and re-seed */
    $pdo->prepare(
        'DELETE FROM deliveries WHERE subscription_id = ? AND status = "scheduled" AND delivery_date >= CURDATE()'
    )->execute([$subId]);
    seedDeliveries($pdo, $subId, $userId, $daysPerWeek);

    jsonResponse(true, 'Subscription resumed successfully!');
}

/* ============================================================
   ACTION: cancel
   ============================================================ */
if ($action === 'cancel') {
    $subId = filter_var($data['sub_id'] ?? 0, FILTER_VALIDATE_INT);
    if (!$subId) jsonResponse(false, 'Invalid subscription.');

    $upd = $pdo->prepare(
        'UPDATE subscriptions SET status = "cancelled", updated_at = NOW()
         WHERE id = ? AND user_id = ? AND status IN ("active","paused")'
    );
    $upd->execute([$subId, $userId]);

    if ($upd->rowCount() === 0) {
        jsonResponse(false, 'Subscription not found or already cancelled.');
    }

    /* Cancel future deliveries */
    $pdo->prepare(
        'UPDATE deliveries SET status = "skipped"
         WHERE subscription_id = ? AND status = "scheduled" AND delivery_date >= CURDATE()'
    )->execute([$subId]);

    jsonResponse(true, 'Subscription cancelled successfully.');
}

/* Unknown action */
jsonResponse(false, 'Unknown action: ' . htmlspecialchars($action));

/* ============================================================
   Helper – seed upcoming deliveries
   ============================================================ */
function seedDeliveries(PDO $pdo, int $subId, int $userId, int $daysPerWeek): void {
    $insStmt = $pdo->prepare(
        'INSERT INTO deliveries (subscription_id, user_id, delivery_date, meal_description, status)
         VALUES (?,?,?,"Breakfast & Lunch","scheduled")'
    );

    $day   = 0;
    $count = 0;
    /* Seed 14 days worth, skipping days if daysPerWeek < 7 */
    while ($count < min($daysPerWeek * 2, 14)) {
        $date = date('Y-m-d', strtotime("+{$day} days"));
        $dow  = (int)date('N', strtotime($date)); // 1=Mon … 7=Sun

        /* For weight loss plan (6 days/week) skip Sundays */
        if ($daysPerWeek === 6 && $dow === 7) {
            $day++;
            continue;
        }
        $insStmt->execute([$subId, $userId, $date]);
        $count++;
        $day++;
    }
}
