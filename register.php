<?php
/**
 * ForkFresh – Customer Registration Page
 */
require_once __DIR__ . '/config/db.php';
startSession();

if (!empty($_SESSION['user_id'])) { redirect(BASE_URL . 'customer/dashboard.php'); }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name']  ?? '');
    $email = trim($_POST['email']      ?? '');
    $phone = trim($_POST['phone']      ?? '');
    $pass  = $_POST['password']        ?? '';
    $pass2 = $_POST['password2']       ?? '';

    if ($first === '' || $last === '' || $email === '' || $pass === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $db   = getDB();
        $chk  = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins  = $db->prepare('INSERT INTO users (first_name, last_name, email, phone, password_hash) VALUES (?,?,?,?,?)');
            $ins->execute([$first, $last, $email, $phone, $hash]);
            $newId = (int)$db->lastInsertId();

            $_SESSION['user_id']    = $newId;
            $_SESSION['user_name']  = $first . ' ' . $last;
            $_SESSION['user_email'] = $email;
            redirect(BASE_URL . 'customer/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register – ForkFresh</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root { --green:#2e7d32; --green-dark:#1b5e20; --orange:#f57c00; --border:#e0e0e0; --bg:#f4f4f4; --white:#ffffff; --radius:10px; --shadow:0 4px 20px rgba(0,0,0,.10); }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family:'Segoe UI',Arial,sans-serif; background:var(--bg); min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px; }
    .auth-card { background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow); width:100%; max-width:460px; padding:36px 32px; }
    .brand { text-align:center; margin-bottom:22px; }
    .brand a { text-decoration:none; }
    .brand-name { font-size:1.8rem; font-weight:900; color:var(--green); }
    .brand-name span { color:var(--orange); }
    .brand-tagline { font-size:.78rem; color:#888; margin-top:2px; }
    h2 { font-size:1.1rem; font-weight:700; margin-bottom:20px; text-align:center; color:#333; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-group { margin-bottom:14px; }
    label { display:block; font-size:.85rem; font-weight:600; margin-bottom:5px; color:#444; }
    input { width:100%; padding:11px 14px; border:1px solid var(--border); border-radius:8px; font-size:.92rem; outline:none; transition:border-color .2s; font-family:inherit; }
    input:focus { border-color:var(--green); }
    .password-wrap { position:relative; }
    .password-wrap input { padding-right:42px; }
    .toggle-pass { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#888; font-size:.9rem; }
    .btn-register { width:100%; padding:12px; background:var(--green); color:#fff; border:none; border-radius:8px; font-size:1rem; font-weight:700; cursor:pointer; margin-top:4px; transition:background .2s; }
    .btn-register:hover { background:var(--green-dark); }
    .alert-err { background:#fdecea; border:1px solid #f5c6cb; color:#c62828; border-radius:8px; padding:10px 14px; font-size:.88rem; margin-bottom:16px; }
    .login-link { text-align:center; margin-top:18px; font-size:.85rem; color:#666; }
    .login-link a { color:var(--green); font-weight:600; text-decoration:none; }
    .login-link a:hover { text-decoration:underline; }
    .back-home { text-align:center; margin-top:12px; font-size:.82rem; }
    .back-home a { color:#888; text-decoration:none; }
    .back-home a:hover { color:var(--green); }
    @media(max-width:480px){ .form-row { grid-template-columns:1fr; } }
  </style>
</head>
<body>
<div class="auth-card">
  <div class="brand">
    <a href="<?= BASE_URL ?>index.php">
      <div class="brand-name">fork<span>fresh</span></div>
    </a>
    <p class="brand-tagline">AFRICAN DELICACIES, DELIVERED FRESH</p>
  </div>

  <h2>Create a Customer Account</h2>

  <?php if ($error): ?>
    <div class="alert-err"><i class="fa fa-circle-exclamation"></i> <?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php" novalidate>
    <div class="form-row">
      <div class="form-group">
        <label for="first_name">First Name *</label>
        <input type="text" id="first_name" name="first_name"
               value="<?= e($_POST['first_name'] ?? '') ?>"
               placeholder="First name" required autocomplete="given-name">
      </div>
      <div class="form-group">
        <label for="last_name">Last Name *</label>
        <input type="text" id="last_name" name="last_name"
               value="<?= e($_POST['last_name'] ?? '') ?>"
               placeholder="Last name" required autocomplete="family-name">
      </div>
    </div>

    <div class="form-group">
      <label for="email">Email Address *</label>
      <input type="email" id="email" name="email"
             value="<?= e($_POST['email'] ?? '') ?>"
             placeholder="you@example.com" required autocomplete="email">
    </div>

    <div class="form-group">
      <label for="phone">Phone Number</label>
      <input type="tel" id="phone" name="phone"
             value="<?= e($_POST['phone'] ?? '') ?>"
             placeholder="+237 6XX XXX XXX" autocomplete="tel">
    </div>

    <div class="form-group">
      <label for="password">Password *</label>
      <div class="password-wrap">
        <input type="password" id="password" name="password"
               placeholder="Min 6 characters" required autocomplete="new-password">
        <button type="button" class="toggle-pass" onclick="toggle('password','eye1')" aria-label="Toggle">
          <i class="fa fa-eye" id="eye1"></i>
        </button>
      </div>
    </div>

    <div class="form-group">
      <label for="password2">Confirm Password *</label>
      <div class="password-wrap">
        <input type="password" id="password2" name="password2"
               placeholder="Repeat password" required autocomplete="new-password">
        <button type="button" class="toggle-pass" onclick="toggle('password2','eye2')" aria-label="Toggle">
          <i class="fa fa-eye" id="eye2"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn-register">Create Account</button>
  </form>

  <div class="login-link">
    Already have an account? <a href="login.php">Login here</a>
  </div>
  <div class="back-home">
    <a href="<?= BASE_URL ?>index.php"><i class="fa fa-arrow-left"></i> Back to Home</a>
  </div>
</div>
<script>
function toggle(inputId, iconId) {
  const inp  = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  if (inp.type === 'password') { inp.type = 'text';     icon.className = 'fa fa-eye-slash'; }
  else                         { inp.type = 'password'; icon.className = 'fa fa-eye'; }
}
</script>
</body>
</html>
