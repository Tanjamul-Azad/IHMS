<?php
// Superseded by role-specific pages (admin/manage_patients.php, doctor/patients.php).
session_start();
$r = $_SESSION['user']['role'] ?? null;
$map = ['admin' => 'admin/manage_patients.php', 'doctor' => 'doctor/patients.php'];
header('Location: ' . ($map[$r] ?? 'dashboard.php'));
exit();
