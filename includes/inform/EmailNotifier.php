<?php
/**
 * EmailNotifier クラス
 * 
 * メール通知機能を提供します。
 * 要件: 8.1, 8.2, 8.5
 */

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailNotifier {
    private $db;
    private $config;

    public function __construct() {
        $this->db = Database::getInstance();
        
        // config.phpから設定を読み込み
        $this->config = [
            'method' => INFORM_EMAIL_METHOD,
            'smtp' => [
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
                'encryption' => SMTP_ENCRYPTION,
                'username' => SMTP_USERNAME,
                'password' => SMTP_PASSWORD,
                'from_email' => SMTP_FROM_EMAIL,
                'from_name' => SMTP_FROM_NAME,
            ],
            'mail' => [
                'from_email' => MAIL_FROM_EMAIL,
                'from_name' => MAIL_FROM_NAME,
            ]
        ];
    }

    /**
     * 通知を送信
     * 
     * フォーム送信時に管理者にメール通知を送信します。
     * メール送信が失敗しても例外をスローせず、エラーをログに記録します。
     * 
     * @param int $formId フォームID
     * @param int $submissionId 送信ID
     * @return bool 送信成功時true、失敗時false
     */
    public function sendNotification($formId, $submissionId) {
        try {
            // フォーム情報を取得
            $formModel = new Form();
            $form = $formModel->getById($formId);
            
            if (!$form) {
                Logger::logError('EmailNotifier::sendNotification', 'Form not found: ' . $formId);
                return false;
            }

            // メール通知が有効かチェック
            $settings = $form['settings'];
            if (empty($settings['email_notifications']) || $settings['email_notifications'] !== true) {
                // メール通知が無効の場合は何もしない
                return true;
            }

            // 通知先メールアドレスを取得
            $notificationEmails = $settings['notification_emails'] ?? [];
            if (empty($notificationEmails) || !is_array($notificationEmails)) {
                Logger::logEmailError('EmailNotifier::sendNotification', 'N/A', 'No notification emails configured for Form ID: ' . $formId);
                return false;
            }

            // 送信情報を取得
            $submissionModel = new Submission();
            $submission = $submissionModel->getById($submissionId);
            
            if (!$submission) {
                Logger::logError('EmailNotifier::sendNotification', 'Submission not found: ' . $submissionId);
                return false;
            }

            // メール本文を生成
            $emailBody = $this->buildEmailBody($form, $submission);
            
            // メール件名
            $subject = '[Inform] 新しいお問い合わせ: ' . $form['name'];
            
            // メール送信方法に応じて送信
            if ($this->config['method'] === 'smtp') {
                return $this->sendViaSMTP($notificationEmails, $subject, $emailBody);
            } else {
                return $this->sendViaMail($notificationEmails, $subject, $emailBody);
            }

        } catch (Exception $e) {
            // エラーをログに記録（要件 8.5: メール送信失敗時もSubmissionは成功）
            Logger::logEmailError('EmailNotifier::sendNotification', 'N/A', $e->getMessage());
            Logger::logError('EmailNotifier::sendNotification', 'Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * メール本文を生成
     * 
     * フォーム名と送信の概要を含むメール本文を生成します。
     * 
     * @param array $form フォーム情報
     * @param array $submission 送信情報
     * @return string メール本文
     */
    private function buildEmailBody($form, $submission) {
        $body = "新しいお問い合わせが届きました。\n\n";
        $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $body .= "フォーム名: " . $form['name'] . "\n";
        $body .= "送信日時: " . $submission['created_at'] . "\n";
        $body .= "送信ID: " . $submission['id'] . "\n";
        $body .= "IPアドレス: " . $submission['ip_address'] . "\n";
        $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        $body .= "【送信内容】\n\n";
        
        // 送信データを整形
        $data = $submission['data'];
        foreach ($data as $fieldName => $fieldValue) {
            // フィールド名を整形
            $displayName = $fieldName;
            
            // 値を整形（配列の場合はカンマ区切りに）
            if (is_array($fieldValue)) {
                $fieldValue = implode(', ', $fieldValue);
            }
            
            $body .= $displayName . ":\n";
            $body .= $fieldValue . "\n\n";
        }
        
        $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $body .= "管理画面で詳細を確認:\n";
        $body .= ADMIN_URL . "/inform/message-detail.php?id=" . $submission['id'] . "\n";
        
        return $body;
    }

    /**
     * SMTP経由でメールを送信
     * 
     * @param array $recipients 受信者メールアドレスの配列
     * @param string $subject 件名
     * @param string $body 本文
     * @return bool 送信成功時true
     */
    private function sendViaSMTP($recipients, $subject, $body) {
        $allSuccess = true;
        
        foreach ($recipients as $email) {
            $email = trim($email);
            if (empty($email)) {
                continue;
            }

            try {
                $mail = new PHPMailer(true);
                
                // SMTP設定
                $mail->isSMTP();
                $mail->Host = $this->config['smtp']['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $this->config['smtp']['username'];
                $mail->Password = $this->config['smtp']['password'];
                $mail->Port = $this->config['smtp']['port'];
                
                // 暗号化設定
                if (!empty($this->config['smtp']['encryption'])) {
                    $mail->SMTPSecure = $this->config['smtp']['encryption'];
                }
                
                // 文字コード設定
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                
                // 送信元
                $mail->setFrom(
                    $this->config['smtp']['from_email'],
                    $this->config['smtp']['from_name']
                );
                
                // 宛先
                $mail->addAddress($email);
                
                // 件名と本文
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->isHTML(false); // テキストメール
                
                // 送信
                $mail->send();
                Logger::logEmailSuccess('EmailNotifier::sendViaSMTP', $email, 'SMTP');
                
            } catch (Exception $e) {
                Logger::logEmailError('EmailNotifier::sendViaSMTP', $email, 'PHPMailer Error: ' . $mail->ErrorInfo);
                $allSuccess = false;
            }
        }
        
        return $allSuccess;
    }

    /**
     * mail()関数でメールを送信
     * 
     * @param array $recipients 受信者メールアドレスの配列
     * @param string $subject 件名
     * @param string $body 本文
     * @return bool 送信成功時true
     */
    private function sendViaMail($recipients, $subject, $body) {
        $allSuccess = true;
        
        // メールヘッダー
        $headers = [
            'From: ' . $this->config['mail']['from_name'] . ' <' . $this->config['mail']['from_email'] . '>',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        $headersString = implode("\r\n", $headers);

        foreach ($recipients as $email) {
            $email = trim($email);
            if (empty($email)) {
                continue;
            }

            // メール送信
            $result = @mail($email, $subject, $body, $headersString);
            
            if (!$result) {
                Logger::logEmailError('EmailNotifier::sendViaMail', $email, 'Mail function returned false');
                $allSuccess = false;
            } else {
                Logger::logEmailSuccess('EmailNotifier::sendViaMail', $email, 'mail()');
            }
        }
        
        return $allSuccess;
    }
}
