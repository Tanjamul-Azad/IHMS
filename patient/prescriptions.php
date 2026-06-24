<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'patient') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];
$rows = [];
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id=? LIMIT 1");
    $stmt->execute([$user['id']]); $pid = $stmt->fetchColumn();
    if ($pid) {
        $stmt = $pdo->prepare("SELECT p.*, d.id doctor_id, d.specialization, u.first_name, u.last_name
                               FROM prescriptions p JOIN doctors d ON p.doctor_id=d.id JOIN users u ON d.user_id=u.id
                               WHERE p.patient_id=? ORDER BY p.prescription_date DESC, p.id DESC");
        $stmt->execute([$pid]); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) { $error = $e->getMessage(); }

$active = count(array_filter($rows, fn($r) => strtolower($r['status'])==='active'));
$page = ['role'=>'patient','active'=>'prescriptions','title'=>'Prescriptions',
         'subtitle'=>'Your active and past medications','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="kpis" style="grid-template-columns:repeat(3,1fr)">
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-green"><i data-lucide="pill"></i></div></div><div class="kpi__val"><?= $active ?></div><div class="kpi__label">Active</div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-brand"><i data-lucide="clipboard-list"></i></div></div><div class="kpi__val"><?= count($rows) ?></div><div class="kpi__label">Total prescriptions</div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-accent"><i data-lucide="user-round"></i></div></div><div class="kpi__val"><?= count(array_unique(array_column($rows,'doctor_id'))) ?></div><div class="kpi__label">Prescribing doctors</div></div>
</div>

<div class="panel">
  <div class="panel__head"><div><h3>All prescriptions</h3></div></div>
  <div class="panel__body flush">
    <?php if ($rows): ?>
    <table class="dtable">
      <thead><tr><th>Medication</th><th>Dosage</th><th>Instructions</th><th>Prescribed by</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><b style="font-family:var(--font-head)"><?= $e($r['medication']) ?></b></td>
          <td><?= $e($r['dosage'] ?: '—') ?></td>
          <td style="max-width:280px;color:var(--text-muted)"><?= $e($r['instructions'] ?: '—') ?></td>
          <td><div class="who"><img src="<?= $e(ihms_avatar('doctor',$r['doctor_id'])) ?>" alt=""><div><b>Dr. <?= $e($r['first_name'].' '.$r['last_name']) ?></b><span><?= $e($r['specialization']) ?></span></div></div></td>
          <td><?= date('M j, Y', strtotime($r['prescription_date'])) ?></td>
          <td><span class="pill is-<?= strtolower($e($r['status'])) ?>"><?= $e($r['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty"><i data-lucide="pill"></i><p>No prescriptions on record yet.</p></div><?php endif; ?>
  </div>
</div>

<?php dash_foot($page); ?>
