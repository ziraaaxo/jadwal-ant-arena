<?php
require_once 'config.php';
requireLogin();

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConnection();
    
    switch ($action) {
        case 'create':
            $tanggal = $_POST['tanggal'] ?? '';
            $deskripsi = $_POST['deskripsi'] ?? '';
            $kategori = $_POST['kategori'] ?? '';
            $nominal = $_POST['nominal'] ?? 0;
            
            if (empty($tanggal) || empty($deskripsi) || empty($kategori) || empty($nominal)) {
                $_SESSION['error_message'] = 'Semua field harus diisi!';
                header("Location: admin-transaksi.php");
                exit();
            }
            
            $stmt = $conn->prepare("INSERT INTO transaksi (tanggal, deskripsi, kategori, nominal) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssd", $tanggal, $deskripsi, $kategori, $nominal);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Transaksi berhasil ditambahkan!';
            } else {
                $_SESSION['error_message'] = 'Gagal menambahkan transaksi: ' . $conn->error;
            }
            
            $stmt->close();
            break;
            
        case 'update':
            $id = $_POST['id'] ?? 0;
            $tanggal = $_POST['tanggal'] ?? '';
            $deskripsi = $_POST['deskripsi'] ?? '';
            $kategori = $_POST['kategori'] ?? '';
            $nominal = $_POST['nominal'] ?? 0;
            
            if (empty($id) || empty($tanggal) || empty($deskripsi) || empty($kategori) || empty($nominal)) {
                $_SESSION['error_message'] = 'Semua field harus diisi!';
                header("Location: admin-transaksi.php");
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE transaksi SET tanggal = ?, deskripsi = ?, kategori = ?, nominal = ? WHERE id = ?");
            $stmt->bind_param("sssdi", $tanggal, $deskripsi, $kategori, $nominal, $id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Transaksi berhasil diupdate!';
            } else {
                $_SESSION['error_message'] = 'Gagal mengupdate transaksi: ' . $conn->error;
            }
            
            $stmt->close();
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            
            if (empty($id)) {
                $_SESSION['error_message'] = 'ID transaksi tidak valid!';
                header("Location: admin-transaksi.php");
                exit();
            }
            
            $stmt = $conn->prepare("DELETE FROM transaksi WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Transaksi berhasil dihapus!';
            } else {
                $_SESSION['error_message'] = 'Gagal menghapus transaksi: ' . $conn->error;
            }
            
            $stmt->close();
            break;
            
        default:
            $_SESSION['error_message'] = 'Aksi tidak valid!';
    }
    
    $conn->close();
}

header("Location: admin-transaksi.php");
exit();
