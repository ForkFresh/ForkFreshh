<?php
$pageTitle = "Contact";
$active = "contact";
require "db.php";

$ok = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = trim($_POST["name"] ?? "");
    $email   = trim($_POST["email"] ?? "");
    $subject = trim($_POST["subject"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if ($name === "" || $email === "" || $message === "") {
        $err = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address.";
    } else {
        $name    = mysqli_real_escape_string($conn, $name);
        $email   = mysqli_real_escape_string($conn, $email);
        $subject = mysqli_real_escape_string($conn, $subject);
        $message = mysqli_real_escape_string($conn, $message);

        $sql = "INSERT INTO contact_messages (name, email, subject, message)
                VALUES ('$name', '$email', '$subject', '$message')";

        if (mysqli_query($conn, $sql)) {
            $ok = "Thank you! Your message has been sent.";
        } else {
            $err = "Something went wrong. Please try again.";
        }
    }
}

require "header.php";
?>

<section class="page-banner">
    <div class="container">
        <h1>Contact</h1>
        <p>We'd love to hear from you</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <h2>Contact Us</h2>
                <p class="lead">We'd love to hear from you</p>

                <div class="info-row">
                    <div class="ico">📞</div>
                    <div>
                        <h4>Phone</h4>
                        <p>+237 612 456 789</p>
                    </div>
                </div>
                <div class="info-row">
                    <div class="ico">✉️</div>
                    <div>
                        <h4>Email</h4>
                        <p>hello@forkfresh.cm</p>
                    </div>
                </div>
                <div class="info-row">
                    <div class="ico">📍</div>
                    <div>
                        <h4>Location</h4>
                        <p>Buea, Cameroon</p>
                    </div>
                </div>
                <div class="info-row">
                    <div class="ico">🕒</div>
                    <div>
                        <h4>Working Hours</h4>
                        <p>Mon – Sat: 8:00 AM – 8:00 PM</p>
                    </div>
                </div>
            </div>

            <div class="form-box">
                <h3>Send us a message</h3>

                <?php if ($ok): ?><div class="alert alert-ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
                <?php if ($err): ?><div class="alert alert-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

                <form method="POST" action="contact.php">
                    <div class="form-group">
                        <label>Your Name *</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Message *</label>
                        <textarea name="message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-green" style="width:100%;">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require "footer.php"; ?>