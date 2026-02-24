# Inform システム デプロイメントチェックリスト

## 本番環境へのデプロイ前の確認事項

### 1. データベース設定 ✅

- [ ] データベーステーブルが作成されている

  ```sql
  -- setup-inform.sql を実行
  mysql -u root -p hajime < setup-inform.sql
  ```

- [ ] データベース接続情報が正しい
  - `includes/config.php` の設定を確認
  - ホスト名、データベース名、ユーザー名、パスワード

- [ ] データベースユーザーの権限が適切
  - SELECT, INSERT, UPDATE, DELETE 権限
  - CREATE, DROP 権限（マイグレーション用）

### 2. ファイルとディレクトリの権限

- [ ] ログディレクトリの書き込み権限

  ```bash
  chmod 755 logs/
  chmod 644 logs/inform.log
  ```

- [ ] アップロードディレクトリの権限（将来の拡張用）

  ```bash
  chmod 755 uploads/
  ```

- [ ] PHPファイルの実行権限
  ```bash
  chmod 644 includes/inform/*.php
  chmod 644 admin/inform/*.php
  chmod 644 public/inform/*.php
  ```

### 3. セキュリティ設定

- [ ] HTTPS が有効になっている
  - SSL証明書がインストールされている
  - HTTPからHTTPSへのリダイレクトが設定されている

- [ ] `.htaccess` ファイルが正しく設定されている

  ```apache
  # HTTPSリダイレクト
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
  ```

- [ ] セキュリティヘッダーが設定されている

  ```apache
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-Content-Type-Options "nosniff"
  Header set X-XSS-Protection "1; mode=block"
  ```

- [ ] PHPエラー表示が無効になっている
  ```php
  // php.ini または .htaccess
  display_errors = Off
  log_errors = On
  error_log = /path/to/logs/php_errors.log
  ```

### 4. 機能テスト

- [ ] フォーム作成機能が動作する
  - 管理画面にログイン
  - 新しいフォームを作成
  - フィールドを追加
  - 保存して確認

- [ ] フォーム埋め込みが動作する
  - iframe埋め込みコードをコピー
  - テストページに貼り付け
  - フォームが表示されることを確認

- [ ] フォーム送信が動作する
  - テストフォームに入力
  - スパム対策チャレンジに回答
  - 送信ボタンをクリック
  - 成功メッセージが表示されることを確認

- [ ] メッセージ管理が動作する
  - 管理画面でメッセージ一覧を表示
  - メッセージ詳細を表示
  - ステータスを変更
  - メッセージを削除

- [ ] メール通知が動作する
  - メール通知を有効にしたフォームを作成
  - テスト送信を実行
  - 通知メールが届くことを確認

### 5. パフォーマンステスト

- [ ] ページ読み込み速度が許容範囲内
  - フォーム一覧: < 1秒
  - フォーム表示: < 2秒
  - フォーム送信: < 3秒

- [ ] データベースクエリが最適化されている
  - スロークエリログを確認
  - インデックスが適切に使用されている

- [ ] 同時アクセステスト
  - 複数ユーザーが同時にフォームを送信
  - レート制限が機能することを確認

### 6. セキュリティテスト

- [ ] SQLインジェクション対策が機能している

  ```bash
  php includes/inform/test_integration_final.php
  ```

- [ ] XSS対策が機能している
  - スクリプトタグを含む入力をテスト
  - 表示時にエスケープされることを確認

- [ ] CSRF対策が機能している
  - トークンなしで送信を試行
  - 拒否されることを確認

- [ ] レート制限が機能している
  - 短時間に複数回送信を試行
  - 制限超過時に拒否されることを確認

- [ ] スパム対策が機能している
  - 誤った回答で送信を試行
  - 拒否されることを確認

### 7. バックアップ設定

- [ ] データベースバックアップが設定されている

  ```bash
  # crontabに追加
  0 2 * * * mysqldump -u root -p hajime > /backup/hajime_$(date +\%Y\%m\%d).sql
  ```

