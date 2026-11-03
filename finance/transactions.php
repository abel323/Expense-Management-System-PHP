<?php
// transactions.php
session_start();
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// -------------------------------------------------------------------
// 1. FILTER CONTROLS & QUERY BUILDER
// -------------------------------------------------------------------
$filter_date_from = trim($_GET['date_from'] ?? '');
$filter_date_to   = trim($_GET['date_to'] ?? '');
$filter_type      = trim($_GET['type'] ?? '');
$filter_category  = trim($_GET['category_id'] ?? '');
$filter_keyword   = trim($_GET['keyword'] ?? '');

$where_conditions = ["t.user_id = :user_id"];
$query_params     = [':user_id' => $user_id];

if (!empty($filter_date_from)) {
    $where_conditions[] = "t.transaction_date >= :date_from";
    $query_params[':date_from'] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "t.transaction_date <= :date_to";
    $query_params[':date_to'] = $filter_date_to;
}

if (!empty($filter_type) && in_array($filter_type, ['income', 'expense', 'transfer'])) {
    $where_conditions[] = "t.type = :type";
    $query_params[':type'] = $filter_type;
}

if (!empty($filter_category)) {
    $where_conditions[] = "t.category_id = :category_id";
    $query_params[':category_id'] = $filter_category;
}

if (!empty($filter_keyword)) {
    $where_conditions[] = "(t.description LIKE :kw OR a.name LIKE :kw OR dest.name LIKE :kw OR c.name LIKE :kw)";
    $query_params[':kw'] = '%' . $filter_keyword . '%';
}

$where_sql = implode(' AND ', $where_conditions);

// -------------------------------------------------------------------
// 2. CSV / EXCEL EXPORT HANDLER (INCLUDES APPLIED FILTERS)
// -------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=transactions_export_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Date', 'Type', 'Category', 'Source Account', 'Destination Account', 'Amount ($)', 'Description']);

    $stmt_export = $pdo->prepare("
        SELECT t.id, t.transaction_date, t.type, 
               c.name AS category_name, 
               a.name AS account_name, 
               dest.name AS to_account_name, 
               t.amount, t.description
        FROM transactions t
        INNER JOIN accounts a ON t.from_account_id = a.id
        LEFT JOIN accounts dest ON t.to_account_id = dest.id
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE {$where_sql}
        ORDER BY t.transaction_date DESC, t.id DESC
    ");
    $stmt_export->execute($query_params);

    while ($row = $stmt_export->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['transaction_date'],
            strtoupper($row['type']),
            $row['category_name'] ?? ($row['type'] === 'transfer' ? 'Internal Transfer' : 'Uncategorized'),
            $row['account_name'],
            $row['to_account_name'] ?? 'N/A',
            number_format($row['amount'], 2, '.', ''),
            $row['description']
        ]);
    }
    fclose($output);
    exit();
}

$is_print_mode = (isset($_GET['action']) && $_GET['action'] === 'print');

// -------------------------------------------------------------------
// 3. FORM SUBMISSION (ADD TRANSACTION)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_transaction') {
    $type = trim($_POST['type'] ?? 'expense');
    $from_account_id = intval($_POST['from_account_id'] ?? 0);
    $to_account_id = !empty($_POST['to_account_id']) ? intval($_POST['to_account_id']) : null;
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $amount = floatval($_POST['amount'] ?? 0);
    $transaction_date = trim($_POST['transaction_date'] ?? date('Y-m-d'));
    $description = trim($_POST['description'] ?? '');

    if ($amount <= 0) {
        $errors[] = "Transaction amount must be greater than zero.";
    }
    if ($from_account_id <= 0) {
        $errors[] = "Please select a valid primary account.";
    }
    if ($type === 'transfer') {
        if (empty($to_account_id)) {
            $errors[] = "Destination account is required for transfers.";
        } elseif ($from_account_id === $to_account_id) {
            $errors[] = "Source and destination accounts cannot be the same.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, from_account_id, to_account_id, category_id, type, amount, transaction_date, description)
                VALUES (:user_id, :from_account_id, :to_account_id, :category_id, :type, :amount, :transaction_date, :description)
            ");
            $stmt->execute([
                ':user_id'          => $user_id,
                ':from_account_id'  => $from_account_id,
                ':to_account_id'    => ($type === 'transfer') ? $to_account_id : null,
                ':category_id'      => ($type !== 'transfer') ? $category_id : null,
                ':type'             => $type,
                ':amount'           => $amount,
                ':transaction_date' => $transaction_date,
                ':description'      => $description
            ]);

            if ($type === 'income') {
                $update_src = $pdo->prepare("UPDATE accounts SET current_balance = current_balance + :amount WHERE id = :id AND user_id = :user_id");
                $update_src->execute([':amount' => $amount, ':id' => $from_account_id, ':user_id' => $user_id]);
            } elseif ($type === 'expense') {
                $update_src = $pdo->prepare("UPDATE accounts SET current_balance = current_balance - :amount WHERE id = :id AND user_id = :user_id");
                $update_src->execute([':amount' => $amount, ':id' => $from_account_id, ':user_id' => $user_id]);
            } elseif ($type === 'transfer') {
                $update_src = $pdo->prepare("UPDATE accounts SET current_balance = current_balance - :amount WHERE id = :id AND user_id = :user_id");
                $update_src->execute([':amount' => $amount, ':id' => $from_account_id, ':user_id' => $user_id]);

                $update_dest = $pdo->prepare("UPDATE accounts SET current_balance = current_balance + :amount WHERE id = :id AND user_id = :user_id");
                $update_dest->execute([':amount' => $amount, ':id' => $to_account_id, ':user_id' => $user_id]);
            }

            $pdo->commit();
            $success_message = "Transaction recorded and account balance(s) updated successfully!";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Transaction failed and changes were rolled back: " . $e->getMessage();
        }
    }
}

