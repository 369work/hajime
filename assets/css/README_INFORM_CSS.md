# Inform CSS スタイリング実装

## 概要

このドキュメントは、Inform お問い合わせフォームシステムのCSSスタイリング実装について説明します。

## ファイル

- `inform.css` - Informフォームシステムのカスタムスタイル

## 要件

**要件 4.3**: フォームのスタイル定義、Tailwind CSS統合、レスポンシブデザイン実装

## 実装内容

### 1. 基本設定

- `.inform-form-container` - フォーム全体のコンテナ（最大幅672px、中央配置）
- レスポンシブパディング

### 2. フォームヘッダー

- `.inform-form-title` - フォームタイトルのスタイル
- `.inform-form-description` - フォーム説明文のスタイル

### 3. メッセージ表示

- `.inform-message-success` - 成功メッセージ（緑色）
- `.inform-message-error` - エラーメッセージ（赤色）
- `.inform-message-warning` - 警告メッセージ（黄色）

### 4. フォームフィールド

- `.inform-field` - フィールドコンテナ
- `.inform-field-label` - フィールドラベル
- `.inform-field-input` - テキスト入力フィールド
- `.inform-field-textarea` - テキストエリア
- `.inform-field-select` - セレクトボックス
- `.inform-field-radio` - ラジオボタン
- `.inform-field-checkbox` - チェックボックス

### 5. スパム対策セクション

- `.inform-antispam` - スパム対策コンテナ
- `.inform-antispam-image` - スパム対策画像
- `.inform-antispam-description` - 説明文

### 6. 送信ボタン

- `.inform-submit-button` - 送信ボタン
- ホバー、フォーカス、アクティブ、無効状態のスタイル

### 7. バリデーションエラー

- `.inform-field-error` - エラー状態のフィールド
- `.inform-error-message` - エラーメッセージテキスト

### 8. ローディング状態

- `.inform-loading` - ローディング中のフォーム
- `.inform-spinner` - スピナーアニメーション

## レスポンシブデザイン

### タブレット (768px以下)

- パディングの調整
- フォームサイズの最適化
- 送信ボタンを全幅に変更

### モバイル (480px以下)

- さらにコンパクトなパディング
- フォントサイズを16pxに設定（iOSのズーム防止）
- タッチフレンドリーなサイズ

## Tailwind CSS統合

このCSSファイルは、Tailwind CSSと併用して使用されます：

1. **Tailwind CSS**: ユーティリティクラスとして使用（`w-full`, `px-3`, `py-2`など）
2. **inform.css**: カスタムコンポーネントクラスとして使用（`.inform-form`, `.inform-field`など）

### 使用例

```html
<!-- Tailwind + Custom CSS -->
<div class="inform-form-container">
  <form class="inform-form">
    <div class="inform-field">
      <label class="inform-field-label">
        お名前 <span class="inform-field-required">*</span>
      </label>
      <input type="text" class="inform-field-input" />
    </div>
  </form>
</div>
```

## ダークモード対応

`@media (prefers-color-scheme: dark)` を使用して、ダークモードに対応しています：

- 背景色を暗色に変更
- テキスト色を明色に変更
- ボーダー色の調整

## アクセシビリティ

### フォーカス表示

- `:focus-visible` を使用した明確なフォーカス表示
- アウトラインオフセットによる視認性向上

### スクリーンリーダー対応

- `.inform-sr-only` クラスで視覚的に非表示だがスクリーンリーダーで読み上げ可能なテキスト

## 印刷スタイル

`@media print` を使用して、印刷時のスタイルを最適化：

- 送信ボタンとスパム対策セクションを非表示
- シャドウを削除してボーダーに変更

## テスト

### テストファイル

`public/inform/test_css.html` - CSSスタイルのテストページ

### テスト方法

1. ブラウザで `http://localhost/hajime/public/inform/test_css.html` を開く
2. すべてのフィールドタイプが正しくスタイリングされていることを確認
3. レスポンシブデザインをテスト（ブラウザのデベロッパーツールでウィンドウサイズを変更）
4. フォーム送信をテスト（成功メッセージとローディング状態を確認）

### ブラウザコンソールでのテスト

```javascript
// 成功メッセージを表示
showSuccess();

// エラーメッセージを表示
showError();
```

## 統合

### embed.php への統合

`public/inform/embed.php` に以下のように統合されています：

```php
<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Inform Custom Styles -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/inform.css">
```

## カスタマイズ

### 色のカスタマイズ

CSS変数を使用していないため、色を変更する場合は直接CSSファイルを編集してください：

```css
/* 例: プライマリカラーを変更 */
.inform-submit-button {
  background-color: #10b981; /* green-500 */
}

.inform-submit-button:hover {
  background-color: #059669; /* green-600 */
}
```

### フォントのカスタマイズ

```css
.inform-form {
  font-family: "Noto Sans JP", sans-serif;
}
```

## パフォーマンス

- CSSファイルサイズ: 約8KB（圧縮前）
- Tailwind CDNと併用（本番環境では最適化を推奨）
- アニメーションは最小限（スピナーのみ）

## ブラウザサポート

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- iOS Safari 14+
- Android Chrome 90+

## 今後の改善案

1. CSS変数の導入（テーマカスタマイズの簡素化）
2. Tailwind CSSのカスタムビルド（ファイルサイズの削減）
3. アニメーションの追加（フィールドエラー時のシェイクなど）
4. より詳細なダークモードのカスタマイズ

## 関連ファイル

- `public/inform/embed.php` - フォーム埋め込みページ
- `includes/inform/FormField.php` - フィールドレンダリング
- `public/inform/test_css.html` - CSSテストページ
