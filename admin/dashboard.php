<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];

function monthly(PDO $pdo, string $col, string $table, string $where = '1', array $params = []): array {
    $months = [];
    for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("-$i months"))] = 0;
    $stmt = $pdo->prepare("SELECT DATE_FORMAT($col,'%Y-%m') ym, COUNT(*) c FROM $table WHERE $where GROUP BY ym");
    $stmt->execute($params);
    foreach ($stmt as $r) if (isset($months[$r['ym']])) $months[$r['ym']] = (int)$r['c'];
    return array_values($months);
}

$stats = ['patients'=>0,'doctors'=>0,'appts'=>0,'revenue'=>0];
$monthLabels = $monthAppts = $statusLabels = $statusData = [];
$topDoctors = $recentAppts = [];
$spPat = $spDoc = $spAppt = $spRev = [];

try {
    $stats['patients'] = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $stats['doctors']  = (int)$pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
    $stats['appts']    = (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    $stats['revenue']  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'")->fetchColumn();

    for ($i = 5; $i >= 0; $i--) $monthLabels[] = date('M', strtotime("-$i months"));
    $monthAppts = monthly($pdo, 'appointment_date', 'appointments');

    foreach ($pdo->query("SELECT status, COUNT(*) c FROM appointments GROUP BY status") as $r) {
        $statusLabels[] = ucfirst($r['status']); $statusData[] = (int)$r['c'];
    }

    $topDoctors = $pdo->query("SELECT d.id, u.first_name, u.last_name, d.specialization, d.rating, COUNT(a.id) appts
                               FROM doctors d JOIN users u ON d.user_id=u.id
                               LEFT JOIN appointments a ON a.doctor_id=d.id
                               GROUP BY d.id, u.first_name, u.last_name, d.specialization, d.rating
                               ORDER BY appts DESC, d.rating DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    $recentAppts = $pdo->query("SELECT a.id, a.appointment_date, a.status, a.reason,
                                       p.id patient_id, pu.first_name pf, pu.last_name pl,
                                       d.id doctor_id, du.first_name df, du.last_name dl, d.specialization
                                FROM appointments a
                                JOIN patients p ON a.patient_id=p.id JOIN users pu ON p.user_id=pu.id
                                JOIN doctors d ON a.doctor_id=d.id JOIN users du ON d.user_id=du.id
                                ORDER BY a.created_at DESC, a.id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

    $spPat  = monthly($pdo, 'created_at', 'patients');
    $spDoc  = monthly($pdo, 'created_at', 'doctors');
    $spAppt = $monthAppts;
    $spRev  = array_map('intval', array_values((function($pdo){
        $m=[]; for($i=5;$i>=0;$i--) $m[date('Y-m',strtotime("-$i months"))]=0;
        foreach($pdo->query("SELECT DATE_FORMAT(payment_date,'%Y-%m') ym, SUM(amount) s FROM payments WHERE status='completed' GROUP BY ym") as $r)
            if(isset($m[$r['ym']])) $m[$r['ym']]=(int)$r['s'];
        return $m;
    })($pdo)));
} catch (PDOException $e) { $error = $e->getMessage(); }

$page = ['role'=>'admin','active'=>'dashboard','title'=>'Admin Dashboard',
         'subtitle'=>'System overview & analytics','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="welcome">
  <div class="welcome__text">
    <h2>System overview</h2>
    <p>Monitor patients, doctors, appointments and revenue across the IHMS network.</p>
  </div>
  <a href="reports.php" class="btn"><i data-lucide="bar-chart-3"></i> Full reports</a>
</div>

<div class="kpis">
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-brand"><i data-lucide="users"></i></div></div>
    <div class="kpi__val"><?= number_format($stats['patients']) ?></div><div class="kpi__label">Total patients</div><div class="kpi__spark" id="sp1"></div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-accent"><i data-lucide="stethoscope"></i></div></div>
    <div class="kpi__val"><?= number_format($stats['doctors']) ?></div><div class="kpi__label">Doctors</div><div class="kpi__spark" id="sp2"></div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-violet"><i data-lucide="calendar-check"></i></div></div>
    <div class="kpi__val"><?= number_format($stats['appts']) ?></div><div class="kpi__label">Appointments</div><div class="kpi__spark" id="sp3"></div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-green"><i data-lucide="dollar-sign"></i></div>
    <span class="kpi__delta up"><i data-lucide="trending-up"></i> paid</span></div>
    <div class="kpi__val">$<?= number_format($stats['revenue']) ?></div><div class="kpi__label">Revenue collected</div><div class="kpi__spark" id="sp4"></div></div>
</div>

<div class="grid grid--2-1 mb">
  <div class="panel">
    <div class="panel__head"><div><h3>Appointments trend</h3><p class="sub">Last 6 months</p></div></div>
    <div class="panel__body"><div id="trend" class="chart-wrap"></div></div>
  </div>
  <div class="panel">
    <div class="panel__head"><div><h3>Appointment status</h3></div></div>
    <div class="panel__body">
      <?php if (array_sum($statusData)): ?><div id="statusDonut" class="chart-wrap"></div>
      <?php else: ?><div class="empty"><i data-lucide="calendar"></i><p>No data.</p></div><?php endif; ?>
    </div>
  </div>
</div>

<div class="grid grid--2-1 mb">
  <div class="panel">
    <div class="panel__head"><div><h3>Top doctors</h3><p class="sub">By appointment volume</p></div>
      <a class="link" href="manage_doctors.php">Manage <i data-lucide="chevron-right"></i></a></div>
    <div class="panel__body flush">
      <div class="lst">
      <?php foreach ($topDoctors as $d): ?>
        <div class="lst__item">
          <img class="lst__avatar lst__avatar--ring" src="<?= $e(ihms_avatar('doctor',$d['id'])) ?>" alt="">
          <div class="lst__main">
            <b>Dr. <?= $e($d['first_name'].' '.$d['last_name']) ?></b>
            <span><i data-lucide="stethoscope"></i><?= $e($d['specialization']) ?></span>
          </div>
          <div class="lst__meta"><b><?= (int)$d['appts'] ?> visits</b>
            <span><i data-lucide="star" style="width:13px;height:13px;display:inline"></i> <?= $d['rating'] ? number_format($d['rating'],1) : '—' ?></span></div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="panel">
    <div class="panel__head"><div><h3>Quick actions</h3></div></div>
    <div class="panel__body">
      <div class="qa-grid">
        <a class="qa" href="manage_doctors.php?action=add"><span class="qa__ico"><i data-lucide="user-plus"></i></span>Add doctor</a>
        <a class="qa" href="manage_patients.php?action=add"><span class="qa__ico"><i data-lucide="user-plus"></i></span>Add patient</a>
        <a class="qa" href="manage_appointments.php"><span class="qa__ico"><i data-lucide="calendar-plus"></i></span>Appointments</a>
        <a class="qa" href="manage_ambulances.php"><span class="qa__ico"><i data-lucide="ambulance"></i></span>Ambulances</a>
        <a class="qa" href="reports.php"><span class="qa__ico"><i data-lucide="bar-chart-3"></i></span>Reports</a>
        <a class="qa" href="settings.php"><span class="qa__ico"><i data-lucide="settings"></i></span>Settings</a>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel__head"><div><h3>Recent appointments</h3></div><a class="link" href="manage_appointments.php">View all <i data-lucide="chevron-right"></i></a></div>
  <div class="panel__body flush">
    <?php if ($recentAppts): ?>
    <table class="dtable">
      <thead><tr><th>Patient</th><th>Doctor</th><th>Reason</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($recentAppts as $a): ?>
        <tr>
          <td><div class="who"><img src="<?= $e(ihms_avatar('patient',$a['patient_id'])) ?>" alt=""><div><b><?= $e($a['pf'].' '.$a['pl']) ?></b><span>Patient #<?= $e($a['patient_id']) ?></span></div></div></td>
          <td><div class="who"><img src="<?= $e(ihms_avatar('doctor',$a['doctor_id'])) ?>" alt=""><div><b>Dr. <?= $e($a['df'].' '.$a['dl']) ?></b><span><?= $e($a['specialization']) ?></span></div></div></td>
          <td><?= $e($a['reason'] ?: '—') ?></td>
          <td><?= date('M j, Y', strtotime($a['appointment_date'])) ?></td>
          <td><span class="pill is-<?= $e($a['status']) ?>"><?= $e($a['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty"><i data-lucide="calendar"></i><p>No appointments yet.</p></div><?php endif; ?>
  </div>
</div>

<?php
$chart = "
var sp=function(id,d,c){var el=document.getElementById(id);if(el)IHMS.spark(el,d,c);};
sp('sp1',".json_encode($spPat ?: [0,0,0,0,0,0]).",IHMS.colors.brand);
sp('sp2',".json_encode($spDoc ?: [0,0,0,0,0,0]).",IHMS.colors.accent);
sp('sp3',".json_encode($spAppt ?: [0,0,0,0,0,0]).",'#6d5ae6');
sp('sp4',".json_encode($spRev ?: [0,0,0,0,0,0]).",IHMS.colors.green);
IHMS.area(document.getElementById('trend'),".json_encode($monthLabels).",[{name:'Appointments',data:".json_encode($monthAppts)."}],{colors:[IHMS.colors.accent],height:300});
";
if (array_sum($statusData)) {
    $chart .= "IHMS.donut(document.getElementById('statusDonut'),".json_encode($statusLabels).",".json_encode($statusData).",{totalLabel:'Total'});";
}
dash_foot($page, $chart);
