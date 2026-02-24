<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Menu.php';
require_once __DIR__ . '/../includes/Page.php';
require_once __DIR__ . '/../includes/Category.php';

$auth = new Auth();
$auth->requireLogin();

$menuModel = new Menu();
$pageModel = new Page();
$categoryModel = new Category();

$success = '';
$error = '';

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $error = '不正なリクエストです。もう一度お試しください。';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'create':
                    $label = trim($_POST['label'] ?? '');
                    $cssId = trim($_POST['css_id'] ?? '');
                    $type = $_POST['type'] ?? 'custom';
                    $referenceId = $_POST['reference_id'] ?? null;
                    $url = trim($_POST['url'] ?? '');

                    if (empty($label)) {
                        $error = '表示名を入力してください。';
                        break;
                    }

                    // CSS IDのバリデーション（英数字・ハイフン・アンダースコアのみ）
                    if (!empty($cssId) && !preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $cssId)) {
                        $error = 'CSS IDは英字で始まり、英数字・ハイフン・アンダースコアのみ使用できます。';
                        break;
                    }

                    $menuModel->create([
                        'label' => $label,
                        'css_id' => $cssId,
                        'type' => $type,
                        'reference_id' => $referenceId,
                        'url' => $url,
                        'is_active' => 1
                    ]);
                    $success = 'メニュー項目を追加しました。';
                    break;

                case 'delete':
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id > 0) {
                        $menuModel->delete($id);
                        $success = 'メニュー項目を削除しました。';
                    }
                    break;

                case 'toggle':
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id > 0) {
                        $menuModel->toggleActive($id);
                        $success = '表示状態を変更しました。';
                    }
                    break;

                case 'reorder':
                    $orderData = $_POST['order'] ?? '';
                    if (!empty($orderData)) {
                        $ids = array_map('intval', explode(',', $orderData));
                        $menuModel->updateOrder($ids);
                        $success = '並び順を保存しました。';
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = 'エラー: ' . $e->getMessage();
        }
    }
}

// データ取得
$menus = $menuModel->getAll();
$pages = $pageModel->getAllPages();
$categories = $categoryModel->getAll();
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メニュー管理 - Hajime CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/admin-nordic.css">
    <style>
        .menu-item {
            cursor: grab;
        }

        .menu-item:active {
            cursor: grabbing;
        }

        .menu-item.dragging {
            opacity: 0.5;
        }

        .menu-item.drag-over {
            border-top: 3px solid #3b82f6;
        }
    </style>
</head>

