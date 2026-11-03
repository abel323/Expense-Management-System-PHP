<?php
// index.php
session_start();
require_once 'config/db.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// 1. Calculate Total Liquid Accounts Balance
$stmt_acc = $pdo->prepare("SELECT SUM(current_balance) AS total_liquid FROM accounts WHERE user_id = :user_id AND is_active = 1");
$stmt_acc->execute([':user_id' => $user_id]);
$liquid_cash = $stmt_acc->fetchColumn() ?: 0;

// 2. Calculate Total Investments & Assets Valuation
$stmt_assets = $pdo->prepare("SELECT SUM(quantity * current_market_price) AS total_assets FROM assets WHERE user_id = :user_id");
$stmt_assets->execute([':user_id' => $user_id]);
$total_assets = $stmt_assets->fetchColumn() ?: 0;

// 3. Calculate Total Outstanding Debts (Borrowed vs Lent)
$stmt_debts = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN type = 'borrowed' THEN (total_amount - paid_amount) ELSE 0 END) AS total_liabilities,
        SUM(CASE WHEN type = 'lent' THEN (total_amount - paid_amount) ELSE 0 END) AS total_receivables
    FROM debts 
    WHERE user_id = :user_id AND status != 'settled'
");
$stmt_debts->execute([':user_id' => $user_id]);
$debt_summary = $stmt_debts->fetch();
$total_liabilities = $debt_summary['total_liabilities'] ?: 0;
$total_receivables = $debt_summary['total_receivables'] ?: 0;

// Net Worth = Cash + Assets + Receivables - Liabilities
$net_worth = ($liquid_cash + $total_assets + $total_receivables) - $total_liabilities;

// 4. Current Month Income & Expense Summary
$current_month = date('Y-m');
$stmt_flow = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS monthly_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS monthly_expense
    FROM transactions 
    WHERE user_id = :user_id 
      AND DATE_FORMAT(transaction_date, '%Y-%m') = :current_month
");
$stmt_flow->execute([':user_id' => $user_id, ':current_month' => $current_month]);
$cash_flow = $stmt_flow->fetch();
$monthly_income = $cash_flow['monthly_income'] ?: 0;
$monthly_expense = $cash_flow['monthly_expense'] ?: 0;

// 5. Fetch Accounts for Quick Glance Card
$stmt_user_accs = $pdo->prepare("SELECT * FROM accounts WHERE user_id = :user_id AND is_active = 1 ORDER BY current_balance DESC LIMIT 4");
$stmt_user_accs->execute([':user_id' => $user_id]);
$accounts_list = $stmt_user_accs->fetchAll();

