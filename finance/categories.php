<?php
// categories.php
session_start();
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Handle Category Creation & Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? 'expense');
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $icon = trim($_POST['icon'] ?? 'bi-tag');
        $color = trim($_POST['color'] ?? '#6c757d');

        if (empty($name)) {
            $errors[] = "Category name is required.";
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO categories (user_id, parent_id, name, type, icon, color)
                    VALUES (:user_id, :parent_id, :name, :type, :icon, :color)
                ");
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':parent_id' => $parent_id,
                    ':name' => $name,
                    ':type' => $type,
                    ':icon' => $icon,
                    ':color' => $color
                ]);
                $success_message = "Category successfully created!";
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_category') {
        $category_id = intval($_POST['category_id'] ?? 0);
        if ($category_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)");
                $stmt->execute([':id' => $category_id, ':user_id' => $user_id]);
                $success_message = "Category removed successfully!";
            } catch (PDOException $e) {
                $errors[] = "Cannot delete category linked to active transactions or sub-categories.";
            }
        }
    }
}

// Fetch Parent Categories (parent_id IS NULL)
$stmt_parents = $pdo->prepare("
    SELECT * FROM categories 
    WHERE (user_id = :user_id OR user_id IS NULL) AND parent_id IS NULL 
    ORDER BY type ASC, name ASC
");
$stmt_parents->execute([':user_id' => $user_id]);
$parent_categories = $stmt_parents->fetchAll();

// Fetch Sub-categories
$stmt_subs = $pdo->prepare("
    SELECT * FROM categories 
    WHERE (user_id = :user_id OR user_id IS NULL) AND parent_id IS NOT NULL 
    ORDER BY name ASC
");
$stmt_subs->execute([':user_id' => $user_id]);
$all_subcategories = $stmt_subs->fetchAll();

// Group sub-categories by parent_id
$subcategories_by_parent = [];
foreach ($all_subcategories as $sub) {
    $subcategories_by_parent[$sub['parent_id']][] = $sub;
}

// Preset Icon Options for Icon Picker
$preset_icons = [
    'bi-tag' => 'Tag', 'bi-cart' => 'Shopping', 'bi-house' => 'Housing',
    'bi-car-front' => 'Transport', 'bi-briefcase' => 'Work / Salary',
    'bi-lightning' => 'Utilities', 'bi-heart-pulse' => 'Health',
    'bi-film' => 'Entertainment', 'bi-book' => 'Education',
    'bi-airplane' => 'Travel', 'bi-piggy-bank' => 'Savings',
    'bi-cup-hot' => 'Food & Dining', 'bi-phone' => 'Phone & Tech',
    'bi-gift' => 'Gifts', 'bi-arrow-down-up' => 'Transfer',
    'bi-tools' => 'Maintenance', 'bi-shield-check' => 'Insurance'
];

require_once 'includes/header.php';
?>

<!-- System Alerts -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Header section -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Transaction Categories</h4>
        <p class="text-muted small mb-0">Organize ledger income, expenses, and transfers into hierarchical categories and sub-categories.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-lg me-1"></i> Add New Category
    </button>
</div>

<!-- Categories Breakdown Grid -->
<div class="row g-4 mb-4">
    <?php foreach (['expense' => 'Expense Categories', 'income' => 'Income Categories', 'transfer' => 'Transfer Types'] as $cat_type => $type_label): ?>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi <?= $cat_type == 'income' ? 'bi-arrow-down-left-circle text-success' : ($cat_type == 'expense' ? 'bi-arrow-up-right-circle text-danger' : 'bi-arrow-left-right text-info'); ?> me-2"></i>
                        <?= $type_label; ?>
                    </h6>
                    <span class="badge bg-light text-dark border">
                        <?= count(array_filter($parent_categories, fn($c) => $c['type'] === $cat_type)); ?> Primary
                    </span>
                </div>

                <div class="card-body p-3">
                    <div class="accordion accordion-flush" id="accordion_<?= $cat_type; ?>">
                        <?php 
                        $filtered_parents = array_filter($parent_categories, fn($c) => $c['type'] === $cat_type);
                        if (empty($filtered_parents)): 
                        ?>
                            <div class="text-center py-4 text-muted small">
                                No <?= $cat_type; ?> categories set up yet.
                            </div>
                        <?php else: ?>
                            <?php foreach ($filtered_parents as $parent): ?>
                                <?php $has_subs = isset($subcategories_by_parent[$parent['id']]); ?>
                                <div class="border rounded p-3 mb-2 bg-light-subtle">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 34px; height: 34px; background-color: <?= htmlspecialchars($parent['color']); ?>;">
                                                <i class="bi <?= htmlspecialchars($parent['icon']); ?>"></i>
                                            </div>
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($parent['name']); ?></span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <?php if ($parent['user_id'] !== null): ?>
                                                <form action="categories.php" method="POST" onsubmit="return confirm('Delete this category?');" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?= $parent['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0"><i class="bi bi-trash"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">System</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Render Subcategories if available -->
                                    <?php if ($has_subs): ?>
                                        <div class="mt-2 ps-4 pt-2 border-top">
                                            <?php foreach ($subcategories_by_parent[$parent['id']] as $sub): ?>
                                                <div class="d-flex justify-content-between align-items-center py-1 small">
                                                    <span class="text-secondary"><i class="bi bi-arrow-return-right me-1"></i><?= htmlspecialchars($sub['name']); ?></span>
                                                    <?php if ($sub['user_id'] !== null): ?>
                                                        <form action="categories.php" method="POST" onsubmit="return confirm('Delete subcategory?');" class="d-inline">
                                                            <input type="hidden" name="action" value="delete_category">
                                                            <input type="hidden" name="category_id" value="<?= $sub['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0"><i class="bi bi-x-circle"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Add Category / Subcategory -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="addCategoryModalLabel"><i class="bi bi-tags me-2"></i>New Category or Subcategory</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="categories.php" method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Fast Food, Streaming Services, Freelance" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Parent Category (Optional)</label>
                            <select name="parent_id" class="form-select">
                                <option value="">-- None (Primary Category) --</option>
                                <?php foreach ($parent_categories as $p_cat): ?>
                                    <option value="<?= $p_cat['id']; ?>">
                                        [<?= ucfirst($p_cat['type']); ?>] <?= htmlspecialchars($p_cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Bootstrap Icon Picker</label>
                            <select name="icon" class="form-select" required>
                                <?php foreach ($preset_icons as $icon_class => $icon_name): ?>
                                    <option value="<?= $icon_class; ?>"><?= $icon_name; ?> (<?= $icon_class; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Badge Color</label>
                            <input type="color" name="color" class="form-control form-control-color w-100" value="#0d6efd" title="Choose color">
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>