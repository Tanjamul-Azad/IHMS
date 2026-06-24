<?php
session_start();
require_once 'db_connect.php';

$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_doctor = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'doctor';
$is_patient = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'patient';

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM blood_donors");
    $total_donors = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) { $total_donors = 0; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank Management System — IHMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navigation.php'; ?>

<div class="container">
  <div class="bb-hero">
    <div class="bb-hero__badge">🩸 Save lives today</div>
    <h1>Blood Bank Management System</h1>
    <p>Connecting blood donors with those in need — simple, secure and accessible for everyone. Whether you're a donor, a patient, or an administrator, this platform helps save lives.</p>
    <div class="bb-stat">Total Registered Donors: <b><?php echo $total_donors > 0 ? $total_donors : 'Coming soon'; ?></b></div>
    <div class="bb-actions">
      <a href="add_donor.php" class="btn">➕ Become a donor</a>
      <a href="add_request.php" class="btn btn-light">🆘 Request blood</a>
    </div>
  </div>

  <div class="bb-roles">
    <div class="bb-role"><b>Donors</b><span>Register to be listed and help save lives.</span></div>
    <div class="bb-role"><b>Patients</b><span>Search for donors and send blood requests easily.</span></div>
    <div class="bb-role"><b>Admins</b><span>Manage donor records and review all requests.</span></div>
  </div>

  <div class="requests-section">
    <h2 style="text-align:center;color:var(--brand);">Blood Group Compatibility Chart</h2>
    <p style="text-align:center;color:var(--text-muted);margin-bottom:1.2rem;">See which blood groups can donate or receive blood from each other.</p>
    <table>
      <thead><tr><th>Blood Group</th><th>Can Donate To</th><th>Can Receive From</th></tr></thead>
      <tbody>
        <?php
        $rows = [
          ['O-','#e53935','All groups','O-'], ['O+','#e53935','O+, A+, B+, AB+','O-, O+'],
          ['A-','#1976d2','A-, A+, AB-, AB+','O-, A-'], ['A+','#1976d2','A+, AB+','O-, O+, A-, A+'],
          ['B-','#388e3c','B-, B+, AB-, AB+','O-, B-'], ['B+','#388e3c','B+, AB+','O-, O+, B-, B+'],
          ['AB-','#ab47bc','AB-, AB+','O-, A-, B-, AB-'], ['AB+','#ab47bc','AB+','All groups'],
        ];
        foreach ($rows as $r): ?>
          <tr><td style="font-weight:700;color:<?= $r[1] ?>"><?= $r[0] ?></td><td><?= $r[2] ?></td><td><?= $r[3] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:1rem;text-align:center;color:var(--text-muted);font-size:.9rem;">Universal Donor: <b>O-</b> &nbsp;|&nbsp; Universal Recipient: <b>AB+</b></div>
  </div>
</div>

<style>
  .bb-hero { background: var(--grad-deep); color:#fff; border-radius: var(--r-xl); padding: clamp(2rem,5vw,3.2rem); text-align:center; box-shadow: var(--shadow-lg); position:relative; overflow:hidden; margin-top: 2rem; }
  .bb-hero::after { content:""; position:absolute; inset:0; background: var(--grad-mesh); opacity:.5; }
  .bb-hero > * { position: relative; z-index:1; }
  .bb-hero__badge { display:inline-block; background: rgba(255,255,255,.14); color:#fff; font-family: var(--font-head); font-weight:700; font-size:.8rem; padding:.4rem .9rem; border-radius:99px; margin-bottom:1rem; }
  .bb-hero h1 { color:#fff; font-size: clamp(1.8rem,4vw,2.6rem); margin:0 0 .6rem; }
  .bb-hero p { color: rgba(255,255,255,.9); max-width: 640px; margin: 0 auto 1.4rem; font-size:1.05rem; }
  .bb-stat { display:inline-block; background: rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18); padding:.7rem 1.4rem; border-radius:99px; font-size:1.05rem; }
  .bb-stat b { color:#fff; }
  .bb-actions { display:flex; gap:.8rem; justify-content:center; margin-top:1.4rem; flex-wrap:wrap; }
  .bb-hero .btn-light { background:#fff; color: var(--brand); }
  .bb-roles { display:grid; grid-template-columns:repeat(3,1fr); gap:1.2rem; margin:1.6rem 0; }
  .bb-role { background:#fff; border:1px solid var(--mist); border-radius: var(--r-lg); padding:1.4rem; box-shadow: var(--shadow-sm); }
  .bb-role b { font-family: var(--font-head); color: var(--ink); display:block; margin-bottom:.3rem; }
  .bb-role span { color: var(--text-muted); font-size:.92rem; }
  @media (max-width:760px){ .bb-roles { grid-template-columns:1fr; } }
</style>
</body>
</html>
