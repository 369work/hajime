<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/inform/Form.php';
require_once __DIR__ . '/../../includes/inform/FormField.php';

$auth = new Auth();
$auth->requireLogin();

$formModel = new Form();
$fieldModel = new FormField();

// CSRF トークン生成
// CSRF トークン生成は Auth クラスで行う
$csrf_token = $auth->generateCSRFToken();

$formId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $formId !== null;
$error = '';
$success = '';
$formData = [
    'name' => '',
    'description' => '',
    'settings' => [
        'email_notifications' => false,
        'notification_emails' => [],
        'success_message' => 'お問い合わせありがとうございます。',
        'redirect_url' => ''
    ]
];
$fields = [];

// 編集モードの場合、既存データを取得
if ($isEdit) {
    try {
        $existingForm = $formModel->getById($formId);
        if (!$existingForm) {
            header('Location: index.php');
            exit;
        }
        $formData = $existingForm;
        $fields = $formModel->getFields($formId);
    } catch (Exception $e) {
        $error = 'フォームの取得に失敗しました: ' . $e->getMessage();
    }
}

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF トークン検証
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'セッションが無効です。ページを再読み込みしてください。';
    } else {
        try {
            // フォーム基本情報の取得
            $name = trim($_POST['form_name'] ?? '');
            $description = trim($_POST['form_description'] ?? '');

            // メール通知設定
            $emailNotifications = isset($_POST['email_notifications']) && $_POST['email_notifications'] === '1';
            $notificationEmails = [];
            if ($emailNotifications && !empty($_POST['notification_emails'])) {
                $emailsInput = trim($_POST['notification_emails']);
                $notificationEmails = array_map('trim', explode(',', $emailsInput));
                // メールアドレスのバリデーション
                $notificationEmails = array_filter($notificationEmails, function ($email) {
                    return filter_var($email, FILTER_VALIDATE_EMAIL);
                });
            }

            $successMessage = trim($_POST['success_message'] ?? 'お問い合わせありがとうございます。');
            $redirectUrl = trim($_POST['redirect_url'] ?? '');

            $settings = [
                'email_notifications' => $emailNotifications,
                'notification_emails' => array_values($notificationEmails),
                'success_message' => $successMessage,
                'redirect_url' => $redirectUrl
            ];

            // フォームの保存
            if ($isEdit) {
                $formModel->update($formId, $name, $description, $settings);
            } else {
                $formId = $formModel->create($name, $description, $settings);
                $isEdit = true;
            }

            // フィールドの処理
            $fieldsData = [];
            if (!empty($_POST['fields'])) {
                $fieldsJson = $_POST['fields'];
                $fieldsData = json_decode($fieldsJson, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($fieldsData)) {
                    $formModel->saveFields($formId, $fieldsData);
                }
            }

            $success = 'フォームを保存しました。';

            // データを再読み込み
            $formData = $formModel->getById($formId);
            $fields = $formModel->getFields($formId);
        } catch (Exception $e) {
            $error = 'フォームの保存に失敗しました: ' . $e->getMessage();
        }
    }
}

