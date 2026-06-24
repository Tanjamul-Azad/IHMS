<?php
session_start();
require_once 'dbConnect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user']['role'] ?? '', ['doctor','admin'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
$errors = []; $patient = null; $records = []; $prescriptions = [];
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.email FROM patients p JOIN users u ON p.user_id=u.id WHERE p.id=?");
        $stmt->execute([$patient_id]); $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($patient) {
            $stmt = $pdo->prepare("SELECT mh.*, du.first_name df, du.last_name dl FROM medical_history mh
                                   LEFT JOIN doctors d ON mh.doctor_id=d.id LEFT JOIN users du ON d.user_id=du.id
                                   WHERE mh.patient_id=? ORDER BY COALESCE(mh.visit_date,DATE(mh.created_at)) DESC");
            $stmt->execute([$patient_id]); $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $pdo->prepare("SELECT p.*, du.first_name df, du.last_name dl FROM prescriptions p
                                   LEFT JOIN doctors d ON p.doctor_id=d.id LEFT JOIN users du ON d.user_id=du.id
                                   WHERE p.patient_id=? ORDER BY p.prescription_date DESC");
            $stmt->execute([$patient_id]); $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else { $errors[] = "No patient found with ID #$patient_id."; }
    } catch (PDOException $e) { $errors[] = $e->getMessage(); }
}

$page = ['role'=>$_SESSION['user']['role'],'active'=>'patients','title'=>'Patient History',
         'subtitle'=>'Look up a patient\'s full medical history','user'=>$user,'base'=>''];
require_once __DIR__ . '/includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="panel mb">
  <div class="panel__body">
    <form method="GET" class="toolbar">
      <div class="field" style="flex:1;min-width:240px">
        <label>Patient ID</label>
        <div class="input-wrap"><i data-lucide="search"></i>
          <input class="input" type="number" name="patient_id" value="<?= $patient_id ?: '' ?>" placeholder="Enter patient ID number" required></div>
      </div>
      <button class="btn btn--brand" type="submit"><i data-lucide="file-search"></i> View history</button>
    </form>
  </div>
</div>

<?php foreach ($errors as $er): ?><div class="alert alert--error"><i data-lucide="alert-triangle"></i><?= $e($er) ?></div><?php endforeach; ?>

<?php if ($patient): ?>
  <div class="panel mb">
    <div class="panel__body" style="display:flex;align-items:center;gap:1.2rem">
      <img src="<?= $e(ihms_avatar('patient',$patient['id'],'')) ?>" alt="" style="width:72px;height:72px;border-radius:16px;object-fit:cover">
      <div style="flex:1">
        <h3 style="margin:0"><?= $e($patient['first_name'].' '.$patient['last_name']) ?></h3>
        <p class="sub" style="margin:.2rem 0 0"><?= $e($patient['email']) ?> · Patient #<?= (int)$patient['id'] ?></p>
      </div>
      <div class="chips">
        <?php if (!empty($patient['blood_group'])): ?><span class="chip"><i data-lucide="droplet"></i><?= $e($patient['blood_group']) ?></span><?php endif; ?>
        <?php if (!empty($patient['gender'])): ?><span class="chip"><i data-lucide="user"></i><?= $e($patient['gender']) ?></span><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="grid grid--1-1">
    <div class="panel">
      <div class="panel__head"><div><h3>Medical history</h3><p class="sub"><?= count($records) ?> records</p></div></div>
      <div class="panel__body flush">
        <?php if ($records): ?><table class="dtable"><thead><tr><th>Date</th><th>Diagnosis</th><th>Doctor</th></tr></thead><tbody>
          <?php foreach ($records as $r): ?><tr><td><?= date('M j, Y', strtotime($r['visit_date'] ?: $r['created_at'])) ?></td><td><?= $e($r['diagnosis']) ?></td><td>Dr. <?= $e(trim(($r['df']??'').' '.($r['dl']??''))?:'—') ?></td></tr><?php endforeach; ?>
        </tbody></table><?php else: ?><div class="empty"><i data-lucide="file-text"></i><p>No records.</p></div><?php endif; ?>
      </div>
    </div>
    <div class="panel">
      <div class="panel__head"><div><h3>Prescriptions</h3><p class="sub"><?= count($prescriptions) ?> total</p></div></div>
      <div class="panel__body flush">
        <?php if ($prescriptions): ?><table class="dtable"><thead><tr><th>Medication</th><th>Date</th><th>Status</th></tr></thead><tbody>
          <?php foreach ($prescriptions as $p): ?><tr><td><b style="font-family:var(--font-head)"><?= $e($p['medication']) ?></b></td><td><?= date('M j, Y', strtotime($p['prescription_date'])) ?></td><td><span class="pill is-<?= strtolower($e($p['status'])) ?>"><?= $e($p['status']) ?></span></td></tr><?php endforeach; ?>
        </tbody></table><?php else: ?><div class="empty"><i data-lucide="pill"></i><p>No prescriptions.</p></div><?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php dash_foot($page); ?>
