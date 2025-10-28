<?php
// データベース設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'hajime_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// サイト設定
define('SITE_URL', 'http://localhost/hajime');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// セッション設定
define('SESSION_LIFETIME', 3600); // 1時間

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');
