<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth_check.php';

// Active page detection for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RealFinance Pro - Wealth & Expense Tracker</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js for Dashboard Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --sidebar-active: #0d6efd;
            --body-bg: #f8fafc;
        }

        body {
            background-color: var(--body-bg);
            font-family: 'Segoe UI', Roboto, -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        #wrapper {
            display: flex;
            width: 100%;
        }

        #sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: #f8fafc;
            min-height: 100vh;
            transition: margin-left 0.3s ease-in-out;
            z-index: 1000;
        }

        #sidebar .sidebar-brand {
            padding: 1.25rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            background-color: #0f172a;
            border-bottom: 1px solid #334155;
        }

        #sidebar .nav-link {
            color: #94a3b8;
            padding: 0.8rem 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        #sidebar .nav-link:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover);
        }

        #sidebar .nav-link.active {
            color: #ffffff;
            background-color: var(--sidebar-hover);
            border-left-color: var(--sidebar-active);
            font-weight: 600;
        }

        #sidebar .nav-link i {
            font-size: 1.15rem;
        }

        #content {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-navbar {
            background-color: #ffffff;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            #sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
                position: fixed;
                height: 100%;
            }
            #sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div id="wrapper">

    <!-- Sidebar Navigation -->
    <nav id="sidebar" class="d-flex flex-column justify-content-between">
        <div>
            <div class="sidebar-brand d-flex align-items-center justify-content-between">
                <span class="text-white"><i class="bi bi-wallet2 text-primary me-2"></i>RealFinance</span>
            </div>

            <ul class="nav flex-column my-3">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="institutions.php" class="nav-link <?= ($current_page == 'accounts.php') ? 'active' : ''; ?>">
                        <i class="bi bi-bank"></i> Institutions
                    </a>
                </li>
                <li class="nav-item">
                    <a href="accounts.php" class="nav-link <?= ($current_page == 'accounts.php') ? 'active' : ''; ?>">
                        <i class="bi bi-bank"></i> Banks & Wallets
                    </a>
                </li>
                <li class="nav-item">
                    <a href="transactions.php" class="nav-link <?= ($current_page == 'transactions.php') ? 'active' : ''; ?>">
                        <i class="bi bi-arrow-down-up"></i> Income & Expenses
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link <?= ($current_page == 'categories.php') ? 'active' : ''; ?>">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="assets.php" class="nav-link <?= ($current_page == 'assets.php') ? 'active' : ''; ?>">
                        <i class="bi bi-graph-up-arrow"></i> Assets Portfolio
                    </a>
                </li>
                <li class="nav-item">
                    <a href="budgets.php" class="nav-link <?= ($current_page == 'budgets.php') ? 'active' : ''; ?>">
                        <i class="bi bi-pie-chart"></i> Budgets
                    </a>
                </li>
                <li class="nav-item">
                    <a href="recurring.php" class="nav-link <?= ($current_page == 'recurring.php') ? 'active' : ''; ?>">
                        <i class="bi bi-arrow-repeat"></i> Recurring & Bills
                    </a>
                </li>
                <li class="nav-item">
                    <a href="debts.php" class="nav-link <?= ($current_page == 'debts.php') ? 'active' : ''; ?>">
                        <i class="bi bi-cash-stack"></i> Debts & Loans
                    </a>
                </li>
            </ul>
        </div>

        <!-- User Profile Quick Bar -->
        <div class="p-3 border-top border-secondary">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="fw-bold text-white text-truncate" style="max-width: 160px;">
                        <?= htmlspecialchars($_SESSION['full_name'] ?? 'User Account'); ?>
                    </div>
                    <small class="text-muted d-block text-truncate"><?= htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
        </div>
    </nav>

    <!-- Content Wrapper -->
    <div id="content">
        
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light top-navbar border-bottom px-4 py-3">
            <div class="container-fluid p-0">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" id="sidebarToggle" class="btn btn-light border d-md-none">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <h5 class="mb-0 text-dark fw-bold">
                        <?php 
                            switch ($current_page) {
                                case 'accounts.php': echo 'Bank Accounts & Mobile Wallets'; break;
                                case 'transactions.php': echo 'Income, Expenses & Internal Transfers'; break;
                                case 'categories.php': echo 'Transaction Categories'; break;
                                case 'assets.php': echo 'Investment Portfolio & Assets'; break;
                                case 'budgets.php': echo 'Monthly Budgets & Limits'; break;
                                case 'recurring.php': echo 'Recurring Payments & Subscriptions'; break;
                                case 'debts.php': echo 'Debts & Loans Ledger'; break;
                                default: echo 'Dashboard & Financial Overview'; break;
                            }
                        ?>
                    </h5>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-dark border py-2 px-3">
                        <i class="bi bi-globe me-1"></i> Currency: <strong><?= htmlspecialchars($_SESSION['base_currency'] ?? 'USD'); ?></strong>
                    </span>
                </div>
            </div>
        </nav>

        <!-- Main Body Container -->
        <div class="container-fluid px-4 py-4">