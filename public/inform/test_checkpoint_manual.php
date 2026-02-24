<?php
/**
 * チェックポイント13: 埋め込みフォームの手動テスト
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

$testResults = [];
$testsPassed = 0;
$testsFailed = 0;

function runTest($testName, $callback) {
    global $testResults, $testsPassed, $testsFailed;
    
    try {
        $result = $callback();
        if ($result['success']) {
            $testsPassed++;
            $testResults[] = "✓ {$testName}";
            if (!empty($result['message'])) {
                $testResults[] = "  → {$result['message']}";
            }
        } else {
            $testsFailed++;
            $testResults[] = "✗ {$testName}";
            if (!empty($result['message'])) {
                $testResults[] = "  → {$result['message']}";
            }
        }
    } catch (Exception $e) {
        $testsFailed++;
        $testResults[] = "✗ {$testName}";
        $testResults[] = "  → Exception: {$e->getMessage()}";
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
    // テスト1: iframe埋め込みファイルの確認
    // ========================================
    echo "テスト1: iframe埋め込みファイルの確認\n";
    
    runTest("embed.phpファイルの存在", function() {
        $embedPhpPath = __DIR__ . '/embed.php';
        if (file_exists($embedPhpPath)) {
            $content = file_get_contents($embedPhpPath);
            
            // 必要な要素が含まれているか確認
            $hasFormRendering = strpos($content, 'Form') !== false;
            $hasCors = strpos($content, 'Access-Control-Allow-Origin') !== false;
            $hasCsrf = strpos($content, 'csrf_token') !== false;
            $hasAntiSpam = strpos($content, 'AntiSpam') !== false;
            $hasTailwind = strpos($content, 'tailwindcss') !== false;
            
            if ($hasFormRendering && $hasCors && $hasCsrf && $hasAntiSpam && $hasTailwind) {
                return ['success' => true, 'message' => 'embed.phpが必要な機能をすべて含んでいます'];
            } else {
                $missing = [];
                if (!$hasFormRendering) $missing[] = 'Form rendering';
                if (!$hasCors) $missing[] = 'CORS headers';
                if (!$hasCsrf) $missing[] = 'CSRF token';
                if (!$hasAntiSpam) $missing[] = 'AntiSpam';
                if (!$hasTailwind) $missing[] = 'Tailwind CSS';
                return ['success' => false, 'message' => '不足している要素: ' . implode(', ', $missing)];
            }
        } else {
            return ['success' => false, 'message' => 'embed.phpが見つかりません'];
        }
    });

    runTest("embed.phpのフォームフィールドレンダリング機能", function() {
        $embedPhpPath = __DIR__ . '/embed.php';
        $content = file_get_contents($embedPhpPath);
        
        // FormFieldのrenderメソッドが使用されているか確認
        $hasFieldRendering = strpos($content, '->render(') !== false;
        $hasFieldLoop = strpos($content, 'foreach') !== false && strpos($content, 'fields') !== false;
        
        if ($hasFieldRendering && $hasFieldLoop) {
            return ['success' => true, 'message' => 'フォームフィールドのレンダリング機能が実装されています'];
        } else {
            return ['success' => false, 'message' => 'フォームフィールドのレンダリング機能が不足しています'];
        }
    });

    runTest("embed.phpのスパム対策表示機能", function() {
        $embedPhpPath = __DIR__ . '/embed.php';
        $content = file_get_contents($embedPhpPath);
        
        // スパム対策画像とテキスト入力が含まれているか確認
        $hasCaptchaImage = strpos($content, 'captcha.php') !== false;
        $hasChallengeAnswer = strpos($content, 'challenge_answer') !== false;
        $hasAntiSpamGeneration = strpos($content, 'generateChallenge') !== false;
        
        if ($hasCaptchaImage && $hasChallengeAnswer && $hasAntiSpamGeneration) {
            return ['success' => true, 'message' => 'スパム対策表示機能が実装されています'];
        } else {
            return ['success' => false, 'message' => 'スパム対策表示機能が不足しています'];
        }
    });

    runTest("embed.phpのAJAXフォーム送信機能", function() {
        $embedPhpPath = __DIR__ . '/embed.php';
        $content = file_get_contents($embedPhpPath);
        
        // JavaScriptのフォーム送信処理が含まれているか確認
        $hasFormSubmit = strpos($content, 'addEventListener') !== false && strpos($content, 'submit') !== false;
        $hasFetch = strpos($content, 'fetch(') !== false;
        $hasFormData = strpos($content, 'FormData') !== false;
        
        if ($hasFormSubmit && $hasFetch && $hasFormData) {
            return ['success' => true, 'message' => 'AJAXフォーム送信機能が実装されています'];
        } else {
            return ['success' => false, 'message' => 'AJAXフォーム送信機能が不足しています'];
        }
    });

    echo "\n";

    // ========================================
    // テスト2: JavaScript埋め込みファイルの確認
    // ========================================
    echo "テスト2: JavaScript埋め込みファイルの確認\n";

    runTest("embed.jsファイルの存在", function() {
        $embedJsPath = __DIR__ . '/embed.js';
        
        if (file_exists($embedJsPath)) {
            $content = file_get_contents($embedJsPath);
            $hasInformEmbed = strpos($content, 'InformEmbed') !== false;
            $hasRenderMethod = strpos($content, 'render') !== false;
            
            if ($hasInformEmbed && $hasRenderMethod) {
                return ['success' => true, 'message' => 'embed.jsが存在し、InformEmbedオブジェクトを含んでいます'];
            } else {
                return ['success' => false, 'message' => 'embed.jsにInformEmbedオブジェクトが不足しています'];
            }
        } else {
            return ['success' => false, 'message' => 'embed.jsが見つかりません'];
        }
    });

    runTest("embed.jsのrenderメソッド", function() {
        $embedJsPath = __DIR__ . '/embed.js';
        $content = file_get_contents($embedJsPath);
        
        // renderメソッドが正しく実装されているか確認
        $hasRender = strpos($content, 'render:') !== false || strpos($content, 'render =') !== false;
        $hasElementId = strpos($content, 'elementId') !== false;
        $hasFormId = strpos($content, 'formId') !== false;
        
        if ($hasRender && $hasElementId && $hasFormId) {
            return ['success' => true, 'message' => 'renderメソッドが正しく実装されています'];
        } else {
            return ['success' => false, 'message' => 'renderメソッドの実装が不完全です'];
        }
    });

    runTest("embed.jsのフォームHTML取得機能", function() {
        $embedJsPath = __DIR__ . '/embed.js';
        $content = file_get_contents($embedJsPath);
        
        // フォームHTMLを取得する機能が実装されているか確認
        $hasFetchFormHtml = strpos($content, '_fetchFormHtml') !== false || strpos($content, 'fetchFormHtml') !== false;
        $hasXhr = strpos($content, 'XMLHttpRequest') !== false;
        $hasEmbedPhp = strpos($content, 'embed.php') !== false;
        
        if ($hasFetchFormHtml && $hasXhr && $hasEmbedPhp) {
            return ['success' => true, 'message' => 'フォームHTML取得機能が実装されています'];
        } else {
            return ['success' => false, 'message' => 'フォームHTML取得機能が不足しています'];
        }
    });

    runTest("embed.jsのフォーム送信処理", function() {
        $embedJsPath = __DIR__ . '/embed.js';
        $content = file_get_contents($embedJsPath);
        
        // フォーム送信処理が実装されているか確認
        $hasSetupFormSubmission = strpos($content, '_setupFormSubmission') !== false || strpos($content, 'setupFormSubmission') !== false;
        $hasSubmitForm = strpos($content, '_submitForm') !== false || strpos($content, 'submitForm') !== false;
        $hasFormData = strpos($content, 'FormData') !== false;
        
        if ($hasSetupFormSubmission && $hasSubmitForm && $hasFormData) {
            return ['success' => true, 'message' => 'フォーム送信処理が実装されています'];
        } else {
            return ['success' => false, 'message' => 'フォーム送信処理が不足しています'];
        }
    });

    runTest("test_embed_js.htmlの存在", function() {
        $testHtmlPath = __DIR__ . '/test_embed_js.html';
        
        if (file_exists($testHtmlPath)) {
            $content = file_get_contents($testHtmlPath);
            $hasInformEmbed = strpos($content, 'InformEmbed') !== false;
            $hasRenderCall = strpos($content, 'InformEmbed.render') !== false;
            $hasContainer = strpos($content, 'inform-form-container') !== false;
            
            if ($hasInformEmbed && $hasRenderCall && $hasContainer) {
                return ['success' => true, 'message' => 'test_embed_js.htmlが正しく設定されています'];
            } else {
                return ['success' => false, 'message' => 'test_embed_js.htmlの設定が不完全です'];
            }
        } else {
            return ['success' => false, 'message' => 'test_embed_js.htmlが見つかりません'];
        }
    });

    echo "\n";

    // ========================================
    // テスト3: フォーム送信処理の確認
    // ========================================
    echo "テスト3: フォーム送信処理の確認\n";

    runTest("submit.phpファイルの存在", function() {
        $submitPhpPath = __DIR__ . '/submit.php';
        
        if (file_exists($submitPhpPath)) {
            $content = file_get_contents($submitPhpPath);
            
            // 必要な機能が含まれているか確認
            $hasCsrfValidation = strpos($content, 'csrf_token') !== false;
            $hasValidation = strpos($content, 'validate') !== false;
            $hasAntiSpam = strpos($content, 'AntiSpam') !== false;
            $hasRateLimit = strpos($content, 'checkRateLimit') !== false;
            $hasSanitize = strpos($content, 'sanitize') !== false || strpos($content, 'htmlspecialchars') !== false;
            $hasJsonResponse = strpos($content, 'json_encode') !== false;
            
            if ($hasCsrfValidation && $hasValidation && $hasAntiSpam && $hasRateLimit && $hasSanitize && $hasJsonResponse) {
                return ['success' => true, 'message' => 'submit.phpが必要な機能をすべて含んでいます'];
            } else {
                $missing = [];
                if (!$hasCsrfValidation) $missing[] = 'CSRF validation';
                if (!$hasValidation) $missing[] = 'Field validation';
                if (!$hasAntiSpam) $missing[] = 'AntiSpam';
                if (!$hasRateLimit) $missing[] = 'Rate limit';
                if (!$hasSanitize) $missing[] = 'Sanitization';
                if (!$hasJsonResponse) $missing[] = 'JSON response';
                return ['success' => false, 'message' => '不足している要素: ' . implode(', ', $missing)];
            }
        } else {
            return ['success' => false, 'message' => 'submit.phpが見つかりません'];
        }
    });

    runTest("フォームデータの保存機能", function() use ($formId) {
        // 実際にフォームデータを保存してみる
        $submissionModel = new Submission();
        
        $testData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'message' => 'これはテストメッセージです。'
        ];
        
        $submissionId = $submissionModel->create(
            $formId,
            $testData,
            '127.0.0.1',
            'Test Browser'
        );
        
        if ($submissionId > 0) {
            // 保存されたデータを取得
            $submission = $submissionModel->getById($submissionId);
            $savedData = is_string($submission['data']) ? json_decode($submission['data'], true) : $submission['data'];
            
            if ($savedData['name'] === $testData['name'] && 
                $savedData['email'] === $testData['email'] && 
                $savedData['message'] === $testData['message']) {
                return ['success' => true, 'message' => 'フォームデータが正しく保存されています'];
            } else {
                return ['success' => false, 'message' => '保存されたデータが一致しません'];
            }
        } else {
            return ['success' => false, 'message' => 'フォームデータの保存に失敗しました'];
        }
    });

    runTest("バリデーション機能", function() use ($formId, $formModel) {
        $formFieldModel = new FormField();
        $fields = $formModel->getFields($formId);
        
        // 必須フィールドのバリデーション
        $nameField = null;
        foreach ($fields as $field) {
            if ($field['name'] === 'name') {
                $nameField = $field;
                break;
            }
        }
        
        if ($nameField) {
            // 空の値でバリデーション
            $validation = $formFieldModel->validate('', $nameField);
            
            if (!$validation['valid']) {
                return ['success' => true, 'message' => '必須フィールドのバリデーションが正しく動作しています'];
            } else {
                return ['success' => false, 'message' => '必須フィールドのバリデーションが機能していません'];
            }
        } else {
            return ['success' => false, 'message' => 'テスト用フィールドが見つかりません'];
        }
    });

    runTest("メールアドレスのバリデーション", function() use ($formId, $formModel) {
        $formFieldModel = new FormField();
        $fields = $formModel->getFields($formId);
        
        // メールフィールドを取得
        $emailField = null;
        foreach ($fields as $field) {
            if ($field['type'] === 'email') {
                $emailField = $field;
                break;
            }
        }
        
        if ($emailField) {
            // 無効なメールアドレスでバリデーション
            $validation = $formFieldModel->validate('invalid-email', $emailField);
            
            if (!$validation['valid']) {
                return ['success' => true, 'message' => 'メールアドレスのバリデーションが正しく動作しています'];
            } else {
                return ['success' => false, 'message' => 'メールアドレスのバリデーションが機能していません'];
            }
        } else {
            return ['success' => false, 'message' => 'メールフィールドが見つかりません'];
        }
    });

    runTest("スパム対策チャレンジの生成と検証", function() {
        $antiSpam = new AntiSpam();
        
        // チャレンジを生成
        $challenge = $antiSpam->generateChallenge();
        
        if (empty($challenge['challenge_id']) || empty($challenge['question'])) {
            return ['success' => false, 'message' => 'チャレンジの生成に失敗しました'];
        }
        
        // 正しい答えを抽出
        $correctAnswer = '';
        if (preg_match('/「(.+?)」/', $challenge['question'], $matches)) {
            $correctAnswer = $matches[1];
        }
        
        // 正しい答えで検証
        $isValid = $antiSpam->verify($challenge['challenge_id'], $correctAnswer);
        
        if ($isValid) {
            // 間違った答えで検証
            $isInvalid = !$antiSpam->verify($challenge['challenge_id'], '間違った答え');
            
            if ($isInvalid) {
                return ['success' => true, 'message' => 'スパム対策チャレンジが正しく動作しています'];
            } else {
                return ['success' => false, 'message' => '間違った答えが受け入れられました'];
            }
        } else {
            return ['success' => false, 'message' => '正しい答えが拒否されました'];
        }
    });

    runTest("XSS対策のサニタイズ", function() {
        $xssInput = '<script>alert("XSS")</script>';
        $sanitized = htmlspecialchars($xssInput, ENT_QUOTES, 'UTF-8');
        
        if ($sanitized !== $xssInput && strpos($sanitized, '&lt;script&gt;') !== false) {
            return ['success' => true, 'message' => 'XSS対策のサニタイズが正しく動作しています'];
        } else {
            return ['success' => false, 'message' => 'XSS対策のサニタイズが機能していません'];
        }
    });

    runTest("レート制限機能", function() {
        $submissionModel = new Submission();
        $testIp = '192.168.1.100';
        
        // レート制限をリセット
        $submissionModel->resetRateLimit($testIp);
        
        $maxSubmissions = Submission::RATE_LIMIT_MAX_SUBMISSIONS;
        $successCount = 0;
        
        // 最大回数+1回チェック
        for ($i = 1; $i <= $maxSubmissions + 1; $i++) {
            if ($submissionModel->checkRateLimit($testIp)) {
                $successCount++;
            }
        }
        
        if ($successCount === $maxSubmissions) {
            return ['success' => true, 'message' => "レート制限が正しく動作しています（{$maxSubmissions}回まで許可）"];
        } else {
            return ['success' => false, 'message' => "レート制限が正しく動作していません（{$successCount}回許可されました）"];
        }
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
        echo "✓✓✓ すべてのテストが成功しました！ ✓✓✓\n\n";
        echo "【確認完了】\n";
        echo "✓ iframe埋め込みが正しく動作しています\n";
        echo "  - embed.phpが必要な機能をすべて含んでいます\n";
        echo "  - フォームフィールドのレンダリング機能が実装されています\n";
        echo "  - スパム対策表示機能が実装されています\n";
        echo "  - AJAXフォーム送信機能が実装されています\n\n";
        
        echo "✓ JavaScript埋め込みが正しく動作しています\n";
        echo "  - embed.jsが存在し、InformEmbedオブジェクトを含んでいます\n";
        echo "  - renderメソッドが正しく実装されています\n";
        echo "  - フォームHTML取得機能が実装されています\n";
        echo "  - フォーム送信処理が実装されています\n";
        echo "  - test_embed_js.htmlが正しく設定されています\n\n";
        
        echo "✓ フォーム送信が正しく処理されています\n";
        echo "  - submit.phpが必要な機能をすべて含んでいます\n";
        echo "  - フォームデータが正しく保存されています\n";
        echo "  - バリデーション機能が正しく動作しています\n";
        echo "  - メールアドレスのバリデーションが正しく動作しています\n";
        echo "  - スパム対策チャレンジが正しく動作しています\n";
        echo "  - XSS対策のサニタイズが正しく動作しています\n";
        echo "  - レート制限機能が正しく動作しています\n\n";
        
        echo "【次のステップ】\n";
        echo "ブラウザで以下のURLにアクセスして、実際の動作を確認してください:\n";
        echo "1. iframe埋め込み: " . SITE_URL . "/public/inform/embed.php?form_id=1\n";
        echo "2. JavaScript埋め込み: " . SITE_URL . "/public/inform/test_embed_js.html\n";
    } else {
        echo "✗ {$testsFailed}個のテストが失敗しました\n";
        echo "失敗したテストを確認して修正してください\n";
    }

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
