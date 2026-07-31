<?php
/**
 * ForkFresh – Meal Plan Handler API
 * Accepts JSON POST requests.
 *
 * Actions:
 *   save_preferences – create or update a custom meal plan subscription
 *                      with preferences, food items, and allergens
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
   ACTION: save_preferences
   ============================================================ */
if ($action === 'save_preferences') {

    /* ── Validate inputs ── */
    $dietAllowed  = ['high_protein','no_salt','vegetarian','no_maggi','balanced'];
    $spiceAllowed = ['no_spice','mild','medium','hot','extra_hot'];

    $diet  = in_array($data['diet_preference'] ?? '', $dietAllowed, true)
           ? $data['diet_preference'] : null;
    $spice = in_array($data['spice_level'] ?? '', $spiceAllowed, true)
           ? $data['spice_level'] : null;

    if (!$diet)  jsonResponse(false, 'Invalid diet preference.');
    if (!$spice) jsonResponse(false, 'Invalid spice level.');

    $foodItems = array_filter(
        array_map('intval', (array)($data['food_items'] ?? [])),
        fn($v) => $v > 0
    );
    $allergens = array_filter(
        array_map('intval', (array)($data['allergens'] ?? [])),
        fn($v) => $v > 0
    );
    $additionalInfo = mb_substr(strip_tags((string)($data['additional_info'] ?? '')), 0, 1000);

    $mode  = ($data['mode'] ?? 'create') === 'edit' ? 'edit' : 'create';
    $subId = filter_var($data['sub_id'] ?? 0, FILTER_VALIDATE_INT);

    /* ── Validate food items exist in DB ── */
    if (!empty($foodItems)) {
        $placeholders = implode(',', array_fill(0, count($foodItems), '?'));
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM food_preference_items WHERE id IN ($placeholders)"
        );
        $check->execute(array_values($foodItems));
        if ((int)$check->fetchColumn() !== count($foodItems)) {
            jsonResponse(false, 'One or more food items are invalid.');
        }
    }

    /* ── Validate allergens exist in DB ── */
    if (!empty($allergens)) {
        $placeholders = implode(',', array_fill(0, count($allergens), '?'));
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM allergen_items WHERE id IN ($placeholders)"
        );
        $check->execute(array_values($allergens));
        if ((int)$check->fetchColumn() !== count($allergens)) {
            jsonResponse(false, 'One or more allergen items are invalid.');
        }
    }

    $pdo->beginTransaction();

    try {

        /* ── CREATE mode: build a new custom subscription ── */
        if ($mode === 'create') {

            /* Cancel any existing active subscription */
            $pdo->prepare(
                'UPDATE subscriptions SET status = "cancelled", updated_at = NOW()
                 WHERE user_id = ? AND status IN ("active","paused")'
            )->execute([$userId]);

            $startDate   = date('Y-m-d');
            $nextBilling = date('Y-m-d', strtotime('+1 week'));

            /* Custom plan: base price FCFA 15,000 / week */
            $customPrice = 15000.00;

            $ins = $pdo->prepare(
                'INSERT INTO subscriptions
                    (user_id, template_id, plan_name, price, billing_cycle,
                     meals_per_day, days_per_week, meal_type, status,
                     start_date, next_billing_date, total_spent, is_custom)
                 VALUES (?, NULL, "Custom Meal Plan", ?, "week",
                         2, 7, "Personalized meals", "active", ?, ?, ?, 1)'
            );
            $ins->execute([
                $userId,
                $customPrice,
                $startDate,
                $nextBilling,
                $customPrice,
            ]);
            $subId = (int)$pdo->lastInsertId();

            /* Seed deliveries */
            seedDeliveries($pdo, $subId, $userId, 7);
        }

        /* ── EDIT mode: verify subscription belongs to user ── */
        if ($mode === 'edit') {
            if (!$subId) jsonResponse(false, 'No subscription ID provided for edit.');

            $check = $pdo->prepare(
                'SELECT id FROM subscriptions WHERE id = ? AND user_id = ?'
            );
            $check->execute([$subId, $userId]);
            if (!$check->fetch()) {
                $pdo->rollBack();
                jsonResponse(false, 'Subscription not found.');
            }
        }

        /* ── Upsert meal_plan_preferences ── */
        $existPref = $pdo->prepare(
            'SELECT id FROM meal_plan_preferences WHERE subscription_id = ? AND user_id = ? LIMIT 1'
        );
        $existPref->execute([$subId, $userId]);
        $prefRow = $existPref->fetch();

        if ($prefRow) {
            /* UPDATE */
            $pdo->prepare(
                'UPDATE meal_plan_preferences
                 SET diet_preference = ?, spice_level = ?, additional_info = ?, updated_at = NOW()
                 WHERE id = ?'
            )->execute([$diet, $spice, $additionalInfo, $prefRow['id']]);
            $prefId = (int)$prefRow['id'];
        } else {
            /* INSERT */
            $pdo->prepare(
                'INSERT INTO meal_plan_preferences
                    (subscription_id, user_id, diet_preference, spice_level, additional_info)
                 VALUES (?,?,?,?,?)'
            )->execute([$subId, $userId, $diet, $spice, $additionalInfo]);
            $prefId = (int)$pdo->lastInsertId();
        }

        /* ── Sync food items (delete + re-insert) ── */
        $pdo->prepare(
            'DELETE FROM preference_food_items WHERE preference_id = ?'
        )->execute([$prefId]);

        if (!empty($foodItems)) {
            $insFoodStmt = $pdo->prepare(
                'INSERT INTO preference_food_items (preference_id, food_item_id) VALUES (?,?)'
            );
            foreach ($foodItems as $fid) {
                $insFoodStmt->execute([$prefId, $fid]);
            }
        }

        /* ── Sync allergens (delete + re-insert) ── */
        $pdo->prepare(
            'DELETE FROM preference_allergens WHERE preference_id = ?'
        )->execute([$prefId]);

        if (!empty($allergens)) {
            $insAlrgStmt = $pdo->prepare(
                'INSERT INTO preference_allergens (preference_id, allergen_id) VALUES (?,?)'
            );
            foreach ($allergens as $aid) {
                $insAlrgStmt->execute([$prefId, $aid]);
            }
        }

        $pdo->commit();

        jsonResponse(true, 'Meal plan preferences saved!', ['sub_id' => $subId]);

    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[ForkFresh] meal-plan-handler error: ' . $e->getMessage());
        jsonResponse(false, 'An error occurred. Please try again.');
    }
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
    while ($count < min($daysPerWeek * 2, 14)) {
        $date = date('Y-m-d', strtotime("+{$day} days"));
        $dow  = (int)date('N', strtotime($date));

        /* Skip Sundays for 6-day plans */
        if ($daysPerWeek === 6 && $dow === 7) {
            $day++;
            continue;
        }
        $insStmt->execute([$subId, $userId, $date]);
        $count++;
        $day++;
    }
}
