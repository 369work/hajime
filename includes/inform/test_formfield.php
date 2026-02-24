<?php
/**
 * FormFieldクラスのテスト
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/FormField.php';

echo "=== FormFieldクラスのテスト ===\n\n";

try {
    $formField = new FormField();
    
    // テスト1: フィールドタイプ定数の確認
    echo "テスト1: フィールドタイプ定数\n";
    echo "TEXT: " . FormField::TYPE_TEXT . "\n";
    echo "EMAIL: " . FormField::TYPE_EMAIL . "\n";
    echo "TEXTAREA: " . FormField::TYPE_TEXTAREA . "\n";
    echo "SELECT: " . FormField::TYPE_SELECT . "\n";
    echo "CHECKBOX: " . FormField::TYPE_CHECKBOX . "\n";
    echo "RADIO: " . FormField::TYPE_RADIO . "\n";
    echo "✓ フィールドタイプ定数が正しく定義されています\n\n";
    
    // テスト2: バリデーションメソッドのテスト
    echo "テスト2: バリデーションメソッド\n";
    
    // 必須フィールドのテスト
    $fieldConfig = [
        'type' => FormField::TYPE_TEXT,
        'label' => 'お名前',
        'config' => ['required' => true]
    ];
    
    $result = $formField->validate('', $fieldConfig);
    if (!$result['valid'] && strpos($result['error'], '必須') !== false) {
        echo "✓ 必須フィールドの検証が正しく動作しています\n";
    } else {
        echo "✗ 必須フィールドの検証に失敗しました\n";
    }
    
    // メールアドレスのバリデーション
    $emailConfig = [
        'type' => FormField::TYPE_EMAIL,
        'label' => 'メールアドレス',
        'config' => ['required' => true]
    ];
    
    $result = $formField->validate('invalid-email', $emailConfig);
    if (!$result['valid'] && strpos($result['error'], 'メールアドレス') !== false) {
        echo "✓ メールアドレスの検証が正しく動作しています\n";
    } else {
        echo "✗ メールアドレスの検証に失敗しました\n";
    }
    
    $result = $formField->validate('test@example.com', $emailConfig);
    if ($result['valid']) {
        echo "✓ 有効なメールアドレスが受け入れられました\n";
    } else {
        echo "✗ 有効なメールアドレスが拒否されました\n";
    }
    
    // 最大長のバリデーション
    $textConfig = [
        'type' => FormField::TYPE_TEXT,
        'label' => 'テキスト',
        'config' => ['max_length' => 10]
    ];
    
    $result = $formField->validate('これは10文字を超える長いテキストです', $textConfig);
    if (!$result['valid'] && strpos($result['error'], '文字以内') !== false) {
        echo "✓ 最大長の検証が正しく動作しています\n";
    } else {
        echo "✗ 最大長の検証に失敗しました\n";
    }
    
    echo "\n";
    
    // テスト3: レンダリングメソッドのテスト
    echo "テスト3: レンダリングメソッド\n";
    
    // テキストフィールドのレンダリング
    $textFieldConfig = [
        'type' => FormField::TYPE_TEXT,
        'label' => 'お名前',
        'name' => 'name',
        'config' => [
            'placeholder' => 'お名前を入力してください',
            'required' => true,
            'max_length' => 100
        ]
    ];
    
    $html = $formField->render($textFieldConfig);
    if (strpos($html, 'お名前') !== false && 
        strpos($html, 'name') !== false && 
        strpos($html, 'required') !== false &&
        strpos($html, 'maxlength="100"') !== false) {
        echo "✓ テキストフィールドのレンダリングが正しく動作しています\n";
    } else {
        echo "✗ テキストフィールドのレンダリングに失敗しました\n";
    }
    
    // セレクトフィールドのレンダリング
    $selectFieldConfig = [
        'type' => FormField::TYPE_SELECT,
        'label' => 'お問い合わせ種別',
        'name' => 'inquiry_type',
        'config' => [
            'required' => true,
            'options' => ['質問', 'ご意見', 'その他']
        ]
    ];
    
    $html = $formField->render($selectFieldConfig);
    if (strpos($html, '<select') !== false && 
        strpos($html, '質問') !== false && 
        strpos($html, 'ご意見') !== false) {
        echo "✓ セレクトフィールドのレンダリングが正しく動作しています\n";
    } else {
        echo "✗ セレクトフィールドのレンダリングに失敗しました\n";
    }
    
    // テキストエリアのレンダリング
    $textareaFieldConfig = [
        'type' => FormField::TYPE_TEXTAREA,
        'label' => 'お問い合わせ内容',
        'name' => 'message',
        'config' => [
            'placeholder' => 'お問い合わせ内容を入力してください',
            'required' => true,
            'max_length' => 1000
        ]
    ];
    
    $html = $formField->render($textareaFieldConfig);
    if (strpos($html, '<textarea') !== false && 
        strpos($html, 'お問い合わせ内容') !== false && 
        strpos($html, 'maxlength="1000"') !== false) {
        echo "✓ テキストエリアのレンダリングが正しく動作しています\n";
    } else {
        echo "✗ テキストエリアのレンダリングに失敗しました\n";
    }
    
    echo "\n";
    
    // テスト4: CRUD操作のテスト（データベース接続が必要）
    echo "テスト4: CRUD操作\n";
    
    // テスト用のフォームIDを使用（存在しない場合はスキップ）
    $testFormId = 1;
    
    try {
        // フィールドの作成
        $fieldId = $formField->create($testFormId, FormField::TYPE_TEXT, 'テストフィールド', [
            'placeholder' => 'テスト',
            'required' => true,
            'max_length' => 50
        ]);
        echo "✓ フィールドの作成が成功しました (ID: {$fieldId})\n";
        
        // フィールドの取得
        $field = $formField->getById($fieldId);
        if ($field && $field['label'] === 'テストフィールド') {
            echo "✓ フィールドの取得が成功しました\n";
        } else {
            echo "✗ フィールドの取得に失敗しました\n";
        }
        
        // フィールドの更新
        $formField->update($fieldId, FormField::TYPE_EMAIL, '更新されたフィールド', [
            'required' => false
        ]);
        $updatedField = $formField->getById($fieldId);
        if ($updatedField && $updatedField['label'] === '更新されたフィールド') {
            echo "✓ フィールドの更新が成功しました\n";
        } else {
            echo "✗ フィールドの更新に失敗しました\n";
        }
        
        // フィールドの削除
        $formField->delete($fieldId);
        $deletedField = $formField->getById($fieldId);
        if ($deletedField === null) {
            echo "✓ フィールドの削除が成功しました\n";
        } else {
            echo "✗ フィールドの削除に失敗しました\n";
        }
        
    } catch (Exception $e) {
        echo "⚠ CRUD操作のテストをスキップしました: " . $e->getMessage() . "\n";
        echo "  (フォームID {$testFormId} が存在しない可能性があります)\n";
    }
    
    echo "\n=== すべてのテストが完了しました ===\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
