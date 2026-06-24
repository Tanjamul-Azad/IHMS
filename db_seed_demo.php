<?php
/**
 * IHMS demo data seeder — makes dashboards look alive (charts + lists).
 * Idempotent: guarded by a marker user, so re-running does nothing.
 * Run:  php db_seed_demo.php      (or open /db_seed_demo.php once)
 *
 * Adds demo doctors, patients, appointments (spread over ~6 months),
 * prescriptions, payments, health logs and medical history. The existing
 * demo accounts (patient@ihms.test / doctor@ihms.test) get extra activity
 * so their dashboards are rich.
 */
$pdo = new PDO("mysql:host=" . (getenv('DB_HOST') ?: 'localhost') . ";dbname=" . (getenv('DB_NAME') ?: 'healthcare_db') . ";charset=utf8mb4",
    getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$cli = php_sapi_name() === 'cli'; $nl = $cli ? "\n" : "<br>";

$m = $pdo->prepare("SELECT id FROM users WHERE email=?"); $m->execute(['seed_marker@ihms.test']);
if ($m->fetchColumn()) { echo "Demo data already seeded — nothing to do.$nl"; exit; }

$pw = password_hash('Passw0rd!', PASSWORD_BCRYPT);
function mkUser($pdo,$pw,$fn,$ln,$email,$role){
    $s=$pdo->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$email]);
    if($id=$s->fetchColumn()) return $id;
    $pdo->prepare("INSERT INTO users(first_name,last_name,email,password,role,status) VALUES(?,?,?,?,?,'active')")
        ->execute([$fn,$ln,$email,$pw,$role]);
    return $pdo->lastInsertId();
}

/* ---- Doctors ---- */
$docDefs = [
    ['Ralph','Edwards','Cardiology','MBBS, MD (Cardiology)','City Heart Institute',14,600],
    ['Sophia','Bennett','Neurology','MBBS, DM (Neurology)','NeuroCare Center',11,750],
    ['Albert','Boje','Pediatrics','MBBS, DCH','Sunrise Children Hospital',9,450],
    ['Leslie','Alexander','Dermatology','MBBS, MD (Skin)','Glow Skin Clinic',8,500],
    ['Floyd','Miles','Orthopedics','MBBS, MS (Ortho)','Bone & Joint Center',16,700],
    ['Courtney','Henry','General Medicine','MBBS','IHMS General Hospital',6,350],
];
$doctorIds = [];
foreach ($docDefs as $i=>$d) {
    $uid = mkUser($pdo,$pw,$d[0],$d[1],'dr.'.strtolower($d[1]).'@ihms.test','doctor');
    $s=$pdo->prepare("SELECT id FROM doctors WHERE user_id=?"); $s->execute([$uid]);
    $did=$s->fetchColumn();
    if(!$did){
        $pdo->prepare("INSERT INTO doctors(user_id,specialization,qualification,license_number,experience_years,consultation_fee,hospital_affiliation,available_days,about,rating,total_ratings)
                       VALUES(?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$uid,$d[2],$d[3],'LIC-SEED-'.(100+$i),$d[5],$d[6],$d[4],'Mon-Fri',
                       'Experienced '.$d[2].' specialist dedicated to compassionate, evidence-based care.',
                       round(4 + (mt_rand(0,9)/10),1), mt_rand(40,180)]);
        $did=$pdo->lastInsertId();
    }
    $doctorIds[]=$did;
}
// include the existing demo doctor
$s=$pdo->query("SELECT d.id FROM doctors d JOIN users u ON d.user_id=u.id WHERE u.email='doctor@ihms.test'");
if($demoDoc=$s->fetchColumn()) $doctorIds[]=$demoDoc;

