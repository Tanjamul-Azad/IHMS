<?php
// Superseded by role-specific prescription pages.
session_start();
$r = $_SESSION['user']['role'] ?? null;
$map = ['doctor' => 'doctor/prescriptions.php', 'patient' => 'patient/prescriptions.php'];
header('Location: ' . ($map[$r] ?? 'dashboard.php'));
exit();
