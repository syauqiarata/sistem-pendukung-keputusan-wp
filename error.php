<?php
require_once 'includes/config.php';

$error_codes = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Page Not Found',
    500 => 'Internal Server Error'
];

$code = isset($_GET['code']) && isset($error_codes[$_GET['code']]) 
    ? (int)$_GET['code'] 
    : 404;

$title = $error_codes[$code];
$message = '';

switch ($code) {
    case 400:
        $message = 'The request could not be understood by the server due to malformed syntax.';
        break;
    case 401:
        $message = 'Authentication is required and has failed or has not yet been provided.';
        break;
    case 403:
        $message = 'You don\'t have permission to access this resource.';
        break;
    case 404:
        $message = 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.';
        break;
    case 500:
        $message = 'The server encountered an internal error and was unable to complete your request.';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= $code ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #dc3545;
            line-height: 1;
        }
        .error-title {
            font-size: 24px;
            margin-bottom: 1rem;
            color: #343a40;
        }
        .error-message {
            color: #6c757d;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="error-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <div class="error-code mb-3">
                        <?= $code ?>
                    </div>
                    <h1 class="error-title">
                        <?= htmlspecialchars($title) ?>
                    </h1>
                    <p class="error-message">
                        <?= htmlspecialchars($message) ?>
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Go Back
                        </a>
                        <a href="index.php" class="btn btn-primary">
                            <i class="bi bi-house"></i> Go Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
