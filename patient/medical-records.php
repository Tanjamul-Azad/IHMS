<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'patient') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];
$records = [];
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id=? LIMIT 1");
    $stmt->execute([$user['id']]); $pid = $stmt->fetchColumn();
    if ($pid) {
        $stmt = $pdo->prepare("SELECT mh.*, d.id doctor_id, d.specialization, u.first_name, u.last_name
                               FROM medical_history mh JOIN doctors d ON mh.doctor_id=d.id JOIN users u ON d.user_id=u.id
                               WHERE mh.patient_id=? ORDER BY COALESCE(mh.visit_date, DATE(mh.created_at)) DESC");
        $stmt->execute([$pid]); $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) { $error = $e->getMessage(); }

$page = ['role'=>'patient','active'=>'records','title'=>'Medical Records',
         'subtitle'=>'Your diagnoses, treatments and visit history','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<?php if ($records): ?>
  <div class="grid grid--1-1">
  <?php foreach ($records as $r): ?>
    <div class="panel">
      <div class="panel__head">
        <div style="display:flex;gap:.8rem;align-items:center">
          <img src="<?= $e(ihms_avatar('doctor',$r['doctor_id'])) ?>" alt="" style="width:46px;height:46px;border-radius:12px;object-fit:cover">
          <div><h3 style="font-size:1rem;margin:0">Dr. <?= $e($r['first_name'].' '.$r['last_name']) ?></h3>
          <p class="sub"><?= $e($r['specialization']) ?></p></div>
        </div>
        <span class="pill is-completed"><?= date('M j, Y', strtotime($r['visit_date'] ?: $r['created_at'])) ?></span>
      </div>
      <div class="panel__body">
        <div class="docard__meta" style="padding:0;gap:.8rem">
          <div style="align-items:flex-start"><i data-lucide="clipboard-list"></i><span><b style="color:var(--ink);font-family:var(--font-head)">Diagnosis:</b> <?= $e($r['diagnosis'] ?: '—') ?></span></div>
          <div style="align-items:flex-start"><i data-lucide="pill"></i><span><b style="color:var(--ink);font-family:var(--font-head)">Treatment:</b> <?= $e($r['treatment'] ?: '—') ?></span></div>
          <?php if (!empty($r['notes'])): ?><div style="align-items:flex-start"><i data-lucide="sticky-note"></i><span><b style="color:var(--ink);font-family:var(--font-head)">Notes:</b> <?= $e($r['notes']) ?></span></div><?php endif; ?>
          <?php if (!empty($r['next_visit_date'])): ?><div><i data-lucide="calendar-clock"></i><span>Next visit: <?= date('M j, Y', strtotime($r['next_visit_date'])) ?></span></div><?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="panel"><div class="empty"><i data-lucide="file-text"></i><p>No medical records yet. They'll appear here after your visits.</p></div></div>
<?php endif; ?>

<?php dash_foot($page); ?>
