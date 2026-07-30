<?php
require_once '../includes/db.php';
startSession();

$userId = getCurrentUserId();
$pdo    = getDB();

/* ── Load all active plan templates from DB ── */
$stmt = $pdo->query(
    'SELECT * FROM meal_plan_templates WHERE is_active = 1 AND is_custom = 0 ORDER BY id ASC'
);
$plans = $stmt->fetchAll();

/* ── Check if user already has an active subscription ── */
$subStmt = $pdo->prepare(
    'SELECT id FROM subscriptions WHERE user_id = ? AND status = "active" LIMIT 1'
);
$subStmt->execute([$userId]);
$existingSub = $subStmt->fetch();

/* ── Detect "change plan" flow from manage-subscription page ── */
$changingPlan = isset($_GET['change']) && $_GET['change'] === '1';
$customizeMode = $existingSub ? 'edit' : 'create';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meal Plans – ForkFresh</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="app-wrapper">

  <!-- ======= SIDEBAR ======= -->
  <?php include 'partials/sidebar.php'; ?>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ======= MAIN ======= -->
  <div class="main-content meal-plans-page">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu">
          <i class="fa fa-bars"></i>
        </button>
        <div style="display:flex;align-items:center;gap:10px;">
          <i class="fa fa-list" style="color:var(--text-mid);font-size:1.1rem;"></i>
          <div>
            <h1 style="font-size:1.25rem;font-weight:800;">Meal Plans (Subscriptions)</h1>
            <p style="font-size:.82rem;color:var(--text-light);">Choose a plan fit for your lifestyle</p>
          </div>
        </div>
      </div>
      <div class="topbar-right">
        <a href="customize-meal-plan.php?mode=<?= $customizeMode ?>"
           class="btn-create-plan">
          <?= $existingSub ? 'Customize my plan' : 'Create your own plan' ?>
        </a>
      </div>
    </header>

    <!-- Plan Cards -->
    <section class="page-body" style="background:#fff;">

      <?php if ($changingPlan): ?>
      <div style="background:#e8f5e9;border:1px solid var(--green-main);border-radius:var(--radius);
                  padding:12px 20px;margin-bottom:20px;display:flex;
                  align-items:center;justify-content:space-between;gap:12px;">
        <p style="font-size:.88rem;">
          <i class="fa fa-circle-info" style="color:var(--green-main);"></i>
          Select a new plan below. Your current plan will be replaced immediately.
        </p>
        <a href="manage-subscription.php"
           style="font-size:.82rem;color:var(--green-main);font-weight:700;white-space:nowrap;">
          &larr; Back
        </a>
      </div>
      <?php elseif ($existingSub): ?>
      <div style="background:#fff3e0;border:1px solid var(--orange);border-radius:var(--radius);
                  padding:12px 20px;margin-bottom:20px;display:flex;
                  align-items:center;justify-content:space-between;gap:12px;">
        <p style="font-size:.88rem;">
          <i class="fa fa-circle-info" style="color:var(--orange);"></i>
          You already have an active subscription.
          Choosing a new plan will replace your current one.
        </p>
        <a href="manage-subscription.php"
           style="font-size:.82rem;color:var(--green-main);font-weight:700;white-space:nowrap;">
          View current plan &rarr;
        </a>
      </div>
      <?php endif; ?>

      <div class="plans-grid">
        <?php foreach ($plans as $plan): ?>
        <div class="plan-card">

          <?php if ($plan['is_popular']): ?>
          <div class="plan-popular-badge">Popular</div>
          <?php endif; ?>

          <h2><?= h($plan['name']) ?></h2>
          <p class="plan-tagline"><?= h($plan['description']) ?></p>

          <div class="plan-price">
            <?= fcfa((float)$plan['price']) ?>
            <span>/<?= h($plan['billing_cycle']) ?></span>
          </div>

          <ul class="plan-features">
            <li>
              <i class="fa fa-check check-icon"></i>
              <?= (int)$plan['meals_per_day'] ?> meals per day
            </li>
            <li>
              <i class="fa fa-check check-icon"></i>
              <?= (int)$plan['days_per_week'] ?> days a week
            </li>
            <li>
              <i class="fa fa-check check-icon"></i>
              <?= h($plan['meal_type']) ?>
            </li>
          </ul>

          <!-- Choose Plan triggers the subscribe API -->
          <button
            class="btn-choose-plan"
            data-plan-id="<?= (int)$plan['id'] ?>"
            data-plan-name="<?= h($plan['name']) ?>"
            data-price="<?= (float)$plan['price'] ?>"
            data-cycle="<?= h($plan['billing_cycle']) ?>">
            Choose plan
          </button>

        </div>
        <?php endforeach; ?>
      </div><!-- /plans-grid -->

      <!-- Benefits strip -->
      <div class="plans-benefits">
        <div class="benefit-item">
          <span class="b-icon"><i class="fa fa-rotate"></i></span>
          Pause and resume anytime
        </div>
        <div class="benefit-item">
          <span class="b-icon"><i class="fa fa-leaf"></i></span>
          Fresh &amp; Hygienic meals
        </div>
        <div class="benefit-item">
          <span class="b-icon"><i class="fa fa-clock"></i></span>
          Save time and money
        </div>
        <div class="benefit-item">
          <span class="b-icon"><i class="fa fa-motorcycle"></i></span>
          Delivered to your door
        </div>
      </div>

    </section><!-- /page-body -->

    <!-- Footer -->
    <?php include 'partials/footer.php'; ?>

  </div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- ── Confirm modal ── -->
