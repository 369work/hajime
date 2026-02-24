<?php
/**
 * Form クラスの基本的なテスト
 * 
 * このファイルは開発中の動作確認用です。
 */

// Hajime CMSの設定を読み込み
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/Form.php';

try {
    echo "=== Form クラスのテスト ===\n\n";

    $form = new Form();

    // テスト1: フォームの作成
    echo "テスト1: フォームの作成\n";
    $formId = $form->create(
        'お問い合わせフォーム',
        'サンプルのお問い合わせフォームです',
        [
            'email_notifications' => true,
            'notification_emails' => ['admin@example.com'],
            'success_message' => 'お問い合わせありがとうございます。'
        ]
    );
    echo "作成されたフォームID: $formId\n\n";

    // テスト2: フォームの取得
    echo "テスト2: フォームの取得\n";
    $retrievedForm = $form->getById($formId);
    echo "取得したフォーム名: {$retrievedForm['name']}\n";
    echo "設定: " . json_encode($retrievedForm['settings'], JSON_UNESCAPED_UNICODE) . "\n\n";

    // テスト3: フォームの更新
    echo "テスト3: フォームの更新\n";
    $updated = $form->update(
        $formId,
        'お問い合わせフォーム（更新版）',
        '更新されたフォームです',
        [
            'email_notifications' => false,
            'notification_emails' => [],
            'success_message' => '送信完了しました。'
        ]
    );
    echo "更新結果: " . ($updated ? '成功' : '失敗') . "\n\n";

    // テスト4: フィールドの保存
    echo "テスト4: フィールドの保存\n";
    $fields = [
        [
            'type' => 'text',
            'label' => 'お名前',
            'name' => 'name',
            'config' => [
                'placeholder' => 'お名前を入力してください',
                'required' => true,
                'max_length' => 100
            ]
        ],
        [
            'type' => 'email',
            'label' => 'メールアドレス',
            'name' => 'email',
            'config' => [
                'placeholder' => 'example@example.com',
                'required' => true
            ]
        ],
        [
            'type' => 'textarea',
            'label' => 'お問い合わせ内容',
            'name' => 'message',
            'config' => [
                'placeholder' => 'お問い合わせ内容を入力してください',
                'required' => true,
                'max_length' => 1000
            ]
        ]
    ];
    $fieldsSaved = $form->saveFields($formId, $fields);
    echo "フィールド保存結果: " . ($fieldsSaved ? '成功' : '失敗') . "\n\n";

    // テスト5: フィールドの取得
    echo "テスト5: フィールドの取得\n";
    $retrievedFields = $form->getFields($formId);
    echo "取得したフィールド数: " . count($retrievedFields) . "\n";
    foreach ($retrievedFields as $field) {
        echo "  - {$field['label']} ({$field['type']})\n";
    }
    echo "\n";

    // テスト6: すべてのフォームの取得
    echo "テスト6: すべてのフォームの取得\n";
    $allForms = $form->getAll();
    echo "フォーム総数: " . count($allForms) . "\n";
    foreach ($allForms as $f) {
        echo "  - ID: {$f['id']}, 名前: {$f['name']}\n";
    }
    echo "\n";

    // テスト7: フォームの削除
    echo "テスト7: フォームの削除\n";
    $deleted = $form->delete($formId);
    echo "削除結果: " . ($deleted ? '成功' : '失敗') . "\n";
    
    // 削除後の確認
    $deletedForm = $form->getById($formId);
    echo "削除後の取得結果: " . ($deletedForm === null ? 'null（正常に削除されました）' : '存在します（エラー）') . "\n\n";

    echo "=== すべてのテストが完了しました ===\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
