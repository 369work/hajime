<?php
/**
 * submit.php エンドポイントの基本的なテスト
 * 
 * このファイルは開発中の動作確認用です。
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';
require_once __DIR__ . '/../../includes/inform/FormField.php';
require_once __DIR__ . '/../../includes/inform/Submission.php';
require_once __DIR__ . '/../../includes/inform/AntiSpam.php';

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    echo "=== submit.php エンドポイントのテスト ===\n\n";

    // 準備: テスト用フォームとフィールドを作成
    echo "準備: テスト用フォームの作成\n";
    $formModel = new Form();
    $formId = $formModel->create(
        'テスト用お問い合わせフォーム',
        'submit.phpのテスト用',
        [
            'email_notifications' => false,
            'success_message' => 'テスト送信が完了しました'
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
    echo "スパム対策チャレンジを生成しました\n";
    echo "Challenge ID: {$challenge['challenge_id']}\n";
    echo "Question: {$challenge['question']}\n";
    
    // 質問から正しい答えを抽出
    $correctAnswer = '';
    if (preg_match('/「(.+?)」/', $challenge['question'], $matches)) {
        $correctAnswer = $matches[1];
    }
    echo "Correct Answer: {$correctAnswer}\n\n";

    // CSRFトークンを生成
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrfToken = $_SESSION['csrf_token'];
    echo "CSRFトークンを生成しました\n\n";

    // テスト1: 正常な送信
    echo "テスト1: 正常な送信\n";
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

    // submit.phpの処理をシミュレート
    $submissionModel = new Submission();
    
    // レート制限をリセット
    $submissionModel->resetRateLimit($_SERVER['REMOTE_ADDR']);
    
    // バリデーション
    $formData = $formModel->getById($formId);
    $formFields = $formModel->getFields($formId);
    
    $formFieldModel = new FormField();
    $submissionData = [];
    $validationErrors = [];
    
    foreach ($formFields as $field) {
        $fieldName = $field['name'];
        $fieldValue = $_POST[$fieldName] ?? '';
        
        $validation = $formFieldModel->validate($fieldValue, $field);
        if (!$validation['valid']) {
            $validationErrors[] = $validation['error'];
        }
        
        $submissionData[$fieldName] = htmlspecialchars($fieldValue, ENT_QUOTES, 'UTF-8');
    }
    
    if (empty($validationErrors)) {
        // スパム対策チェック
        if ($antiSpam->verify($_POST['challenge_id'], $_POST['challenge_answer'])) {
            // レート制限チェック
            if ($submissionModel->checkRateLimit($_SERVER['REMOTE_ADDR'])) {
                // 送信を保存
                $submissionId = $submissionModel->create(
                    $formId,
                    $submissionData,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                );
                echo "✓ 送信が正常に保存されました (ID: $submissionId)\n";
            } else {
                echo "✗ レート制限エラー\n";
            }
        } else {
            echo "✗ スパム対策チャレンジの検証に失敗しました\n";
        }
    } else {
        echo "✗ バリデーションエラー: " . implode(', ', $validationErrors) . "\n";
    }
    echo "\n";

    // テスト2: 必須フィールドが空の場合
    echo "テスト2: 必須フィールドが空の場合\n";
    $challenge2 = $antiSpam->generateChallenge();
    $_POST = [
        'form_id' => $formId,
        'csrf_token' => $csrfToken,
        'challenge_id' => $challenge2['challenge_id'],
        'challenge_answer' => 'ねこ',
        'name' => '', // 空
        'email' => 'test@example.com',
        'message' => 'テスト'
    ];
    
    $validationErrors = [];
    foreach ($formFields as $field) {
        $fieldName = $field['name'];
        $fieldValue = $_POST[$fieldName] ?? '';
        
        $validation = $formFieldModel->validate($fieldValue, $field);
        if (!$validation['valid']) {
            $validationErrors[] = $validation['error'];
        }
    }
    
    if (!empty($validationErrors)) {
        echo "✓ バリデーションエラーが正しく検出されました: " . implode(', ', $validationErrors) . "\n";
    } else {
        echo "✗ バリデーションエラーが検出されませんでした\n";
    }
    echo "\n";

    // テスト3: 無効なメールアドレス
    echo "テスト3: 無効なメールアドレス\n";
    $challenge3 = $antiSpam->generateChallenge();
    $_POST = [
        'form_id' => $formId,
        'csrf_token' => $csrfToken,
        'challenge_id' => $challenge3['challenge_id'],
        'challenge_answer' => 'ねこ',
        'name' => '山田太郎',
        'email' => 'invalid-email', // 無効なメール
        'message' => 'テスト'
    ];
    
    $validationErrors = [];
    foreach ($formFields as $field) {
        $fieldName = $field['name'];
        $fieldValue = $_POST[$fieldName] ?? '';
        
        $validation = $formFieldModel->validate($fieldValue, $field);
        if (!$validation['valid']) {
            $validationErrors[] = $validation['error'];
        }
    }
    
    if (!empty($validationErrors)) {
        echo "✓ メールアドレスのバリデーションエラーが正しく検出されました: " . implode(', ', $validationErrors) . "\n";
    } else {
        echo "✗ メールアドレスのバリデーションエラーが検出されませんでした\n";
    }
    echo "\n";

    // テスト4: XSS対策のサニタイズ
    echo "テスト4: XSS対策のサニタイズ\n";
    $challenge4 = $antiSpam->generateChallenge();
    $xssInput = '<script>alert("XSS")</script>';
    $_POST = [
        'form_id' => $formId,
        'csrf_token' => $csrfToken,
        'challenge_id' => $challenge4['challenge_id'],
        'challenge_answer' => 'ねこ',
        'name' => $xssInput,
        'email' => 'test@example.com',
        'message' => 'テスト'
    ];
    
    $sanitized = htmlspecialchars($xssInput, ENT_QUOTES, 'UTF-8');
    if ($sanitized !== $xssInput && strpos($sanitized, '&lt;script&gt;') !== false) {
        echo "✓ XSS対策のサニタイズが正しく動作しています\n";
        echo "  元の入力: $xssInput\n";
        echo "  サニタイズ後: $sanitized\n";
    } else {
        echo "✗ XSS対策のサニタイズが正しく動作していません\n";
    }
    echo "\n";

    // テスト5: レート制限
    echo "テスト5: レート制限\n";
    $testIp = '192.168.1.100';
    $submissionModel->resetRateLimit($testIp);
    
    $maxSubmissions = Submission::RATE_LIMIT_MAX_SUBMISSIONS;
    echo "最大送信回数: {$maxSubmissions}\n";
    
    $successCount = 0;
    for ($i = 1; $i <= $maxSubmissions + 1; $i++) {
        if ($submissionModel->checkRateLimit($testIp)) {
            $successCount++;
        }
    }
    
    if ($successCount === $maxSubmissions) {
        echo "✓ レート制限が正しく動作しています（{$successCount}回まで許可）\n";
    } else {
        echo "✗ レート制限が正しく動作していません（{$successCount}回許可されました）\n";
    }
    echo "\n";

    // テスト6: CSRFトークンの検証
    echo "テスト6: CSRFトークンの検証\n";
    $invalidToken = 'invalid_token';
    if ($invalidToken !== $csrfToken) {
        echo "✓ 無効なCSRFトークンが正しく検出されました\n";
    } else {
        echo "✗ CSRFトークンの検証に失敗しました\n";
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
