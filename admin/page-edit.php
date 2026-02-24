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

require_once __DIR__ . '/../includes/Category.php';
require_once __DIR__ . '/../includes/Tag.php';

$categoryModel = new Category();
$tagModel = new Tag();
$categories = $categoryModel->getFlatList();
$tags = $tagModel->getAll();
$relatedTagIds = [];

// 編集モード
if ($pageId) {
    $page = $pageModel->getPageById($pageId);
    if (!$page) {
        header('Location: index.php');
        exit;
    }
    // 関連タグIDを取得
    $relatedTags = $tagModel->getTagsByPageId($pageId);
    $relatedTagIds = array_column($relatedTags, 'id');
}

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $errors[] = '不正なリクエストです。もう一度お試しください。';
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $content = $_POST['content'] ?? '';
        $template = $_POST['template'] ?? 'default';
        $status = $_POST['status'] ?? 'draft';
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $postTags = $_POST['tags'] ?? [];
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
                'category_id' => $categoryId,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription
            ];

            try {
                if ($pageId) {
                    $pageModel->updatePage($pageId, $data);
                    $tagModel->syncPageTags($pageId, $postTags);
                    $success = 'ページを更新しました。';
                    $page = $pageModel->getPageById($pageId);
                    // 関連タグを再取得
                    $relatedTags = $tagModel->getTagsByPageId($pageId);
                    $relatedTagIds = array_column($relatedTags, 'id');
                } else {
                    $newId = $pageModel->createPage($data);
                    $tagModel->syncPageTags($newId, $postTags);
                    $success = 'ページを作成しました。';
                    header('Location: page-edit.php?id=' . $newId . '&success=created');
                    exit;
                }
            } catch (Exception $e) {
                $errors[] = 'エラーが発生しました: ' . $e->getMessage();
            }
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
                    <a href="index.php" class="text-xl font-bold text-gray-800">Hajime CMS</a>
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="index.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">ページ</a>
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="index.php" class="text-blue-600 hover:text-blue-800">
                ← ダッシュボードに戻る
            </a>
        </div>

        <div class="nordic-card">
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
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
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
                        aria-required="true">
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
                        title="英小文字、数字、ハイフンのみ使用可能です">
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">
                        コンテンツ
                    </label>
                    <!-- コンテンツツールバー -->
                    <div class="flex items-center gap-2 mb-2">
                        <button
                            type="button"
                            id="btn-insert-image"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-200 transition-colors"
                            aria-label="画像を挿入">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            画像を挿入
                        </button>
                    </div>
                    <textarea
                        id="content"
                        name="content"
                        rows="15"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"><?php echo htmlspecialchars($page['content'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">HTMLタグが使用できます</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                            カテゴリー
                        </label>
                        <select
                            id="category_id"
                            name="category_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="0">未分類</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"
                                    <?php echo ($page['category_id'] ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['indented_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="template" class="block text-sm font-medium text-gray-700 mb-1">
                            テンプレート
                        </label>
                        <?php
                        // テンプレートディレクトリをスキャン
                        $templatesDir = __DIR__ . '/../templates/';
                        $templates = [];
                        if (is_dir($templatesDir)) {
                            $items = scandir($templatesDir);
                            foreach ($items as $item) {
                                if ($item !== '.' && $item !== '..' && is_dir($templatesDir . $item)) {
                                    $templates[] = $item;
                                }
                            }
                        }
                        ?>
                        <select
                            id="template"
                            name="template"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($templates as $tpl): ?>
                                <option value="<?php echo htmlspecialchars($tpl); ?>" <?php echo ($page['template'] ?? 'default') === $tpl ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($tpl)); ?>
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
                            <option value="draft" <?php echo ($page['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>
                                下書き
                            </option>
                            <option value="published" <?php echo ($page['status'] ?? '') === 'published' ? 'selected' : ''; ?>>
                                公開
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            タグ
                        </label>
                        <div class="bg-gray-50 border border-gray-300 rounded-md p-4 max-h-48 overflow-y-auto">
                            <?php if (empty($tags)): ?>
                                <p class="text-sm text-gray-500">タグがありません。<a href="tags.php" class="text-blue-600 hover:underline">タグ管理</a>から作成してください。</p>
                            <?php else: ?>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <?php foreach ($tags as $tag): ?>
                                        <label class="inline-flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>"
                                                <?php echo in_array($tag['id'], $relatedTagIds) ? 'checked' : ''; ?>
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            <span class="text-sm text-gray-700"><?php echo htmlspecialchars($tag['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
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
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">
                                メタディスクリプション
                            </label>
                            <textarea
                                id="meta_description"
                                name="meta_description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($page['meta_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a
                        href="index.php"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        キャンセル
                    </a>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        <?php echo $pageId ? '更新' : '作成'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 画像挿入モーダル -->
    <div id="image-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="image-modal-title">
        <!-- オーバーレイ -->
        <div id="image-modal-overlay" class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"></div>
        <!-- モーダル本体 -->
        <div class="absolute inset-4 md:inset-10 lg:inset-16 bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden">
            <!-- ヘッダー -->
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                <h3 id="image-modal-title" class="text-lg font-semibold text-gray-800">画像を挿入</h3>
                <button
                    type="button"
                    id="btn-close-modal"
                    class="p-1 text-gray-500 hover:text-gray-800 rounded-md hover:bg-gray-200 transition-colors"
                    aria-label="閉じる">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- タブ -->
            <div class="flex border-b">
                <button
                    type="button"
                    class="modal-tab active px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                    data-tab="library">
                    画像ライブラリ
                </button>
                <button
                    type="button"
                    class="modal-tab px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                    data-tab="upload">
                    新規アップロード
                </button>
            </div>

            <!-- コンテンツ -->
            <div class="flex-1 overflow-auto">
                <!-- 画像ライブラリタブ -->
                <div id="tab-library" class="tab-content p-6">
                    <div id="image-grid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                        <!-- 画像サムネイルが動的に挿入される -->
                    </div>
                    <div id="image-grid-empty" class="hidden text-center py-12 text-gray-500">
                        <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p>アップロードされた画像はありません。</p>
                        <p class="text-sm mt-1">「新規アップロード」タブから画像をアップロードしてください。</p>
                    </div>
                    <div id="image-grid-loading" class="text-center py-12 text-gray-500">
                        <svg class="animate-spin mx-auto w-8 h-8 text-blue-500 mb-3" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p>読み込み中...</p>
                    </div>
                </div>

                <!-- アップロードタブ -->
                <div id="tab-upload" class="tab-content hidden p-6">
                    <div
                        id="drop-zone"
                        class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer"
                        role="button"
                        tabindex="0"
                        aria-label="画像をドラッグ＆ドロップまたはクリックしてアップロード">
                        <svg class="mx-auto w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="text-gray-600 font-medium mb-1">画像をドラッグ＆ドロップ</p>
                        <p class="text-sm text-gray-500">またはクリックしてファイルを選択</p>
                        <p class="text-xs text-gray-400 mt-2">JPEG、PNG、GIF、WebP（最大5MB）</p>
                        <input type="file" id="file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" aria-hidden="true">
                    </div>

                    <!-- alt属性入力 -->
                    <div class="mt-4">
                        <label for="upload-alt-text" class="block text-sm font-medium text-gray-700 mb-1">
                            alt属性（画像の説明）
                        </label>
                        <input
                            type="text"
                            id="upload-alt-text"
                            placeholder="画像の説明を入力してください"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">アクセシビリティのために画像の説明を入力してください</p>
                    </div>

                    <!-- アップロード進捗 -->
                    <div id="upload-progress" class="hidden mt-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                            </div>
                            <span id="progress-text" class="text-sm text-gray-600">0%</span>
                        </div>
                    </div>

                    <!-- アップロード結果 -->
                    <div id="upload-result" class="hidden mt-4 p-4 rounded-lg"></div>
                </div>
            </div>

            <!-- フッター（画像選択時のみ表示） -->
            <div id="modal-footer" class="hidden border-t bg-gray-50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img id="selected-preview" src="" alt="" class="w-12 h-12 object-cover rounded border">
                        <div>
                            <p id="selected-name" class="text-sm font-medium text-gray-800"></p>
                            <p id="selected-size" class="text-xs text-gray-500"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <input
                            type="text"
                            id="insert-alt-text"
                            placeholder="alt属性"
                            class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-48">
                        <button
                            type="button"
                            id="btn-insert-selected"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm font-medium">
                            挿入
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* モーダルタブのアクティブ状態 */
        .modal-tab {
            color: #6b7280;
            border-color: transparent;
        }

        .modal-tab.active {
            color: #2563eb;
            border-color: #2563eb;
        }

        .modal-tab:hover:not(.active) {
            color: #374151;
            border-color: #d1d5db;
        }

        /* 画像グリッドアイテム */
        .image-grid-item {
            position: relative;
            aspect-ratio: 1;
            border: 2px solid transparent;
            border-radius: 0.5rem;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.15s;
        }

        .image-grid-item:hover {
            border-color: #93c5fd;
        }

        .image-grid-item.selected {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px #2563eb;
        }

        .image-grid-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-grid-item .delete-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1;
        }

        .image-grid-item:hover .delete-btn {
            opacity: 1;
        }

        /* ドロップゾーンのドラッグ中スタイル */
        #drop-zone.drag-over {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }
    </style>

    <script>
        'use strict';

        /**
         * 画像挿入モーダルの管理
         */
        const ImageModal = (() => {
            // DOM要素
            const modal = document.getElementById('image-modal');
            const overlay = document.getElementById('image-modal-overlay');
            const btnOpen = document.getElementById('btn-insert-image');
            const btnClose = document.getElementById('btn-close-modal');
            const imageGrid = document.getElementById('image-grid');
            const gridEmpty = document.getElementById('image-grid-empty');
            const gridLoading = document.getElementById('image-grid-loading');
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('file-input');
            const uploadProgress = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const uploadResult = document.getElementById('upload-result');
            const uploadAltText = document.getElementById('upload-alt-text');
            const modalFooter = document.getElementById('modal-footer');
            const selectedPreview = document.getElementById('selected-preview');
            const selectedName = document.getElementById('selected-name');
            const selectedSize = document.getElementById('selected-size');
            const insertAltText = document.getElementById('insert-alt-text');
            const btnInsert = document.getElementById('btn-insert-selected');
            const contentTextarea = document.getElementById('content');
            const tabs = document.querySelectorAll('.modal-tab');

            let selectedImage = null;

            /**
             * 初期化
             */
            const init = () => {
                btnOpen.addEventListener('click', open);
                btnClose.addEventListener('click', close);
                overlay.addEventListener('click', close);
                btnInsert.addEventListener('click', insertImage);

                // タブ切り替え
                tabs.forEach(tab => {
                    tab.addEventListener('click', () => switchTab(tab.dataset.tab));
                });

                // ドラッグ＆ドロップ
                dropZone.addEventListener('click', () => fileInput.click());
                dropZone.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        fileInput.click();
                    }
                });
                dropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropZone.classList.add('drag-over');
                });
                dropZone.addEventListener('dragleave', () => {
                    dropZone.classList.remove('drag-over');
                });
                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('drag-over');
                    if (e.dataTransfer.files.length > 0) {
                        handleFileUpload(e.dataTransfer.files[0]);
                    }
                });
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        handleFileUpload(e.target.files[0]);
                    }
                });

                // Escapeキーで閉じる
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                        close();
                    }
                });
            };

            /**
             * モーダルを開く
             */
            const open = () => {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                loadImages();
            };

            /**
             * モーダルを閉じる
             */
            const close = () => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
                selectedImage = null;
                modalFooter.classList.add('hidden');
            };

            /**
             * タブ切り替え
             */
            const switchTab = (tabName) => {
                tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === tabName));
                document.querySelectorAll('.tab-content').forEach(tc => tc.classList.add('hidden'));
                document.getElementById(`tab-${tabName}`).classList.remove('hidden');
            };

            /**
             * 画像一覧を読み込む
             */
            const loadImages = async () => {
                gridLoading.classList.remove('hidden');
                gridEmpty.classList.add('hidden');
                imageGrid.innerHTML = '';

                try {
                    const response = await fetch('upload.php?action=list');
                    const json = await response.json();

                    gridLoading.classList.add('hidden');

                    if (!json.success || json.data.items.length === 0) {
                        gridEmpty.classList.remove('hidden');
                        return;
                    }

                    json.data.items.forEach(img => {
                        const item = createImageItem(img);
                        imageGrid.appendChild(item);
                    });
                } catch (error) {
                    gridLoading.classList.add('hidden');
                    gridEmpty.classList.remove('hidden');
                    console.error('画像一覧の読み込みに失敗:', error);
                }
            };

            /**
             * 画像グリッドアイテムを作成
             */
            const createImageItem = (img) => {
                const div = document.createElement('div');
                div.className = 'image-grid-item';
                div.setAttribute('role', 'option');
                div.setAttribute('aria-label', img.alt_text || img.original_name);
                div.setAttribute('tabindex', '0');

                const imgEl = document.createElement('img');
                imgEl.src = img.thumbnail_url;
                imgEl.alt = img.alt_text || img.original_name;
                imgEl.loading = 'lazy';
                div.appendChild(imgEl);

                // 削除ボタン
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'delete-btn';
                deleteBtn.innerHTML = '×';
                deleteBtn.title = '削除';
                deleteBtn.setAttribute('aria-label', `${img.original_name}を削除`);
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteImage(img.id, div);
                });
                div.appendChild(deleteBtn);

                // 選択処理
                const selectHandler = () => selectImage(img, div);
                div.addEventListener('click', selectHandler);
                div.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        selectHandler();
                    }
                });

                return div;
            };

            /**
             * 画像を選択
             */
            const selectImage = (img, element) => {
                // 前の選択を解除
                document.querySelectorAll('.image-grid-item.selected').forEach(el => {
                    el.classList.remove('selected');
                });

                element.classList.add('selected');
                selectedImage = img;

                // フッター表示
                modalFooter.classList.remove('hidden');
                selectedPreview.src = img.thumbnail_url;
                selectedPreview.alt = img.alt_text || img.original_name;
                selectedName.textContent = img.original_name;
                selectedSize.textContent = `${img.width}×${img.height}`;
                insertAltText.value = img.alt_text || '';
            };

            /**
             * 画像を挿入
             */
            const insertImage = () => {
                if (!selectedImage) return;

                const alt = insertAltText.value.trim();
                const imgTag = `<img src="${selectedImage.url}" alt="${escapeHtml(alt)}" width="${selectedImage.width}" height="${selectedImage.height}">`;

                // テキストエリアのカーソル位置に挿入
                const start = contentTextarea.selectionStart;
                const end = contentTextarea.selectionEnd;
                const text = contentTextarea.value;
                contentTextarea.value = text.substring(0, start) + imgTag + text.substring(end);

                // カーソルを挿入テキストの直後に移動
                const newPos = start + imgTag.length;
                contentTextarea.setSelectionRange(newPos, newPos);
                contentTextarea.focus();

                close();
            };

            /**
             * ファイルアップロード処理
             */
            const handleFileUpload = async (file) => {
                // リセット
                uploadResult.classList.add('hidden');
                uploadProgress.classList.remove('hidden');
                progressBar.style.width = '0%';
                progressText.textContent = '0%';

                const formData = new FormData();
                formData.append('image', file);
                formData.append('alt_text', uploadAltText.value.trim());

                try {
                    const xhr = new XMLHttpRequest();

                    // 進捗イベント
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            progressBar.style.width = `${percent}%`;
                            progressText.textContent = `${percent}%`;
                        }
                    });

                    // 完了イベント
                    const result = await new Promise((resolve, reject) => {
                        xhr.addEventListener('load', () => {
                            try {
                                resolve(JSON.parse(xhr.responseText));
                            } catch {
                                reject(new Error('レスポンスの解析に失敗しました。'));
                            }
                        });
                        xhr.addEventListener('error', () => reject(new Error('アップロードに失敗しました。')));

                        xhr.open('POST', 'upload.php');
                        xhr.send(formData);
                    });

                    uploadProgress.classList.add('hidden');

                    if (result.success) {
                        showUploadResult('success', `「${result.data.original_name}」をアップロードしました。`);
                        // アップロード入力をリセット
                        fileInput.value = '';
                        uploadAltText.value = '';
                        // ライブラリを更新して切り替え
                        await loadImages();
                        switchTab('library');
                    } else {
                        showUploadResult('error', result.error || 'アップロードに失敗しました。');
                    }
                } catch (error) {
                    uploadProgress.classList.add('hidden');
                    showUploadResult('error', error.message);
                }
            };

            /**
             * 画像削除処理
             */
            const deleteImage = async (id, element) => {
                if (!confirm('この画像を削除しますか？')) return;

                try {
                    const response = await fetch('upload.php?action=delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id
                        }),
                    });
                    const json = await response.json();

                    if (json.success) {
                        element.remove();
                        // 選択中の画像が削除された場合
                        if (selectedImage && selectedImage.id === id) {
                            selectedImage = null;
                            modalFooter.classList.add('hidden');
                        }
                        // 画像がなくなった場合
                        if (imageGrid.children.length === 0) {
                            gridEmpty.classList.remove('hidden');
                        }
                    } else {
                        alert(json.error || '削除に失敗しました。');
                    }
                } catch (error) {
                    alert('削除に失敗しました: ' + error.message);
                }
            };

            /**
             * アップロード結果表示
             */
            const showUploadResult = (type, message) => {
                uploadResult.classList.remove('hidden');
                if (type === 'success') {
                    uploadResult.className = 'mt-4 p-4 rounded-lg bg-green-50 text-green-800 border border-green-200';
                } else {
                    uploadResult.className = 'mt-4 p-4 rounded-lg bg-red-50 text-red-800 border border-red-200';
                }
                uploadResult.textContent = message;
            };

            /**
             * HTMLエスケープ
             */
            const escapeHtml = (text) => {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };

            return {
                init
            };
        })();

        // DOMContentLoaded で初期化
        document.addEventListener('DOMContentLoaded', ImageModal.init);
    </script>
</body>

</html>