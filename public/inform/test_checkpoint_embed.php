<?php
/**
 * チェックポイント13: 埋め込みフォームの統合テスト
 * 
 * このテストは以下を確認します:
 * 1. iframe埋め込みが正しく動作すること
 * 2. JavaScript埋め込みが正しく動作すること
 * 3. フォーム送信が正しく処理されること
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

$testResults = [];
$testsPassed = 0;
$testsFailed = 0;

function runTest($testName, $callback) {
    global $testResults, $testsPassed, $testsFailed;
    
    try {
        $result = $callback();
        if ($result['success']) {
            $testsPassed++;
            $testResults[] = "✓ {$testName}: {$result['message']}";
        } else {
            $testsFailed++;
            $testResults[] = "✗ {$testName}: {$result['message']}";
        }
    } catch (Exception $e) {
        $testsFailed++;
        $testResults[] = "✗ {$testName}: Exception - {$e->getMessage()}";
    }
}

try {
    echo "=== チェックポイント13: 埋め込みフォームのテスト ===\n\n";

    // 準備: テスト用フォームを作成
    echo "準備: テスト用フォームの作成\n";
    $formModel = new Form();
    $formId = $formModel->create(
        'チェックポイントテスト用フォーム',
        'iframe/JavaScript埋め込みと送信処理のテスト',
        [
            'email_notifications' => false,
            'success_message' => 'お問い合わせありがとうございます。'
        ]
    );
    echo "作成されたフォームID: {$formId}\n";

    // フィールドを追加
    $fields = [
        [
            'type' => 'text',
            'label' => 'お名前',
            'name' => 'name',
            'config' => ['required' => true, 'max_length' => 100, 'placeholder' => 'お名前を入力してください']
        ],
        [
            'type' => 'email',
            'label' => 'メールアドレス',
            'name' => 'email',
            'config' => ['required' => true, 'placeholder' => 'example@example.com']
        ],
        [
            'type' => 'textarea',
            'label' => 'お問い合わせ内容',
            'name' => 'message',
            'config' => ['required' => true, 'max_length' => 1000, 'placeholder' => 'お問い合わせ内容を入力してください']
        ]
    ];
    $formModel->saveFields($formId, $fields);
    echo "フィールドを追加しました\n\n";

    // ========================================
    // テスト1: iframe埋め込みの動作確認
    // ========================================
    echo "テスト1: iframe埋め込みの動作確認\n";
    
    runTest("iframe埋め込み - embed.phpへのアクセス", function() use ($formId) {
        // embed.phpを直接実行してHTMLが生成されることを確認
        $_GET['form_id'] = $formId;
        
        ob_start();
        include __DIR__ . '/embed.php';
        $output = ob_get_clean();
        
        // HTMLが生成されているか確認
        $hasHtml = strpos($output, '<!DOCTYPE html>') !== false;
        $hasForm = strpos($output, '<form') !== false;
        $hasCsrf = strpos($output, 'csrf_token') !== false;
        $hasChallenge = strpos($output, 'challenge_id') !== false;
        $hasTailwind = strpos($output, 'tailwindcss.com') !== false;
        
        if ($hasHtml && $hasForm && $hasCsrf && $hasChallenge && $hasTailwind) {
            return ['success' => true, 'message' => 'embed.phpが正しくHTMLを生成しています'];
        } else {
            $missing = [];
            if (!$hasHtml) $missing[] = 'HTML';
            if (!$hasForm) $missing[] = 'Form';
            if (!$hasCsrf) $missing[] = 'CSRF';
            if (!$hasChallenge) $missing[] = 'Challenge';
            if (!$hasTailwind) $missing[] = 'Tailwind';
            return ['success' => false, 'message' => '不足している要素: ' . implode(', ', $missing)];
        }
    });

    runTest("iframe埋め込み - フォームフィールドのレンダリング", function() use ($formId, $formModel) {
        $_GET['form_id'] = $formId;
        
        ob_start();
        include __DIR__ . '/embed.php';
        $output = ob_get_clean();
        
        // すべてのフィールドがレンダリングされているか確認
        $hasNameField = strpos($output, 'name="name"') !== false;
        $hasEmailField = strpos($output, 'name="email"') !== false;
        $hasMessageField = strpos($output, 'name="message"') !== false;
        
        if ($hasNameField && $hasEmailField && $hasMessageField) {
            return ['success' => true, 'message' => 'すべてのフィールドが正しくレンダリングされています'];
        } else {
            return ['success' => false, 'message' => '一部のフィールドがレンダリングされていません'];
        }
    });

    runTest("iframe埋め込み - スパム対策要素の表示", function() use ($formId) {
        $_GET['form_id'] = $formId;
        
        ob_start();
        include __DIR__ . '/embed.php';
        $output = ob_get_clean();
        
        // スパム対策画像とテキスト入力が含まれているか確認
        $hasCaptchaImage = strpos($output, 'captcha.php') !== false;
        $hasChallengeAnswer = strpos($output, 'name="challenge_answer"') !== false;
        
        if ($hasCaptchaImage && $hasChallengeAnswer) {
            return ['success' => true, 'message' => 'スパム対策要素が正しく表示されています'];
        } else {
            return ['success' => false, 'message' => 'スパム対策要素が不足しています'];
        }
    });

    runTest("iframe埋め込み - CORSヘッダーの設定", function() use ($formId) {
        $_GET['form_id'] = $formId;
        
        ob_start();
        include __DIR__ . '/embed.php';
        ob_get_clean();
        
        // ヘッダーが設定されているか確認（headers_list()で確認）
        $headers = headers_list();
        $hasCorsHeader = false;
        
        foreach ($headers as $header) {
            if (stripos($header, 'Access-Control-Allow-Origin') !== false) {
                $hasCorsHeader = true;
                break;
            }
        }
        
        if ($hasCorsHeader) {
            return ['success' => true, 'message' => 'CORSヘッダーが正しく設定されています'];
        } else {
            return ['success' => false, 'message' => 'CORSヘッダーが設定されていません'];
        }
    });

    runTest("iframe埋め込み - 無効なフォームIDのエラーハンドリング", function() {
        $_GET['form_id'] = 99999; // 存在しないID
        
        ob_start();
        include __DIR__ . '/embed.php';
        $output = ob_get_clean();
        
        $hasError = strpos($output, 'エラー') !== false;
        
        if ($hasError) {
            return ['success' => true, 'message' => '無効なフォームIDに対してエラーが表示されます'];
        } else {
            return ['success' => false, 'message' => 'エラーハンドリングが機能していません'];
        }
    });

    echo "\n";

    // ========================================
    // テスト2: JavaScript埋め込みの動作確認
    // ========================================
    echo "テスト2: JavaScript埋め込みの動作確認\n";

    runTest("JavaScript埋め込み - embed.jsの存在確認", function() {
        $embedJsPath = __DIR__ . '/embed.js';
        
        if (file_exists($embedJsPath)) {
            $content = file_get_contents($embedJsPath);
            $hasInformEmbed = strpos($content, 'InformEmbed') !== false;
            $hasRenderMethod = strpos($content, 'render:') !== false || strpos($content, 'render =') !== false;
            
            if ($hasInformEmbed && $hasRenderMethod) {
                return ['success' => true, 'message' => 'embed.jsが存在し、必要な機能を含んでいます'];
            } else {
                return ['success' => false, 'message' => 'embed.jsに必要な機能が不足しています'];
            }
        } else {
            return ['success' => false, 'message' => 'embed.jsが見つかりません'];
        }
    });

    runTest("JavaScript埋め込み - InformEmbedオブジェクトの構造", function() {
        $embedJsPath = __DIR__ . '/embed.js';
        $content = file_get_contents($embedJsPath);
        
        // 必要なメソッドが定義されているか確認
        $hasRender = strpos($content, 'render') !== false;
        $hasFetchFormHtml = strpos($content, '_fetchFormHtml') !== false || strpos($content, 'fetchFormHtml') !== false;
        $hasSetupFormSubmission = strpos($content, '_setupFormSubmission') !== false || strpos($content, 'setupFormSubmission') !== false;
        $hasSubmitForm = strpos($content, '_submitForm') !== false || strpos($content, 'submitForm') !== false;
        
        if ($hasRender && $hasFetchFormHtml && $hasSetupFormSubmission && $hasSubmitForm) {
            return ['success' => true, 'message' => 'InformEmbedオブジェクトが正しい構造を持っています'];
        } else {
            return ['success' => false, 'message' => 'InformEmbedオブジェクトに必要なメソッドが不足しています'];
        }
    });

    runTest("JavaScript埋め込み - テストHTMLファイルの存在", function() {
        $testHtmlPath = __DIR__ . '/test_embed_js.html';
        
        if (file_exists($testHtmlPath)) {
            $content = file_get_contents($testHtmlPath);
            $hasInformEmbed = strpos($content, 'InformEmbed') !== false;
            $hasRenderCall = strpos($content, 'InformEmbed.render') !== false;
            
            if ($hasInformEmbed && $hasRenderCall) {
                return ['success' => true, 'message' => 'test_embed_js.htmlが存在し、正しく設定されています'];
            } else {
                return ['success' => false, 'message' => 'test_embed_js.htmlの設定が不完全です'];
            }
        } else {
            return ['success' => false, 'message' => 'test_embed_js.htmlが見つかりません'];
        }
    });

    echo "\n";

    // ========================================
    // テスト3: フォーム送信処理の動作確認
    // ========================================
    echo "テスト3: フォーム送信処理の動作確認\n";

    // スパム対策チャレンジを生成
    $antiSpam = new AntiSpam();
    $challenge = $antiSpam->generateChallenge();
    $correctAnswer = '';
    if (preg_match('/「(.+?)」/', $challenge['question'], $matches)) {
        $correctAnswer = $matches[1];
    }

    // CSRFトークンを生成
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrfToken = $_SESSION['csrf_token'];

    runTest("フォーム送信 - 正常な送信", function() use ($formId, $csrfToken, $challenge, $correctAnswer) {
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

        ob_start();
        try {
            include __DIR__ . '/submit.php';
        } catch (Exception $e) {
        }
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && $response['success'] === true) {
            return ['success' => true, 'message' => '正常な送信が成功しました'];
        } else {
            $message = $response ? $response['message'] : 'JSONレスポンスが無効です';
            return ['success' => false, 'message' => "送信に失敗: {$message}"];
        }
    });

    runTest("フォーム送信 - CSRFトークンエラー", function() use ($formId, $antiSpam) {
        $challenge2 = $antiSpam->generateChallenge();
        $correctAnswer2 = '';
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
        $_SERVER['REQUEST_METHOD'] = 'POST';

        ob_start();
        try {
            include __DIR__ . '/submit.php';
        } catch (Exception $e) {
        }
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && $response['success'] === false && strpos($response['message'], 'セッション') !== false) {
            return ['success' => true, 'message' => 'CSRFトークンエラーが正しく検出されました'];
        } else {
            return ['success' => false, 'message' => 'CSRFトークンエラーの検出に失敗しました'];
        }
    });

    runTest("フォーム送信 - 必須フィールドのバリデーション", function() use ($formId, $antiSpam) {
        $challenge3 = $antiSpam->generateChallenge();
        $correctAnswer3 = '';
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
        $_SERVER['REQUEST_METHOD'] = 'POST';

        ob_start();
        try {
            include __DIR__ . '/submit.php';
        } catch (Exception $e) {
        }
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && $response['success'] === false && strpos($response['message'], '必須') !== false) {
            return ['success' => true, 'message' => '必須フィールドのバリデーションが正しく動作しています'];
        } else {
            return ['success' => false, 'message' => '必須フィールドのバリデーションに失敗しました'];
        }
    });

    runTest("フォーム送信 - メールアドレス形式のバリデーション", function() use ($formId, $antiSpam) {
        $challenge4 = $antiSpam->generateChallenge();
        $correctAnswer4 = '';
        if (preg_match('/「(.+?)」/', $challenge4['question'], $matches)) {
            $correctAnswer4 = $matches[1];
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_POST = [
            'form_id' => $formId,
            'csrf_token' => $_SESSION['csrf_token'],
            'challenge_id' => $challenge4['challenge_id'],
            'challenge_answer' => $correctAnswer4,
            'name' => '山田太郎',
            'email' => 'invalid-email', // 無効なメール
            'message' => 'テスト'
        ];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        ob_start();
        try {
            include __DIR__ . '/submit.php';
        } catch (Exception $e) {
        }
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && $response['success'] === false && strpos($response['message'], 'メール') !== false) {
            return ['success' => true, 'message' => 'メールアドレス形式のバリデーションが正しく動作しています'];
        } else {
            return ['success' => false, 'message' => 'メールアドレス形式のバリデーションに失敗しました'];
        }
    });

    runTest("フォーム送信 - スパム対策チャレンジの検証", function() use ($formId, $antiSpam) {
        $challenge5 = $antiSpam->generateChallenge();

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_POST = [
            'form_id' => $formId,
            'csrf_token' => $_SESSION['csrf_token'],
            'challenge_id' => $challenge5['challenge_id'],
            'challenge_answer' => '間違った答え',
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'message' => 'テスト'
        ];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        ob_start();
        try {
            include __DIR__ . '/submit.php';
        } catch (Exception $e) {
        }
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && $response['success'] === false && strpos($response['message'], '確認質問') !== false) {
            return ['success' => true, 'message' => 'スパム対策チャレンジの検証が正しく動作しています'];
        } else {
            return ['success' => false, 'message' => 'スパム対策チャレンジの検証に失敗しました'];
        }
    });

    runTest("フォーム送信 - XSS対策のサニタイズ", function() use ($formId, $antiSpam) {
        $challenge6 = $antiSpam->generateChallenge();
        $correctAnswer6 = '';
        if (preg_match('/「(.+?)」/', $challenge6['question'], $matches)) {
            $correctAnswer6 = $matches[1];
        }

        $xssInput = '<script>alert("XSS")</script>';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_POST = [
            'form_id' => $formId,
            'csrf_token' => $_SESSION['csrf_token'],
            'challenge_id' => $challenge6['challenge_id'],
            'challenge_answer' => $correctAnswer6,
            'name' => $xssInput,
            'email' => 'test@example.com',
            'message' => 'テスト'
        ];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        ob_start();
        try {
            include __DIR__ . '/submit.php';
        } catch (Exception $e) {
        }
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        if ($response && $response['success'] === true) {
            // 送信されたデータを確認
            $submissionModel = new Submission();
            $submissions = $submissionModel->getByForm($formId);
            
            if (!empty($submissions)) {
                $lastSubmission = end($submissions);
                $submissionData = json_decode($lastSubmission['data'], true);
                $sanitizedName = $submissionData['name'];
                
                if (strpos($sanitizedName, '&lt;script&gt;') !== false) {
                    return ['success' => true, 'message' => 'XSS対策のサニタイズが正しく動作しています'];
                } else {
                    return ['success' => false, 'message' => 'XSS対策のサニタイズが不十分です'];
                }
            }
        }
        
        return ['success' => false, 'message' => 'XSS対策のテストに失敗しました'];
    });

    echo "\n";

    // クリーンアップ
    echo "クリーンアップ: テスト用フォームの削除\n";
    $formModel->delete($formId);
    echo "✓ テスト用フォームを削除しました\n\n";

    // テスト結果のサマリー
    echo "=== テスト結果 ===\n\n";
    foreach ($testResults as $result) {
        echo $result . "\n";
    }
    echo "\n";
    echo "合計: " . ($testsPassed + $testsFailed) . " テスト\n";
    echo "成功: {$testsPassed}\n";
    echo "失敗: {$testsFailed}\n\n";

    if ($testsFailed === 0) {
        echo "✓ すべてのテストが成功しました！\n";
        echo "✓ iframe埋め込みが正しく動作しています\n";
        echo "✓ JavaScript埋め込みが正しく動作しています\n";
        echo "✓ フォーム送信が正しく処理されています\n";
    } else {
        echo "✗ {$testsFailed}個のテストが失敗しました\n";
        echo "失敗したテストを確認して修正してください\n";
    }

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
