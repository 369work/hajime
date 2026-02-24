<?php

/**
 * タグ管理画面
 * 一覧表示・作成・削除を1ページ内で処理する。
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Tag.php';

$auth = new Auth();
$auth->requireLogin();

$tagModel = new Tag();
$errors = [];
$success = '';

// 削除処理
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    try {
        $tagModel->delete($deleteId);
        header('Location: tags.php?success=deleted');
        exit;
    } catch (Exception $e) {
        $errors[] = '削除に失敗しました: ' . $e->getMessage();
    }
}

// 作成処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $errors[] = '不正なリクエストです。もう一度お試しください。';
    } else {
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            $errors[] = 'タグ名を入力してください。';
        }

        if (empty($errors)) {
            try {
                $tagModel->create($name);
                header('Location: tags.php?success=created');
                exit;
            } catch (Exception $e) {
                $errors[] = 'エラーが発生しました: ' . $e->getMessage();
            }
        }
    }
}

// 成功メッセージ
if (isset($_GET['success'])) {
    $successMap = [
        'created' => 'タグを作成しました。',
        'deleted' => 'タグを削除しました。',
    ];
    $success = $successMap[$_GET['success']] ?? '';
}

$tags = $tagModel->getAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タグ管理 - Hajime CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/admin-nordic.css">
</head>

<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="text-xl font-bold text-gray-800">Hajime CMS</a>
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="index.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">ページ</a>
                        <a href="categories.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">カテゴリー</a>
                        <a href="tags.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">タグ</a>
                        <a href="menus.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">メニュー</a>
                        <a href="settings.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">設定</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo SITE_URL; ?>" target="_blank" class="text-sm text-gray-600 hover:text-gray-800">サイトを表示</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 作成フォーム -->
            <div class="lg:col-span-1">
                <div class="nordic-card">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">新規タグ</h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">タグ名 <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" required
                                placeholder="例: 重要"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">スラッグは自動的に生成されます</p>
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            作成
                        </button>
                    </form>
                </div>
            </div>

            <!-- タグ一覧 -->
            <div class="lg:col-span-2">
                <div class="nordic-card overflow-hidden !p-0">
                    <div class="px-6 py-4 border-b bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-800">タグ一覧</h2>
                    </div>

                    <?php if (empty($tags)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <p>タグはまだありません。</p>
                        </div>
                    <?php else: ?>
                        <div class="p-6 flex flex-wrap gap-3">
                            <?php foreach ($tags as $tag): ?>
                                <div class="inline-flex items-center gap-2 bg-gray-100 border border-gray-200 rounded-full px-4 py-2 group hover:bg-gray-200 transition-colors">
                                    <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($tag['name']); ?></span>
                                    <span class="text-xs text-gray-500">(<?php echo $tag['page_count']; ?>)</span>
                                    <a href="?delete=<?php echo $tag['id']; ?>"
                                        onclick="return confirm('タグ「<?php echo htmlspecialchars($tag['name']); ?>」を削除しますか？')"
                                        class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
                                        aria-label="<?php echo htmlspecialchars($tag['name']); ?>を削除">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>