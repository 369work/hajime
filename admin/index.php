<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Page.php';

$auth = new Auth();
$auth->requireLogin();

$pageModel = new Page();
$pages = $pageModel->getAllPages();
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード - Hajime CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/admin-nordic.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-6">
                    <h1 class="text-xl font-bold text-gray-800">Hajime CMS</h1>
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="index.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">ページ</a>
                        <a href="categories.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">カテゴリー</a>
                        <a href="tags.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">タグ</a>
                        <a href="menus.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">メニュー</a>
                        <a href="settings.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">設定</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo SITE_URL; ?>" target="_blank" class="text-gray-600 hover:text-gray-800">
                        サイトを表示
                    </a>
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Inform セクション -->
        <div class="mb-8">
            <div class="nordic-card">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Inform - お問い合わせフォーム</h3>
                        <p class="text-sm text-gray-600">埋め込み可能なお問い合わせフォームを作成・管理します</p>
                    </div>
                    <a
                        href="inform/index.php"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                        フォーム管理
                    </a>
                </div>
            </div>
        </div>

        <!-- カテゴリー・タグ管理セクション -->
        <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="nordic-card">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">カテゴリー管理</h3>
                        <p class="text-sm text-gray-600">記事を分類するカテゴリーを作成・編集</p>
                    </div>
                    <a href="categories.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors">
                        管理
                    </a>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">タグ管理</h3>
                        <p class="text-sm text-gray-600">記事に付与するタグを作成・削除</p>
                    </div>
                    <a href="tags.php" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors">
                        管理
                    </a>
                </div>
            </div>
        </div>

        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">ページ一覧</h2>
            <a
                href="page-edit.php"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                新規ページ作成
            </a>
        </div>

        <div class="nordic-card overflow-hidden !p-0">
            <?php if (empty($pages)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p>ページがまだ作成されていません。</p>
                    <a href="page-edit.php" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        最初のページを作成する
                    </a>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                タイトル
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                スラッグ
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ステータス
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                更新日時
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                操作
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($page['title']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($page['slug']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($page['status'] === 'published'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            公開
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            下書き
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('Y/m/d H:i', strtotime($page['updated_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <a href="page-edit.php?id=<?php echo $page['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        編集
                                    </a>
                                    <a
                                        href="page-delete.php?id=<?php echo $page['id']; ?>"
                                        class="text-red-600 hover:text-red-900"
                                        onclick="return confirm('本当に削除しますか?');">
                                        削除
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>