<?php
session_start();
require_once '../dbConnect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user']['role'] ?? '') !== 'patient') {
    header("Location: ../login.php");
    exit();
}
$user = $_SESSION['user'];

if (!isset($_GET['doctor_id'])) { header("Location: browse_doctors.php"); exit(); }

$stmt = $pdo->prepare("SELECT d.*, u.first_name, u.last_name FROM doctors d JOIN users u ON d.user_id=u.id WHERE d.id=?");
$stmt->execute([$_GET['doctor_id']]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doctor) { die('Doctor not found.'); }
$doctorId = (int)$doctor['id'];

// Availability slots
$stmt = $pdo->prepare("SELECT * FROM doctor_availability WHERE doctor_id=? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
$stmt->execute([$doctorId]);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
$available_days = []; $slots_by_day = [];
foreach ($slots as $s) { $available_days[$s['day_of_week']] = true; $slots_by_day[$s['day_of_week']][] = ['start_time'=>$s['start_time'],'end_time'=>$s['end_time'],'id'=>$s['id']]; }
$available_days = array_keys($available_days);

$error = $success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    try {
        $ps = $pdo->prepare("SELECT id FROM patients WHERE user_id=? LIMIT 1");
        $ps->execute([$user['id']]); $patientId = $ps->fetchColumn();
        if (!$patientId) throw new Exception('Patient profile missing.');
        if (!$date || !$time) throw new Exception('Please choose a date and time.');

        $dayName = date('l', strtotime($date));
        // Only enforce the day check when the doctor has published availability.
        if ($available_days && !in_array($dayName, $available_days)) throw new Exception("Doctor isn't available on {$dayName}s.");
        if (strtotime($date) < strtotime(date('Y-m-d'))) throw new Exception('Please choose a future date.');

        $dt = date('Y-m-d H:i:s', strtotime("$date $time"));
        // double-booking guard
        $chk = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date=? AND status<>'cancelled'");
        $chk->execute([$doctorId, $dt]);
        if ($chk->fetchColumn() > 0) throw new Exception('That time slot is already booked. Please pick another.');
        // same-day duplicate guard
        $dup = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND patient_id=? AND DATE(appointment_date)=? AND status NOT IN ('cancelled','completed')");
        $dup->execute([$doctorId, $patientId, $date]);
        if ($dup->fetchColumn() > 0) throw new Exception('You already have an appointment with this doctor that day.');

        $ins = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, reason) VALUES (?,?,?, 'pending', ?)");
        $ins->execute([$patientId, $doctorId, $dt, $reason]);
        // notify patient
        try { $pdo->prepare("INSERT INTO notifications (user_id,type,message) VALUES (?, 'Appointment', ?)")
                  ->execute([$user['id'], "Your appointment request with Dr. {$doctor['first_name']} {$doctor['last_name']} on ".date('M j, Y', strtotime($date))." was submitted."]); } catch (Exception $e) {}
        $_SESSION['flash'] = 'Appointment request submitted!';
        header("Location: my_appointments.php"); exit();
    } catch (Exception $e) { $error = $e->getMessage(); }
}

$page = ['role'=>'patient','active'=>'book','title'=>'Book Appointment',
         'subtitle'=>'Request a consultation with Dr. '.htmlspecialchars($doctor['first_name'].' '.$doctor['last_name']),'user'=>$user];
require_once __DIR__ . '/../includes/dash_layout.php';
dash_head($page);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>

