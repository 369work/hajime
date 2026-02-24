<?php

/**
 * カテゴリー管理画面
 * 一覧表示・作成・編集・削除を1ページ内で処理する。
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Category.php';

$auth = new Auth();
$auth->requireLogin();

$categoryModel = new Category();
$errors = [];
$success = '';
$editCategory = null;

// 編集モード判定
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
if ($editId > 0) {
    $editCategory = $categoryModel->getById($editId);
}

// 削除処理
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    try {
        $pageCount = $categoryModel->getPageCount($deleteId);
        if ($pageCount > 0) {
            $errors[] = "このカテゴリーには{$pageCount}件のページが割り当てられています。先にページのカテゴリーを変更してください。";
        } else {
            $categoryModel->delete($deleteId);
            header('Location: categories.php?success=deleted');
            exit;
        }
    } catch (Exception $e) {
        $errors[] = '削除に失敗しました: ' . $e->getMessage();
    }
}

// 保存処理（作成 / 更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $errors[] = '不正なリクエストです。もう一度お試しください。';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parentId = (int) ($_POST['parent_id'] ?? 0);
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $postEditId = (int) ($_POST['edit_id'] ?? 0);

        if (empty($name)) {
            $errors[] = 'カテゴリー名を入力してください。';
        }

        if (empty($errors)) {
            $data = [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'parent_id'   => $parentId,
                'sort_order'  => $sortOrder,
            ];

            try {
                if ($postEditId > 0) {
                    $categoryModel->update($postEditId, $data);
                    $success = 'カテゴリーを更新しました。';
                } else {
                    $categoryModel->create($data);
                    $success = 'カテゴリーを作成しました。';
                }
                // リダイレクトしてPOSTを消す
                header('Location: categories.php?success=' . ($postEditId ? 'updated' : 'created'));
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
        'created' => 'カテゴリーを作成しました。',
        'updated' => 'カテゴリーを更新しました。',
        'deleted' => 'カテゴリーを削除しました。',
    ];
    $success = $successMap[$_GET['success']] ?? '';
}

$categories = $categoryModel->getFlatList();
$categoriesTree = $categoryModel->getTree();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カテゴリー管理 - Hajime CMS</title>
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
                        <a href="categories.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">カテゴリー</a>
                        <a href="tags.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">タグ</a>
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
            <!-- 作成 / 編集フォーム -->
            <div class="lg:col-span-1">
                <div class="nordic-card">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        <?php echo $editCategory ? 'カテゴリーを編集' : '新規カテゴリー'; ?>
                    </h2>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                        <input type="hidden" name="edit_id" value="<?php echo $editCategory['id'] ?? 0; ?>">

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前 <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" required
                                value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">スラッグ</label>
                            <input type="text" id="slug" name="slug"
                                value="<?php echo htmlspecialchars($editCategory['slug'] ?? ''); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">空の場合は名前から自動生成されます</p>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">説明</label>
                            <textarea id="description" name="description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">親カテゴリー</label>
                            <select id="parent_id" name="parent_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="0">なし（トップレベル）</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php if ($editCategory && $cat['id'] == $editCategory['id']) continue; ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo (($editCategory['parent_id'] ?? 0) == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['indented_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">表示順</label>
                            <input type="number" id="sort_order" name="sort_order"
                                value="<?php echo $editCategory['sort_order'] ?? 0; ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <?php echo $editCategory ? '更新' : '作成'; ?>
                            </button>
                            <?php if ($editCategory): ?>
                                <a href="categories.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">キャンセル</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- カテゴリー一覧 -->
            <div class="lg:col-span-2">
                <div class="nordic-card overflow-hidden !p-0">
                    <div class="px-6 py-4 border-b bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-800">カテゴリー一覧</h2>
                    </div>

                    <?php if (empty($categories)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <p>カテゴリーはまだありません。</p>
                        </div>
                    <?php else: ?>
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">名前</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">スラッグ</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ページ数</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($categories as $cat): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($cat['indented_name']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-500"><?php echo htmlspecialchars($cat['slug']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-500"><?php echo $categoryModel->getPageCount((int) $cat['id']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                            <a href="?edit=<?php echo $cat['id']; ?>"
                                                class="text-blue-600 hover:text-blue-800 text-sm">編集</a>
                                            <a href="?delete=<?php echo $cat['id']; ?>"
                                                onclick="return confirm('このカテゴリーを削除しますか？')"
                                                class="text-red-600 hover:text-red-800 text-sm">削除</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>