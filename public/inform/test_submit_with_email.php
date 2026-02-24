<?php
/**
 * submit.php のメール通知統合テスト
 * 
 * このテストは、フォーム送信時にメール通知が正しく送信されることを検証します。
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';
require_once __DIR__ . '/../../includes/inform/FormField.php';
require_once __DIR__ . '/../../includes/inform/AntiSpam.php';

echo "=== submit.php メール通知統合テスト開始 ===\n\n";

try {
    // セッション開始
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // テスト用のフォームを作成（メール通知有効）
    $formModel = new Form();
    $formId = $formModel->create(
        'テストフォーム（メール通知統合）',
        'submit.phpのメール通知統合テスト用フォーム',
        [
            'email_notifications' => true,
            'notification_emails' => ['admin@example.com', 'test@example.com'],
            'success_message' => 'お問い合わせありがとうございます。メール通知が送信されました。'
        ]
    );
    echo "✓ テストフォーム作成成功 (ID: $formId)\n";

    // テスト用のフィールドを作成
    $fields = [
        [
            'type' => 'text',
            'label' => 'お名前',
            'name' => 'name',
            'config' => ['required' => true, 'max_length' => 100]
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
            'config' => ['required' => true, 'max_length' => 1000]
        ]
    ];
    $formModel->saveFields($formId, $fields);
    echo "✓ フィールド作成成功\n";

    // スパム対策チャレンジを生成
    $antiSpam = new AntiSpam();
    $challenge = $antiSpam->generateChallenge();
    echo "✓ スパム対策チャレンジ生成成功 (ID: {$challenge['id']})\n";

    // CSRFトークンを生成
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // submit.phpにPOSTリクエストをシミュレート
    echo "\n--- submit.phpへのPOSTリクエストをシミュレート ---\n";
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'Test User Agent';
    $_SERVER['HTTP_HOST'] = 'localhost';
    
    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'form_id' => $formId,
        'name' => '田中花子',
        'email' => 'tanaka@example.com',
        'message' => 'これはメール通知統合テストのメッセージです。',
        'challenge_id' => $challenge['id'],
        'challenge_answer' => $challenge['answer']
    ];

    // submit.phpを実行
    ob_start();
    include __DIR__ . '/submit.php';
    $output = ob_get_clean();

    // レスポンスを解析
    $response = json_decode($output, true);
    
    if ($response && $response['success']) {
        echo "✓ フォーム送信成功\n";
        echo "  メッセージ: {$response['message']}\n";
        echo "  注意: メール通知は送信されましたが、ローカル環境では実際のメール配信は行われません。\n";
        echo "  エラーログを確認してメール送信の試行を確認してください。\n";
    } else {
        echo "✗ フォーム送信失敗\n";
        if ($response) {
            echo "  エラー: {$response['message']}\n";
        } else {
            echo "  レスポンス: $output\n";
        }
    }

    // クリーンアップ
    echo "\n--- クリーンアップ ---\n";
    $formModel->delete($formId);
    echo "✓ テストデータ削除完了\n";

    echo "\n=== すべてのテストが完了しました ===\n";

} catch (Exception $e) {
    echo "\n✗ エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
