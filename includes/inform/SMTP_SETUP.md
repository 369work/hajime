# Inform SMTP設定ガイド

## 概要

Informシステムでは、メール通知を送信する際に2つの方法を選択できます：

1. **mail()関数** - PHPの標準mail()関数を使用（デフォルト）
2. **SMTP** - SMTPサーバー経由で送信（推奨）

## SMTP設定方法

### 1. 設定ファイルを編集

`includes/config.php` を開いて、以下の設定を行います：

```php
// Inform メール設定
define('INFORM_EMAIL_METHOD', 'smtp'); // 'smtp' に変更

// SMTP設定
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'お問い合わせフォーム');
```

### 2. 主要なメールサービスの設定例

#### Gmail

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');  // アプリパスワードを使用
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'お問い合わせフォーム');
```

**注意:** Gmailを使用する場合は、アプリパスワードを生成する必要があります：

1. Googleアカウントにログイン
2. セキュリティ設定を開く
3. 2段階認証を有効化
4. アプリパスワードを生成
5. 生成されたパスワードを使用

#### Outlook / Hotmail

```php
define('SMTP_HOST', 'smtp-mail.outlook.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'your-email@outlook.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'your-email@outlook.com');
define('SMTP_FROM_NAME', 'お問い合わせフォーム');
```

#### Yahoo Mail

```php
define('SMTP_HOST', 'smtp.mail.yahoo.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'your-email@yahoo.com');
define('SMTP_PASSWORD', 'your-app-password');  // アプリパスワードを使用
define('SMTP_FROM_EMAIL', 'your-email@yahoo.com');
define('SMTP_FROM_NAME', 'お問い合わせフォーム');
```

#### さくらインターネット

```php
define('SMTP_HOST', 'your-domain.sakura.ne.jp');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'your-email@your-domain.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'your-email@your-domain.com');
define('SMTP_FROM_NAME', 'お問い合わせフォーム');
```

#### エックスサーバー

```php
define('SMTP_HOST', 'your-domain.xsrv.jp');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl');
define('SMTP_USERNAME', 'your-email@your-domain.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'your-email@your-domain.com');
define('SMTP_FROM_NAME', 'お問い合わせフォーム');
```

### 3. ポート番号と暗号化方式

| ポート | 暗号化 | 説明               |
| ------ | ------ | ------------------ |
| 25     | なし   | 非暗号化（非推奨） |
| 587    | TLS    | STARTTLS（推奨）   |
| 465    | SSL    | SSL/TLS            |

### 4. テスト送信

設定が完了したら、テストフォームから送信してメールが届くか確認してください。

エラーが発生した場合は、`logs/inform.log` を確認してください。

## トラブルシューティング

### メールが送信されない

1. **SMTP設定を確認**
   - ホスト名、ポート、ユーザー名、パスワードが正しいか確認
   - 暗号化方式が正しいか確認

2. **ログを確認**

   ```bash
   tail -f logs/inform.log
   ```

3. **ファイアウォールを確認**
   - SMTPポート（587または465）が開いているか確認

4. **認証情報を確認**
   - メールサービスによってはアプリパスワードが必要
   - 2段階認証が有効になっているか確認

### Gmail特有の問題

- **「安全性の低いアプリのアクセス」エラー**
  → アプリパスワードを使用してください

- **「認証に失敗しました」エラー**
  → 2段階認証を有効にして、アプリパスワードを生成してください

### デバッグモード

詳細なエラー情報を確認するには、EmailNotifier.phpで以下を追加：

```php
$mail->SMTPDebug = 2; // デバッグレベル（0=オフ、1=クライアント、2=クライアント+サーバー）
```

## mail()関数に戻す

SMTP設定がうまくいかない場合は、`includes/config.php` で以下のように変更：

```php
define('INFORM_EMAIL_METHOD', 'mail');
```

## セキュリティ上の注意

1. **パスワードを直接書かない**
   - 環境変数を使用することを推奨
   - `.gitignore` に `config.php` を追加（または別ファイルに分離）

2. **アプリパスワードを使用**
   - メインのパスワードではなく、アプリ専用のパスワードを使用

3. **TLS/SSLを使用**
   - 暗号化なしの接続は避ける

## サポート

問題が解決しない場合は、以下を確認してください：

- PHPMailerのバージョン: 7.0以上
- PHPのバージョン: 7.4以上
- OpenSSL拡張が有効になっているか

---

**作成日:** 2026年1月30日  
**バージョン:** 1.0
