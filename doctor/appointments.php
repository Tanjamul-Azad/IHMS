<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'doctor') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];
$flash = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id=? LIMIT 1");
    $stmt->execute([$user['id']]); $doctorId = $stmt->fetchColumn();
    if (!$doctorId) { session_destroy(); header("Location: ../login.php"); exit(); }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['appt_id'], $_POST['new_status'])) {
        $allowed = ['confirmed','completed','cancelled','pending'];
        if (in_array($_POST['new_status'], $allowed)) {
            $pdo->prepare("UPDATE appointments SET status=? WHERE id=? AND doctor_id=?")
                ->execute([$_POST['new_status'], $_POST['appt_id'], $doctorId]);
            $flash = 'Appointment updated.';
        }
    }

    $stmt = $pdo->prepare("SELECT a.*, p.id patient_id, u.first_name, u.last_name
                           FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id
                           WHERE a.doctor_id=? ORDER BY a.appointment_date DESC");
    $stmt->execute([$doctorId]); $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $error = $e->getMessage(); $appointments = []; }

$counts = ['all'=>count($appointments),'upcoming'=>0,'completed'=>0,'cancelled'=>0];
foreach ($appointments as $a) {
    if ($a['status']==='cancelled') $counts['cancelled']++;
    elseif ($a['status']==='completed') $counts['completed']++;
    elseif (strtotime($a['appointment_date'])>=time()) $counts['upcoming']++;
}

$page = ['role'=>'doctor','active'=>'appointments','title'=>'Appointments',
         'subtitle'=>'Manage your patient appointments','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<?php if ($flash): ?><div class="alert alert--success"><i data-lucide="check-circle"></i><?= $e($flash) ?></div><?php endif; ?>

<div class="panel">
  <div class="panel__head">
    <div class="seg" id="filters">
      <button class="active" data-filter="all">All · <?= $counts['all'] ?></button>
      <button data-filter="upcoming">Upcoming · <?= $counts['upcoming'] ?></button>
      <button data-filter="completed">Completed · <?= $counts['completed'] ?></button>
      <button data-filter="cancelled">Cancelled · <?= $counts['cancelled'] ?></button>
    </div>
  </div>
  <div class="panel__body flush">
    <?php if ($appointments): ?>
      <div class="lst" id="apptList">
      <?php foreach ($appointments as $a):
        $cat = $a['status']==='cancelled' ? 'cancelled' : ($a['status']==='completed' ? 'completed' : (strtotime($a['appointment_date'])>=time() ? 'upcoming' : 'completed')); ?>
        <div class="lst__item" data-cat="<?= $cat ?>">
          <img class="lst__avatar lst__avatar--ring" src="<?= $e(ihms_avatar('patient',$a['patient_id'])) ?>" alt="">
          <div class="lst__main">
            <b><?= $e($a['first_name'].' '.$a['last_name']) ?></b>
            <span><i data-lucide="clipboard-list"></i><?= $e($a['reason'] ?: 'Consultation') ?></span>
          </div>
          <div class="lst__meta"><b><?= date('M j, Y', strtotime($a['appointment_date'])) ?></b><span><?= date('g:i A', strtotime($a['appointment_date'])) ?></span></div>
          <span class="pill is-<?= $e($a['status']) ?>" style="margin:0 1rem"><?= $e($a['status']) ?></span>
          <form method="POST" style="display:flex;gap:.4rem">
            <input type="hidden" name="appt_id" value="<?= (int)$a['id'] ?>">
            <?php if ($a['status']==='pending'): ?>
              <button class="btn btn--brand btn--sm" name="new_status" value="confirmed" style="padding:.4rem .8rem"><i data-lucide="check"></i></button>
            <?php endif; ?>
            <?php if (in_array($a['status'],['pending','confirmed'])): ?>
              <button class="btn btn--ghost btn--sm" name="new_status" value="completed" style="padding:.4rem .8rem" title="Mark completed"><i data-lucide="check-check"></i></button>
              <button class="btn btn--ghost btn--sm" name="new_status" value="cancelled" style="padding:.4rem .8rem;color:#c43b51;border-color:#f0c4cc" title="Cancel"><i data-lucide="x"></i></button>
            <?php endif; ?>
          </form>
        </div>
      <?php endforeach; ?>
      </div>
    <?php else: ?><div class="empty"><i data-lucide="calendar"></i><p>No appointments yet.</p></div><?php endif; ?>
  </div>
</div>

<?php
$js = "
var fbtns=document.querySelectorAll('#filters button');
fbtns.forEach(function(b){ b.addEventListener('click',function(){
  fbtns.forEach(function(x){x.classList.remove('active');}); b.classList.add('active');
  var f=b.getAttribute('data-filter');
  document.querySelectorAll('#apptList .lst__item').forEach(function(it){ it.style.display=(f==='all'||it.getAttribute('data-cat')===f)?'':'none'; });
});});
";
dash_foot($page, $js);
