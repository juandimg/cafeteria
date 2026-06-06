<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['loggedin'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
