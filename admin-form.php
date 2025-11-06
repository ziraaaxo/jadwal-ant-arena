<?php
require_once 'config.php';
requireLogin();

$conn = getConnection();
$isEdit = false;
$data = [
    'id' => '',
    'jam' => '',
    'senin' => '',
    'selasa' => '',
    'rabu' => '',
    'kamis' => '',
    'jumat' => '',
    'sabtu' => '',
    'minggu' => ''
];

// Jika mode edit, ambil data berdasarkan ID
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $isEdit = true;
    $id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM jadwal WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
    } else {
        header("Location: admin-dashboard.php");
        exit();
    }
    
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Tambah'; ?> Jadwal - Ant Arena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-dark navbar-custom mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-speedometer2"></i> Admin Dashboard - Ant Arena
            </span>
            <div class="d-flex">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                </span>
                <a href="admin-logout.php" class="btn btn-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row mb-3">
            <div class="col-12">
                <a href="admin-dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-<?php echo $isEdit ? 'pencil' : 'plus-circle'; ?>"></i>
                            <?php echo $isEdit ? 'Edit' : 'Tambah'; ?> Jadwal
                        </h4>
                    </div>
                    <div class="card-body">
                        <form action="admin-actions.php" method="POST">
                            <input type="hidden" name="action" value="<?php echo $isEdit ? 'update' : 'create'; ?>">
                            <?php if ($isEdit): ?>
                                <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="jam" class="form-label">Jam Operasional *</label>
                                    <input type="text" class="form-control" id="jam" name="jam" 
                                           value="<?php echo htmlspecialchars($data['jam']); ?>" 
                                           placeholder="Contoh: 08.00 - 11.00" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="senin" class="form-label">Senin</label>
                                    <input type="text" class="form-control" id="senin" name="senin" 
                                           value="<?php echo htmlspecialchars($data['senin']); ?>" 
                                           placeholder="Nama tim atau 'Tersedia'">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="selasa" class="form-label">Selasa</label>
                                    <input type="text" class="form-control" id="selasa" name="selasa" 
                                           value="<?php echo htmlspecialchars($data['selasa']); ?>" 
                                           placeholder="Nama tim atau 'Tersedia'">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="rabu" class="form-label">Rabu</label>
                                    <input type="text" class="form-control" id="rabu" name="rabu" 
                                           value="<?php echo htmlspecialchars($data['rabu']); ?>" 
                                           placeholder="Nama tim atau 'Tersedia'">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="kamis" class="form-label">Kamis</label>
                                    <input type="text" class="form-control" id="kamis" name="kamis" 
                                           value="<?php echo htmlspecialchars($data['kamis']); ?>" 
                                           placeholder="Nama tim atau 'Tersedia'">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="jumat" class="form-label">Jumat</label>
                                    <input type="text" class="form-control" id="jumat" name="jumat" 
                                           value="<?php echo htmlspecialchars($data['jumat']); ?>" 
                                           placeholder="Nama tim atau 'Tersedia'">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sabtu" class="form-label">Sabtu</label>
                                    <input type="text" class="form-control" id="sabtu" name="sabtu" 
                                           value="<?php echo htmlspecialchars($data['sabtu']); ?>" 
                                           placeholder="Nama tim atau 'Tersedia'">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <label for="minggu" class="form-label">Minggu</label>
                                    <input type="text" class="form-control" id="minggu" name="minggu" 
                                           value="<?php echo htmlspecialchars($data['minggu']); ?>" 
                                           placeholder="Nama tim atau 'Tersedia'">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> <?php echo $isEdit ? 'Update' : 'Simpan'; ?>
                                    </button>
                                    <a href="admin-dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Batal
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
