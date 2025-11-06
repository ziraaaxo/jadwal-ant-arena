<?php
require_once 'config.php';

// Ambil data jadwal dari database
$conn = getConnection();
$sql = "SELECT * FROM jadwal ORDER BY id ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jadwal - Ant Arena</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="favicon.ico" />
</head>

<body>
    <div class="container site-container">
        <section id="jadwal" class="jadwal">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-calendar-week" style="color: var(--primary-gradient-start);"></i>
                        Jadwal Operasional
                    </h2>
                    <p class="mb-0" style="color: var(--text-secondary); font-size: 0.95rem;">Schedule & Booking
                        Information</p>
                </div>
                <a href="admin-login.php" class="btn btn-primary" style="text-decoration: none;">
                    <i class="bi bi-lock"></i> Admin
                </a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Jam</th>
                            <th>Senin</th>
                            <th>Selasa</th>
                            <th>Rabu</th>
                            <th>Kamis</th>
                            <th>Jumat</th>
                            <th>Sabtu</th>
                            <th>Minggu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><strong>" . htmlspecialchars($row['jam']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['senin']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['selasa']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['rabu']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['kamis']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['jumat']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['sabtu']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['minggu']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>Tidak ada data jadwal</td></tr>";
                }
                $conn->close();
                ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-center">
                <small style="color: var(--text-secondary);">
                    <i class="bi bi-info-circle"></i> Data diperbarui secara real-time oleh admin
                </small>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>