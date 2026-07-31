<?php
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();

$userId = getCurrentUserId();
$pdo    = getDB();

/* ── mode: "create" (new custom plan) or "edit" (change existing) ── */
$mode  = in_array($_GET['mode'] ?? 'create', ['create','edit']) ? $_GET['mode'] : 'create';

/* ── If editing, load existing preferences ── */
$existing = null;
if ($mode === 'edit') {
    $stmt = $pdo->prepare(
        'SELECT p.*, s.plan_name, s.id AS sub_id
         FROM meal_plan_preferences p
         JOIN subscriptions s ON s.id = p.subscription_id
         WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 1'
    );
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();
}

/* ── Load allergen & food item lists from DB ── */
$allergens = $pdo->query('SELECT * FROM allergen_items ORDER BY id')->fetchAll();
$foodItems = $pdo->query('SELECT * FROM food_preference_items ORDER BY id')->fetchAll();

/* ── Pre-selected food items ── */
$selectedFoods    = [];
$selectedAllergens = [];
if ($existing) {
    $f = $pdo->prepare(
        'SELECT food_item_id FROM preference_food_items WHERE preference_id = ?'
    );
    $f->execute([$existing['id']]);
    $selectedFoods = array_column($f->fetchAll(), 'food_item_id');

    $a = $pdo->prepare(
        'SELECT allergen_id FROM preference_allergens WHERE preference_id = ?'
    );
    $a->execute([$existing['id']]);
    $selectedAllergens = array_column($a->fetchAll(), 'allergen_id');
}

/* ── Food emoji map ── */
$foodEmoji = [
    'Chicken'    => '🍗',
    'Beef'       => '🥩',
    'Pork'       => '🐖',
    'Potato'     => '🥔',
    'Vegetables' => '🥦',
];

