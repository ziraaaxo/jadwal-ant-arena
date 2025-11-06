<?php
/**
 * admin-stats.php
 * API endpoint untuk mendapatkan data statistik jadwal
 */
require_once 'config.php';

// Set header JSON
header('Content-Type: application/json');

// Cek apakah user sudah login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $conn = getConnection();
    
    // Total jadwal/baris
    $totalJadwal = $conn->query("SELECT COUNT(*) as total FROM jadwal")->fetch_assoc()['total'];
    
    // Total slot (jadwal x 7 hari)
    $totalSlots = $totalJadwal * 7;
    
    // Hitung slot yang tersedia
    $slotTersedia = 0;
    $slotTerisi = 0;
    
    $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
    $result = $conn->query("SELECT * FROM jadwal");
    
    while ($row = $result->fetch_assoc()) {
        foreach ($days as $day) {
            if (strtolower(trim($row[$day])) === 'tersedia' || trim($row[$day]) === '-' || trim($row[$day]) === '') {
                $slotTersedia++;
            } else {
                $slotTerisi++;
            }
        }
    }
    
    // Tim yang paling sering muncul (top 5)
    $teamCount = [];
    $result = $conn->query("SELECT * FROM jadwal");
    
    while ($row = $result->fetch_assoc()) {
        foreach ($days as $day) {
            $team = trim($row[$day]);
            if (!empty($team) && strtolower($team) !== 'tersedia' && $team !== '-') {
                if (!isset($teamCount[$team])) {
                    $teamCount[$team] = 0;
                }
                $teamCount[$team]++;
            }
        }
    }
    
    arsort($teamCount);
    $topTeams = array_slice($teamCount, 0, 5, true);
    
    // Data per hari (slot terisi per hari)
    $perHari = [];
    foreach ($days as $day) {
        $terisi = 0;
        $result = $conn->query("SELECT * FROM jadwal");
        while ($row = $result->fetch_assoc()) {
            $team = trim($row[$day]);
            if (!empty($team) && strtolower($team) !== 'tersedia' && $team !== '-') {
                $terisi++;
            }
        }
        $perHari[$day] = $terisi;
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_jadwal' => $totalJadwal,
            'total_slots' => $totalSlots,
            'slot_tersedia' => $slotTersedia,
            'slot_terisi' => $slotTerisi,
            'persentase_terisi' => $totalSlots > 0 ? round(($slotTerisi / $totalSlots) * 100, 1) : 0,
            'top_teams' => $topTeams,
            'per_hari' => $perHari
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
