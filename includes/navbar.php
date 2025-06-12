<?php
$current_page = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-calculator-fill me-2"></i>
            <?= APP_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?= isActive('index.php') ?>" href="index.php">
                        <i class="bi bi-house-door"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('alternatif.php') ?>" href="alternatif.php">
                        <i class="bi bi-people"></i> Alternatif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('kriteria.php') ?>" href="kriteria.php">
                        <i class="bi bi-list-check"></i> Kriteria
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('penilaian.php') ?>" href="penilaian.php">
                        <i class="bi bi-clipboard-data"></i> Penilaian
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= isActive('hasil.php') ?>" href="hasil.php">
                        <i class="bi bi-graph-up"></i> Hasil
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php
$flash = getFlashMessage();
if ($flash): 
?>
<div class="container mt-3">
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>
