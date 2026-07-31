<?php
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Contact Us';

$ok = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $err = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)');
        $stmt->execute([$name, $email, $subject, $message]);
        $ok = 'Thank you! Your message has been sent.';
    }
}
require_once 'includes/header.php';
?>

<section class="page-banner" style="background:#fff8e1;padding:28px 0;">
  <div class="container">
    <h1 style="font-size:1.6rem;font-weight:800;">Contact Us</h1>
    <p style="color:#666;margin-top:4px;">We'd love to hear from you</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="contact-grid">
      <!-- Info -->
      <div class="contact-info">
        <h2 style="margin-bottom:8px;">Get in Touch</h2>
        <p style="color:#666;margin-bottom:22px;">We're available Mon–Sat, 8AM–8PM</p>
        <div class="info-row"><div class="ico">📞</div><div><h4>Phone</h4><p>+237 612 456 789</p></div></div>
        <div class="info-row"><div class="ico">✉️</div><div><h4>Email</h4><p>hello@forkfresh.cm</p></div></div>
        <div class="info-row"><div class="ico">📍</div><div><h4>Location</h4><p>Buea, Cameroon</p></div></div>
        <div class="info-row"><div class="ico">🕒</div><div><h4>Working Hours</h4><p>Mon – Sat: 8:00 AM – 8:00 PM</p></div></div>
      </div>

      <!-- Form -->
      <div class="form-box" style="border:1px solid #eee;border-radius:10px;padding:24px;">
        <h3 style="margin-bottom:18px;">Send us a message</h3>
        <?php if ($ok): ?><div class="alert alert-ok" style="background:#e8f5e9;color:#2e7d32;padding:10px 14px;border-radius:8px;margin-bottom:14px;"><?= e($ok) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-err" style="background:#fdecea;color:#c62828;padding:10px 14px;border-radius:8px;margin-bottom:14px;"><?= e($err) ?></div><?php endif; ?>
        <form method="POST" action="contact.php">
          <div class="form-group">
            <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">Your Name *</label>
            <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>"
                   style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;font-family:inherit;">
          </div>
          <div class="form-group" style="margin-top:12px;">
            <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">Email Address *</label>
            <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"
                   style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;font-family:inherit;">
          </div>
          <div class="form-group" style="margin-top:12px;">
            <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">Subject</label>
            <input type="text" name="subject" value="<?= e($_POST['subject'] ?? '') ?>"
                   style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;font-family:inherit;">
          </div>
          <div class="form-group" style="margin-top:12px;">
            <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:5px;">Message *</label>
            <textarea name="message" required rows="5"
                      style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:.9rem;font-family:inherit;resize:vertical;"><?= e($_POST['message'] ?? '') ?></textarea>
          </div>
          <button type="submit"
                  style="margin-top:14px;width:100%;padding:12px;background:#2e7d32;color:#fff;border:none;border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;">
            Send Message
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
