<?php
require_once 'includes/config.php';

// Function to calculate WP method
function calculateWP() {
    global $db;
    
    try {
        // Get all alternatifs
        $stmt = $db->query("SELECT * FROM alternatif");
        $alternatifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all kriterias
        $stmt = $db->query("SELECT * FROM kriteria");
        $kriterias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        $total_s = 0;
        
        // Calculate S values
        foreach ($alternatifs as $alternatif) {
            $s_value = 1;
            
            foreach ($kriterias as $kriteria) {
                // Get nilai for this alternatif and kriteria
                $stmt = $db->prepare("
                    SELECT nilai 
                    FROM penilaian 
                    WHERE alternatif_id = ? AND kriteria_id = ?
                ");
                $stmt->execute([$alternatif['id'], $kriteria['id']]);
                $nilai = $stmt->fetchColumn();
                
                if ($nilai === false) {
                    throw new Exception("Nilai tidak ditemukan untuk alternatif {$alternatif['nama']} pada kriteria {$kriteria['nama']}");
                }
                
                // Calculate power based on kriteria type
                $power = $kriteria['tipe'] === 'benefit' ? $kriteria['bobot'] : -$kriteria['bobot'];
                
                // Calculate partial S value
                $s_value *= pow($nilai, $power);
            }
            
            $results[$alternatif['id']] = [
                'alternatif' => $alternatif,
                'nilai_s' => $s_value
            ];
            
            $total_s += $s_value;
        }
        
        // Calculate V values and store results
        $db->beginTransaction();
        
        // Clear previous results
        $db->query("DELETE FROM hasil_perhitungan");
        
        // Insert new results
        $stmt = $db->prepare("
            INSERT INTO hasil_perhitungan (alternatif_id, nilai_s, nilai_v, ranking)
            VALUES (?, ?, ?, ?)
        ");
        
        // Sort by V value for ranking
        $rankings = [];
        foreach ($results as $alternatif_id => $result) {
            $v_value = $result['nilai_s'] / $total_s;
            $rankings[] = [
                'alternatif_id' => $alternatif_id,
                'nilai_s' => $result['nilai_s'],
                'nilai_v' => $v_value
            ];
        }
        
        // Sort by V value descending
        usort($rankings, function($a, $b) {
            return $b['nilai_v'] <=> $a['nilai_v'];
        });
        
        // Insert with rankings
        foreach ($rankings as $rank => $data) {
            $stmt->execute([
                $data['alternatif_id'],
                $data['nilai_s'],
                $data['nilai_v'],
                $rank + 1
            ]);
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

// Handle calculation request
if (isset($_POST['calculate'])) {
    try {
        calculateWP();
        setFlashMessage('success', 'Perhitungan berhasil dilakukan');
    } catch (Exception $e) {
        setFlashMessage('danger', 'Gagal melakukan perhitungan: ' . $e->getMessage());
    }
    redirect('hasil.php');
}

// Get calculation results
try {
    $stmt = $db->query("
        SELECT h.*, a.nama as alternatif_nama
        FROM hasil_perhitungan h
        JOIN alternatif a ON a.id = h.alternatif_id
        ORDER BY h.ranking
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get last calculation time
    $stmt = $db->query("
        SELECT MAX(tanggal_perhitungan) as last_calculation
        FROM hasil_perhitungan
    ");
    $lastCalculation = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    setFlashMessage('danger', 'Gagal mengambil hasil perhitungan: ' . $e->getMessage());
    $results = [];
    $lastCalculation = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Perhitungan - SPK Metode WP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <?php
        $flash = getFlashMessage();
        if ($flash) {
            echo "<div class='alert alert-{$flash['type']} alert-dismissible fade show' role='alert'>
                    {$flash['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                  </div>";
        }
        ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Hasil Perhitungan</h5>
                <div>
                    <?php if ($lastCalculation): ?>
                    <small class="text-muted me-3">
                        Perhitungan terakhir: <?= date('d/m/Y H:i', strtotime($lastCalculation)) ?>
                    </small>
                    <?php endif; ?>
                    <form action="hasil.php" method="POST" class="d-inline">
                        <button type="submit" name="calculate" class="btn btn-primary">
                            Hitung Ulang
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($results)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
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
                                <td><?= htmlspecialchars($result['alternatif_nama']) ?></td>
                                <td><?= number_format($result['nilai_s'], 4) ?></td>
                                <td><?= number_format($result['nilai_v'], 4) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Chart visualization -->
                <div class="mt-4">
                    <canvas id="rankingChart"></canvas>
                </div>
                <?php else: ?>
                <div class="text-center">
                    <p class="mb-0">Belum ada hasil perhitungan</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>
    
    <?php if (!empty($results)): ?>
    <script>
        // Create chart
        const ctx = document.getElementById('rankingChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($results, 'alternatif_nama')) ?>,
                datasets: [{
                    label: 'Nilai V',
                    data: <?= json_encode(array_column($results, 'nilai_v')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
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