<body class="bg-gray-100">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="text-xl font-bold text-gray-800">Hajime CMS</a>
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="index.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">ページ</a>
                        <a href="categories.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">カテゴリー</a>
                        <a href="tags.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">タグ</a>
                        <a href="menus.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">メニュー</a>
                        <a href="settings.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">設定</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo SITE_URL; ?>" target="_blank" class="text-sm text-gray-600 hover:text-gray-800">サイトを表示</a>
                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="logout.php" class="text-sm text-red-600 hover:text-red-800">ログアウト</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">メニュー管理</h2>
            <p class="text-sm text-gray-600 mt-1">サイトのヘッダーナビゲーションに表示するメニュー項目を管理します。</p>
        </div>

        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 左カラム: メニュー一覧 -->
            <div class="lg:col-span-2">
                <div class="nordic-card !p-0">
                    <div class="p-4 border-b flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800">現在のメニュー項目</h3>
                        <button id="save-order-btn" class="hidden bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700 transition-colors">
                            並び順を保存
                        </button>
                    </div>

                    <?php if (empty($menus)): ?>
                        <div class="p-8 text-center text-gray-500">
                            <p>メニュー項目がありません。</p>
                            <p class="text-sm mt-1">右のフォームから追加してください。</p>
                        </div>
                    <?php else: ?>
                        <ul id="menu-list" class="divide-y divide-gray-100">
                            <?php foreach ($menus as $menu): ?>
                                <li class="menu-item flex items-center justify-between p-4 hover:bg-gray-50" draggable="true" data-id="<?php echo $menu['id']; ?>">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-gray-400 cursor-grab" title="ドラッグで並べ替え">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                            </svg>
                                        </span>
                                        <div>
                                            <span class="font-medium text-gray-800 <?php echo !$menu['is_active'] ? 'line-through text-gray-400' : ''; ?>">
                                                <?php echo htmlspecialchars($menu['label']); ?>
                                            </span>
                                            <span class="ml-2 text-xs px-2 py-0.5 rounded-full
                                                <?php
                                                switch ($menu['type']) {
                                                    case 'page':
                                                        echo 'bg-blue-100 text-blue-700';
                                                        break;
                                                    case 'category':
                                                        echo 'bg-green-100 text-green-700';
                                                        break;
                                                    case 'custom':
                                                        echo 'bg-yellow-100 text-yellow-700';
                                                        break;
                                                }
                                                ?>">
                                                <?php
                                                switch ($menu['type']) {
                                                    case 'page':
                                                        echo 'ページ';
                                                        break;
                                                    case 'category':
                                                        echo 'カテゴリー';
                                                        break;
                                                    case 'custom':
                                                        echo 'カスタム';
                                                        break;
                                                }
                                                ?>
                                            </span>
                                            <?php if ($menu['reference_name']): ?>
                                                <span class="text-xs text-gray-500 ml-1">(<?php echo htmlspecialchars($menu['reference_name']); ?>)</span>
                                            <?php elseif ($menu['type'] === 'custom' && $menu['url']): ?>
                                                <span class="text-xs text-gray-500 ml-1">(<?php echo htmlspecialchars($menu['url']); ?>)</span>
                                            <?php endif; ?>
                                            <?php if (!empty($menu['css_id'])): ?>
                                                <span class="text-xs font-mono text-purple-600 ml-1">#<?php echo htmlspecialchars($menu['css_id']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                                            <button type="submit" class="text-xs px-2 py-1 rounded border transition-colors
                                                <?php echo $menu['is_active']
                                                    ? 'border-green-300 text-green-700 hover:bg-green-50'
                                                    : 'border-gray-300 text-gray-500 hover:bg-gray-50'; ?>"
                                                title="<?php echo $menu['is_active'] ? '無効にする' : '有効にする'; ?>">
                                                <?php echo $menu['is_active'] ? '有効' : '無効'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('このメニュー項目を削除しますか？');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                                            <button type="submit" class="text-xs px-2 py-1 rounded border border-red-300 text-red-600 hover:bg-red-50 transition-colors">
                                                削除
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 右カラム: 追加フォーム -->
            <div class="lg:col-span-1">
                <div class="nordic-card">
                    <h3 class="font-semibold text-gray-800 mb-4">メニュー項目を追加</h3>
                    <form method="POST" id="add-menu-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="create">

                        <div class="mb-4">
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">種別</label>
                            <select id="type" name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                <option value="page">ページ</option>
                                <option value="category">カテゴリー</option>
                                <option value="custom">カスタムURL</option>
                            </select>
                        </div>

                        <div id="page-select" class="mb-4">
                            <label for="page_id" class="block text-sm font-medium text-gray-700 mb-1">ページ選択</label>
                            <select id="page_id" name="reference_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                <option value="">-- 選択 --</option>
                                <?php foreach ($pages as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-name="<?php echo htmlspecialchars($p['title']); ?>">
                                        <?php echo htmlspecialchars($p['title']); ?>
                                        (<?php echo $p['status'] === 'published' ? '公開' : '下書き'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="category-select" class="mb-4 hidden">
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">カテゴリー選択</label>
                            <select id="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                <option value="">-- 選択 --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-name="<?php echo htmlspecialchars($cat['name']); ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="custom-url" class="mb-4 hidden">
                            <label for="url" class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                            <input type="url" id="url" name="url" placeholder="https://example.com"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        </div>

                        <div class="mb-4">
                            <label for="label" class="block text-sm font-medium text-gray-700 mb-1">表示名</label>
                            <input type="text" id="label" name="label" required placeholder="メニューに表示するテキスト"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        </div>

                        <div class="mb-4">
                            <label for="css_id" class="block text-sm font-medium text-gray-700 mb-1">CSS ID <span class="text-gray-400 font-normal">(任意)</span></label>
                            <input type="text" id="css_id" name="css_id" placeholder="例: nav-about"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm font-mono">
                            <p class="text-xs text-gray-500 mt-1">英字で始まる英数字・ハイフン・アンダースコア。CSSで <code class="bg-gray-100 px-1 rounded">#nav-about</code> のように指定できます。</p>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium">
                            追加
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 並び順保存用の非表示フォーム -->
    <form id="reorder-form" method="POST" class="hidden">
        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="reorder">
        <input type="hidden" name="order" id="reorder-input">
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- 種別切替ロジック ---
            const typeSelect = document.getElementById('type');
            const pageSelect = document.getElementById('page-select');
            const categorySelect = document.getElementById('category-select');
            const customUrl = document.getElementById('custom-url');
            const labelInput = document.getElementById('label');
            const pageIdSelect = document.getElementById('page_id');
            const categoryIdSelect = document.getElementById('category_id');

            const toggleFields = () => {
                const type = typeSelect.value;
                pageSelect.classList.toggle('hidden', type !== 'page');
                categorySelect.classList.toggle('hidden', type !== 'category');
                customUrl.classList.toggle('hidden', type !== 'custom');

                // name属性を動的に切り替え（reference_idが正しいselectに紐づくように）
                pageIdSelect.name = type === 'page' ? 'reference_id' : '';
                categoryIdSelect.name = type === 'category' ? 'reference_id' : '';
            };

            typeSelect.addEventListener('change', toggleFields);
            toggleFields();

            // ページ/カテゴリー選択時にラベルを自動入力
            pageIdSelect.addEventListener('change', () => {
                const selected = pageIdSelect.options[pageIdSelect.selectedIndex];
                if (selected && selected.dataset.name && !labelInput.value) {
                    labelInput.value = selected.dataset.name;
                }
            });
            categoryIdSelect.addEventListener('change', () => {
                const selected = categoryIdSelect.options[categoryIdSelect.selectedIndex];
                if (selected && selected.dataset.name && !labelInput.value) {
                    labelInput.value = selected.dataset.name;
                }
            });

            // --- ドラッグ＆ドロップ並べ替え ---
            const menuList = document.getElementById('menu-list');
            if (!menuList) return;

            const saveBtn = document.getElementById('save-order-btn');
            let dragItem = null;
            let orderChanged = false;

            menuList.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('dragstart', (e) => {
                    dragItem = item;
                    item.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });

                item.addEventListener('dragend', () => {
                    item.classList.remove('dragging');
                    menuList.querySelectorAll('.menu-item').forEach(el => el.classList.remove('drag-over'));
                    dragItem = null;
                });

                item.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    if (item !== dragItem) {
                        item.classList.add('drag-over');
                    }
                });

                item.addEventListener('dragleave', () => {
                    item.classList.remove('drag-over');
                });

                item.addEventListener('drop', (e) => {
                    e.preventDefault();
                    item.classList.remove('drag-over');
                    if (dragItem && item !== dragItem) {
                        const rect = item.getBoundingClientRect();
                        const midY = rect.top + rect.height / 2;
                        if (e.clientY < midY) {
                            menuList.insertBefore(dragItem, item);
                        } else {
                            menuList.insertBefore(dragItem, item.nextSibling);
                        }
                        orderChanged = true;
                        saveBtn.classList.remove('hidden');
                    }
                });
            });

            // 並び順保存
            saveBtn.addEventListener('click', () => {
                const ids = Array.from(menuList.querySelectorAll('.menu-item'))
                    .map(el => el.dataset.id);
                document.getElementById('reorder-input').value = ids.join(',');
                document.getElementById('reorder-form').submit();
            });
        });
    </script>
</body>

</html>