<?php
require_once 'config.php';

// Function to calculate WP
function calculateWP() {
    global $db;
    try {
        // Get all alternatives
        $stmt = $db->query("SELECT * FROM alternatif");
        $alternatifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all criteria
        $stmt = $db->query("SELECT * FROM kriteria");
        $kriterias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all scores
        $stmt = $db->query("SELECT * FROM penilaian");
        $penilaians = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $penilaians[$row['alternatif_id']][$row['kriteria_id']] = $row['nilai'];
        }
        
        // Calculate S values
        $s_values = [];
        foreach ($alternatifs as $alternatif) {
            if (!isset($penilaians[$alternatif['id']])) continue;
            
            $s = 1;
            foreach ($kriterias as $kriteria) {
                if (!isset($penilaians[$alternatif['id']][$kriteria['id']])) continue;
                
                $nilai = $penilaians[$alternatif['id']][$kriteria['id']];
                $power = $kriteria['tipe'] === 'benefit' ? $kriteria['bobot'] : -$kriteria['bobot'];
                $s *= pow($nilai, $power);
            }
            $s_values[$alternatif['id']] = $s;
        }
        
        // Calculate total S
        $total_s = array_sum($s_values);
        
        // Calculate V values and store results
        $results = [];
        foreach ($alternatifs as $alternatif) {
            if (!isset($s_values[$alternatif['id']])) continue;
            
            $v = $s_values[$alternatif['id']] / $total_s;
            $results[] = [
                'alternatif_id' => $alternatif['id'],
                'nama' => $alternatif['nama'],
                'nilai_s' => $s_values[$alternatif['id']],
                'nilai_v' => $v
            ];
        }
        
        // Sort by V value descending
        usort($results, function($a, $b) {
            return $b['nilai_v'] <=> $a['nilai_v'];
        });
        
        // Add ranking
        foreach ($results as $index => $result) {
            $results[$index]['ranking'] = $index + 1;
        }
        
        // Store results in database
        $db->beginTransaction();
        
        // Clear old results
        $db->exec("DELETE FROM hasil");
        
        // Insert new results
        $stmt = $db->prepare("
            INSERT INTO hasil (alternatif_id, nilai_s, nilai_v, ranking, tanggal) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        foreach ($results as $result) {
            $stmt->execute([
                $result['alternatif_id'],
                $result['nilai_s'],
                $result['nilai_v'],
                $result['ranking']
            ]);
        }
        
        $db->commit();
        return $results;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

// Handle recalculation request
if (isset($_POST['recalculate'])) {
    try {
        $results = calculateWP();
        setFlash('success', 'Perhitungan berhasil diperbarui');
    } catch (Exception $e) {
        setFlash('danger', 'Gagal melakukan perhitungan: ' . $e->getMessage());
    }
    redirect('hasil.php');
}

// Get latest results
try {
    $stmt = $db->query("
        SELECT h.*, a.nama 
        FROM hasil h 
        JOIN alternatif a ON h.alternatif_id = a.id 
        ORDER BY h.ranking
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get calculation date
    $stmt = $db->query("SELECT MAX(tanggal) as last_calc FROM hasil");
    $lastCalculation = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    setFlash('danger', 'Gagal mengambil data hasil: ' . $e->getMessage());
    $results = [];
    $lastCalculation = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand"><?= SITE_NAME ?></a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">Home</a>
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
                    <a href="hasil.php" class="nav-link active">Hasil</a>
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

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    Hasil Perhitungan
                    <?php if ($lastCalculation): ?>
                    <small>(<?= date('d/m/Y H:i', strtotime($lastCalculation)) ?>)</small>
                    <?php endif; ?>
                </h2>
                <form action="hasil.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="recalculate" value="1">
                    <button type="submit" class="btn btn-primary">Hitung Ulang</button>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($results)): ?>
                <div class="alert alert-info">
                    Belum ada hasil perhitungan. Silakan lakukan perhitungan terlebih dahulu.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ranking</th>
                                <th>Alternatif</th>
                                <th>Nilai S</th>
                                <th>Nilai V</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?= $result['ranking'] ?></td>
                                <td><?= htmlspecialchars($result['nama']) ?></td>
                                <td><?= formatNumber($result['nilai_s']) ?></td>
                                <td><?= formatNumber($result['nilai_v']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Chart -->
                <div class="chart-container" style="position: relative; height: 400px; margin-top: 2rem;">
                    <canvas id="resultChart"></canvas>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>
    <?php if (!empty($results)): ?>
    <script>
        // Create chart
        const ctx = document.getElementById('resultChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($results, 'nama')) ?>,
                datasets: [{
                    label: 'Nilai V',
                    data: <?= json_encode(array_column($results, 'nilai_v')) ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.5)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
