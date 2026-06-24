<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'doctor') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];

function monthly(PDO $pdo, string $col, string $table, string $where, array $params): array {
    $months = [];
    for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("-$i months"))] = 0;
    $stmt = $pdo->prepare("SELECT DATE_FORMAT($col,'%Y-%m') ym, COUNT(*) c FROM $table WHERE $where GROUP BY ym");
    $stmt->execute($params);
    foreach ($stmt as $r) if (isset($months[$r['ym']])) $months[$r['ym']] = (int)$r['c'];
    return array_values($months);
}

$totalPatients = $todayCount = $upcoming = $presCount = 0;
$weekLabels = $weekData = $statusLabels = $statusData = [];
$todaySchedule = $recentPatients = [];
$sparkAppt = $sparkPres = [];

try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id=? LIMIT 1");
    $stmt->execute([$user['id']]); $did = $stmt->fetchColumn();

    if ($did) {
        $_SESSION['doctor_id'] = $did;

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id=?");
        $stmt->execute([$did]); $totalPatients = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND DATE(appointment_date)=CURDATE()");
        $stmt->execute([$did]); $todayCount = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date>=NOW() AND status NOT IN ('cancelled','completed')");
        $stmt->execute([$did]); $upcoming = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE doctor_id=?");
        $stmt->execute([$did]); $presCount = (int)$stmt->fetchColumn();

        // Appointments last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $weekLabels[] = date('D', strtotime($day));
            $s = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND DATE(appointment_date)=?");
            $s->execute([$did, $day]); $weekData[] = (int)$s->fetchColumn();
        }

        // Status donut
        $stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM appointments WHERE doctor_id=? GROUP BY status");
        $stmt->execute([$did]);
        foreach ($stmt as $r) { $statusLabels[] = ucfirst($r['status']); $statusData[] = (int)$r['c']; }

        // Today's schedule
        $stmt = $pdo->prepare("SELECT a.id, a.appointment_date, a.status, a.reason, p.id patient_id, u.first_name, u.last_name
                               FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id
                               WHERE a.doctor_id=? AND DATE(a.appointment_date)=CURDATE()
                               ORDER BY a.appointment_date ASC");
        $stmt->execute([$did]); $todaySchedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recent patients
        $stmt = $pdo->prepare("SELECT p.id patient_id, u.first_name, u.last_name, p.blood_group, MAX(a.appointment_date) last_visit, COUNT(a.id) visits
                               FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id
                               WHERE a.doctor_id=? GROUP BY p.id, u.first_name, u.last_name, p.blood_group
                               ORDER BY last_visit DESC LIMIT 6");
        $stmt->execute([$did]); $recentPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sparkAppt = monthly($pdo, 'appointment_date', 'appointments', 'doctor_id=?', [$did]);
        $sparkPres = monthly($pdo, 'prescription_date', 'prescriptions', 'doctor_id=?', [$did]);
    }
} catch (PDOException $e) { $error = $e->getMessage(); }

