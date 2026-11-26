<?php
require_once __DIR__.'/../config/database.php';
if(session_status()!==PHP_SESSION_ACTIVE){
 session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);
 session_start();
}
function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
function is_logged_in():bool{return isset($_SESSION['user_id'],$_SESSION['role']);}
function require_login():void{if(!is_logged_in()){header('Location: /Inventory-Management-System/login.php');exit;}}
function require_role(array $roles):void{
 require_login();
 if(!in_array($_SESSION['role'],$roles,true)){http_response_code(403);exit('403 - Access denied.');}
}
function csrf_token():string{if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));return $_SESSION['csrf_token'];}
function verify_csrf():bool{return isset($_POST['csrf_token'],$_SESSION['csrf_token'])&&hash_equals($_SESSION['csrf_token'],$_POST['csrf_token']);}
