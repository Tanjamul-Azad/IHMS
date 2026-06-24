<?php
session_start();

// Already logged in? Go to the right dashboard.
if (isset($_SESSION['user_id']) && isset($_SESSION['user']['role'])) {
    switch ($_SESSION['user']['role']) {
        case 'admin':   header("Location: admin/dashboard.php"); exit();
        case 'doctor':  header("Location: doctor/doctor_dashboard.php"); exit();
        case 'patient': header("Location: patient/patient_dashboard.php"); exit();
    }
}

$error  = null;
$notice = null;
if (isset($_GET['timeout']))   { $notice = "Your session expired. Please sign in again."; }
if (isset($_GET['registered'])) {
    $notice = $_SESSION['registration_success'] ?? "Registration successful! Please sign in.";
    unset($_SESSION['registration_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        // Connect defensively so a DB outage shows a friendly message
        // instead of a raw fatal error screen.
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';
        $dbName = getenv('DB_NAME') ?: 'healthcare_db';
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbName;charset=utf8mb4",
            $dbUser, $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = [
                'id' => $user['id'], 'first_name' => $user['first_name'],
                'last_name' => $user['last_name'], 'email' => $user['email'],
                'role' => $user['role']
            ];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['role']      = $user['role']; // some pages read this key
            $_SESSION['last_activity'] = time();

            // Resolve the role-specific profile id used across the app.
            if ($user['role'] === 'patient') {
                $ps = $pdo->prepare("SELECT id FROM patients WHERE user_id = ? LIMIT 1");
                $ps->execute([$user['id']]);
                if ($pid = $ps->fetchColumn()) { $_SESSION['patient_id'] = $pid; }
            } elseif ($user['role'] === 'doctor') {
                $ds = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
                $ds->execute([$user['id']]);
                if ($did = $ds->fetchColumn()) { $_SESSION['doctor_id'] = $did; }
            }
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            switch ($user['role']) {
                case 'admin':   header("Location: admin/dashboard.php"); break;
                case 'doctor':  header("Location: doctor/doctor_dashboard.php"); break;
                case 'patient': header("Location: patient/patient_dashboard.php"); break;
                default: $error = "Your account has an invalid role."; session_destroy();
            }
            if (!$error) exit();
        } else {
            $error = "Invalid email or password. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "We couldn't sign you in right now. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — IHMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="auth">

  <!-- Brand panel -->
  <aside class="auth__brand">
    <span class="auth__orb auth__orb--1"></span>
    <span class="auth__orb auth__orb--2"></span>

    <a href="index.php" class="brand">
      <span class="brand__mark"><i data-lucide="heart-pulse"></i></span>
      <span>IHMS<small>Healthcare</small></span>
    </a>

    <div class="auth__hero">
      <h2>Welcome back to better healthcare.</h2>
      <p>Sign in to manage appointments, view medical records and stay connected with your care team — all in one secure place.</p>
      <ul class="auth__points">
        <li>
          <span class="pt-ico"><i data-lucide="calendar-check"></i></span>
          <div><b>Instant appointments</b><span>Book and manage visits in seconds.</span></div>
        </li>
        <li>
          <span class="pt-ico"><i data-lucide="shield-check"></i></span>
          <div><b>Private &amp; secure</b><span>Your records are encrypted end-to-end.</span></div>
        </li>
        <li>
          <span class="pt-ico"><i data-lucide="clock"></i></span>
          <div><b>Care, around the clock</b><span>Access your health information anytime.</span></div>
        </li>
      </ul>
    </div>

    <blockquote class="auth__quote">
      "IHMS made managing my family's healthcare effortless."
      <span>— Sophia M., verified patient</span>
    </blockquote>
  </aside>

  <!-- Form panel -->
  <main class="auth__main">
    <div class="auth__card">
      <a href="index.php" class="auth__back"><i data-lucide="arrow-left"></i> Back to home</a>
      <h1>Sign in</h1>
      <p class="sub">Enter your details to access your dashboard.</p>

      <?php if ($notice): ?>
        <div class="alert alert--info"><i data-lucide="info"></i> <?= htmlspecialchars($notice) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert--error"><i data-lucide="alert-circle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>
        <div class="field">
          <label for="email">Email address</label>
          <div class="input-wrap">
            <i data-lucide="mail"></i>
            <input class="input" type="email" id="email" name="email" placeholder="you@example.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <i data-lucide="lock"></i>
            <input class="input" type="password" id="password" name="password" placeholder="Enter your password" required>
            <button type="button" class="input-toggle" id="pwToggle" aria-label="Show password"><i data-lucide="eye"></i></button>
          </div>
        </div>

        <div class="auth__row">
          <label><input type="checkbox" name="remember"> Remember me</label>
          <a href="recover-password.php">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn--brand btn--block btn--lg"><i data-lucide="log-in"></i> Sign in</button>
      </form>

      <div class="auth__divider">or continue with</div>
      <div class="auth__social">
        <button type="button" class="btn-social"><i data-lucide="chrome" class="g"></i> Google</button>
        <button type="button" class="btn-social"><i data-lucide="facebook" class="f"></i> Facebook</button>
      </div>

      <p class="auth__alt">Don't have an account? <a href="register.php">Create one</a></p>

      <div class="demo-note">
        <b>Demo admin:</b> sampleadmin@gmail.com &nbsp;/&nbsp; 1234
      </div>
    </div>
  </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  if (window.lucide) lucide.createIcons();
  (function () {
    var btn = document.getElementById('pwToggle');
    var pw  = document.getElementById('password');
    if (btn && pw) btn.addEventListener('click', function () {
      var show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      btn.innerHTML = '<i data-lucide="' + (show ? 'eye-off' : 'eye') + '"></i>';
      if (window.lucide) lucide.createIcons();
    });
  })();
</script>
</body>
</html>
