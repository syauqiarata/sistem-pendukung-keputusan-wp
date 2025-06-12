<?php
// Error handling function
function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $errorType = match($errno) {
        E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE => 'Fatal Error',
        E_USER_ERROR => 'User Error',
        E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'Warning',
        E_NOTICE, E_USER_NOTICE => 'Notice',
        E_STRICT => 'Strict Standards',
        E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
        default => 'Unknown Error'
    };
    
    error_log("$errorType: $errstr in $errfile on line $errline");
    
    if ($errno === E_USER_ERROR) {
        exit(1);
    }
    
    return true;
}

// Security functions
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function check_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die('CSRF token validation failed');
        }
    }
}

// Navigation and session functions
function redirect($location) {
    if (!headers_sent()) {
        header("Location: $location");
        exit;
    }
    echo '<script>window.location.href="' . $location . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $location . '"></noscript>';
    exit;
}

function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        if (time() - $_SESSION['flash']['timestamp'] < 300) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        unset($_SESSION['flash']);
    }
    return null;
}

// Utility functions
function formatNumber($number, $decimals = 4) {
    return number_format((float)$number, $decimals, '.', '');
}

function validateDate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// WP Method specific functions
function normalizeWeights($weights) {
    $total = array_sum($weights);
    if ($total == 0) return $weights;
    
    return array_map(function($weight) use ($total) {
        return $weight / $total;
    }, $weights);
}

function calculateSValue($nilai, $bobot, $tipe) {
    $power = ($tipe === 'benefit') ? $bobot : -$bobot;
    return pow($nilai, $power);
}

function calculateVValue($sValue, $totalS) {
    return $sValue / $totalS;
}

// Database utility functions
function getLastInsertId($db) {
    return $db->lastInsertId();
}

function beginTransaction($db) {
    return $db->beginTransaction();
}

function commitTransaction($db) {
    return $db->commit();
}

function rollbackTransaction($db) {
    if ($db->inTransaction()) {
        return $db->rollBack();
    }
    return false;
}

// File handling functions
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isAllowedFileType($filename) {
    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    return in_array(getFileExtension($filename), $allowed);
}

function generateUniqueFilename($originalName) {
    $ext = getFileExtension($originalName);
    return uniqid() . '_' . time() . '.' . $ext;
}
