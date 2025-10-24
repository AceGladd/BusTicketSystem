<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$error = '';
$success = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_company'])) {
    $company_id = $_POST['company_id'] ?? '';

    $stmt = $db->prepare("DELETE FROM bus_company WHERE id = ?");
    if ($stmt->execute([$company_id])) {
        $success = 'Firma silindi.';
    } else {
        $error = 'Firma silinirken bir hata oluştu.';
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_company'])) {
    $company_id = $_POST['company_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $logo_path = trim($_POST['logo_path'] ?? '');

    if (empty($name)) {
        $error = 'Firma adı boş olamaz.';
    } else {
        // Check if name already exists
        $stmt = $db->prepare("SELECT id FROM bus_company WHERE name = ? AND id != ?");
        $stmt->execute([$name, $company_id]);

        if ($stmt->fetch()) {
            $error = 'Bu firma adı zaten kullanılıyor.';
        } else {
            if (empty($company_id)) {
                // Add new company
                $company_id = Database::getInstance()->generateUUIDPublic();

                $stmt = $db->prepare("
                    INSERT INTO bus_company (id, name, logo_path)
                    VALUES (?, ?, ?)
                ");

                if ($stmt->execute([$company_id, $name, $logo_path])) {
                    $success = 'Firma eklendi.';
                } else {
                    $error = 'Firma eklenirken bir hata oluştu.';
                }
            } else {
                // Update existing company
                $stmt = $db->prepare("
                    UPDATE bus_company
                    SET name = ?, logo_path = ?
                    WHERE id = ?
                ");

                if ($stmt->execute([$name, $logo_path, $company_id])) {
                    $success = 'Firma güncellendi.';
                } else {
                    $error = 'Firma güncellenirken bir hata oluştu.';
                }
            }
        }
    }
}

// Get companies with statistics
$stmt = $db->query("
    SELECT bc.*,
           (SELECT COUNT(*) FROM trips WHERE company_id = bc.id) as trip_count,
           (SELECT COUNT(*) FROM users WHERE company_id = bc.id AND role = 'firma_admin') as admin_count
    FROM bus_company bc
    ORDER BY bc.created_at DESC
");
$companies = $stmt->fetchAll();

// Get edit data if editing
$editCompany = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM bus_company WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCompany = $stmt->fetch();
}

$pageTitle = 'Firma Yönetimi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $editCompany ? '<i class="bi bi-pencil"></i> Firma Düzenle' : '<i class="bi bi-plus-circle"></i> Yeni Firma'; ?>
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
                    <?php if ($editCompany): ?>
                        <input type="hidden" name="company_id" value="<?php echo $editCompany['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Firma Adı</label>
                        <input type="text" class="form-control" name="name" required
                               value="<?php echo htmlspecialchars($editCompany['name'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Logo Path (İsteğe Bağlı)</label>
                        <input type="text" class="form-control" name="logo_path"
                               value="<?php echo htmlspecialchars($editCompany['logo_path'] ?? ''); ?>">
                        <small class="text-muted">Örnek: metro.png</small>
                    </div>

                    <button type="submit" name="save_company" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> <?php echo $editCompany ? 'Güncelle' : 'Ekle'; ?>
                    </button>

                    <?php if ($editCompany): ?>
                        <a href="/admin/companies.php" class="btn btn-secondary w-100 mt-2">
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
                    <i class="bi bi-list"></i> Firmalar
                    <span class="badge bg-primary"><?php echo count($companies); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (count($companies) === 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Henüz firma eklenmemiş.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Firma Adı</th>
                                    <th>Logo</th>
                                    <th>Sefer Sayısı</th>
                                    <th>Admin Sayısı</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($company['name']); ?></strong></td>
                                        <td>
                                            <?php if ($company['logo_path']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($company['logo_path']); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $company['trip_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $company['admin_count']; ?></span>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($company['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/admin/companies.php?edit=<?php echo $company['id']; ?>"
                                                   class="btn btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" class="d-inline"
                                                      onsubmit="return confirmDelete('Bu firmayı silmek istediğinizden emin misiniz? İlişkili tüm veriler silinecektir.')">
                                                    <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                                    <button type="submit" name="delete_company" class="btn btn-danger btn-sm">
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
