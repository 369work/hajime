<?php
/**
 * サンプルデータ作成スクリプト
 * 
 * メッセージ管理ページを確認するためのサンプルデータを作成します。
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';
require_once __DIR__ . '/../../includes/inform/Submission.php';

try {
    echo "=== サンプルデータの作成 ===\n\n";

    $form = new Form();
    $submission = new Submission();

    // フォームを作成
    echo "フォームを作成中...\n";
    $formId = $form->create(
        'お問い合わせフォーム',
        'サンプルのお問い合わせフォームです',
        [
            'email_notifications' => true,
            'notification_emails' => ['admin@example.com'],
            'success_message' => 'お問い合わせありがとうございます。'
        ]
    );
    echo "フォームID: $formId を作成しました\n\n";

    // サンプル送信データを作成
    echo "サンプル送信データを作成中...\n";
    
    $sampleData = [
        [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'subject' => '製品について',
            'message' => '御社の製品について詳しく知りたいです。カタログを送っていただけますか？'
        ],
        [
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
            'subject' => '価格について',
            'message' => '大量注文の場合の割引はありますか？見積もりをお願いします。'
        ],
        [
            'name' => '鈴木一郎',
            'email' => 'suzuki@example.com',
            'subject' => 'サポート',
            'message' => '製品の使い方がわかりません。サポートをお願いします。'
        ],
        [
            'name' => '田中美咲',
            'email' => 'tanaka@example.com',
            'subject' => '不具合報告',
            'message' => '製品に不具合があります。交換をお願いします。'
        ],
        [
            'name' => '高橋健太',
            'email' => 'takahashi@example.com',
            'subject' => '納期について',
            'message' => '注文した商品の納期を教えてください。'
        ]
    ];

    $statuses = [
        Submission::STATUS_NEW,
        Submission::STATUS_NEW,
        Submission::STATUS_IN_PROGRESS,
        Submission::STATUS_IN_PROGRESS,
        Submission::STATUS_RESOLVED
    ];

    foreach ($sampleData as $index => $data) {
        $id = $submission->create(
            $formId,
            $data,
            '192.168.1.' . (100 + $index),
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        );
        
        // ステータスを設定
        if ($statuses[$index] !== Submission::STATUS_NEW) {
            $submission->updateStatus($id, $statuses[$index]);
        }
        
        echo "  送信 " . ($index + 1) . " を作成しました (ID: $id, 名前: {$data['name']}, ステータス: {$statuses[$index]})\n";
    }

    echo "\n=== サンプルデータの作成が完了しました ===\n\n";
    echo "メッセージ管理ページにアクセスしてください:\n";
    echo "  " . SITE_URL . "/admin/inform/messages.php\n\n";
    echo "フォームID: $formId\n";

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