/* ---- Patients ---- */
$patDefs = [
    ['Emma','Wilson','Female','O+'],['James','Carter','Male','A+'],['Olivia','Brown','Female','B+'],
    ['Liam','Davis','Male','AB+'],['Ava','Martinez','Female','O-'],['Noah','Garcia','Male','A-'],
    ['Sophia','Lee','Female','B-'],['Mason','Clark','Male','O+'],['Isabella','Lewis','Female','A+'],
    ['Lucas','Walker','Male','AB-'],
];
$patientIds=[];
foreach ($patDefs as $i=>$p){
    $uid=mkUser($pdo,$pw,$p[0],$p[1],'pt.'.strtolower($p[1]).$i.'@ihms.test','patient');
    $s=$pdo->prepare("SELECT id FROM patients WHERE user_id=?"); $s->execute([$uid]);
    $pid=$s->fetchColumn();
    if(!$pid){
        $dob=date('Y-m-d', strtotime('-'.mt_rand(20,60).' years -'.mt_rand(0,360).' days'));
        $pdo->prepare("INSERT INTO patients(user_id,dob,date_of_birth,gender,blood_group,phone,city,address)
                       VALUES(?,?,?,?,?,?,?,?)")
            ->execute([$uid,$dob,$dob,$p[2],$p[3],'+1 555 0'.mt_rand(100,999),'Springfield',mt_rand(10,99).' Main St']);
        $pid=$pdo->lastInsertId();
    }
    $patientIds[]=$pid;
}
$s=$pdo->query("SELECT p.id FROM patients p JOIN users u ON p.user_id=u.id WHERE u.email='patient@ihms.test'");
$demoPat=$s->fetchColumn(); if($demoPat) $patientIds[]=$demoPat;

/* ---- Appointments (spread across last 6 months + upcoming) ---- */
$reasons=['Routine check-up','Follow-up consultation','Chest pain evaluation','Skin rash','Headache & migraine','Child vaccination','Joint pain','Blood pressure review','Fever & cold','Annual physical'];
$apptIns=$pdo->prepare("INSERT INTO appointments(patient_id,doctor_id,appointment_date,status,reason,notes) VALUES(?,?,?,?,?,?)");
$payIns =$pdo->prepare("INSERT INTO payments(patient_id,appointment_id,amount,payment_method,transaction_id,status,payment_date) VALUES(?,?,?,?,?,?,?)");
$apptCount=0;
for($k=0;$k<70;$k++){
    $pid=$patientIds[array_rand($patientIds)];
    $did=$doctorIds[array_rand($doctorIds)];
    $daysOffset = mt_rand(0,1) ? -mt_rand(1,175) : mt_rand(1,16); // mostly past, some future
    $dt=date('Y-m-d H:i:s', strtotime(($daysOffset<0?'':'+').$daysOffset.' days', strtotime(sprintf('%02d:00',mt_rand(9,16)))));
    if($daysOffset<0){ $status=['completed','completed','completed','cancelled'][mt_rand(0,3)]; }
    else { $status=['pending','confirmed','confirmed'][mt_rand(0,2)]; }
    $apptIns->execute([$pid,$did,$dt,$status,$reasons[array_rand($reasons)],'']);
    $aid=$pdo->lastInsertId(); $apptCount++;
    if($status==='completed'){
        $payIns->execute([$pid,$aid,mt_rand(35,90)*10,['Card','Cash','Insurance'][mt_rand(0,2)],'TXN'.strtoupper(substr(md5($aid),0,8)),'completed',$dt]);
    }
}
// Guarantee the demo patient & doctor have upcoming + past activity
for($k=0;$k<6;$k++){
    $apptIns->execute([$demoPat,$doctorIds[array_rand($doctorIds)],date('Y-m-d H:i:s',strtotime('+'.($k+1).' days 10:30')),'confirmed',$reasons[array_rand($reasons)],'']);
}
for($k=0;$k<8;$k++){
    $apptIns->execute([$patientIds[array_rand($patientIds)],$demoDoc,date('Y-m-d H:i:s',strtotime('-'.mt_rand(1,40).' days '.mt_rand(9,16).':00')),'completed',$reasons[array_rand($reasons)],'']);
}
for($k=0;$k<5;$k++){
    $apptIns->execute([$patientIds[array_rand($patientIds)],$demoDoc,date('Y-m-d H:i:s',strtotime('+'.($k+1).' days '.(9+$k).':00')),['pending','confirmed'][mt_rand(0,1)],$reasons[array_rand($reasons)],'']);
}

/* ---- Prescriptions ---- */
$meds=[['Atorvastatin','10mg','Once daily at night'],['Amoxicillin','500mg','Three times a day for 7 days'],
       ['Metformin','500mg','Twice daily after meals'],['Ibuprofen','400mg','As needed for pain'],
       ['Cetirizine','10mg','Once daily'],['Omeprazole','20mg','Before breakfast'],['Paracetamol','650mg','Every 6 hours if fever']];
$presIns=$pdo->prepare("INSERT INTO prescriptions(patient_id,doctor_id,medication,dosage,instructions,prescription_date,status) VALUES(?,?,?,?,?,?,?)");
for($k=0;$k<24;$k++){
    $m=$meds[array_rand($meds)];
    $presIns->execute([$patientIds[array_rand($patientIds)],$doctorIds[array_rand($doctorIds)],$m[0],$m[1],$m[2],
        date('Y-m-d',strtotime('-'.mt_rand(0,120).' days')),['Active','Active','Completed'][mt_rand(0,2)]]);
}
for($k=0;$k<4;$k++){ $m=$meds[array_rand($meds)]; $presIns->execute([$demoPat,$demoDoc,$m[0],$m[1],$m[2],date('Y-m-d',strtotime('-'.$k.' days')),'Active']); }

/* ---- Health logs for the demo patient (last 14 days) ---- */
$hlIns=$pdo->prepare("INSERT INTO health_logs(patient_id,log_date,weight_kg,blood_pressure_systolic,blood_pressure_diastolic,glucose_mgdl,temperature_c,heart_rate,mood) VALUES(?,?,?,?,?,?,?,?,?)");
$moods=['Good','Tired','Energetic','Okay','Great'];
for($d=13;$d>=0;$d--){
    $hlIns->execute([$demoPat,date('Y-m-d H:i:s',strtotime("-$d days 08:00")),
        round(70+ sin($d/3)*1.5 + mt_rand(-4,4)/10,1),
        mt_rand(115,132), mt_rand(72,85), mt_rand(88,116), round(36.4+mt_rand(0,8)/10,1),
        mt_rand(64,82), $moods[array_rand($moods)]]);
}

/* ---- Medical history for demo patient ---- */
$mhIns=$pdo->prepare("INSERT INTO medical_history(patient_id,doctor_id,diagnosis,treatment,notes,visit_date,next_visit_date) VALUES(?,?,?,?,?,?,?)");
$mhDefs=[['Hypertension (Stage 1)','Lifestyle changes + low-dose medication','Monitor BP weekly'],
         ['Seasonal allergic rhinitis','Antihistamines','Avoid known allergens'],
         ['Vitamin D deficiency','Supplementation','Recheck in 3 months']];
foreach($mhDefs as $i=>$h){
    $mhIns->execute([$demoPat,$doctorIds[array_rand($doctorIds)],$h[0],$h[1],$h[2],
        date('Y-m-d',strtotime('-'.(($i+1)*30).' days')),date('Y-m-d',strtotime('+'.(($i+1)*15).' days'))]);
}

/* ---- Blood stock demo quantities ---- */
try {
    foreach (['A+'=>24,'A-'=>9,'B+'=>18,'B-'=>6,'AB+'=>7,'AB-'=>3,'O+'=>31,'O-'=>12] as $g=>$q){
        $pdo->prepare("UPDATE blood_stock SET quantity=? WHERE blood_group=?")->execute([$q,$g]);
    }
} catch (Exception $e) {}

mkUser($pdo,$pw,'Seed','Marker','seed_marker@ihms.test','patient'); // marker
echo "Seeded: ".count($doctorIds)." doctors, ".count($patientIds)." patients, ~$apptCount appointments + prescriptions, payments, health logs.$nl";
echo "Done.$nl";
