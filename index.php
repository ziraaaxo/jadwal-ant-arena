<?php
require_once 'config.php';

// Ambil data jadwal dari database & siapkan handler reservasi
$conn = getConnection();

// Kumpulkan pilihan jam (untuk form reservasi)
$jamOptions = [];
$resJam = $conn->query("SELECT jam FROM jadwal ORDER BY id ASC");
if ($resJam) {
    while ($r = $resJam->fetch_assoc()) {
        $jamOptions[] = $r['jam'];
    }
}

// Handler submit reservasi
$successMessage = '';
$errorMessage = '';
$formData = [
    'nama_tim' => '',
    'email' => '',
    'no_telepon' => '',
    'hari' => '',
    'jam' => '',
    'tanggal_mulai' => '',
    'pesan' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_action']) && $_POST['reserve_action'] === 'tambah') {
    // Ambil dan sanitasi input
    $formData['nama_tim'] = trim($_POST['nama_tim'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['no_telepon'] = trim($_POST['no_telepon'] ?? '');
    $formData['hari'] = trim($_POST['hari'] ?? '');
    $formData['jam'] = trim($_POST['jam'] ?? '');
    $formData['tanggal_mulai'] = trim($_POST['tanggal_mulai'] ?? '');
    $formData['pesan'] = trim($_POST['pesan'] ?? '');

    $allowedDays = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu'];

    // Validasi
    $errors = [];
    if ($formData['nama_tim'] === '') $errors[] = 'Nama tim wajib diisi';
    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if ($formData['no_telepon'] === '' || !preg_match('/^[0-9+\-\s]{8,20}$/', $formData['no_telepon'])) $errors[] = 'No. telepon tidak valid';
    if (!in_array(strtolower($formData['hari']), $allowedDays, true)) $errors[] = 'Hari tidak valid';
    if ($formData['jam'] === '' || !in_array($formData['jam'], $jamOptions, true)) $errors[] = 'Jam tidak valid';
    // Tanggal harus format YYYY-MM-DD dan hari sesuai
    $tanggalValid = DateTime::createFromFormat('Y-m-d', $formData['tanggal_mulai']);
    $tanggalErrors = DateTime::getLastErrors();
    // DateTime::getLastErrors() may return false on some PHP setups; guard before indexing
    $dateHasErrors = false;
    if ($tanggalErrors !== false && is_array($tanggalErrors)) {
        $dateHasErrors = ($tanggalErrors['warning_count'] > 0 || $tanggalErrors['error_count'] > 0);
    }
    if (!$tanggalValid || $dateHasErrors) {
        $errors[] = 'Tanggal tidak valid';
    } else {
        $tanggalValid->setTime(0,0,0);
        $today = new DateTime('today');
        if ($tanggalValid < $today) $errors[] = 'Tanggal tidak boleh lewat';
        // Cocokkan hari-of-week dengan pilihan hari (opsional untuk membantu admin)
        $hariIndex = (int)$tanggalValid->format('N'); // 1=Senin ... 7=Minggu
        $mapHari = [1=>'senin',2=>'selasa',3=>'rabu',4=>'kamis',5=>'jumat',6=>'sabtu',7=>'minggu'];
        if (isset($mapHari[$hariIndex]) && $mapHari[$hariIndex] !== strtolower($formData['hari'])) {
            // Tidak fatal, tapi beri info agar user menyadari
            // Kita tetap izinkan submit, admin akan verifikasi.
        }
    }

    if (empty($errors)) {
        // Insert ke tabel reservasi (status default pending)
        $stmt = $conn->prepare("INSERT INTO reservasi (nama_tim, email, no_telepon, hari, jam, pesan, tanggal_mulai) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $hariLower = strtolower($formData['hari']);
            $stmt->bind_param('sssssss', $formData['nama_tim'], $formData['email'], $formData['no_telepon'], $hariLower, $formData['jam'], $formData['pesan'], $formData['tanggal_mulai']);
            if ($stmt->execute()) {
                $successMessage = 'Permintaan reservasi berhasil dikirim. Status: pending. Admin akan mengonfirmasi.';
                // Reset form
                $formData = [
                    'nama_tim' => '',
                    'email' => '',
                    'no_telepon' => '',
                    'hari' => '',
                    'jam' => '',
                    'tanggal_mulai' => '',
                    'pesan' => ''
                ];
            } else {
                $errorMessage = 'Gagal menyimpan reservasi: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $errorMessage = 'Gagal menyiapkan query reservasi.';
        }
    } else {
        $errorMessage = implode('. ', $errors);
    }
}

// Data jadwal untuk tabel
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

<body class="site-index">
    <div class="container site-container">
        <section id="jadwal" class="jadwal">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-calendar-week" style="color: var(--primary-gradient-start);"></i>
                        Jadwal Operasional
                    </h2>
                    <p class="mb-0" style="color: var(--text-secondary); font-size: 0.95rem;">Informasi Jadwal dan
                        Reservasi</p>
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
                        // Fungsi helper untuk menampilkan nilai atau "Tersedia" jika kosong
                        $showValue = function($val) {
                            return empty(trim($val)) ? '<span class="text-success">Tersedia</span>' : htmlspecialchars($val);
                        };
                        echo "<td>" . $showValue($row['senin']) . "</td>";
                        echo "<td>" . $showValue($row['selasa']) . "</td>";
                        echo "<td>" . $showValue($row['rabu']) . "</td>";
                        echo "<td>" . $showValue($row['kamis']) . "</td>";
                        echo "<td>" . $showValue($row['jumat']) . "</td>";
                        echo "<td>" . $showValue($row['sabtu']) . "</td>";
                        echo "<td>" . $showValue($row['minggu']) . "</td>";
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

        <!-- Reservasi section -->
        <section id="reservasi" class="mt-5">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-calendar-plus"></i> Ajukan Reservasi</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($successMessage)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle"></i> <?= $successMessage ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($errorMessage)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?= $errorMessage ?>
                            </div>
                            <?php endif; ?>
                            <form method="post" class="row g-3">
                                <input type="hidden" name="reserve_action" value="tambah">
                                <div class="col-12">
                                    <label for="nama_tim" class="form-label">Nama Tim</label>
                                    <input type="text" class="form-control" id="nama_tim" name="nama_tim" required
                                        value="<?= htmlspecialchars($formData['nama_tim']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                        value="<?= htmlspecialchars($formData['email']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="no_telepon" class="form-label">No. Telepon/WA</label>
                                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" required
                                        value="<?= htmlspecialchars($formData['no_telepon']) ?>"
                                        placeholder="08xxxxxxxxxx">
                                </div>
                                <div class="col-md-6">
                                    <label for="hari" class="form-label">Hari</label>
                                    <select class="form-select" id="hari" name="hari" required>
                                        <option value="" disabled <?= $formData['hari']===''?'selected':''; ?>>Pilih
                                            hari</option>
                                        <?php foreach (['senin','selasa','rabu','kamis','jumat','sabtu','minggu'] as $h): ?>
                                        <option value="<?= $h ?>"
                                            <?= strtolower($formData['hari'])===$h?'selected':''; ?>><?= ucfirst($h) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="jam" class="form-label">Jam</label>
                                    <select class="form-select" id="jam" name="jam" required>
                                        <option value="" disabled <?= $formData['jam']===''?'selected':''; ?>>Pilih jam
                                        </option>
                                        <?php foreach ($jamOptions as $jam): ?>
                                        <option value="<?= htmlspecialchars($jam) ?>"
                                            <?= $formData['jam']===$jam?'selected':''; ?>><?= htmlspecialchars($jam) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="tanggal_mulai" class="form-label">Tanggal</label>
                                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai"
                                        required value="<?= htmlspecialchars($formData['tanggal_mulai']) ?>"
                                        min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-12">
                                    <label for="pesan" class="form-label">Catatan (opsional)</label>
                                    <textarea class="form-control" id="pesan" name="pesan" rows="3"
                                        placeholder="Keterangan tambahan..."><?= htmlspecialchars($formData['pesan']) ?></textarea>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Kirim
                                        Permintaan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informasi Reservasi</h5>
                        </div>
                        <div class="card-body">
                            <ul class="mb-3" style="color: var(--text-secondary);">
                                <li>Pilih hari dan jam sesuai tabel jadwal di atas.</li>
                                <li>Permintaan Anda akan berstatus <strong>pending</strong> sampai dikonfirmasi admin.
                                </li>
                                <li>Admin akan menghubungi melalui email/WA untuk konfirmasi.</li>
                            </ul>
                            <div class="alert alert-light border mb-3" role="alert">
                                <i class="bi bi-exclamation-circle"></i> Jika jadwal menampilkan nama tim pada hari/jam
                                tertentu, slot tersebut kemungkinan sudah terisi.
                            </div>

                            <!-- Tracking form -->
                            <div class="mb-3">
                                <label for="track_query" class="form-label"><strong>Lacak Reservasi</strong></label>
                                <div class="input-group">
                                    <input type="text" id="track_query" class="form-control"
                                        placeholder="Masukkan email atau nomor telepon"
                                        aria-label="Email atau No. Telepon">
                                    <button class="btn btn-outline-primary" id="trackBtn" type="button"><i
                                            class="bi bi-search"></i> Lacak</button>
                                </div>
                                <div class="form-text" style="color:var(--text-secondary);">Lacak menggunakan email atau
                                    nomor telepon yang Anda gunakan saat mengajukan reservasi.</div>
                            </div>

                            <div id="trackResultArea" style="min-height:60px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- gallery section -->
        <section id="gallery" class="gallery mt-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-images" style="color: var(--primary-gradient-start);"></i>
                        Galeri Ant Arena
                    </h2>
                    <p class="mb-0" style="color: var(--text-secondary); font-size: 0.95rem;">Lihat koleksi foto kami
                    </p>
                </div>
            </div>
            <?php
            // Kumpulkan daftar gambar dari folder assets (jpg, jpeg, png, webp, gif)
            $imageFiles = glob(__DIR__ . '/assets/galeri/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
            $imageUrls = [];
            foreach ($imageFiles as $filePath) {
                $imageUrls[] = 'assets/galeri/' . basename($filePath);
            }
            // Jika tidak ada gambar ditemukan, fallback ke galeri.jpg
            if (empty($imageUrls)) {
                $imageUrls = ['assets/galeri/galeri1.png'];
            }
            // Pastikan minimal 3 item agar selalu ada kiri, tengah, kanan
            while (count($imageUrls) < 3) {
                $imageUrls[] = $imageUrls[count($imageUrls) % max(1, count($imageUrls))];
            }
            ?>

            <div class="gallery-slider">
                <button class="slider-btn slider-prev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
                <div class="gallery-viewport">
                    <!-- Items akan diinject via JS; berikut fallback tanpa JS -->
                    <noscript>
                        <div class="gallery-item is-prev"><img src="<?= htmlspecialchars($imageUrls[0]) ?>"
                                alt="Galeri 1"></div>
                        <div class="gallery-item is-current"><img src="<?= htmlspecialchars($imageUrls[1]) ?>"
                                alt="Galeri 2"></div>
                        <div class="gallery-item is-next"><img src="<?= htmlspecialchars($imageUrls[2]) ?>"
                                alt="Galeri 3"></div>
                    </noscript>
                </div>
                <button class="slider-btn slider-next" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
            </div>
        </section>
        <!-- end gallery section -->
        <!-- fasilitas section (mirip galeri) -->
        <section id="fasilitas" class="gallery mt-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="bi bi-building" style="color: var(--primary-gradient-start);"></i>
                        Fasilitas Ant Arena
                    </h2>
                    <p class="mb-0" style="color: var(--text-secondary); font-size: 0.95rem;">Lihat fasilitas
                    </p>
                </div>
            </div>
            <?php
            // Kumpulkan daftar gambar dari folder assets/fasilitas
            $fasilitasFiles = glob(__DIR__ . '/assets/fasilitas/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
            $fasilitasUrls = [];
            foreach ($fasilitasFiles as $filePath) {
                $fasilitasUrls[] = 'assets/fasilitas/' . basename($filePath);
            }
            // Jika tidak ada gambar ditemukan, fallback ke galeri image
            if (empty($fasilitasUrls)) {
                $fasilitasUrls = ['assets/galeri/galeri1.png'];
            }
            // Pastikan minimal 3 item agar selalu ada kiri, tengah, kanan
            while (count($fasilitasUrls) < 3) {
                $fasilitasUrls[] = $fasilitasUrls[count($fasilitasUrls) % max(1, count($fasilitasUrls))];
            }
            ?>

            <div class="gallery-slider">
                <button class="slider-btn slider-prev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
                <div class="gallery-viewport fasilitas-viewport">
                    <!-- Items akan diinject via JS; berikut fallback tanpa JS -->
                    <noscript>
                        <div class="gallery-item is-prev"><img src="<?= htmlspecialchars($fasilitasUrls[0]) ?>"
                                alt="Fasilitas 1"></div>
                        <div class="gallery-item is-current"><img src="<?= htmlspecialchars($fasilitasUrls[1]) ?>"
                                alt="Fasilitas 2"></div>
                        <div class="gallery-item is-next"><img src="<?= htmlspecialchars($fasilitasUrls[2]) ?>"
                                alt="Fasilitas 3"></div>
                    </noscript>
                </div>
                <button class="slider-btn slider-next" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
            </div>
        </section>
        <!-- end fasilitas section -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Galeri slider: tampilkan 1 foto tengah besar + 2 foto samping kecil, geser otomatis looping
    (function() {
        const images = <?php echo json_encode(array_values($imageUrls), JSON_UNESCAPED_SLASHES); ?>;
        const viewport = document.querySelector('.gallery-viewport');
        if (!viewport || !images || !images.length) return;

        // Buat elemen item untuk setiap gambar
        images.forEach((src, idx) => {
            const item = document.createElement('div');
            item.className = 'gallery-item';
            item.dataset.index = String(idx);
            const img = document.createElement('img');
            img.src = src;
            img.alt = 'Galeri ' + (idx + 1);
            item.appendChild(img);
            viewport.appendChild(item);
        });

        const items = Array.from(viewport.querySelectorAll('.gallery-item'));
        let current = 0;

        function applyClasses() {
            const n = items.length;
            const prev = (current - 1 + n) % n;
            const next = (current + 1) % n;
            items.forEach((el, i) => {
                el.classList.remove('is-prev', 'is-current', 'is-next', 'is-hidden');
                if (i === current) el.classList.add('is-current');
                else if (i === prev) el.classList.add('is-prev');
                else if (i === next) el.classList.add('is-next');
                else el.classList.add('is-hidden');
            });
        }

        function nextSlide() {
            current = (current + 1) % items.length;
            applyClasses();
        }

        function prevSlide() {
            current = (current - 1 + items.length) % items.length;
            applyClasses();
        }

        applyClasses();

        // Auto-play (10s) and controls
        let delay = 10000;
        let timer = setInterval(nextSlide, delay);

        function resetTimer() {
            clearInterval(timer);
            timer = setInterval(nextSlide, delay);
        }
        // Prefer attaching hover pause to the full slider container (so buttons also pause)
        const sliderEl = viewport.closest('.gallery-slider') || viewport;
        sliderEl.addEventListener('mouseenter', () => {
            clearInterval(timer);
        });
        sliderEl.addEventListener('mouseleave', () => {
            resetTimer();
        });

        // Wire prev/next buttons (if present)
        const btnPrev = sliderEl.querySelector('.slider-prev');
        const btnNext = sliderEl.querySelector('.slider-next');
        if (btnPrev) btnPrev.addEventListener('click', () => {
            prevSlide();
            resetTimer();
        });
        if (btnNext) btnNext.addEventListener('click', () => {
            nextSlide();
            resetTimer();
        });

        // Optional: swipe support for mobile
        let startX = null;
        viewport.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        }, {
            passive: true
        });
        viewport.addEventListener('touchend', (e) => {
            if (startX == null) return;
            const dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) {
                if (dx < 0) nextSlide();
                else prevSlide();
            }
            startX = null;
        });
    })();
    </script>
    <script>
    // Track reservation via AJAX
    (function() {
        const input = document.getElementById('track_query');
        const btn = document.getElementById('trackBtn');
        const area = document.getElementById('trackResultArea');

        if (!input || !btn || !area) return;

        function renderResults(payload) {
            area.innerHTML = '';
            if (!payload || !payload.success) {
                const err = (payload && payload.message) ? payload.message : 'Terjadi kesalahan saat pelacakan.';
                area.innerHTML = `<div class="alert alert-danger">${err}</div>`;
                return;
            }
            const data = payload.data || [];
            if (!data.length) {
                area.innerHTML =
                    '<div class="alert alert-info">Tidak ditemukan reservasi dengan data tersebut.</div>';
                return;
            }

            const container = document.createElement('div');
            data.forEach(r => {
                const card = document.createElement('div');
                card.className = 'card mb-2';
                card.innerHTML = `
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div style="font-weight:700">${escapeHtml(r.nama_tim || '')}</div>
                                <div class="small text-muted">${escapeHtml(r.email || '')} &middot; ${escapeHtml(r.no_telepon || '')}</div>
                                <div class="small mt-1">${escapeHtml(r.hari || '')}, ${escapeHtml(r.jam || '')} &middot; ${escapeHtml(r.tanggal_mulai || '')}</div>
                                ${r.pesan ? `<div class="small mt-1 text-secondary">Catatan: ${escapeHtml(r.pesan)}</div>` : ''}
                            </div>
                            <div class="text-end">
                                <div>${statusBadge(r.status || '')}</div>
                            </div>
                        </div>
                    </div>`;
                container.appendChild(card);
            });
            area.appendChild(container);
        }

        function statusBadge(status) {
            const s = (status || '').toLowerCase();
            if (s === 'approved') return '<span class="badge bg-success">Disetujui</span>';
            if (s === 'rejected') return '<span class="badge bg-danger">Ditolak</span>';
            return '<span class="badge bg-secondary">Pending</span>';
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
                '&quot;');
        }

        function doTrack() {
            const q = input.value.trim();
            if (!q) {
                area.innerHTML =
                    '<div class="alert alert-warning">Masukkan email atau nomor telepon untuk melacak.</div>';
                return;
            }
            btn.disabled = true;
            const original = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mencari...';

            fetch(`track-reservasi.php?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(json => renderResults(json))
                .catch(err => {
                    area.innerHTML = '<div class="alert alert-danger">Gagal terhubung ke server.</div>';
                    console.error(err);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = original;
                });
        }

        btn.addEventListener('click', doTrack);
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') doTrack();
        });
    })();
    </script>
    <script>
    // Fasilitas slider: mirror dari galeri tapi mengambil gambar dari assets/fasilitas
    (function() {
        const images = <?php echo json_encode(array_values($fasilitasUrls), JSON_UNESCAPED_SLASHES); ?>;
        const viewport = document.querySelector('.fasilitas-viewport');
        if (!viewport || !images || !images.length) return;

        // Buat elemen item untuk setiap gambar
        images.forEach((src, idx) => {
            const item = document.createElement('div');
            item.className = 'gallery-item';
            item.dataset.index = String(idx);
            const img = document.createElement('img');
            img.src = src;
            img.alt = 'Fasilitas ' + (idx + 1);
            item.appendChild(img);
            viewport.appendChild(item);
        });

        const items = Array.from(viewport.querySelectorAll('.gallery-item'));
        let current = 0;

        function applyClasses() {
            const n = items.length;
            const prev = (current - 1 + n) % n;
            const next = (current + 1) % n;
            items.forEach((el, i) => {
                el.classList.remove('is-prev', 'is-current', 'is-next', 'is-hidden');
                if (i === current) el.classList.add('is-current');
                else if (i === prev) el.classList.add('is-prev');
                else if (i === next) el.classList.add('is-next');
                else el.classList.add('is-hidden');
            });
        }

        function nextSlide() {
            current = (current + 1) % items.length;
            applyClasses();
        }

        function prevSlide() {
            current = (current - 1 + items.length) % items.length;
            applyClasses();
        }

        applyClasses();

        // Auto-play (10s) and controls
        let delay = 10000;
        let timer = setInterval(nextSlide, delay);

        function resetTimer() {
            clearInterval(timer);
            timer = setInterval(nextSlide, delay);
        }
        const sliderEl = viewport.closest('.gallery-slider') || viewport;
        sliderEl.addEventListener('mouseenter', () => {
            clearInterval(timer);
        });
        sliderEl.addEventListener('mouseleave', () => {
            resetTimer();
        });
        const btnPrev = sliderEl.querySelector('.slider-prev');
        const btnNext = sliderEl.querySelector('.slider-next');
        if (btnPrev) btnPrev.addEventListener('click', () => {
            prevSlide();
            resetTimer();
        });
        if (btnNext) btnNext.addEventListener('click', () => {
            nextSlide();
            resetTimer();
        });

        // Optional: swipe support for mobile
        let startX = null;
        viewport.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        }, {
            passive: true
        });
        viewport.addEventListener('touchend', (e) => {
            if (startX == null) return;
            const dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) {
                if (dx < 0) nextSlide();
                else prevSlide();
            }
            startX = null;
        });
    })();
    </script>
</body>

</html>