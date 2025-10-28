<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Page.php';

$pageModel = new Page();

// スラッグパラメータを取得
$slug = $_GET['page'] ?? 'home';

// ページを取得
$page = $pageModel->getPageBySlug($slug);

// ページが見つからない場合は404
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

// テンプレートファイルを読み込み
$templateFile = __DIR__ . '/templates/' . $page['template'] . '/index.php';
if (!file_exists($templateFile)) {
    $templateFile = __DIR__ . '/templates/default/index.php';
}

include $templateFile;
