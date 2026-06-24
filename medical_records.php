<?php
// Superseded by role-specific medical record pages.
session_start();
$r = $_SESSION['user']['role'] ?? null;
$map = ['doctor' => 'doctor/medical-records.php', 'patient' => 'patient/medical-records.php'];
header('Location: ' . ($map[$r] ?? 'dashboard.php'));
exit();
