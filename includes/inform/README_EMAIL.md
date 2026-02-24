# メール通知機能

## 概要

Informシステムのメール通知機能は、フォーム送信時に管理者に自動的にメール通知を送信します。

## 実装されたコンポーネント

### 1. EmailNotifierクラス (`includes/inform/EmailNotifier.php`)

メール通知の送信を管理するクラスです。

**主要メソッド:**

- `sendNotification($formId, $submissionId)`: 指定されたフォーム送信に対してメール通知を送信
- `buildEmailBody($form, $submission)`: メール本文を生成（プライベートメソッド）

**機能:**

- フォーム設定に基づいてメール通知の有効/無効を判断
- 複数の通知先メールアドレスをサポート
- メール送信失敗時もエラーをログに記録し、例外をスローしない（要件 8.5）
- メール本文にフォーム名、送信日時、送信内容を含める（要件 8.2）

### 2. submit.phpへの統合 (`public/inform/submit.php`)

フォーム送信処理の成功後、自動的にメール通知を送信します。

**統合ポイント:**

```php
// 送信をデータベースに保存
$submissionId = $submissionModel->create(...);

// メール通知を送信（要件 8.1）
$emailNotifier = new EmailNotifier();
$emailNotifier->sendNotification($formId, $submissionId);

// 成功レスポンスを返す
```

## フォーム設定

メール通知を有効にするには、フォーム作成時に以下の設定を行います：

```php
$settings = [
    'email_notifications' => true,  // メール通知を有効化
    'notification_emails' => [      // 通知先メールアドレス（配列）
        'admin@example.com',
        'manager@example.com'
    ],
    'success_message' => 'お問い合わせありがとうございます。'
];
```

## メール本文の形式

送信されるメールには以下の情報が含まれます：

```
新しいお問い合わせが届きました。

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
フォーム名: お問い合わせフォーム
送信日時: 2026-01-30 14:00:00
送信ID: 123
IPアドレス: 192.168.1.100
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

【送信内容】

name:
山田太郎

email:
yamada@example.com

message:
お問い合わせ内容...

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

管理画面で詳細を確認:
http://localhost/hajime/admin/inform/message-detail.php?id=123
```

## エラーハンドリング

### メール送信失敗時の動作

- メール送信が失敗しても、フォーム送信自体は成功として処理されます（要件 8.5）
- エラーはエラーログに記録されます
- ユーザーには成功メッセージが表示されます

### ログ出力

メール送信の試行と結果はすべてエラーログに記録されます：

```
EmailNotifier::sendNotification: メール送信成功 (宛先: admin@example.com, Form ID: 1)
EmailNotifier::sendNotification エラー: メール送信失敗 (宛先: test@example.com, Form ID: 1)
```

## ローカル環境での注意事項

ローカル開発環境では、PHPの`mail()`関数が正しく動作しない場合があります。

**対処方法:**

1. **XAMPPの場合**: `php.ini`でSMTPサーバーを設定
2. **テスト用**: MailHogやMailtrapなどのメールテストツールを使用
3. **本番環境**: 適切なSMTPサーバーを設定

## テスト

以下のテストファイルで機能を検証できます：

- `includes/inform/test_emailnotifier.php`: EmailNotifierクラスの基本テスト
- `includes/inform/test_email_integration.php`: メール通知統合の総合テスト

**テスト実行:**

```bash
php includes/inform/test_emailnotifier.php
php includes/inform/test_email_integration.php
```

## 要件の充足

このメール通知機能は以下の要件を満たしています：

- **要件 8.1**: メール通知が有効な場合、送信時にメールを送信
- **要件 8.2**: メール本文にフォーム名と送信の概要を含める
- **要件 8.5**: メール送信失敗時も送信自体は成功として処理

## 今後の拡張

以下の機能は将来的に追加可能です：

- PHPMailerを使用したSMTP認証対応
- メールテンプレートのカスタマイズ
- 添付ファイルのサポート
- HTML形式のメール本文
- 送信者への自動返信メール
