<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$success = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'purchase') {
        $success = 'Bilet satın alma işlemi başarılı!';
    } elseif ($_GET['success'] === 'cancel') {
        $success = 'Bilet iptal işlemi başarılı!';
    }
}

// Handle ticket cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ticket'])) {
    $ticket_id = $_POST['ticket_id'] ?? '';

    // Get ticket details
    $stmt = $db->prepare("
        SELECT t.*, tr.departure_time
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        WHERE t.id = ? AND t.user_id = ? AND t.status = 'active'
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
    $ticket = $stmt->fetch();

    if ($ticket) {
        $departure_time = strtotime($ticket['departure_time']);
        $current_time = time();
        $time_diff = ($departure_time - $current_time) / 3600; // Convert to hours

        if ($time_diff < 1) {
            $error = 'Kalkış saatine 1 saatten az kaldığı için bilet iptal edilemez.';
        } else {
            $db->beginTransaction();

            try {
                // Update ticket status
                $stmt = $db->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$ticket_id]);

                // Delete booked seat
                $stmt = $db->prepare("DELETE FROM booked_seats WHERE ticket_id = ?");
                $stmt->execute([$ticket_id]);

                // Refund to user balance
                $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$ticket['total_price'], $_SESSION['user_id']]);

                $db->commit();

                header('Location: /user/tickets.php?success=cancel');
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Bilet iptal edilirken bir hata oluştu.';
            }
        }
    }
}

// Get user tickets
$stmt = $db->prepare("
    SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time,
           bc.name as company_name
    FROM tickets t
    JOIN trips tr ON t.trip_id = tr.id
    JOIN bus_company bc ON tr.company_id = bc.id
    WHERE t.user_id = ?
    ORDER BY tr.departure_time DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll();

$currentUser = getCurrentUser();

$pageTitle = 'Biletlerim';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="bi bi-ticket-perforated"></i> Biletlerim
                    <span class="badge bg-primary"><?php echo count($tickets); ?></span>
                </h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info mb-4">
                    <i class="bi bi-wallet2"></i> Mevcut Bakiye:
                    <strong><?php echo number_format($currentUser['balance'], 2); ?> TL</strong>
                </div>

                <?php if (count($tickets) === 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Henüz bilet satın almadınız.
                        <a href="/index.php" class="alert-link">Sefer ara</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Firma</th>
                                    <th>Güzergah</th>
                                    <th>Tarih & Saat</th>
                                    <th>Koltuk</th>
                                    <th>Fiyat</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <?php
                                    $departure_time = strtotime($ticket['departure_time']);
                                    $current_time = time();
                                    $time_diff = ($departure_time - $current_time) / 3600;
                                    $can_cancel = $ticket['status'] === 'active' && $time_diff >= 1;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ticket['company_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($ticket['departure_city']); ?>
                                            <i class="bi bi-arrow-right"></i>
                                            <?php echo htmlspecialchars($ticket['destination_city']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y', strtotime($ticket['departure_time'])); ?><br>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($ticket['departure_time'])); ?> -
                                                <?php echo date('H:i', strtotime($ticket['arrival_time'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                Koltuk <?php echo $ticket['seat_number']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($ticket['total_price'], 2); ?> TL</td>
                                        <td>
                                            <?php if ($ticket['status'] === 'active'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">İptal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/user/download_pdf.php?ticket_id=<?php echo $ticket['id']; ?>"
                                                   class="btn btn-info" target="_blank">
                                                    <i class="bi bi-download"></i> PDF
                                                </a>
                                                <?php if ($can_cancel): ?>
                                                    <form method="POST" class="d-inline"
                                                          onsubmit="return confirmDelete('Bu bileti iptal etmek istediğinizden emin misiniz?')">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                        <button type="submit" name="cancel_ticket" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-x-circle"></i> İptal
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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
