<?php
require_once 'includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $nama = sanitize($_POST['nama']);
            $bobot = (float)$_POST['bobot'];
            $tipe = sanitize($_POST['tipe']);
            
            try {
                $stmt = $db->prepare("INSERT INTO kriteria (nama, bobot, tipe) VALUES (?, ?, ?)");
                $stmt->execute([$nama, $bobot, $tipe]);
                setFlashMessage('success', 'Kriteria berhasil ditambahkan');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal menambahkan kriteria: ' . $e->getMessage());
            }
        } 
        elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $nama = sanitize($_POST['nama']);
            $bobot = (float)$_POST['bobot'];
            $tipe = sanitize($_POST['tipe']);
            
            try {
                $stmt = $db->prepare("UPDATE kriteria SET nama = ?, bobot = ?, tipe = ? WHERE id = ?");
                $stmt->execute([$nama, $bobot, $tipe, $id]);
                setFlashMessage('success', 'Kriteria berhasil diperbarui');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal memperbarui kriteria: ' . $e->getMessage());
            }
        }
        elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            
            try {
                $stmt = $db->prepare("DELETE FROM kriteria WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', 'Kriteria berhasil dihapus');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal menghapus kriteria: ' . $e->getMessage());
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
} catch (PDOException $e) {
    setFlashMessage('danger', 'Gagal mengambil data kriteria: ' . $e->getMessage());
    $kriterias = [];
    $totalBobot = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kriteria - SPK Metode WP</title>
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
        
        // Show warning if total bobot is not 1
        if (abs($totalBobot - 1) > 0.0001) {
            echo "<div class='alert alert-warning' role='alert'>
                    Total bobot saat ini adalah " . number_format($totalBobot, 4) . ". 
                    Total bobot kriteria harus berjumlah 1.
                  </div>";
        }
        ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Data Kriteria</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    Tambah Kriteria
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
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
                                <td><?= number_format($kriteria['bobot'], 4) ?></td>
                                <td><?= ucfirst($kriteria['tipe']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning btn-action"
                                            data-bs-toggle="modal" data-bs-target="#editModal"
                                            data-id="<?= $kriteria['id'] ?>"
                                            data-nama="<?= htmlspecialchars($kriteria['nama']) ?>"
                                            data-bobot="<?= $kriteria['bobot'] ?>"
                                            data-tipe="<?= $kriteria['tipe'] ?>">
                                        Edit
                                    </button>
                                    <form action="kriteria.php" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus kriteria ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $kriteria['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-action">Hapus</button>
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
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kriteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="kriteria.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Kriteria</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                            <div class="invalid-feedback">
                                Nama kriteria harus diisi
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="bobot" class="form-label">Bobot</label>
                            <input type="number" class="form-control" id="bobot" name="bobot" 
                                   step="0.0001" min="0" max="1" required>
                            <div class="invalid-feedback">
                                Bobot harus diisi dengan nilai antara 0 dan 1
                            </div>
                            <small class="text-muted">
                                Total bobot semua kriteria harus berjumlah 1
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="tipe" class="form-label">Tipe</label>
                            <select class="form-select" id="tipe" name="tipe" required>
                                <option value="benefit">Benefit</option>
                                <option value="cost">Cost</option>
                            </select>
                            <div class="invalid-feedback">
                                Tipe kriteria harus dipilih
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Kriteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="kriteria.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_nama" class="form-label">Nama Kriteria</label>
                            <input type="text" class="form-control" id="edit_nama" name="nama" required>
                            <div class="invalid-feedback">
                                Nama kriteria harus diisi
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_bobot" class="form-label">Bobot</label>
                            <input type="number" class="form-control" id="edit_bobot" name="bobot" 
                                   step="0.0001" min="0" max="1" required>
                            <div class="invalid-feedback">
                                Bobot harus diisi dengan nilai antara 0 dan 1
                            </div>
                            <small class="text-muted">
                                Total bobot semua kriteria harus berjumlah 1
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tipe" class="form-label">Tipe</label>
                            <select class="form-select" id="edit_tipe" name="tipe" required>
                                <option value="benefit">Benefit</option>
                                <option value="cost">Cost</option>
                            </select>
                            <div class="invalid-feedback">
                                Tipe kriteria harus dipilih
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Handle edit modal data
        const editModal = document.getElementById('editModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const nama = button.getAttribute('data-nama');
                const bobot = button.getAttribute('data-bobot');
                const tipe = button.getAttribute('data-tipe');
                
                editModal.querySelector('#edit_id').value = id;
                editModal.querySelector('#edit_nama').value = nama;
                editModal.querySelector('#edit_bobot').value = bobot;
                editModal.querySelector('#edit_tipe').value = tipe;
            });
        }
    </script>
</body>
</html>