// Fetch Accounts
$stmt_accounts = $pdo->prepare("SELECT id, name, current_balance FROM accounts WHERE user_id = :user_id AND is_active = 1 ORDER BY name ASC");
$stmt_accounts->execute([':user_id' => $user_id]);
$accounts = $stmt_accounts->fetchAll();

// Fetch Categories
$stmt_categories = $pdo->prepare("SELECT id, name, type, icon FROM categories WHERE user_id = :user_id OR user_id IS NULL ORDER BY name ASC");
$stmt_categories->execute([':user_id' => $user_id]);
$categories = $stmt_categories->fetchAll();

// Fetch Transactions with Active Filters
$limit_clause = $is_print_mode ? "" : "LIMIT 100";
$stmt_tx = $pdo->prepare("
    SELECT t.*, 
           a.name AS account_name, 
           dest.name AS to_account_name, 
           c.name AS category_name, 
           c.icon AS category_icon, 
           c.color AS category_color
    FROM transactions t
    INNER JOIN accounts a ON t.from_account_id = a.id
    LEFT JOIN accounts dest ON t.to_account_id = dest.id
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE {$where_sql}
    ORDER BY t.transaction_date DESC, t.id DESC
    {$limit_clause}
");
$stmt_tx->execute($query_params);
$transactions = $stmt_tx->fetchAll();

// Build URL Query string for export links to persist filters
$export_query_params = $_GET;
unset($export_query_params['action']);
$export_qs = http_build_query($export_query_params);

// -------------------------------------------------------------------
// 4. PRINT / PDF MODE TEMPLATE
// -------------------------------------------------------------------
if ($is_print_mode):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Filtered Transaction Statement - Print / PDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11pt; background: #fff !important; }
            .container { max-width: 100% !important; width: 100% !important; padding: 0 !important; }
            .table { border: 1px solid #dee2e6 !important; }
        }
    </style>
</head>
<body class="bg-light p-4" onload="window.print()">
    <div class="container bg-white p-4 rounded shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="transactions.php?<?= $export_qs; ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Transactions</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i> Print / Save as PDF</button>
        </div>

        <div class="border-bottom pb-3 mb-4 d-flex justify-content-between align-items-end">
            <div>
                <h3 class="fw-bold mb-1">Financial Statement</h3>
                <p class="text-muted small mb-0">Generated on <?= date('F d, Y - h:i A'); ?></p>
            </div>
            <div class="text-end">
                <span class="fw-bold d-block">Transaction Report</span>
                <span class="text-muted small">Total Matching Records: <?= count($transactions); ?></span>
            </div>
        </div>

        <table class="table table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category / Details</th>
                    <th>Account</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="5" class="text-center py-4">No transactions matched your search criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?= date('Y-m-d', strtotime($tx['transaction_date'])); ?></td>
                            <td><span class="fw-bold"><?= strtoupper($tx['type']); ?></span></td>
                            <td>
                                <?= htmlspecialchars($tx['type'] === 'transfer' ? 'Transfer to ' . ($tx['to_account_name'] ?? 'N/A') : ($tx['category_name'] ?? 'Uncategorized')); ?>
                                <?php if (!empty($tx['description'])): ?>
                                    <small class="d-block text-muted"><?= htmlspecialchars($tx['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($tx['account_name']); ?></td>
                            <td class="text-end fw-bold">
                                <?= $tx['type'] === 'expense' ? '-' : ($tx['type'] === 'income' ? '+' : ''); ?>
                                $<?= number_format($tx['amount'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php 
exit(); 
endif;

require_once 'includes/header.php';
?>

<!-- Notifications -->
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

<!-- Header & Export Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Transaction Ledger</h4>
        <p class="text-muted small mb-0">Record, filter, and review cash flows and transfer entries.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="transactions.php?action=export_csv<?= !empty($export_qs) ? '&' . $export_qs : ''; ?>" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel (CSV)
        </a>
        <a href="transactions.php?action=print<?= !empty($export_qs) ? '&' . $export_qs : ''; ?>" target="_blank" class="btn btn-outline-secondary">
            <i class="bi bi-printer me-1"></i> Print / PDF
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
            <i class="bi bi-plus-circle me-1"></i> Log Transaction
        </button>
    </div>
</div>

<!-- Filter Control Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form action="transactions.php" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Search Keyword</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="keyword" class="form-control form-control-sm" placeholder="Description, account..." value="<?= htmlspecialchars($filter_keyword); ?>">
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="expense" <?= $filter_type === 'expense' ? 'selected' : ''; ?>>Expense</option>
                    <option value="income" <?= $filter_type === 'income' ? 'selected' : ''; ?>>Income</option>
                    <option value="transfer" <?= $filter_type === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id']; ?>" <?= $filter_category == $cat['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_date_from); ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_date_to); ?>">
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100" title="Apply Filter">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="transactions.php" class="btn btn-light btn-sm text-secondary border w-100" title="Reset Filters">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Ledger Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Type</th>
                        <th>Category / Details</th>
                        <th>Account</th>
                        <th class="text-end pe-4">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-funnel fs-1 d-block mb-2 text-secondary"></i>
                                No transactions matched your filter query.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td class="ps-4 small text-muted">
                                    <?= date('M d, Y', strtotime($tx['transaction_date'])); ?>
                                </td>
                                <td>
                                    <?php if ($tx['type'] === 'income'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-arrow-down-left me-1"></i>Income</span>
                                    <?php elseif ($tx['type'] === 'expense'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-arrow-up-right me-1"></i>Expense</span>
                                    <?php else: ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-arrow-left-right me-1"></i>Transfer</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($tx['type'] === 'transfer'): ?>
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="bi bi-arrow-left-right text-info"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold d-block text-dark">Internal Transfer</span>
                                                <small class="text-muted">To: <?= htmlspecialchars($tx['to_account_name'] ?? 'N/A'); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; background-color: <?= $tx['category_color'] ?? '#6c757d'; ?>;">
                                                <i class="bi <?= $tx['category_icon'] ?? 'bi-tag'; ?>"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold d-block text-dark"><?= htmlspecialchars($tx['category_name'] ?? 'Uncategorized'); ?></span>
                                                <?php if (!empty($tx['description'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($tx['description']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-secondary"><?= htmlspecialchars($tx['account_name']); ?></span>
                                </td>
                                <td class="text-end pe-4 fw-bold fs-6 <?= $tx['type'] === 'income' ? 'text-success' : ($tx['type'] === 'expense' ? 'text-danger' : 'text-info'); ?>">
                                    <?= $tx['type'] === 'expense' ? '-' : ($tx['type'] === 'income' ? '+' : ''); ?>
                                    $<?= number_format($tx['amount'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add New Transaction -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="addTransactionModalLabel"><i class="bi bi-receipt me-2"></i>New Transaction</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="transactions.php" method="POST">
                <input type="hidden" name="action" value="add_transaction">
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Transaction Type</label>
                        <select name="type" id="tx_type" class="form-select" onchange="toggleTransferFields()" required>
                            <option value="expense">Expense (-)</option>
                            <option value="income">Income (+)</option>
                            <option value="transfer">Internal Transfer (⇄)</option>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" id="lbl_from_account_id">Account</label>
                            <select name="from_account_id" class="form-select" required>
                                <option value="">-- Select Account --</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id']; ?>">
                                        <?= htmlspecialchars($acc['name']); ?> ($<?= number_format($acc['current_balance'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="field_to_account" style="display: none;">
                            <label class="form-label fw-bold">Destination Account</label>
                            <select name="to_account_id" class="form-select">
                                <option value="">-- Select Destination --</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['id']; ?>">
                                        <?= htmlspecialchars($acc['name']); ?> ($<?= number_format($acc['current_balance'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="field_category">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id']; ?>">
                                        [<?= ucfirst($cat['type']); ?>] <?= htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount ($)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Description / Notes</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g., Grocery shopping, Monthly salary, Coffee">
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTransferFields() {
    const type = document.getElementById('tx_type').value;
    const toAccountField = document.getElementById('field_to_account');
    const categoryField = document.getElementById('field_category');
    const accountLabel = document.getElementById('lbl_from_account_id');

    if (type === 'transfer') {
        toAccountField.style.display = 'block';
        categoryField.style.display = 'none';
        accountLabel.innerText = 'From Account';
    } else {
        toAccountField.style.display = 'none';
        categoryField.style.display = 'block';
        accountLabel.innerText = 'Account';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>