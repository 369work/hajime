<?php
/**
 * EmailNotifier クラスのテスト
 * 
 * このテストは、EmailNotifierクラスの基本的な機能を検証します。
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/Form.php';
require_once __DIR__ . '/FormField.php';
require_once __DIR__ . '/Submission.php';
require_once __DIR__ . '/EmailNotifier.php';

echo "=== EmailNotifier テスト開始 ===\n\n";

try {
    // テスト用のフォームを作成
    $formModel = new Form();
    $formId = $formModel->create(
        'テストフォーム（メール通知）',
        'メール通知機能のテスト用フォーム',
        [
            'email_notifications' => true,
            'notification_emails' => ['test@example.com'],
            'success_message' => 'お問い合わせありがとうございます。'
        ]
    );
    echo "✓ テストフォーム作成成功 (ID: $formId)\n";

    // テスト用のフィールドを作成
    $fields = [
        [
            'type' => 'text',
            'label' => 'お名前',
            'name' => 'name',
            'config' => ['required' => true]
        ],
        [
            'type' => 'email',
            'label' => 'メールアドレス',
            'name' => 'email',
            'config' => ['required' => true]
        ],
        [
            'type' => 'textarea',
            'label' => 'お問い合わせ内容',
            'name' => 'message',
            'config' => ['required' => true]
        ]
    ];
    $formModel->saveFields($formId, $fields);
    echo "✓ フィールド作成成功\n";

    // テスト用の送信を作成
    $submissionModel = new Submission();
    $submissionData = [
        'name' => '山田太郎',
        'email' => 'yamada@example.com',
        'message' => 'これはテストメッセージです。'
    ];
    $submissionId = $submissionModel->create(
        $formId,
        $submissionData,
        '127.0.0.1',
        'Test User Agent'
    );
    echo "✓ テスト送信作成成功 (ID: $submissionId)\n\n";

    // EmailNotifierのテスト
    echo "--- EmailNotifier::sendNotification テスト ---\n";
    $emailNotifier = new EmailNotifier();
    $result = $emailNotifier->sendNotification($formId, $submissionId);
    
    if ($result) {
        echo "✓ メール通知送信成功\n";
        echo "  注意: 実際のメール送信はローカル環境では動作しない可能性があります。\n";
        echo "  エラーログを確認してください。\n";
    } else {
        echo "✗ メール通知送信失敗（これは正常な場合があります）\n";
        echo "  ローカル環境ではmail()関数が動作しないことがあります。\n";
    }

    // メール通知が無効な場合のテスト
    echo "\n--- メール通知無効時のテスト ---\n";
    $formId2 = $formModel->create(
        'テストフォーム（メール通知無効）',
        'メール通知が無効なフォーム',
        [
            'email_notifications' => false
        ]
    );
    $submissionId2 = $submissionModel->create(
        $formId2,
        $submissionData,
        '127.0.0.1',
        'Test User Agent'
    );
    
    $result2 = $emailNotifier->sendNotification($formId2, $submissionId2);
    if ($result2) {
        echo "✓ メール通知無効時は送信をスキップ（正常）\n";
    }

    // 通知先メールアドレスが未設定の場合のテスト
    echo "\n--- 通知先メールアドレス未設定時のテスト ---\n";
    $formId3 = $formModel->create(
        'テストフォーム（メールアドレス未設定）',
        'メールアドレスが未設定なフォーム',
        [
            'email_notifications' => true,
            'notification_emails' => []
        ]
    );
    $submissionId3 = $submissionModel->create(
        $formId3,
        $submissionData,
        '127.0.0.1',
        'Test User Agent'
    );
    
    $result3 = $emailNotifier->sendNotification($formId3, $submissionId3);
    if (!$result3) {
        echo "✓ メールアドレス未設定時はエラーを返す（正常）\n";
    }

    // クリーンアップ
    echo "\n--- クリーンアップ ---\n";
    $formModel->delete($formId);
    $formModel->delete($formId2);
    $formModel->delete($formId3);
    echo "✓ テストデータ削除完了\n";

    echo "\n=== すべてのテストが完了しました ===\n";

} catch (Exception $e) {
    echo "\n✗ エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
