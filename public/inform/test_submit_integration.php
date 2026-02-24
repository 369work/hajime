<?php
/**
 * submit.php エンドポイントの統合テスト
 * 
 * 実際のHTTPリクエストをシミュレートしてテストします。
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';
require_once __DIR__ . '/../../includes/inform/AntiSpam.php';

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    echo "=== submit.php 統合テスト ===\n\n";

    // 準備: テスト用フォームを作成
    echo "準備: テスト用フォームの作成\n";
    $formModel = new Form();
    $formId = $formModel->create(
        'テスト用お問い合わせフォーム',
        '統合テスト用',
        [
            'email_notifications' => false,
            'success_message' => 'お問い合わせありがとうございます。'
        ]
    );
    echo "作成されたフォームID: $formId\n";

    // フィールドを追加
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
    echo "フィールドを追加しました\n\n";

    // スパム対策チャレンジを生成
    $antiSpam = new AntiSpam();
    $challenge = $antiSpam->generateChallenge();
    
    // 正しい答えを抽出
    $correctAnswer = '';
    if (preg_match('/「(.+?)」/', $challenge['question'], $matches)) {
        $correctAnswer = $matches[1];
    }
    
    echo "スパム対策チャレンジを生成しました\n";
    echo "Challenge ID: {$challenge['challenge_id']}\n";
    echo "Question: {$challenge['question']}\n";
    echo "Answer: {$correctAnswer}\n\n";

    // CSRFトークンを生成
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrfToken = $_SESSION['csrf_token'];

    // テスト1: 正常な送信（JSONレスポンスを確認）
    echo "テスト1: 正常な送信\n";
    
    // submit.phpを直接includeしてテスト
    $_POST = [
        'form_id' => $formId,
        'csrf_token' => $csrfToken,
        'challenge_id' => $challenge['challenge_id'],
        'challenge_answer' => $correctAnswer,
        'name' => '山田太郎',
        'email' => 'yamada@example.com',
        'message' => 'これはテストメッセージです。'
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';

    // 出力バッファリングを開始
    ob_start();
    
    // submit.phpを実行
    try {
        include __DIR__ . '/submit.php';
    } catch (Exception $e) {
        // exit()が呼ばれるので例外をキャッチ
    }
    
    $output = ob_get_clean();
    
    // JSONレスポンスをパース
    $response = json_decode($output, true);
    
    if ($response && $response['success'] === true) {
        echo "✓ 正常な送信が成功しました\n";
        echo "  メッセージ: {$response['message']}\n";
    } else {
        echo "✗ 送信に失敗しました\n";
        if ($response) {
            echo "  エラー: {$response['message']}\n";
        } else {
            echo "  レスポンス: $output\n";
        }
    }
    echo "\n";

    // テスト2: CSRFトークンエラー
    echo "テスト2: CSRFトークンエラー\n";
    
    $challenge2 = $antiSpam->generateChallenge();
    if (preg_match('/「(.+?)」/', $challenge2['question'], $matches)) {
        $correctAnswer2 = $matches[1];
    }
    
    $_POST = [
        'form_id' => $formId,
        'csrf_token' => 'invalid_token',
        'challenge_id' => $challenge2['challenge_id'],
        'challenge_answer' => $correctAnswer2,
        'name' => '山田太郎',
        'email' => 'yamada@example.com',
        'message' => 'テスト'
    ];
    
    ob_start();
    try {
        include __DIR__ . '/submit.php';
    } catch (Exception $e) {
    }
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    if ($response && $response['success'] === false && strpos($response['message'], 'セッション') !== false) {
        echo "✓ CSRFトークンエラーが正しく検出されました\n";
        echo "  メッセージ: {$response['message']}\n";
    } else {
        echo "✗ CSRFトークンエラーの検出に失敗しました\n";
    }
    echo "\n";

    // テスト3: バリデーションエラー
    echo "テスト3: バリデーションエラー（必須フィールド未入力）\n";
    
    $challenge3 = $antiSpam->generateChallenge();
    if (preg_match('/「(.+?)」/', $challenge3['question'], $matches)) {
        $correctAnswer3 = $matches[1];
    }
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_POST = [
        'form_id' => $formId,
        'csrf_token' => $_SESSION['csrf_token'],
        'challenge_id' => $challenge3['challenge_id'],
        'challenge_answer' => $correctAnswer3,
        'name' => '', // 空
        'email' => 'test@example.com',
        'message' => 'テスト'
    ];
    
    ob_start();
    try {
        include __DIR__ . '/submit.php';
    } catch (Exception $e) {
    }
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    if ($response && $response['success'] === false && strpos($response['message'], '必須') !== false) {
        echo "✓ バリデーションエラーが正しく検出されました\n";
        echo "  メッセージ: {$response['message']}\n";
    } else {
        echo "✗ バリデーションエラーの検出に失敗しました\n";
    }
    echo "\n";

    // クリーンアップ
    echo "クリーンアップ: テスト用フォームの削除\n";
    $formModel->delete($formId);
    echo "✓ テスト用フォームを削除しました\n\n";

    echo "=== すべてのテストが完了しました ===\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
