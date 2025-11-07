<?php
/**
 * admin-bulk-action.php
 * AJAX endpoint untuk bulk actions (sediakan semua / kosongkan semua)
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

// Ambil action
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validasi action
if (!in_array($action, ['set_available', 'clear_all', 'set_row_available', 'set_day_available'])) {
    echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    exit();
}

try {
    $conn = getConnection();
    
    if ($action === 'set_available') {
        // Set semua kolom hari menjadi empty string (treat as available in UI)
        $sql = "UPDATE jadwal SET 
                senin = '', 
                selasa = '', 
                rabu = '', 
                kamis = '', 
                jumat = '', 
                sabtu = '', 
                minggu = ''";
        
        if ($conn->query($sql)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Semua slot berhasil diisi dengan "Tersedia"',
                'affected_rows' => $conn->affected_rows
            ]);
        } else {
            throw new Exception('Gagal execute query: ' . $conn->error);
        }
        
    } else if ($action === 'clear_all') {
        // Kosongkan semua kolom hari (isi dengan string kosong atau "-")
        $sql = "UPDATE jadwal SET 
                senin = '-', 
                selasa = '-', 
                rabu = '-', 
                kamis = '-', 
                jumat = '-', 
                sabtu = '-', 
                minggu = '-'";
        
        if ($conn->query($sql)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Semua slot berhasil dikosongkan',
                'affected_rows' => $conn->affected_rows
            ]);
        } else {
            throw new Exception('Gagal execute query: ' . $conn->error);
        }
        
    } else if ($action === 'set_row_available') {
        // Set satu baris tertentu menjadi "Tersedia"
        $jadwalId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($jadwalId <= 0) {
            throw new Exception('ID jadwal tidak valid');
        }
        
    $stmt = $conn->prepare("UPDATE jadwal SET 
        senin = '', 
        selasa = '', 
        rabu = '', 
        kamis = '', 
        jumat = '', 
        sabtu = '', 
        minggu = ''
        WHERE id = ?");
        
        $stmt->bind_param('i', $jadwalId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Baris berhasil diisi dengan "Tersedia"',
                'affected_rows' => $stmt->affected_rows
            ]);
        } else {
            throw new Exception('Gagal execute query: ' . $stmt->error);
        }
        
        $stmt->close();
    } else if ($action === 'set_day_available') {
        // Set semua slot untuk hari tertentu menjadi "Tersedia"
        $day = isset($_POST['day']) ? $_POST['day'] : '';
        $validDays = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        
        if (!in_array($day, $validDays)) {
            throw new Exception('Hari tidak valid');
        }
        
    $sql = "UPDATE jadwal SET " . $conn->real_escape_string($day) . " = ''";
        
        if ($conn->query($sql)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Semua slot untuk hari ' . ucfirst($day) . ' berhasil disediakan',
                'affected_rows' => $conn->affected_rows
            ]);
        } else {
            throw new Exception('Gagal execute query: ' . $conn->error);
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
