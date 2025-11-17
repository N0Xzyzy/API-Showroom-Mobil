<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function requireAuth() {
  $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
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
