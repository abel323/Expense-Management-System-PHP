<?php
// assets.php
session_start();
require_once 'config/db.php';

// Auth check
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success_message = '';

// -------------------------------------------------------------------
// 1. ACTION HANDLERS (ADD, EDIT, DELETE)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- ADD ASSET ---
    if ($action === 'add_asset') {
        $name                    = trim($_POST['name'] ?? '');
        $asset_class             = trim($_POST['asset_class'] ?? 'other');
        $purchase_price_per_unit = floatval($_POST['purchase_price_per_unit'] ?? 0);
        $current_market_price    = floatval($_POST['current_market_price'] ?? 0);
        $purchase_date           = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
        $institution             = trim($_POST['institution'] ?? '');
        $notes                   = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            $errors[] = "Asset name is required.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO assets (user_id, name, asset_class, purchase_price_per_unit, current_market_price, purchase_date, institution, notes)
                VALUES (:user_id, :name, :asset_class, :purchase_price_per_unit, :current_market_price, :purchase_date, :institution, :notes)
            ");
            $stmt->execute([
                ':user_id'                 => $user_id,
                ':name'                    => $name,
                ':asset_class'             => $asset_class,
                ':purchase_price_per_unit' => $purchase_price_per_unit,
                ':current_market_price'    => $current_market_price,
                ':purchase_date'           => $purchase_date,
                ':institution'             => $institution,
                ':notes'                   => $notes
            ]);
            $success_message = "New asset added to portfolio!";
        }
    }

    // --- EDIT ASSET ---
    if ($action === 'edit_asset') {
        $asset_id                = intval($_POST['asset_id'] ?? 0);
        $name                    = trim($_POST['name'] ?? '');
        $asset_class             = trim($_POST['asset_class'] ?? 'other');
        $purchase_price_per_unit = floatval($_POST['purchase_price_per_unit'] ?? 0);
        $current_market_price    = floatval($_POST['current_market_price'] ?? 0);
        $purchase_date           = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
        $institution             = trim($_POST['institution'] ?? '');
        $notes                   = trim($_POST['notes'] ?? '');

        if ($asset_id <= 0 || empty($name)) {
            $errors[] = "Invalid asset details.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE assets 
                SET name = :name, asset_class = :asset_class, purchase_price_per_unit = :purchase_price_per_unit, 
                    current_market_price = :current_market_price, purchase_date = :purchase_date, 
                    institution = :institution, notes = :notes
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                ':name'                    => $name,
                ':asset_class'             => $asset_class,
                ':purchase_price_per_unit' => $purchase_price_per_unit,
                ':current_market_price'    => $current_market_price,
                ':purchase_date'           => $purchase_date,
                ':institution'             => $institution,
                ':notes'                   => $notes,
                ':id'                      => $asset_id,
                ':user_id'                 => $user_id
            ]);
            $success_message = "Asset details updated successfully!";
        }
    }

    // --- DELETE ASSET ---
    if ($action === 'delete_asset') {
        $asset_id = intval($_POST['asset_id'] ?? 0);
        if ($asset_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM assets WHERE id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $asset_id, ':user_id' => $user_id]);
            $success_message = "Asset removed from portfolio.";
        }
    }
}

// -------------------------------------------------------------------
// 2. SECURE SORTING & DATA RETRIEVAL
// -------------------------------------------------------------------
$allowed_sorts = ['name', 'asset_class', 'purchase_price_per_unit', 'current_market_price', 'purchase_date'];
$sort_col = $_GET['sort'] ?? 'current_market_price';
if (!in_array($sort_col, $allowed_sorts)) {
    $sort_col = 'current_market_price';
}
$sort_dir = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc') ? 'ASC' : 'DESC';

// Interpolate whitelisted column directly into SQL
$stmt = $pdo->prepare("SELECT * FROM assets WHERE user_id = :user_id ORDER BY {$sort_col} {$sort_dir}");
$stmt->execute([':user_id' => $user_id]);
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$total_portfolio_value = 0;
$total_cost_basis      = 0;

foreach ($assets as $a) {
    $total_portfolio_value += $a['current_market_price'];
    $total_cost_basis      += $a['purchase_price_per_unit'];
}

