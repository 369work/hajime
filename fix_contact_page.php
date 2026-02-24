<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== お問い合わせページのスラッグを修正 ===\n\n";

// スラッグを修正
$stmt = $db->query(
    "UPDATE pages SET slug = 'contact' WHERE id = 3",
    []
);

echo "✓ スラッグを 'contact' に更新しました\n\n";

// 確認
$stmt = $db->query("SELECT id, title, slug, status FROM pages WHERE id = 3");
$page = $stmt->fetch();

echo "更新後の情報:\n";
echo "ID: {$page['id']}\n";
echo "タイトル: {$page['title']}\n";
echo "スラッグ: {$page['slug']}\n";
echo "ステータス: {$page['status']}\n";
echo "URL: http://localhost/hajime/?page={$page['slug']}\n";
