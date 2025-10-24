<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($allowedRoles) {
    requireLogin();

    if (!in_array($_SESSION['role'], (array)$allowedRoles)) {
        header('Location: /index.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    require_once __DIR__ . '/../config/database.php';
    global $db;

    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function logout() {
    session_destroy();
    header('Location: /index.php');
    exit;
}
