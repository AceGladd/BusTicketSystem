<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$error = '';
$success = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $admin_id = $_POST['admin_id'] ?? '';

    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'firma_admin'");
    if ($stmt->execute([$admin_id])) {
        $success = 'Firma admin silindi.';
    } else {
        $error = 'Firma admin silinirken bir hata oluştu.';
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin'])) {
    $admin_id = $_POST['admin_id'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company_id = $_POST['company_id'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($full_name) || empty($email) || empty($company_id)) {
        $error = 'Lütfen tüm zorunlu alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } elseif (empty($admin_id) && empty($password)) {
        $error = 'Yeni kullanıcı için şifre gereklidir.';
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } else {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $admin_id]);

        if ($stmt->fetch()) {
            $error = 'Bu e-posta adresi zaten kullanılıyor.';
        } else {
            if (empty($admin_id)) {
                // Add new firma admin
                $admin_id = Database::getInstance()->generateUUIDPublic();
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $db->prepare("
                    INSERT INTO users (id, full_name, email, password, role, company_id, balance)
                    VALUES (?, ?, ?, ?, 'firma_admin', ?, 1000)
                ");

                if ($stmt->execute([$admin_id, $full_name, $email, $hashedPassword, $company_id])) {
                    $success = 'Firma admin eklendi.';
                } else {
                    $error = 'Firma admin eklenirken bir hata oluştu.';
                }
            } else {
                // Update existing firma admin
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        UPDATE users
                        SET full_name = ?, email = ?, password = ?, company_id = ?
                        WHERE id = ? AND role = 'firma_admin'
                    ");
                    $params = [$full_name, $email, $hashedPassword, $company_id, $admin_id];
                } else {
                    $stmt = $db->prepare("
                        UPDATE users
                        SET full_name = ?, email = ?, company_id = ?
                        WHERE id = ? AND role = 'firma_admin'
                    ");
                    $params = [$full_name, $email, $company_id, $admin_id];
                }

                if ($stmt->execute($params)) {
                    $success = 'Firma admin güncellendi.';
                } else {
                    $error = 'Firma admin güncellenirken bir hata oluştu.';
                }
            }
        }
    }
}

// Get all companies for dropdown
$companies = $db->query("SELECT * FROM bus_company ORDER BY name")->fetchAll();

// Get firma admins
$stmt = $db->query("
    SELECT u.*, bc.name as company_name
    FROM users u
    LEFT JOIN bus_company bc ON u.company_id = bc.id
    WHERE u.role = 'firma_admin'
    ORDER BY u.created_at DESC
");
$admins = $stmt->fetchAll();

// Get edit data if editing
$editAdmin = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'firma_admin'");
    $stmt->execute([$_GET['edit']]);
    $editAdmin = $stmt->fetch();
}

$pageTitle = 'Firma Admin Yönetimi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $editAdmin ? '<i class="bi bi-pencil"></i> Admin Düzenle' : '<i class="bi bi-plus-circle"></i> Yeni Firma Admin'; ?>
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
                    <?php if ($editAdmin): ?>
                        <input type="hidden" name="admin_id" value="<?php echo $editAdmin['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" name="full_name" required
                               value="<?php echo htmlspecialchars($editAdmin['full_name'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" class="form-control" name="email" required
                               value="<?php echo htmlspecialchars($editAdmin['email'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Firma</label>
                        <select class="form-select" name="company_id" required>
                            <option value="">Firma Seçin</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>"
                                    <?php echo (isset($editAdmin['company_id']) && $editAdmin['company_id'] === $company['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($company['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Şifre <?php echo $editAdmin ? '(Değiştirmek için doldurun)' : ''; ?>
                        </label>
                        <input type="password" class="form-control" name="password"
                               <?php echo $editAdmin ? '' : 'required'; ?>>
                        <small class="text-muted">En az 6 karakter</small>
                    </div>

                    <button type="submit" name="save_admin" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> <?php echo $editAdmin ? 'Güncelle' : 'Ekle'; ?>
                    </button>

                    <?php if ($editAdmin): ?>
                        <a href="/admin/firma_admins.php" class="btn btn-secondary w-100 mt-2">
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
                    <i class="bi bi-list"></i> Firma Adminler
                    <span class="badge bg-primary"><?php echo count($admins); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (count($admins) === 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Henüz firma admin eklenmemiş.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ad Soyad</th>
                                    <th>E-posta</th>
                                    <th>Firma</th>
                                    <th>Kayıt Tarihi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($admin['company_name'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($admin['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/admin/firma_admins.php?edit=<?php echo $admin['id']; ?>"
                                                   class="btn btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" class="d-inline"
                                                      onsubmit="return confirmDelete('Bu firma admini silmek istediğinizden emin misiniz?')">
                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                    <button type="submit" name="delete_admin" class="btn btn-danger btn-sm">
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
