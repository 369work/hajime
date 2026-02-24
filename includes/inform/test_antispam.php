<?php
/**
 * AntiSpamクラスの基本的な機能テスト
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/AntiSpam.php';

echo "=== AntiSpam クラステスト ===\n\n";

try {
    $antiSpam = new AntiSpam();
    
    // テスト1: チャレンジ生成
    echo "テスト1: チャレンジ生成\n";
    $challenge = $antiSpam->generateChallenge();
    echo "✓ チャレンジID: " . $challenge['challenge_id'] . "\n";
    echo "✓ 質問: " . $challenge['question'] . "\n\n";
    
    // テスト2: 正しい回答の検証
    echo "テスト2: 正しい回答の検証\n";
    $correctAnswer = '';
    if (strpos($challenge['question'], 'ねこ') !== false) {
        $correctAnswer = 'ねこ';
    } elseif (strpos($challenge['question'], 'いぬ') !== false) {
        $correctAnswer = 'いぬ';
    } elseif (strpos($challenge['question'], 'さくら') !== false) {
        $correctAnswer = 'さくら';
    } elseif (strpos($challenge['question'], 'にほん') !== false) {
        $correctAnswer = 'にほん';
    } elseif (strpos($challenge['question'], 'ありがとう') !== false) {
        $correctAnswer = 'ありがとう';
    }
    
    $isValid = $antiSpam->verify($challenge['challenge_id'], $correctAnswer);
    if ($isValid) {
        echo "✓ 正しい回答が受け入れられました\n\n";
    } else {
        echo "✗ エラー: 正しい回答が拒否されました\n\n";
    }
    
    // テスト3: 誤った回答の検証
    echo "テスト3: 誤った回答の検証\n";
    $challenge2 = $antiSpam->generateChallenge();
    $isValid2 = $antiSpam->verify($challenge2['challenge_id'], '間違った回答');
    if (!$isValid2) {
        echo "✓ 誤った回答が拒否されました\n\n";
    } else {
        echo "✗ エラー: 誤った回答が受け入れられました\n\n";
    }
    
    // テスト4: 存在しないチャレンジIDの検証
    echo "テスト4: 存在しないチャレンジIDの検証\n";
    $isValid3 = $antiSpam->verify('invalid_challenge_id', 'ねこ');
    if (!$isValid3) {
        echo "✓ 存在しないチャレンジIDが拒否されました\n\n";
    } else {
        echo "✗ エラー: 存在しないチャレンジIDが受け入れられました\n\n";
    }
    
    // テスト5: 画像生成
    echo "テスト5: 画像生成\n";
    $image = $antiSpam->generateImage('テスト画像');
    if (is_resource($image) || is_object($image)) {
        echo "✓ 画像が正常に生成されました\n";
        imagedestroy($image);
    } else {
        echo "✗ エラー: 画像の生成に失敗しました\n";
    }
    
    echo "\n=== すべてのテストが完了しました ===\n";
    
} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
