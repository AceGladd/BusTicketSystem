<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('firma_admin');

$currentUser = getCurrentUser();
$error = '';
$success = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_trip'])) {
    $trip_id = $_POST['trip_id'] ?? '';

    $stmt = $db->prepare("DELETE FROM trips WHERE id = ? AND company_id = ?");
    if ($stmt->execute([$trip_id, $currentUser['company_id']])) {
        $success = 'Sefer silindi.';
    } else {
        $error = 'Sefer silinirken bir hata oluştu.';
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_trip'])) {
    $trip_id = $_POST['trip_id'] ?? '';
    $departure_city = trim($_POST['departure_city'] ?? '');
    $destination_city = trim($_POST['destination_city'] ?? '');
    $departure_time = $_POST['departure_time'] ?? '';
    $arrival_time = $_POST['arrival_time'] ?? '';
    $price = $_POST['price'] ?? '';
    $capacity = $_POST['capacity'] ?? '';

    if (empty($departure_city) || empty($destination_city) || empty($departure_time) || empty($arrival_time) || empty($price) || empty($capacity)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif ($departure_city === $destination_city) {
        $error = 'Kalkış ve varış şehri aynı olamaz.';
    } elseif (strtotime($departure_time) >= strtotime($arrival_time)) {
        $error = 'Varış saati kalkış saatinden sonra olmalıdır.';
    } else {
        if (empty($trip_id)) {
            // Add new trip
            $trip_id = Database::getInstance()->generateUUIDPublic();

            $stmt = $db->prepare("
                INSERT INTO trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$trip_id, $currentUser['company_id'], $departure_city, $destination_city, $departure_time, $arrival_time, $price, $capacity])) {
                $success = 'Sefer eklendi.';
            } else {
                $error = 'Sefer eklenirken bir hata oluştu.';
            }
        } else {
            // Update existing trip
            $stmt = $db->prepare("
                UPDATE trips
                SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ?, capacity = ?
                WHERE id = ? AND company_id = ?
            ");

            if ($stmt->execute([$departure_city, $destination_city, $departure_time, $arrival_time, $price, $capacity, $trip_id, $currentUser['company_id']])) {
                $success = 'Sefer güncellendi.';
            } else {
                $error = 'Sefer güncellenirken bir hata oluştu.';
            }
        }
    }
}

// Get trips
$stmt = $db->prepare("
    SELECT t.*,
           (SELECT COUNT(*) FROM tickets WHERE trip_id = t.id AND status = 'active') as sold_tickets
    FROM trips t
    WHERE t.company_id = ?
    ORDER BY t.departure_time DESC
");
$stmt->execute([$currentUser['company_id']]);
$trips = $stmt->fetchAll();

// Get edit data if editing
$editTrip = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$_GET['edit'], $currentUser['company_id']]);
    $editTrip = $stmt->fetch();
}

$pageTitle = 'Sefer Yönetimi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $editTrip ? '<i class="bi bi-pencil"></i> Sefer Düzenle' : '<i class="bi bi-plus-circle"></i> Yeni Sefer'; ?>
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
                    <?php if ($editTrip): ?>
                        <input type="hidden" name="trip_id" value="<?php echo $editTrip['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Kalkış Şehri</label>
                        <input type="text" class="form-control" name="departure_city" required
                               value="<?php echo htmlspecialchars($editTrip['departure_city'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Varış Şehri</label>
                        <input type="text" class="form-control" name="destination_city" required
                               value="<?php echo htmlspecialchars($editTrip['destination_city'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kalkış Tarihi & Saati</label>
                        <input type="datetime-local" class="form-control" name="departure_time" required
                               value="<?php echo $editTrip ? date('Y-m-d\TH:i', strtotime($editTrip['departure_time'])) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Varış Tarihi & Saati</label>
                        <input type="datetime-local" class="form-control" name="arrival_time" required
                               value="<?php echo $editTrip ? date('Y-m-d\TH:i', strtotime($editTrip['arrival_time'])) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fiyat (TL)</label>
                        <input type="number" step="0.01" class="form-control" name="price" required
                               value="<?php echo $editTrip['price'] ?? ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kapasite</label>
                        <input type="number" class="form-control" name="capacity" required
                               value="<?php echo $editTrip['capacity'] ?? ''; ?>">
                    </div>

                    <button type="submit" name="save_trip" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> <?php echo $editTrip ? 'Güncelle' : 'Ekle'; ?>
                    </button>

                    <?php if ($editTrip): ?>
                        <a href="/firma_admin/trips.php" class="btn btn-secondary w-100 mt-2">
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
                    <i class="bi bi-list"></i> Seferler
                    <span class="badge bg-primary"><?php echo count($trips); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (count($trips) === 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Henüz sefer eklenmemiş.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Güzergah</th>
                                    <th>Tarih</th>
                                    <th>Fiyat</th>
                                    <th>Kapasite</th>
                                    <th>Satış</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trips as $trip): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($trip['departure_city']); ?>
                                            <i class="bi bi-arrow-right"></i>
                                            <?php echo htmlspecialchars($trip['destination_city']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?>
                                        </td>
                                        <td><?php echo number_format($trip['price'], 2); ?> TL</td>
                                        <td><?php echo $trip['capacity']; ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $trip['sold_tickets']; ?> / <?php echo $trip['capacity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/firma_admin/trips.php?edit=<?php echo $trip['id']; ?>"
                                                   class="btn btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" class="d-inline"
                                                      onsubmit="return confirmDelete('Bu seferi silmek istediğinizden emin misiniz?')">
                                                    <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                                    <button type="submit" name="delete_trip" class="btn btn-danger btn-sm">
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
