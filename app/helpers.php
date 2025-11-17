<?php
function json($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function generateToken() {
  return bin2hex(random_bytes(32));
}

function hashPassword($plain) {
  return password_hash($plain, PASSWORD_BCRYPT);
}

function verifyPassword($plain, $hash) {
  return password_verify($plain, $hash);
}

function sanitize($s) {
  return trim(filter_var($s, FILTER_SANITIZE_STRING));
}

function moveUploadedImage($fieldName) {
  if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) return null;
  $tmp = $_FILES[$fieldName]['tmp_name'];
  $ext = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
  $allowed = ['jpg','jpeg','png','webp'];
  if (!in_array(strtolower($ext), $allowed)) return null;
  if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
  $filename = uniqid('car_', true) . '.' . $ext;
  $dest = UPLOAD_DIR . $filename;
  if (!move_uploaded_file($tmp, $dest)) return null;
  return '/uploads/cars/' . $filename; // path publik relatif
}
