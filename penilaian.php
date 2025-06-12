<?php
require_once 'includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $alternatif_id = (int)$_POST['alternatif_id'];
            $nilai = $_POST['nilai'];
            $kriteria_ids = $_POST['kriteria_id'];
            
            try {
                $db->beginTransaction();
                
                // Delete existing scores for this alternatif
                $stmt = $db->prepare("DELETE FROM penilaian WHERE alternatif_id = ?");
                $stmt->execute([$alternatif_id]);
                
                // Insert new scores
                $stmt = $db->prepare("INSERT INTO penilaian (alternatif_id, kriteria_id, nilai) VALUES (?, ?, ?)");
                foreach ($kriteria_ids as $index => $kriteria_id) {
                    $stmt->execute([$alternatif_id, $kriteria_id, $nilai[$index]]);
                }
                
                $db->commit();
                setFlashMessage('success', 'Penilaian berhasil disimpan');
            } catch (PDOException $e) {
                $db->rollBack();
                setFlashMessage('danger', 'Gagal menyimpan penilaian: ' . $e->getMessage());
            }
        }
    }
    redirect('penilaian.php');
}

// Get all alternatif with their scores
try {
    $stmt = $db->query("
        SELECT 
            a.id,
            a.nama,
            (SELECT COUNT(*) FROM penilaian p WHERE p.alternatif_id = a.id) as total_nilai
        FROM alternatif a
        ORDER BY a.nama
    ");
    $alternatifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setFlashMessage('danger', 'Gagal mengambil data alternatif: ' . $e->getMessage());
    $alternatifs = [];
}

// Get all kriteria
try {
    $stmt = $db->query("SELECT * FROM kriteria ORDER BY nama");
    $kriterias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setFlashMessage('danger', 'Gagal mengambil data kriteria: ' . $e->getMessage());
    $kriterias = [];
}

// Get scores for specific alternatif if requested
$selectedAlternatif = null;
$scores = [];
if (isset($_GET['alternatif_id'])) {
    $alternatif_id = (int)$_GET['alternatif_id'];
    try {
        $stmt = $db->prepare("
            SELECT a.*, 
                   (SELECT GROUP_CONCAT(p.nilai) 
                    FROM penilaian p 
                    WHERE p.alternatif_id = a.id 
                    ORDER BY p.kriteria_id) as nilai_string
            FROM alternatif a
            WHERE a.id = ?
        ");
        $stmt->execute([$alternatif_id]);
        $selectedAlternatif = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selectedAlternatif) {
            $stmt = $db->prepare("
                SELECT p.*, k.nama as kriteria_nama, k.bobot, k.tipe
                FROM penilaian p
                JOIN kriteria k ON k.id = p.kriteria_id
                WHERE p.alternatif_id = ?
                ORDER BY k.nama
            ");
            $stmt->execute([$alternatif_id]);
            $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Gagal mengambil data penilaian: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian - SPK Metode WP</title>
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

        <div class="row">
            <!-- Alternatif List -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Daftar Alternatif</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($alternatifs as $alternatif): ?>
                            <a href="?alternatif_id=<?= $alternatif['id'] ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                                      <?= isset($_GET['alternatif_id']) && $_GET['alternatif_id'] == $alternatif['id'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($alternatif['nama']) ?>
                                <?php if ($alternatif['total_nilai'] > 0): ?>
                                    <span class="badge bg-primary rounded-pill">
                                        <?= $alternatif['total_nilai'] ?>/<?= count($kriterias) ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                            <?php if (empty($alternatifs)): ?>
                            <div class="list-group-item text-center text-muted">
                                Tidak ada data alternatif
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Penilaian Form -->
            <div class="col-md-8">
                <?php if ($selectedAlternatif): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Penilaian: <?= htmlspecialchars($selectedAlternatif['nama']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="penilaian.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="<?= empty($scores) ? 'add' : 'edit' ?>">
                            <input type="hidden" name="alternatif_id" value="<?= $selectedAlternatif['id'] ?>">
                            
                            <?php foreach ($kriterias as $kriteria): ?>
                            <?php
                                $nilai = '';
                                foreach ($scores as $score) {
                                    if ($score['kriteria_id'] == $kriteria['id']) {
                                        $nilai = $score['nilai'];
                                        break;
                                    }
                                }
                            ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <?= htmlspecialchars($kriteria['nama']) ?>
                                    <small class="text-muted">
                                        (Bobot: <?= $kriteria['bobot'] ?>, 
                                        Tipe: <?= ucfirst($kriteria['tipe']) ?>)
                                    </small>
                                </label>
                                <input type="hidden" name="kriteria_id[]" value="<?= $kriteria['id'] ?>">
                                <input type="number" class="form-control" name="nilai[]" 
                                       value="<?= $nilai ?>" step="0.01" min="0" required>
                                <div class="invalid-feedback">
                                    Nilai kriteria harus diisi
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <button type="submit" class="btn btn-primary">
                                <?= empty($scores) ? 'Simpan' : 'Update' ?> Penilaian
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <p class="mb-0">Pilih alternatif untuk melakukan penilaian</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
