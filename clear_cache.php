<?php
echo "=== キャッシュクリア ===\n\n";

// OPcacheをクリア
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPcacheをクリアしました\n";
} else {
    echo "- OPcacheは有効ではありません\n";
}

// セッションをクリア
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
echo "✓ セッションをクリアしました\n";

echo "\nブラウザでページを再読み込みしてください（Ctrl+Shift+R）\n";