// 6. Expense Breakdown by Category (Current Month)
$stmt_cat_chart = $pdo->prepare("
    SELECT c.name, SUM(t.amount) AS category_total, c.color 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = :user_id 
      AND t.type = 'expense' 
      AND DATE_FORMAT(t.transaction_date, '%Y-%m') = :current_month
    GROUP BY c.id 
    ORDER BY category_total DESC 
    LIMIT 5
");
$stmt_cat_chart->execute([':user_id' => $user_id, ':current_month' => $current_month]);
$category_chart_data = $stmt_cat_chart->fetchAll();

// Extract Chart Data Arrays
$chart_labels = array_column($category_chart_data, 'name');
$chart_values = array_column($category_chart_data, 'category_total');
$chart_colors = array_column($category_chart_data, 'color');

// 7. Recent 5 Transactions
$stmt_recent_tx = $pdo->prepare("
    SELECT t.*, c.name as category_name, c.icon as category_icon, c.color as category_color
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = :user_id
    ORDER BY t.transaction_date DESC
    LIMIT 5
");
$stmt_recent_tx->execute([':user_id' => $user_id]);
$recent_transactions = $stmt_recent_tx->fetchAll();
?>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Net Worth Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 p-3 bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-white-50 small fw-bold">TOTAL NET WORTH</span>
                <i class="bi bi-shield-check fs-4 text-white-50"></i>
            </div>
            <h2 class="fw-bold mb-1">$<?= number_format($net_worth, 2); ?></h2>
            <small class="text-white-50"><i class="bi bi-info-circle me-1"></i>Assets + Liquid Cash - Debts</small>
        </div>
    </div>

    <!-- Liquid Cash Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">LIQUID CASH & WALLETS</span>
                <i class="bi bi-bank fs-4 text-success"></i>
            </div>
            <h2 class="fw-bold mb-1 text-dark">$<?= number_format($liquid_cash, 2); ?></h2>
            <small class="text-muted">Across <?= count($accounts_list); ?> active accounts</small>
        </div>
    </div>

    <!-- Monthly Income Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">MONTHLY INCOME</span>
                <i class="bi bi-arrow-down-left-circle fs-4 text-success"></i>
            </div>
            <h2 class="fw-bold mb-1 text-success">+$<?= number_format($monthly_income, 2); ?></h2>
            <small class="text-muted">This Month (<?= date('F Y'); ?>)</small>
        </div>
    </div>

    <!-- Monthly Expenses Card -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted small fw-bold">MONTHLY EXPENSES</span>
                <i class="bi bi-arrow-up-right-circle fs-4 text-danger"></i>
            </div>
            <h2 class="fw-bold mb-1 text-danger">-$<?= number_format($monthly_expense, 2); ?></h2>
            <small class="text-muted">This Month (<?= date('F Y'); ?>)</small>
        </div>
    </div>
</div>

<!-- Main Section: Charts and Accounts Overview -->
<div class="row g-4 mb-4">
    <!-- Category Expense Distribution Chart -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-secondary">Expense Breakdown by Category (<?= date('M Y'); ?>)</h6>
                <a href="categories.php" class="btn btn-sm btn-light border">Manage</a>
            </div>
            <div style="max-height: 280px; position: relative;" class="d-flex justify-content-center">
                <?php if (empty($category_chart_data)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-pie-chart fs-1 d-block mb-2"></i>
                        No expense records found for this month.
                    </div>
                <?php else: ?>
                    <canvas id="expenseChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Accounts Summary -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-secondary">My Accounts & Wallets</h6>
                <a href="accounts.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i> Add Account</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($accounts_list)): ?>
                    <div class="text-center py-4 text-muted">No accounts added yet.</div>
                <?php else: ?>
                    <?php foreach ($accounts_list as $acc): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-light p-2 rounded text-primary fs-5">
                                    <i class="bi <?= $acc['type'] == 'mobile_wallet' ? 'bi-phone' : ($acc['type'] == 'credit_card' ? 'bi-credit-card' : 'bi-bank'); ?>"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($acc['name']); ?></div>
                                    <small class="text-muted uppercase"><?= strtoupper($acc['type']); ?> <?= $acc['account_number_last4'] ? '(*'.$acc['account_number_last4'].')' : ''; ?></small>
                                </div>
                            </div>
                            <span class="fw-bold text-dark">$<?= number_format($acc['current_balance'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions Table -->
<div class="card border-0 shadow-sm p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 text-secondary">Recent Ledger Transactions</h6>
        <a href="transactions.php" class="btn btn-sm btn-light border">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_transactions)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No transactions recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_transactions as $tx): ?>
                        <tr>
                            <td class="text-muted small"><?= date('M d, Y', strtotime($tx['transaction_date'])); ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi <?= $tx['category_icon'] ?: 'bi-tag'; ?> me-1" style="color: <?= $tx['category_color'] ?: '#6c757d'; ?>"></i>
                                    <?= htmlspecialchars($tx['category_name'] ?: 'Uncategorized'); ?>
                                </span>
                            </td>
                            <td class="text-dark fw-medium"><?= htmlspecialchars($tx['description'] ?: '—'); ?></td>
                            <td>
                                <?php if ($tx['type'] == 'income'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success">Income</span>
                                <?php elseif ($tx['type'] == 'expense'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger">Expense</span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info border border-info">Transfer</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold <?= $tx['type'] == 'income' ? 'text-success' : ($tx['type'] == 'expense' ? 'text-danger' : 'text-dark'); ?>">
                                <?= $tx['type'] == 'income' ? '+' : ($tx['type'] == 'expense' ? '-' : ''); ?>$<?= number_format($tx['amount'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Chart.js Script Initialization -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('expenseChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_labels); ?>,
                datasets: [{
                    data: <?= json_encode($chart_values); ?>,
                    backgroundColor: <?= json_encode(!empty($chart_colors) ? $chart_colors : ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0']); ?>,
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
});
</script>