$currentUser = $auth->getCurrentUser();
$pageTitle = $isEdit ? 'フォーム編集' : '新規フォーム作成';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Inform - Hajime CMS</title>
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
            <a href="index.php" class="text-blue-600 hover:text-blue-800">
                ← フォーム一覧に戻る
            </a>
        </div>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle); ?></h2>
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

        <form method="POST" id="formEditForm" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
            <input type="hidden" name="fields" id="fieldsInput" value="">

            <!-- フォーム基本情報 -->
            <div class="nordic-card">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">基本情報</h3>

                <div class="mb-4">
                    <label for="form_name" class="block text-gray-700 font-bold mb-2">
                        フォーム名 <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="form_name"
                        name="form_name"
                        value="<?php echo htmlspecialchars($formData['name']); ?>"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label for="form_description" class="block text-gray-700 font-bold mb-2">
                        説明
                    </label>
                    <textarea
                        id="form_description"
                        name="form_description"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                </div>
            </div>

            <!-- フィールド設定 -->
            <div class="nordic-card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">フォームフィールド</h3>
                    <button
                        type="button"
                        onclick="addField()"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        + フィールドを追加
                    </button>
                </div>

                <div id="fieldsContainer" class="space-y-4">
                    <!-- フィールドがここに追加されます -->
                </div>

                <div id="emptyFieldsMessage" class="text-center text-gray-500 py-8">
                    フィールドがまだ追加されていません。「フィールドを追加」ボタンをクリックしてください。
                </div>
            </div>

            <!-- メール通知設定 -->
            <div class="nordic-card">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">メール通知設定</h3>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="email_notifications"
                            id="email_notifications"
                            value="1"
                            <?php echo !empty($formData['settings']['email_notifications']) ? 'checked' : ''; ?>
                            class="mr-2"
                            onchange="toggleEmailSettings()">
                        <span class="text-gray-700">メール通知を有効にする</span>
                    </label>
                </div>

                <div id="emailSettingsContainer" style="display: <?php echo !empty($formData['settings']['email_notifications']) ? 'block' : 'none'; ?>;">
                    <div class="mb-4">
                        <label for="notification_emails" class="block text-gray-700 font-bold mb-2">
                            通知先メールアドレス <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="notification_emails"
                            name="notification_emails"
                            value="<?php echo htmlspecialchars(implode(', ', $formData['settings']['notification_emails'] ?? [])); ?>"
                            placeholder="admin@example.com, manager@example.com"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">複数のアドレスはカンマ区切りで入力してください</p>
                    </div>

                    <div class="mb-4">
                        <label for="success_message" class="block text-gray-700 font-bold mb-2">
                            送信完了メッセージ
                        </label>
                        <textarea
                            id="success_message"
                            name="success_message"
                            rows="2"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($formData['settings']['success_message'] ?? 'お問い合わせありがとうございます。'); ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="redirect_url" class="block text-gray-700 font-bold mb-2">
                            リダイレクトURL（オプション）
                        </label>
                        <input
                            type="url"
                            id="redirect_url"
                            name="redirect_url"
                            value="<?php echo htmlspecialchars($formData['settings']['redirect_url'] ?? ''); ?>"
                            placeholder="https://example.com/thank-you"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">送信後にリダイレクトする場合はURLを入力してください</p>
                    </div>
                </div>
            </div>

            <!-- 保存ボタン -->
            <div class="flex justify-end space-x-4">
                <a
                    href="index.php"
                    class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400 transition-colors">
                    キャンセル
                </a>
                <button
                    type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    保存
                </button>
            </div>
        </form>

        <!-- 埋め込みコード（編集モードのみ） -->
        <?php if ($isEdit): ?>
            <div class="nordic-card mt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">埋め込みコード</h3>

                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-2">iframe埋め込み</h4>
                    <div class="relative">
                        <textarea
                            id="iframeCode"
                            readonly
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm"><?php
                                                                                                                    $iframeUrl = SITE_URL . '/public/inform/embed.php?form_id=' . $formId;
                                                                                                                    echo htmlspecialchars('<iframe src="' . $iframeUrl . '" width="100%" height="600" frameborder="0"></iframe>');
                                                                                                                    ?></textarea>
                        <button
                            type="button"
                            onclick="copyToClipboard('iframeCode')"
                            class="absolute top-2 right-2 bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                            コピー
                        </button>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-2">JavaScript埋め込み</h4>
                    <div class="relative">
                        <textarea
                            id="jsCode"
                            readonly
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm"><?php
                                                                                                                    $jsUrl = SITE_URL . '/public/inform/embed.js';
                                                                                                                    $jsCode = '<div id="inform-form-' . $formId . '"></div>' . "\n";
                                                                                                                    $jsCode .= '<script src="' . $jsUrl . '"></script>' . "\n";
                                                                                                                    $jsCode .= '<script>InformEmbed.render("inform-form-' . $formId . '", ' . $formId . ');</script>';
                                                                                                                    echo htmlspecialchars($jsCode);
                                                                                                                    ?></textarea>
                        <button
                            type="button"
                            onclick="copyToClipboard('jsCode')"
                            class="absolute top-2 right-2 bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                            コピー
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // フィールドデータを保持する配列
        let fields = <?php echo json_encode($fields); ?>;
        let fieldCounter = fields.length;

        // ページ読み込み時にフィールドを表示
        document.addEventListener('DOMContentLoaded', function() {
            renderFields();
        });

        // フィールドを追加
        function addField() {
            const field = {
                type: 'text',
                label: '',
                name: '',
                config: {
                    placeholder: '',
                    required: false,
                    max_length: '',
                    options: []
                }
            };
            fields.push(field);
            renderFields();
        }

        // フィールドを削除
        function removeField(index) {
            if (confirm('このフィールドを削除しますか？')) {
                fields.splice(index, 1);
                renderFields();
            }
        }

        // フィールドを上に移動
        function moveFieldUp(index) {
            if (index > 0) {
                [fields[index - 1], fields[index]] = [fields[index], fields[index - 1]];
                renderFields();
            }
        }

        // フィールドを下に移動
        function moveFieldDown(index) {
            if (index < fields.length - 1) {
                [fields[index], fields[index + 1]] = [fields[index + 1], fields[index]];
                renderFields();
            }
        }

        // フィールドタイプ変更時の処理
        function onFieldTypeChange(index) {
            const type = document.getElementById(`field_type_${index}`).value;
            fields[index].type = type;
            renderFields();
        }

        // フィールドを更新
        function updateField(index) {
            const field = fields[index];
            field.label = document.getElementById(`field_label_${index}`).value;
            field.name = generateFieldName(field.label);
            field.config.placeholder = document.getElementById(`field_placeholder_${index}`).value;
            field.config.required = document.getElementById(`field_required_${index}`).checked;

            const maxLengthInput = document.getElementById(`field_max_length_${index}`);
            if (maxLengthInput) {
                field.config.max_length = maxLengthInput.value;
            }

            const optionsInput = document.getElementById(`field_options_${index}`);
            if (optionsInput) {
                const optionsText = optionsInput.value.trim();
                field.config.options = optionsText ? optionsText.split('\n').map(o => o.trim()).filter(o => o) : [];
            }
        }

        // ラベルからフィールド名を生成
        function generateFieldName(label) {
            return label.toLowerCase()
                .replace(/[^a-z0-9_]/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '') || 'field_' + Date.now();
        }

        // フィールドをレンダリング
        function renderFields() {
            const container = document.getElementById('fieldsContainer');
            const emptyMessage = document.getElementById('emptyFieldsMessage');

            if (fields.length === 0) {
                container.innerHTML = '';
                emptyMessage.style.display = 'block';
                return;
            }

            emptyMessage.style.display = 'none';
            container.innerHTML = fields.map((field, index) => renderFieldEditor(field, index)).join('');
        }

        // フィールドエディタをレンダリング
        function renderFieldEditor(field, index) {
            const needsOptions = ['select', 'radio', 'checkbox'].includes(field.type);
            const needsMaxLength = ['text', 'textarea'].includes(field.type);
            const optionsValue = field.config.options ? field.config.options.join('\n') : '';

            return `
                <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                    <div class="flex justify-between items-start mb-4">
                        <h4 class="font-semibold text-gray-700">フィールド ${index + 1}</h4>
                        <div class="flex space-x-2">
                            <button type="button" onclick="moveFieldUp(${index})"
                                class="text-gray-600 hover:text-gray-800 ${index === 0 ? 'opacity-50 cursor-not-allowed' : ''}"
                                ${index === 0 ? 'disabled' : ''}>
                                ↑
                            </button>
                            <button type="button" onclick="moveFieldDown(${index})"
                                class="text-gray-600 hover:text-gray-800 ${index === fields.length - 1 ? 'opacity-50 cursor-not-allowed' : ''}"
                                ${index === fields.length - 1 ? 'disabled' : ''}>
                                ↓
                            </button>
                            <button type="button" onclick="removeField(${index})"
                                class="text-red-600 hover:text-red-800">
                                削除
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                フィールドタイプ <span class="text-red-500">*</span>
                            </label>
                            <select id="field_type_${index}"
                                onchange="onFieldTypeChange(${index})"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="text" ${field.type === 'text' ? 'selected' : ''}>テキスト</option>
                                <option value="email" ${field.type === 'email' ? 'selected' : ''}>メールアドレス</option>
                                <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>テキストエリア</option>
                                <option value="select" ${field.type === 'select' ? 'selected' : ''}>セレクトボックス</option>
                                <option value="radio" ${field.type === 'radio' ? 'selected' : ''}>ラジオボタン</option>
                                <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>チェックボックス</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                ラベル <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="field_label_${index}"
                                value="${escapeHtml(field.label)}"
                                onchange="updateField(${index})"
                                placeholder="例: お名前"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            プレースホルダー
                        </label>
                        <input type="text" id="field_placeholder_${index}"
                            value="${escapeHtml(field.config.placeholder || '')}"
                            onchange="updateField(${index})"
                            placeholder="例: お名前を入力してください"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    ${needsMaxLength ? `
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            最大文字数
                        </label>
                        <input type="number" id="field_max_length_${index}"
                            value="${field.config.max_length || ''}"
                            onchange="updateField(${index})"
                            placeholder="例: 100"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    ` : ''}

                    ${needsOptions ? `
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            選択肢（1行に1つ） <span class="text-red-500">*</span>
                        </label>
                        <textarea id="field_options_${index}"
                            onchange="updateField(${index})"
                            rows="4"
                            placeholder="オプション1\nオプション2\nオプション3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">${escapeHtml(optionsValue)}</textarea>
                    </div>
                    ` : ''}

                    <div class="mt-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="field_required_${index}"
                                ${field.config.required ? 'checked' : ''}
                                onchange="updateField(${index})"
                                class="mr-2">
                            <span class="text-sm text-gray-700">必須フィールド</span>
                        </label>
                    </div>
                </div>
            `;
        }

        // HTMLエスケープ
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // フォーム送信前にフィールドデータをJSON化
        document.getElementById('formEditForm').addEventListener('submit', function(e) {
            // すべてのフィールドを更新
            fields.forEach((field, index) => {
                updateField(index);
            });

            // フィールドデータをJSON化してhidden inputに設定
            document.getElementById('fieldsInput').value = JSON.stringify(fields);
        });

        // メール通知設定の表示/非表示
        function toggleEmailSettings() {
            const checkbox = document.getElementById('email_notifications');
            const container = document.getElementById('emailSettingsContainer');
            container.style.display = checkbox.checked ? 'block' : 'none';
        }

        // クリップボードにコピー
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            document.execCommand('copy');

            // コピー成功メッセージを表示
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'コピーしました！';
            button.classList.add('bg-green-600');
            button.classList.remove('bg-blue-600');

            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('bg-green-600');
                button.classList.add('bg-blue-600');
            }, 2000);
        }
    </script>
</body>

</html>