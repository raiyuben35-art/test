<?php require_once __DIR__.'/auth.php'; $pageTitle=$pageTitle??'Dashboard';?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($pageTitle)?> | IMS</title><link rel="stylesheet" href="/Inventory-Management-System/assets/css/style.css"></head><body class="app">
<header class="topbar"><a href="/Inventory-Management-System/dashboard.php">◇ IMS</a><span><?=e($_SESSION['full_name']??'')?> · <?=e($_SESSION['role']??'')?></span><a href="/Inventory-Management-System/logout.php">Log out</a></header>
