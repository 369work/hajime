<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page['meta_title'] ?: $page['title']); ?></title>
    <?php if (!empty($page['meta_description'])): ?>
        <meta name="description" content="<?php echo htmlspecialchars($page['meta_description']); ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <header class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <nav class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <a href="<?php echo SITE_URL; ?>" class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                Hajime CMS
                </a>
                <div class="space-x-8">
                    <?php
                    // 公開ページのナビゲーションを取得
                    $navPages = $pageModel->getPublishedPages();
                    foreach ($navPages as $navPage):
                    ?>
                        <a 
                            href="?page=<?php echo htmlspecialchars($navPage['slug']); ?>" 
                            class="text-gray-700 hover:text-indigo-600 transition-colors font-medium <?php echo $navPage['slug'] === $slug ? 'text-indigo-600' : ''; ?>"
                        >
                            <?php echo htmlspecialchars($navPage['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <article class="bg-white rounded-2xl shadow-xl p-12 transform hover:shadow-2xl transition-shadow duration-300">
            <h1 class="text-5xl font-extrabold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-8">
                <?php echo htmlspecialchars($page['title']); ?>
            </h1>
            
            <div class="prose-modern max-w-none">
                <?php echo $page['content']; ?>
            </div>
        </article>
    </main>

    <footer class="bg-gradient-to-r from-blue-900 to-indigo-900 text-white mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <p class="text-blue-100">&copy; <?php echo date('Y'); ?> Hajime CMS. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <style>
        .prose-modern {
            color: #1f2937;
            line-height: 1.8;
            font-size: 1.125rem;
        }
        .prose-modern h2 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #2563eb, #4f46e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .prose-modern h3 {
            font-size: 1.875rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #4f46e5;
        }
        .prose-modern p {
            margin-bottom: 1.5rem;
        }
        .prose-modern ul, .prose-modern ol {
            margin-bottom: 1.5rem;
            padding-left: 2rem;
        }
        .prose-modern li {
            margin-bottom: 0.75rem;
        }
        .prose-modern a {
            color: #2563eb;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: border-color 0.3s;
        }
        .prose-modern a:hover {
            border-bottom-color: #2563eb;
        }
        .prose-modern img {
            max-width: 100%;
            height: auto;
            border-radius: 1rem;
            margin: 2rem 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .prose-modern blockquote {
            border-left: 4px solid #4f46e5;
            padding-left: 1.5rem;
            margin: 2rem 0;
            color: #6b7280;
            font-style: italic;
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
        }
        .prose-modern strong {
            color: #4f46e5;
            font-weight: 600;
        }
    </style>
</body>
</html>
