<?php
require_once dirname(__DIR__) . '/config/db.php';
requireCustomer();

$userId = getCurrentUserId();
$pdo    = getDB();

/* ── Active subscription ── */
$subStmt = $pdo->prepare(
    'SELECT s.*, t.name AS template_name
     FROM subscriptions s
     LEFT JOIN meal_plan_templates t ON t.id = s.template_id
     WHERE s.user_id = ? AND s.status IN ("active","paused")
     ORDER BY s.created_at DESC LIMIT 1'
);
$subStmt->execute([$userId]);
$sub = $subStmt->fetch();

/* ── Stats ── */
$activeCount = 0;
$mealsWeek   = 0;
$totalSpent  = 0.00;
$startDate   = '—';
$nextBilling = '—';

if ($sub) {
    $activeCount = ($sub['status'] === 'active') ? 1 : 0;
    $mealsWeek   = (int)$sub['meals_per_day'] * (int)$sub['days_per_week'];
    $totalSpent  = (float)$sub['total_spent'];
    $startDate   = date('M j, Y', strtotime($sub['start_date']));
    $nextBilling = date('M j, Y', strtotime($sub['next_billing_date']));
}

/* ── Upcoming deliveries (next 5) ── */
$delStmt = $pdo->prepare(
    'SELECT * FROM deliveries
     WHERE user_id = ? AND status = "scheduled" AND delivery_date >= CURDATE()
     ORDER BY delivery_date ASC LIMIT 5'
);
$delStmt->execute([$userId]);
$deliveries = $delStmt->fetchAll();

/* mealsWeek is derived from the plan config (meals_per_day × days_per_week) */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Subscription – ForkFresh</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="app-wrapper">
  <?php include 'partials/sidebar.php'; ?>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu">
          <i class="fa fa-bars"></i>
        </button>
        <div style="display:flex;align-items:center;gap:10px;">
          <i class="fa fa-list" style="color:var(--text-mid);font-size:1.1rem;"></i>
          <div>
            <h1 style="font-size:1.2rem;font-weight:800;">Manage Meal Plan</h1>
            <p style="font-size:.82rem;color:var(--text-light);">Overview of your meal plan and activity</p>
          </div>
        </div>
      </div>
      <div class="topbar-right">
        <a href="meal-plans.php" style="font-size:.85rem;color:var(--green-main);font-weight:600;">
          <i class="fa fa-plus"></i> New Plan
        </a>
      </div>
    </header>

    <main class="page-body manage-sub-page" style="padding:20px 28px;">

      <?php if (!$sub): ?>
      <!-- No subscription state -->
      <div style="text-align:center;padding:60px 20px;">
        <i class="fa fa-bowl-food" style="font-size:3.5rem;color:var(--border);margin-bottom:16px;"></i>
        <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:8px;">No active subscription</h2>
        <p style="color:var(--text-light);font-size:.9rem;margin-bottom:24px;">
          Choose a meal plan to get started with daily deliveries.
        </p>
        <a href="meal-plans.php"
           style="background:var(--green-main);color:#fff;padding:12px 28px;
                  border-radius:8px;font-weight:700;font-size:.95rem;">
          Browse Meal Plans
        </a>
      </div>

      <?php else: ?>

      <!-- ── Stats Row ── -->
      <div class="sub-stats">
        <div class="stat-box">
          <div class="stat-label">Active Subscriptions</div>
          <div class="stat-value"><?= $activeCount ?></div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Start Delivery</div>
          <div class="stat-value"><?= h($startDate) ?></div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Meals this week</div>
          <div class="stat-value"><?= $mealsWeek ?></div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Total Spending</div>
          <div class="stat-value"><?= fcfa($totalSpent) ?></div>
        </div>
      </div>

      <!-- ── Body: Active Plan + Upcoming Deliveries ── -->
      <div class="sub-body" style="margin-top:6px;">

        <!-- Active Plan Card -->
        <div class="active-plan-card">
          <h2>Your Active Plan
            <span class="badge-<?= h($sub['status']) ?>" style="margin-left:8px;font-size:.75rem;">
              <?= ucfirst(h($sub['status'])) ?>
            </span>
          </h2>

          <p class="plan-title"><?= h($sub['plan_name']) ?></p>

          <div class="plan-detail-item">
            <i class="fa fa-utensils d-icon"></i>
            <?= (int)$sub['meals_per_day'] ?> meals per day
          </div>
          <div class="plan-detail-item">
            <i class="fa fa-calendar-days d-icon"></i>
            <?= (int)$sub['days_per_week'] ?> days a week
          </div>
          <div class="plan-detail-item">
            <i class="fa fa-bowl-rice d-icon"></i>
            <?= h($sub['meal_type']) ?>
          </div>
          <p class="plan-billing">
            Next Billing: <strong><?= h($nextBilling) ?></strong>
          </p>

          <div class="plan-action-btns">
            <!-- Pause / Resume -->
            <?php if ($sub['status'] === 'active'): ?>
            <button class="btn-pause" id="btnPauseResume"
                    data-sub-id="<?= (int)$sub['id'] ?>"
                    data-action="pause">
              <i class="fa fa-pause"></i> Pause Plan
            </button>
            <?php else: ?>
            <button class="btn-pause" id="btnPauseResume"
                    data-sub-id="<?= (int)$sub['id'] ?>"
                    data-action="resume">
              <i class="fa fa-play"></i> Resume Plan
            </button>
            <?php endif; ?>

            <!-- Change Plan → goes to meal-plans.php with change flag -->
            <a href="meal-plans.php?change=1" class="btn-change-plan">
              Change Plan
            </a>
          </div>

          <!-- Cancel link -->
          <div style="margin-top:14px;">
            <button id="btnCancel"
                    data-sub-id="<?= (int)$sub['id'] ?>"
                    style="background:none;border:none;color:#c0392b;
                           font-size:.83rem;cursor:pointer;text-decoration:underline;">
              Cancel subscription
            </button>
          </div>
        </div>

        <!-- Upcoming Deliveries -->
        <div class="deliveries-card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h2 style="margin-bottom:0;">Upcoming Deliveries</h2>
            <a href="deliveries.php" class="view-all-link">View all</a>
          </div>

          <?php if (empty($deliveries)): ?>
          <p style="font-size:.87rem;color:var(--text-light);text-align:center;padding:20px 0;">
            No upcoming deliveries scheduled yet.
          </p>
          <?php else: ?>
          <?php foreach ($deliveries as $del): ?>
          <div class="delivery-item">
            <div class="delivery-icon">
              <i class="fa fa-motorcycle"></i>
            </div>
            <div class="delivery-info">
              <div class="delivery-date">
                <?= date('M j, Y', strtotime($del['delivery_date'])) ?>
              </div>
              <div class="delivery-meal"><?= h($del['meal_description']) ?></div>
            </div>
            <div style="margin-left:auto;">
              <span style="font-size:.75rem;padding:2px 10px;border-radius:20px;
                           background:#e6f9e6;color:var(--green-dark);font-weight:600;">
                Scheduled
              </span>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>

        </div><!-- /deliveries-card -->
      </div><!-- /sub-body -->

      <?php endif; ?>

    </main>

    <?php include 'partials/footer.php'; ?>
  </div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- Cancel confirm modal -->
