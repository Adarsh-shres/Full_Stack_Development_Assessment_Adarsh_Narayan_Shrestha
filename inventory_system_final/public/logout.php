<?php
require_once __DIR__ . '/../config/security.php';

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/login.php');
exit;
