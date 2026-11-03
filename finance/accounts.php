<?php
// accounts.php
session_start();
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Handle Form Submission for Adding a New Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_account') {
    $account_name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $institution_id = !empty($_POST['institution_id']) ? intval($_POST['institution_id']) : null;
    $account_number_last4 = !empty($_POST['account_number_last4']) ? trim($_POST['account_number_last4']) : null;
    $initial_balance = isset($_POST['initial_balance']) ? floatval($_POST['initial_balance']) : 0.00;
    $currency = trim($_POST['currency'] ?? 'USD');

    // Basic Validation
    if (empty($account_name)) {
        $errors[] = "Account name is required.";
    }
    if (empty($type)) {
        $errors[] = "Account type is required.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert new account record
            $stmt = $pdo->prepare("
                INSERT INTO accounts (user_id, institution_id, name, account_number_last4, type, currency, current_balance)
                VALUES (:user_id, :institution_id, :name, :last4, :type, :currency, :balance)
            ");
            $stmt->execute([
                ':user_id' => $user_id,
                ':institution_id' => $institution_id,
                ':name' => $account_name,
                ':last4' => $account_number_last4,
                ':type' => $type,
                ':currency' => $currency,
                ':balance' => $initial_balance
            ]);

            $new_account_id = $pdo->lastInsertId();

            // Log account creation in Audit Logs
            $audit_stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (:user_id, 'ACCOUNT_CREATED', :details)");
            $audit_stmt->execute([
                ':user_id' => $user_id,
                ':details' => "Created account '$account_name' with starting balance of $initial_balance $currency"
            ]);

            $pdo->commit();
            $success_message = "Account successfully added!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Financial Institutions for Modal Dropdown
$stmt_inst = $pdo->prepare("SELECT * FROM institutions ORDER BY name ASC");
$stmt_inst->execute();
$institutions = $stmt_inst->fetchAll();

// Fetch Accounts List with Joined Institution Information
$stmt_acc = $pdo->prepare("
    SELECT a.*, i.name AS institution_name 
    FROM accounts a
    LEFT JOIN institutions i ON a.institution_id = i.id
    WHERE a.user_id = :user_id AND a.is_active = 1
    ORDER BY a.created_at DESC
");
$stmt_acc->execute([':user_id' => $user_id]);
$accounts = $stmt_acc->fetchAll();

// Calculate Summary Totals
$total_checking = 0;
$total_savings = 0;
$total_wallets = 0;
$total_credit = 0;

foreach ($accounts as $acc) {
    switch ($acc['type']) {
        case 'checking': $total_checking += $acc['current_balance']; break;
        case 'savings': $total_savings += $acc['current_balance']; break;
        case 'mobile_wallet': $total_wallets += $acc['current_balance']; break;
        case 'credit_card': $total_credit += $acc['current_balance']; break;
    }
}
$grand_total = $total_checking + $total_savings + $total_wallets + $total_credit;

// Include dynamic layout header
require_once 'includes/header.php';
?>

<!-- Alerts -->
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

<!-- Top Action Header & Total Summary Cards -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Accounts & Wallets</h4>
        <p class="text-muted small mb-0">Manage liquid funds across commercial banks, mobile money, and credit cards.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
        <i class="bi bi-plus-lg me-1"></i> Add New Account
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3">
            <span class="text-muted small fw-bold">TOTAL LIQUIDITY</span>
            <h3 class="fw-bold text-primary mb-0">$<?= number_format($grand_total, 2); ?></h3>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3">
            <span class="text-muted small fw-bold">CHECKING & SAVINGS</span>
            <h3 class="fw-bold text-dark mb-0">$<?= number_format($total_checking + $total_savings, 2); ?></h3>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3">
            <span class="text-muted small fw-bold">MOBILE WALLETS</span>
            <h3 class="fw-bold text-success mb-0">$<?= number_format($total_wallets, 2); ?></h3>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3">
            <span class="text-muted small fw-bold">CREDIT CARDS</span>
            <h3 class="fw-bold text-danger mb-0">$<?= number_format($total_credit, 2); ?></h3>
        </div>
    </div>
</div>

<!-- Accounts Data Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Account Name</th>
                        <th>Institution</th>
                        <th>Type</th>
                        <th>Account No (Last 4)</th>
                        <th>Currency</th>
                        <th class="text-end pe-4">Current Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-bank fs-2 d-block mb-2"></i>
                                No active bank accounts or wallets found. Click "Add New Account" to begin.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $acc): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark">
                                    <i class="bi <?= $acc['type'] == 'mobile_wallet' ? 'bi-phone' : ($acc['type'] == 'credit_card' ? 'bi-credit-card' : 'bi-bank'); ?> me-2 text-primary"></i>
                                    <?= htmlspecialchars($acc['name']); ?>
                                </td>
                                <td class="text-secondary">
                                    <?= htmlspecialchars($acc['institution_name'] ?: 'Independent / Cash'); ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= strtoupper(str_replace('_', ' ', $acc['type'])); ?>
                                    </span>
                                </td>
                                <td class="text-muted">
                                    <?= $acc['account_number_last4'] ? '•••• ' . htmlspecialchars($acc['account_number_last4']) : '—'; ?>
                                </td>
                                <td>
                                    <span class="fw-semibold text-secondary"><?= htmlspecialchars($acc['currency']); ?></span>
                                </td>
                                <td class="text-end pe-4 fw-bold text-dark fs-6">
                                    $<?= number_format($acc['current_balance'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add New Account -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="addAccountModalLabel"><i class="bi bi-bank me-2"></i>Add New Financial Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="accounts.php" method="POST">
                <input type="hidden" name="action" value="add_account">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Account Label / Title</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Chase Checking, Telebirr Wallet" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Account Type</label>
                            <select name="type" class="form-select" required>
                                <option value="checking">Checking Account</option>
                                <option value="savings">Savings Account</option>
                                <option value="mobile_wallet">Mobile Wallet</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="loan">Personal Loan / Debt Account</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Financial Institution</label>
                            <select name="institution_id" class="form-select">
                                <option value="">-- None / Independent --</option>
                                <?php foreach ($institutions as $inst): ?>
                                    <option value="<?= $inst['id']; ?>"><?= htmlspecialchars($inst['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Initial Balance</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="initial_balance" class="form-control" value="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Currency</label>
                            <input type="text" name="currency" class="form-control" value="USD" maxlength="3" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Account Number (Last 4 Digits)</label>
                        <input type="text" name="account_number_last4" class="form-control" placeholder="1234" maxlength="4">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>