<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Submission.php';
require_once __DIR__ . '/../../includes/inform/Form.php';

$auth = new Auth();
$auth->requireLogin();

$submissionModel = new Submission();
$formModel = new Form();
$error = '';
$success = '';

// 送信IDの取得
$submissionId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$submissionId) {
    header('Location: messages.php');
    exit;
}

// ステータス更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'セッションが無効です。ページを再読み込みしてください。';
    } else {
        try {
            $submissionModel->updateStatus($submissionId, $_POST['status']);
            $success = 'ステータスを更新しました。';
        } catch (Exception $e) {
            $error = 'ステータスの更新に失敗しました: ' . $e->getMessage();
        }
    }
}

// 送信データを取得
$submission = null;
$form = null;
try {
    $submission = $submissionModel->getById($submissionId);
    if (!$submission) {
        header('Location: messages.php');
        exit;
    }

    // フォーム情報を取得
    $form = $formModel->getById($submission['form_id']);
} catch (Exception $e) {
    $error = '送信データの取得に失敗しました: ' . $e->getMessage();
    error_log('Inform message-detail.php エラー: ' . $e->getMessage());
}

$currentUser = $auth->getCurrentUser();

// ステータスラベル
$statusLabels = [
    Submission::STATUS_NEW => '新規',
    Submission::STATUS_IN_PROGRESS => '対応中',
    Submission::STATUS_RESOLVED => '解決済み'
];

// ステータスカラー
$statusColors = [
    Submission::STATUS_NEW => 'bg-blue-100 text-blue-800',
    Submission::STATUS_IN_PROGRESS => 'bg-yellow-100 text-yellow-800',
    Submission::STATUS_RESOLVED => 'bg-green-100 text-green-800'
];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メッセージ詳細 - Inform - Hajime CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/admin-nordic.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../index.php" class="text-xl font-bold text-gray-800">Hajime CMS</a>
                    <span class="ml-4 text-gray-400">|</span>
                    <span class="ml-4 text-lg text-gray-600">Inform</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo SITE_URL; ?>" target="_blank" class="text-gray-600 hover:text-gray-800">
                        サイトを表示
                    </a>
                    <span class="text-gray-600">
                        <?php echo htmlspecialchars($currentUser['username']); ?>
                    </span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">
                        ログアウト
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="messages.php" class="text-blue-600 hover:text-blue-800">
                ← メッセージ一覧に戻る
            </a>
        </div>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">メッセージ詳細 #<?php echo $submissionId; ?></h2>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($submission): ?>
            <!-- 送信情報 -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">送信情報</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="nordic-card">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            フォーム名
                        </label>
                        <p class="text-gray-900">
                            <?php echo htmlspecialchars($form['name'] ?? 'N/A'); ?>
                        </p>
                    </div>

                    <div class="nordic-card">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            送信日時
                        </label>
                        <p class="text-gray-900">
                            <?php echo date('Y年m月d日 H:i:s', strtotime($submission['created_at'])); ?>
                        </p>
                    </div>

                    <div class="nordic-card">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            IPアドレス
                        </label>
                        <p class="text-gray-900">
                            <?php echo htmlspecialchars($submission['ip_address'] ?? 'N/A'); ?>
                        </p>
                    </div>

                    <div class="nordic-card">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            現在のステータス
                        </label>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?php echo $statusColors[$submission['status']]; ?>">
                            <?php echo htmlspecialchars($statusLabels[$submission['status']]); ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($submission['user_agent'])): ?>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            ユーザーエージェント
                        </label>
                        <p class="text-gray-900 text-sm break-all">
                            <?php echo htmlspecialchars($submission['user_agent']); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ステータス変更 -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">ステータス変更</h3>

                <form method="POST" class="flex items-end space-x-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                    <div class="flex-1">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            新しいステータス
                        </label>
                        <select
                            id="status"
                            name="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                                <option
                                    value="<?php echo $statusValue; ?>"
                                    <?php echo ($statusValue === $submission['status']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($statusLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        更新
                    </button>
                </form>
            </div>

            <!-- 送信データ -->
            <div class="nordic-card">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">送信内容</h3>

                <?php if (empty($submission['data'])): ?>
                    <p class="text-gray-500">送信データがありません。</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($submission['data'] as $fieldName => $fieldValue): ?>
                            <div class="border-b border-gray-200 pb-4 last:border-b-0">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <?php echo htmlspecialchars($fieldName); ?>
                                </label>
                                <div class="text-gray-900 whitespace-pre-wrap break-words">
                                    <?php
                                    if (is_array($fieldValue)) {
                                        // 配列の場合（チェックボックスなど）
                                        echo htmlspecialchars(implode(', ', $fieldValue));
                                    } else {
                                        // 文字列の場合
                                        echo htmlspecialchars($fieldValue);
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>