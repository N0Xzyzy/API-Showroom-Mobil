<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../middleware.php';

class CarController {
  public static function list() {
    $q     = trim($_GET['q'] ?? '');
    $brand = trim($_GET['brand'] ?? '');
    $min   = $_GET['min_price'] ?? null;
    $max   = $_GET['max_price'] ?? null;
    $page  = max(1, intval($_GET['page'] ?? 1));
    $limit = 12;
    $offset = ($page - 1) * $limit;

    $sql  = 'SELECT * FROM cars WHERE 1=1';
    $params = [];                       // array untuk bindValue

    if ($q) {
        $sql .= ' AND (title LIKE :q1 OR brand LIKE :q2 OR model LIKE :q3)';
        $params[':q1'] = "%$q%";
        $params[':q2'] = "%$q%";
        $params[':q3'] = "%$q%";
    }
    if ($brand) {
        $sql .= ' AND brand = :brand';
        $params[':brand'] = $brand;
    }
    if ($min !== null) {
        $sql .= ' AND price >= :min';
        $params[':min'] = $min;
    }
    if ($max !== null) {
        $sql .= ' AND price <= :max';
        $params[':max'] = $max;
    }

    $sql .= ' ORDER BY created_at DESC LIMIT :lim OFFSET :off';
    $params[':lim'] = $limit;
    $params[':off'] = $offset;

    $stmt = db()->prepare($sql);

    /* bind semua parameter dengan tipe yang tepat */
    foreach ($params as $key => $value) {
        if ($key === ':lim' || $key === ':off') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }

    $stmt->execute();
    json(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'page' => $page]);
}

  public static function detail($id) {
    $stmt = db()->prepare('SELECT * FROM cars WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) json(['error' => 'Not found'], 404);
    json($row);
  }

  public static function create() {
    $user = requireAuth(); requireAdmin($user);

    $title = sanitize($_POST['title'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $year = intval($_POST['year'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (!$title || !$brand || !$model || !$year || !$price) json(['error' => 'Invalid input'], 422);

    $imagePath = moveUploadedImage('image'); // optional
    $stmt = db()->prepare('INSERT INTO cars (title, brand, model, year, price, stock, image_path, description) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$title, $brand, $model, $year, $price, $stock, $imagePath, $description]);
    json(['message' => 'Created', 'id' => db()->lastInsertId()], 201);
  }

  public static function update($id) {
    $user = requireAuth(); requireAdmin($user);

    $stmt = db()->prepare('SELECT * FROM cars WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) json(['error' => 'Not found'], 404);

    $fields = ['title','brand','model','year','price','stock','description'];
    $updates = []; $params = [];
    foreach ($fields as $f) {
      if (isset($_POST[$f])) { $updates[] = "$f = ?"; $params[] = $_POST[$f]; }
    }
    $img = moveUploadedImage('image');
    if ($img) { $updates[] = "image_path = ?"; $params[] = $img; }
    if (empty($updates)) json(['message' => 'No changes'], 200);
    $params[] = $id;
    $sql = 'UPDATE cars SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';
    db()->prepare($sql)->execute($params);
    json(['message' => 'Updated']);
  }

  public static function delete($id) {
    $user = requireAuth(); requireAdmin($user);
    db()->prepare('DELETE FROM cars WHERE id = ?')->execute([$id]);
    json(['message' => 'Deleted']);
  }
}
