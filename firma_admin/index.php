<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('firma_admin');

$currentUser = getCurrentUser();

// Get company info
$stmt = $db->prepare("SELECT * FROM bus_company WHERE id = ?");
$stmt->execute([$currentUser['company_id']]);
$company = $stmt->fetch();

// Get statistics
$stmt = $db->prepare("SELECT COUNT(*) as count FROM trips WHERE company_id = ?");
$stmt->execute([$currentUser['company_id']]);
$trip_count = $stmt->fetch()['count'];

$stmt = $db->prepare("
    SELECT COUNT(*) as count
    FROM tickets t
    JOIN trips tr ON t.trip_id = tr.id
    WHERE tr.company_id = ? AND t.status = 'active'
");
$stmt->execute([$currentUser['company_id']]);
$ticket_count = $stmt->fetch()['count'];

$stmt = $db->prepare("
    SELECT COALESCE(SUM(t.total_price), 0) as total
    FROM tickets t
    JOIN trips tr ON t.trip_id = tr.id
    WHERE tr.company_id = ? AND t.status = 'active'
");
$stmt->execute([$currentUser['company_id']]);
$revenue = $stmt->fetch()['total'];

$pageTitle = 'Firma Admin Paneli';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-building"></i> <?php echo htmlspecialchars($company['name']); ?> - Yönetim Paneli
        </h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow">
            <div class="card-body">
                <i class="bi bi-bus-front fs-1 text-primary"></i>
                <h3 class="mt-3"><?php echo $trip_count; ?></h3>
                <p class="text-muted mb-0">Toplam Sefer</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow">
            <div class="card-body">
                <i class="bi bi-ticket-perforated fs-1 text-success"></i>
                <h3 class="mt-3"><?php echo $ticket_count; ?></h3>
                <p class="text-muted mb-0">Satılan Bilet</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow">
            <div class="card-body">
                <i class="bi bi-cash-stack fs-1 text-warning"></i>
                <h3 class="mt-3"><?php echo number_format($revenue, 2); ?> TL</h3>
                <p class="text-muted mb-0">Toplam Gelir</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card shadow">
            <div class="card-body text-center">
                <i class="bi bi-bus-front fs-1 text-primary mb-3"></i>
                <h5 class="card-title">Sefer Yönetimi</h5>
                <p class="card-text">Seferlerinizi ekleyin, düzenleyin veya silin</p>
                <a href="/firma_admin/trips.php" class="btn btn-primary">
                    <i class="bi bi-arrow-right-circle"></i> Seferlere Git
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card shadow">
            <div class="card-body text-center">
                <i class="bi bi-tag fs-1 text-success mb-3"></i>
                <h5 class="card-title">Kupon Yönetimi</h5>
                <p class="card-text">İndirim kuponları oluşturun ve yönetin</p>
                <a href="/firma_admin/coupons.php" class="btn btn-success">
                    <i class="bi bi-arrow-right-circle"></i> Kuponlara Git
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
