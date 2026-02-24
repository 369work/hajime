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

// 削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'セッションが無効です。ページを再読み込みしてください。';
    } else {
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            try {
                $submissionModel->delete((int)$_POST['id']);
                $success = 'メッセージを削除しました。';
            } catch (Exception $e) {
                $error = 'メッセージの削除に失敗しました: ' . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'update_status' && isset($_POST['id']) && isset($_POST['status'])) {
            try {
                $submissionModel->updateStatus((int)$_POST['id'], $_POST['status']);
                $success = 'ステータスを更新しました。';
            } catch (Exception $e) {
                $error = 'ステータスの更新に失敗しました: ' . $e->getMessage();
            }
        }
    }
}

// フィルタ条件の取得
$filters = [];
if (!empty($_GET['form_id'])) {
    $filters['form_id'] = (int)$_GET['form_id'];
}
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'] . ' 23:59:59';
}

// 送信一覧を取得
$submissions = [];
try {
    $submissions = $submissionModel->getAll($filters);
} catch (Exception $e) {
    $error = '送信一覧の取得に失敗しました: ' . $e->getMessage();
    error_log('Inform messages.php エラー: ' . $e->getMessage());
}

// フォーム一覧を取得（フィルタ用）
$forms = [];
try {
    $forms = $formModel->getAll();
} catch (Exception $e) {
    error_log('Inform messages.php フォーム取得エラー: ' . $e->getMessage());
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
    <title>メッセージ管理 - Inform - Hajime CMS</title>
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
            <a href="index.php" class="text-blue-600 hover:text-blue-800">
                ← フォーム一覧に戻る
            </a>
        </div>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">メッセージ管理</h2>
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

        <!-- フィルタ -->
        <div class="nordic-card mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">フィルタ</h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="form_id" class="block text-sm font-medium text-gray-700 mb-1">
                        フォーム
                    </label>
                    <select
                        id="form_id"
                        name="form_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">すべて</option>
                        <?php foreach ($forms as $form): ?>
                            <option
                                value="<?php echo $form['id']; ?>"
                                <?php echo (isset($filters['form_id']) && $filters['form_id'] == $form['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($form['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        ステータス
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">すべて</option>
                        <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                            <option
                                value="<?php echo $statusValue; ?>"
                                <?php echo (isset($filters['status']) && $filters['status'] === $statusValue) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($statusLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">
                        開始日
                    </label>
                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">
                        終了日
                    </label>
                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-4 flex justify-end space-x-2">
                    <a
                        href="messages.php"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                        クリア
                    </a>
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        フィルタを適用
                    </button>
                </div>
            </form>
        </div>

        <!-- 送信一覧 -->
        <div class="nordic-card overflow-hidden !p-0">
            <?php if (empty($submissions)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p>メッセージがありません。</p>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                フォーム名
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                送信日時
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ステータス
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                IPアドレス
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    #<?php echo $submission['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($submission['form_name'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('Y/m/d H:i', strtotime($submission['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColors[$submission['status']]; ?>">
                                        <?php echo htmlspecialchars($statusLabels[$submission['status']]); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($submission['ip_address'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a
                                            href="message-detail.php?id=<?php echo $submission['id']; ?>"
                                            class="text-blue-600 hover:text-blue-900">
                                            詳細
                                        </a>

                                        <!-- ステータス変更ドロップダウン -->
                                        <select
                                            onchange="updateStatus(<?php echo $submission['id']; ?>, this.value)"
                                            class="text-sm border border-gray-300 rounded px-2 py-1">
                                            <option value="">ステータス変更</option>
                                            <?php foreach ($statusLabels as $statusValue => $statusLabel): ?>
                                                <?php if ($statusValue !== $submission['status']): ?>
                                                    <option value="<?php echo $statusValue; ?>">
                                                        <?php echo htmlspecialchars($statusLabel); ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>

                                        <button
                                            onclick="deleteSubmission(<?php echo $submission['id']; ?>)"
                                            class="text-red-600 hover:text-red-900">
                                            削除
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- 非表示のフォーム（ステータス更新・削除用） -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
        <input type="hidden" name="action" id="actionInput">
        <input type="hidden" name="id" id="idInput">
        <input type="hidden" name="status" id="statusInput">
    </form>

    <script>
        function updateStatus(id, status) {
            if (!status) return;

            if (confirm('ステータスを変更しますか？')) {
                document.getElementById('actionInput').value = 'update_status';
                document.getElementById('idInput').value = id;
                document.getElementById('statusInput').value = status;
                document.getElementById('actionForm').submit();
            }
        }

        function deleteSubmission(id) {
            if (confirm('本当に削除しますか？この操作は取り消せません。')) {
                document.getElementById('actionInput').value = 'delete';
                document.getElementById('idInput').value = id;
                document.getElementById('actionForm').submit();
            }
        }
    </script>
</body>

</html>