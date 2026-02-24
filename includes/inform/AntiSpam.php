<?php
require_once __DIR__ . '/../Database.php';

/**
 * AntiSpam クラス
 * 
 * 画像ベースの日本語質問によるスパム対策を実装します。
 */
class AntiSpam {
    private $db;
    
    /**
     * 日本語質問セット
     */
    private $challenges = [
        ['question' => 'ねこ', 'answer' => 'ねこ'],
        ['question' => 'いぬ', 'answer' => 'いぬ'],
        ['question' => 'さくら', 'answer' => 'さくら'],
        ['question' => 'にほん', 'answer' => 'にほん'],
        ['question' => 'ありがとう', 'answer' => 'ありがとう']
    ];
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * チャレンジを生成
     * 
     * ランダムな質問を選択し、データベースに保存します。
     * 
     * @return array ['challenge_id' => string, 'question' => string]
     */
    public function generateChallenge() {
        // ランダムな質問を選択
        $questionIndex = array_rand($this->challenges);
        $challenge = $this->challenges[$questionIndex];
        
        // 一意のチャレンジIDを生成
        $challengeId = bin2hex(random_bytes(32));
        
        // 有効期限を設定（15分後）
        $expiresAt = date('Y-m-d H:i:s', time() + 900);
        
        // データベースに保存
        $sql = "INSERT INTO inform_challenges (id, question_index, expires_at) VALUES (?, ?, ?)";
        $this->db->query($sql, [$challengeId, $questionIndex, $expiresAt]);
        
        return [
            'challenge_id' => $challengeId,
            'question' => $challenge['question']
        ];
    }
    
    /**
     * 画像を生成
     * 
     * GDライブラリを使用してテキストを画像として生成します。
     * 
     * @param string $text 画像に表示するテキスト
     * @return resource GD画像リソース
     */
    public function generateImage($text) {
        // 画像サイズを設定
        $width = 400;
        $height = 80;
        
        // 画像を作成
        $image = imagecreatetruecolor($width, $height);
        
        // 背景色を設定（白）
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        
        // テキスト色を設定（黒）
        $textColor = imagecolorallocate($image, 0, 0, 0);
        
        // フォントサイズとパスを設定
        $fontSize = 20;
        $fontPath = __DIR__ . '/../../assets/fonts/KosugiMaru-Regular.ttf';
        
        // フォントファイルが存在しない場合は別のフォントを試す
        if (!file_exists($fontPath)) {
            $fontPath = __DIR__ . '/../../assets/fonts/hkkakus.ttf';
        }
        
        if (file_exists($fontPath)) {
            // TrueTypeフォントを使用
            // 文字間隔を広げるために、1文字ずつ描画
            $fontSize = 24;
            $letterSpacing = 15; // 文字間隔
            
            // 全体の幅を計算
            $totalWidth = 0;
            $chars = mb_str_split($text);
            foreach ($chars as $char) {
                $bbox = imagettfbbox($fontSize, 0, $fontPath, $char);
                $totalWidth += ($bbox[2] - $bbox[0]) + $letterSpacing;
            }
            $totalWidth -= $letterSpacing; // 最後の文字の後のスペースを削除
            
            // 開始位置を計算（中央揃え）
            $startX = (int)(($width - $totalWidth) / 2);
            $y = (int)($height / 2 + $fontSize / 2);
            
            // 1文字ずつ描画
            $currentX = $startX;
            foreach ($chars as $char) {
                imagettftext($image, $fontSize, 0, $currentX, $y, $textColor, $fontPath, $char);
                $bbox = imagettfbbox($fontSize, 0, $fontPath, $char);
                $charWidth = $bbox[2] - $bbox[0];
                $currentX += $charWidth + $letterSpacing;
            }
        } else {
            // フォントが見つからない場合はエラー
            $errorText = 'Font file not found';
            $x = 10;
            $y = 30;
            imagestring($image, 5, $x, $y, $errorText, $textColor);
        }
        
        // ノイズを追加（ボット対策）
        for ($i = 0; $i < 50; $i++) {
            $noiseColor = imagecolorallocate($image, rand(200, 255), rand(200, 255), rand(200, 255));
            imagesetpixel($image, rand(0, $width), rand(0, $height), $noiseColor);
        }
        
        return $image;
    }
    
    /**
     * 回答を検証
     * 
     * チャレンジIDと回答を検証します。
     * 
     * @param string $challengeId チャレンジID
     * @param string $answer ユーザーの回答
     * @return bool 検証結果
     */
    public function verify($challengeId, $answer) {
        // チャレンジを取得
        $sql = "SELECT question_index, expires_at FROM inform_challenges WHERE id = ?";
        $stmt = $this->db->query($sql, [$challengeId]);
        $challenge = $stmt->fetch();
        
        if (!$challenge) {
            return false;
        }
        
        // 有効期限をチェック
        if (strtotime($challenge['expires_at']) < time()) {
            // 期限切れのチャレンジを削除
            $this->deleteChallenge($challengeId);
            return false;
        }
        
        // 回答をチェック
        $questionIndex = $challenge['question_index'];
        $correctAnswer = $this->challenges[$questionIndex]['answer'];
        
        // 回答が正しいかチェック（大文字小文字を区別しない）
        $isValid = mb_strtolower(trim($answer)) === mb_strtolower($correctAnswer);
        
        // 使用済みのチャレンジを削除
        if ($isValid) {
            $this->deleteChallenge($challengeId);
        }
        
        return $isValid;
    }
    
    /**
     * チャレンジを削除
     * 
     * @param string $challengeId チャレンジID
     */
    private function deleteChallenge($challengeId) {
        $sql = "DELETE FROM inform_challenges WHERE id = ?";
        $this->db->query($sql, [$challengeId]);
    }
    
    /**
     * 期限切れのチャレンジをクリーンアップ
     */
    public function cleanupExpiredChallenges() {
        $sql = "DELETE FROM inform_challenges WHERE expires_at < NOW()";
        $this->db->query($sql);
    }
}
