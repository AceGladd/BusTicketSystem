<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Lütfen giriş yapın']);
    exit;
}

$coupon_code = trim($_POST['coupon_code'] ?? '');
$trip_id = $_POST['trip_id'] ?? '';

if (empty($coupon_code) || empty($trip_id)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

// Get trip details
$stmt = $db->prepare("SELECT company_id FROM trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    echo json_encode(['success' => false, 'message' => 'Sefer bulunamadı']);
    exit;
}

// Validate coupon
$stmt = $db->prepare("
    SELECT *
    FROM coupons
    WHERE code = ?
      AND (company_id IS NULL OR company_id = ?)
      AND expire_date >= datetime('now')
      AND usage_count < usage_limit
");
$stmt->execute([$coupon_code, $trip['company_id']]);
$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz veya süresi dolmuş kupon']);
    exit;
}

// Check if user already used this coupon
$stmt = $db->prepare("
    SELECT COUNT(*) as count
    FROM user_coupons
    WHERE user_id = ? AND coupon_id = ?
");
$stmt->execute([$_SESSION['user_id'], $coupon['id']]);
$result = $stmt->fetch();

if ($result['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Bu kuponu daha önce kullandınız']);
    exit;
}

echo json_encode([
    'success' => true,
    'discount' => $coupon['discount'],
    'coupon_id' => $coupon['id']
]);
