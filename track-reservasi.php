<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$conn = getConnection();

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode(['success' => false, 'message' => 'Query kosong']);
    exit;
}

// Decide whether q looks like an email or phone
$isEmail = filter_var($q, FILTER_VALIDATE_EMAIL) !== false;

try {
    if ($isEmail) {
        $stmt = $conn->prepare('SELECT id, nama_tim, email, no_telepon, hari, jam, tanggal_mulai, status, pesan, created_at FROM reservasi WHERE email = ? ORDER BY created_at DESC LIMIT 20');
        $stmt->bind_param('s', $q);
    } else {
        // normalize phone: remove spaces and common separators for comparison
        $normalized = preg_replace('/[^0-9+]/', '', $q);
        $stmt = $conn->prepare('SELECT id, nama_tim, email, no_telepon, hari, jam, tanggal_mulai, status, pesan, created_at FROM reservasi WHERE REPLACE(REPLACE(REPLACE(no_telepon, " ", ""), "-", ""), ".", "") = ? OR no_telepon = ? ORDER BY created_at DESC LIMIT 20');
        $stmt->bind_param('ss', $normalized, $q);
    }

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Query gagal dijalankan']);
        exit;
    }

    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }

    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}