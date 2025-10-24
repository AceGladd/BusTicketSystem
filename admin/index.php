<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as count FROM bus_company");
$company_count = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'firma_admin'");
$firma_admin_count = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$user_count = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM trips");
$trip_count = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM tickets WHERE status = 'active'");
$ticket_count = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COALESCE(SUM(total_price), 0) as total FROM tickets WHERE status = 'active'");
$total_revenue = $stmt->fetch()['total'];

$pageTitle = 'Admin Paneli';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-shield-lock"></i> Admin Paneli
        </h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow">
            <div class="card-body">
                <i class="bi bi-building fs-1 text-primary"></i>
                <h3 class="mt-3"><?php echo $company_count; ?></h3>
                <p class="text-muted mb-0">Otobüs Firması</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow">
            <div class="card-body">
                <i class="bi bi-people fs-1 text-info"></i>
                <h3 class="mt-3"><?php echo $firma_admin_count; ?></h3>
                <p class="text-muted mb-0">Firma Admin</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow">
            <div class="card-body">
                <i class="bi bi-person fs-1 text-success"></i>
                <h3 class="mt-3"><?php echo $user_count; ?></h3>
                <p class="text-muted mb-0">Kullanıcı</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow">
            <div class="card-body">
                <i class="bi bi-bus-front fs-1 text-warning"></i>
                <h3 class="mt-3"><?php echo $trip_count; ?></h3>
                <p class="text-muted mb-0">Toplam Sefer</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow">
            <div class="card-body">
                <i class="bi bi-ticket-perforated fs-1 text-danger"></i>
                <h3 class="mt-3"><?php echo $ticket_count; ?></h3>
                <p class="text-muted mb-0">Satılan Bilet</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow">
            <div class="card-body">
                <i class="bi bi-cash-stack fs-1 text-success"></i>
                <h3 class="mt-3"><?php echo number_format($total_revenue, 2); ?> TL</h3>
                <p class="text-muted mb-0">Toplam Gelir</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card shadow">
            <div class="card-body text-center">
                <i class="bi bi-building fs-1 text-primary mb-3"></i>
                <h5 class="card-title">Firma Yönetimi</h5>
                <p class="card-text">Otobüs firmalarını yönetin</p>
                <a href="/admin/companies.php" class="btn btn-primary">
                    <i class="bi bi-arrow-right-circle"></i> Firmalara Git
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow">
            <div class="card-body text-center">
                <i class="bi bi-people fs-1 text-info mb-3"></i>
                <h5 class="card-title">Firma Admin Yönetimi</h5>
                <p class="card-text">Firma adminlerini yönetin</p>
                <a href="/admin/firma_admins.php" class="btn btn-info">
                    <i class="bi bi-arrow-right-circle"></i> Adminlere Git
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow">
            <div class="card-body text-center">
                <i class="bi bi-tag fs-1 text-success mb-3"></i>
                <h5 class="card-title">Genel Kupon Yönetimi</h5>
                <p class="card-text">Tüm firmalar için kupon oluşturun</p>
                <a href="/admin/coupons.php" class="btn btn-success">
                    <i class="bi bi-arrow-right-circle"></i> Kuponlara Git
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
