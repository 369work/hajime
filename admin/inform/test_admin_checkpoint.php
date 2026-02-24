<?php
/**
 * チェックポイント8: 管理画面の統合テスト
 * 
 * すべての管理画面ページが正しく動作することを確認します。
 * - フォーム一覧ページ (index.php)
 * - フォーム作成・編集ページ (form-edit.php)
 * - フォーム削除機能 (form-delete.php)
 * - メッセージ一覧ページ (messages.php)
 * - メッセージ詳細ページ (message-detail.php)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';
require_once __DIR__ . '/../../includes/inform/FormField.php';
require_once __DIR__ . '/../../includes/inform/Submission.php';

$testsPassed = 0;
$testsFailed = 0;

function testResult($testName, $passed, $message = '') {
    global $testsPassed, $testsFailed;
    
    if ($passed) {
        $testsPassed++;
        echo "✓ $testName\n";
    } else {
        $testsFailed++;
        echo "✗ $testName\n";
        if ($message) {
            echo "  エラー: $message\n";
        }
    }
}

try {
    echo "=== チェックポイント8: 管理画面の統合テスト ===\n\n";

    $form = new Form();
    $field = new FormField();
    $submission = new Submission();

    // ========================================
    // セクション1: ページファイルの存在確認
    // ========================================
    echo "セクション1: ページファイルの存在確認\n";
    echo str_repeat('-', 50) . "\n";

    $requiredFiles = [
        'index.php' => __DIR__ . '/index.php',
        'form-edit.php' => __DIR__ . '/form-edit.php',
        'form-delete.php' => __DIR__ . '/form-delete.php',
        'messages.php' => __DIR__ . '/messages.php',
        'message-detail.php' => __DIR__ . '/message-detail.php'
    ];

    foreach ($requiredFiles as $name => $path) {
        testResult(
            "ファイル存在: $name",
            file_exists($path),
            "ファイルが見つかりません: $path"
        );
    }
    echo "\n";

    // ========================================
    // セクション2: フォーム一覧ページの機能テスト
    // ========================================
    echo "セクション2: フォーム一覧ページの機能テスト\n";
    echo str_repeat('-', 50) . "\n";

    // テスト: フォーム一覧の取得
    $forms = $form->getAll();
    testResult(
        "フォーム一覧の取得",
        is_array($forms),
        "getAll() が配列を返しませんでした"
    );

    // テスト: フォームの作成
    $testFormId = $form->create(
        'チェックポイントテストフォーム',
        'これは管理画面テスト用のフォームです',
        [
            'email_notifications' => true,
            'notification_emails' => ['test@example.com'],
            'success_message' => 'テストメッセージ'
        ]
    );
    testResult(
        "フォームの作成",
        $testFormId > 0,
        "フォームの作成に失敗しました"
    );

    // テスト: 作成したフォームの取得
    $createdForm = $form->getById($testFormId);
    testResult(
        "作成したフォームの取得",
        $createdForm !== null && $createdForm['name'] === 'チェックポイントテストフォーム',
        "フォームの取得に失敗しました"
    );

    echo "\n";

    // ========================================
    // セクション3: フォーム編集ページの機能テスト
    // ========================================
    echo "セクション3: フォーム編集ページの機能テスト\n";
    echo str_repeat('-', 50) . "\n";

    // テスト: フォームフィールドの追加
    $testFields = [
        [
            'type' => FormField::TYPE_TEXT,
            'label' => 'お名前',
            'name' => 'name',
            'config' => [
                'placeholder' => 'お名前を入力してください',
                'required' => true,
                'max_length' => 100
            ]
        ],
        [
            'type' => FormField::TYPE_EMAIL,
            'label' => 'メールアドレス',
            'name' => 'email',
            'config' => [
                'placeholder' => 'email@example.com',
                'required' => true
            ]
        ],
        [
            'type' => FormField::TYPE_TEXTAREA,
            'label' => 'お問い合わせ内容',
            'name' => 'message',
            'config' => [
                'placeholder' => 'お問い合わせ内容を入力してください',
                'required' => true,
                'max_length' => 1000
            ]
        ]
    ];

    $form->saveFields($testFormId, $testFields);
    $savedFields = $form->getFields($testFormId);
    testResult(
        "フォームフィールドの保存",
        count($savedFields) === 3,
        "フィールド数が一致しません。期待: 3, 実際: " . count($savedFields)
    );

    // テスト: フォームの更新
    $updated = $form->update(
        $testFormId,
        'チェックポイントテストフォーム（更新）',
        '説明を更新しました',
        [
            'email_notifications' => false,
            'notification_emails' => [],
            'success_message' => '更新されたメッセージ'
        ]
    );
    testResult(
        "フォームの更新",
        $updated,
        "フォームの更新に失敗しました"
    );

    $updatedForm = $form->getById($testFormId);
    testResult(
        "更新内容の確認",
        $updatedForm['name'] === 'チェックポイントテストフォーム（更新）',
        "フォーム名が更新されていません"
    );

    // テスト: 埋め込みコードの生成（URLの確認）
    $iframeUrl = SITE_URL . '/public/inform/embed.php?form_id=' . $testFormId;
    $jsUrl = SITE_URL . '/public/inform/embed.js';
    testResult(
        "iframe埋め込みURL生成",
        strpos($iframeUrl, 'form_id=' . $testFormId) !== false,
        "埋め込みURLが正しく生成されていません"
    );
    testResult(
        "JavaScript埋め込みURL生成",
        strpos($jsUrl, 'embed.js') !== false,
        "JavaScript URLが正しく生成されていません"
    );

    echo "\n";

    // ========================================
    // セクション4: メッセージ管理ページの機能テスト
    // ========================================
    echo "セクション4: メッセージ管理ページの機能テスト\n";
    echo str_repeat('-', 50) . "\n";

    // テスト: 送信データの作成
    $testSubmissions = [
        [
            'data' => [
                'name' => 'テストユーザー1',
                'email' => 'test1@example.com',
                'message' => 'テストメッセージ1'
            ],
            'ip' => '192.168.1.100',
            'status' => Submission::STATUS_NEW
        ],
        [
            'data' => [
                'name' => 'テストユーザー2',
                'email' => 'test2@example.com',
                'message' => 'テストメッセージ2'
            ],
            'ip' => '192.168.1.101',
            'status' => Submission::STATUS_IN_PROGRESS
        ],
        [
            'data' => [
                'name' => 'テストユーザー3',
                'email' => 'test3@example.com',
                'message' => 'テストメッセージ3'
            ],
            'ip' => '192.168.1.102',
            'status' => Submission::STATUS_RESOLVED
        ]
    ];

    $submissionIds = [];
    foreach ($testSubmissions as $testData) {
        $id = $submission->create(
            $testFormId,
            $testData['data'],
            $testData['ip'],
            'Mozilla/5.0 (Test)'
        );
        
        if ($testData['status'] !== Submission::STATUS_NEW) {
            $submission->updateStatus($id, $testData['status']);
        }
        
        $submissionIds[] = $id;
    }
    testResult(
        "送信データの作成",
        count($submissionIds) === 3,
        "送信データの作成に失敗しました"
    );

    // テスト: すべての送信の取得
    $allSubmissions = $submission->getAll();
    testResult(
        "すべての送信の取得",
        count($allSubmissions) >= 3,
        "送信データの取得に失敗しました"
    );

    // テスト: フォームIDでフィルタ
    $filteredByForm = $submission->getAll(['form_id' => $testFormId]);
    testResult(
        "フォームIDでフィルタ",
        count($filteredByForm) === 3,
        "フィルタが正しく動作していません。期待: 3, 実際: " . count($filteredByForm)
    );

    // テスト: ステータスでフィルタ
    $newSubmissions = $submission->getAll([
        'form_id' => $testFormId,
        'status' => Submission::STATUS_NEW
    ]);
    testResult(
        "ステータスでフィルタ（新規）",
        count($newSubmissions) === 1,
        "ステータスフィルタが正しく動作していません"
    );

    // テスト: 日付範囲でフィルタ
    $today = date('Y-m-d 00:00:00');
    $tomorrow = date('Y-m-d 23:59:59', strtotime('+1 day'));
    $dateFiltered = $submission->getAll([
        'form_id' => $testFormId,
        'date_from' => $today,
        'date_to' => $tomorrow
    ]);
    testResult(
        "日付範囲でフィルタ",
        count($dateFiltered) === 3,
        "日付フィルタが正しく動作していません"
    );

    echo "\n";

    // ========================================
    // セクション5: メッセージ詳細ページの機能テスト
    // ========================================
    echo "セクション5: メッセージ詳細ページの機能テスト\n";
    echo str_repeat('-', 50) . "\n";

    // テスト: 送信の詳細取得
    $detailSubmission = $submission->getById($submissionIds[0]);
    testResult(
        "送信の詳細取得",
        $detailSubmission !== null,
        "送信の詳細取得に失敗しました"
    );

    testResult(
        "送信データの内容確認",
        isset($detailSubmission['data']['name']) && 
        $detailSubmission['data']['name'] === 'テストユーザー1',
        "送信データの内容が正しくありません"
    );

    // テスト: ステータスの更新
    $statusUpdated = $submission->updateStatus($submissionIds[0], Submission::STATUS_RESOLVED);
    testResult(
        "ステータスの更新",
        $statusUpdated,
        "ステータスの更新に失敗しました"
    );

    $updatedSubmission = $submission->getById($submissionIds[0]);
    testResult(
        "更新されたステータスの確認",
        $updatedSubmission['status'] === Submission::STATUS_RESOLVED,
        "ステータスが更新されていません"
    );

    // テスト: 送信の削除
    $deleted = $submission->delete($submissionIds[2]);
    testResult(
        "送信の削除",
        $deleted,
        "送信の削除に失敗しました"
    );

    $deletedSubmission = $submission->getById($submissionIds[2]);
    testResult(
        "削除された送信の確認",
        $deletedSubmission === null,
        "送信が削除されていません"
    );

    echo "\n";

    // ========================================
    // セクション6: フォーム削除機能のテスト
    // ========================================
    echo "セクション6: フォーム削除機能のテスト\n";
    echo str_repeat('-', 50) . "\n";

    // テスト: フォームの削除（カスケード削除）
    $formDeleted = $form->delete($testFormId);
    testResult(
        "フォームの削除",
        $formDeleted,
        "フォームの削除に失敗しました"
    );

    $deletedForm = $form->getById($testFormId);
    testResult(
        "削除されたフォームの確認",
        $deletedForm === null,
        "フォームが削除されていません"
    );

    // テスト: カスケード削除の確認（関連する送信も削除されているか）
    $remainingSubmissions = $submission->getAll(['form_id' => $testFormId]);
    testResult(
        "カスケード削除の確認",
        count($remainingSubmissions) === 0,
        "関連する送信が削除されていません"
    );

    echo "\n";

    // ========================================
    // テスト結果のサマリー
    // ========================================
    echo str_repeat('=', 50) . "\n";
    echo "テスト結果サマリー\n";
    echo str_repeat('=', 50) . "\n";
    echo "成功: $testsPassed\n";
    echo "失敗: $testsFailed\n";
    echo "合計: " . ($testsPassed + $testsFailed) . "\n";
    echo "\n";

    if ($testsFailed === 0) {
        echo "✓ すべてのテストが成功しました！\n";
        echo "管理画面は正しく動作しています。\n\n";
        echo "次のステップ:\n";
        echo "  - ブラウザで管理画面にアクセスして動作を確認してください\n";
        echo "  - フォーム一覧: " . SITE_URL . "/admin/inform/index.php\n";
        echo "  - メッセージ管理: " . SITE_URL . "/admin/inform/messages.php\n";
    } else {
        echo "✗ いくつかのテストが失敗しました。\n";
        echo "上記のエラーメッセージを確認して修正してください。\n";
    }

} catch (Exception $e) {
    echo "\n致命的なエラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
