<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../middleware.php';

class OrderController {
  public static function checkout() {
    $user = requireAuth();
    // load cart
    $cartStmt = db()->prepare('SELECT id FROM carts WHERE user_id = ?');
    $cartStmt->execute([$user['id']]);
    $cart = $cartStmt->fetch();
    if (!$cart) json(['error' => 'Cart empty'], 422);

    $itemsStmt = db()->prepare('SELECT ci.*, c.title, c.stock FROM cart_items ci JOIN cars c ON c.id = ci.car_id WHERE ci.cart_id = ?');
    $itemsStmt->execute([$cart['id']]);
    $items = $itemsStmt->fetchAll();
    if (empty($items)) json(['error' => 'Cart empty'], 422);

    // stock check
    foreach ($items as $it) {
      if ($it['qty'] > $it['stock']) json(['error' => 'Insufficient stock for ' . $it['title']], 422);
    }

    $total = 0;
    foreach ($items as $it) $total += ($it['qty'] * $it['price']);

    $code = 'ORD' . strtoupper(substr(md5(uniqid('', true)), 0, 10));

    try {
      db()->beginTransaction();

      // create order (paid langsung)
      $oStmt = db()->prepare('INSERT INTO orders (user_id, order_code, status, total) VALUES (?,?,?,?)');
      $oStmt->execute([$user['id'], $code, 'paid', $total]);
      $orderId = db()->lastInsertId();

      // order items
      $oi = db()->prepare('INSERT INTO order_items (order_id, car_id, title, qty, price, subtotal) VALUES (?,?,?,?,?,?)');
      $updStock = db()->prepare('UPDATE cars SET stock = stock - ? WHERE id = ?');
      foreach ($items as $it) {
        $oi->execute([$orderId, $it['car_id'], $it['title'], $it['qty'], $it['price'], $it['qty'] * $it['price']]);
        $updStock->execute([$it['qty'], $it['car_id']]);
      }

      // payment snapshot
      db()->prepare('INSERT INTO payments (order_id, amount, method) VALUES (?,?,?)')->execute([$orderId, $total, 'cash']);

      // clear cart
      db()->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cart['id']]);

      db()->commit();
      json(['message' => 'Payment success', 'order_id' => $orderId, 'order_code' => $code, 'total' => $total]);
    } catch (Exception $e) {
      db()->rollBack();
      json(['error' => 'Checkout failed'], 500);
    }
  }

  public static function myOrders() {
    $user = requireAuth();
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
    json(['data' => $orders]);
  }

  public static function detail($id) {
    $user = requireAuth();
    // owner or admin
    $own = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $own->execute([$id]);
    $order = $own->fetch();
    if (!$order) json(['error' => 'Not found'], 404);
    if ($user['role'] !== 'admin' && $order['user_id'] != $user['id']) json(['error' => 'Forbidden'], 403);

    $items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $items->execute([$id]);
    $order['items'] = $items->fetchAll();
    json($order);
  }

  // Admin
  public static function adminList() {
    $user = requireAuth(); requireAdmin($user);
    $stmt = db()->query('SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC');
    json(['data' => $stmt->fetchAll()]);
  }

  public static function adminUpdate($id) {
    $user = requireAuth(); requireAdmin($user);
    $status = $_POST['status'] ?? null;
    if (!in_array($status, ['pending','paid','cancelled'])) json(['error' => 'Invalid status'], 422);
    db()->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $id]);
    json(['message' => 'Updated']);
  }

  public static function adminDelete($id) {
    $user = requireAuth(); requireAdmin($user);
    db()->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
    json(['message' => 'Deleted']);
  }
}
