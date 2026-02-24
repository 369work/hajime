# フォーム送信処理 (submit.php)

## 概要

`submit.php` は、Inform お問い合わせフォームシステムのフォーム送信を処理するエンドポイントです。

## 機能

### 1. セキュリティ対策

- **CSRFトークン検証** (要件 9.1)
  - セッションベースのCSRFトークンを検証
  - 無効なトークンは拒否

- **XSS対策** (要件 9.2)
  - すべてのユーザー入力を `htmlspecialchars()` でサニタイズ
  - 配列入力も再帰的にサニタイズ

- **SQLインジェクション対策** (要件 9.3)
  - プリペアドステートメントを使用（モデルクラスで実装）

- **レート制限** (要件 9.5)
  - IPアドレスごとに送信回数を制限
  - デフォルト: 60分間に5回まで

### 2. バリデーション

- **必須フィールドチェック** (要件 5.1)
  - 必須フィールドが空の場合はエラー

- **メールアドレス形式チェック** (要件 5.2)
  - `filter_var()` を使用してメール形式を検証

- **最大長チェック**
  - テキストフィールドとテキストエリアの最大長を検証

- **選択肢チェック**
  - select、radio、checkboxの値が定義されたオプションに含まれるか検証

### 3. スパム対策

- **画像ベースの日本語質問** (要件 5.3, 6.1-6.5)
  - AntiSpamクラスを使用してチャレンジを検証
  - 正しい回答がない場合は送信を拒否

### 4. データ保存

- **送信データの永続化** (要件 5.5)
  - Submissionクラスを使用してデータベースに保存
  - IPアドレスとユーザーエージェントも記録

### 5. レスポンス

- **JSONレスポンス** (要件 5.6)
  - 成功時: `{"success": true, "message": "成功メッセージ"}`
  - エラー時: `{"success": false, "message": "エラーメッセージ"}`
  - リダイレクトURL（設定されている場合）も含む

## 使用方法

### 基本的な使用

```html
<form method="POST" action="http://localhost/hajime/public/inform/submit.php">
  <input type="hidden" name="form_id" value="1" />
  <input type="hidden" name="csrf_token" value="..." />
  <input type="hidden" name="challenge_id" value="..." />

  <input type="text" name="name" required />
  <input type="email" name="email" required />
  <textarea name="message" required></textarea>
  <input type="text" name="challenge_answer" required />

  <button type="submit">送信</button>
</form>
```

### AJAXでの使用

```javascript
const formData = new FormData(form);

fetch("http://localhost/hajime/public/inform/submit.php", {
  method: "POST",
  body: formData,
})
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      console.log("送信成功:", data.message);
      if (data.redirect_url) {
        window.location.href = data.redirect_url;
      }
    } else {
      console.error("送信失敗:", data.message);
    }
  })
  .catch((error) => {
    console.error("ネットワークエラー:", error);
  });
```

## エラーメッセージ

| エラー                 | メッセージ                                                       |
| ---------------------- | ---------------------------------------------------------------- |
| CSRFトークンエラー     | セッションが無効です。ページを再読み込みしてください             |
| 無効なフォームID       | 無効なフォームIDです                                             |
| フォームが見つからない | フォームが見つかりません                                         |
| レート制限超過         | 送信回数が制限を超えました。しばらくしてから再度お試しください   |
| スパム対策失敗         | 確認質問の回答が正しくありません                                 |
| 必須フィールド未入力   | {フィールド名}は必須です                                         |
| メール形式エラー       | 有効なメールアドレスを入力してください                           |
| 最大長超過             | {フィールド名}は{最大長}文字以内で入力してください               |
| システムエラー         | システムエラーが発生しました。しばらくしてから再度お試しください |

## テスト

### 基本テスト

```bash
php public/inform/test_submit.php
```

### 統合テスト

```bash
php public/inform/test_submit_integration.php
```

## セキュリティ考慮事項

1. **CSRFトークン**: 送信成功後に新しいトークンを生成
2. **レート制限**: IPアドレスベースで悪用を防止
3. **入力サニタイズ**: すべてのユーザー入力をエスケープ
4. **エラーログ**: 詳細なエラーはログに記録し、ユーザーには一般的なメッセージを表示

## 関連ファイル

- `includes/inform/Form.php` - フォームモデル
- `includes/inform/FormField.php` - フィールドモデルとバリデーション
- `includes/inform/Submission.php` - 送信モデルとレート制限
- `includes/inform/AntiSpam.php` - スパム対策
- `public/inform/embed.php` - フォーム表示エンドポイント
