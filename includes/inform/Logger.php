<?php
/**
 * Logger クラス
 * 
 * ロギング機能を提供します。
 * 要件: 8.5
 */
class Logger {
    
    // ログレベル
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_INFO = 'INFO';
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_SECURITY = 'SECURITY';

    // ログファイルのパス
    private static $logDir = __DIR__ . '/../../logs';
    private static $logFile = 'inform.log';

    /**
     * データベースエラーをログに記録
     * 
     * @param string $context エラーが発生したコンテキスト（クラス名::メソッド名）
     * @param string $message エラーメッセージ
     * @param Exception|null $exception 例外オブジェクト（オプション）
     * @return void
     */
    public static function logDatabaseError($context, $message, $exception = null) {
        $logMessage = "Database Error in {$context}: {$message}";
        
        if ($exception) {
            $logMessage .= " | Exception: " . $exception->getMessage();
            $logMessage .= " | File: " . $exception->getFile() . ":" . $exception->getLine();
        }
        
        self::writeLog(self::LEVEL_ERROR, $logMessage);
        
        // PHPのエラーログにも記録
        error_log($logMessage);
    }

    /**
     * メール送信エラーをログに記録
     * 
     * @param string $context エラーが発生したコンテキスト
     * @param string $recipient 送信先メールアドレス
     * @param string $message エラーメッセージ
     * @return void
     */
    public static function logEmailError($context, $recipient, $message) {
        $logMessage = "Email Error in {$context}: Failed to send to {$recipient} | {$message}";
        
        self::writeLog(self::LEVEL_ERROR, $logMessage);
        
        // PHPのエラーログにも記録
        error_log($logMessage);
    }

    /**
     * メール送信成功をログに記録
     * 
     * @param string $context コンテキスト
     * @param string $recipient 送信先メールアドレス
     * @param int $formId フォームID
     * @return void
     */
    public static function logEmailSuccess($context, $recipient, $formId) {
        $logMessage = "Email Success in {$context}: Sent to {$recipient} for Form ID {$formId}";
        
        self::writeLog(self::LEVEL_INFO, $logMessage);
    }

    /**
     * セキュリティ関連のエラーをログに記録
     * 
     * @param string $type セキュリティエラーのタイプ（CSRF, XSS, SQL Injection, Rate Limit等）
     * @param string $message エラーメッセージ
     * @param array $context 追加のコンテキスト情報（IPアドレス、ユーザーエージェント等）
     * @return void
     */
    public static function logSecurityError($type, $message, $context = []) {
        $ipAddress = $context['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgent = $context['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $requestUri = $context['request_uri'] ?? ($_SERVER['REQUEST_URI'] ?? 'unknown');
        
        $logMessage = "Security Alert [{$type}]: {$message}";
        $logMessage .= " | IP: {$ipAddress}";
        $logMessage .= " | User-Agent: {$userAgent}";
        $logMessage .= " | URI: {$requestUri}";
        
        // 追加のコンテキスト情報があれば追加
        if (!empty($context['additional'])) {
            $logMessage .= " | Additional: " . json_encode($context['additional']);
        }
        
        self::writeLog(self::LEVEL_SECURITY, $logMessage);
        
        // PHPのエラーログにも記録
        error_log($logMessage);
    }

    /**
     * 一般的なエラーをログに記録
     * 
     * @param string $context エラーが発生したコンテキスト
     * @param string $message エラーメッセージ
     * @param string $level ログレベル（デフォルト: ERROR）
     * @return void
     */
    public static function logError($context, $message, $level = self::LEVEL_ERROR) {
        $logMessage = "{$context}: {$message}";
        
        self::writeLog($level, $logMessage);
        
        // PHPのエラーログにも記録
        error_log($logMessage);
    }

    /**
     * 情報をログに記録
     * 
     * @param string $context コンテキスト
     * @param string $message メッセージ
     * @return void
     */
    public static function logInfo($context, $message) {
        $logMessage = "{$context}: {$message}";
        
        self::writeLog(self::LEVEL_INFO, $logMessage);
    }

    /**
     * ログファイルに書き込み
     * 
     * @param string $level ログレベル
     * @param string $message ログメッセージ
     * @return void
     */
    private static function writeLog($level, $message) {
        try {
            // ログディレクトリが存在しない場合は作成
            if (!is_dir(self::$logDir)) {
                @mkdir(self::$logDir, 0755, true);
            }

            $logFilePath = self::$logDir . '/' . self::$logFile;
            
            // タイムスタンプとログレベルを追加
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
            
            // ログファイルに追記
            @file_put_contents($logFilePath, $logEntry, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            // ログ書き込みに失敗した場合はPHPのエラーログに記録
            error_log("Logger::writeLog failed: " . $e->getMessage());
        }
    }

    /**
     * ログファイルをクリア（テスト用）
     * 
     * @return bool 成功した場合true
     */
    public static function clearLog() {
        $logFilePath = self::$logDir . '/' . self::$logFile;
        
        if (file_exists($logFilePath)) {
            return @unlink($logFilePath);
        }
        
        return true;
    }

    /**
     * ログファイルの内容を取得（デバッグ用）
     * 
     * @param int $lines 取得する行数（デフォルト: 100）
     * @return array ログエントリの配列
     */
    public static function getRecentLogs($lines = 100) {
        $logFilePath = self::$logDir . '/' . self::$logFile;
        
        if (!file_exists($logFilePath)) {
            return [];
        }
        
        $content = @file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($content === false) {
            return [];
        }
        
        // 最新のN行を取得
        return array_slice($content, -$lines);
    }
}
