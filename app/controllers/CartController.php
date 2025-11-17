<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../middleware.php';

class CartController {
  private static function ensureCart($userId) {
    $stmt = db()->prepare('SELECT id FROM carts WHERE user_id = ?');
    $stmt->execute([$userId]);
    $cart = $stmt->fetch();
    if ($cart) return $cart['id'];
    db()->prepare('INSERT INTO carts (user_id) VALUES (?)')->execute([$userId]);
    return db()->lastInsertId();
  }

  public static function get() {
    $user = requireAuth();
    $cartId = self::ensureCart($user['id']);
    $stmt = db()->prepare('SELECT ci.id, ci.car_id, c.title, ci.qty, ci.price, (ci.qty * ci.price) AS subtotal
                           FROM cart_items ci JOIN cars c ON c.id = ci.car_id WHERE ci.cart_id = ?');
    $stmt->execute([$cartId]);
    $items = $stmt->fetchAll();
    $total = array_sum(array_column($items, 'subtotal'));
    json(['items' => $items, 'total' => $total]);
  }

  public static function add() {
    $user = requireAuth();
    $cartId = self::ensureCart($user['id']);
    $carId = intval($_POST['car_id'] ?? 0);
    $qty = max(1, intval($_POST['qty'] ?? 1));

    $car = db()->prepare('SELECT id, price, stock, title FROM cars WHERE id = ?');
    $car->execute([$carId]);
    $c = $car->fetch();
    if (!$c) json(['error' => 'Car not found'], 404);
    if ($qty > $c['stock']) json(['error' => 'Insufficient stock'], 422);

    // check if item exists, update qty
    $chk = db()->prepare('SELECT id, qty FROM cart_items WHERE cart_id = ? AND car_id = ?');
    $chk->execute([$cartId, $carId]);
    $item = $chk->fetch();
    if ($item) {
      $newQty = $item['qty'] + $qty;
      if ($newQty > $c['stock']) json(['error' => 'Insufficient stock'], 422);
      db()->prepare('UPDATE cart_items SET qty = ?, price = ? WHERE id = ?')->execute([$newQty, $c['price'], $item['id']]);
    } else {
      db()->prepare('INSERT INTO cart_items (cart_id, car_id, qty, price) VALUES (?,?,?,?)')->execute([$cartId, $carId, $qty, $c['price']]);
    }
    json(['message' => 'Added']);
  }

  public static function remove() {
    $user = requireAuth();
    $cartId = self::ensureCart($user['id']);
    $itemId = intval($_POST['item_id'] ?? 0);
    db()->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?')->execute([$itemId, $cartId]);
    json(['message' => 'Removed']);
  }

  public static function clear() {
    $user = requireAuth();
    $cartId = self::ensureCart($user['id']);
    db()->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
    json(['message' => 'Cleared']);
  }
}
