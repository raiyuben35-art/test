
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin']);

$pdo = db();

$message = '';
$error = '';


// ======================================
// ADD, UPDATE AND DELETE CATEGORIES
// ======================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        if (!verify_csrf()) {
            throw new Exception('Invalid security token. Please refresh.');
        }

        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $category_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($action === 'save') {

            if ($category_name === '') {
                throw new Exception('Category name is required.');
            }

            // Check duplicate category names
            $stmt = $pdo->prepare(
                "SELECT id FROM categories
                 WHERE category_name = ? AND id != ?"
            );

            $stmt->execute([$category_name, $id]);

            if ($stmt->fetch()) {
                throw new Exception('This category already exists.');
            }

            if ($id > 0) {

                // Update category
                $stmt = $pdo->prepare(
                    "UPDATE categories
                     SET category_name = ?, description = ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $category_name,
                    $description,
                    $id
                ]);

                $message = 'Category updated successfully.';

            } else {

                // Add category
                $stmt = $pdo->prepare(
                    "INSERT INTO categories
                     (category_name, description)
                     VALUES (?, ?)"
                );

                $stmt->execute([
                    $category_name,
                    $description
                ]);

                $message = 'Category added successfully.';
            }
        }

        elseif ($action === 'delete') {

            if ($id <= 0) {
                throw new Exception('Invalid category selected.');
            }

            // Check whether category is being used
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM products WHERE category_id = ?"
            );

            $stmt->execute([$id]);

            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception(
                    'Cannot delete this category because products are using it.'
                );
            }

            $stmt = $pdo->prepare(
                "DELETE FROM categories WHERE id = ?"
            );

            $stmt->execute([$id]);

            $message = 'Category deleted successfully.';

        } else {

            throw new Exception('Invalid action.');

        }

    } catch (Throwable $e) {

        $error = $e->getMessage();

    }
}


// ======================================
// EDIT CATEGORY
// ======================================

$editCategory = null;

if (isset($_GET['edit'])) {

    $stmt = $pdo->prepare(
        "SELECT * FROM categories WHERE id = ?"
    );

    $stmt->execute([(int)$_GET['edit']]);

    $editCategory = $stmt->fetch();

}


// ======================================
// GET ALL CATEGORIES
// ======================================

$categories = $pdo->query(
    "SELECT c.*,
    (SELECT COUNT(*) FROM products p
     WHERE p.category_id = c.id) AS product_count
     FROM categories c
     ORDER BY c.id DESC"
)->fetchAll();


// ======================================
// INCLUDE HEADER AND SIDEBAR
// ======================================

$pageTitle = 'Category Management';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>


<!-- MAIN CONTENT -->

<main class="products-page" style="margin-left:250px; width:calc(100% - 250px); padding:35px; box-sizing:border-box; position:relative;">

    <small>ADMIN</small>

    <h1>Category Management</h1>

    <p class="muted">
        Add, edit and organize your product categories.
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


    <!-- ADD / EDIT CATEGORY -->

    <section class="panel">

        <h2>
            <?= $editCategory ? 'Edit Category' : 'Add New Category' ?>
        </h2>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="save"
            >

            <input
                type="hidden"
                name="id"
                value="<?= e($editCategory['id'] ?? '') ?>"
            >

            <div class="product-form">

                <label>
                    Category Name

                    <input
                        type="text"
                        name="category_name"
                        placeholder="Enter category name"
                        value="<?= e($editCategory['category_name'] ?? '') ?>"
                        required
                    >
                </label>


                <label>
                    Description

                    <textarea
                        name="description"
                        placeholder="Enter category description"
                    ><?= e($editCategory['description'] ?? '') ?></textarea>
                </label>

            </div>


            <button type="submit">
                <?= $editCategory ? 'Update Category' : 'Add Category' ?>
            </button>


            <?php if ($editCategory): ?>

                <a href="categories.php">Cancel Edit</a>

            <?php endif; ?>

        </form>

    </section>


    <!-- CATEGORY LIST -->

    <section class="panel">

        <h2>All Categories</h2>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Total Products</th>
                        <th>Actions</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($categories as $category): ?>

                        <tr>

                            <td>
                                <?= e($category['id']) ?>
                            </td>

                            <td>
                                <?= e($category['category_name']) ?>
                            </td>

                            <td>
                                <?= e($category['description'] ?? '-') ?>
                            </td>

                            <td>
                                <?= e($category['product_count']) ?>
                            </td>

                            <td>

                                <a href="?edit=<?= e($category['id']) ?>">
                                    Edit
                                </a>


                                <form
                                    method="POST"
                                    style="display:inline"
                                    onsubmit="return confirm('Delete this category?')"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= e($category['id']) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="delete-btn"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>


                    <?php if (!$categories): ?>

                        <tr>
                            <td colspan="5">No categories found.</td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>


</body>
</html>
