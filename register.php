<?php
session_start();
require_once 'dbConnect.php';

if (isset($_SESSION['user'])) { header('Location: dashboard.php'); exit(); }
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — IHMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<div class="auth">
  <aside class="auth__brand">
    <span class="auth__orb auth__orb--1"></span>
    <span class="auth__orb auth__orb--2"></span>
    <a href="index.php" class="brand">
      <span class="brand__mark"><i data-lucide="heart-pulse"></i></span>
      <span>IHMS<small>Healthcare</small></span>
    </a>
    <div class="auth__hero">
      <h2>Join thousands managing health the smart way.</h2>
      <p>Create your free account to book appointments, track prescriptions, view records and connect with top doctors.</p>
      <ul class="auth__points">
        <li><span class="pt-ico"><i data-lucide="stethoscope"></i></span><div><b>Top specialists</b><span>Find and book trusted doctors.</span></div></li>
        <li><span class="pt-ico"><i data-lucide="file-text"></i></span><div><b>All records, one place</b><span>Prescriptions, history and reports.</span></div></li>
        <li><span class="pt-ico"><i data-lucide="shield-check"></i></span><div><b>Private &amp; secure</b><span>Your data stays protected.</span></div></li>
      </ul>
    </div>
    <blockquote class="auth__quote">"Signing up took a minute and saved me hours."<span>— James C., verified patient</span></blockquote>
  </aside>

  <main class="auth__main">
    <div class="auth__card">
      <a href="index.php" class="auth__back"><i data-lucide="arrow-left"></i> Back to home</a>
      <h1>Create account</h1>
      <p class="sub">Get started with your IHMS healthcare account.</p>

      <?php if (!empty($errors['user_exist'])): ?>
        <div class="alert alert--error"><i data-lucide="alert-circle"></i> <?= $e($errors['user_exist']) ?></div>
      <?php endif; ?>

      <form method="POST" action="user-account.php" novalidate>
        <div class="form-row">
          <div class="field">
            <label for="first_name">First name</label>
            <div class="input-wrap"><i data-lucide="user"></i>
              <input class="input" type="text" id="first_name" name="first_name" placeholder="Jane" value="<?= $e($old['first_name'] ?? '') ?>" required></div>
            <?php if (!empty($errors['first_name'])): ?><small style="color:#b3261e"><?= $e($errors['first_name']) ?></small><?php endif; ?>
          </div>
          <div class="field">
            <label for="last_name">Last name</label>
            <div class="input-wrap"><i data-lucide="user"></i>
              <input class="input" type="text" id="last_name" name="last_name" placeholder="Doe" value="<?= $e($old['last_name'] ?? '') ?>" required></div>
            <?php if (!empty($errors['last_name'])): ?><small style="color:#b3261e"><?= $e($errors['last_name']) ?></small><?php endif; ?>
          </div>
        </div>

        <div class="field">
          <label for="email">Email address</label>
          <div class="input-wrap"><i data-lucide="mail"></i>
            <input class="input" type="email" id="email" name="email" placeholder="you@example.com" value="<?= $e($old['email'] ?? '') ?>" required></div>
          <?php if (!empty($errors['email'])): ?><small style="color:#b3261e"><?= $e($errors['email']) ?></small><?php endif; ?>
        </div>

        <div class="form-row">
          <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap"><i data-lucide="lock"></i>
              <input class="input" type="password" id="password" name="password" placeholder="Create a password" required>
              <button type="button" class="input-toggle" data-target="password" aria-label="Show"><i data-lucide="eye"></i></button></div>
            <?php if (!empty($errors['password'])): ?><small style="color:#b3261e"><?= $e($errors['password']) ?></small><?php endif; ?>
          </div>
          <div class="field">
            <label for="confirm_password">Confirm password</label>
            <div class="input-wrap"><i data-lucide="lock"></i>
              <input class="input" type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required></div>
            <?php if (!empty($errors['confirm_password'])): ?><small style="color:#b3261e"><?= $e($errors['confirm_password']) ?></small><?php endif; ?>
          </div>
        </div>

        <div class="field">
          <label for="role">I am registering as</label>
          <div class="input-wrap"><i data-lucide="users"></i>
            <select class="input" id="role" name="role" required>
              <option value="patient" <?= ($old['role'] ?? '')==='patient'?'selected':'' ?>>Patient</option>
              <option value="doctor" <?= ($old['role'] ?? '')==='doctor'?'selected':'' ?>>Doctor</option>
            </select>
          </div>
        </div>

        <button type="submit" name="signup" value="1" class="btn btn--brand btn--block btn--lg"><i data-lucide="user-plus"></i> Create account</button>
      </form>

      <p class="auth__alt">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
  </main>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  if (window.lucide) lucide.createIcons();
  document.querySelectorAll('.input-toggle').forEach(function(btn){
    btn.addEventListener('click', function(){
      var inp = document.getElementById(btn.dataset.target);
      var show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.innerHTML = '<i data-lucide="' + (show ? 'eye-off':'eye') + '"></i>';
      if (window.lucide) lucide.createIcons();
    });
  });
</script>
</body>
</html>