- [ ] ファイルバックアップが設定されている

  ```bash
  # crontabに追加
  0 3 * * * tar -czf /backup/hajime_files_$(date +\%Y\%m\%d).tar.gz /path/to/hajime
  ```

- [ ] バックアップの復元テストを実施
  - バックアップから復元
  - すべての機能が動作することを確認

### 8. モニタリング設定

- [ ] エラーログの監視が設定されている
  - `logs/inform.log` を定期的に確認
  - エラー通知を設定

- [ ] アクセスログの監視が設定されている
  - 異常なアクセスパターンを検出
  - 不正アクセスを検出

- [ ] パフォーマンス監視が設定されている
  - レスポンスタイムを監視
  - データベースクエリ時間を監視

### 9. ドキュメント

- [ ] 管理者マニュアルが作成されている
  - フォームの作成方法
  - メッセージの管理方法
  - トラブルシューティング

- [ ] ユーザーマニュアルが作成されている
  - フォームの埋め込み方法
  - よくある質問

- [ ] 技術ドキュメントが作成されている
  - システムアーキテクチャ
  - データベーススキーマ
  - API仕様

### 10. 本番環境設定

- [ ] 本番環境のURLが設定されている
  - `includes/config.php` のBASE_URLを更新
  - 埋め込みコードのURLを確認

- [ ] メール設定が正しい
  - SMTPサーバーの設定
  - 送信元メールアドレス
  - テストメールの送信

- [ ] タイムゾーンが正しい

  ```php
  // php.ini または config.php
  date_default_timezone_set('Asia/Tokyo');
  ```

- [ ] セッション設定が適切
  ```php
  // php.ini
  session.cookie_secure = 1
  session.cookie_httponly = 1
  session.cookie_samesite = Strict
  ```

## デプロイ手順

### ステップ1: 準備

1. 本番環境のバックアップを取得
2. メンテナンスモードを有効化
3. 現在のバージョンを記録

### ステップ2: ファイルのアップロード

1. FTPまたはSCPでファイルをアップロード

   ```bash
   scp -r hajime/ user@server:/var/www/html/
   ```

2. ファイル権限を設定
   ```bash
   chmod -R 755 /var/www/html/hajime
   chmod -R 644 /var/www/html/hajime/**/*.php
   ```

### ステップ3: データベースのマイグレーション

1. データベースバックアップを取得

   ```bash
   mysqldump -u root -p hajime > hajime_backup.sql
   ```

2. マイグレーションを実行
   ```bash
   mysql -u root -p hajime < setup-inform.sql
   ```

### ステップ4: 設定ファイルの更新

1. `includes/config.php` を更新
2. データベース接続情報を確認
3. BASE_URLを本番環境のURLに変更

### ステップ5: テスト

1. 統合テストを実行

   ```bash
   php includes/inform/test_integration_final.php
   ```

2. 手動テストを実施
   - フォーム作成
   - フォーム送信
   - メッセージ管理

### ステップ6: メンテナンスモードを解除

1. メンテナンスモードを無効化
2. 本番環境にアクセス
3. すべての機能が動作することを確認

### ステップ7: モニタリング

1. エラーログを監視
2. アクセスログを監視
3. パフォーマンスを監視

## ロールバック手順

問題が発生した場合:

1. メンテナンスモードを有効化
2. データベースバックアップから復元
   ```bash
   mysql -u root -p hajime < hajime_backup.sql
   ```
3. ファイルを前のバージョンに戻す
4. 設定ファイルを復元
5. テストを実施
6. メンテナンスモードを解除

## サポート連絡先

- 技術サポート: support@example.com
- 緊急連絡先: emergency@example.com
- ドキュメント: https://docs.example.com/inform

## 変更履歴

| 日付       | バージョン | 変更内容     | 担当者  |
| ---------- | ---------- | ------------ | ------- |
| 2026-01-30 | 1.0        | 初回リリース | Kiro AI |

---

**注意:** このチェックリストは、本番環境へのデプロイ前に必ず確認してください。すべての項目にチェックを入れてから、デプロイを実施してください。