$page = ['role'=>'doctor','active'=>'dashboard','title'=>'Dashboard',
         'subtitle'=>'Good day, Dr. '.htmlspecialchars($user['last_name'] ?: $user['first_name']),
         'user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="welcome">
  <div class="welcome__text">
    <h2>Welcome back, Dr. <?= $e($user['first_name']) ?> 🩺</h2>
    <p>You have <strong><?= $todayCount ?></strong> appointment<?= $todayCount==1?'':'s' ?> scheduled today.</p>
  </div>
  <a href="manage_appointments.php" class="btn"><i data-lucide="calendar-clock"></i> Manage requests</a>
</div>

<div class="kpis">
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-brand"><i data-lucide="users"></i></div></div>
    <div class="kpi__val"><?= $totalPatients ?></div><div class="kpi__label">Total patients</div><div class="kpi__spark" id="sp1"></div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-accent"><i data-lucide="calendar-days"></i></div>
    <span class="kpi__delta up"><i data-lucide="clock"></i> today</span></div>
    <div class="kpi__val"><?= $todayCount ?></div><div class="kpi__label">Today's appointments</div><div class="kpi__spark" id="sp2"></div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-green"><i data-lucide="calendar-check"></i></div></div>
    <div class="kpi__val"><?= $upcoming ?></div><div class="kpi__label">Upcoming</div><div class="kpi__spark" id="sp3"></div></div>
  <div class="kpi"><div class="kpi__top"><div class="kpi__ico is-amber"><i data-lucide="pill"></i></div></div>
    <div class="kpi__val"><?= $presCount ?></div><div class="kpi__label">Prescriptions written</div><div class="kpi__spark" id="sp4"></div></div>
</div>

<div class="grid grid--2-1 mb">
  <div class="panel">
    <div class="panel__head"><div><h3>Appointment volume</h3><p class="sub">Last 7 days</p></div></div>
    <div class="panel__body"><div id="weekBar" class="chart-wrap"></div></div>
  </div>
  <div class="panel">
    <div class="panel__head"><div><h3>Status mix</h3></div></div>
    <div class="panel__body">
      <?php if (array_sum($statusData)): ?><div id="statusDonut" class="chart-wrap"></div>
      <?php else: ?><div class="empty"><i data-lucide="calendar"></i><p>No appointments yet.</p></div><?php endif; ?>
    </div>
  </div>
</div>

<div class="grid grid--2-1 mb">
  <div class="panel">
    <div class="panel__head"><div><h3>Today's schedule</h3><p class="sub"><?= date('l, F j') ?></p></div>
      <a class="link" href="appointments.php">All appointments <i data-lucide="chevron-right"></i></a></div>
    <div class="panel__body flush">
      <?php if ($todaySchedule): ?>
        <div class="lst">
        <?php foreach ($todaySchedule as $a): ?>
          <div class="lst__item">
            <img class="lst__avatar lst__avatar--ring" src="<?= $e(ihms_avatar('patient',$a['patient_id'])) ?>" alt="">
            <div class="lst__main">
              <b><?= $e($a['first_name'].' '.$a['last_name']) ?></b>
              <span><i data-lucide="clipboard-list"></i><?= $e($a['reason'] ?: 'Consultation') ?></span>
            </div>
            <div class="lst__meta"><b><?= date('g:i A', strtotime($a['appointment_date'])) ?></b></div>
            <span class="pill is-<?= $e($a['status']) ?>" style="margin-left:1rem"><?= $e($a['status']) ?></span>
          </div>
        <?php endforeach; ?>
        </div>
      <?php else: ?><div class="empty"><i data-lucide="coffee"></i><p>No appointments scheduled today.</p></div><?php endif; ?>
    </div>
  </div>
  <div class="panel">
    <div class="panel__head"><div><h3>Quick actions</h3></div></div>
    <div class="panel__body">
      <div class="qa-grid">
        <a class="qa" href="prescriptions.php"><span class="qa__ico"><i data-lucide="pill"></i></span>New prescription</a>
        <a class="qa" href="manage_appointments.php"><span class="qa__ico"><i data-lucide="calendar-clock"></i></span>Requests</a>
        <a class="qa" href="schedule.php"><span class="qa__ico"><i data-lucide="clock"></i></span>My schedule</a>
        <a class="qa" href="patients.php"><span class="qa__ico"><i data-lucide="users"></i></span>Patients</a>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel__head"><div><h3>Recent patients</h3></div><a class="link" href="patients.php">View all <i data-lucide="chevron-right"></i></a></div>
  <div class="panel__body flush">
    <?php if ($recentPatients): ?>
    <table class="dtable">
      <thead><tr><th>Patient</th><th>Blood group</th><th>Visits</th><th>Last visit</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($recentPatients as $p): ?>
        <tr>
          <td><div class="who"><img src="<?= $e(ihms_avatar('patient',$p['patient_id'])) ?>" alt=""><div><b><?= $e($p['first_name'].' '.$p['last_name']) ?></b><span>Patient #<?= $e($p['patient_id']) ?></span></div></div></td>
          <td><span class="pill is-active"><?= $e($p['blood_group'] ?: 'N/A') ?></span></td>
          <td><?= (int)$p['visits'] ?></td>
          <td><?= $p['last_visit'] ? date('M j, Y', strtotime($p['last_visit'])) : '—' ?></td>
          <td style="text-align:right"><a class="link" href="patients.php">View <i data-lucide="chevron-right"></i></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty"><i data-lucide="users"></i><p>No patients yet.</p></div><?php endif; ?>
  </div>
</div>

<?php
$chart = "
var sp=function(id,d,c){var el=document.getElementById(id);if(el)IHMS.spark(el,d,c);};
sp('sp1',".json_encode($sparkAppt ?: [0,0,0,0,0,0]).",IHMS.colors.brand);
sp('sp2',".json_encode($weekData ?: [0,0,0,0,0,0,0]).",IHMS.colors.accent);
sp('sp3',".json_encode($sparkAppt ?: [0,0,0,0,0,0]).",IHMS.colors.green);
sp('sp4',".json_encode($sparkPres ?: [0,0,0,0,0,0]).",IHMS.colors.amber);
IHMS.bar(document.getElementById('weekBar'),".json_encode($weekLabels).",[{name:'Appointments',data:".json_encode($weekData)."}],{colors:[IHMS.colors.brand]});
";
if (array_sum($statusData)) {
    $chart .= "IHMS.donut(document.getElementById('statusDonut'),".json_encode($statusLabels).",".json_encode($statusData).",{totalLabel:'Total'});";
}
dash_foot($page, $chart);
