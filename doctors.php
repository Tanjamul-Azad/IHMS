<?php
// Superseded by admin/manage_doctors.php.
session_start();
$r = $_SESSION['user']['role'] ?? null;
$map = ['admin' => 'admin/manage_doctors.php'];
header('Location: ' . ($map[$r] ?? 'dashboard.php'));
exit();
