<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'doctor') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];
$patients = [];
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id=? LIMIT 1");
    $stmt->execute([$user['id']]); $doctorId = $stmt->fetchColumn();
    if (!$doctorId) { session_destroy(); header("Location: ../login.php"); exit(); }
    $stmt = $pdo->prepare("SELECT p.id patient_id, u.first_name, u.last_name, u.email, p.gender, p.blood_group, p.phone,
                                  COUNT(a.id) visits, MAX(a.appointment_date) last_visit
                           FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id
                           WHERE a.doctor_id=? GROUP BY p.id,u.first_name,u.last_name,u.email,p.gender,p.blood_group,p.phone
                           ORDER BY last_visit DESC");
    $stmt->execute([$doctorId]); $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $error = $e->getMessage(); }

$page = ['role'=>'doctor','active'=>'patients','title'=>'My Patients',
         'subtitle'=>'Patients you have attended to','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="panel">
  <div class="panel__head"><div><h3>Patient list</h3><p class="sub"><?= count($patients) ?> patients</p></div></div>
  <div class="panel__body flush">
    <?php if ($patients): ?>
    <table class="dtable">
      <thead><tr><th>Patient</th><th>Gender</th><th>Blood group</th><th>Visits</th><th>Last visit</th><th>Contact</th></tr></thead>
      <tbody>
      <?php foreach ($patients as $p): ?>
        <tr>
          <td><div class="who"><img src="<?= $e(ihms_avatar('patient',$p['patient_id'])) ?>" alt=""><div><b><?= $e($p['first_name'].' '.$p['last_name']) ?></b><span>Patient #<?= $e($p['patient_id']) ?></span></div></div></td>
          <td><?= $e($p['gender'] ?: '—') ?></td>
          <td><span class="pill is-active"><?= $e($p['blood_group'] ?: 'N/A') ?></span></td>
          <td><?= (int)$p['visits'] ?></td>
          <td><?= $p['last_visit'] ? date('M j, Y', strtotime($p['last_visit'])) : '—' ?></td>
          <td style="color:var(--text-muted)"><?= $e($p['phone'] ?: $p['email']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty"><i data-lucide="users"></i><p>No patients yet. They'll appear here once you have appointments.</p></div><?php endif; ?>
  </div>
</div>

<?php dash_foot($page); ?>
