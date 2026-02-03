<?php
require_once __DIR__ . '/app.php';

if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.use_strict_mode', '1');
  ini_set('session.cookie_httponly', '1');

  $isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443);

  if ($isHttps) {
    ini_set('session.cookie_secure', '1');
  }

  session_start();
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function require_login() {
  if (empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
  }
}

function require_role($role) {
  require_login();
  if (($_SESSION['user']['role'] ?? '') !== $role) {
    http_response_code(403);
    exit('Forbidden');
  }
}

function require_any_role($roles) {
  require_login();
  $r = $_SESSION['user']['role'] ?? '';
  if (!in_array($r, $roles, true)) {
    http_response_code(403);
    exit('Forbidden');
  }
}

function csrf_token() {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf'];
}

function csrf_check() {
  $t = $_POST['csrf'] ?? '';
  if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
    http_response_code(400);
    exit('Bad request (CSRF)');
  }
}

function flash_set($type, $msg) {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get() {
  $f = $_SESSION['flash'] ?? null;
  unset($_SESSION['flash']);
  return $f;
}
