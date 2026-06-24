<?php
/**
 * IHMS database migration — idempotent and NON-destructive.
 *
 * Brings a freshly-imported `healthcare_db` (from healthcare_db.sql) up to what
 * the application code expects:
 *   - missing tables the code queries (notifications, reports, payments)
 *   - `medical_records` view over `medical_history`
 *   - patients.date_of_birth / patients.phone columns the forms use
 *   - blood-bank tables (blood_donors, blood_stock) + stock seed
 *   - demo accounts for each role
 *
 * Safe to run multiple times. Run from CLI:  php db_migrate.php
 * (or open /db_migrate.php once in the browser).
 */

$host = getenv('DB_HOST') ?: 'localhost';
$name = getenv('DB_NAME') ?: 'healthcare_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$cli  = (php_sapi_name() === 'cli');
$nl   = $cli ? "\n" : "<br>";
function say($m){ global $nl; echo $m.$nl; }

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Cannot connect to '$name'. Import healthcare_db.sql first. (" . $e->getMessage() . ")");
}

/* ---- 1. Missing application tables ---- */
$pdo->exec("CREATE TABLE IF NOT EXISTS notifications(
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'General',
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS reports(
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  report_type VARCHAR(100) NOT NULL,
  file_path VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'processing',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS payments(
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  appointment_id INT DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  payment_method VARCHAR(255) DEFAULT NULL,
  transaction_id VARCHAR(255) DEFAULT NULL,
  status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_patient (patient_id), KEY idx_appt (appointment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
say("Tables ensured: notifications, reports, payments");

/* ---- 2. medical_records view over medical_history ---- */
$pdo->exec("CREATE OR REPLACE VIEW medical_records AS SELECT * FROM medical_history");
say("View ensured: medical_records -> medical_history");

/* ---- 3. patients columns used by forms (MariaDB IF NOT EXISTS) ---- */
$cols = $pdo->query("SHOW COLUMNS FROM patients")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('date_of_birth', $cols)) {
    $pdo->exec("ALTER TABLE patients ADD COLUMN date_of_birth DATE NULL AFTER dob");
    $pdo->exec("UPDATE patients SET date_of_birth = dob WHERE dob IS NOT NULL");
    say("Added column: patients.date_of_birth");
}
if (!in_array('phone', $cols)) {
    $pdo->exec("ALTER TABLE patients ADD COLUMN phone VARCHAR(20) NULL AFTER postal_code");
    say("Added column: patients.phone");
}

/* ---- 4. Blood-bank tables (non-conflicting with main schema) ---- */
$pdo->exec("CREATE TABLE IF NOT EXISTS blood_donors(
  id INT PRIMARY KEY AUTO_INCREMENT,
  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  blood_group VARCHAR(5) NOT NULL,
  age INT NOT NULL,
  gender ENUM('male','female','other') NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(100),
  address TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS blood_stock(
  id INT PRIMARY KEY AUTO_INCREMENT,
  blood_group VARCHAR(5) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ((int)$pdo->query("SELECT COUNT(*) FROM blood_stock")->fetchColumn() === 0) {
    $ins = $pdo->prepare("INSERT INTO blood_stock (blood_group, quantity) VALUES (?, 0)");
    foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g) { $ins->execute([$g]); }
    say("Blood stock initialized (8 groups)");
}
say("Tables ensured: blood_donors, blood_stock");

/* ---- 5. Demo accounts (idempotent upsert) ---- */
function upsertUser(PDO $pdo, $fn, $ln, $email, $plainPw, $role) {
    $s = $pdo->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$email]);
    $id = $s->fetchColumn();
    $hash = password_hash($plainPw, PASSWORD_BCRYPT);
    if ($id) {
        $pdo->prepare("UPDATE users SET password=?, role=?, first_name=?, last_name=?, status='active' WHERE id=?")
            ->execute([$hash, $role, $fn, $ln, $id]);
        return $id;
    }
    $pdo->prepare("INSERT INTO users(first_name,last_name,email,password,role,status) VALUES(?,?,?,?,?,'active')")
        ->execute([$fn, $ln, $email, $hash, $role]);
    return $pdo->lastInsertId();
}
function ensureProfile(PDO $pdo, $table, $uid, array $extra = []) {
    $s = $pdo->prepare("SELECT id FROM `$table` WHERE user_id=?"); $s->execute([$uid]);
    if ($s->fetchColumn()) return;
    $cols = array_merge(['user_id'], array_keys($extra));
    $ph   = implode(',', array_fill(0, count($cols), '?'));
    $pdo->prepare("INSERT INTO `$table` (".implode(',', $cols).") VALUES ($ph)")
        ->execute(array_merge([$uid], array_values($extra)));
}

$admin = upsertUser($pdo,'Sample','Admin','sampleadmin@gmail.com','1234','admin');
ensureProfile($pdo,'admin_users',$admin,['department'=>'Administration','access_level'=>'super_admin']);

$pat = upsertUser($pdo,'Demo','Patient','patient@ihms.test','Passw0rd!','patient');
ensureProfile($pdo,'patients',$pat);

$doc = upsertUser($pdo,'Demo','Doctor','doctor@ihms.test','Passw0rd!','doctor');
ensureProfile($pdo,'doctors',$doc,[
    'specialization'=>'Cardiology','qualification'=>'MBBS, MD',
    'license_number'=>'LIC-DEMO-001','experience_years'=>10,'consultation_fee'=>500
]);
say("Demo accounts ready: sampleadmin@gmail.com/1234, patient@ihms.test & doctor@ihms.test /Passw0rd!");

say("");
say("Migration complete.");
