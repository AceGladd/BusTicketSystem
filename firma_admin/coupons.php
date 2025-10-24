<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('firma_admin');

$currentUser = getCurrentUser();
$error = '';
$success = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon'])) {
    $coupon_id = $_POST['coupon_id'] ?? '';

    $stmt = $db->prepare("DELETE FROM coupons WHERE id = ? AND company_id = ?");
    if ($stmt->execute([$coupon_id, $currentUser['company_id']])) {
        $success = 'Kupon silindi.';
    } else {
        $error = 'Kupon silinirken bir hata oluştu.';
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_coupon'])) {
    $coupon_id = $_POST['coupon_id'] ?? '';
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount = $_POST['discount'] ?? '';
    $usage_limit = $_POST['usage_limit'] ?? '';
    $expire_date = $_POST['expire_date'] ?? '';

    if (empty($code) || empty($discount) || empty($usage_limit) || empty($expire_date)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif ($discount < 1 || $discount > 100) {
        $error = 'İndirim oranı 1-100 arasında olmalıdır.';
    } else {
        // Check if code already exists
        $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
        $stmt->execute([$code, $coupon_id]);

        if ($stmt->fetch()) {
            $error = 'Bu kupon kodu zaten kullanılıyor.';
        } else {
            if (empty($coupon_id)) {
                // Add new coupon
                $coupon_id = Database::getInstance()->generateUUIDPublic();

                $stmt = $db->prepare("
                    INSERT INTO coupons (id, code, discount, company_id, usage_limit, expire_date)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                if ($stmt->execute([$coupon_id, $code, $discount, $currentUser['company_id'], $usage_limit, $expire_date])) {
                    $success = 'Kupon eklendi.';
                } else {
                    $error = 'Kupon eklenirken bir hata oluştu.';
                }
            } else {
                // Update existing coupon
                $stmt = $db->prepare("
                    UPDATE coupons
                    SET code = ?, discount = ?, usage_limit = ?, expire_date = ?
                    WHERE id = ? AND company_id = ?
                ");

                if ($stmt->execute([$code, $discount, $usage_limit, $expire_date, $coupon_id, $currentUser['company_id']])) {
                    $success = 'Kupon güncellendi.';
                } else {
                    $error = 'Kupon güncellenirken bir hata oluştu.';
                }
            }
        }
    }
}

// Get coupons
$stmt = $db->prepare("
    SELECT c.*
    FROM coupons c
    WHERE c.company_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$currentUser['company_id']]);
$coupons = $stmt->fetchAll();

// Get edit data if editing
$editCoupon = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$_GET['edit'], $currentUser['company_id']]);
    $editCoupon = $stmt->fetch();
}

$pageTitle = 'Kupon Yönetimi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $editCoupon ? '<i class="bi bi-pencil"></i> Kupon Düzenle' : '<i class="bi bi-plus-circle"></i> Yeni Kupon'; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?php if ($editCoupon): ?>
                        <input type="hidden" name="coupon_id" value="<?php echo $editCoupon['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Kupon Kodu</label>
                        <input type="text" class="form-control" name="code" required
                               value="<?php echo htmlspecialchars($editCoupon['code'] ?? ''); ?>"
                               style="text-transform: uppercase;">
                        <small class="text-muted">Örnek: YILBASI20</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">İndirim Oranı (%)</label>
                        <input type="number" step="0.01" min="1" max="100" class="form-control" name="discount" required
                               value="<?php echo $editCoupon['discount'] ?? ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kullanım Limiti</label>
                        <input type="number" min="1" class="form-control" name="usage_limit" required
                               value="<?php echo $editCoupon['usage_limit'] ?? ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Son Kullanma Tarihi</label>
                        <input type="datetime-local" class="form-control" name="expire_date" required
                               value="<?php echo $editCoupon ? date('Y-m-d\TH:i', strtotime($editCoupon['expire_date'])) : ''; ?>">
                    </div>

                    <button type="submit" name="save_coupon" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> <?php echo $editCoupon ? 'Güncelle' : 'Ekle'; ?>
                    </button>

                    <?php if ($editCoupon): ?>
                        <a href="/firma_admin/coupons.php" class="btn btn-secondary w-100 mt-2">
                            <i class="bi bi-x-circle"></i> İptal
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list"></i> Kuponlar
                    <span class="badge bg-primary"><?php echo count($coupons); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (count($coupons) === 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Henüz kupon eklenmemiş.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kod</th>
                                    <th>İndirim</th>
                                    <th>Kullanım</th>
                                    <th>Son Kullanma</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon): ?>
                                    <?php
                                    $is_expired = strtotime($coupon['expire_date']) < time();
                                    $is_full = $coupon['usage_count'] >= $coupon['usage_limit'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                        <td>%<?php echo $coupon['discount']; ?></td>
                                        <td>
                                            <?php echo $coupon['usage_count']; ?> / <?php echo $coupon['usage_limit']; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($coupon['expire_date'])); ?></td>
                                        <td>
                                            <?php if ($is_expired): ?>
                                                <span class="badge bg-danger">Süresi Dolmuş</span>
                                            <?php elseif ($is_full): ?>
                                                <span class="badge bg-warning">Limit Doldu</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/firma_admin/coupons.php?edit=<?php echo $coupon['id']; ?>"
                                                   class="btn btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" class="d-inline"
                                                      onsubmit="return confirmDelete('Bu kuponu silmek istediğinizden emin misiniz?')">
                                                    <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                                    <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
