<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$conn = getConnection();

// Hitung total pemasukan
$sqlPemasukan = "SELECT COALESCE(SUM(nominal), 0) as total FROM transaksi WHERE kategori = 'pemasukan'";
$resultPemasukan = $conn->query($sqlPemasukan);
$totalPemasukan = $resultPemasukan->fetch_assoc()['total'];

// Hitung total pengeluaran
$sqlPengeluaran = "SELECT COALESCE(SUM(nominal), 0) as total FROM transaksi WHERE kategori = 'pengeluaran'";
$resultPengeluaran = $conn->query($sqlPengeluaran);
$totalPengeluaran = $resultPengeluaran->fetch_assoc()['total'];

// Hitung saldo
$saldo = $totalPemasukan - $totalPengeluaran;

// Data transaksi per bulan (6 bulan terakhir)
$sqlPerBulan = "SELECT 
    DATE_FORMAT(tanggal, '%Y-%m') as bulan,
    kategori,
    SUM(nominal) as total
FROM transaksi
WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(tanggal, '%Y-%m'), kategori
ORDER BY bulan ASC";

$resultPerBulan = $conn->query($sqlPerBulan);
$dataPerBulan = [
    'labels' => [],
    'pemasukan' => [],
    'pengeluaran' => []
];

$bulanData = [];
while ($row = $resultPerBulan->fetch_assoc()) {
    $bulan = $row['bulan'];
    if (!isset($bulanData[$bulan])) {
        $bulanData[$bulan] = ['pemasukan' => 0, 'pengeluaran' => 0];
    }
    $bulanData[$bulan][$row['kategori']] = (float)$row['total'];
}

// Format data untuk chart
foreach ($bulanData as $bulan => $data) {
    $dataPerBulan['labels'][] = date('M Y', strtotime($bulan . '-01'));
    $dataPerBulan['pemasukan'][] = $data['pemasukan'];
    $dataPerBulan['pengeluaran'][] = $data['pengeluaran'];
}

// Data transaksi terbaru (5 terakhir)
$sqlTerbaru = "SELECT * FROM transaksi ORDER BY tanggal DESC, id DESC LIMIT 5";
$resultTerbaru = $conn->query($sqlTerbaru);
$transaksiTerbaru = [];
while ($row = $resultTerbaru->fetch_assoc()) {
    $transaksiTerbaru[] = $row;
}

$conn->close();

echo json_encode([
    'success' => true,
    'data' => [
        'total_pemasukan' => (float)$totalPemasukan,
        'total_pengeluaran' => (float)$totalPengeluaran,
        'saldo' => (float)$saldo,
        'per_bulan' => $dataPerBulan,
        'transaksi_terbaru' => $transaksiTerbaru
    ]
]);
