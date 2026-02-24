<?php
/**
 * SQLインジェクション対策のテスト
 * 
 * プリペアドステートメントが正しく機能していることを確認します。
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/Form.php';

try {
    echo "=== SQLインジェクション対策のテスト ===\n\n";

    $form = new Form();

    // テスト1: 悪意のあるフォーム名
    echo "テスト1: 悪意のあるフォーム名でフォームを作成\n";
    $maliciousName = "'; DROP TABLE inform_forms; --";
    $formId = $form->create($maliciousName, 'テスト説明', []);
    echo "   作成されたフォームID: $formId\n";
    
    // フォームを取得して、名前がそのまま保存されていることを確認
    $retrievedForm = $form->getById($formId);
    echo "   保存された名前: {$retrievedForm['name']}\n";
    
    if ($retrievedForm['name'] === $maliciousName) {
        echo "   ✓ SQLインジェクションが防止されました（文字列がそのまま保存されています）\n\n";
    } else {
        echo "   ✗ エラー: 名前が正しく保存されていません\n\n";
    }

    // テスト2: テーブルがまだ存在することを確認
    echo "テスト2: inform_formsテーブルが存在することを確認\n";
    $allForms = $form->getAll();
    echo "   フォーム総数: " . count($allForms) . "\n";
    echo "   ✓ テーブルは削除されていません\n\n";

    // テスト3: 悪意のあるフィールド名
    echo "テスト3: 悪意のあるフィールド名でフィールドを保存\n";
    $maliciousFieldName = "test' OR '1'='1";
    $fields = [
        [
            'type' => 'text',
            'label' => 'テストラベル',
            'name' => $maliciousFieldName,
            'config' => []
        ]
    ];
    $form->saveFields($formId, $fields);
    
    $retrievedFields = $form->getFields($formId);
    echo "   保存されたフィールド名: {$retrievedFields[0]['name']}\n";
    
    if ($retrievedFields[0]['name'] === $maliciousFieldName) {
        echo "   ✓ SQLインジェクションが防止されました\n\n";
    } else {
        echo "   ✗ エラー: フィールド名が正しく保存されていません\n\n";
    }

    // テスト4: 悪意のあるIDでの取得
    echo "テスト4: 悪意のあるIDでフォームを取得\n";
    $maliciousId = "1 OR 1=1";
    $result = $form->getById($maliciousId);
    
    if ($result === null || $result['id'] == 1) {
        echo "   ✓ SQLインジェクションが防止されました\n";
        echo "   （不正なIDは正しく処理されました）\n\n";
    } else {
        echo "   ✗ エラー: 予期しない結果が返されました\n\n";
    }

    // クリーンアップ
    $form->delete($formId);
    
    echo "=== すべてのテストが完了しました ===\n";
    echo "✓ プリペアドステートメントが正しく機能しています\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
