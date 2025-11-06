<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// CREATE - Tambah jadwal baru
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $jam = $_POST['jam'] ?? '';
    $senin = $_POST['senin'] ?? 'Tersedia';
    $selasa = $_POST['selasa'] ?? 'Tersedia';
    $rabu = $_POST['rabu'] ?? 'Tersedia';
    $kamis = $_POST['kamis'] ?? 'Tersedia';
    $jumat = $_POST['jumat'] ?? 'Tersedia';
    $sabtu = $_POST['sabtu'] ?? 'Tersedia';
    $minggu = $_POST['minggu'] ?? 'Tersedia';
    
    if (!empty($jam)) {
        $stmt = $conn->prepare("INSERT INTO jadwal (jam, senin, selasa, rabu, kamis, jumat, sabtu, minggu) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $jam, $senin, $selasa, $rabu, $kamis, $jumat, $sabtu, $minggu);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Jadwal berhasil ditambahkan!";
        } else {
            $_SESSION['error_message'] = "Gagal menambahkan jadwal: " . $stmt->error;
        }
        
        $stmt->close();
    }
    
    header("Location: admin-dashboard.php");
    exit();
}

// UPDATE - Edit jadwal
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $jam = $_POST['jam'] ?? '';
    $senin = $_POST['senin'] ?? 'Tersedia';
    $selasa = $_POST['selasa'] ?? 'Tersedia';
    $rabu = $_POST['rabu'] ?? 'Tersedia';
    $kamis = $_POST['kamis'] ?? 'Tersedia';
    $jumat = $_POST['jumat'] ?? 'Tersedia';
    $sabtu = $_POST['sabtu'] ?? 'Tersedia';
    $minggu = $_POST['minggu'] ?? 'Tersedia';
    
    if ($id > 0 && !empty($jam)) {
        $stmt = $conn->prepare("UPDATE jadwal SET jam = ?, senin = ?, selasa = ?, rabu = ?, kamis = ?, jumat = ?, sabtu = ?, minggu = ? WHERE id = ?");
        $stmt->bind_param("ssssssssi", $jam, $senin, $selasa, $rabu, $kamis, $jumat, $sabtu, $minggu, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Jadwal berhasil diupdate!";
        } else {
            $_SESSION['error_message'] = "Gagal mengupdate jadwal: " . $stmt->error;
        }
        
        $stmt->close();
    }
    
    header("Location: admin-dashboard.php");
    exit();
}

// DELETE - Hapus jadwal
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM jadwal WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Jadwal berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus jadwal: " . $stmt->error;
        }
        
        $stmt->close();
    }
    
    header("Location: admin-dashboard.php");
    exit();
}

$conn->close();

// Jika tidak ada action valid, redirect ke dashboard
header("Location: admin-dashboard.php");
exit();
?>