$total_unrealized_gain  = $total_portfolio_value - $total_cost_basis;
$overall_roi_percentage = $total_cost_basis > 0 ? ($total_unrealized_gain / $total_cost_basis) * 100 : 0;

$asset_classes = [
    'real_estate'     => ['label' => 'Real Estate', 'icon' => 'bi-house-door', 'color' => '#0d6efd'],
    'stocks'          => ['label' => 'Stocks & ETFs', 'icon' => 'bi-graph-up-arrow', 'color' => '#198754'],
    'crypto'          => ['label' => 'Crypto Assets', 'icon' => 'bi-currency-bitcoin', 'color' => '#f7931a'],
    'vehicle'         => ['label' => 'Vehicles', 'icon' => 'bi-car-front', 'color' => '#0dcaf0'],
    'precious_metals' => ['label' => 'Precious Metals', 'icon' => 'bi-gem', 'color' => '#ffc107'],
    'cash_equivalent' => ['label' => 'Cash & Savings', 'icon' => 'bi-bank', 'color' => '#20c997'],
    'other'           => ['label' => 'Other / Personal', 'icon' => 'bi-box-seam', 'color' => '#6c757d']
];

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

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Assets Portfolio</h4>
        <p class="text-muted small mb-0">Track capital investments, tangible property, and valuation changes.</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAssetModal">
        <i class="bi bi-plus-circle me-1"></i> Add New Asset
    </button>
</div>

<!-- Portfolio Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <span class="text-secondary small fw-bold text-uppercase">Total Portfolio Value</span>
            <h3 class="fw-bold text-dark mt-2 mb-0">$<?= number_format($total_portfolio_value, 2); ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <span class="text-secondary small fw-bold text-uppercase">Total Cost Basis</span>
            <h3 class="fw-bold text-secondary mt-2 mb-0">$<?= number_format($total_cost_basis, 2); ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <span class="text-secondary small fw-bold text-uppercase">Unrealized Gain / Loss</span>
            <h3 class="fw-bold mt-2 mb-0 <?= $total_unrealized_gain >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?= $total_unrealized_gain >= 0 ? '+' : ''; ?>$<?= number_format($total_unrealized_gain, 2); ?>
            </h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <span class="text-secondary small fw-bold text-uppercase">Overall Portfolio Return</span>
            <h3 class="fw-bold mt-2 mb-0 <?= $overall_roi_percentage >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?= $overall_roi_percentage >= 0 ? '+' : ''; ?><?= number_format($overall_roi_percentage, 2); ?>%
            </h3>
        </div>
    </div>
</div>

