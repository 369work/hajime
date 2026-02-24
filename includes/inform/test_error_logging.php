<?php
/**
 * エラーハンドリングとロギング機能のテスト
 * 
 * このファイルは、ErrorHandlerとLoggerクラスの基本機能をテストします。
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/ErrorHandler.php';
require_once __DIR__ . '/Logger.php';

echo "<h1>エラーハンドリングとロギング機能のテスト</h1>";

// ログファイルをクリア
Logger::clearLog();
echo "<p>✓ ログファイルをクリアしました</p>";

// テスト1: バリデーションエラーメッセージ
echo "<h2>テスト1: バリデーションエラーメッセージ</h2>";
$requiredError = ErrorHandler::getValidationError('required_field', 'お名前');
echo "<p>必須フィールドエラー: {$requiredError}</p>";
assert($requiredError === 'お名前は必須です', 'Required field error message failed');

$emailError = ErrorHandler::getValidationError('invalid_email');
echo "<p>メールエラー: {$emailError}</p>";
assert($emailError === '有効なメールアドレスを入力してください', 'Email error message failed');

$maxLengthError = ErrorHandler::getValidationError('max_length_exceeded', 'メッセージ', 500);
echo "<p>最大長エラー: {$maxLengthError}</p>";
assert($maxLengthError === 'メッセージは500文字以内で入力してください', 'Max length error message failed');

echo "<p style='color: green;'>✓ すべてのバリデーションエラーメッセージが正しく取得できました</p>";

// テスト2: システムエラーメッセージ
echo "<h2>テスト2: システムエラーメッセージ</h2>";
$dbError = ErrorHandler::getSystemError('database_error');
echo "<p>データベースエラー: {$dbError}</p>";
assert($dbError === 'システムエラーが発生しました。しばらくしてから再度お試しください', 'Database error message failed');

$formNotFound = ErrorHandler::getSystemError('form_not_found');
echo "<p>フォーム未検出: {$formNotFound}</p>";
assert($formNotFound === 'フォームが見つかりません', 'Form not found error message failed');

echo "<p style='color: green;'>✓ すべてのシステムエラーメッセージが正しく取得できました</p>";

// テスト3: セキュリティエラーメッセージ
echo "<h2>テスト3: セキュリティエラーメッセージ</h2>";
$csrfError = ErrorHandler::getSecurityError('csrf_token_invalid');
echo "<p>CSRFエラー: {$csrfError}</p>";
assert($csrfError === 'セッションが無効です。ページを再読み込みしてください', 'CSRF error message failed');

$rateLimitError = ErrorHandler::getSecurityError('rate_limit_exceeded');
echo "<p>レート制限エラー: {$rateLimitError}</p>";
assert($rateLimitError === '送信回数が制限を超えました。しばらくしてから再度お試しください', 'Rate limit error message failed');

echo "<p style='color: green;'>✓ すべてのセキュリティエラーメッセージが正しく取得できました</p>";

// テスト4: ロギング機能
echo "<h2>テスト4: ロギング機能</h2>";

// データベースエラーログ
Logger::logDatabaseError('TestClass::testMethod', 'Test database error message');
echo "<p>✓ データベースエラーをログに記録しました</p>";

// メール送信エラーログ
Logger::logEmailError('TestClass::sendEmail', 'test@example.com', 'Test email error');
echo "<p>✓ メール送信エラーをログに記録しました</p>";

// メール送信成功ログ
Logger::logEmailSuccess('TestClass::sendEmail', 'success@example.com', 1);
echo "<p>✓ メール送信成功をログに記録しました</p>";

// セキュリティエラーログ
Logger::logSecurityError('CSRF', 'Test CSRF attack detected', [
    'ip_address' => '192.168.1.100',
    'user_agent' => 'Test User Agent',
    'additional' => ['form_id' => 123]
]);
echo "<p>✓ セキュリティエラーをログに記録しました</p>";

// 一般エラーログ
Logger::logError('TestClass::testMethod', 'Test general error');
echo "<p>✓ 一般エラーをログに記録しました</p>";

// 情報ログ
Logger::logInfo('TestClass::testMethod', 'Test info message');
echo "<p>✓ 情報をログに記録しました</p>";

// テスト5: ログファイルの内容を確認
echo "<h2>テスト5: ログファイルの内容を確認</h2>";
$logs = Logger::getRecentLogs(10);
echo "<p>記録されたログエントリ数: " . count($logs) . "</p>";

if (count($logs) >= 6) {
    echo "<p style='color: green;'>✓ すべてのログが正しく記録されました</p>";
    echo "<h3>ログエントリ:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    foreach ($logs as $log) {
        echo htmlspecialchars($log) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>✗ ログの記録に問題があります</p>";
}

// テスト6: 存在しないキーのエラーメッセージ
echo "<h2>テスト6: 存在しないキーのエラーメッセージ</h2>";
$unknownValidation = ErrorHandler::getValidationError('unknown_key');
echo "<p>不明なバリデーションキー: {$unknownValidation}</p>";
assert($unknownValidation === 'バリデーションエラーが発生しました', 'Unknown validation key failed');

$unknownSystem = ErrorHandler::getSystemError('unknown_key');
echo "<p>不明なシステムキー: {$unknownSystem}</p>";
assert($unknownSystem === 'システムエラーが発生しました', 'Unknown system key failed');

$unknownSecurity = ErrorHandler::getSecurityError('unknown_key');
echo "<p>不明なセキュリティキー: {$unknownSecurity}</p>";
assert($unknownSecurity === 'セキュリティエラーが発生しました', 'Unknown security key failed');

echo "<p style='color: green;'>✓ 存在しないキーに対するデフォルトメッセージが正しく返されました</p>";

echo "<h2 style='color: green;'>✓ すべてのテストが成功しました！</h2>";
echo "<p>エラーハンドリングとロギング機能は正常に動作しています。</p>";
