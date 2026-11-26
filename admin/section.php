<?php
require_once __DIR__.'/../includes/auth.php';require_role(['admin']);
$allowed=['Products', 'Categories', 'Suppliers', 'Users', 'Reports'];$page=$_GET['page']??'Products';
if(!in_array($page,$allowed,true)){http_response_code(404);exit('Page not found');}
$pageTitle=$page;require_once __DIR__.'/../includes/header.php';require_once __DIR__.'/../includes/sidebar.php';?>
<main><small>ADMIN</small><h1><?=e($page)?></h1><section class="panel"><h2><?=e($page)?></h2><p>This module will be connected to the inventory database in the next development stage.</p></section></main></div></body></html>