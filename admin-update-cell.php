<?php
/**
 * admin-update-cell.php
 * AJAX endpoint untuk update satu kolom tim pada jadwal
 */
require_once 'config.php';

// Set header JSON
header('Content-Type: application/json');

// Cek apakah user sudah login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Silakan login terlebih dahulu']);
    exit();
}

// Cek method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Ambil parameter
$jadwalId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$day = isset($_POST['day']) ? $_POST['day'] : '';
$value = isset($_POST['value']) ? trim($_POST['value']) : '';

// Validasi input
if ($jadwalId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID jadwal tidak valid']);
    exit();
}

// Validasi nama hari (kolom)
$validDays = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
if (!in_array($day, $validDays)) {
    echo json_encode(['success' => false, 'message' => 'Nama hari tidak valid']);
    exit();
}

if (empty($value)) {
    echo json_encode(['success' => false, 'message' => 'Nama tim tidak boleh kosong']);
    exit();
}

// Update database
try {
    $conn = getConnection();
    
    // Gunakan prepared statement dengan dynamic column name (aman karena sudah divalidasi)
    $sql = "UPDATE jadwal SET $day = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Gagal prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('si', $value, $jadwalId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Data berhasil diupdate',
                'data' => [
                    'id' => $jadwalId,
                    'day' => $day,
                    'value' => $value
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Tidak ada perubahan data atau ID tidak ditemukan'
            ]);
        }
    } else {
        throw new Exception('Gagal execute query: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
