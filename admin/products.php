
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin']);

$pdo = db();
$message = '';
$error = '';

// ADD, UPDATE AND DELETE PRODUCTS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if (!verify_csrf()) {
    throw new Exception('Invalid security token. Please refresh the page.');
}
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['product_name'] ?? '');
            $sku = trim($_POST['sku'] ?? '');
            $category = !empty($_POST['category_id'])
                ? (int)$_POST['category_id'] : null;
            $supplier = !empty($_POST['supplier_id'])
                ? (int)$_POST['supplier_id'] : null;
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['unit_price'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            $reorder = (int)($_POST['reorder_level'] ?? 5);

            if ($name === '' || $sku === '' || $price < 0 ||
                $quantity < 0 || $reorder < 0) {
                throw new Exception('Please enter valid product details.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    "UPDATE products SET product_name=?, sku=?,
                    category_id=?, supplier_id=?, description=?,
                    unit_price=?, quantity=?, reorder_level=?
                    WHERE id=?"
                );

                $stmt->execute([
                    $name, $sku, $category, $supplier, $description,
                    $price, $quantity, $reorder, $id
                ]);

                $message = 'Product updated successfully.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO products
                    (product_name, sku, category_id, supplier_id,
                    description, unit_price, quantity, reorder_level)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $name, $sku, $category, $supplier, $description,
                    $price, $quantity, $reorder
                ]);

                $message = 'Product added successfully.';
            }
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);

            $message = 'Product deleted successfully.';
        }
    } catch (Throwable $e) {
        $error = 'Operation failed: ' . $e->getMessage();
    }
}

// GET CATEGORIES AND SUPPLIERS
$categories = $pdo->query(
    "SELECT id, category_name FROM categories ORDER BY category_name"
)->fetchAll();

$suppliers = $pdo->query(
    "SELECT id, supplier_name FROM suppliers ORDER BY supplier_name"
)->fetchAll();

// EDIT PRODUCT
$editProduct = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

// SEARCH PRODUCTS
$search = trim($_GET['search'] ?? '');

$sql = "SELECT p.*, c.category_name, s.supplier_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN suppliers s ON p.supplier_id = s.id";

$params = [];

if ($search !== '') {
    $sql .= " WHERE p.product_name LIKE ?
              OR p.sku LIKE ?
              OR c.category_name LIKE ?";
    $term = "%$search%";
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="products-page" style="margin-left:250px; width:calc(100% - 250px); padding:35px; box-sizing:border-box; position:relative;">
    <small>ADMIN</small>
    <h1>Product Management</h1>
    <p class="muted">Add, edit, search and manage your inventory products.</p>

    <?php if ($message): ?>
        <div class="notice success">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2><?= $editProduct ? 'Edit Product' : 'Add New Product' ?></h2>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">

            <input type="hidden" name="id"
                   value="<?= e($editProduct['id'] ?? '') ?>">

            <div class="product-form">

                <label>
                    Product Name
                    <input type="text" name="product_name"
                           value="<?= e($editProduct['product_name'] ?? '') ?>"
                           required>
                </label>

                <label>
                    SKU
                    <input type="text" name="sku"
                           value="<?= e($editProduct['sku'] ?? '') ?>"
                           required>
                </label>

                <label>
                    Category
                    <select name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category['id']) ?>"
                                <?= (($editProduct['category_id'] ?? '') == $category['id']) ? 'selected' : '' ?>>
                                <?= e($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Supplier
                    <select name="supplier_id">
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= e($supplier['id']) ?>"
                                <?= (($editProduct['supplier_id'] ?? '') == $supplier['id']) ? 'selected' : '' ?>>
                                <?= e($supplier['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Unit Price (NPR)
                    <input type="number" name="unit_price"
                           min="0" step="0.01"
                           value="<?= e($editProduct['unit_price'] ?? '0') ?>"
                           required>
                </label>

                <label>
                    Quantity
                    <input type="number" name="quantity"
                           min="0"
                           value="<?= e($editProduct['quantity'] ?? '0') ?>"
                           required>
                </label>

                <label>
                    Reorder Level
                    <input type="number" name="reorder_level"
                           min="0"
                           value="<?= e($editProduct['reorder_level'] ?? '5') ?>"
                           required>
                </label>

                <label>
                    Description
                    <textarea name="description"><?= e($editProduct['description'] ?? '') ?></textarea>
                </label>

            </div>

            <button type="submit">
                <?= $editProduct ? 'Update Product' : 'Add Product' ?>
            </button>

            <?php if ($editProduct): ?>
                <a href="products.php">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>All Products</h2>

        <form method="GET" class="search-form">
            <input type="search" name="search"
                   placeholder="Search product, SKU or category..."
                   value="<?= e($search) ?>">
            <button type="submit">Search</button>
            <a href="products.php">Clear</a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= e($product['id']) ?></td>
                            <td><?= e($product['product_name']) ?></td>
                            <td><?= e($product['sku']) ?></td>
                            <td><?= e($product['category_name'] ?? '-') ?></td>
                            <td><?= e($product['supplier_name'] ?? '-') ?></td>
                            <td>Rs. <?= number_format((float)$product['unit_price'], 2) ?></td>
                            <td><?= e($product['quantity']) ?></td>

                            <td>
                                <?php if ($product['quantity'] <= $product['reorder_level']): ?>
                                    <span class="low-stock">Low Stock</span>
                                <?php else: ?>
                                    <span class="in-stock">In Stock</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="?edit=<?= e($product['id']) ?>">Edit</a>

                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('Delete this product?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                      <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id"
                                           value="<?= e($product['id']) ?>">
                                    <button type="submit" class="delete-btn">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$products): ?>
                        <tr>
                            <td colspan="9">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

</body>
</html>