<div id="cancelModal" style="display:none;position:fixed;inset:0;
     background:rgba(0,0,0,.45);z-index:9000;
     align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:var(--radius-lg);padding:32px 28px;
              max-width:400px;width:90%;box-shadow:var(--shadow-md);">
    <h2 style="font-size:1.05rem;font-weight:800;margin-bottom:8px;">Cancel Subscription?</h2>
    <p style="font-size:.87rem;color:var(--text-mid);margin-bottom:22px;">
      This will cancel your active meal plan. You will no longer receive deliveries.
      This action cannot be undone.
    </p>
    <div style="display:flex;gap:12px;justify-content:flex-end;">
      <button id="cancelModalNo"
        style="padding:9px 20px;border:1.5px solid var(--border);
               border-radius:8px;background:#fff;font-size:.88rem;
               color:var(--text-mid);font-weight:600;cursor:pointer;">
        Keep Plan
      </button>
      <button id="cancelModalYes"
        style="padding:9px 20px;background:#c0392b;color:#fff;
               border:none;border-radius:8px;font-size:.88rem;
               font-weight:700;cursor:pointer;">
        Yes, Cancel
      </button>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="assets/js/app.js"></script>
<script>
/* ── Pause / Resume ── */
const btnPR = document.getElementById('btnPauseResume');
if (btnPR) {
  btnPR.addEventListener('click', async function () {
    const subId  = this.dataset.subId;
    const action = this.dataset.action;
    this.disabled = true;

    try {
      const res = await postJSON('../api/subscription-handler.php', { action, sub_id: subId });
      if (res.success) {
        showToast(res.message, 'success');
        setTimeout(() => location.reload(), 1200);
      } else {
        showToast(res.message || 'Action failed.', 'error');
        this.disabled = false;
      }
    } catch {
      showToast('Network error.', 'error');
      this.disabled = false;
    }
  });
}

/* ── Cancel subscription ── */
const cancelModal = document.getElementById('cancelModal');
const btnCancel   = document.getElementById('btnCancel');

btnCancel && btnCancel.addEventListener('click', () => {
  cancelModal.style.display = 'flex';
});

document.getElementById('cancelModalNo') &&
  document.getElementById('cancelModalNo').addEventListener('click', () => {
    cancelModal.style.display = 'none';
  });

document.getElementById('cancelModalYes') &&
  document.getElementById('cancelModalYes').addEventListener('click', async function () {
    const subId = document.getElementById('btnCancel').dataset.subId;
    this.disabled    = true;
    this.textContent = 'Cancelling…';

    try {
      const res = await postJSON('../api/subscription-handler.php',
                                 { action: 'cancel', sub_id: subId });
      if (res.success) {
        cancelModal.style.display = 'none';
        showToast('Subscription cancelled.', 'success');
        setTimeout(() => location.reload(), 1400);
      } else {
        showToast(res.message || 'Could not cancel.', 'error');
        this.disabled    = false;
        this.textContent = 'Yes, Cancel';
      }
    } catch {
      showToast('Network error.', 'error');
      this.disabled    = false;
      this.textContent = 'Yes, Cancel';
    }
  });

/* close cancel modal on backdrop */
cancelModal && cancelModal.addEventListener('click', e => {
  if (e.target === cancelModal) cancelModal.style.display = 'none';
});
</script>
</body>
</html>
