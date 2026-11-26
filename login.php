<?php
require_once __DIR__.'/includes/auth.php';
if(is_logged_in()){header('Location: dashboard.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf()) $error='Session expired. Refresh and try again.';
 else {
  $username=trim($_POST['username']??''); $password=$_POST['password']??''; $role=$_POST['role']??'';
  if($username===''||$password===''||!in_array($role,['admin','staff','customer'],true)) $error='Please enter all fields.';
  else {
   try{
    $q=db()->prepare('SELECT id,username,password_hash,full_name,role FROM users WHERE username=? AND role=? AND is_active=1 LIMIT 1');
    $q->execute([$username,$role]); $u=$q->fetch();
    if($u&&password_verify($password,$u['password_hash'])){
     session_regenerate_id(true);
     $_SESSION['user_id']=(int)$u['id']; $_SESSION['username']=$u['username']; $_SESSION['full_name']=$u['full_name']; $_SESSION['role']=$u['role'];
     header('Location: dashboard.php');exit;
    }
    $error='Invalid username, password or selected role.';
   }catch(PDOException $ex){$error='Database not ready. Run setup.php once, then refresh.';}
  }
 }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>IMS Login</title><link rel="stylesheet" href="assets/css/style.css"></head><body class="login-body">
<div class="login-container">
<section class="branding"><div class="logo">◇</div><h1>Inventory<br>Management System</h1><p>Manage your stock. Simplify your business.</p></section>
<section class="login-section"><div class="login-box"><h2>Login</h2><p class="muted">Please enter your credentials</p>
<?php if($error):?><div class="alert"><?=e($error)?></div><?php endif;?>
<form method="POST" action="login.php">
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<label for="username">Username</label><input id="username" name="username" type="text" placeholder="Enter username" autocomplete="username" required>
<label for="password">Password</label><input id="password" name="password" type="password" placeholder="Enter password" autocomplete="current-password" required>
<label for="role">Select Role</label><select id="role" name="role" required><option value="">-- Choose Role --</option><option value="admin">Administrator</option><option value="staff">Staff</option><option value="customer">Customer</option></select>
<button type="submit">Login</button><a class="forgot" href="#" onclick="alert('Please contact the system administrator to reset your password.');return false;">Forgot Password?</a>
</form></div></section></div></body></html>
