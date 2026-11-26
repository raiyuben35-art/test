
<?php

require_once __DIR__ . '/../includes/auth.php';

require_role(['admin']);

$pageTitle = 'Admin Dashboard';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="main" style="margin-left:250px; width:calc(100% - 250px); padding:35px; box-sizing:border-box; position:relative;">

    <small>ADMIN</small>

    <h1>Admin Dashboard</h1>

    <p class="muted">
        Welcome, <?= e($_SESSION['full_name']) ?>.
        Manage products, users, suppliers and reports.
    </p>

    <!-- Dashboard Statistics -->
    <div class="stats">

        <div class="stat">
            <span>Total Products</span>
            <strong>250</strong>
        </div>

        <div class="stat">
            <span>Total Stock</span>
            <strong>1,842</strong>
        </div>

        <div class="stat">
            <span>Low Stock Items</span>
            <strong>3</strong>
        </div>

        <div class="stat">
            <span>Suppliers</span>
            <strong>5</strong>
        </div>

    </div>

    <!-- Quick Actions -->
    <section class="panel">

        <h2>Quick Actions</h2>

        <p>
            Manage products, users, suppliers and reports.
        </p>

    </section>

</main>

</body>
</html>
