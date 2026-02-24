<?php

/**
 * 画像アップロード機能のテストスクリプト
 *
 * 実行方法: php admin/test_image_upload.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Image.php';

echo "=== 画像アップロード機能テスト ===\n\n";

$passed = 0;
$failed = 0;

function test(string $name, bool $result): void
{
    global $passed, $failed;
    if ($result) {
        echo "✅ {$name}\n";
        $passed++;
    } else {
        echo "❌ {$name}\n";
        $failed++;
    }
}

// テスト1: Imageクラスのインスタンス作成
echo "--- 基本機能テスト ---\n";
try {
    $imageModel = new Image();
    test('Imageクラスのインスタンス作成', true);
} catch (\Exception $e) {
    test('Imageクラスのインスタンス作成', false);
    echo "  エラー: {$e->getMessage()}\n";
    exit(1);
}

// テスト2: ディレクトリが存在するか
$imageDir = __DIR__ . '/../uploads/images/';
$thumbDir = __DIR__ . '/../uploads/thumbnails/';
test('画像ディレクトリが存在する', is_dir($imageDir));
test('サムネイルディレクトリが存在する', is_dir($thumbDir));

// テスト3: テスト用画像を生成してアップロード
echo "\n--- アップロードテスト ---\n";
$testImagePath = sys_get_temp_dir() . '/test_upload_' . time() . '.png';
$img = imagecreatetruecolor(200, 150);
$bg = imagecolorallocate($img, 100, 150, 200);
imagefill($img, 0, 0, $bg);
$text = imagecolorallocate($img, 255, 255, 255);
imagestring($img, 5, 50, 60, 'TEST IMAGE', $text);
imagepng($img, $testImagePath);
imagedestroy($img);

test('テスト画像の生成', file_exists($testImagePath));

// $_FILES相当のデータを作成
$mockFile = [
    'name' => 'test_image.png',
    'type' => 'image/png',
    'tmp_name' => $testImagePath,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($testImagePath),
];

// move_uploaded_file は CLI だと動かないので、copy で代替テスト
// 代わりにバリデーションのみテスト
echo "\n--- バリデーションテスト ---\n";

// 不正なMIMEタイプ
$badFile = $mockFile;
$badFile['type'] = 'application/pdf';
try {
    // リフレクションでprivateメソッドをテスト
    $reflection = new ReflectionClass($imageModel);
    $method = $reflection->getMethod('validateFile');
    $method->setAccessible(true);
    $method->invoke($imageModel, $badFile);
    test('不正なMIMEタイプの拒否', false);
} catch (\RuntimeException $e) {
    test('不正なMIMEタイプの拒否', str_contains($e->getMessage(), '対応していない'));
}

// 正しいMIMEタイプ
try {
    $method->invoke($imageModel, $mockFile);
    test('正しい画像ファイルの受け入れ', true);
} catch (\RuntimeException $e) {
    test('正しい画像ファイルの受け入れ', false);
    echo "  エラー: {$e->getMessage()}\n";
}

// サイズ超過テスト
$bigFile = $mockFile;
$bigFile['size'] = 10 * 1024 * 1024; // 10MB
try {
    $method->invoke($imageModel, $bigFile);
    test('サイズ超過ファイルの拒否', false);
} catch (\RuntimeException $e) {
    test('サイズ超過ファイルの拒否', str_contains($e->getMessage(), '上限'));
}

// テスト4: サムネイル生成
echo "\n--- サムネイル生成テスト ---\n";
$thumbMethod = $reflection->getMethod('createThumbnail');
$thumbMethod->setAccessible(true);
$testFilename = 'test_thumb_' . time() . '.png';
$thumbMethod->invoke($imageModel, $testImagePath, $testFilename, 'image/png');
$thumbExists = file_exists($thumbDir . $testFilename);
test('サムネイル生成', $thumbExists);
if ($thumbExists) {
    $thumbInfo = getimagesize($thumbDir . $testFilename);
    test('サムネイルサイズが300px以下', $thumbInfo[0] <= 300 && $thumbInfo[1] <= 300);
    // クリーンアップ
    unlink($thumbDir . $testFilename);
}

// テスト5: 空の一覧取得
echo "\n--- 一覧取得テスト ---\n";
$list = $imageModel->getAll();
test('画像一覧の取得', is_array($list) && isset($list['items']));
test('一覧にページネーション情報がある', isset($list['total']) && isset($list['pages']));

// テスト6: ファイル名生成
echo "\n--- ファイル名生成テスト ---\n";
$nameMethod = $reflection->getMethod('generateSafeFilename');
$nameMethod->setAccessible(true);
$filename1 = $nameMethod->invoke($imageModel, 'jpg');
$filename2 = $nameMethod->invoke($imageModel, 'png');
test('ファイル名が拡張子を含む', str_ends_with($filename1, '.jpg'));
test('ファイル名がユニーク', $filename1 !== $filename2);
test('ファイル名にパス区切り文字なし', !str_contains($filename1, '/') && !str_contains($filename1, '\\'));

// テスト7: URL生成
echo "\n--- URL生成テスト ---\n";
$imageUrl = $imageModel->getImageUrl('test.jpg');
$thumbUrl = $imageModel->getThumbnailUrl('test.jpg');
test('画像URLの生成', str_contains($imageUrl, '/images/test.jpg'));
test('サムネイルURLの生成', str_contains($thumbUrl, '/thumbnails/test.jpg'));

// クリーンアップ
if (file_exists($testImagePath)) {
    unlink($testImagePath);
}

echo "\n=== テスト結果 ===\n";
echo "合格: {$passed}, 不合格: {$failed}\n";
echo ($failed === 0) ? "✅ すべてのテストが合格しました！\n" : "❌ {$failed}件のテストが不合格です。\n";