<div class="grid grid--2-1">
  <!-- Booking form -->
  <div class="panel">
    <div class="panel__head"><div><h3>Schedule your visit</h3><p class="sub">Pick a day & time that works for you</p></div></div>
    <div class="panel__body">
      <?php if ($error): ?><div class="alert alert--error"><i data-lucide="alert-triangle"></i><?= $e($error) ?></div><?php endif; ?>
      <form method="POST">
        <div class="form-row">
          <div class="field">
            <label>Appointment date</label>
            <input class="input input--plain" type="date" name="appointment_date" id="appointment_date" required min="<?= date('Y-m-d') ?>">
          </div>
          <div class="field">
            <label>Time slot</label>
            <select class="input" name="appointment_time" id="appointment_time" required disabled><option value="">Select a date first</option></select>
          </div>
        </div>
        <div class="field">
          <label>Reason for visit</label>
          <textarea class="input input--plain" name="reason" rows="4" required placeholder="Describe your symptoms or reason for consultation…"></textarea>
        </div>
        <div class="alert alert--info"><i data-lucide="info"></i>Available days:
          <?= $available_days ? $e(implode(', ', $available_days)) : 'this doctor has not published availability yet — pick any weekday and the clinic will confirm.' ?></div>
        <button class="btn btn--brand btn--lg btn--block" type="submit"><i data-lucide="calendar-check"></i> Request appointment</button>
      </form>
    </div>
  </div>

  <!-- Doctor summary -->
  <div class="panel" style="align-self:start">
    <div class="panel__body" style="text-align:center">
      <img src="<?= $e(ihms_avatar('doctor',$doctorId)) ?>" alt="" style="width:110px;height:110px;border-radius:20px;object-fit:cover;margin:0 auto .9rem;box-shadow:var(--shadow-md)">
      <h3 style="margin:0">Dr. <?= $e($doctor['first_name'].' '.$doctor['last_name']) ?></h3>
      <div class="docard__spec" style="margin-bottom:.6rem"><?= $e($doctor['specialization']) ?></div>
      <div class="chips" style="justify-content:center;padding:0 0 1rem">
        <span class="chip"><i data-lucide="briefcase"></i><?= (int)($doctor['experience_years'] ?? 0) ?>+ yrs</span>
        <span class="chip"><i data-lucide="star"></i><?= $doctor['rating'] ? number_format($doctor['rating'],1) : 'New' ?></span>
        <?php if (!empty($doctor['consultation_fee'])): ?><span class="chip"><i data-lucide="banknote"></i>$<?= (int)$doctor['consultation_fee'] ?></span><?php endif; ?>
      </div>
    </div>
    <div class="docard__meta" style="border-top:1px solid var(--mist-50);padding-top:1rem">
      <?php if (!empty($doctor['qualification'])): ?><div><i data-lucide="award"></i><?= $e($doctor['qualification']) ?></div><?php endif; ?>
      <?php if (!empty($doctor['hospital_affiliation'])): ?><div><i data-lucide="building-2"></i><?= $e($doctor['hospital_affiliation']) ?></div><?php endif; ?>
      <?php if (!empty($doctor['available_days'])): ?><div><i data-lucide="calendar-days"></i><?= $e($doctor['available_days']) ?></div><?php endif; ?>
      <?php if (!empty($doctor['about'])): ?><div style="align-items:flex-start"><i data-lucide="info"></i><span><?= $e($doctor['about']) ?></span></div><?php endif; ?>
    </div>
  </div>
</div>

<?php
$js = "
var slotsByDay=".json_encode($slots_by_day).", availableDays=".json_encode($available_days).";
var dateEl=document.getElementById('appointment_date'), timeEl=document.getElementById('appointment_time');
if(dateEl){ dateEl.addEventListener('change',function(){
  var d=new Date(this.value+'T00:00'); var day=d.toLocaleDateString('en-US',{weekday:'long'});
  timeEl.innerHTML='';
  if(availableDays.length && availableDays.indexOf(day)===-1){ timeEl.innerHTML='<option value=\"\">Not available on '+day+'</option>'; timeEl.disabled=true; return; }
  var slots=slotsByDay[day]||[];
  if(!slots.length){ // no published slots: offer default hourly slots 09-16
    timeEl.disabled=false; timeEl.innerHTML='<option value=\"\">Select time</option>';
    for(var h=9;h<=16;h++){ var v=(h<10?'0'+h:h)+':00:00'; timeEl.innerHTML+='<option value=\"'+v+'\">'+(h>12?h-12:h)+':00 '+(h>=12?'PM':'AM')+'</option>'; }
    return;
  }
  timeEl.disabled=false; timeEl.innerHTML='<option value=\"\">Select time</option>';
  slots.forEach(function(s){ var label=s.start_time.substring(0,5)+' - '+s.end_time.substring(0,5);
    timeEl.innerHTML+='<option value=\"'+s.start_time+'\">'+label+'</option>'; });
});}
";
dash_foot($page, $js);
