<?php
/**
 * admin-reset-password.php
 * Use this script to reset the admin user's password to 'admin123'.
 * IMPORTANT: Delete this file after use (it's only intended for local development).
 *
 * Usage (open in browser):
 *  - Step 1 (preview): http://localhost/ant-arena-jadwal/admin-reset-password.php
 *  - Step 2 (execute reset): http://localhost/ant-arena-jadwal/admin-reset-password.php?confirm=1
 */
require_once 'config.php';

// Safety: only allow from localhost
$allowedHosts = ['127.0.0.1', '::1', 'localhost'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedHosts)) {
    echo "Access denied. Run this script from the same machine (localhost).";
    exit();
}

if (!isset($_GET['confirm'])) {
    echo "<h3>Reset Admin Password — Preview</h3>";
    echo "<p>This script will set the admin user's password to <strong>admin123</strong>.</p>";
    echo "<p>To execute the reset, add <code>?confirm=1</code> to the URL.</p>";
    echo "<p><strong>Warning:</strong> Delete this file after successful reset.</p>";
    echo "<p><a href='admin-reset-password.php?confirm=1'>Reset password now</a></p>";
    exit();
}

// Perform reset
try {
    $conn = getConnection();
    $newPassword = 'admin123';
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
    $stmt->bind_param('s', $hash);
    if ($stmt->execute()) {
        echo "<h3>Password berhasil di-reset</h3>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password baru: <strong>{$newPassword}</strong></p>";
        echo "<p>Silakan login di <a href='admin-login.php'>admin-login.php</a>.</p>";
        echo "<p><strong>Hapus file ini setelah digunakan!</strong></p>";
    } else {
        echo "Gagal mengupdate password: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>