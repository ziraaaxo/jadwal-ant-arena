<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $action = $_POST['action'] ?? '';

    if (empty($id) || empty($action)) {
        setError("Data tidak lengkap");
        header("Location: admin-reservasi.php");
        exit;
    }

    // Validasi action
    if (!in_array($action, ['approve', 'reject'])) {
        setError("Aksi tidak valid");
        header("Location: admin-reservasi.php");
        exit;
    }

    // Ambil data reservasi
    $stmt = $conn->prepare("SELECT * FROM reservasi WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservasi = $result->fetch_assoc();
    $stmt->close();

    if (!$reservasi) {
        setError("Reservasi tidak ditemukan");
        header("Location: admin-reservasi.php");
        exit;
    }

    // Pastikan reservasi masih pending
    if ($reservasi['status'] !== 'pending') {
        setError("Reservasi sudah diproses sebelumnya");
        header("Location: admin-reservasi.php");
        exit;
    }

    // Update status reservasi
    $new_status = $action === 'approve' ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE reservasi SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);

    if ($stmt->execute()) {
        // Jika disetujui, update jadwal
        if ($action === 'approve') {
            // Update jadwal dengan nama tim
            $hari = $reservasi['hari'];
            $jam = $reservasi['jam'];
            $nama_tim = $reservasi['nama_tim'];

            $stmt = $conn->prepare("UPDATE jadwal SET $hari = ? WHERE jam = ?");
            $stmt->bind_param("ss", $nama_tim, $jam);
            
            if ($stmt->execute()) {
                setSuccess("Reservasi berhasil disetujui dan jadwal telah diperbarui");
            } else {
                setError("Reservasi disetujui tetapi gagal memperbarui jadwal");
            }
        } else {
            setSuccess("Reservasi berhasil ditolak");
        }
    } else {
        setError("Gagal memproses reservasi: " . $conn->error);
    }

    $stmt->close();
    header("Location: admin-reservasi.php");
    exit;
} else {
    header("Location: admin-reservasi.php");
    exit;
}