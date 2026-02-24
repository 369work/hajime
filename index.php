<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Page.php';

require_once __DIR__ . '/includes/Category.php';
require_once __DIR__ . '/includes/Tag.php';
require_once __DIR__ . '/includes/Menu.php';

$pageModel = new Page();
$categoryModel = new Category();
$tagModel = new Tag();

$page = null;
$posts = [];
$archiveTitle = '';

// ルーティング処理
if (isset($_GET['category'])) {
    // カテゴリーアーカイブ
    $slug = $_GET['category'];
    $category = $categoryModel->getBySlug($slug);
    if ($category) {
        $posts = $pageModel->getPagesByCategory($category['id']);
        $archiveTitle = 'カテゴリー: ' . $category['name'];
        $page = [
            'title' => $archiveTitle,
            'content' => '', // アーカイブ表示のため空
            'template' => 'default',
            'meta_title' => $archiveTitle . ' - Hajime CMS',
            'meta_description' => $category['description']
        ];
    } else {
        http_response_code(404);
        $page = [
            'title' => 'カテゴリーが見つかりません',
            'content' => '<p>指定されたカテゴリーは存在しません。</p>',
            'template' => 'default',
            'meta_title' => '404 Not Found',
            'meta_description' => ''
        ];
    }
} elseif (isset($_GET['tag'])) {
    // タグアーカイブ
    $slug = $_GET['tag'];
    $tag = $tagModel->getBySlug($slug);
    if ($tag) {
        $posts = $pageModel->getPagesByTag($tag['id']);
        $archiveTitle = 'タグ: ' . $tag['name'];
        $page = [
            'title' => $archiveTitle,
            'content' => '', // アーカイブ表示のため空
            'template' => 'default',
            'meta_title' => $archiveTitle . ' - Hajime CMS',
            'meta_description' => 'タグ: ' . $tag['name'] . ' の記事一覧'
        ];
    } else {
        http_response_code(404);
        $page = [
            'title' => 'タグが見つかりません',
            'content' => '<p>指定されたタグは存在しません。</p>',
            'template' => 'default',
            'meta_title' => '404 Not Found',
            'meta_description' => ''
        ];
    }
} else {
    // 通常ページ
    $slug = $_GET['page'] ?? 'home';
    $page = $pageModel->getPageBySlug($slug);

    if (!$page) {
        http_response_code(404);
        $page = [
            'title' => 'ページが見つかりません',
            'content' => '<p>お探しのページは見つかりませんでした。</p>',
            'template' => 'default',
            'meta_title' => 'ページが見つかりません',
            'meta_description' => ''
        ];
    }

    // ページに関連するタグを取得（表示用）
    if (isset($page['id'])) {
        $page['tags'] = $tagModel->getTagsByPageId($page['id']);
    }
}

require_once __DIR__ . '/includes/Setting.php';

// サイト設定を取得
$settingModel = new Setting();
$siteSettings = $settingModel->getAll();

// ナビゲーション用：管理されたメニューを取得
$menuModel = new Menu();
$menuItems = $menuModel->getActiveMenus();

// テンプレート決定ロジック
// サイト設定のテーマを優先
$theme = $siteSettings['theme'] ?? 'default';

// プレビュー用にGETパラメータでテーマを強制切り替えできる機能（デバッグ用）
if (isset($_GET['preview_theme']) && is_dir(__DIR__ . '/templates/' . $_GET['preview_theme'])) {
    $theme = $_GET['preview_theme'];
}

$templateFile = __DIR__ . '/templates/' . $theme . '/index.php';

if (!file_exists($templateFile)) {
    // フォールバック
    $templateFile = __DIR__ . '/templates/default/index.php';
}

include $templateFile;
