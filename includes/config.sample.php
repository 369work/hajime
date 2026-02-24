<?php
// データベース設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'hajime_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// サイト設定
define('SITE_URL', 'http://localhost/hajime');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('ASSET_URL', SITE_URL . '/assets/');

// セッション設定
define('SESSION_LIFETIME', 3600); // 1時間

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// Inform メール設定
define('INFORM_EMAIL_METHOD', 'mail'); // 'smtp' または 'mail'

// SMTP設定（INFORM_EMAIL_METHOD が 'smtp' の場合に使用）
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls'); // 'tls', 'ssl', または ''
define('SMTP_USERNAME', 'your-email@example.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'noreply@example.com');
define('SMTP_FROM_NAME', 'Inform お問い合わせフォーム');

// mail()設定（INFORM_EMAIL_METHOD が 'mail' の場合に使用）
define('MAIL_FROM_EMAIL', 'noreply@localhost');
define('MAIL_FROM_NAME', 'Inform お問い合わせフォーム');
