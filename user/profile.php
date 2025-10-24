<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$currentUser = getCurrentUser();

$pageTitle = 'Profil';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-person-circle"></i> Profil Bilgileri</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Ad Soyad</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <input type="text" class="form-control" value="Kullanıcı" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Bakiye</label>
                    <input type="text" class="form-control" value="<?php echo number_format($currentUser['balance'], 2); ?> TL" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kayıt Tarihi</label>
                    <input type="text" class="form-control" value="<?php echo date('d.m.Y H:i', strtotime($currentUser['created_at'])); ?>" readonly>
                </div>

                <a href="/user/tickets.php" class="btn btn-primary">
                    <i class="bi bi-ticket-perforated"></i> Biletlerim
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
