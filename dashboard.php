<?php
// Central dashboard router: sends each user to their role-specific dashboard.
// Lets every "dashboard.php" / "../dashboard.php" link in the app resolve correctly.
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user']['role'])) {
    header('Location: login.php');
    exit();
}

switch ($_SESSION['user']['role']) {
    case 'admin':            header('Location: admin/dashboard.php'); break;
    case 'doctor':           header('Location: doctor/doctor_dashboard.php'); break;
    case 'patient':          header('Location: patient/patient_dashboard.php'); break;
    case 'blood_bank_admin': header('Location: blood_bank/admin.php'); break;
    default:                 session_destroy(); header('Location: login.php');
}
exit();
