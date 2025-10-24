<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('user');

$trip_id = $_GET['trip_id'] ?? '';
$error = '';
$success = '';

if (empty($trip_id)) {
    header('Location: /index.php');
    exit;
}

// Get trip details
$stmt = $db->prepare("
    SELECT t.*, bc.name as company_name,
           (t.capacity - COALESCE(bs_count.booked, 0)) as available_seats
    FROM trips t
    JOIN bus_company bc ON t.company_id = bc.id
    LEFT JOIN (
        SELECT trip_id, COUNT(*) as booked
        FROM booked_seats
        GROUP BY trip_id
    ) bs_count ON t.id = bs_count.trip_id
    WHERE t.id = ?
");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: /index.php');
    exit;
}

// Get booked seats
$stmt = $db->prepare("SELECT seat_number FROM booked_seats WHERE trip_id = ?");
$stmt->execute([$trip_id]);
$bookedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);

$currentUser = getCurrentUser();

// Handle purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seat_number = $_POST['seat_number'] ?? '';
    $coupon_id = $_POST['coupon_id'] ?? '';
    $coupon_discount = $_POST['coupon_discount'] ?? 0;

    if (empty($seat_number)) {
        $error = 'Lütfen bir koltuk seçin.';
    } elseif (in_array($seat_number, $bookedSeats)) {
        $error = 'Seçtiğiniz koltuk dolu.';
    } else {
        // Calculate final price
        $total_price = $trip['price'] - ($trip['price'] * $coupon_discount / 100);

        if ($currentUser['balance'] < $total_price) {
            $error = 'Yetersiz bakiye. Bakiyeniz: ' . number_format($currentUser['balance'], 2) . ' TL';
        } else {
            $db->beginTransaction();

            try {
                // Create ticket
                $ticket_id = Database::getInstance()->generateUUIDPublic();

                $stmt = $db->prepare("
                    INSERT INTO tickets (id, trip_id, user_id, seat_number, total_price, status)
                    VALUES (?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$ticket_id, $trip_id, $_SESSION['user_id'], $seat_number, $total_price]);

                // Book seat
                $booked_seat_id = Database::getInstance()->generateUUIDPublic();
                $stmt = $db->prepare("
                    INSERT INTO booked_seats (id, ticket_id, trip_id, seat_number)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$booked_seat_id, $ticket_id, $trip_id, $seat_number]);

                // Deduct from user balance
                $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$total_price, $_SESSION['user_id']]);

                // Record coupon usage if used
                if (!empty($coupon_id)) {
                    $user_coupon_id = Database::getInstance()->generateUUIDPublic();
                    $stmt = $db->prepare("
                        INSERT INTO user_coupons (id, coupon_id, user_id, ticket_id, is_used)
                        VALUES (?, ?, ?, ?, 1)
                    ");
                    $stmt->execute([$user_coupon_id, $coupon_id, $_SESSION['user_id'], $ticket_id]);

                    // Update coupon usage count
                    $stmt = $db->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?");
                    $stmt->execute([$coupon_id]);
                }

                $db->commit();

                header('Location: /user/tickets.php?success=purchase');
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Bilet satın alınırken bir hata oluştu: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Bilet Satın Al';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-ticket-perforated"></i> Bilet Satın Al</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <h5><?php echo htmlspecialchars($trip['company_name']); ?></h5>
                <p class="mb-2">
                    <i class="bi bi-geo-alt"></i>
                    <?php echo htmlspecialchars($trip['departure_city']); ?> -
                    <?php echo htmlspecialchars($trip['destination_city']); ?>
                </p>
                <p class="mb-2">
                    <i class="bi bi-calendar"></i>
                    <?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?>
                </p>
                <p class="mb-2">
                    <i class="bi bi-cash"></i>
                    <?php echo number_format($trip['price'], 2); ?> TL
                </p>
                <p class="mb-4">
                    <i class="bi bi-people"></i>
                    <?php echo $trip['available_seats']; ?> koltuk müsait
                </p>

                <form method="POST" id="purchaseForm">
                    <h5 class="mb-3">Koltuk Seçin</h5>

                    <div class="seat-selection">
                        <?php for ($i = 1; $i <= $trip['capacity']; $i++): ?>
                            <?php $isBooked = in_array($i, $bookedSeats); ?>
                            <div class="seat <?php echo $isBooked ? 'seat-booked' : ''; ?>"
                                 data-seat="<?php echo $i; ?>"
                                 onclick="selectSeat(<?php echo $i; ?>, <?php echo $isBooked ? 'true' : 'false'; ?>)">
                                <i class="bi bi-person"></i>
                                <div><?php echo $i; ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <input type="hidden" name="seat_number" id="seat_number" required>
                    <input type="hidden" name="coupon_id" id="coupon_id">
                    <input type="hidden" name="coupon_discount" id="coupon_discount" value="0">
                    <input type="hidden" id="trip_id" value="<?php echo htmlspecialchars($trip_id); ?>">
                    <input type="hidden" id="trip_price" value="<?php echo $trip['price']; ?>">

                    <div class="mt-4">
                        <label for="coupon_code" class="form-label">İndirim Kuponu (İsteğe Bağlı)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="coupon_code" placeholder="Kupon kodunu girin">
                            <button type="button" class="btn btn-outline-primary" onclick="applyCoupon()">
                                <i class="bi bi-check-circle"></i> Uygula
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">
                        <i class="bi bi-credit-card"></i> Ödeme Yap
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Ödeme Özeti</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Bilet Fiyatı:</span>
                    <strong><?php echo number_format($trip['price'], 2); ?> TL</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>İndirim:</span>
                    <strong class="text-success" id="discount_display">0.00 TL</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Toplam:</strong>
                    <strong class="text-primary fs-5" id="total_price_display">
                        <?php echo number_format($trip['price'], 2); ?> TL
                    </strong>
                </div>
                <div class="alert alert-info mb-0">
                    <small>
                        <i class="bi bi-wallet2"></i> Mevcut Bakiye:
                        <strong><?php echo number_format($currentUser['balance'], 2); ?> TL</strong>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateTotalPrice() {
    const priceElement = document.getElementById('trip_price');
    const couponDiscountElement = document.getElementById('coupon_discount');
    const discountDisplay = document.getElementById('discount_display');
    const totalDisplay = document.getElementById('total_price_display');

    if (priceElement && totalDisplay) {
        const basePrice = parseFloat(priceElement.value);
        const discount = couponDiscountElement ? parseFloat(couponDiscountElement.value) : 0;
        const discountAmount = basePrice * discount / 100;
        const totalPrice = basePrice - discountAmount;

        discountDisplay.textContent = discountAmount.toFixed(2) + ' TL';
        totalDisplay.textContent = totalPrice.toFixed(2) + ' TL';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
