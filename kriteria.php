<?php
require_once 'config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $nama = sanitize($_POST['nama']);
        $bobot = (float)$_POST['bobot'];
        $tipe = sanitize($_POST['tipe']);
        
        if ($_POST['action'] === 'add') {
            try {
                // Check if total weight with new addition would exceed 1
                $stmt = $db->query("SELECT SUM(bobot) as total FROM kriteria");
                $currentTotal = (float)$stmt->fetchColumn();
                if (($currentTotal + $bobot) > 1) {
                    throw new Exception("Total bobot tidak boleh melebihi 1");
                }

                $stmt = $db->prepare("INSERT INTO kriteria (nama, bobot, tipe) VALUES (?, ?, ?)");
                $stmt->execute([$nama, $bobot, $tipe]);
                setFlash('success', 'Kriteria berhasil ditambahkan');
            } catch(Exception $e) {
                setFlash('danger', 'Gagal menambahkan kriteria: ' . $e->getMessage());
            }
        } 
        elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            try {
                // Get current weight of the criteria being edited
                $stmt = $db->prepare("SELECT bobot FROM kriteria WHERE id = ?");
                $stmt->execute([$id]);
                $currentBobot = (float)$stmt->fetchColumn();

                // Check if total weight would exceed 1
                $stmt = $db->query("SELECT SUM(bobot) as total FROM kriteria WHERE id != $id");
                $otherTotal = (float)$stmt->fetchColumn();
                if (($otherTotal + $bobot) > 1) {
                    throw new Exception("Total bobot tidak boleh melebihi 1");
                }

                $stmt = $db->prepare("UPDATE kriteria SET nama = ?, bobot = ?, tipe = ? WHERE id = ?");
                $stmt->execute([$nama, $bobot, $tipe, $id]);
                setFlash('success', 'Kriteria berhasil diperbarui');
            } catch(Exception $e) {
                setFlash('danger', 'Gagal memperbarui kriteria: ' . $e->getMessage());
            }
        }
        elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM kriteria WHERE id = ?");
                $stmt->execute([$id]);
                setFlash('success', 'Kriteria berhasil dihapus');
            } catch(PDOException $e) {
                setFlash('danger', 'Gagal menghapus kriteria: ' . $e->getMessage());
            }
        }
    }
    redirect('kriteria.php');
}

// Get all kriteria
try {
    $stmt = $db->query("SELECT * FROM kriteria ORDER BY nama");
    $kriterias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total bobot
    $totalBobot = array_sum(array_column($kriterias, 'bobot'));
} catch(PDOException $e) {
    setFlash('danger', 'Gagal mengambil data kriteria: ' . $e->getMessage());
    $kriterias = [];
    $totalBobot = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kriteria - <?= SITE_NAME ?></title>
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
                    <a href="kriteria.php" class="nav-link active">Kriteria</a>
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

        <?php if (abs($totalBobot - 1) > 0.0001): ?>
        <div class="alert alert-warning">
            Total bobot saat ini adalah <?= formatNumber($totalBobot) ?>. Total bobot kriteria harus berjumlah 1.
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Data Kriteria</h2>
                <button class="btn btn-primary" onclick="showModal('addModal')">
                    Tambah Kriteria
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kriteria</th>
                                <th>Bobot</th>
                                <th>Tipe</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kriterias as $index => $kriteria): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($kriteria['nama']) ?></td>
                                <td><?= formatNumber($kriteria['bobot']) ?></td>
                                <td><?= ucfirst($kriteria['tipe']) ?></td>
                                <td>
                                    <button class="btn btn-primary" onclick="editKriteria(<?= htmlspecialchars(json_encode($kriteria)) ?>)">
                                        Edit
                                    </button>
                                    <form action="kriteria.php" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $kriteria['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($kriterias)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data kriteria</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Tambah Kriteria</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form action="kriteria.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label class="form-label">Nama Kriteria</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bobot</label>
                        <input type="number" name="bobot" class="form-control" step="0.01" min="0" max="1" required>
                        <small>Bobot harus bernilai antara 0 dan 1. Total bobot semua kriteria harus berjumlah 1.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe</label>
                        <select name="tipe" class="form-control" required>
                            <option value="benefit">Benefit</option>
                            <option value="cost">Cost</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Kriteria</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form action="kriteria.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label class="form-label">Nama Kriteria</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bobot</label>
                        <input type="number" name="bobot" id="edit_bobot" class="form-control" step="0.01" min="0" max="1" required>
                        <small>Bobot harus bernilai antara 0 dan 1. Total bobot semua kriteria harus berjumlah 1.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe</label>
                        <select name="tipe" id="edit_tipe" class="form-control" required>
                            <option value="benefit">Benefit</option>
                            <option value="cost">Cost</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function editKriteria(kriteria) {
            document.getElementById('edit_id').value = kriteria.id;
            document.getElementById('edit_nama').value = kriteria.nama;
            document.getElementById('edit_bobot').value = kriteria.bobot;
            document.getElementById('edit_tipe').value = kriteria.tipe;
            showModal('editModal');
        }
    </script>
</body>
</html>
