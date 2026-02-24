# AntiSpam スパム対策機能

## 概要

画像ベースの日本語質問によるスパム対策システムです。ボットによる自動送信を防ぎ、人間のユーザーのみがフォームを送信できるようにします。

## 実装されたファイル

### 1. AntiSpam.php

スパム対策のコアクラス。以下の機能を提供します：

- **generateChallenge()**: ランダムな日本語質問を選択し、データベースに保存
- **generateImage($text)**: GDライブラリを使用してテキストを画像として生成
- **verify($challengeId, $answer)**: ユーザーの回答を検証
- **cleanupExpiredChallenges()**: 期限切れのチャレンジを削除

### 2. captcha.php

画像生成エンドポイント。チャレンジIDをクエリパラメータで受け取り、対応する質問を画像として返します。

## 使用方法

### チャレンジの生成

```php
require_once 'includes/inform/AntiSpam.php';

$antiSpam = new AntiSpam();
$challenge = $antiSpam->generateChallenge();

// 結果:
// [
//     'challenge_id' => '64文字の16進数文字列',
//     'question' => '「ねこ」と入力してください'
// ]
```

### 画像の表示

```html
<img
  src="public/inform/captcha.php?challenge_id=<?php echo $challenge['challenge_id']; ?>"
  alt="スパム対策質問"
/>
```

### 回答の検証

```php
$isValid = $antiSpam->verify($challengeId, $userAnswer);

if ($isValid) {
    // 回答が正しい - フォーム送信を許可
} else {
    // 回答が誤っている - エラーを表示
}
```

## 質問セット

以下の5つの日本語質問がランダムに選択されます：

1. 「ねこ」と入力してください
2. 「いぬ」と入力してください
3. 「さくら」と入力してください
4. 「にほん」と入力してください
5. 「ありがとう」と入力してください

## セキュリティ機能

- **有効期限**: チャレンジは15分後に自動的に期限切れになります
- **ワンタイム使用**: 正しい回答が検証されると、チャレンジは削除されます
- **画像レンダリング**: テキストを画像として表示し、コピー&ペーストを防止
- **ノイズ追加**: 画像にランダムなノイズを追加し、OCR攻撃を困難にします

## データベーステーブル

```sql
CREATE TABLE inform_challenges (
    id VARCHAR(64) PRIMARY KEY,
    question_index INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_expires (expires_at)
);
```

## テスト

基本的な機能テストを実行するには：

```bash
php includes/inform/test_antispam.php
```

Captchaエンドポイントをテストするには：

```bash
php includes/inform/test_captcha_endpoint.php
```

## 要件の検証

このモジュールは以下の要件を満たしています：

- **要件 6.1**: 日本語のテキスト質問を画像として表示 ✓
- **要件 6.2**: 事前定義されたセットからランダムな質問を使用 ✓
- **要件 6.3**: 回答がチャレンジ質問と一致することを検証 ✓
- **要件 6.4**: スパム対策チャレンジが失敗した場合に送信を拒否 ✓
- **要件 6.5**: コピー&ペーストを防ぐためにテキストを画像としてレンダリング ✓

## 注意事項

### 日本語フォント

画像生成で日本語を正しく表示するには、TrueTypeフォントが必要です：

- フォントパス: `assets/fonts/NotoSansJP-Regular.ttf`
- フォントが存在しない場合は、組み込みフォントにフォールバックします（日本語は正しく表示されません）

### GDライブラリ

このモジュールはPHPのGDライブラリを使用します。GDライブラリが有効になっていることを確認してください：

```bash
php -m | grep -i gd
```

## 今後の拡張

- より多くの質問パターンの追加
- 難易度レベルの設定
- 画像スタイルのカスタマイズ
- 音声チャレンジのサポート（アクセシビリティ向上）
