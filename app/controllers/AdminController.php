<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../middleware.php';

class AdminController {
  public static function summary() {
    $user = requireAuth(); requireAdmin($user);

    $pdo = db();

    $totalSales = $pdo->query('SELECT COALESCE(SUM(amount),0) AS total FROM payments')->fetch()['total'];
    $totalOrders = $pdo->query('SELECT COUNT(*) AS c FROM orders')->fetch()['c'];
    $best = $pdo->query('SELECT title, SUM(qty) AS sold FROM order_items GROUP BY car_id ORDER BY sold DESC LIMIT 5')->fetchAll();

    $daily = $pdo->query("
      SELECT DATE(paid_at) AS day, SUM(amount) AS revenue
      FROM payments
      GROUP BY DATE(paid_at)
      ORDER BY day DESC
      LIMIT 14
    ")->fetchAll();

    json([
      'total_sales' => (float)$totalSales,
      'total_orders' => (int)$totalOrders,
      'best_sellers' => $best,
      'daily_revenue' => $daily
    ]);
  }
}
