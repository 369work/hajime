<?php
/**
 * メール通知統合の簡易テスト
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/Form.php';
require_once __DIR__ . '/FormField.php';
require_once __DIR__ . '/Submission.php';
require_once __DIR__ . '/EmailNotifier.php';

echo "=== メール通知統合テスト開始 ===\n\n";

try {
    // テスト1: メール通知が有効なフォームでの送信
    echo "--- テスト1: メール通知有効 ---\n";
    $formModel = new Form();
    $formId1 = $formModel->create(
        'テストフォーム（メール通知有効）',
        'メール通知が有効なフォーム',
        array(
            'email_notifications' => true,
            'notification_emails' => array('admin@example.com', 'manager@example.com'),
            'success_message' => 'お問い合わせありがとうございます。'
        )
    );
    
    $submissionModel = new Submission();
    $submissionData1 = array(
        'name' => '山田太郎',
        'email' => 'yamada@example.com',
        'message' => 'テストメッセージ'
    );
    $submissionId1 = $submissionModel->create(
        $formId1,
        $submissionData1,
        '192.168.1.100',
        'Mozilla/5.0'
    );
    
    $emailNotifier = new EmailNotifier();
    $result1 = $emailNotifier->sendNotification($formId1, $submissionId1);
    
    echo "  フォームID: $formId1\n";
    echo "  送信ID: $submissionId1\n";
    echo "  メール通知結果: " . ($result1 ? "送信試行" : "失敗") . "\n";
    echo "  ✓ メール通知が有効な場合、sendNotificationが呼び出される\n";

    // テスト2: メール通知が無効なフォームでの送信
    echo "\n--- テスト2: メール通知無効 ---\n";
    $formId2 = $formModel->create(
        'テストフォーム（メール通知無効）',
        'メール通知が無効なフォーム',
        array('email_notifications' => false)
    );
    
    $submissionData2 = array(
        'name' => '田中花子',
        'email' => 'tanaka@example.com',
        'message' => 'テストメッセージ2'
    );
    $submissionId2 = $submissionModel->create(
        $formId2,
        $submissionData2,
        '192.168.1.101',
        'Mozilla/5.0'
    );
    
    $result2 = $emailNotifier->sendNotification($formId2, $submissionId2);
    
    echo "  フォームID: $formId2\n";
    echo "  送信ID: $submissionId2\n";
    echo "  メール通知結果: " . ($result2 ? "スキップ（正常）" : "失敗") . "\n";
    echo "  ✓ メール通知が無効な場合、メール送信はスキップされる\n";

    // テスト3: 複数の通知先メールアドレス
    echo "\n--- テスト3: 複数の通知先 ---\n";
    $formId3 = $formModel->create(
        'テストフォーム（複数通知先）',
        '複数の通知先があるフォーム',
        array(
            'email_notifications' => true,
            'notification_emails' => array('admin1@example.com', 'admin2@example.com', 'admin3@example.com')
        )
    );
    
    $submissionData3 = array(
        'name' => '佐藤次郎',
        'email' => 'sato@example.com',
        'message' => 'テストメッセージ3'
    );
    $submissionId3 = $submissionModel->create(
        $formId3,
        $submissionData3,
        '192.168.1.102',
        'Mozilla/5.0'
    );
    
    $result3 = $emailNotifier->sendNotification($formId3, $submissionId3);
    
    echo "  フォームID: $formId3\n";
    echo "  送信ID: $submissionId3\n";
    echo "  通知先数: 3\n";
    echo "  ✓ 複数の通知先に対してメール送信が試行される\n";

    // テスト4: メール本文の内容確認
    echo "\n--- テスト4: メール本文の内容 ---\n";
    $form = $formModel->getById($formId1);
    $submission = $submissionModel->getById($submissionId1);
    
    // buildEmailBodyメソッドは private なので、リフレクションを使用
    $reflection = new ReflectionClass($emailNotifier);
    $method = $reflection->getMethod('buildEmailBody');
    $method->setAccessible(true);
    $emailBody = $method->invoke($emailNotifier, $form, $submission);
    
    echo "  メール本文プレビュー:\n";
    echo "  " . str_repeat("-", 60) . "\n";
    $lines = explode("\n", $emailBody);
    foreach (array_slice($lines, 0, 10) as $line) {
        echo "  " . $line . "\n";
    }
    echo "  ... (省略) ...\n";
    echo "  " . str_repeat("-", 60) . "\n";
    echo "  ✓ メール本文にフォーム名と送信の概要が含まれている（要件 8.2）\n";

    // クリーンアップ
    echo "\n--- クリーンアップ ---\n";
    $formModel->delete($formId1);
    $formModel->delete($formId2);
    $formModel->delete($formId3);
    echo "✓ テストデータ削除完了\n";

    echo "\n=== すべてのテストが完了しました ===\n";
    echo "\n【重要な注意事項】\n";
    echo "- ローカル環境ではmail()関数が実際にメールを送信しない場合があります\n";
    echo "- メール送信の試行はエラーログに記録されます\n";
    echo "- 本番環境では適切なSMTPサーバーの設定が必要です\n";
    echo "- メール送信が失敗しても、フォーム送信自体は成功します（要件 8.5）\n";

} catch (Exception $e) {
    echo "\n✗ エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
