<?php
/**
 * ErrorHandler クラス
 * 
 * エラーメッセージの定義と管理を行います。
 * 要件: 5.4
 */
class ErrorHandler {
    
    /**
     * バリデーションエラーメッセージ
     */
    const VALIDATION_ERRORS = [
        'required_field' => '%sは必須です',
        'invalid_email' => '有効なメールアドレスを入力してください',
        'max_length_exceeded' => '%sは%d文字以内で入力してください',
        'invalid_format' => '%sの形式が正しくありません',
        'invalid_option' => '%sで無効な選択肢が選ばれました',
        'spam_challenge_failed' => '確認質問の回答が正しくありません',
        'spam_challenge_missing' => 'スパム対策の確認が必要です',
    ];

    /**
     * システムエラーメッセージ
     */
    const SYSTEM_ERRORS = [
        'database_error' => 'システムエラーが発生しました。しばらくしてから再度お試しください',
        'form_not_found' => 'フォームが見つかりません',
        'submission_not_found' => '送信が見つかりません',
        'fields_not_configured' => 'フォームフィールドが設定されていません',
        'json_conversion_failed' => 'データの変換に失敗しました',
        'transaction_failed' => '処理に失敗しました。もう一度お試しください',
        'email_send_failed' => 'メール送信に失敗しました',
        'email_config_missing' => '通知先メールアドレスが設定されていません',
    ];

    /**
     * セキュリティエラーメッセージ
     */
    const SECURITY_ERRORS = [
        'csrf_token_invalid' => 'セッションが無効です。ページを再読み込みしてください',
        'csrf_token_missing' => 'セキュリティトークンが見つかりません',
        'rate_limit_exceeded' => '送信回数が制限を超えました。しばらくしてから再度お試しください',
        'invalid_request_method' => '無効なリクエストです',
        'invalid_form_id' => '無効なフォームIDです',
        'unauthorized_access' => 'アクセスが拒否されました',
        'sql_injection_attempt' => '不正なリクエストが検出されました',
        'xss_attempt' => '不正な入力が検出されました',
    ];

    /**
     * バリデーションエラーメッセージを取得
     * 
     * @param string $key エラーキー
     * @param mixed ...$params メッセージのパラメータ（フィールド名、最大長など）
     * @return string エラーメッセージ
     */
    public static function getValidationError($key, ...$params) {
        if (!isset(self::VALIDATION_ERRORS[$key])) {
            return 'バリデーションエラーが発生しました';
        }
        
        $message = self::VALIDATION_ERRORS[$key];
        
        // パラメータがある場合はsprintfで置換
        if (!empty($params)) {
            return sprintf($message, ...$params);
        }
        
        return $message;
    }

    /**
     * システムエラーメッセージを取得
     * 
     * @param string $key エラーキー
     * @return string エラーメッセージ
     */
    public static function getSystemError($key) {
        if (!isset(self::SYSTEM_ERRORS[$key])) {
            return 'システムエラーが発生しました';
        }
        
        return self::SYSTEM_ERRORS[$key];
    }

    /**
     * セキュリティエラーメッセージを取得
     * 
     * @param string $key エラーキー
     * @return string エラーメッセージ
     */
    public static function getSecurityError($key) {
        if (!isset(self::SECURITY_ERRORS[$key])) {
            return 'セキュリティエラーが発生しました';
        }
        
        return self::SECURITY_ERRORS[$key];
    }

    /**
     * すべてのエラーメッセージを取得（デバッグ用）
     * 
     * @return array すべてのエラーメッセージ
     */
    public static function getAllErrors() {
        return [
            'validation' => self::VALIDATION_ERRORS,
            'system' => self::SYSTEM_ERRORS,
            'security' => self::SECURITY_ERRORS,
        ];
    }
}
