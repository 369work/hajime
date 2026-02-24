<?php
/**
 * admin/inform/index.php の基本的なテスト
 * 
 * このファイルは開発中の動作確認用です。
 * 認証が必要なページなので、直接アクセスできることを確認します。
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';

try {
    echo "=== admin/inform/index.php のテスト ===\n\n";

    // テスト1: Form クラスが正しく動作するか確認
    echo "テスト1: Form クラスの動作確認\n";
    $formModel = new Form();
    echo "Form クラスのインスタンス化: 成功\n\n";

    // テスト2: フォーム一覧の取得
    echo "テスト2: フォーム一覧の取得\n";
    $forms = $formModel->getAll();
    echo "取得したフォーム数: " . count($forms) . "\n";
    
    if (count($forms) > 0) {
        echo "フォーム一覧:\n";
        foreach ($forms as $form) {
            echo "  - ID: {$form['id']}, 名前: {$form['name']}\n";
            echo "    説明: " . (strlen($form['description'] ?? '') > 50 
                ? substr($form['description'], 0, 50) . '...' 
                : ($form['description'] ?? '')) . "\n";
            echo "    作成日時: {$form['created_at']}\n";
            echo "    更新日時: {$form['updated_at']}\n";
        }
    } else {
        echo "フォームが存在しません。\n";
    }
    echo "\n";

    // テスト3: テストフォームを作成して表示を確認
    echo "テスト3: テストフォームの作成と表示\n";
    $testFormId = $formModel->create(
        'テストフォーム',
        'これはテスト用のフォームです。',
        [
            'email_notifications' => true,
            'notification_emails' => ['test@example.com']
        ]
    );
    echo "テストフォームID: $testFormId\n";

    // 再度一覧を取得
    $forms = $formModel->getAll();
    echo "フォーム数（作成後）: " . count($forms) . "\n";

    // テストフォームを削除
    $formModel->delete($testFormId);
    echo "テストフォームを削除しました。\n\n";

    // テスト4: ページファイルの存在確認
    echo "テスト4: ページファイルの存在確認\n";
    $indexPath = __DIR__ . '/index.php';
    $deletePath = __DIR__ . '/form-delete.php';
    
    echo "index.php: " . (file_exists($indexPath) ? '存在します' : '存在しません') . "\n";
    echo "form-delete.php: " . (file_exists($deletePath) ? '存在します' : '存在しません') . "\n\n";

    // テスト5: 必要なクラスとファイルの確認
    echo "テスト5: 必要なクラスとファイルの確認\n";
    echo "Auth クラス: " . (class_exists('Auth') ? '利用可能' : '利用不可') . "\n";
    echo "Database クラス: " . (class_exists('Database') ? '利用可能' : '利用不可') . "\n";
    echo "Form クラス: " . (class_exists('Form') ? '利用可能' : '利用不可') . "\n\n";

    echo "=== すべてのテストが完了しました ===\n";
    echo "\n注意: 実際のページ表示を確認するには、ブラウザで以下のURLにアクセスしてください:\n";
    echo ADMIN_URL . "/inform/index.php\n";
    echo "（ログインが必要です）\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
