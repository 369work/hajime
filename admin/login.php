<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

// すでにログインしている場合はダッシュボードへ
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!$auth->verifyCSRFToken($csrf_token)) {
        $error = '不正なリクエストです。もう一度お試しください。';
    } else {
        if ($auth->login($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'ユーザー名またはパスワードが正しくありません。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hajime CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/admin-nordic.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-gray-900 mb-2 font-outfit tracking-tight">Hajime</h1>
            <p class="text-gray-500 font-medium">Administration Console</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm font-medium">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="nordic-card">

            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                        ユーザー名
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-required="true">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        パスワード
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-required="true">
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    ログイン
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                デフォルトユーザー: admin / password: admin123
            </p>
        </div>
</body>

</html>