<?php
/**
 * スパム対策画像生成エンドポイント
 * 
 * チャレンジIDをクエリパラメータで受け取り、
 * 対応する質問を画像として生成して返します。
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/inform/AntiSpam.php';

// Content-Typeヘッダーを設定
header('Content-Type: image/png');

// CORSヘッダーを設定（外部サイトからのアクセスを許可）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// チャレンジIDを取得
$challengeId = $_GET['challenge_id'] ?? '';

if (empty($challengeId)) {
    // エラー画像を生成
    $image = imagecreatetruecolor(400, 80);
    $bgColor = imagecolorallocate($image, 255, 200, 200);
    imagefilledrectangle($image, 0, 0, 400, 80, $bgColor);
    $textColor = imagecolorallocate($image, 255, 0, 0);
    imagestring($image, 5, 10, 30, 'Error: Invalid challenge ID', $textColor);
    imagepng($image);
    imagedestroy($image);
    exit;
}

try {
    $antiSpam = new AntiSpam();
    
    // チャレンジIDから質問を取得
    $db = Database::getInstance();
    $sql = "SELECT question_index, expires_at FROM inform_challenges WHERE id = ?";
    $stmt = $db->query($sql, [$challengeId]);
    $challenge = $stmt->fetch();
    
    if (!$challenge) {
        // チャレンジが見つからない場合
        $image = imagecreatetruecolor(400, 80);
        $bgColor = imagecolorallocate($image, 255, 200, 200);
        imagefilledrectangle($image, 0, 0, 400, 80, $bgColor);
        $textColor = imagecolorallocate($image, 255, 0, 0);
        imagestring($image, 5, 10, 30, 'Error: Challenge not found', $textColor);
        imagepng($image);
        imagedestroy($image);
        exit;
    }
    
    // 有効期限をチェック
    if (strtotime($challenge['expires_at']) < time()) {
        // 期限切れ
        $image = imagecreatetruecolor(400, 80);
        $bgColor = imagecolorallocate($image, 255, 200, 200);
        imagefilledrectangle($image, 0, 0, 400, 80, $bgColor);
        $textColor = imagecolorallocate($image, 255, 0, 0);
        imagestring($image, 5, 10, 30, 'Error: Challenge expired', $textColor);
        imagepng($image);
        imagedestroy($image);
        exit;
    }
    
    // 質問テキストを取得
    $challenges = [
        ['question' => 'ねこ', 'answer' => 'ねこ'],
        ['question' => 'いぬ', 'answer' => 'いぬ'],
        ['question' => 'さくら', 'answer' => 'さくら'],
        ['question' => 'にほん', 'answer' => 'にほん'],
        ['question' => 'ありがとう', 'answer' => 'ありがとう']
    ];
    
    $questionText = $challenges[$challenge['question_index']]['question'];
    
    // 画像を生成
    $image = $antiSpam->generateImage($questionText);
    
    // 画像を出力
    imagepng($image);
    imagedestroy($image);
    
} catch (Exception $e) {
    // エラーが発生した場合
    error_log('Captcha generation error: ' . $e->getMessage());
    
    $image = imagecreatetruecolor(400, 80);
    $bgColor = imagecolorallocate($image, 255, 200, 200);
    imagefilledrectangle($image, 0, 0, 400, 80, $bgColor);
    $textColor = imagecolorallocate($image, 255, 0, 0);
    imagestring($image, 5, 10, 30, 'Error: Failed to generate image', $textColor);
    imagepng($image);
    imagedestroy($image);
}
