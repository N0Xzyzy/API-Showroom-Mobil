<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

class AuthController {
  public static function register() {
    $name = sanitize($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$password) json(['error' => 'Invalid input'], 422);

    $exists = db()->prepare('SELECT id FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ($exists->fetch()) json(['error' => 'Email already used'], 409);

    $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)');
    $stmt->execute([$name, $email, hashPassword($password), 'customer']);
    json(['message' => 'Registered'], 201);
  }

  public static function login() {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !verifyPassword($password, $user['password_hash'])) {
      json(['error' => 'Invalid credentials'], 401);
    }
    $token = generateToken();
    db()->prepare('UPDATE users SET api_token = ?, updated_at = NOW() WHERE id = ?')->execute([$token, $user['id']]);
    json([
      'api_token' => $token,
      'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
      ]
    ]);
  }

  public static function logout() {
    $user = requireAuth();
    db()->prepare('UPDATE users SET api_token = NULL WHERE id = ?')->execute([$user['id']]);
    json(['message' => 'Logged out']);
  }
}
