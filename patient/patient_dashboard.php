<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'patient') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];

/** Last-6-months counts as a small array for KPI sparklines. */
function monthly(PDO $pdo, string $col, string $table, string $where, array $params): array {
    $months = [];
    for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("-$i months"))] = 0;
    $stmt = $pdo->prepare("SELECT DATE_FORMAT($col,'%Y-%m') ym, COUNT(*) c FROM $table WHERE $where GROUP BY ym");
    $stmt->execute($params);
    foreach ($stmt as $r) if (isset($months[$r['ym']])) $months[$r['ym']] = (int)$r['c'];
    return array_values($months);
}

$upcoming = $active = $records = $logsCount = 0;
$vitLabels = $vitBp = $vitHr = [];
$statusLabels = $statusData = [];
$upcomingList = $recentPres = [];
$next = null;
$sparkAppt = $sparkPres = $sparkRec = $sparkWeight = [];

try {
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $pid = $stmt->fetchColumn();

    if ($pid) {
        $_SESSION['patient_id'] = $pid;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id=? AND appointment_date>=NOW() AND status NOT IN ('cancelled','completed')");
        $stmt->execute([$pid]); $upcoming = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id=? AND status='Active'");
        $stmt->execute([$pid]); $active = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM medical_history WHERE patient_id=?");
        $stmt->execute([$pid]); $records = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM health_logs WHERE patient_id=?");
        $stmt->execute([$pid]); $logsCount = (int)$stmt->fetchColumn();

        // Vitals (last 14 readings)
        $stmt = $pdo->prepare("SELECT log_date, blood_pressure_systolic bps, heart_rate hr, weight_kg w FROM health_logs WHERE patient_id=? ORDER BY log_date ASC LIMIT 14");
        $stmt->execute([$pid]);
        foreach ($stmt as $r) {
            $vitLabels[] = date('M j', strtotime($r['log_date']));
            $vitBp[] = (int)$r['bps']; $vitHr[] = (int)$r['hr']; $sparkWeight[] = (float)$r['w'];
        }
        $sparkWeight = array_slice($sparkWeight, -7);

        // Appointment status distribution
        $stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM appointments WHERE patient_id=? GROUP BY status");
        $stmt->execute([$pid]);
        foreach ($stmt as $r) { $statusLabels[] = ucfirst($r['status']); $statusData[] = (int)$r['c']; }

        // Upcoming appointments
        $stmt = $pdo->prepare("SELECT a.id, a.appointment_date, a.status, a.reason, d.id doctor_id, d.specialization, u.first_name, u.last_name
                               FROM appointments a JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
                               WHERE a.patient_id=? AND a.appointment_date>=NOW() AND a.status<>'cancelled'
                               ORDER BY a.appointment_date ASC LIMIT 5");
        $stmt->execute([$pid]); $upcomingList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $next = $upcomingList[0] ?? null;

        // Recent prescriptions
        $stmt = $pdo->prepare("SELECT p.medication, p.dosage, p.prescription_date, p.status, d.id doctor_id, u.first_name, u.last_name
                               FROM prescriptions p JOIN doctors d ON p.doctor_id=d.id JOIN users u ON d.user_id=u.id
                               WHERE p.patient_id=? ORDER BY p.prescription_date DESC LIMIT 5");
        $stmt->execute([$pid]); $recentPres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sparkAppt = monthly($pdo, 'appointment_date', 'appointments', 'patient_id=?', [$pid]);
        $sparkPres = monthly($pdo, 'prescription_date', 'prescriptions', 'patient_id=?', [$pid]);
        $sparkRec  = monthly($pdo, 'created_at', 'medical_history', 'patient_id=?', [$pid]);
    }
} catch (PDOException $e) { $error = $e->getMessage(); }

$page = ['role'=>'patient','active'=>'dashboard','title'=>'Dashboard',
         'subtitle'=>'Welcome back, '.htmlspecialchars($user['first_name']).' — here is your health overview',
         'user'=>$user,'notif'=>1];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<!-- Welcome banner -->
<div class="welcome">
  <div class="welcome__text">
    <h2>Hello <?= $e($user['first_name']) ?> 👋</h2>
    <p>Stay on top of your appointments, prescriptions and vitals — all in one place.</p>
  </div>
  <a href="book_appointment.php" class="btn"><i data-lucide="calendar-plus"></i> Book Appointment</a>
</div>

<!-- KPIs -->
<div class="kpis">
  <div class="kpi">
    <div class="kpi__top"><div class="kpi__ico is-brand"><i data-lucide="calendar-check"></i></div>
      <span class="kpi__delta up"><i data-lucide="trending-up"></i> live</span></div>
    <div class="kpi__val"><?= $upcoming ?></div><div class="kpi__label">Upcoming appointments</div>
    <div class="kpi__spark" id="sp1"></div>
  </div>
  <div class="kpi">
    <div class="kpi__top"><div class="kpi__ico is-green"><i data-lucide="pill"></i></div></div>
    <div class="kpi__val"><?= $active ?></div><div class="kpi__label">Active prescriptions</div>
    <div class="kpi__spark" id="sp2"></div>
  </div>
  <div class="kpi">
    <div class="kpi__top"><div class="kpi__ico is-accent"><i data-lucide="file-text"></i></div></div>
    <div class="kpi__val"><?= $records ?></div><div class="kpi__label">Medical records</div>
    <div class="kpi__spark" id="sp3"></div>
  </div>
  <div class="kpi">
    <div class="kpi__top"><div class="kpi__ico is-violet"><i data-lucide="activity"></i></div></div>
    <div class="kpi__val"><?= $logsCount ?></div><div class="kpi__label">Health log entries</div>
    <div class="kpi__spark" id="sp4"></div>
  </div>
</div>

<!-- Vitals + status -->
<div class="grid grid--2-1 mb">
  <div class="panel">
    <div class="panel__head">
      <div><h3>Vitals trend</h3><p class="sub">Blood pressure & heart rate · last <?= count($vitLabels) ?> readings</p></div>
      <a class="link" href="health-log.php">Add reading <i data-lucide="chevron-right"></i></a>
    </div>
    <div class="panel__body">
      <?php if ($vitLabels): ?><div id="vitals" class="chart-wrap"></div>
      <?php else: ?><div class="empty"><i data-lucide="activity"></i><p>No vitals logged yet. Start your health log.</p></div><?php endif; ?>
    </div>
  </div>
  <div class="panel">
    <div class="panel__head"><div><h3>Appointments</h3><p class="sub">By status</p></div></div>
    <div class="panel__body">
      <?php if (array_sum($statusData)): ?><div id="apptDonut" class="chart-wrap"></div>
      <?php else: ?><div class="empty"><i data-lucide="calendar"></i><p>No appointments yet.</p></div><?php endif; ?>
    </div>
  </div>
</div>

<!-- Upcoming + quick actions -->
<div class="grid grid--2-1 mb">
  <div class="panel">
    <div class="panel__head"><div><h3>Upcoming appointments</h3></div><a class="link" href="my_appointments.php">View all <i data-lucide="chevron-right"></i></a></div>
    <div class="panel__body flush">
      <?php if ($upcomingList): ?>
        <div class="lst">
        <?php foreach ($upcomingList as $a): ?>
          <div class="lst__item">
            <img class="lst__avatar lst__avatar--ring" src="<?= $e(ihms_avatar('doctor',$a['doctor_id'])) ?>" alt="">
            <div class="lst__main">
              <b>Dr. <?= $e($a['first_name'].' '.$a['last_name']) ?></b>
              <span><i data-lucide="stethoscope"></i><?= $e($a['specialization']) ?></span>
            </div>
            <div class="lst__meta">
              <b><?= date('M j', strtotime($a['appointment_date'])) ?></b>
              <span><?= date('g:i A', strtotime($a['appointment_date'])) ?></span>
            </div>
            <span class="pill is-<?= $e($a['status']) ?>" style="margin-left:1rem"><?= $e($a['status']) ?></span>
          </div>
        <?php endforeach; ?>
        </div>
      <?php else: ?><div class="empty"><i data-lucide="calendar-plus"></i><p>No upcoming appointments. <a href="book_appointment.php">Book one →</a></p></div><?php endif; ?>
    </div>
  </div>
  <div class="panel">
    <div class="panel__head"><div><h3>Quick actions</h3></div></div>
    <div class="panel__body">
      <div class="qa-grid">
        <a class="qa" href="book_appointment.php"><span class="qa__ico"><i data-lucide="calendar-plus"></i></span>Book visit</a>
        <a class="qa" href="browse_doctors.php"><span class="qa__ico"><i data-lucide="stethoscope"></i></span>Find doctor</a>
        <a class="qa" href="health-log.php"><span class="qa__ico"><i data-lucide="activity"></i></span>Log vitals</a>
        <a class="qa" href="../blood_bank/index.php"><span class="qa__ico"><i data-lucide="droplet"></i></span>Blood bank</a>
        <a class="qa" href="prescriptions.php"><span class="qa__ico"><i data-lucide="pill"></i></span>Prescriptions</a>
        <a class="qa" href="reports.php"><span class="qa__ico"><i data-lucide="clipboard-list"></i></span>Reports</a>
      </div>
    </div>
  </div>
</div>

<!-- Recent prescriptions -->
<div class="panel">
  <div class="panel__head"><div><h3>Recent prescriptions</h3></div><a class="link" href="prescriptions.php">View all <i data-lucide="chevron-right"></i></a></div>
  <div class="panel__body flush">
    <?php if ($recentPres): ?>
    <table class="dtable">
      <thead><tr><th>Medication</th><th>Dosage</th><th>Prescribed by</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($recentPres as $p): ?>
        <tr>
          <td><b style="font-family:var(--font-head)"><?= $e($p['medication']) ?></b></td>
          <td><?= $e($p['dosage']) ?></td>
          <td><div class="who"><img src="<?= $e(ihms_avatar('doctor',$p['doctor_id'])) ?>" alt=""><div><b>Dr. <?= $e($p['first_name'].' '.$p['last_name']) ?></b></div></div></td>
          <td><?= date('M j, Y', strtotime($p['prescription_date'])) ?></td>
          <td><span class="pill is-<?= strtolower($e($p['status'])) ?>"><?= $e($p['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="empty"><i data-lucide="pill"></i><p>No prescriptions on record.</p></div><?php endif; ?>
  </div>
</div>

<?php
$chart = "
var sp=function(id,d,c){var el=document.getElementById(id);if(el)IHMS.spark(el,d,c);};
sp('sp1',".json_encode($sparkAppt ?: [0,0,0,0,0,0]).",IHMS.colors.brand);
sp('sp2',".json_encode($sparkPres ?: [0,0,0,0,0,0]).",IHMS.colors.green);
sp('sp3',".json_encode($sparkRec ?: [0,0,0,0,0,0]).",IHMS.colors.accent);
sp('sp4',".json_encode($sparkWeight ?: [0,0,0,0,0,0]).",'#6d5ae6');
";
if ($vitLabels) {
    $chart .= "IHMS.area(document.getElementById('vitals'),".json_encode($vitLabels).",[
      {name:'Systolic BP',data:".json_encode($vitBp)."},
      {name:'Heart rate',data:".json_encode($vitHr)."}
    ],{colors:[IHMS.colors.accent,IHMS.colors.rose],legend:true,height:300});";
}
if (array_sum($statusData)) {
    $chart .= "IHMS.donut(document.getElementById('apptDonut'),".json_encode($statusLabels).",".json_encode($statusData).",{totalLabel:'Appointments'});";
}
dash_foot($page, $chart);
