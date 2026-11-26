<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__.'/includes/auth.php';


if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {
        $error = 'Session expired. Please refresh and try again.';
    } else {

        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($full_name === '' || $username === '' ||
            $password === '' || $confirm_password === '') {

            $error = 'Please complete all fields.';

        } elseif (strlen($full_name) > 100 || strlen($username) > 50) {

            $error = 'Name or username is too long.';

        } elseif (strlen($password) < 8) {
    $error = 'Password must contain at least 8 characters.';
} elseif (!preg_match('/[A-Za-z]/', $password)) {
    $error = 'Password must contain at least one alphabet.';
} elseif (!preg_match('/[0-9]/', $password)) {
    $error = 'Password must contain at least one number.';
} elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
    $error = 'Password must contain at least one special character.';
} elseif ($password !== $confirm_password) {
    $error = 'Passwords do not match.';
}

         else {

            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $query = db()->prepare(
                    'INSERT INTO users
                    (username, password_hash, full_name, role, is_active)
                    VALUES (?, ?, ?, ?, 1)'
                );

                $query->execute([
                    $username,
                    $hash,
                    $full_name,
                    'customer'
                ]);

                header('Location: login.php?registered=1');
                exit;

            } catch (PDOException $ex) {

                if ($ex->getCode() === '23000') {
                    $error = 'This username is already taken.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMS Registration</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-body">

<div class="login-container">

    <section class="branding">
        <div class="logo">◇</div>
        <h1>Inventory<br>Management System</h1>
        <p>Create your customer account.</p>
    </section>

    <section class="login-section">
        <div class="login-box">

            <h2>Register</h2>
            <p class="muted">Create a new customer account</p>

            <?php if ($error): ?>
                <div class="alert"><?=e($error)?></div>
            <?php endif; ?>

            <form method="POST" action="register.php">

                <input type="hidden" name="csrf_token"
                       value="<?=e(csrf_token())?>">

                <label>Full Name</label>
                <input type="text" name="full_name"
                       placeholder="Enter your full name"
                       maxlength="100" required>

                <label>Username</label>
                <input type="text" name="username"
                       placeholder="Choose a username"
                       maxlength="50" required>

                <label>Password</label>
<div class="password-wrapper">
    <input type="password" name="password" id="password"
           placeholder="At least 8 characters"
           minlength="8" required>
    <button type="button" id="togglePassword">Show</button>
</div>

                <div class="password-hint">
    <p>Password must contain:</p>
    <ul>
        <li id="req-length">✗ At least 8 characters</li>
        <li id="req-letter">✗ At least one alphabet</li>
        <li id="req-number">✗ At least one number</li>
        <li id="req-special">✗ At least one special character</li>
    </ul>
</div>
                <label>Confirm Password</label>
                <input type="password" name="confirm_password"
                       placeholder="Confirm your password"
                       minlength="8" required>

                <button type="submit">Create Account</button>

                <p class="muted">
                    Already have an account?
                    <a href="login.php">Login</a>
                </p>

            </form>

        </div>
    </section>

</div>
<script>
const passwordInput = document.querySelector('input[name="password"]');

passwordInput.addEventListener('input', function () {
    const password = this.value;

    document.getElementById('req-length').classList.toggle('valid', password.length >= 8);
    document.getElementById('req-letter').classList.toggle('valid', /[A-Za-z]/.test(password));
    document.getElementById('req-number').classList.toggle('valid', /[0-9]/.test(password));
    document.getElementById('req-special').classList.toggle('valid', /[^A-Za-z0-9]/.test(password));

    document.getElementById('req-length').textContent = (password.length >= 8 ? '✓' : '✗') + ' At least 8 characters';
    document.getElementById('req-letter').textContent = (/[A-Za-z]/.test(password) ? '✓' : '✗') + ' At least one alphabet';
    document.getElementById('req-number').textContent = (/[0-9]/.test(password) ? '✓' : '✗') + ' At least one number';
    document.getElementById('req-special').textContent = (/[^A-Za-z0-9]/.test(password) ? '✓' : '✗') + ' At least one special character';
});

const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', function () {
    const passwordInput = document.getElementById('password');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        this.textContent = 'Hide';
    } else {
        passwordInput.type = 'password';
        this.textContent = 'Show';
    }
});
</script>
</body>
</html>