/* ── Allergen emoji map ── */
$allergenEmoji = [
    'Groundnut' => '🥜',
    'Wheat'     => '🌾',
    'Soy Beans' => '🫘',
    'Dairy'     => '🥛',
    'Eggs'      => '🥚',
    'Fish'      => '🐟',
    'Sesame'    => '🌱',
    'Sulphites' => '🧪',
    'Shellfish' => '🦐',
    'Other'     => '…',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customize Meal Plan – ForkFresh</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="app-wrapper">
  <?php include 'partials/sidebar.php'; ?>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="main-content customize-page">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu">
          <i class="fa fa-bars"></i>
        </button>
        <div style="display:flex;align-items:center;gap:10px;">
          <i class="fa fa-list" style="color:var(--text-mid);font-size:1.1rem;"></i>
          <div>
            <h1 style="font-size:1.2rem;font-weight:800;">Customize Your Meal Plan</h1>
            <p style="font-size:.82rem;color:var(--text-light);">
              Tell us your preferences and we'll normalize your meal
            </p>
          </div>
        </div>
      </div>
    </header>

    <!-- Step Tabs -->
    <div class="step-tabs" id="stepTabs">
      <div class="step-tab active" data-step="1">1. Preferences</div>
      <div class="step-tab"        data-step="2">2. Allergies</div>
      <div class="step-tab"        data-step="3">3. Review</div>
    </div>

    <form id="customizeForm" novalidate>
      <input type="hidden" name="mode"   value="<?= h($mode) ?>">
      <input type="hidden" name="sub_id" value="<?= $existing ? (int)$existing['sub_id'] : '' ?>">

      <!-- ══════════════════════════════════════
           STEP 1 – PREFERENCES
           ══════════════════════════════════════ -->
      <div class="step-panel active" id="step1">

        <!-- Diet Preferences (single-select chips) -->
        <p class="step-section-title">Diet Preferences</p>
        <div class="chip-group" data-group="single" id="dietGroup">
          <?php
          $diets = [
            'high_protein' => 'High Protein',
            'no_salt'      => 'No Salt',
            'vegetarian'   => 'Vegetarian',
            'no_maggi'     => 'No Maggi',
            'balanced'     => 'Balanced',
          ];
          foreach ($diets as $val => $label):
            $sel = ($existing && $existing['diet_preference'] === $val) ? 'selected' : '';
            $def = (!$existing && $val === 'balanced') ? 'selected' : '';
          ?>
          <label class="chip-label <?= $sel ?: $def ?>">
            <input type="radio" name="diet_preference" value="<?= h($val) ?>"
              <?= ($sel || $def) ? 'checked' : '' ?>>
            <span class="chip-dot"></span>
            <?= h($label) ?>
          </label>
          <?php endforeach; ?>
        </div>

        <!-- Food Preferences (multi-select icon cards) -->
        <p class="step-section-title">
          Food Preferences
          <span style="font-size:.78rem;font-weight:400;color:var(--text-light);">
            (Select your favourite ingredient)
          </span>
        </p>
        <div class="food-pref-grid" data-group="multi" id="foodGroup">
          <?php foreach ($foodItems as $item):
            $isSel = in_array($item['id'], $selectedFoods) ? 'selected' : '';
            $emoji = $foodEmoji[$item['name']] ?? '🍽️';
          ?>
          <label class="food-card-label <?= $isSel ?>">
            <input type="checkbox" name="food_items[]" value="<?= (int)$item['id'] ?>"
              <?= $isSel ? 'checked' : '' ?>>
            <span class="food-emoji"><?= $emoji ?></span>
            <span><?= h($item['name']) ?></span>
            <span class="check-badge"><i class="fa fa-check" style="font-size:.6rem;"></i></span>
          </label>
          <?php endforeach; ?>
        </div>

        <!-- Spice Level (single-select chips) -->
        <p class="step-section-title">Spice Level</p>
        <div class="chip-group" data-group="single" id="spiceGroup">
          <?php
          $spices = [
            'no_spice'  => 'No Spice',
            'mild'      => 'Mild',
            'medium'    => 'Medium',
            'hot'       => 'Hot',
            'extra_hot' => 'Extra Hot',
          ];
          foreach ($spices as $val => $label):
            $sel = ($existing && $existing['spice_level'] === $val) ? 'selected' : '';
            $def = (!$existing && $val === 'medium') ? 'selected' : '';
          ?>
          <label class="chip-label <?= $sel ?: $def ?>">
            <input type="radio" name="spice_level" value="<?= h($val) ?>"
              <?= ($sel || $def) ? 'checked' : '' ?>>
            <span class="chip-dot"></span>
            <?= h($label) ?>
          </label>
          <?php endforeach; ?>
        </div>

      </div><!-- /step1 -->

      <!-- ══════════════════════════════════════
           STEP 2 – ALLERGIES
           ══════════════════════════════════════ -->
      <div class="step-panel" id="step2">

        <p class="step-section-title">
          Food Allergies
          <span style="font-size:.78rem;font-weight:400;color:var(--text-light);">
            (Select any you are allergic to)
          </span>
        </p>

        <div class="allergen-grid" data-group="multi" id="allergenGroup">
          <?php foreach ($allergens as $al):
            $isSel = in_array($al['id'], $selectedAllergens) ? 'selected' : '';
            $emoji  = $allergenEmoji[$al['name']] ?? '⚠️';
          ?>
          <label class="allergen-label <?= $isSel ?>">
            <input type="checkbox" name="allergens[]" value="<?= (int)$al['id'] ?>"
              <?= $isSel ? 'checked' : '' ?>>
            <span class="allergen-emoji"><?= $emoji ?></span>
            <span><?= h($al['name']) ?></span>
            <span class="check-badge"><i class="fa fa-check" style="font-size:.55rem;"></i></span>
          </label>
          <?php endforeach; ?>
        </div>

        <p class="step-section-title" style="margin-top:8px;">Additional Information</p>
        <p style="font-size:.82rem;color:var(--text-light);margin-bottom:10px;">
          Please let us know about any other allergy or dietary restrictions
        </p>
        <textarea
          name="additional_info"
          class="additional-info-box"
          placeholder="e.g. I am lactose intolerant, no palm oil please…"
        ><?= $existing ? h($existing['additional_info'] ?? '') : '' ?></textarea>

      </div><!-- /step2 -->

      <!-- ══════════════════════════════════════
           STEP 3 – REVIEW
           ══════════════════════════════════════ -->
      <div class="step-panel" id="step3">

        <div class="review-summary">
          <h2>Review Your Meal Plan Preferences</h2>
          <p style="font-size:.85rem;color:var(--text-light);margin-bottom:18px;">
            Please review your selections before we create your personalized meal plan
          </p>

          <div class="review-cards-row">
            <div class="review-card">
              <div class="rc-icon"><i class="fa fa-seedling"></i></div>
              <div class="rc-label">Diet Preferences</div>
              <div class="rc-value" id="reviewDiet">—</div>
            </div>
            <div class="review-card">
              <div class="rc-icon"><i class="fa fa-drumstick-bite"></i></div>
              <div class="rc-label">Food Preferences</div>
              <div class="rc-value" id="reviewFoods">—</div>
            </div>
            <div class="review-card">
              <div class="rc-icon"><i class="fa fa-fire-flame-curved"></i></div>
              <div class="rc-label">Spice Level</div>
              <div class="rc-value" id="reviewSpice">—</div>
            </div>
          </div>
        </div>

        <div class="review-detail-row" style="cursor:pointer;" id="reviewAllergyRow">
          <span class="rd-label">Allergies</span>
          <span class="rd-value" id="reviewAllergies">No Allergies Selected</span>
          <span class="rd-chevron"><i class="fa fa-chevron-right"></i></span>
        </div>

        <div class="review-detail-row">
          <span class="rd-label">Additional Information</span>
          <span class="rd-value" id="reviewAdditional">None Provided</span>
          <span class="rd-chevron"><i class="fa fa-chevron-right"></i></span>
        </div>

        <!-- Hidden submit flag -->
        <input type="hidden" name="action" value="save_preferences">

      </div><!-- /step3 -->

    </form><!-- /customizeForm -->

    <!-- ── Step Navigation Bar ── -->
    <div class="step-nav" id="stepNav">
      <button type="button" class="btn-step-back" id="btnBack" style="visibility:hidden;">
        <i class="fa fa-chevron-left"></i> Back to Preferences
      </button>
      <button type="button" class="btn-step-next" id="btnNext">
        Next: Allergies <i class="fa fa-chevron-right"></i>
      </button>
    </div>

    <?php include 'partials/footer.php'; ?>

  </div><!-- /main-content -->
</div><!-- /app-wrapper -->

<div class="toast-container" id="toastContainer"></div>
<script src="assets/js/app.js"></script>
<script src="assets/js/customize-wizard.js"></script>
</body>
</html>
