# Inform お問い合わせフォームシステム - Form クラス

## 概要

`Form` クラスは、Inform お問い合わせフォームシステムのコアコンポーネントです。フォームの作成、取得、更新、削除（CRUD操作）とフィールド管理機能を提供します。

## 実装済み機能

### CRUD操作

1. **create($name, $description, $settings)** - フォームを作成
   - フォーム名、説明、設定（JSON形式）を受け取る
   - 作成されたフォームのIDを返す
   - 入力バリデーション付き

2. **getById($id)** - IDでフォームを取得
   - フォームデータを配列で返す
   - 存在しない場合はnullを返す
   - JSON設定を自動的にデコード

3. **getAll()** - すべてのフォームを取得
   - フォームの配列を返す
   - 作成日時の降順でソート
   - JSON設定を自動的にデコード

4. **update($id, $name, $description, $settings)** - フォームを更新
   - 指定されたIDのフォームを更新
   - 成功した場合trueを返す
   - 入力バリデーション付き

5. **delete($id)** - フォームを削除
   - 指定されたIDのフォームを削除
   - 外部キー制約により、関連するフィールドと送信データも自動削除（CASCADE）
   - 成功した場合trueを返す

### フィールド管理

1. **getFields($formId)** - フォームのフィールドを取得
   - 指定されたフォームのすべてのフィールドを取得
   - sort_order順にソート
   - JSON設定を自動的にデコード

2. **saveFields($formId, $fields)** - フォームのフィールドを保存
   - 既存のフィールドを削除して新しいフィールドを保存
   - トランザクションを使用して整合性を保証
   - フィールドバリデーション付き（type、label、nameは必須）

## セキュリティ機能

### SQLインジェクション対策

すべてのデータベース操作でプリペアドステートメントを使用しています。これにより、悪意のあるSQL文が実行されることを防ぎます。

**テスト済み:**

- 悪意のあるフォーム名: `'; DROP TABLE inform_forms; --`
- 悪意のあるフィールド名: `test' OR '1'='1`
- 悪意のあるID: `1 OR 1=1`

すべてのケースで、入力値はそのまま文字列として保存され、SQLインジェクションは発生しませんでした。

### エラーハンドリング

- データベースエラーは適切にキャッチされ、ログに記録されます
- ユーザーフレンドリーなエラーメッセージを返します
- トランザクション使用時は、エラー発生時に自動的にロールバックされます

## 使用例

```php
<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/inform/Form.php';

$form = new Form();

// フォームを作成
$formId = $form->create(
    'お問い合わせフォーム',
    'サンプルのお問い合わせフォームです',
    [
        'email_notifications' => true,
        'notification_emails' => ['admin@example.com'],
        'success_message' => 'お問い合わせありがとうございます。'
    ]
);

// フィールドを追加
$fields = [
    [
        'type' => 'text',
        'label' => 'お名前',
        'name' => 'name',
        'config' => [
            'placeholder' => 'お名前を入力してください',
            'required' => true,
            'max_length' => 100
        ]
    ],
    [
        'type' => 'email',
        'label' => 'メールアドレス',
        'name' => 'email',
        'config' => [
            'placeholder' => 'example@example.com',
            'required' => true
        ]
    ]
];
$form->saveFields($formId, $fields);

// フォームを取得
$retrievedForm = $form->getById($formId);
echo "フォーム名: {$retrievedForm['name']}\n";

// フィールドを取得
$retrievedFields = $form->getFields($formId);
foreach ($retrievedFields as $field) {
    echo "フィールド: {$field['label']} ({$field['type']})\n";
}
```

## テスト

以下のテストファイルが用意されています:

1. **test_form.php** - 基本的なCRUD操作とフィールド管理のテスト
2. **test_cascade_delete.php** - カスケード削除のテスト
3. **test_sql_injection.php** - SQLインジェクション対策のテスト

すべてのテストが正常に完了しています。

## データベーステーブル

### inform_forms

| カラム      | 型           | 説明               |
| ----------- | ------------ | ------------------ |
| id          | INT          | 主キー（自動採番） |
| name        | VARCHAR(200) | フォーム名         |
| description | TEXT         | フォームの説明     |
| settings    | JSON         | フォーム設定       |
| created_at  | TIMESTAMP    | 作成日時           |
| updated_at  | TIMESTAMP    | 更新日時           |

### inform_fields

| カラム     | 型           | 説明                             |
| ---------- | ------------ | -------------------------------- |
| id         | INT          | 主キー（自動採番）               |
| form_id    | INT          | 所属するフォームのID（外部キー） |
| type       | ENUM         | フィールドタイプ                 |
| label      | VARCHAR(200) | フィールドのラベル               |
| name       | VARCHAR(100) | フィールドの内部名               |
| config     | JSON         | フィールド設定                   |
| sort_order | INT          | 表示順序                         |
| created_at | TIMESTAMP    | 作成日時                         |

## 要件との対応

このクラスは以下の要件を満たしています:

- **要件 1.1**: フォーム一覧の表示（getAll）
- **要件 1.2**: フォームの作成（create）
- **要件 1.3**: フォームの編集（update、saveFields）
- **要件 1.4**: フォーム設定の永続化（create、update）
- **要件 1.5**: フォームと関連データの削除（delete with CASCADE）
- **要件 9.3**: SQLインジェクション対策（プリペアドステートメント使用）

## 次のステップ

次のタスクは以下の通りです:

- **2.2**: Formクラスのプロパティテスト（プロパティ1: フォーム設定の永続化）
- **2.3**: Formクラスのプロパティテスト（プロパティ2: フォーム削除時のカスケード削除）
- **2.4**: FormFieldクラスの実装
