# エラーハンドリングとロギング機能

## 概要

このドキュメントは、Informシステムのエラーハンドリングとロギング機能について説明します。

## 実装されたコンポーネント

### 1. ErrorHandler クラス (`includes/inform/ErrorHandler.php`)

エラーメッセージの一元管理を提供します。

#### 機能

- **バリデーションエラーメッセージ**: フォーム入力のバリデーションエラー
- **システムエラーメッセージ**: データベースやシステム関連のエラー
- **セキュリティエラーメッセージ**: CSRF、XSS、レート制限などのセキュリティエラー

#### 使用例

```php
// バリデーションエラー
$error = ErrorHandler::getValidationError('required_field', 'お名前');
// 出力: "お名前は必須です"

$error = ErrorHandler::getValidationError('invalid_email');
// 出力: "有効なメールアドレスを入力してください"

$error = ErrorHandler::getValidationError('max_length_exceeded', 'メッセージ', 500);
// 出力: "メッセージは500文字以内で入力してください"

// システムエラー
$error = ErrorHandler::getSystemError('database_error');
// 出力: "システムエラーが発生しました。しばらくしてから再度お試しください"

// セキュリティエラー
$error = ErrorHandler::getSecurityError('csrf_token_invalid');
// 出力: "セッションが無効です。ページを再読み込みしてください"
```

#### 利用可能なエラーキー

**バリデーションエラー:**

- `required_field` - 必須フィールドエラー
- `invalid_email` - メールアドレス形式エラー
- `max_length_exceeded` - 最大長超過エラー
- `invalid_format` - 形式エラー
- `invalid_option` - 無効な選択肢エラー
- `spam_challenge_failed` - スパム対策失敗
- `spam_challenge_missing` - スパム対策未入力

**システムエラー:**

- `database_error` - データベースエラー
- `form_not_found` - フォーム未検出
- `submission_not_found` - 送信未検出
- `fields_not_configured` - フィールド未設定
- `json_conversion_failed` - JSON変換失敗
- `transaction_failed` - トランザクション失敗
- `email_send_failed` - メール送信失敗
- `email_config_missing` - メール設定未設定

**セキュリティエラー:**

- `csrf_token_invalid` - CSRFトークン無効
- `csrf_token_missing` - CSRFトークン未設定
- `rate_limit_exceeded` - レート制限超過
- `invalid_request_method` - 無効なリクエストメソッド
- `invalid_form_id` - 無効なフォームID
- `unauthorized_access` - 未認証アクセス
- `sql_injection_attempt` - SQLインジェクション試行
- `xss_attempt` - XSS攻撃試行

### 2. Logger クラス (`includes/inform/Logger.php`)

ロギング機能を提供します。

#### 機能

- **データベースエラーログ**: データベース操作のエラーを記録
- **メール送信ログ**: メール送信の成功/失敗を記録
- **セキュリティログ**: セキュリティ関連のイベントを記録
- **一般エラーログ**: その他のエラーを記録
- **情報ログ**: 一般的な情報を記録

#### ログレベル

- `ERROR` - エラー
- `WARNING` - 警告
- `INFO` - 情報
- `DEBUG` - デバッグ
- `SECURITY` - セキュリティ

#### 使用例

```php
// データベースエラーログ
Logger::logDatabaseError('Form::create', 'Failed to create form', $exception);

// メール送信エラーログ
Logger::logEmailError('EmailNotifier::sendNotification', 'admin@example.com', 'Mail function failed');

// メール送信成功ログ
Logger::logEmailSuccess('EmailNotifier::sendNotification', 'admin@example.com', 1);

// セキュリティエラーログ
Logger::logSecurityError('CSRF', 'Invalid CSRF token detected', [
    'ip_address' => '192.168.1.100',
    'user_agent' => 'Mozilla/5.0...',
    'additional' => ['form_id' => 123]
]);

// 一般エラーログ
Logger::logError('MyClass::myMethod', 'Something went wrong');

// 情報ログ
Logger::logInfo('MyClass::myMethod', 'Operation completed successfully');
```

#### ログファイル

- **場所**: `logs/inform.log`
- **形式**: `[日時] [レベル] メッセージ`
- **例**: `[2026-01-30 14:27:22] [ERROR] Database Error in Form::create: Failed to create form`

#### ユーティリティメソッド

```php
// ログファイルをクリア（テスト用）
Logger::clearLog();

// 最新のログエントリを取得（デバッグ用）
$logs = Logger::getRecentLogs(100); // 最新100行を取得
```

## 統合

### 既存クラスへの統合

以下のクラスがErrorHandlerとLoggerを使用するように更新されました:

1. **Form.php** - データベースエラーをLoggerで記録
2. **Submission.php** - データベースエラーをLoggerで記録
3. **EmailNotifier.php** - メール送信の成功/失敗をLoggerで記録
4. **submit.php** - ErrorHandlerとLoggerを使用してエラーを処理

### submit.phpでの使用例

```php
// CSRFトークンエラー
if (empty($csrfToken) || $csrfToken !== $_SESSION['csrf_token']) {
    Logger::logSecurityError('CSRF', 'Invalid or missing CSRF token');
    sendJsonResponse(false, ErrorHandler::getSecurityError('csrf_token_invalid'));
}

// レート制限エラー
if (!$submissionModel->checkRateLimit($ipAddress)) {
    Logger::logSecurityError('RATE_LIMIT', 'Rate limit exceeded', [
        'ip_address' => $ipAddress,
        'form_id' => $formId
    ]);
    sendJsonResponse(false, ErrorHandler::getSecurityError('rate_limit_exceeded'));
}

// 送信成功
Logger::logInfo('submit.php', "Submission created successfully: ID {$submissionId}, Form {$formId}");
```

## テスト

テストファイル: `includes/inform/test_error_logging.php`

### テストの実行

```bash
php includes/inform/test_error_logging.php
```

### テスト内容

1. バリデーションエラーメッセージの取得
2. システムエラーメッセージの取得
3. セキュリティエラーメッセージの取得
4. ロギング機能（データベース、メール、セキュリティ、一般、情報）
5. ログファイルの内容確認
6. 存在しないキーのデフォルトメッセージ

## 要件との対応

### 要件 5.4: バリデーションエラーメッセージ

ErrorHandlerクラスがすべてのバリデーションエラーメッセージを一元管理し、一貫性のあるエラーメッセージを提供します。

### 要件 8.5: メール送信エラーのロギング

Loggerクラスがメール送信の成功/失敗を記録し、メール送信失敗時もSubmissionは成功として処理されます。

## ベストプラクティス

1. **エラーメッセージの一貫性**: ErrorHandlerを使用してすべてのエラーメッセージを管理
2. **詳細なロギング**: すべてのエラーと重要なイベントをログに記録
3. **セキュリティログ**: セキュリティ関連のイベントは必ずログに記録
4. **ユーザーフレンドリーなメッセージ**: ユーザーには一般的なエラーメッセージを表示し、詳細はログに記録
5. **コンテキスト情報**: ログにはIPアドレス、ユーザーエージェント、リクエストURIなどのコンテキスト情報を含める

## 今後の拡張

- ログローテーション機能の追加
- ログレベルによるフィルタリング
- 管理画面でのログ閲覧機能
- メール通知（重大なエラー発生時）
- 外部ログサービスとの統合（Sentry、Logglyなど）