<!-- Asset Ledger Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">
                            <a href="?sort=name&dir=<?= $sort_col === 'name' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?>" class="text-dark text-decoration-none">
                                Asset Name <?= $sort_col === 'name' ? ($sort_dir === 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=asset_class&dir=<?= $sort_col === 'asset_class' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?>" class="text-dark text-decoration-none">
                                Class <?= $sort_col === 'asset_class' ? ($sort_dir === 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                        <th>Institution</th>
                        <th>
                            <a href="?sort=purchase_price_per_unit&dir=<?= $sort_col === 'purchase_price_per_unit' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?>" class="text-dark text-decoration-none">
                                Purchase Price / Unit <?= $sort_col === 'purchase_price_per_unit' ? ($sort_dir === 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=current_market_price&dir=<?= $sort_col === 'current_market_price' && $sort_dir === 'ASC' ? 'desc' : 'asc'; ?>" class="text-dark text-decoration-none">
                                Current Market Price <?= $sort_col === 'current_market_price' ? ($sort_dir === 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                        <th>Gain / Loss</th>
                        <th>Allocation %</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assets)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary"></i>
                                No assets logged in your portfolio yet. Click "Add New Asset" to begin.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assets as $asset): 
                            $gain = $asset['current_market_price'] - $asset['purchase_price_per_unit'];
                            $roi  = $asset['purchase_price_per_unit'] > 0 ? ($gain / $asset['purchase_price_per_unit']) * 100 : 0;
                            $allocation_pct = $total_portfolio_value > 0 ? ($asset['current_market_price'] / $total_portfolio_value) * 100 : 0;
                            $meta = $asset_classes[$asset['asset_class']] ?? $asset_classes['other'];
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; background-color: <?= $meta['color']; ?>;">
                                            <i class="bi <?= $meta['icon']; ?>"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold d-block text-dark"><?= htmlspecialchars($asset['name']); ?></span>
                                            <?php if (!empty($asset['notes'])): ?>
                                                <small class="text-muted d-block text-truncate" style="max-width: 180px;"><?= htmlspecialchars($asset['notes']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-normal"><?= $meta['label']; ?></span>
                                </td>
                                <td class="text-secondary"><?= htmlspecialchars($asset['institution'] ?: 'N/A'); ?></td>
                                <td class="fw-semibold text-secondary">$<?= number_format($asset['purchase_price_per_unit'], 2); ?></td>
                                <td class="fw-bold text-dark">$<?= number_format($asset['current_market_price'], 2); ?></td>
                                <td>
                                    <span class="fw-bold <?= $gain >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?= $gain >= 0 ? '+' : ''; ?>$<?= number_format($gain, 2); ?>
                                    </span>
                                    <small class="d-block <?= $gain >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        (<?= $gain >= 0 ? '+' : ''; ?><?= number_format($roi, 2); ?>%)
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2" style="width: 100px;">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?= $allocation_pct; ?>%; background-color: <?= $meta['color']; ?>;"></div>
                                        </div>
                                        <small class="fw-semibold text-muted"><?= number_format($allocation_pct, 1); ?>%</small>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            onclick='openEditModal(<?= json_encode($asset, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="assets.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this asset from your portfolio?');">
                                        <input type="hidden" name="action" value="delete_asset">
                                        <input type="hidden" name="asset_id" value="<?= $asset['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Asset -->
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i>Add Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="assets.php" method="POST">
                <input type="hidden" name="action" value="add_asset">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Asset Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Primary Residence, Tesla Stock, Bitcoin Wallet" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Asset Class</label>
                            <select name="asset_class" class="form-select" required>
                                <?php foreach ($asset_classes as $key => $type): ?>
                                    <option value="<?= $key; ?>"><?= $type['label']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Broker / Institution</label>
                            <input type="text" name="institution" class="form-control" placeholder="e.g. Robinhood, Coinbase">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase Price / Unit ($)</label>
                            <input type="number" step="0.01" min="0" name="purchase_price_per_unit" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Current Market Price ($)</label>
                            <input type="number" step="0.01" min="0" name="current_market_price" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes / Allocation Details</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Asset -->
<div class="modal fade" id="editAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Update Asset Valuation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="assets.php" method="POST">
                <input type="hidden" name="action" value="edit_asset">
                <input type="hidden" name="asset_id" id="edit_asset_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Asset Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Asset Class</label>
                            <select name="asset_class" id="edit_asset_class" class="form-select" required>
                                <?php foreach ($asset_classes as $key => $type): ?>
                                    <option value="<?= $key; ?>"><?= $type['label']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Institution / Custodian</label>
                            <input type="text" name="institution" id="edit_institution" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purchase Price / Unit ($)</label>
                            <input type="number" step="0.01" min="0" name="purchase_price_per_unit" id="edit_purchase_price_per_unit" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Current Market Price ($)</label>
                            <input type="number" step="0.01" min="0" name="current_market_price" id="edit_current_market_price" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Purchase Date</label>
                        <input type="date" name="purchase_date" id="edit_purchase_date" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark"><i class="bi bi-check-circle me-1"></i> Update Valuation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(asset) {
    document.getElementById('edit_asset_id').value = asset.id;
    document.getElementById('edit_name').value = asset.name;
    document.getElementById('edit_asset_class').value = asset.asset_class;
    document.getElementById('edit_institution').value = asset.institution || '';
    document.getElementById('edit_purchase_price_per_unit').value = asset.purchase_price_per_unit;
    document.getElementById('edit_current_market_price').value = asset.current_market_price;
    document.getElementById('edit_purchase_date').value = asset.purchase_date || '';
    document.getElementById('edit_notes').value = asset.notes || '';

    const modal = new bootstrap.Modal(document.getElementById('editAssetModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>