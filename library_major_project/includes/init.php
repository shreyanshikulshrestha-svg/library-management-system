<?php
/**
 * init.php - loaded first by every page.
 * Starts the session, connects to MySQL and defines helper functions.
 */
date_default_timezone_set('Asia/Kolkata');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Throw exceptions on MySQL errors so they can be handled with try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ---- Settings (XAMPP defaults) ----
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'library_system';

// ---- Library rules ----
const FINE_PER_DAY      = 5;    // Rs. charged per day after the due date
const MAX_ISSUE_LIMIT   = 3;    // books a student may hold at once
const DEFAULT_LOAN_DAYS = 14;

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('<h2>Database connection failed</h2>'
      . '<p>Start MySQL in XAMPP and import <code>database/library_system.sql</code> '
      . 'using phpMyAdmin, then reload this page.</p>');
}

/* ------------------------------------------------------------------
   Database helper: prepare + bind + execute in one call.
   Returns the mysqli_stmt (use ->get_result(), ->affected_rows, ->insert_id)
------------------------------------------------------------------- */
function run(mysqli $db, string $sql, string $types = '', array $params = []) {
    $st = $db->prepare($sql);
    if ($params) {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    return $st;
}

/* ---------------------------- Output --------------------------- */
function h($value): string {              // escape output (prevents XSS)
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function fdate(?string $d): string {      // 2026-11-13 -> 13 Nov 2026
    return $d ? date('d M Y', strtotime($d)) : '-';
}
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/* ------------------------- Flash messages ---------------------- */
function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function take_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* --------------------------- CSRF guard ------------------------ */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}
function csrf_ok(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
function is_post(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* ------------------ Server-side validation rules --------------- */
/* These mirror assets/js/validate.js so both sides agree.          */
function ok_name(string $v): bool   { return (bool)preg_match("/^[A-Za-z][A-Za-z .'-]{1,99}$/", $v); }
function ok_email(string $v): bool  { return filter_var($v, FILTER_VALIDATE_EMAIL) !== false && strlen($v) <= 120; }
function ok_phone(string $v): bool  { return (bool)preg_match('/^[6-9][0-9]{9}$/', $v); }
function ok_isbn(string $v): bool   { return (bool)preg_match('/^([0-9]{10}|[0-9]{13})$/', $v); }
function ok_enroll(string $v): bool { return (bool)preg_match('/^[A-Za-z0-9\/-]{5,20}$/', $v); }
function ok_text(string $v, int $min = 2, int $max = 150): bool {
    $len = mb_strlen($v);
    return $len >= $min && $len <= $max;
}

/* ------------------------------ Fines -------------------------- */
function days_late(string $due, ?string $until = null): int {
    $d1 = new DateTime($due);
    $d2 = new DateTime($until ?? date('Y-m-d'));
    return $d2 > $d1 ? (int)$d1->diff($d2)->days : 0;
}
function fine_for(string $due, ?string $until = null): int {
    return days_late($due, $until) * FINE_PER_DAY;
}
