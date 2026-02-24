<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';

$auth = new Auth();
$auth->requireLogin();

$formModel = new Form();
$forms = [];
$error = '';

try {
    $forms = $formModel->getAll();
} catch (Exception $e) {
    $error = 'フォーム一覧の取得に失敗しました: ' . $e->getMessage();
    error_log('Inform index.php エラー: ' . $e->getMessage());
}

// 削除成功メッセージ
$success = '';
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $success = 'フォームを削除しました。';
}

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inform - フォーム一覧 - Hajime CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/admin-nordic.css">
</head>

<body class="bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../index.php" class="text-xl font-bold text-gray-800">Hajime CMS</a>
                    <span class="ml-4 text-gray-400">|</span>
                    <span class="ml-4 text-lg text-gray-600">Inform</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo SITE_URL; ?>" target="_blank" class="text-gray-600 hover:text-gray-800">
                        サイトを表示
                    </a>
                    <span class="text-gray-600">
                        <?php echo htmlspecialchars($currentUser['username']); ?>
                    </span>
                    <a href="../logout.php" class="text-red-600 hover:text-red-800">
                        ログアウト
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="../index.php" class="text-blue-600 hover:text-blue-800">
                ← ダッシュボードに戻る
            </a>
        </div>

        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">フォーム一覧</h2>
            <div class="flex space-x-2">
                <a
                    href="messages.php"
                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                    メッセージ管理
                </a>
                <a
                    href="form-edit.php"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    新規フォーム作成
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <div class="nordic-card overflow-hidden !p-0">
            <?php if (empty($forms)): ?>
                <div class="p-8 text-center text-gray-500">
                    <p>フォームがまだ作成されていません。</p>
                    <a href="form-edit.php" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                        最初のフォームを作成する
                    </a>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                フォーム名
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                説明
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                作成日時
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
                        <?php foreach ($forms as $form): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($form['name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500">
                                        <?php
                                        $description = $form['description'] ?? '';
                                        echo htmlspecialchars(
                                            strlen($description) > 100
                                                ? substr($description, 0, 100) . '...'
                                                : $description
                                        );
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('Y/m/d H:i', strtotime($form['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('Y/m/d H:i', strtotime($form['updated_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <a href="form-edit.php?id=<?php echo $form['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        編集
                                    </a>
                                    <a
                                        href="form-delete.php?id=<?php echo $form['id']; ?>"
                                        class="text-red-600 hover:text-red-900"
                                        onclick="return confirm('本当に削除しますか? 関連する送信データもすべて削除されます。');">
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