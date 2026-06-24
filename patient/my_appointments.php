<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'patient') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];

$appointments = [];
$counts = ['all'=>0,'upcoming'=>0,'completed'=>0,'cancelled'=>0];
try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id=? LIMIT 1");
    $stmt->execute([$user['id']]); $pid = $stmt->fetchColumn();
    if ($pid) {
        $stmt = $pdo->prepare("SELECT a.id, a.appointment_date, a.status, a.reason,
                                      d.id doctor_id, d.specialization, d.consultation_fee, u.first_name, u.last_name
                               FROM appointments a JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
                               WHERE a.patient_id=? ORDER BY a.appointment_date DESC");
        $stmt->execute([$pid]); $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($appointments as $a) {
            $counts['all']++;
            if ($a['status']==='cancelled') $counts['cancelled']++;
            elseif ($a['status']==='completed') $counts['completed']++;
            elseif (strtotime($a['appointment_date'])>=time()) $counts['upcoming']++;
        }
    }
} catch (PDOException $e) { $error = $e->getMessage(); }

$page = ['role'=>'patient','active'=>'appointments','title'=>'My Appointments',
         'subtitle'=>'View and manage your appointments','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="panel">
  <div class="panel__head">
    <div class="seg" id="filters">
      <button class="active" data-filter="all">All <span class="muted">· <?= $counts['all'] ?></span></button>
      <button data-filter="upcoming">Upcoming · <?= $counts['upcoming'] ?></button>
      <button data-filter="completed">Completed · <?= $counts['completed'] ?></button>
      <button data-filter="cancelled">Cancelled · <?= $counts['cancelled'] ?></button>
    </div>
    <a class="btn btn--brand" href="book_appointment.php"><i data-lucide="calendar-plus"></i> Book new</a>
  </div>
  <div class="panel__body flush">
    <?php if ($appointments): ?>
      <div class="lst" id="apptList">
      <?php foreach ($appointments as $a):
        $isUpcoming = $a['status']!=='cancelled' && $a['status']!=='completed' && strtotime($a['appointment_date'])>=time();
        $cat = $a['status']==='cancelled' ? 'cancelled' : ($a['status']==='completed' ? 'completed' : ($isUpcoming ? 'upcoming' : 'completed'));
      ?>
        <div class="lst__item" data-cat="<?= $cat ?>">
          <img class="lst__avatar lst__avatar--ring" src="<?= $e(ihms_avatar('doctor',$a['doctor_id'])) ?>" alt="">
          <div class="lst__main">
            <b>Dr. <?= $e($a['first_name'].' '.$a['last_name']) ?></b>
            <span><i data-lucide="stethoscope"></i><?= $e($a['specialization']) ?> · <?= $e($a['reason'] ?: 'Consultation') ?></span>
          </div>
          <div class="lst__meta">
            <b><?= date('M j, Y', strtotime($a['appointment_date'])) ?></b>
            <span><?= date('g:i A', strtotime($a['appointment_date'])) ?></span>
          </div>
          <span class="pill is-<?= $e($a['status']) ?>" style="margin:0 1rem"><?= $e($a['status']) ?></span>
          <div style="display:flex;gap:.5rem">
            <a class="btn btn--ghost btn--sm" href="view_appointment.php?id=<?= (int)$a['id'] ?>" style="padding:.45rem .9rem"><i data-lucide="eye"></i></a>
            <?php if ($isUpcoming): ?>
              <a class="btn btn--ghost btn--sm" href="cancel_appointment.php?id=<?= (int)$a['id'] ?>" style="padding:.45rem .9rem;color:#c43b51;border-color:#f0c4cc" onclick="return confirm('Cancel this appointment?')"><i data-lucide="x"></i></a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty"><i data-lucide="calendar-x"></i><p>You have no appointments yet. <a href="book_appointment.php">Book your first visit →</a></p></div>
    <?php endif; ?>
  </div>
</div>

<?php
$js = "
var fbtns=document.querySelectorAll('#filters button');
fbtns.forEach(function(b){ b.addEventListener('click',function(){
  fbtns.forEach(function(x){x.classList.remove('active');}); b.classList.add('active');
  var f=b.getAttribute('data-filter');
  document.querySelectorAll('#apptList .lst__item').forEach(function(it){
    it.style.display=(f==='all'||it.getAttribute('data-cat')===f)?'':'none';
  });
});});
";
dash_foot($page, $js);
