<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Setting.php';
require_once __DIR__ . '/../includes/Image.php';

$auth = new Auth();
$auth->requireLogin();
$settingModel = new Setting();
$imageModel = new Image();

$success = '';
$error = '';

// POST処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!$auth->verifyCSRFToken($csrf_token)) {
        $error = '不正なリクエストです。もう一度お試しください。';
    } else {
        try {
            $settings = [
                'site_title' => $_POST['site_title'] ?? '',
                'site_description' => $_POST['site_description'] ?? '',
                'theme' => $_POST['theme'] ?? 'default',
                'shop_name' => $_POST['shop_name'] ?? '',
                'shop_address' => $_POST['shop_address'] ?? '',
                'shop_tel' => $_POST['shop_tel'] ?? '',
                'shop_hours' => $_POST['shop_hours'] ?? '',
                'admin_email' => $_POST['admin_email'] ?? '',
            ];

            foreach ($settings as $key => $value) {
                $settingModel->set($key, $value);
            }

            // 画像処理
            $currentUser = $auth->getCurrentUser();

            // サイトロゴ
            if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
                try {
                    $uploaded = $imageModel->upload($_FILES['site_logo'], $currentUser['id'], 'Site Logo');
                    $settingModel->set('site_logo', $uploaded['url']);
                } catch (Exception $e) {
                    $error .= ' ロゴ画像のアップロードに失敗しました: ' . $e->getMessage();
                }
            }

            // サイトアイコン
            if (isset($_FILES['site_icon']) && $_FILES['site_icon']['error'] === UPLOAD_ERR_OK) {
                try {
                    $uploaded = $imageModel->upload($_FILES['site_icon'], $currentUser['id'], 'Site Icon');
                    $settingModel->set('site_icon', $uploaded['url']);
                } catch (Exception $e) {
                    $error .= ' アイコンのアップロードに失敗しました: ' . $e->getMessage();
                }
            }

            $success = '設定を保存しました。';
        } catch (Exception $e) {
            $error = 'エラー: ' . $e->getMessage();
        }
    }
}

// 設定値取得
$currentSettings = $settingModel->getAll();

// テンプレート一覧取得
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
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>サイト設定 - Hajime CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/admin-nordic.css">
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
                        <a href="menus.php" class="text-sm font-medium text-gray-600 hover:text-gray-800">メニュー</a>
                        <a href="settings.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">設定</a>
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">サイト設定</h2>
            <p class="text-sm text-gray-600 mt-1">サイト全体の基本情報やデザイン設定を管理します。</p>
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

        <form method="POST" enctype="multipart/form-data" class="nordic-card overflow-hidden !p-0">
            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
            <!-- タブナビゲーション (簡易実装: 今回は1ページでセクション分け) -->
            <div class="border-b bg-gray-50 px-6 py-3">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">設定項目</h3>
            </div>

            <div class="p-6 space-y-8">
                <!-- 基本設定 -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">基本設定</h3>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="site_title" class="block text-sm font-medium text-gray-700">サイトタイトル</label>
                            <input type="text" name="site_title" id="site_title"
                                value="<?php echo htmlspecialchars($currentSettings['site_title'] ?? ''); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label for="site_description" class="block text-sm font-medium text-gray-700">キャッチコピー / 説明</label>
                            <input type="text" name="site_description" id="site_description"
                                value="<?php echo htmlspecialchars($currentSettings['site_description'] ?? ''); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-gray-700">管理者メールアドレス</label>
                            <input type="email" name="admin_email" id="admin_email"
                                value="<?php echo htmlspecialchars($currentSettings['admin_email'] ?? ''); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2 border">
                        </div>
                    </div>
                </div>

                <!-- 画像設定 -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">画像設定</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">サイトロゴ</label>
                            <div class="mt-1 flex items-center">
                                <?php if (!empty($currentSettings['site_logo'])): ?>
                                    <div class="mr-4">
                                        <img src="<?php echo htmlspecialchars($currentSettings['site_logo']); ?>" alt="Current Logo" class="h-12 w-auto object-contain border p-1 bg-gray-50">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="site_logo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">ヘッダーに表示されるロゴ画像です。透過PNGまたはSVGを推奨します。</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">サイトアイコン (Favicon)</label>
                            <div class="mt-1 flex items-center">
                                <?php if (!empty($currentSettings['site_icon'])): ?>
                                    <div class="mr-4">
                                        <img src="<?php echo htmlspecialchars($currentSettings['site_icon']); ?>" alt="Current Icon" class="h-8 w-8 object-contain border p-1 bg-gray-50">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="site_icon" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">ブラウザのタブに表示されるアイコンです。正方形（32x32以上）を推奨します。</p>
                        </div>
                    </div>
                </div>

                <!-- デザイン設定 -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">デザイン設定</h3>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="theme" class="block text-sm font-medium text-gray-700">使用テーマ (テンプレート)</label>
                            <select id="theme" name="theme" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border">
                                <?php foreach ($templates as $tpl): ?>
                                    <option value="<?php echo htmlspecialchars($tpl); ?>" <?php echo ($currentSettings['theme'] ?? 'default') === $tpl ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($tpl)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-2 text-xs text-gray-500">
                                選択したテーマがサイト全体に適用されます。各ページの個別設定よりも優先されます。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 店舗情報 -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b">店舗情報</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="shop_name" class="block text-sm font-medium text-gray-700">店舗名 / 会社名</label>
                            <input type="text" name="shop_name" id="shop_name"
                                value="<?php echo htmlspecialchars($currentSettings['shop_name'] ?? ''); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2 border">
                        </div>
                        <div>
                            <label for="shop_tel" class="block text-sm font-medium text-gray-700">電話番号</label>
                            <input type="text" name="shop_tel" id="shop_tel"
                                value="<?php echo htmlspecialchars($currentSettings['shop_tel'] ?? ''); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2 border">
                        </div>
                        <div class="md:col-span-2">
                            <label for="shop_address" class="block text-sm font-medium text-gray-700">住所</label>
                            <input type="text" name="shop_address" id="shop_address"
                                value="<?php echo htmlspecialchars($currentSettings['shop_address'] ?? ''); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2 border">
                        </div>
                        <div class="md:col-span-2">
                            <label for="shop_hours" class="block text-sm font-medium text-gray-700">営業時間 / 定休日</label>
                            <textarea name="shop_hours" id="shop_hours" rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm px-3 py-2 border"><?php echo htmlspecialchars($currentSettings['shop_hours'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 text-right">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    設定を保存
                </button>
            </div>
        </form>
    </div>
</body>

</html>