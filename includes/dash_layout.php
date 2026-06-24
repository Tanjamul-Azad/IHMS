<?php
/**
 * Reusable dashboard shell (sidebar + topbar) for patient / doctor / admin.
 * Usage from a page that is ONE folder deep (patient/, doctor/, admin/):
 *
 *   $page = ['role'=>'patient','active'=>'dashboard','title'=>'Dashboard',
 *            'subtitle'=>'...', 'user'=>$user, 'notif'=>3];
 *   require_once __DIR__ . '/../includes/dash_layout.php';
 *   dash_head($page);
 *      ...page content...
 *   dash_foot($page, $chartScript);
 */

/** Deterministic real photo for a user, from the bundled assets. */
function ihms_avatar(string $role, $id, string $base = '../'): string {
    $id = (int)$id;
    if ($role === 'doctor') return $base . 'assets/img/doctor-' . (($id % 4) + 1) . '.jpg';
    if ($role === 'patient') return $base . 'assets/img/patient-' . (($id % 3) + 1) . '.jpg';
    return $base . 'assets/img/doctor-2.jpg'; // admin / fallback
}

function ihms_nav(string $role): array {
    // Hrefs are app-root-relative (folder-qualified); dash_head() prefixes $base.
    switch ($role) {
        case 'doctor': return [
            'Overview' => [
                ['dashboard','Dashboard','home','doctor/doctor_dashboard.php'],
            ],
            'Care' => [
                ['appointments','Appointments','calendar-check','doctor/appointments.php'],
                ['requests','Requests','calendar-clock','doctor/manage_appointments.php'],
                ['patients','My Patients','users','doctor/patients.php'],
                ['prescriptions','Prescriptions','pill','doctor/prescriptions.php'],
                ['records','Medical Records','file-text','doctor/medical-records.php'],
                ['schedule','My Schedule','clock','doctor/schedule.php'],
            ],
            'More' => [
                ['blood','Blood Bank','droplet','blood_bank/index.php'],
                ['profile','My Profile','user','profile.php'],
            ],
        ];
        case 'admin': return [
            'Overview' => [
                ['dashboard','Dashboard','home','admin/dashboard.php'],
            ],
            'Management' => [
                ['doctors','Doctors','stethoscope','admin/manage_doctors.php'],
                ['patients','Patients','users','admin/manage_patients.php'],
                ['appointments','Appointments','calendar-check','admin/manage_appointments.php'],
                ['ambulances','Ambulances','ambulance','admin/manage_ambulances.php'],
            ],
            'System' => [
                ['reports','Reports','bar-chart-3','admin/reports.php'],
                ['settings','Settings','settings','admin/settings.php'],
            ],
        ];
        default: return [ // patient
            'Overview' => [
                ['dashboard','Dashboard','home','patient/patient_dashboard.php'],
            ],
            'Appointments' => [
                ['appointments','My Appointments','calendar-check','patient/my_appointments.php'],
                ['book','Book Appointment','calendar-plus','patient/book_appointment.php'],
                ['find','Find Doctors','stethoscope','patient/browse_doctors.php'],
            ],
            'My Health' => [
                ['records','Medical Records','file-text','patient/medical-records.php'],
                ['prescriptions','Prescriptions','pill','patient/prescriptions.php'],
                ['health','Health Log','activity','patient/health-log.php'],
                ['reports','Reports','clipboard-list','patient/reports.php'],
            ],
            'More' => [
                ['payments','Payments','credit-card','patient/payments.php'],
                ['notifications','Notifications','bell','patient/notifications.php'],
                ['blood','Blood Bank','droplet','blood_bank/index.php'],
                ['profile','Profile','user','profile.php'],
            ],
        ];
    }
}

function dash_head(array $p): void {
    $base   = $p['base'] ?? '../';
    $role   = $p['role'];
    $active = $p['active'] ?? '';
    $user   = $p['user'];
    $title  = $p['title'] ?? 'Dashboard';
    $sub    = $p['subtitle'] ?? '';
    $name   = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $avatar = ihms_avatar($role, $user['id'] ?? 0, $base);
    $roleLabel = ['patient'=>'Patient','doctor'=>'Doctor','admin'=>'Administrator'][$role] ?? $role;
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $e($title) ?> — IHMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <?php if (!empty($p['bootstrap'])): ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= $base ?>assets/css/theme.css">
  <link rel="stylesheet" href="<?= $base ?>assets/css/dashboard.css">
  <?php if (!empty($p['bootstrap'])): ?>
  <link rel="stylesheet" href="<?= $base ?>assets/css/bootstrap-bridge.css">
  <?php endif; ?>
  <?php foreach (($p['css'] ?? []) as $href): ?><link rel="stylesheet" href="<?= $base.$href ?>"><?php endforeach; ?>
</head>
<body class="dash">
<div class="side__backdrop" id="sideBackdrop"></div>
<aside class="side" id="side">
  <a class="side__brand" href="<?= $base . ($role==='admin' ? 'admin/dashboard.php' : ($role==='doctor' ? 'doctor/doctor_dashboard.php' : 'patient/patient_dashboard.php')) ?>">
    <span class="brand__mark"><i data-lucide="heart-pulse"></i></span>
    <span>IHMS<small><?= $e($roleLabel) ?> Portal</small></span>
  </a>
  <nav class="side__nav">
    <?php foreach (ihms_nav($role) as $group => $links): ?>
      <div class="side__label"><?= $e($group) ?></div>
      <?php foreach ($links as [$key,$label,$icon,$href]): ?>
        <a class="side__link <?= $key===$active ? 'is-active':'' ?>" href="<?= $e($base.$href) ?>">
          <i data-lucide="<?= $e($icon) ?>"></i><span><?= $e($label) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="side__user">
    <img src="<?= $e($avatar) ?>" alt="">
    <div><b><?= $e($name ?: 'User') ?></b><span><?= $e($roleLabel) ?></span></div>
    <a href="<?= $base ?>logout.php" title="Log out"><i data-lucide="log-out"></i></a>
  </div>
</aside>

<div class="dash__main">
  <header class="topbar">
    <button class="topbar__toggle" id="sideToggle" aria-label="Menu"><i data-lucide="menu"></i></button>
    <div class="topbar__title">
      <h1><?= $e($title) ?></h1>
      <?php if ($sub): ?><p><?= $e($sub) ?></p><?php endif; ?>
    </div>
    <div class="topbar__actions">
      <div class="search"><i data-lucide="search"></i><input type="text" placeholder="Search…"></div>
      <a class="icon-btn" href="<?= $role==='patient' ? $base.'patient/notifications.php' : '#' ?>" aria-label="Notifications">
        <i data-lucide="bell"></i><?php if (!empty($p['notif'])): ?><span class="dot"></span><?php endif; ?>
      </a>
      <div class="userchip">
        <img src="<?= $e($avatar) ?>" alt="">
        <div><b><?= $e($name ?: 'User') ?></b><span><?= $e($roleLabel) ?></span></div>
      </div>
    </div>
  </header>
  <main class="dash__content">
<?php
}

function dash_foot(array $p, string $chartScript = ''): void {
    ?>
  </main>
</div>
<?php if (!empty($p['bootstrap'])): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
<script src="<?= $p['base'] ?? '../' ?>assets/js/dashboard.js"></script>
<?php if ($chartScript): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
<?= $chartScript ?>
});
</script>
<?php endif; ?>
</body>
</html>
<?php
}
