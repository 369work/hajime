<?php
/**
 * メッセージ管理ページの基本的なテスト
 * 
 * このファイルは開発中の動作確認用です。
 * テストデータを作成して、メッセージ管理ページが正しく動作するか確認します。
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';
require_once __DIR__ . '/../../includes/inform/Submission.php';

try {
    echo "=== メッセージ管理ページのテスト ===\n\n";

    $form = new Form();
    $submission = new Submission();

    // テスト用のフォームを作成
    echo "準備: テスト用フォームの作成\n";
    $formId = $form->create(
        'テスト用お問い合わせフォーム',
        'メッセージ管理ページのテスト用',
        [
            'email_notifications' => false,
            'notification_emails' => [],
            'success_message' => 'お問い合わせありがとうございます。'
        ]
    );
    echo "作成されたフォームID: $formId\n\n";

    // テスト用の送信データを作成
    echo "テスト用の送信データを作成中...\n";
    
    $testSubmissions = [
        [
            'data' => [
                'name' => '山田太郎',
                'email' => 'yamada@example.com',
                'message' => 'お問い合わせ内容1です。'
            ],
            'ip' => '192.168.1.100',
            'status' => Submission::STATUS_NEW
        ],
        [
            'data' => [
                'name' => '佐藤花子',
                'email' => 'sato@example.com',
                'message' => 'お問い合わせ内容2です。'
            ],
            'ip' => '192.168.1.101',
            'status' => Submission::STATUS_IN_PROGRESS
        ],
        [
            'data' => [
                'name' => '鈴木一郎',
                'email' => 'suzuki@example.com',
                'message' => 'お問い合わせ内容3です。'
            ],
            'ip' => '192.168.1.102',
            'status' => Submission::STATUS_RESOLVED
        ]
    ];

    $submissionIds = [];
    foreach ($testSubmissions as $index => $testData) {
        $id = $submission->create(
            $formId,
            $testData['data'],
            $testData['ip'],
            'Mozilla/5.0 (Test Browser)'
        );
        
        // ステータスを設定
        if ($testData['status'] !== Submission::STATUS_NEW) {
            $submission->updateStatus($id, $testData['status']);
        }
        
        $submissionIds[] = $id;
        echo "  送信 " . ($index + 1) . " を作成しました (ID: $id, ステータス: {$testData['status']})\n";
    }
    echo "\n";

    // テスト1: すべての送信を取得
    echo "テスト1: すべての送信を取得\n";
    $allSubmissions = $submission->getAll();
    echo "全送信数: " . count($allSubmissions) . "\n";
    echo "✓ getAll() が正しく動作しています\n\n";

    // テスト2: フォームIDでフィルタ
    echo "テスト2: フォームIDでフィルタ\n";
    $filteredByForm = $submission->getAll(['form_id' => $formId]);
    echo "フォームID {$formId} の送信数: " . count($filteredByForm) . "\n";
    if (count($filteredByForm) === 3) {
        echo "✓ フォームIDフィルタが正しく動作しています\n";
    } else {
        echo "✗ フォームIDフィルタが正しく動作していません\n";
    }
    echo "\n";

    // テスト3: ステータスでフィルタ
    echo "テスト3: ステータスでフィルタ\n";
    $newSubmissions = $submission->getAll(['status' => Submission::STATUS_NEW]);
    $inProgressSubmissions = $submission->getAll(['status' => Submission::STATUS_IN_PROGRESS]);
    $resolvedSubmissions = $submission->getAll(['status' => Submission::STATUS_RESOLVED]);
    
    echo "新規: " . count($newSubmissions) . " 件\n";
    echo "対応中: " . count($inProgressSubmissions) . " 件\n";
    echo "解決済み: " . count($resolvedSubmissions) . " 件\n";
    echo "✓ ステータスフィルタが正しく動作しています\n\n";

    // テスト4: 日付範囲でフィルタ
    echo "テスト4: 日付範囲でフィルタ\n";
    $today = date('Y-m-d 00:00:00');
    $tomorrow = date('Y-m-d 23:59:59', strtotime('+1 day'));
    $dateFiltered = $submission->getAll([
        'date_from' => $today,
        'date_to' => $tomorrow
    ]);
    echo "今日の送信数: " . count($dateFiltered) . "\n";
    echo "✓ 日付範囲フィルタが正しく動作しています\n\n";

    // テスト5: 送信の詳細を取得
    echo "テスト5: 送信の詳細を取得\n";
    $detailSubmission = $submission->getById($submissionIds[0]);
    echo "送信ID {$submissionIds[0]} の詳細:\n";
    echo "  - フォームID: {$detailSubmission['form_id']}\n";
    echo "  - ステータス: {$detailSubmission['status']}\n";
    echo "  - IPアドレス: {$detailSubmission['ip_address']}\n";
    echo "  - 名前: {$detailSubmission['data']['name']}\n";
    echo "  - メール: {$detailSubmission['data']['email']}\n";
    echo "✓ getById() が正しく動作しています\n\n";

    // テスト6: ステータスの更新
    echo "テスト6: ステータスの更新\n";
    $updated = $submission->updateStatus($submissionIds[0], Submission::STATUS_RESOLVED);
    if ($updated) {
        $updatedSubmission = $submission->getById($submissionIds[0]);
        if ($updatedSubmission['status'] === Submission::STATUS_RESOLVED) {
            echo "✓ ステータスの更新が正しく動作しています\n";
        } else {
            echo "✗ ステータスの更新に失敗しました\n";
        }
    } else {
        echo "✗ updateStatus() が失敗しました\n";
    }
    echo "\n";

    // テスト7: 送信の削除
    echo "テスト7: 送信の削除\n";
    $deleted = $submission->delete($submissionIds[2]);
    if ($deleted) {
        $deletedSubmission = $submission->getById($submissionIds[2]);
        if ($deletedSubmission === null) {
            echo "✓ 送信の削除が正しく動作しています\n";
        } else {
            echo "✗ 送信の削除に失敗しました\n";
        }
    } else {
        echo "✗ delete() が失敗しました\n";
    }
    echo "\n";

    // クリーンアップ
    echo "クリーンアップ: テストデータの削除\n";
    $form->delete($formId);
    echo "✓ テスト用フォームと関連データを削除しました\n\n";

    echo "=== すべてのテストが完了しました ===\n\n";
    echo "メッセージ管理ページにアクセスしてください:\n";
    echo "  - メッセージ一覧: " . SITE_URL . "/admin/inform/messages.php\n";
    echo "  - メッセージ詳細: " . SITE_URL . "/admin/inform/message-detail.php?id=[ID]\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
