<?php
/**
 * Captchaエンドポイントのテスト
 * 
 * このスクリプトはチャレンジを生成し、
 * captcha.phpエンドポイントのURLを表示します。
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/AntiSpam.php';

echo "=== Captcha エンドポイントテスト ===\n\n";

try {
    $antiSpam = new AntiSpam();
    
    // チャレンジを生成
    $challenge = $antiSpam->generateChallenge();
    
    echo "チャレンジが生成されました:\n";
    echo "- チャレンジID: " . $challenge['challenge_id'] . "\n";
    echo "- 質問: " . $challenge['question'] . "\n\n";
    
    // Captcha画像のURLを生成
    $captchaUrl = SITE_URL . '/public/inform/captcha.php?challenge_id=' . $challenge['challenge_id'];
    
    echo "Captcha画像URL:\n";
    echo $captchaUrl . "\n\n";
    
    echo "ブラウザで上記URLにアクセスして、画像が正しく表示されることを確認してください。\n";
    echo "画像には「" . $challenge['question'] . "」というテキストが表示されるはずです。\n\n";
    
    // 正しい回答を抽出
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
    
    echo "正しい回答: " . $correctAnswer . "\n";
    
} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "\n";
}
