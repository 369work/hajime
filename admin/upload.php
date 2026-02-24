<?php

/**
 * 画像アップロードAPIエンドポイント
 *
 * AJAX経由での画像アップロード・一覧取得・削除を処理する。
 * すべてのリクエストに認証が必要。
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Image.php';

// 認証チェック
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => '認証が必要です。'], JSON_UNESCAPED_UNICODE);
    exit;
}

$currentUser = $auth->getCurrentUser();
$imageModel = new Image();

// リクエストのアクション判定
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json; charset=utf-8');

try {
    if ($method === 'POST' && $action === 'delete') {
        // 画像削除
        handleDelete($imageModel);
    } elseif ($method === 'POST' && $action === 'update_alt') {
        // alt属性更新
        handleUpdateAlt($imageModel);
    } elseif ($method === 'POST') {
        // 画像アップロード
        handleUpload($imageModel, (int) $currentUser['id']);
    } elseif ($method === 'GET' && $action === 'list') {
        // 画像一覧取得
        handleList($imageModel);
    } else {
        http_response_code(400);
        echo json_encode(['error' => '無効なリクエストです。'], JSON_UNESCAPED_UNICODE);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 画像アップロード処理
 */
function handleUpload(Image $imageModel, int $userId): void
{
    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['error' => '画像ファイルが送信されていません。'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $altText = trim($_POST['alt_text'] ?? '');
    $result = $imageModel->upload($_FILES['image'], $userId, $altText);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => '画像をアップロードしました。',
        'data'    => $result,
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 画像一覧取得処理
 */
function handleList(Image $imageModel): void
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));

    $result = $imageModel->getAll($page, $perPage);

    echo json_encode([
        'success' => true,
        'data'    => $result,
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 画像削除処理
 */
function handleDelete(Image $imageModel): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($input['id'] ?? $_POST['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => '画像IDが指定されていません。'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $imageModel->delete($id);

    echo json_encode([
        'success' => true,
        'message' => '画像を削除しました。',
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * alt属性更新処理
 */
function handleUpdateAlt(Image $imageModel): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($input['id'] ?? 0);
    $altText = trim($input['alt_text'] ?? '');

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => '画像IDが指定されていません。'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $imageModel->updateAltText($id, $altText);

    echo json_encode([
        'success' => true,
        'message' => 'alt属性を更新しました。',
    ], JSON_UNESCAPED_UNICODE);
}
