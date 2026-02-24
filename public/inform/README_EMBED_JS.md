# Inform JavaScript埋め込み機能

## 概要

Inform JavaScript埋め込み機能を使用すると、任意のウェブサイトにお問い合わせフォームを動的に埋め込むことができます。iframeとは異なり、JavaScriptを使用してフォームを直接ページに注入するため、よりシームレスな統合が可能です。

## 基本的な使用方法

### ステップ1: HTMLに埋め込み先の要素を追加

フォームを表示したい場所に、IDを持つ`<div>`要素を配置します。

```html
<div id="inform-form-container"></div>
```

### ステップ2: JavaScriptライブラリを読み込む

Inform JavaScript埋め込みライブラリを読み込みます。

```html
<script src="https://example.com/public/inform/embed.js"></script>
```

### ステップ3: フォームをレンダリング

`InformEmbed.render()`メソッドを呼び出してフォームをレンダリングします。

```html
<script>
  InformEmbed.render("inform-form-container", 1);
</script>
```

## 完全な例

```html
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>お問い合わせ</title>
  </head>
  <body>
    <h1>お問い合わせ</h1>
    <p>お気軽にお問い合わせください。</p>

    <!-- フォームを埋め込む要素 -->
    <div id="inform-form-container"></div>

    <!-- Inform JavaScript埋め込みライブラリを読み込み -->
    <script src="https://example.com/public/inform/embed.js"></script>
    <script>
      // フォームをレンダリング
      InformEmbed.render("inform-form-container", 1);
    </script>
  </body>
</html>
```

## API リファレンス

### InformEmbed.render(elementId, formId, options)

フォームを指定された要素にレンダリングします。

**パラメータ:**

- `elementId` (string, 必須): フォームを注入する要素のID
- `formId` (number, 必須): レンダリングするフォームのID
- `options` (object, オプション): オプション設定（将来の拡張用）

**例:**

```javascript
InformEmbed.render("my-form", 123);
```

## カスタマイズ

### ベースURLの設定

デフォルトでは、ベースURLはスクリプトのURLから自動検出されます。手動で設定する場合は、以下のようにします。

```html
<script>
  window.INFORM_BASE_URL = "https://example.com";
</script>
<script src="https://example.com/public/inform/embed.js"></script>
```

### スタイリング

フォームはTailwind CSSでスタイリングされています。独自のCSSを追加してカスタマイズすることも可能です。

```html
<style>
  #inform-form-container {
    max-width: 600px;
    margin: 0 auto;
  }
</style>
```

## iframe埋め込みとの比較

| 特徴                   | JavaScript埋め込み   | iframe埋め込み   |
| ---------------------- | -------------------- | ---------------- |
| 統合の容易さ           | 中                   | 簡単             |
| スタイルのカスタマイズ | 容易                 | 制限あり         |
| ページとの統合         | シームレス           | 分離             |
| ブラウザ互換性         | IE11+                | すべて           |
| セキュリティ           | 同一オリジンポリシー | サンドボックス化 |

## トラブルシューティング

### フォームが表示されない

1. ブラウザのコンソールでエラーメッセージを確認してください
2. フォームIDが正しいか確認してください
3. ベースURLが正しく設定されているか確認してください
4. CORSヘッダーが適切に設定されているか確認してください

### フォーム送信が動作しない

1. ブラウザのコンソールでエラーメッセージを確認してください
2. ネットワークタブで送信リクエストを確認してください
3. CSRFトークンが正しく生成されているか確認してください

## ブラウザ互換性

- Chrome (最新版)
- Firefox (最新版)
- Safari (最新版)
- Edge (最新版)
- Internet Explorer 11+

## セキュリティ

- すべてのフォーム送信はCSRFトークンで保護されています
- ユーザー入力はサーバー側でサニタイズされます
- スパム対策チャレンジが自動的に含まれます
- レート制限により悪用を防止します

## テスト

テストページ: `public/inform/test_embed_js.html`

ローカル環境でテストする場合:

```
http://localhost/hajime/public/inform/test_embed_js.html
```

## 要件

- 要件 4.2: JavaScript埋め込みによるフォーム表示
