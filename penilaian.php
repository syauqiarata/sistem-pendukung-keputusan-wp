<?php
require_once 'config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $alternatif_id = (int)$_POST['alternatif_id'];
            $nilai = $_POST['nilai'];
            $kriteria_ids = $_POST['kriteria_id'];
            
            try {
                $db->beginTransaction();
                
                // Delete existing scores for this alternative
                $stmt = $db->prepare("DELETE FROM penilaian WHERE alternatif_id = ?");
                $stmt->execute([$alternatif_id]);
                
                // Insert new scores
                $stmt = $db->prepare("INSERT INTO penilaian (alternatif_id, kriteria_id, nilai) VALUES (?, ?, ?)");
                foreach ($kriteria_ids as $index => $kriteria_id) {
                    $stmt->execute([$alternatif_id, $kriteria_id, $nilai[$index]]);
                }
                
                $db->commit();
                setFlash('success', 'Penilaian berhasil disimpan');
            } catch(Exception $e) {
                $db->rollBack();
                setFlash('danger', 'Gagal menyimpan penilaian: ' . $e->getMessage());
            }
        }
    }
    redirect('penilaian.php');
}

// Get all alternatif
try {
    $stmt = $db->query("SELECT * FROM alternatif ORDER BY nama");
    $alternatifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    setFlash('danger', 'Gagal mengambil data alternatif');
    $alternatifs = [];
}

// Get all kriteria
try {
    $stmt = $db->query("SELECT * FROM kriteria ORDER BY nama");
    $kriterias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    setFlash('danger', 'Gagal mengambil data kriteria');
    $kriterias = [];
}

// Get existing penilaian
$penilaians = [];
try {
    $stmt = $db->query("SELECT * FROM penilaian");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $penilaians[$row['alternatif_id']][$row['kriteria_id']] = $row['nilai'];
    }
} catch(PDOException $e) {
    setFlash('danger', 'Gagal mengambil data penilaian');
}

// Selected alternatif for form
$selected_alternatif = null;
if (isset($_GET['alternatif_id'])) {
    $selected_id = (int)$_GET['alternatif_id'];
    foreach ($alternatifs as $alt) {
        if ($alt['id'] === $selected_id) {
            $selected_alternatif = $alt;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian - <?= SITE_NAME ?></title>
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
                    <a href="penilaian.php" class="nav-link active">Penilaian</a>
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

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Data Penilaian</h2>
            </div>
            <div class="card-body">
                <?php if (empty($kriterias)): ?>
                <div class="alert alert-warning">
                    Belum ada kriteria yang ditambahkan. Silakan tambah kriteria terlebih dahulu.
                </div>
                <?php elseif (empty($alternatifs)): ?>
                <div class="alert alert-warning">
                    Belum ada alternatif yang ditambahkan. Silakan tambah alternatif terlebih dahulu.
                </div>
                <?php else: ?>

                <!-- Alternatif Selection -->
                <div class="form-group">
                    <label class="form-label">Pilih Alternatif</label>
                    <select class="form-control" onchange="window.location.href='penilaian.php?alternatif_id=' + this.value">
                        <option value="">-- Pilih Alternatif --</option>
                        <?php foreach ($alternatifs as $alternatif): ?>
                        <option value="<?= $alternatif['id'] ?>" <?= ($selected_alternatif && $selected_alternatif['id'] === $alternatif['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($alternatif['nama']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($selected_alternatif): ?>
                <!-- Penilaian Form -->
                <form action="penilaian.php" method="POST" class="mt-4">
                    <input type="hidden" name="action" value="<?= isset($penilaians[$selected_alternatif['id']]) ? 'edit' : 'add' ?>">
                    <input type="hidden" name="alternatif_id" value="<?= $selected_alternatif['id'] ?>">
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kriteria</th>
                                <th>Bobot</th>
                                <th>Tipe</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kriterias as $kriteria): ?>
                            <tr>
                                <td><?= htmlspecialchars($kriteria['nama']) ?></td>
                                <td><?= formatNumber($kriteria['bobot']) ?></td>
                                <td><?= ucfirst($kriteria['tipe']) ?></td>
                                <td>
                                    <input type="hidden" name="kriteria_id[]" value="<?= $kriteria['id'] ?>">
                                    <input type="number" name="nilai[]" class="form-control" 
                                           value="<?= isset($penilaians[$selected_alternatif['id']][$kriteria['id']]) ? 
                                                     $penilaians[$selected_alternatif['id']][$kriteria['id']] : '' ?>"
                                           step="0.01" min="0" required>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="form-group text-end">
                        <button type="submit" class="btn btn-primary">Simpan Penilaian</button>
                    </div>
                </form>
                <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>

        <!-- Overview Table -->
        <?php if (!empty($alternatifs) && !empty($kriterias)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h2 class="card-title">Ringkasan Penilaian</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Alternatif</th>
                                <?php foreach ($kriterias as $kriteria): ?>
                                <th><?= htmlspecialchars($kriteria['nama']) ?></th>
                                <?php endforeach; ?>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alternatifs as $alternatif): ?>
                            <tr>
                                <td><?= htmlspecialchars($alternatif['nama']) ?></td>
                                <?php 
                                $complete = true;
                                foreach ($kriterias as $kriteria): 
                                    $nilai = isset($penilaians[$alternatif['id']][$kriteria['id']]) ? 
                                            $penilaians[$alternatif['id']][$kriteria['id']] : null;
                                    if ($nilai === null) $complete = false;
                                ?>
                                <td><?= $nilai !== null ? formatNumber($nilai) : '-' ?></td>
                                <?php endforeach; ?>
                                <td>
                                    <?php if ($complete): ?>
                                    <span class="badge success">Lengkap</span>
                                    <?php else: ?>
                                    <span class="badge warning">Belum Lengkap</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
