<?php
/**
 * フォーム送信処理エンドポイント
 * 
 * フォーム送信を処理し、バリデーション、スパム対策、
 * レート制限をチェックしてデータベースに保存します。
 * 
 * 要件: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 9.1, 9.2, 9.5
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';
require_once __DIR__ . '/../../includes/inform/FormField.php';
require_once __DIR__ . '/../../includes/inform/Submission.php';
require_once __DIR__ . '/../../includes/inform/AntiSpam.php';
require_once __DIR__ . '/../../includes/inform/EmailNotifier.php';
require_once __DIR__ . '/../../includes/inform/ErrorHandler.php';
require_once __DIR__ . '/../../includes/inform/Logger.php';

// CORSヘッダーを設定
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * JSONレスポンスを返す
 */
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

/**
 * ユーザー入力をサニタイズ（XSS対策）
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// POSTリクエストのみ受け付ける
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Logger::logSecurityError('INVALID_METHOD', 'Non-POST request to submit endpoint');
    sendJsonResponse(false, ErrorHandler::getSecurityError('invalid_request_method'));
}

try {
    // 1. CSRFトークンを検証（要件 9.1）
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
        Logger::logSecurityError('CSRF', 'Invalid or missing CSRF token');
        sendJsonResponse(false, ErrorHandler::getSecurityError('csrf_token_invalid'));
    }

    // 2. フォームIDを取得
    $formId = $_POST['form_id'] ?? '';
    if (empty($formId) || !is_numeric($formId)) {
        Logger::logSecurityError('INVALID_INPUT', 'Invalid form ID provided');
        sendJsonResponse(false, ErrorHandler::getSecurityError('invalid_form_id'));
    }
    $formId = (int)$formId;

    // 3. フォームを取得
    $formModel = new Form();
    $form = $formModel->getById($formId);
    
    if (!$form) {
        Logger::logError('submit.php', 'Form not found: ' . $formId);
        sendJsonResponse(false, ErrorHandler::getSystemError('form_not_found'));
    }

    // 4. フォームフィールドを取得
    $fields = $formModel->getFields($formId);
    
    if (empty($fields)) {
        Logger::logError('submit.php', 'No fields configured for form: ' . $formId);
        sendJsonResponse(false, ErrorHandler::getSystemError('fields_not_configured'));
    }

    // 5. レート制限をチェック（要件 9.5）
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $submissionModel = new Submission();
    
    if (!$submissionModel->checkRateLimit($ipAddress)) {
        Logger::logSecurityError('RATE_LIMIT', 'Rate limit exceeded', [
            'ip_address' => $ipAddress,
            'form_id' => $formId
        ]);
        sendJsonResponse(false, ErrorHandler::getSecurityError('rate_limit_exceeded'));
    }

    // 6. スパム対策チャレンジを検証（要件 5.3）
    $challengeId = $_POST['challenge_id'] ?? '';
    $challengeAnswer = $_POST['challenge_answer'] ?? '';
    
    if (empty($challengeId) || empty($challengeAnswer)) {
        Logger::logSecurityError('SPAM', 'Missing spam challenge data');
        sendJsonResponse(false, ErrorHandler::getValidationError('spam_challenge_missing'));
    }
    
    $antiSpam = new AntiSpam();
    if (!$antiSpam->verify($challengeId, $challengeAnswer)) {
        Logger::logSecurityError('SPAM', 'Failed spam challenge verification', [
            'ip_address' => $ipAddress,
            'form_id' => $formId
        ]);
        sendJsonResponse(false, ErrorHandler::getValidationError('spam_challenge_failed'));
    }

    // 7. フォームフィールドをバリデーション（要件 5.1, 5.2）
    $formFieldModel = new FormField();
    $submissionData = [];
    $validationErrors = [];

    foreach ($fields as $field) {
        $fieldName = $field['name'];
        $fieldValue = $_POST[$fieldName] ?? '';
        
        // ユーザー入力をサニタイズ（XSS対策 - 要件 9.2）
        $fieldValue = sanitizeInput($fieldValue);
        
        // バリデーション実行
        $validation = $formFieldModel->validate($fieldValue, $field);
        
        if (!$validation['valid']) {
            $validationErrors[] = $validation['error'];
        }
        
        // 送信データに追加
        $submissionData[$fieldName] = $fieldValue;
    }

    // バリデーションエラーがある場合（要件 5.4）
    if (!empty($validationErrors)) {
        Logger::logInfo('submit.php', 'Validation failed for form: ' . $formId);
        sendJsonResponse(false, implode('<br>', $validationErrors));
    }

    // 8. データベースに送信を保存（要件 5.5）
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $submissionId = $submissionModel->create(
        $formId,
        $submissionData,
        $ipAddress,
        $userAgent
    );

    Logger::logInfo('submit.php', "Submission created successfully: ID {$submissionId}, Form {$formId}");

    // 9. メール通知を送信（要件 8.1）
    // メール送信が失敗しても送信自体は成功として処理（要件 8.5）
    $emailNotifier = new EmailNotifier();
    $emailNotifier->sendNotification($formId, $submissionId);

    // 10. 成功レスポンスを返す（要件 5.6）
    $successMessage = $form['settings']['success_message'] ?? 'お問い合わせありがとうございます。';
    $redirectUrl = $form['settings']['redirect_url'] ?? null;
    
    $responseData = [];
    if (!empty($redirectUrl)) {
        $responseData['redirect_url'] = $redirectUrl;
    }
    
    // CSRFトークンを再生成（セキュリティのため）
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    sendJsonResponse(true, $successMessage, $responseData);

} catch (Exception $e) {
    // エラーをログに記録
    Logger::logError('submit.php', 'Unexpected error: ' . $e->getMessage());
    Logger::logError('submit.php', 'Stack trace: ' . $e->getTraceAsString());
    
    // ユーザーには一般的なエラーメッセージを表示
    sendJsonResponse(false, ErrorHandler::getSystemError('database_error'));
}
