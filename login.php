<?php
/**
 * ForkFresh – Unified Login Page
 * Handles three roles: customer, rider, admin
 */
require_once __DIR__ . '/config/db.php';
startSession();

// If already logged in, redirect to the right dashboard
if (!empty($_SESSION['user_id']))  { redirect(BASE_URL . 'customer/dashboard.php'); }
if (!empty($_SESSION['rider_id'])) { redirect(BASE_URL . 'rider/dashboard.php'); }
if (!empty($_SESSION['admin_id'])) { redirect(BASE_URL . 'admin/dashboard.php'); }

$role  = $_GET['role'] ?? 'customer';   // customer | rider | admin
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role  = $_POST['role']  ?? 'customer';
    $email = trim($_POST['email']  ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {
        $error = 'Please enter your email and password.';
    } else {
        $db = getDB();

        if ($role === 'customer') {
            $stmt = $db->prepare('SELECT id, first_name, last_name, password_hash, is_active FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && $user['is_active'] && password_verify($pass, $user['password_hash'])) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $email;
                redirect(BASE_URL . 'customer/dashboard.php');
            } else {
                $error = 'Invalid email or password.';
            }

        } elseif ($role === 'rider') {
            $stmt = $db->prepare('SELECT id, name, password_hash, is_active FROM riders WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $rider = $stmt->fetch();
            if ($rider && $rider['is_active'] && password_verify($pass, $rider['password_hash'])) {
                $_SESSION['rider_id']   = $rider['id'];
                $_SESSION['rider_name'] = $rider['name'];
                redirect(BASE_URL . 'rider/dashboard.php');
            } else {
                $error = 'Invalid email or password.';
            }

        } elseif ($role === 'admin') {
            $stmt = $db->prepare('SELECT id, name, password_hash, is_active FROM admins WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            if ($admin && $admin['is_active'] && password_verify($pass, $admin['password_hash'])) {
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                redirect(BASE_URL . 'admin/dashboard.php');
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

$roleTitles = ['customer' => 'Customer', 'rider' => 'Rider / Driver', 'admin' => 'Admin'];
$pageRole   = $roleTitles[$role] ?? 'Customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – ForkFresh</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --green:      #2e7d32;
      --green-dark: #1b5e20;
      --orange:     #f57c00;
      --border:     #e0e0e0;
      --bg:         #f4f4f4;
      --white:      #ffffff;
      --radius:     10px;
      --shadow:     0 4px 20px rgba(0,0,0,.10);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .auth-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 420px;
      padding: 36px 32px;
    }
    .brand { text-align: center; margin-bottom: 24px; }
    .brand a { text-decoration: none; }
    .brand-name { font-size: 1.8rem; font-weight: 900; color: var(--green); }
    .brand-name span { color: var(--orange); }
    .brand-tagline { font-size: .78rem; color: #888; margin-top: 2px; }
    h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; text-align: center; color: #333; }

    /* Role tabs */
    .role-tabs { display: flex; gap: 0; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; margin-bottom: 24px; }
    .role-tab {
      flex: 1; padding: 10px 4px; text-align: center;
      font-size: .82rem; font-weight: 600; color: #666;
      text-decoration: none; background: #fafafa;
      border-right: 1px solid var(--border);
      transition: background .2s, color .2s;
    }
    .role-tab:last-child { border-right: none; }
    .role-tab.active { background: var(--green); color: #fff; }
    .role-tab:hover:not(.active) { background: #f0f7f0; color: var(--green); }

    .form-group { margin-bottom: 16px; }
    label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 6px; color: #444; }
    input[type="email"], input[type="password"] {
      width: 100%; padding: 11px 14px;
      border: 1px solid var(--border); border-radius: 8px;
      font-size: .92rem; outline: none;
      transition: border-color .2s;
    }
    input:focus { border-color: var(--green); }
    .password-wrap { position: relative; }
    .password-wrap input { padding-right: 42px; }
    .toggle-pass {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: #888; font-size: .9rem;
    }

    .btn-login {
      width: 100%; padding: 12px;
      background: var(--green); color: #fff;
      border: none; border-radius: 8px;
      font-size: 1rem; font-weight: 700;
      cursor: pointer; margin-top: 4px;
      transition: background .2s;
    }
    .btn-login:hover { background: var(--green-dark); }

    .alert-err {
      background: #fdecea; border: 1px solid #f5c6cb;
      color: #c62828; border-radius: 8px;
      padding: 10px 14px; font-size: .88rem; margin-bottom: 18px;
    }
    .register-link { text-align: center; margin-top: 20px; font-size: .85rem; color: #666; }
    .register-link a { color: var(--green); font-weight: 600; text-decoration: none; }
    .register-link a:hover { text-decoration: underline; }
    .back-home { text-align: center; margin-top: 14px; font-size: .82rem; }
    .back-home a { color: #888; text-decoration: none; }
    .back-home a:hover { color: var(--green); }
.logo img{
    width:190px;
}
  </style>
</head>
<body>
<div class="auth-card">
  <div class="brand">
     <div class="logo">
      <a href="<?= BASE_URL ?>index.php">
        <img src="<?= BASE_URL ?>assets/images/IMG_8023.PNG" alt="ForkFresh" onerror="this.outerHTML='<span style=\'font-weight:900;font-size:1.3rem;color:#2e7d32\'>fork<span style=\'color:#f57c00\'>fresh</span></span>'">
      </a>
    </div>
    <p class="brand-tagline">AFRICAN DELICACIES, DELIVERED FRESH</p>
  </div>

  <!-- Role switcher tabs -->
  <div class="role-tabs" role="tablist">
    <a href="login.php?role=customer" class="role-tab <?= $role === 'customer' ? 'active' : '' ?>" role="tab">
      <i class="fa fa-user"></i> Customer
    </a>
    <a href="login.php?role=rider" class="role-tab <?= $role === 'rider' ? 'active' : '' ?>" role="tab">
      <i class="fa fa-motorcycle"></i> Rider
    </a>
    <a href="login.php?role=admin" class="role-tab <?= $role === 'admin' ? 'active' : '' ?>" role="tab">
      <i class="fa fa-shield-halved"></i> 
    </a>
  </div>

  <h2>Sign in as <?= e($pageRole) ?></h2>

  <?php if ($error): ?>
    <div class="alert-err"><i class="fa fa-circle-exclamation"></i> <?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php" novalidate>
    <input type="hidden" name="role" value="<?= e($role) ?>">

    <div class="form-group">
      <label for="email">Email Address</label>
      <input type="email" id="email" name="email"
             value="<?= e($_POST['email'] ?? '') ?>"
             placeholder="you@example.com" required autocomplete="email">
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <div class="password-wrap">
        <input type="password" id="password" name="password"
               placeholder="••••••••" required autocomplete="current-password">
        <button type="button" class="toggle-pass" onclick="togglePass()" aria-label="Toggle password">
          <i class="fa fa-eye" id="eyeIcon"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn-login">Login</button>
  </form>

  <?php if ($role === 'customer'): ?>
  <div class="register-link">
    Don't have an account? <a href="register.php">Register here</a>
  </div>
  <?php endif; ?>

  <div class="back-home">
    <a href="<?= BASE_URL ?>index.php"><i class="fa fa-arrow-left"></i> Back to Home</a>
  </div>
</div>

<script>
function togglePass() {
  const input = document.getElementById('password');
  const icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fa fa-eye';
  }
}
</script>
</body>
</html>
