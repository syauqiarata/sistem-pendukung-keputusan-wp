<?php
require_once 'config.php';

// Get statistics
try {
    // Count alternatives
    $stmt = $db->query("SELECT COUNT(*) FROM alternatif");
    $totalAlternatif = $stmt->fetchColumn();

    // Count criteria
    $stmt = $db->query("SELECT COUNT(*) FROM kriteria");
    $totalKriteria = $stmt->fetchColumn();

    // Count completed assessments
    $stmt = $db->query("
        SELECT COUNT(DISTINCT alternatif_id) as total 
        FROM penilaian 
        WHERE alternatif_id IN (
            SELECT alternatif_id 
            FROM penilaian 
            GROUP BY alternatif_id 
            HAVING COUNT(*) = (SELECT COUNT(*) FROM kriteria)
        )
    ");
    $completedAssessments = $stmt->fetchColumn();

    // Get last calculation
    $stmt = $db->query("
        SELECT MAX(tanggal_perhitungan) as last_calculation 
        FROM hasil_perhitungan
    ");
    $lastCalculation = $stmt->fetchColumn();

} catch(PDOException $e) {
    error_log("Error fetching dashboard statistics: " . $e->getMessage());
    $error = "Terjadi kesalahan saat mengambil data statistik";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand"><?= SITE_NAME ?></a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="index.php" class="nav-link active">Home</a>
                </li>
                <li class="nav-item">
                    <a href="alternatif.php" class="nav-link">Alternatif</a>
                </li>
                <li class="nav-item">
                    <a href="kriteria.php" class="nav-link">Kriteria</a>
                </li>
                <li class="nav-item">
                    <a href="penilaian.php" class="nav-link">Penilaian</a>
                </li>
                <li class="nav-item">
                    <a href="hasil.php" class="nav-link">Hasil</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <?php
        $flash = getFlash();
        if ($flash): 
        ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= $flash['message'] ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col">
                <div class="stats-card">
                    <h3>Total Alternatif</h3>
                    <p><?= $totalAlternatif ?? 0 ?></p>
                    <a href="alternatif.php" class="btn btn-primary">Kelola</a>
                </div>
            </div>
            
            <div class="col">
                <div class="stats-card">
                    <h3>Total Kriteria</h3>
                    <p><?= $totalKriteria ?? 0 ?></p>
                    <a href="kriteria.php" class="btn btn-primary">Kelola</a>
                </div>
            </div>
            
            <div class="col">
                <div class="stats-card">
                    <h3>Penilaian Lengkap</h3>
                    <p><?= $completedAssessments ?? 0 ?></p>
                    <a href="penilaian.php" class="btn btn-primary">Input</a>
                </div>
            </div>
            
            <div class="col">
                <div class="stats-card">
                    <h3>Perhitungan Terakhir</h3>
                    <p><?= $lastCalculation ? date('d/m/Y H:i', strtotime($lastCalculation)) : '-' ?></p>
                    <a href="hasil.php" class="btn btn-primary">Lihat</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Sistem Pendukung Keputusan - Metode WP</h2>
            </div>
                    <div class="card-body">
                        <h5>Langkah-langkah Perhitungan:</h5>
                        <ol>
                            <li>Input data alternatif yang akan dinilai</li>
                            <li>Tentukan kriteria beserta bobot dan tipenya (benefit/cost)</li>
                            <li>Lakukan penilaian untuk setiap alternatif berdasarkan kriteria</li>
                            <li>Sistem akan melakukan perhitungan menggunakan metode WP:
                                <ul>
                                    <li>Normalisasi bobot kriteria</li>
                                    <li>Menghitung nilai vektor S</li>
                                    <li>Menghitung nilai vektor V</li>
                                    <li>Melakukan perankingan</li>
                                </ul>
                            </li>
                            <li>Hasil perhitungan dapat dilihat pada menu Hasil</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
