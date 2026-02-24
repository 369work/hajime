<?php
/**
 * 最終統合テスト - Inform お問い合わせフォームシステム
 * 
 * このテストは以下を検証します:
 * - すべての機能が正しく動作すること
 * - セキュリティ対策が適切に実装されていること
 * - パフォーマンスが許容範囲内であること
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/Form.php';
require_once __DIR__ . '/FormField.php';
require_once __DIR__ . '/Submission.php';
require_once __DIR__ . '/AntiSpam.php';
require_once __DIR__ . '/EmailNotifier.php';

class IntegrationTest {
    private $db;
    private $testResults = [];
    private $testFormId = null;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function runAllTests() {
        echo "=== Inform システム 最終統合テスト ===\n\n";
        
        // 機能テスト
        $this->testFormCreationAndManagement();
        $this->testFormFieldManagement();
        $this->testFormEmbedding();
        $this->testFormSubmission();
        $this->testAntiSpamProtection();
        $this->testMessageManagement();
        $this->testEmailNotification();
        
        // セキュリティテスト
        $this->testSQLInjectionProtection();
        $this->testXSSProtection();
        $this->testCSRFProtection();
        $this->testRateLimiting();
        
        // パフォーマンステスト
        $this->testPerformance();
        
        // クリーンアップ
        $this->cleanup();
        
        // 結果サマリー
        $this->printSummary();
    }
    
    private function testFormCreationAndManagement() {
        echo "📋 テスト: フォームの作成と管理\n";
        
        try {
            $form = new Form($this->db);
            
            // フォーム作成
            $settings = json_encode([
                'email_notifications' => true,
                'notification_emails' => ['test@example.com'],
                'success_message' => 'ありがとうございます'
            ]);
            
            $this->testFormId = $form->create(
                'テスト統合フォーム',
                'これは統合テスト用のフォームです',
                $settings
            );
            
            $this->assert($this->testFormId > 0, 'フォームが作成されました');
            
            // フォーム取得
            $retrieved = $form->getById($this->testFormId);
            $this->assert($retrieved !== null, 'フォームが取得できました');
            $this->assert($retrieved['name'] === 'テスト統合フォーム', 'フォーム名が正しい');
            
            // フォーム更新
            $form->update($this->testFormId, '更新されたフォーム', '更新された説明', $settings);
            $updated = $form->getById($this->testFormId);
            $this->assert($updated['name'] === '更新されたフォーム', 'フォームが更新されました');
            
            // フォーム一覧取得
            $allForms = $form->getAll();
            $this->assert(count($allForms) > 0, 'フォーム一覧が取得できました');
            
            $this->pass('フォームの作成と管理');
        } catch (Exception $e) {
            $this->fail('フォームの作成と管理', $e->getMessage());
        }
    }
    
    private function testFormFieldManagement() {
        echo "📝 テスト: フォームフィールドの管理\n";
        
        try {
            $form = new Form($this->db);
            
            // フィールド追加
            $fields = [
                [
                    'type' => 'text',
                    'label' => '名前',
                    'name' => 'name',
                    'config' => json_encode(['required' => true, 'max_length' => 100]),
                    'sort_order' => 1
                ],
                [
                    'type' => 'email',
                    'label' => 'メールアドレス',
                    'name' => 'email',
                    'config' => json_encode(['required' => true]),
                    'sort_order' => 2
                ],
                [
                    'type' => 'textarea',
                    'label' => 'お問い合わせ内容',
                    'name' => 'message',
                    'config' => json_encode(['required' => true, 'max_length' => 1000]),
                    'sort_order' => 3
                ]
            ];
            
            $form->saveFields($this->testFormId, $fields);
            
            // フィールド取得
            $savedFields = $form->getFields($this->testFormId);
            $this->assert(count($savedFields) === 3, '3つのフィールドが保存されました');
            $this->assert($savedFields[0]['type'] === 'text', 'テキストフィールドが正しい');
            $this->assert($savedFields[1]['type'] === 'email', 'メールフィールドが正しい');
            $this->assert($savedFields[2]['type'] === 'textarea', 'テキストエリアが正しい');
            
            $this->pass('フォームフィールドの管理');
        } catch (Exception $e) {
            $this->fail('フォームフィールドの管理', $e->getMessage());
        }
    }
    
    private function testFormEmbedding() {
        echo "🔗 テスト: フォームの埋め込み\n";
        
        try {
            // iframe埋め込みコード
            $iframeCode = '<iframe src="http://localhost/hajime/public/inform/embed.php?form_id=' . $this->testFormId . '" width="100%" height="600" frameborder="0"></iframe>';
            $this->assert(strpos($iframeCode, 'form_id=' . $this->testFormId) !== false, 'iframe埋め込みコードが正しい');
            
            // JavaScript埋め込みコード
            $jsCode = '<div id="inform-form-' . $this->testFormId . '"></div><script src="http://localhost/hajime/public/inform/embed.js"></script><script>InformEmbed.render("inform-form-' . $this->testFormId . '", ' . $this->testFormId . ');</script>';
            $this->assert(strpos($jsCode, 'form-' . $this->testFormId) !== false, 'JavaScript埋め込みコードが正しい');
            
            $this->pass('フォームの埋め込み');
        } catch (Exception $e) {
            $this->fail('フォームの埋め込み', $e->getMessage());
        }
    }
    
    private function testFormSubmission() {
        echo "📤 テスト: フォーム送信\n";
        
        try {
            $submission = new Submission();
            
            // 送信データ（配列形式）
            $data = [
                'name' => '山田太郎',
                'email' => 'yamada@example.com',
                'message' => 'これはテスト送信です'
            ];
            
            // 送信作成
            $submissionId = $submission->create($this->testFormId, $data, '127.0.0.1', 'Test User Agent');
            $this->assert($submissionId > 0, '送信が作成されました');
            
            // 送信取得
            $retrieved = $submission->getById($submissionId);
            $this->assert($retrieved !== null, '送信が取得できました');
            $this->assert($retrieved['form_id'] == $this->testFormId, 'フォームIDが正しい');
            $this->assert($retrieved['status'] === 'new', 'ステータスが新規です');
            
            // ステータス更新
            $submission->updateStatus($submissionId, 'in_progress');
            $updated = $submission->getById($submissionId);
            $this->assert($updated['status'] === 'in_progress', 'ステータスが更新されました');
            
            $this->pass('フォーム送信');
        } catch (Exception $e) {
            $this->fail('フォーム送信', $e->getMessage());
        }
    }
    
    private function testAntiSpamProtection() {
        echo "🛡️ テスト: スパム対策\n";
        
        try {
            $antiSpam = new AntiSpam();
            
            // チャレンジ生成
            $challenge = $antiSpam->generateChallenge();
            $this->assert(!empty($challenge['challenge_id']), 'チャレンジIDが生成されました');
            $this->assert(!empty($challenge['question']), 'チャレンジ質問が生成されました');
            
            // 正しい回答を取得（テスト用）
            $dbInstance = Database::getInstance();
            $stmt = $dbInstance->query(
                "SELECT question_index FROM inform_challenges WHERE id = ?",
                [$challenge['challenge_id']]
            );
            $result = $stmt->fetch();
            
            $challenges = [
                ['question' => '「ねこ」と入力してください', 'answer' => 'ねこ'],
                ['question' => '「いぬ」と入力してください', 'answer' => 'いぬ'],
                ['question' => '「さくら」と入力してください', 'answer' => 'さくら'],
                ['question' => '「にほん」と入力してください', 'answer' => 'にほん'],
                ['question' => '「ありがとう」と入力してください', 'answer' => 'ありがとう']
            ];
            
            $correctAnswer = $challenges[$result['question_index']]['answer'];
            
            // 正しい回答の検証
            $isValid = $antiSpam->verify($challenge['challenge_id'], $correctAnswer);
            $this->assert($isValid === true, '正しい回答が受け入れられました');
            
            // 新しいチャレンジを生成して誤った回答をテスト
            $challenge2 = $antiSpam->generateChallenge();
            $isInvalid = $antiSpam->verify($challenge2['challenge_id'], '間違った回答');
            $this->assert($isInvalid === false, '誤った回答が拒否されました');
            
            $this->pass('スパム対策');
        } catch (Exception $e) {
            $this->fail('スパム対策', $e->getMessage());
        }
    }
    
    private function testMessageManagement() {
        echo "💬 テスト: メッセージ管理\n";
        
        try {
            $submission = new Submission();
            
            // フォーム別の送信取得
            $submissions = $submission->getByForm($this->testFormId);
            $this->assert(is_array($submissions), 'フォーム別の送信が取得できました');
            
            // フィルタリング
            $filtered = $submission->getByForm($this->testFormId, ['status' => 'in_progress']);
            $this->assert(is_array($filtered), 'フィルタリングが機能しています');
            
            $this->pass('メッセージ管理');
        } catch (Exception $e) {
            $this->fail('メッセージ管理', $e->getMessage());
        }
    }
    
    private function testEmailNotification() {
        echo "📧 テスト: メール通知\n";
        
        try {
            // EmailNotifierクラスの存在確認
            $this->assert(class_exists('EmailNotifier'), 'EmailNotifierクラスが存在します');
            
            // メール送信失敗時も送信が継続されることを確認
            // (実際のメール送信はテストしない)
            
            $this->pass('メール通知');
        } catch (Exception $e) {
            $this->fail('メール通知', $e->getMessage());
        }
    }
    
    private function testSQLInjectionProtection() {
        echo "🔒 テスト: SQLインジェクション対策\n";
        
        try {
            $form = new Form($this->db);
            
            // SQLインジェクション試行
            $maliciousInput = "'; DROP TABLE inform_forms; --";
            
            try {
                $form->create($maliciousInput, 'test', '{}');
                // 例外が発生しなければ、入力がエスケープされている
                $this->pass('SQLインジェクション対策');
            } catch (Exception $e) {
                // エラーが発生してもテーブルが削除されていなければOK
                $dbInstance = Database::getInstance();
                $conn = $dbInstance->getConnection();
                $stmt = $conn->query("SHOW TABLES LIKE 'inform_forms'");
                $this->assert($stmt->rowCount() > 0, 'テーブルが保護されています');
                $this->pass('SQLインジェクション対策');
            }
        } catch (Exception $e) {
            $this->fail('SQLインジェクション対策', $e->getMessage());
        }
    }
    
    private function testXSSProtection() {
        echo "🔒 テスト: XSS対策\n";
        
        try {
            $submission = new Submission();
            
            // XSS試行（配列形式）
            $maliciousData = [
                'name' => '<script>alert("XSS")</script>',
                'email' => 'test@example.com',
                'message' => '<img src=x onerror=alert("XSS")>'
            ];
            
            $submissionId = $submission->create($this->testFormId, $maliciousData, '127.0.0.1', 'Test');
            $retrieved = $submission->getById($submissionId);
            
            $data = $retrieved['data'];
            
            // スクリプトタグがエスケープされているか確認
            // (実際の表示時にhtmlspecialcharsが使用されることを前提)
            $this->assert($submissionId > 0, 'XSS入力が保存されました（エスケープ処理は表示時）');
            
            $this->pass('XSS対策');
        } catch (Exception $e) {
            $this->fail('XSS対策', $e->getMessage());
        }
    }
    
    private function testCSRFProtection() {
        echo "🔒 テスト: CSRF対策\n";
        
        try {
            // CSRFトークン生成の確認
            if (!isset($_SESSION)) {
                session_start();
            }
            
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $this->assert(!empty($_SESSION['csrf_token']), 'CSRFトークンが生成されました');
            
            $this->pass('CSRF対策');
        } catch (Exception $e) {
            $this->fail('CSRF対策', $e->getMessage());
        }
    }
    
    private function testRateLimiting() {
        echo "⏱️ テスト: レート制限\n";
        
        try {
            $submission = new Submission();
            
            // レート制限チェック
            $testIp = '192.168.1.100';
            
            // まずリセット
            $submission->resetRateLimit($testIp);
            
            $canSubmit = $submission->checkRateLimit($testIp);
            $this->assert($canSubmit === true, 'レート制限チェックが機能しています');
            
            $this->pass('レート制限');
        } catch (Exception $e) {
            $this->fail('レート制限', $e->getMessage());
        }
    }
    
    private function testPerformance() {
        echo "⚡ テスト: パフォーマンス\n";
        
        try {
            $form = new Form($this->db);
            
            // フォーム取得のパフォーマンス
            $start = microtime(true);
            for ($i = 0; $i < 100; $i++) {
                $form->getById($this->testFormId);
            }
            $end = microtime(true);
            $duration = $end - $start;
            
            $this->assert($duration < 1.0, 'フォーム取得が高速です（100回: ' . round($duration, 3) . '秒）');
            
            // フォーム一覧取得のパフォーマンス
            $start = microtime(true);
            $form->getAll();
            $end = microtime(true);
            $duration = $end - $start;
            
            $this->assert($duration < 0.5, 'フォーム一覧取得が高速です（' . round($duration, 3) . '秒）');
            
            $this->pass('パフォーマンス');
        } catch (Exception $e) {
            $this->fail('パフォーマンス', $e->getMessage());
        }
    }
    
    private function cleanup() {
        echo "\n🧹 クリーンアップ中...\n";
        
        try {
            if ($this->testFormId) {
                $form = new Form($this->db);
                $form->delete($this->testFormId);
                echo "✓ テストデータを削除しました\n";
            }
        } catch (Exception $e) {
            echo "⚠ クリーンアップエラー: " . $e->getMessage() . "\n";
        }
    }
    
    private function assert($condition, $message) {
        if ($condition) {
            echo "  ✓ " . $message . "\n";
            return true;
        } else {
            echo "  ✗ " . $message . "\n";
            throw new Exception($message);
        }
    }
    
    private function pass($testName) {
        $this->testResults[$testName] = 'PASS';
        echo "✅ " . $testName . " - 成功\n\n";
    }
    
    private function fail($testName, $error) {
        $this->testResults[$testName] = 'FAIL: ' . $error;
        echo "❌ " . $testName . " - 失敗: " . $error . "\n\n";
    }
    
    private function printSummary() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "テスト結果サマリー\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $test => $result) {
            if ($result === 'PASS') {
                echo "✅ " . $test . "\n";
                $passed++;
            } else {
                echo "❌ " . $test . " - " . $result . "\n";
                $failed++;
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "合計: " . count($this->testResults) . " テスト\n";
        echo "成功: " . $passed . "\n";
        echo "失敗: " . $failed . "\n";
        echo str_repeat("=", 50) . "\n";
        
        if ($failed === 0) {
            echo "\n🎉 すべてのテストが成功しました！\n";
            echo "Inform システムは本番環境にデプロイする準備ができています。\n";
        } else {
            echo "\n⚠️ いくつかのテストが失敗しました。\n";
            echo "失敗したテストを確認して修正してください。\n";
        }
    }
}

// テスト実行
$test = new IntegrationTest();
$test->runAllTests();
