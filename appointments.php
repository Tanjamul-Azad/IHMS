<?php
// Superseded by role-specific appointment pages.
session_start();
$r = $_SESSION['user']['role'] ?? null;
$map = [
    'admin'   => 'admin/manage_appointments.php',
    'doctor'  => 'doctor/appointments.php',
    'patient' => 'patient/my_appointments.php',
];
header('Location: ' . ($map[$r] ?? 'dashboard.php'));
exit();
