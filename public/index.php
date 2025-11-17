<?php
require_once __DIR__ . '/../app/helpers.php';

// Map action => controller@method
$routes = [
  // Auth
  'auth/register' => ['AuthController', 'register'],
  'auth/login'    => ['AuthController', 'login'],
  'auth/logout'   => ['AuthController', 'logout'],

  // Users
  'users'         => ['UserController', 'list'],
  'users/detail'  => ['UserController', 'detail'], // ?id=...
  'users/create'  => ['UserController', 'create'],
  'users/update'  => ['UserController', 'update'], // ?id=...
  'users/delete'  => ['UserController', 'delete'], // ?id=...

  // Cars
  'cars'          => ['CarController', 'list'],
  'cars/detail'   => ['CarController', 'detail'], // ?id=...
  'cars/create'   => ['CarController', 'create'],
  'cars/update'   => ['CarController', 'update'], // ?id=...
  'cars/delete'   => ['CarController', 'delete'], // ?id=...

  // Cart
  'cart'          => ['CartController', 'get'],
  'cart/add'      => ['CartController', 'add'],
  'cart/remove'   => ['CartController', 'remove'],
  'cart/clear'    => ['CartController', 'clear'],

  // Orders
  'checkout'      => ['OrderController', 'checkout'],
  'orders'        => ['OrderController', 'myOrders'],
  'orders/detail' => ['OrderController', 'detail'], // ?id=...

  // Admin
  'admin/orders'  => ['OrderController', 'adminList'],
  'admin/orders/update' => ['OrderController', 'adminUpdate'], // ?id=...
  'admin/orders/delete' => ['OrderController', 'adminDelete'], // ?id=...
  'admin/summary' => ['AdminController', 'summary']
];

// Autoload controllers
spl_autoload_register(function($class) {
  $path = __DIR__ . '/../app/controllers/' . $class . '.php';
  if (file_exists($path)) require_once $path;
});
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/middleware.php';

$action = $_GET['action'] ?? '';
if (!isset($routes[$action])) json(['error' => 'Route not found'], 404);

[$ctrl, $method] = $routes[$action];

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id !== null) {
  $ctrl::$method($id);
} else {
  $ctrl::$method();
}
