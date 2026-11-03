<?php
// institutions.php
session_start();
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Handle Form Submission for Adding a New Institution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_institution') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $logo_url = trim($_POST['logo_url'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = "Institution name is required.";
    }
    if (empty($type)) {
        $errors[] = "Type is required.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO institutions (name, type, logo_url) 
                VALUES (:name, :type, :logo_url)
            ");
            $stmt->execute([
                ':name' => $name,
                ':type' => $type,
                ':logo_url' => !empty($logo_url) ? $logo_url : null
            ]);

            $success_message = "Financial institution added successfully!";
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Institutions with Linked Accounts Count for Current User
$stmt = $pdo->prepare("
    SELECT i.*, 
           COUNT(a.id) AS linked_accounts_count
    FROM institutions i
    LEFT JOIN accounts a ON i.id = a.institution_id AND a.user_id = :user_id AND a.is_active = 1
    GROUP BY i.id
    ORDER BY i.name ASC
");
$stmt->execute([':user_id' => $user_id]);
$institutions = $stmt->fetchAll();

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

<!-- Page Header & Action -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Financial Institutions</h4>
        <p class="text-muted small mb-0">Manage commercial banks, mobile wallet providers, and brokerage entities.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInstitutionModal">
        <i class="bi bi-building-add me-1"></i> Add Institution
    </button>
</div>

<!-- Institutions Grid -->
<div class="row g-4 mb-4">
    <?php if (empty($institutions)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center text-muted">
                <i class="bi bi-building fs-1 d-block mb-3"></i>
                <h5>No Financial Institutions Configured</h5>
                <p class="small mb-3">Add banks or mobile wallets to group your financial accounts effectively.</p>
                <div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addInstitutionModal">
                        Add First Institution
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($institutions as $inst): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="bg-light p-3 rounded-circle text-primary fs-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <?php if ($inst['type'] == 'mobile_wallet'): ?>
                                        <i class="bi bi-phone"></i>
                                    <?php elseif ($inst['type'] == 'brokerage'): ?>
                                        <i class="bi bi-graph-up-arrow"></i>
                                    <?php elseif ($inst['type'] == 'crypto_exchange'): ?>
                                        <i class="bi bi-currency-bitcoin"></i>
                                    <?php else: ?>
                                        <i class="bi bi-bank"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-light text-dark border uppercase">
                                    <?= str_replace('_', ' ', strtoupper($inst['type'])); ?>
                                </span>
                            </div>

                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($inst['name']); ?></h5>
                        </div>

                        <div class="pt-3 border-top mt-3 d-flex justify-content-between align-items-center text-muted small">
                            <span>Linked Accounts:</span>
                            <span class="badge bg-primary-subtle text-primary fw-bold fs-6">
                                <?= $inst['linked_accounts_count']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Add New Institution -->
<div class="modal fade" id="addInstitutionModal" tabindex="-1" aria-labelledby="addInstitutionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="addInstitutionModalLabel"><i class="bi bi-building me-2"></i>New Financial Institution</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="institutions.php" method="POST">
                <input type="hidden" name="action" value="add_institution">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Institution Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Bank of America, Telebirr, Binance" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Institution Type</label>
                        <select name="type" class="form-select" required>
                            <option value="bank">Commercial Bank</option>
                            <option value="mobile_wallet">Mobile Wallet Provider</option>
                            <option value="brokerage">Stock Brokerage</option>
                            <option value="crypto_exchange">Crypto Exchange</option>
                            <option value="other">Other / Private</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Logo URL (Optional)</label>
                        <input type="url" name="logo_url" class="form-control" placeholder="https://example.com/logo.png">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Institution</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>