<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Page.php';

$auth = new Auth();
$auth->requireLogin();

$pageModel = new Page();
$pageId = $_GET['id'] ?? null;
$page = null;
$errors = [];
$success = '';

// 編集モード
if ($pageId) {
    $page = $pageModel->getPageById($pageId);
    if (!$page) {
        header('Location: index.php');
        exit;
    }
}

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $template = $_POST['template'] ?? 'default';
    $status = $_POST['status'] ?? 'draft';
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');

    // バリデーション
    if (empty($title)) {
        $errors[] = 'タイトルは必須です。';
    }

    if (empty($slug)) {
        $slug = $pageModel->generateUniqueSlug($title, $pageId);
    } else {
        // スラッグの重複チェック
        if ($pageModel->isSlugExists($slug, $pageId)) {
            $errors[] = 'このスラッグは既に使用されています。';
        }
    }

    if (empty($errors)) {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'template' => $template,
            'status' => $status,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription
        ];

        try {
            if ($pageId) {
                $pageModel->updatePage($pageId, $data);
                $success = 'ページを更新しました。';
                $page = $pageModel->getPageById($pageId);
            } else {
                $newId = $pageModel->createPage($data);
                $success = 'ページを作成しました。';
                header('Location: page-edit.php?id=' . $newId . '&success=created');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'エラーが発生しました: ' . $e->getMessage();
        }
    }
}

// 成功メッセージ
if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $success = 'ページを作成しました。';
}

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageId ? 'ページ編集' : '新規ページ作成'; ?> - Hajime CMS</title>
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="index.php" class="text-blue-600 hover:text-blue-800">
                ← ダッシュボードに戻る
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                <?php echo $pageId ? 'ページ編集' : '新規ページ作成'; ?>
            </h2>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                        タイトル <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        required 
                        value="<?php echo htmlspecialchars($page['title'] ?? ''); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-required="true"
                    >
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
                        スラッグ
                        <span class="text-gray-500 text-xs">(空白の場合は自動生成されます)</span>
                    </label>
                    <input 
                        type="text" 
                        id="slug" 
                        name="slug" 
                        value="<?php echo htmlspecialchars($page['slug'] ?? ''); ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        pattern="[a-z0-9-]+"
                        title="英小文字、数字、ハイフンのみ使用可能です"
                    >
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                        コンテンツ
                    </label>
                    <textarea 
                        id="content" 
                        name="content" 
                        rows="15"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                    ><?php echo htmlspecialchars($page['content'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">HTMLタグが使用できます</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="template" class="block text-sm font-medium text-gray-700 mb-1">
                            テンプレート
                        </label>
                        <select 
                            id="template" 
                            name="template"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="default" <?php echo ($page['template'] ?? 'default') === 'default' ? 'selected' : ''; ?>>
                                デフォルト
                            </option>
                            <option value="modern" <?php echo ($page['template'] ?? '') === 'modern' ? 'selected' : ''; ?>>
                                モダン
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            ステータス
                        </label>
                        <select 
                            id="status" 
                            name="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="draft" <?php echo ($page['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>
                                下書き
                            </option>
                            <option value="published" <?php echo ($page['status'] ?? '') === 'published' ? 'selected' : ''; ?>>
                                公開
                            </option>
                        </select>
                    </div>
                </div>

                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">SEO設定</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-1">
                                メタタイトル
                            </label>
                            <input 
                                type="text" 
                                id="meta_title" 
                                name="meta_title" 
                                value="<?php echo htmlspecialchars($page['meta_title'] ?? ''); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">
                                メタディスクリプション
                            </label>
                            <textarea 
                                id="meta_description" 
                                name="meta_description" 
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            ><?php echo htmlspecialchars($page['meta_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a 
                        href="index.php" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        キャンセル
                    </a>
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                    >
                        <?php echo $pageId ? '更新' : '作成'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
