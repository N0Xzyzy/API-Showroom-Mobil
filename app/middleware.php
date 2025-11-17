<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function getAuthHeader(): ?string
{
    // 1. Versi standar
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }
    // 2. Apache CGI/FastCGI
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    // 3. FPM / nginx
    if (function_exists('apache_request_headers')) {
        $hdrs = apache_request_headers();
        if (!empty($hdrs['Authorization'])) {
            return $hdrs['Authorization'];
        }
        if (!empty($hdrs['authorization'])) { // case-insensitive
            return $hdrs['authorization'];
        }
    }
    return null;
}

function requireAuth() {
  $hdr = getAuthHeader();
  if (!preg_match('/Bearer\s+(\S+)/', $hdr, $m)) json(['error' => 'Unauthorized'], 401);
  $token = $m[1];
  $stmt = db()->prepare('SELECT * FROM users WHERE api_token = ?');
  $stmt->execute([$token]);
  $user = $stmt->fetch();
  if (!$user) json(['error' => 'Unauthorized'], 401);
  return $user;
}

function requireAdmin($user) {
  if ($user['role'] !== 'admin') json(['error' => 'Forbidden'], 403);
}
