<?php
require_once 'config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $nama = sanitize($_POST['nama']);
        
        if ($_POST['action'] === 'add') {
            try {
                $stmt = $db->prepare("INSERT INTO alternatif (nama) VALUES (?)");
                $stmt->execute([$nama]);
                setFlash('success', 'Alternatif berhasil ditambahkan');
            } catch(PDOException $e) {
                setFlash('danger', 'Gagal menambahkan alternatif: ' . $e->getMessage());
            }
        } 
        elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $db->prepare("UPDATE alternatif SET nama = ? WHERE id = ?");
                $stmt->execute([$nama, $id]);
                setFlash('success', 'Alternatif berhasil diperbarui');
            } catch(PDOException $e) {
                setFlash('danger', 'Gagal memperbarui alternatif: ' . $e->getMessage());
            }
        }
        elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM alternatif WHERE id = ?");
                $stmt->execute([$id]);
                setFlash('success', 'Alternatif berhasil dihapus');
            } catch(PDOException $e) {
                setFlash('danger', 'Gagal menghapus alternatif: ' . $e->getMessage());
            }
        }
    }
    redirect('alternatif.php');
}

// Get all alternatif
try {
    $stmt = $db->query("SELECT * FROM alternatif ORDER BY nama");
    $alternatifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    setFlash('danger', 'Gagal mengambil data alternatif: ' . $e->getMessage());
    $alternatifs = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alternatif - <?= SITE_NAME ?></title>
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
                    <a href="alternatif.php" class="nav-link active">Alternatif</a>
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

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Data Alternatif</h2>
                <button class="btn btn-primary" onclick="showModal('addModal')">
                    Tambah Alternatif
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Alternatif</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alternatifs as $index => $alternatif): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($alternatif['nama']) ?></td>
                                <td>
                                    <button class="btn btn-primary" onclick="editAlternatif(<?= $alternatif['id'] ?>, '<?= htmlspecialchars($alternatif['nama']) ?>')">
                                        Edit
                                    </button>
                                    <form action="alternatif.php" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $alternatif['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($alternatifs)): ?>
                            <tr>
                                <td colspan="3" class="text-center">Tidak ada data alternatif</td>
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
                <h3 class="modal-title">Tambah Alternatif</h3>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form action="alternatif.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label class="form-label">Nama Alternatif</label>
                        <input type="text" name="nama" class="form-control" required>
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
                <h3 class="modal-title">Edit Alternatif</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form action="alternatif.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label class="form-label">Nama Alternatif</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
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
        function editAlternatif(id, nama) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            showModal('editModal');
        }
    </script>
</body>
</html>
