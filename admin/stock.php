
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin', 'staff']);

$pdo = db();

$message = '';
$error = '';


// ======================================
// HANDLE STOCK-IN AND STOCK-OUT
// ======================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if (!verify_csrf()) {
            throw new Exception('Invalid security token. Please refresh.');
        }

        $product_id = (int)($_POST['product_id'] ?? 0);
        $type = $_POST['transaction_type'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $note = trim($_POST['note'] ?? '');

        // Validate input
        if ($product_id <= 0 || $quantity <= 0) {
            throw new Exception('Select a product and enter a valid quantity.');
        }

        if (!in_array($type, ['stock_in', 'stock_out'], true)) {
            throw new Exception('Invalid transaction type.');
        }

        // Start database transaction
        $pdo->beginTransaction();

        // Lock product row
        $stmt = $pdo->prepare(
            "SELECT quantity FROM products WHERE id = ? FOR UPDATE"
        );

        $stmt->execute([$product_id]);

        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception('Product not found.');
        }

        $current_quantity = (int)$product['quantity'];

        // Check available stock
        if ($type === 'stock_out' && $quantity > $current_quantity) {
            throw new Exception('Insufficient stock available.');
        }

        // Calculate new stock quantity
        if ($type === 'stock_in') {
            $new_quantity = $current_quantity + $quantity;
        } else {
            $new_quantity = $current_quantity - $quantity;
        }

        // Update product quantity
        $stmt = $pdo->prepare(
            "UPDATE products SET quantity = ? WHERE id = ?"
        );

        $stmt->execute([$new_quantity, $product_id]);

        // Save transaction history
        $stmt = $pdo->prepare(
            "INSERT INTO stock_transactions
            (product_id, transaction_type, quantity, note)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $product_id,
            $type,
            $quantity,
            $note
        ]);

        // Complete transaction
        $pdo->commit();

        $message = 'Stock transaction recorded successfully.';

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();
    }
}


// ======================================
// GET PRODUCTS
// ======================================

$products = $pdo->query(
    "SELECT id, product_name, sku, quantity
     FROM products
     ORDER BY product_name"
)->fetchAll();


// ======================================
// GET TRANSACTION HISTORY
// ======================================

$transactions = $pdo->query(
    "SELECT t.*, p.product_name, p.sku
     FROM stock_transactions t
     JOIN products p ON t.product_id = p.id
     ORDER BY t.id DESC"
)->fetchAll();


// ======================================
// INCLUDE HEADER AND SIDEBAR
// ======================================

$pageTitle = 'Stock Management';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>


<!-- ======================================
     MAIN CONTENT
====================================== -->

<main class="products-page" style="margin-left:250px; width:calc(100% - 250px); padding:35px; box-sizing:border-box; position:relative;">

    <small>INVENTORY</small>

    <h1>Stock Management</h1>

    <p class="muted">
        Record incoming and outgoing stock.
    </p>


    <!-- SUCCESS MESSAGE -->

    <?php if ($message): ?>

        <div class="notice success">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if ($error): ?>

        <div class="notice error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <!-- ======================================
         STOCK TRANSACTION FORM
    ====================================== -->

    <section class="panel">

        <h2>Record Stock Transaction</h2>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="product-form">

                <!-- PRODUCT -->

                <label>
                    Product

                    <select name="product_id" required>

                        <option value="">Select Product</option>

                        <?php foreach ($products as $product): ?>

                            <option value="<?= e($product['id']) ?>">

                                <?= e($product['product_name']) ?>
                                (<?= e($product['sku']) ?>)
                                — Stock: <?= e($product['quantity']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>


                <!-- TRANSACTION TYPE -->

                <label>
                    Transaction Type

                    <select name="transaction_type" required>

                        <option value="stock_in">Stock-In</option>

                        <option value="stock_out">Stock-Out</option>

                    </select>

                </label>


                <!-- QUANTITY -->

                <label>
                    Quantity

                    <input
                        type="number"
                        name="quantity"
                        min="1"
                        required
                    >

                </label>


                <!-- NOTE -->

                <label>
                    Note

                    <textarea
                        name="note"
                        placeholder="Optional transaction details"
                    ></textarea>

                </label>

            </div>


            <button type="submit">
                Record Transaction
            </button>

        </form>

    </section>


    <!-- ======================================
         TRANSACTION HISTORY
    ====================================== -->

    <section class="panel">

        <h2>Transaction History</h2>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Note</th>
                        <th>Date</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($transactions as $transaction): ?>

                        <tr>

                            <td>
                                <?= e($transaction['id']) ?>
                            </td>

                            <td>
                                <?= e($transaction['product_name']) ?>
                            </td>

                            <td>
                                <?= e($transaction['sku']) ?>
                            </td>

                            <td>
                                <?= e($transaction['transaction_type']) ?>
                            </td>

                            <td>
                                <?= e($transaction['quantity']) ?>
                            </td>

                            <td>
                                <?= e($transaction['note'] ?? '-') ?>
                            </td>

                            <td>
                                <?= e($transaction['created_at']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>


                    <?php if (!$transactions): ?>

                        <tr>
                            <td colspan="7">
                                No transactions found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>


</body>
</html>
