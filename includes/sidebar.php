
<?php

$menus = [
    'admin' => [
        'Dashboard' => '/Inventory-Management-System/admin/dashboard.php',
        'Products' => '/Inventory-Management-System/admin/products.php',
        'Stock Management' => '/Inventory-Management-System/admin/stock.php',
        'Categories' => '/Inventory-Management-System/admin/categories.php',
        'Suppliers' => '/Inventory-Management-System/admin/suppliers.php',
        'Users' => '/Inventory-Management-System/admin/users.php',
        'Reports' => '/Inventory-Management-System/admin/reports.php'
    ],

    'staff' => [
        'Dashboard' => '/Inventory-Management-System/staff/dashboard.php',
        'Stock Management' => '/Inventory-Management-System/admin/stock.php'
    ],

    'customer' => [
        'Home' => '/Inventory-Management-System/customer/section.php',
        'Products' => '/Inventory-Management-System/customer/section.php?page=products',
        'My Orders' => '/Inventory-Management-System/customer/section.php?page=orders'
    ]
];

?>

<aside class="sidebar">
    <h2>◇ IMS</h2>

    <?php foreach ($menus[$_SESSION['role']] ?? [] as $n => $u): ?>
        <a href="<?= e($u) ?>"><?= e($n) ?></a>
    <?php endforeach; ?>

</aside>
