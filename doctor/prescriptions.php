<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'doctor') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];
$errors = []; $success = '';
try {
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id=? LIMIT 1");
    $stmt->execute([$user['id']]); $doctorId = $stmt->fetchColumn();
    if (!$doctorId) { session_destroy(); header("Location: ../login.php"); exit(); }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['write_prescription'])) {
        $pid = $_POST['patient_id'] ?? ''; $med = trim($_POST['medication'] ?? '');
        $dose = trim($_POST['dosage'] ?? ''); $instr = trim($_POST['instructions'] ?? '');
        if ($pid && $med) {
            $pdo->prepare("INSERT INTO prescriptions (patient_id,doctor_id,medication,dosage,instructions,prescription_date,status) VALUES (?,?,?,?,?,CURDATE(),'Active')")
                ->execute([$pid,$doctorId,$med,$dose,$instr]);
            // mark earliest active appointment completed
            $pdo->prepare("UPDATE appointments SET status='completed' WHERE doctor_id=? AND patient_id=? AND status IN ('pending','confirmed') ORDER BY appointment_date ASC LIMIT 1")
                ->execute([$doctorId,$pid]);
            $success = 'Prescription written successfully.';
        } else { $errors[] = 'Patient and medication are required.'; }
    }

    // patients this doctor has seen (fallback: all)
    $patients = $pdo->prepare("SELECT DISTINCT p.id, u.first_name, u.last_name FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id WHERE a.doctor_id=? ORDER BY u.first_name");
    $patients->execute([$doctorId]); $patients = $patients->fetchAll(PDO::FETCH_ASSOC);
    if (!$patients) $patients = $pdo->query("SELECT p.id, u.first_name, u.last_name FROM patients p JOIN users u ON p.user_id=u.id ORDER BY u.first_name")->fetchAll(PDO::FETCH_ASSOC);

    $rx = $pdo->prepare("SELECT p.*, pt.id patient_id, u.first_name, u.last_name FROM prescriptions p JOIN patients pt ON p.patient_id=pt.id JOIN users u ON pt.user_id=u.id WHERE p.doctor_id=? ORDER BY p.prescription_date DESC, p.id DESC");
    $rx->execute([$doctorId]); $prescriptions = $rx->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $errors[] = $e->getMessage(); $patients = $prescriptions = []; }

$page = ['role'=>'doctor','active'=>'prescriptions','title'=>'Prescriptions',
         'subtitle'=>'Write and review prescriptions','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="grid grid--2-1">
  <div class="panel" style="order:2">
    <div class="panel__head"><div><h3>Recent prescriptions</h3></div></div>
    <div class="panel__body flush">
      <?php if ($prescriptions): ?>
      <table class="dtable">
        <thead><tr><th>Patient</th><th>Medication</th><th>Dosage</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($prescriptions as $p): ?>
          <tr>
            <td><div class="who"><img src="<?= $e(ihms_avatar('patient',$p['patient_id'])) ?>" alt=""><div><b><?= $e($p['first_name'].' '.$p['last_name']) ?></b></div></div></td>
            <td><b style="font-family:var(--font-head)"><?= $e($p['medication']) ?></b></td>
            <td><?= $e($p['dosage'] ?: '—') ?></td>
            <td><?= date('M j, Y', strtotime($p['prescription_date'])) ?></td>
            <td><span class="pill is-<?= strtolower($e($p['status'])) ?>"><?= $e($p['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><div class="empty"><i data-lucide="pill"></i><p>No prescriptions written yet.</p></div><?php endif; ?>
    </div>
  </div>

  <div class="panel" style="order:1;align-self:start">
    <div class="panel__head"><div><h3>Write prescription</h3></div></div>
    <div class="panel__body">
      <?php if ($success): ?><div class="alert alert--success"><i data-lucide="check-circle"></i><?= $e($success) ?></div><?php endif; ?>
      <?php foreach ($errors as $er): ?><div class="alert alert--error"><i data-lucide="alert-triangle"></i><?= $e($er) ?></div><?php endforeach; ?>
      <form method="POST">
        <div class="field"><label>Patient</label>
          <select class="input" name="patient_id" required>
            <option value="">Select patient…</option>
            <?php foreach ($patients as $p): ?><option value="<?= (int)$p['id'] ?>"><?= $e($p['first_name'].' '.$p['last_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Medication</label><input class="input input--plain" name="medication" required placeholder="e.g. Amoxicillin"></div>
        <div class="field"><label>Dosage</label><input class="input input--plain" name="dosage" placeholder="e.g. 500mg twice daily"></div>
        <div class="field"><label>Instructions</label><textarea class="input input--plain" name="instructions" rows="3" placeholder="Additional instructions…"></textarea></div>
        <button class="btn btn--brand btn--block" name="write_prescription" value="1"><i data-lucide="plus"></i> Add prescription</button>
      </form>
    </div>
  </div>
</div>

<?php dash_foot($page); ?>
