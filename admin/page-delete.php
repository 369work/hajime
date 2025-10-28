<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Page.php';

$auth = new Auth();
$auth->requireLogin();

$pageId = $_GET['id'] ?? null;

if (!$pageId) {
    header('Location: index.php');
    exit;
}

$pageModel = new Page();
$page = $pageModel->getPageById($pageId);

if (!$page) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        $pageModel->deletePage($pageId);
        header('Location: index.php?deleted=1');
        exit;
    } catch (Exception $e) {
        $error = 'エラーが発生しました: ' . $e->getMessage();
    }
}

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ページ削除 - Hajime CMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold text-gray-800">Hajime CMS</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">
                        <?php echo htmlspecialchars($currentUser['username']); ?>
                    </span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">
                        ログアウト
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="index.php" class="text-blue-600 hover:text-blue-800">
                ← ダッシュボードに戻る
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">ページの削除</h2>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            以下のページを削除しようとしています。この操作は取り消せません。
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-6 p-4 bg-gray-50 rounded-md">
                <dl class="space-y-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">タイトル</dt>
                        <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($page['title']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">スラッグ</dt>
                        <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($page['slug']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">ステータス</dt>
                        <dd class="text-sm text-gray-900">
                            <?php echo $page['status'] === 'published' ? '公開' : '下書き'; ?>
                        </dd>
                    </div>
                </dl>
            </div>

            <form method="POST" action="" class="flex justify-end space-x-4">
                <a 
                    href="index.php" 
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    キャンセル
                </a>
                <button 
                    type="submit" 
                    name="confirm"
                    value="1"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
                >
                    削除する
                </button>
            </form>
        </div>
    </div>
</body>
</html>
