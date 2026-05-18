<?php
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirect_to(string $url): never { header('Location: ' . $url); exit; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_logged_in(): bool { return isset($_SESSION['user']); }
function full_name(?array $user): string { return $user ? trim($user['csaladi_nev'].' '.$user['utonev']) : 'Vendég'; }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.h(csrf_token()).'">'; }
function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
            http_response_code(419);
            echo 'Érvénytelen űrlap token. Töltse újra az oldalt.';
            exit;
        }
    }
}
function flash(?string $message = null, string $type = 'success'): ?array {
    if ($message !== null) { $_SESSION['flash'] = ['message'=>$message, 'type'=>$type]; return null; }
    if (!empty($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}
function num($value): string { return rtrim(rtrim(number_format((float)$value, 2, ',', ' '), '0'), ','); }
function s_len(string $s): int { return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s); }
function s_lower(string $s): string { return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s); }
function s_trimwidth(string $s, int $limit): string { if (function_exists('mb_strimwidth')) return mb_strimwidth($s, 0, $limit, '...', 'UTF-8'); return strlen($s) > $limit ? substr($s, 0, max(0, $limit-3)).'...' : $s; }
function selected($a, $b): string { return (string)$a === (string)$b ? 'selected' : ''; }
function checked($v): string { return $v ? 'checked' : ''; }
