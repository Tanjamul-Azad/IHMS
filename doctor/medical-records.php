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

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_record'])) {
        $pid = $_POST['patient_id'] ?? ''; $diag = trim($_POST['diagnosis'] ?? '');
        $treat = trim($_POST['treatment'] ?? ''); $notes = trim($_POST['notes'] ?? ''); $next = $_POST['next_visit_date'] ?: null;
        if ($pid && $diag) {
            $pdo->prepare("INSERT INTO medical_history (patient_id,doctor_id,diagnosis,treatment,notes,visit_date,next_visit_date) VALUES (?,?,?,?,?,CURDATE(),?)")
                ->execute([$pid,$doctorId,$diag,$treat,$notes,$next]);
            $success = 'Medical record added.';
        } else { $errors[] = 'Patient and diagnosis are required.'; }
    }

    $patients = $pdo->prepare("SELECT DISTINCT p.id, u.first_name, u.last_name FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN users u ON p.user_id=u.id WHERE a.doctor_id=? ORDER BY u.first_name");
    $patients->execute([$doctorId]); $patients = $patients->fetchAll(PDO::FETCH_ASSOC);
    if (!$patients) $patients = $pdo->query("SELECT p.id, u.first_name, u.last_name FROM patients p JOIN users u ON p.user_id=u.id ORDER BY u.first_name")->fetchAll(PDO::FETCH_ASSOC);

    $recs = $pdo->prepare("SELECT mh.*, p.id patient_id, u.first_name, u.last_name FROM medical_history mh JOIN patients p ON mh.patient_id=p.id JOIN users u ON p.user_id=u.id WHERE mh.doctor_id=? ORDER BY COALESCE(mh.visit_date,DATE(mh.created_at)) DESC");
    $recs->execute([$doctorId]); $records = $recs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $errors[] = $e->getMessage(); $patients = $records = []; }

$page = ['role'=>'doctor','active'=>'records','title'=>'Medical Records',
         'subtitle'=>'Record and review patient histories','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="grid grid--2-1">
  <div class="panel" style="order:2">
    <div class="panel__head"><div><h3>Records</h3></div></div>
    <div class="panel__body flush">
      <?php if ($records): ?>
      <table class="dtable">
        <thead><tr><th>Patient</th><th>Diagnosis</th><th>Treatment</th><th>Visit</th></tr></thead>
        <tbody>
        <?php foreach ($records as $r): ?>
          <tr>
            <td><div class="who"><img src="<?= $e(ihms_avatar('patient',$r['patient_id'])) ?>" alt=""><div><b><?= $e($r['first_name'].' '.$r['last_name']) ?></b></div></div></td>
            <td><?= $e($r['diagnosis']) ?></td>
            <td style="color:var(--text-muted)"><?= $e($r['treatment'] ?: '—') ?></td>
            <td><?= date('M j, Y', strtotime($r['visit_date'] ?: $r['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><div class="empty"><i data-lucide="file-text"></i><p>No records yet.</p></div><?php endif; ?>
    </div>
  </div>

  <div class="panel" style="order:1;align-self:start">
    <div class="panel__head"><div><h3>Add record</h3></div></div>
    <div class="panel__body">
      <?php if ($success): ?><div class="alert alert--success"><i data-lucide="check-circle"></i><?= $e($success) ?></div><?php endif; ?>
      <?php foreach ($errors as $er): ?><div class="alert alert--error"><i data-lucide="alert-triangle"></i><?= $e($er) ?></div><?php endforeach; ?>
      <form method="POST">
        <div class="field"><label>Patient</label>
          <select class="input" name="patient_id" required><option value="">Select patient…</option>
            <?php foreach ($patients as $p): ?><option value="<?= (int)$p['id'] ?>"><?= $e($p['first_name'].' '.$p['last_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Diagnosis</label><input class="input input--plain" name="diagnosis" required></div>
        <div class="field"><label>Treatment</label><input class="input input--plain" name="treatment"></div>
        <div class="field"><label>Notes</label><textarea class="input input--plain" name="notes" rows="2"></textarea></div>
        <div class="field"><label>Next visit (optional)</label><input class="input input--plain" type="date" name="next_visit_date"></div>
        <button class="btn btn--brand btn--block" name="add_record" value="1"><i data-lucide="plus"></i> Add record</button>
      </form>
    </div>
  </div>
</div>

<?php dash_foot($page); ?>
