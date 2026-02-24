<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== 古いチャレンジをクリア ===\n\n";

// すべてのチャレンジを削除
$stmt = $db->query("DELETE FROM inform_challenges");

echo "✓ すべての古いチャレンジを削除しました\n";
echo "ページを再読み込みすると、新しいチャレンジが生成されます\n";
