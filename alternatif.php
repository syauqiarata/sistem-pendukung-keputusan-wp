<?php
require_once 'includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $nama = sanitize($_POST['nama']);
            
            try {
                $stmt = $db->prepare("INSERT INTO alternatif (nama) VALUES (?)");
                $stmt->execute([$nama]);
                setFlashMessage('success', 'Alternatif berhasil ditambahkan');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal menambahkan alternatif: ' . $e->getMessage());
            }
        } 
        elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $nama = sanitize($_POST['nama']);
            
            try {
                $stmt = $db->prepare("UPDATE alternatif SET nama = ? WHERE id = ?");
                $stmt->execute([$nama, $id]);
                setFlashMessage('success', 'Alternatif berhasil diperbarui');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal memperbarui alternatif: ' . $e->getMessage());
            }
        }
        elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            
            try {
                $stmt = $db->prepare("DELETE FROM alternatif WHERE id = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', 'Alternatif berhasil dihapus');
            } catch (PDOException $e) {
                setFlashMessage('danger', 'Gagal menghapus alternatif: ' . $e->getMessage());
            }
        }
    }
    redirect('alternatif.php');
}

// Get all alternatif
try {
    $stmt = $db->query("SELECT * FROM alternatif ORDER BY nama");
    $alternatifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setFlashMessage('danger', 'Gagal mengambil data alternatif: ' . $e->getMessage());
    $alternatifs = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alternatif - SPK Metode WP</title>
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
                <h5 class="mb-0">Data Alternatif</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    Tambah Alternatif
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
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
                                    <button type="button" class="btn btn-sm btn-warning btn-action"
                                            data-bs-toggle="modal" data-bs-target="#editModal"
                                            data-id="<?= $alternatif['id'] ?>"
                                            data-nama="<?= htmlspecialchars($alternatif['nama']) ?>">
                                        Edit
                                    </button>
                                    <form action="alternatif.php" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus alternatif ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $alternatif['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-action">Hapus</button>
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
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Alternatif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="alternatif.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Alternatif</label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                            <div class="invalid-feedback">
                                Nama alternatif harus diisi
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
                    <h5 class="modal-title">Edit Alternatif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="alternatif.php" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_nama" class="form-label">Nama Alternatif</label>
                            <input type="text" class="form-control" id="edit_nama" name="nama" required>
                            <div class="invalid-feedback">
                                Nama alternatif harus diisi
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
                
                editModal.querySelector('#edit_id').value = id;
                editModal.querySelector('#edit_nama').value = nama;
            });
        }
    </script>
</body>
</html>
