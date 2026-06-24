<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'patient') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];

$search = $_GET['search'] ?? '';
$specialization = $_GET['specialization'] ?? '';
$location = $_GET['location'] ?? '';

$doctors = $specializations = $locations = [];
$query = "
    SELECT d.*, u.first_name, u.last_name, u.email,
           d.experience_years AS experience, d.hospital_affiliation AS hospital,
           d.hospital_affiliation AS location,
           COUNT(DISTINCT a.id) as total_patients
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN appointments a ON d.id = a.doctor_id
    WHERE u.status = 'active'
";
$params = [];
if ($search) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR d.specialization LIKE ? OR d.hospital_affiliation LIKE ?)";
    $t = "%$search%"; $params = array_merge($params, [$t,$t,$t,$t]);
}
if ($specialization) { $query .= " AND d.specialization = ?"; $params[] = $specialization; }
if ($location)       { $query .= " AND d.hospital_affiliation = ?"; $params[] = $location; }
$query .= " GROUP BY d.id ORDER BY d.experience_years DESC, total_patients DESC";

try {
    $stmt = $pdo->prepare($query); $stmt->execute($params);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $specializations = $pdo->query("SELECT DISTINCT specialization FROM doctors WHERE specialization IS NOT NULL AND specialization<>'' ORDER BY specialization")->fetchAll(PDO::FETCH_COLUMN);
    $locations = $pdo->query("SELECT DISTINCT hospital_affiliation FROM doctors WHERE hospital_affiliation IS NOT NULL AND hospital_affiliation<>'' ORDER BY hospital_affiliation")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) { $error = $e->getMessage(); }

$page = ['role'=>'patient','active'=>'find','title'=>'Find Doctors',
         'subtitle'=>'Browse our specialists and book a consultation','user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<!-- Filter -->
<div class="panel mb">
  <div class="panel__body">
    <form method="GET" class="toolbar">
      <div class="field" style="flex:1;min-width:220px">
        <label>Search</label>
        <div class="input-wrap"><i data-lucide="search"></i>
          <input class="input" type="text" name="search" value="<?= $e($search) ?>" placeholder="Name, specialization, hospital…"></div>
      </div>
      <div class="field">
        <label>Specialization</label>
        <select class="input" name="specialization">
          <option value="">All specializations</option>
          <?php foreach ($specializations as $s): ?><option value="<?= $e($s) ?>" <?= $specialization===$s?'selected':'' ?>><?= $e($s) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Hospital</label>
        <select class="input" name="location">
          <option value="">All hospitals</option>
          <?php foreach ($locations as $l): ?><option value="<?= $e($l) ?>" <?= $location===$l?'selected':'' ?>><?= $e($l) ?></option><?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn--brand" type="submit"><i data-lucide="search"></i> Search</button>
      <?php if ($search||$specialization||$location): ?><a class="btn btn--ghost" href="browse_doctors.php">Reset</a><?php endif; ?>
    </form>
  </div>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert--error"><i data-lucide="alert-triangle"></i><?= $e($error) ?></div>
<?php elseif (empty($doctors)): ?>
  <div class="panel"><div class="empty"><i data-lucide="search-x"></i><p>No doctors match your search. <a href="browse_doctors.php">Clear filters →</a></p></div></div>
<?php else: ?>
  <div class="grid grid--3">
    <?php foreach ($doctors as $d): ?>
      <div class="docard">
        <div class="docard__top">
          <img src="<?= $e(ihms_avatar('doctor',$d['id'])) ?>" alt="">
          <div>
            <h3>Dr. <?= $e($d['first_name'].' '.$d['last_name']) ?></h3>
            <span class="docard__spec"><?= $e($d['specialization']) ?></span>
            <div class="docard__rate"><i data-lucide="star"></i> <?= $d['rating'] ? number_format($d['rating'],1) : 'New' ?> · <?= (int)$d['total_patients'] ?> patients</div>
          </div>
        </div>
        <div class="chips">
          <span class="chip"><i data-lucide="briefcase"></i><?= (int)$d['experience'] ?>+ yrs</span>
          <?php if (!empty($d['consultation_fee'])): ?><span class="chip"><i data-lucide="banknote"></i>$<?= (int)$d['consultation_fee'] ?></span><?php endif; ?>
        </div>
        <div class="docard__meta">
          <?php if (!empty($d['hospital'])): ?><div><i data-lucide="building-2"></i><?= $e($d['hospital']) ?></div><?php endif; ?>
          <?php if (!empty($d['available_days'])): ?><div><i data-lucide="calendar-days"></i><?= $e($d['available_days']) ?></div><?php endif; ?>
          <div><i data-lucide="mail"></i><?= $e($d['email']) ?></div>
        </div>
        <div class="docard__foot">
          <a class="btn btn--brand" href="book_appointment.php?doctor_id=<?= (int)$d['id'] ?>"><i data-lucide="calendar-plus"></i> Book</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php dash_foot($page); ?>
