<?php
session_start();

// 既にインストール済みか確認
if (file_exists('../includes/config.php')) {
    $message = "すでにインストールされています。再インストールするには includes/config.php を削除してください。";
}

$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
$errors = [];
$success = false;

// サイトURLの自動検出
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$detected_url = "$protocol://$host$path";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    // 入力データの取得
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'hajime_db';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $site_url = $_POST['site_url'] ?? $detected_url;

    $admin_user = $_POST['admin_user'] ?? 'admin';
    $admin_pass = $_POST['admin_pass'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';

    // バリデーション
    if (empty($db_host) || empty($db_name) || empty($db_user)) $errors[] = "データベース情報を入力してください。";
    if (empty($admin_user) || empty($admin_pass) || empty($admin_email)) $errors[] = "管理者情報を入力してください。";

    // データベース接続確認
    if (empty($errors)) {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $mysqli = new mysqli($db_host, $db_user, $db_pass);

            // データベース作成
            $mysqli->query("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $mysqli->select_db($db_name);

            // SQLファイルの実行
            $sql_files = [
                '../setup-hajime.sql',
                '../setup-categories-tags.sql',
                '../setup-uploads.sql',
                '../setup-inform.sql'
            ];

            foreach ($sql_files as $file) {
                if (!file_exists($file)) continue;

                $sql_content = file_get_contents($file);
                // コメント削除
                $sql_content = preg_replace('/--.*$/m', '', $sql_content);
                // USE文削除
                $sql_content = preg_replace('/USE\s+.*;/i', '', $sql_content);

                // クエリ分割と実行
                $queries = explode(';', $sql_content);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $mysqli->query($query);
                    }
                }
            }

            // 管理者ユーザー作成
            $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'admin') ON DUPLICATE KEY UPDATE password = ?, email = ?");
            $stmt->bind_param("sssss", $admin_user, $hash, $admin_email, $hash, $admin_email);
            $stmt->execute();

            // config.php 生成
            $config_content = "<?php\n";
            $config_content .= "// データベース設定\n";
            $config_content .= "define('DB_HOST', '$db_host');\n";
            $config_content .= "define('DB_NAME', '$db_name');\n";
            $config_content .= "define('DB_USER', '$db_user');\n";
            $config_content .= "define('DB_PASS', '$db_pass');\n";
            $config_content .= "define('DB_CHARSET', 'utf8mb4');\n\n";

            $config_content .= "// サイト設定\n";
            $config_content .= "define('SITE_URL', '$site_url');\n";
            $config_content .= "define('ADMIN_URL', SITE_URL . '/admin');\n";
            $config_content .= "define('UPLOAD_DIR', __DIR__ . '/../uploads/');\n";
            $config_content .= "define('UPLOAD_URL', SITE_URL . '/uploads/');\n";
            $config_content .= "define('ASSET_URL', SITE_URL . '/assets/');\n\n";

            $config_content .= "// セッション設定\n";
            $config_content .= "define('SESSION_LIFETIME', 3600);\n\n";

            $config_content .= "// タイムゾーン設定\n";
            $config_content .= "date_default_timezone_set('Asia/Tokyo');\n\n";

            $config_content .= "// Inform メール設定 (デフォルト)\n";
            $config_content .= "define('INFORM_EMAIL_METHOD', 'mail');\n";
            // SMTP設定のプレースホルダーは省略（必要ならconfig.sample.php参照）

            file_put_contents('../includes/config.php', $config_content);

            $success = true;
        } catch (Exception $e) {
            $errors[] = "インストールエラー: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hajime CMS インストーラー</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: #0056b3;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            text-align: center;
        }

        .note {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Hajime CMS Setup</h1>

        <?php if (isset($message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success">
                <h2>インストール完了！</h2>
                <p>Hajime CMSのセットアップが完了しました。</p>
                <p>セキュリティのため、必ず <strong>install ディレクトリを削除</strong> してください。</p>
                <div style="margin-top: 1.5rem;">
                    <a href="../admin/login.php" style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;">管理画面へ移動</a>
                    <br><br>
                    <a href="../" style="color: #007bff;">サイトトップへ</a>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="step" value="2">

                <h3>サイト設定</h3>
                <div class="form-group">
                    <label>サイトURL</label>
                    <input type="text" name="site_url" value="<?= htmlspecialchars($detected_url) ?>" required>
                </div>

                <h3>データベース設定</h3>
                <div class="form-group">
                    <label>ホスト名</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>データベース名</label>
                    <input type="text" name="db_name" value="hajime_db" required>
                    <div class="note">※存在しない場合は作成を試みます</div>
                </div>
                <div class="form-group">
                    <label>ユーザー名</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label>パスワード</label>
                    <input type="password" name="db_pass">
                </div>

                <h3>管理者アカウント作成</h3>
                <div class="form-group">
                    <label>ユーザー名</label>
                    <input type="text" name="admin_user" value="admin" required>
                </div>
                <div class="form-group">
                    <label>メールアドレス</label>
                    <input type="email" name="admin_email" required>
                </div>
                <div class="form-group">
                    <label>パスワード</label>
                    <input type="password" name="admin_pass" required>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit">インストール実行</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>