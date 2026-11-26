<?php
require_once __DIR__.'/includes/auth.php'; require_login();
$map=['admin'=>'admin/dashboard.php','staff'=>'staff/dashboard.php','customer'=>'customer/dashboard.php'];
header('Location: '.$map[$_SESSION['role']]);exit;
