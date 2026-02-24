<?php
/**
 * カスケード削除のテスト
 * 
 * フォームを削除したときに、関連するフィールドも削除されることを確認します。
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/Form.php';

try {
    echo "=== カスケード削除のテスト ===\n\n";

    $form = new Form();
    $db = Database::getInstance();

    // テストフォームを作成
    echo "1. テストフォームを作成\n";
    $formId = $form->create(
        'カスケード削除テスト',
        'このフォームは削除テスト用です',
        []
    );
    echo "   作成されたフォームID: $formId\n\n";

    // フィールドを追加
    echo "2. フィールドを追加\n";
    $fields = [
        [
            'type' => 'text',
            'label' => 'テストフィールド1',
            'name' => 'test1',
            'config' => ['required' => true]
        ],
        [
            'type' => 'email',
            'label' => 'テストフィールド2',
            'name' => 'test2',
            'config' => ['required' => false]
        ]
    ];
    $form->saveFields($formId, $fields);
    echo "   フィールドを追加しました\n\n";

    // フィールドが存在することを確認
    echo "3. フィールドの存在を確認\n";
    $stmt = $db->query(
        "SELECT COUNT(*) as count FROM inform_fields WHERE form_id = :form_id",
        [':form_id' => $formId]
    );
    $result = $stmt->fetch();
    echo "   削除前のフィールド数: {$result['count']}\n\n";

    // フォームを削除
    echo "4. フォームを削除\n";
    $form->delete($formId);
    echo "   フォームを削除しました\n\n";

    // フィールドが削除されたことを確認
    echo "5. フィールドが削除されたことを確認\n";
    $stmt = $db->query(
        "SELECT COUNT(*) as count FROM inform_fields WHERE form_id = :form_id",
        [':form_id' => $formId]
    );
    $result = $stmt->fetch();
    echo "   削除後のフィールド数: {$result['count']}\n\n";

    if ($result['count'] == 0) {
        echo "✓ カスケード削除が正常に動作しました！\n";
    } else {
        echo "✗ エラー: フィールドが削除されていません\n";
    }

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
