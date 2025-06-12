<?php
require_once 'includes/config.php';

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
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Alternatif</h6>
                                <h2 class="mt-2 mb-0"><?= $totalAlternatif ?? 0 ?></h2>
                            </div>
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-primary-dark">
                        <a href="alternatif.php" class="text-white text-decoration-none">
                            Kelola Alternatif <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Kriteria</h6>
                                <h2 class="mt-2 mb-0"><?= $totalKriteria ?? 0 ?></h2>
                            </div>
                            <i class="bi bi-list-check fs-1"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-success-dark">
                        <a href="kriteria.php" class="text-white text-decoration-none">
                            Kelola Kriteria <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Penilaian Lengkap</h6>
                                <h2 class="mt-2 mb-0"><?= $completedAssessments ?? 0 ?></h2>
                            </div>
                            <i class="bi bi-clipboard-check fs-1"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-warning-dark">
                        <a href="penilaian.php" class="text-white text-decoration-none">
                            Input Penilaian <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Perhitungan Terakhir</h6>
                                <p class="mt-2 mb-0">
                                    <?= $lastCalculation ? date('d/m/Y H:i', strtotime($lastCalculation)) : 'Belum ada' ?>
                                </p>
                            </div>
                            <i class="bi bi-calculator fs-1"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-info-dark">
                        <a href="hasil.php" class="text-white text-decoration-none">
                            Lihat Hasil <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Sistem Pendukung Keputusan - Metode Weighted Product (WP)</h4>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
