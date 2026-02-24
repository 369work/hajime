<?php
/**
 * Submission クラスの基本的なテスト
 * 
 * このファイルは開発中の動作確認用です。
 */

// Hajime CMSの設定を読み込み
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/Submission.php';
require_once __DIR__ . '/Form.php';

try {
    echo "=== Submission クラスのテスト ===\n\n";

    $submission = new Submission();
    $form = new Form();

    // テスト用のフォームを作成
    echo "準備: テスト用フォームの作成\n";
    $formId = $form->create(
        'テスト用お問い合わせフォーム',
        'Submissionクラスのテスト用',
        ['email_notifications' => false]
    );
    echo "作成されたフォームID: $formId\n\n";

    // テスト1: ステータス定数の確認
    echo "テスト1: ステータス定数の確認\n";
    echo "STATUS_NEW: " . Submission::STATUS_NEW . "\n";
    echo "STATUS_IN_PROGRESS: " . Submission::STATUS_IN_PROGRESS . "\n";
    echo "STATUS_RESOLVED: " . Submission::STATUS_RESOLVED . "\n";
    echo "✓ ステータス定数が正しく定義されています\n\n";

    // テスト2: 送信の作成
    echo "テスト2: 送信の作成\n";
    $submissionData = [
        'name' => '山田太郎',
        'email' => 'yamada@example.com',
        'message' => 'これはテストメッセージです。'
    ];
    $submissionId = $submission->create(
        $formId,
        $submissionData,
        '192.168.1.100',
        'Mozilla/5.0 (Test Browser)'
    );
    echo "作成された送信ID: $submissionId\n\n";

    // テスト3: 送信の取得
    echo "テスト3: 送信の取得\n";
    $retrievedSubmission = $submission->getById($submissionId);
    echo "取得した送信データ:\n";
    echo "  - フォームID: {$retrievedSubmission['form_id']}\n";
    echo "  - ステータス: {$retrievedSubmission['status']}\n";
    echo "  - IPアドレス: {$retrievedSubmission['ip_address']}\n";
    echo "  - データ: " . json_encode($retrievedSubmission['data'], JSON_UNESCAPED_UNICODE) . "\n";
    
    if ($retrievedSubmission['status'] === Submission::STATUS_NEW) {
        echo "✓ 初期ステータスが正しく設定されています\n";
    } else {
        echo "✗ 初期ステータスが正しくありません\n";
    }
    echo "\n";

    // テスト4: ステータスの更新
    echo "テスト4: ステータスの更新\n";
    $updated = $submission->updateStatus($submissionId, Submission::STATUS_IN_PROGRESS);
    echo "更新結果: " . ($updated ? '成功' : '失敗') . "\n";
    
    $updatedSubmission = $submission->getById($submissionId);
    if ($updatedSubmission['status'] === Submission::STATUS_IN_PROGRESS) {
        echo "✓ ステータスが正しく更新されました\n";
    } else {
        echo "✗ ステータスの更新に失敗しました\n";
    }
    echo "\n";

    // テスト5: フォームIDで送信を取得
    echo "テスト5: フォームIDで送信を取得\n";
    
    // 追加の送信を作成
    $submission->create($formId, ['name' => '佐藤花子', 'email' => 'sato@example.com', 'message' => 'テスト2'], '192.168.1.101', 'Test');
    $submission->create($formId, ['name' => '鈴木一郎', 'email' => 'suzuki@example.com', 'message' => 'テスト3'], '192.168.1.102', 'Test');
    
    $formSubmissions = $submission->getByForm($formId);
    echo "フォームID {$formId} の送信数: " . count($formSubmissions) . "\n";
    foreach ($formSubmissions as $sub) {
        echo "  - ID: {$sub['id']}, 名前: {$sub['data']['name']}, ステータス: {$sub['status']}\n";
    }
    echo "\n";

    // テスト6: フィルタリング機能
    echo "テスト6: フィルタリング機能\n";
    
    // ステータスでフィルタ
    $filteredSubmissions = $submission->getByForm($formId, ['status' => Submission::STATUS_NEW]);
    echo "ステータスが 'new' の送信数: " . count($filteredSubmissions) . "\n";
    
    // 日付範囲でフィルタ
    $today = date('Y-m-d 00:00:00');
    $tomorrow = date('Y-m-d 23:59:59', strtotime('+1 day'));
    $dateFiltered = $submission->getByForm($formId, [
        'date_from' => $today,
        'date_to' => $tomorrow
    ]);
    echo "今日の送信数: " . count($dateFiltered) . "\n\n";

    // テスト7: すべての送信を取得
    echo "テスト7: すべての送信を取得\n";
    $allSubmissions = $submission->getAll();
    echo "全送信数: " . count($allSubmissions) . "\n";
    
    // フォームIDでフィルタ
    $filteredAll = $submission->getAll(['form_id' => $formId]);
    echo "フォームID {$formId} でフィルタした送信数: " . count($filteredAll) . "\n";
    
    // フォーム名が含まれているか確認
    if (isset($filteredAll[0]['form_name'])) {
        echo "✓ フォーム名が正しく取得されています: {$filteredAll[0]['form_name']}\n";
    } else {
        echo "✗ フォーム名の取得に失敗しました\n";
    }
    echo "\n";

    // テスト8: レート制限のチェック
    echo "テスト8: レート制限のチェック\n";
    
    $testIp = '192.168.1.200';
    
    // レート制限をリセット
    $submission->resetRateLimit($testIp);
    
    // 最大回数まで送信
    $maxSubmissions = Submission::RATE_LIMIT_MAX_SUBMISSIONS;
    echo "最大送信回数: {$maxSubmissions}\n";
    
    for ($i = 1; $i <= $maxSubmissions; $i++) {
        $canSubmit = $submission->checkRateLimit($testIp);
        if ($canSubmit) {
            echo "  送信 {$i}: 許可\n";
        } else {
            echo "  送信 {$i}: 拒否（予期しないエラー）\n";
        }
    }
    
    // 制限を超える送信
    $canSubmit = $submission->checkRateLimit($testIp);
    if (!$canSubmit) {
        echo "✓ レート制限が正しく動作しています（制限超過を検出）\n";
    } else {
        echo "✗ レート制限が正しく動作していません（制限超過を検出できませんでした）\n";
    }
    
    // リセット後の確認
    $submission->resetRateLimit($testIp);
    $canSubmit = $submission->checkRateLimit($testIp);
    if ($canSubmit) {
        echo "✓ レート制限のリセットが正しく動作しています\n";
    } else {
        echo "✗ レート制限のリセットに失敗しました\n";
    }
    echo "\n";

    // テスト9: 送信の削除
    echo "テスト9: 送信の削除\n";
    $deleted = $submission->delete($submissionId);
    echo "削除結果: " . ($deleted ? '成功' : '失敗') . "\n";
    
    // 削除後の確認
    $deletedSubmission = $submission->getById($submissionId);
    if ($deletedSubmission === null) {
        echo "✓ 送信が正しく削除されました\n";
    } else {
        echo "✗ 送信の削除に失敗しました\n";
    }
    echo "\n";

    // クリーンアップ: テスト用フォームを削除
    echo "クリーンアップ: テスト用フォームの削除\n";
    $form->delete($formId);
    echo "✓ テスト用フォームを削除しました\n\n";

    echo "=== すべてのテストが完了しました ===\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
