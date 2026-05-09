<?php
require_once __DIR__ . '/../config/session.php';
$_SESSION = [];
session_destroy();
header('Location: /auth/login.php');
exit;
