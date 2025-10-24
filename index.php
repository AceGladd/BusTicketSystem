<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Ana Sayfa';
require_once __DIR__ . '/includes/header.php';

// Get all unique cities for departure and destination
$stmt = $db->query("
    SELECT DISTINCT departure_city FROM trips
    UNION
    SELECT DISTINCT destination_city FROM trips
    ORDER BY 1
");
$cities = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle search
$trips = [];
$searchPerformed = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $searchPerformed = true;
    $departure_city = $_GET['departure_city'] ?? '';
    $destination_city = $_GET['destination_city'] ?? '';
    $date = $_GET['date'] ?? '';

    $query = "
        SELECT t.*, bc.name as company_name,
               (t.capacity - COALESCE(bs_count.booked, 0)) as available_seats
        FROM trips t
        JOIN bus_company bc ON t.company_id = bc.id
        LEFT JOIN (
            SELECT trip_id, COUNT(*) as booked
            FROM booked_seats
            GROUP BY trip_id
        ) bs_count ON t.id = bs_count.trip_id
        WHERE t.departure_time >= datetime('now')
    ";

    $params = [];

    if (!empty($departure_city)) {
        $query .= " AND t.departure_city = ?";
        $params[] = $departure_city;
    }

    if (!empty($destination_city)) {
        $query .= " AND t.destination_city = ?";
        $params[] = $destination_city;
    }

    if (!empty($date)) {
        $query .= " AND DATE(t.departure_time) = ?";
        $params[] = $date;
    }

    $query .= " ORDER BY t.departure_time ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $trips = $stmt->fetchAll();
}
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-search"></i> Sefer Ara</h4>
            </div>
            <div class="card-body p-4">
                <form method="GET" action="/index.php">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="departure_city" class="form-label">Nereden</label>
                            <select class="form-select" id="departure_city" name="departure_city" required>
                                <option value="">Şehir Seçin</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"
                                        <?php echo (isset($_GET['departure_city']) && $_GET['departure_city'] === $city) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($city); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="destination_city" class="form-label">Nereye</label>
                            <select class="form-select" id="destination_city" name="destination_city" required>
                                <option value="">Şehir Seçin</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"
                                        <?php echo (isset($_GET['destination_city']) && $_GET['destination_city'] === $city) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($city); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="date" class="form-label">Tarih</label>
                            <input type="date" class="form-control" id="date" name="date"
                                   value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" name="search" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Sefer Ara
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($searchPerformed): ?>
    <div class="row">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="bi bi-list-ul"></i> Bulunan Seferler
                <?php if (count($trips) > 0): ?>
                    <span class="badge bg-primary"><?php echo count($trips); ?></span>
                <?php endif; ?>
            </h4>

            <?php if (count($trips) === 0): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Aradığınız kriterlere uygun sefer bulunamadı.
                </div>
            <?php else: ?>
                <?php foreach ($trips as $trip): ?>
                    <div class="card mb-3 trip-card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($trip['company_name']); ?></h5>
                                </div>

                                <div class="col-md-2">
                                    <div class="text-center">
                                        <h4 class="mb-0"><?php echo date('H:i', strtotime($trip['departure_time'])); ?></h4>
                                        <small class="text-muted"><?php echo htmlspecialchars($trip['departure_city']); ?></small>
                                    </div>
                                </div>

                                <div class="col-md-1 text-center">
                                    <i class="bi bi-arrow-right fs-3 text-primary"></i>
                                </div>

                                <div class="col-md-2">
                                    <div class="text-center">
                                        <h4 class="mb-0"><?php echo date('H:i', strtotime($trip['arrival_time'])); ?></h4>
                                        <small class="text-muted"><?php echo htmlspecialchars($trip['destination_city']); ?></small>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="text-center">
                                        <h5 class="mb-0 text-success"><?php echo number_format($trip['price'], 2); ?> TL</h5>
                                        <small class="text-muted">
                                            <?php echo $trip['available_seats']; ?> koltuk müsait
                                        </small>
                                    </div>
                                </div>

                                <div class="col-md-3 text-end">
                                    <?php if ($trip['available_seats'] > 0): ?>
                                        <?php if (isLoggedIn()): ?>
                                            <a href="/user/purchase.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-primary">
                                                <i class="bi bi-ticket-perforated"></i> Bilet Al
                                            </a>
                                        <?php else: ?>
                                            <a href="/login.php" class="btn btn-warning">
                                                <i class="bi bi-box-arrow-in-right"></i> Giriş Yapın
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <i class="bi bi-x-circle"></i> Dolu
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Sefer aramak için yukarıdaki formu kullanın.
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
