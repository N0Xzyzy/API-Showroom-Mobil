<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../middleware.php';

class UserController {
  public static function list() {
    $user = requireAuth(); requireAdmin($user);
    $stmt = db()->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
    json(['data' => $stmt->fetchAll()]);
  }
  public static function detail($id) {
    $user = requireAuth();
    if ($user['role'] !== 'admin' && $user['id'] != $id) json(['error' => 'Forbidden'], 403);
    $stmt = db()->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if (!$u) json(['error' => 'Not found'], 404);
    json($u);
  }
  public static function create() {
    $user = requireAuth(); requireAdmin($user);
    $name = sanitize($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    if (!$name || !$email || !$password) json(['error' => 'Invalid input'], 422);
    db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,?)')
      ->execute([$name, $email, hashPassword($password), $role]);
    json(['message' => 'Created'], 201);
  }
  public static function update($id) {
    $user = requireAuth(); requireAdmin($user);
    $fields = ['name','email','role'];
    $updates = []; $params = [];
    foreach ($fields as $f) if (isset($_POST[$f])) { $updates[] = "$f = ?"; $params[] = $_POST[$f]; }
    if (isset($_POST['password'])) { $updates[] = "password_hash = ?"; $params[] = hashPassword($_POST['password']); }
    if (empty($updates)) json(['message' => 'No changes'], 200);
    $params[] = $id;
    db()->prepare('UPDATE users SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?')->execute($params);
    json(['message' => 'Updated']);
  }
  public static function delete($id) {
    $user = requireAuth(); requireAdmin($user);
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    json(['message' => 'Deleted']);
  }
}
