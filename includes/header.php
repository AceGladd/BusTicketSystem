<?php
require_once __DIR__ . '/auth.php';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Bilet Satın Alma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/index.php">
                <i class="bi bi-bus-front"></i> Bilet Satın Alma
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">Ana Sayfa</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/index.php">
                                    <i class="bi bi-shield-lock"></i> Admin Panel
                                </a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'firma_admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/firma_admin/index.php">
                                    <i class="bi bi-building"></i> Firma Panel
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/user/tickets.php">
                                    <i class="bi bi-ticket-perforated"></i> Biletlerim
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['full_name']); ?>
                                <?php if ($_SESSION['role'] === 'user'): ?>
                                    <span class="badge bg-success"><?php echo number_format($currentUser['balance'], 2); ?> TL</span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($_SESSION['role'] === 'user'): ?>
                                    <li><a class="dropdown-item" href="/user/profile.php">Profil</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="/logout.php">Çıkış Yap</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Giriş Yap</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register.php">Kayıt Ol</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container my-4">