<div id="confirmModal" style="display:none;position:fixed;inset:0;
     background:rgba(0,0,0,.45);z-index:9000;
     align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:var(--radius-lg);padding:32px 28px;
              max-width:420px;width:90%;box-shadow:var(--shadow-md);">
    <h2 style="font-size:1.1rem;font-weight:800;margin-bottom:8px;" id="modalTitle">
      Confirm Plan
    </h2>
    <p style="font-size:.88rem;color:var(--text-mid);margin-bottom:20px;" id="modalBody">
      Are you sure you want to subscribe to this plan?
    </p>
    <div style="display:flex;gap:12px;justify-content:flex-end;">
      <button id="modalCancel"
        style="padding:9px 20px;border:1.5px solid var(--border);
               border-radius:8px;background:#fff;font-size:.9rem;
               color:var(--text-mid);font-weight:600;cursor:pointer;">
        Cancel
      </button>
      <button id="modalConfirm"
        style="padding:9px 22px;background:var(--orange);color:#fff;
               border:none;border-radius:8px;font-size:.9rem;
               font-weight:700;cursor:pointer;">
        Subscribe
      </button>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="assets/js/app.js"></script>
<script>
(function () {
  let pendingPlanId   = null;
  let pendingPlanName = null;
  let pendingPrice    = null;
  let pendingCycle    = null;

  const modal        = document.getElementById('confirmModal');
  const modalTitle   = document.getElementById('modalTitle');
  const modalBody    = document.getElementById('modalBody');
  const modalConfirm = document.getElementById('modalConfirm');
  const modalCancel  = document.getElementById('modalCancel');

  /* Open confirm modal on "Choose plan" click */
  document.querySelectorAll('.btn-choose-plan').forEach(btn => {
    btn.addEventListener('click', function () {
      pendingPlanId   = this.dataset.planId;
      pendingPlanName = this.dataset.planName;
      pendingPrice    = this.dataset.price;
      pendingCycle    = this.dataset.cycle;

      modalTitle.textContent = 'Subscribe to ' + pendingPlanName;
      modalBody.textContent  =
        'You will be charged FCFA ' +
        Number(pendingPrice).toLocaleString() + ' per ' + pendingCycle +
        '. You can pause or cancel anytime.';

      modal.style.display = 'flex';
    });
  });

  /* Cancel */
  modalCancel.addEventListener('click', closeModal);
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

  function closeModal() {
    modal.style.display = 'none';
    pendingPlanId = null;
  }

  /* Confirm → POST to API */
  modalConfirm.addEventListener('click', async function () {
    if (!pendingPlanId) return;

    modalConfirm.disabled    = true;
    modalConfirm.textContent = 'Subscribing…';

    try {
      const res = await postJSON('../api/subscription-handler.php', {
        action:      'subscribe',
        template_id: pendingPlanId,
      });

      if (res.success) {
        closeModal();
        showToast('Subscribed to ' + pendingPlanName + '!', 'success');
        setTimeout(() => { window.location.href = 'manage-subscription.php'; }, 1400);
      } else {
        showToast(res.message || 'Could not subscribe. Please try again.', 'error');
        modalConfirm.disabled    = false;
        modalConfirm.textContent = 'Subscribe';
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'error');
      modalConfirm.disabled    = false;
      modalConfirm.textContent = 'Subscribe';
    }
  });
})();
</script>
</body>
</html>
