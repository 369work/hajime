<?php
/**
 * 埋め込みフォーム表示エンドポイント
 * 
 * フォームIDをクエリパラメータで受け取り、
 * HTMLフォームをレンダリングして返します。
 * 
 * 要件: 4.1, 4.3, 4.4, 4.5, 9.4
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';
require_once __DIR__ . '/../../includes/inform/FormField.php';
require_once __DIR__ . '/../../includes/inform/AntiSpam.php';

// CORSヘッダーを設定（外部サイトからのアクセスを許可）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: text/html; charset=UTF-8');

// フォームIDを取得
$formId = $_GET['form_id'] ?? '';

if (empty($formId) || !is_numeric($formId)) {
    echo '<div style="padding: 20px; background-color: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c00;">';
    echo 'エラー: 無効なフォームIDです';
    echo '</div>';
    exit;
}

try {
    // フォームを取得
    $formModel = new Form();
    $form = $formModel->getById((int)$formId);
    
    if (!$form) {
        echo '<div style="padding: 20px; background-color: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c00;">';
        echo 'エラー: フォームが見つかりません';
        echo '</div>';
        exit;
    }
    
    // フォームフィールドを取得
    $fields = $formModel->getFields((int)$formId);
    
    // スパム対策チャレンジを生成
    $antiSpam = new AntiSpam();
    $challenge = $antiSpam->generateChallenge();
    
    // CSRFトークンを生成
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];
    
} catch (Exception $e) {
    error_log('Embed form error: ' . $e->getMessage());
    echo '<div style="padding: 20px; background-color: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c00;">';
    echo 'エラー: フォームの読み込みに失敗しました';
    echo '</div>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inform Custom Styles -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(SITE_URL . '/assets/css/inform.css', ENT_QUOTES, 'UTF-8'); ?>">
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div class="max-w-2xl mx-auto">
        <!-- フォームタイトルと説明 -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                <?php echo htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8'); ?>
            </h2>
            <?php if (!empty($form['description'])): ?>
                <p class="text-gray-600">
                    <?php echo nl2br(htmlspecialchars($form['description'], ENT_QUOTES, 'UTF-8')); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- 成功メッセージ -->
        <div id="success-message" class="hidden mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
            <?php 
            $successMessage = $form['settings']['success_message'] ?? 'お問い合わせありがとうございます。';
            echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8');
            ?>
        </div>

        <!-- エラーメッセージ -->
        <div id="error-message" class="hidden mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md"></div>

        <!-- フォーム -->
        <form id="inform-form" method="POST" action="<?php echo htmlspecialchars(SITE_URL . '/public/inform/submit.php', ENT_QUOTES, 'UTF-8'); ?>" class="bg-white p-6 rounded-lg shadow-md">
            <!-- 隠しフィールド -->
            <input type="hidden" name="form_id" value="<?php echo htmlspecialchars($formId, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="challenge_id" value="<?php echo htmlspecialchars($challenge['challenge_id'], ENT_QUOTES, 'UTF-8'); ?>">

            <!-- フォームフィールドをレンダリング -->
            <?php
            $formFieldModel = new FormField();
            foreach ($fields as $field) {
                echo $formFieldModel->render($field);
            }
            ?>

            <!-- スパム対策チャレンジ -->
            <div class="mb-6 p-4 bg-gray-50 border border-gray-300 rounded-md">
                <label class="block text-gray-700 font-bold mb-2">
                    スパム対策確認 <span class="text-red-500">*</span>
                </label>
                <p class="text-sm text-gray-600 mb-3">
                    画像と同じ文字を入力してください
                </p>
                <img 
                    src="<?php echo htmlspecialchars(SITE_URL . '/public/inform/captcha.php?challenge_id=' . $challenge['challenge_id'], ENT_QUOTES, 'UTF-8'); ?>" 
                    alt="スパム対策画像" 
                    class="mb-3 border border-gray-300 rounded"
                >
                <input 
                    type="text" 
                    name="challenge_answer" 
                    required 
                    placeholder="画像の文字を入力"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <!-- 送信ボタン -->
            <div class="flex justify-end">
                <button 
                    type="submit" 
                    class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                >
                    送信
                </button>
            </div>
        </form>
    </div>

    <script>
        // フォーム送信処理
        document.getElementById('inform-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            
            // ボタンを無効化
            submitButton.disabled = true;
            submitButton.textContent = '送信中...';
            
            // メッセージを非表示
            successMessage.classList.add('hidden');
            errorMessage.classList.add('hidden');
            
            // AJAX送信
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 成功時
                    successMessage.classList.remove('hidden');
                    form.reset();
                    
                    // リダイレクトURLが設定されている場合
                    if (data.redirect_url) {
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    }
                } else {
                    // エラー時
                    errorMessage.textContent = data.message || 'エラーが発生しました';
                    errorMessage.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.textContent = 'ネットワークエラーが発生しました';
                errorMessage.classList.remove('hidden');
            })
            .finally(() => {
                // ボタンを有効化
                submitButton.disabled = false;
                submitButton.textContent = '送信';
            });
        });
    </script>
</body>
</html>
