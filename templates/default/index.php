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
<body class="bg-gray-50">
    <header class="bg-white shadow-sm">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <a href="<?php echo SITE_URL; ?>" class="text-2xl font-bold text-gray-800">
                Hajime CMS
                </a>
                <div class="space-x-6">
                    <?php
                    // 公開ページのナビゲーションを取得
                    $navPages = $pageModel->getPublishedPages();
                    foreach ($navPages as $navPage):
                    ?>
                        <a 
                            href="?page=<?php echo htmlspecialchars($navPage['slug']); ?>" 
                            class="text-gray-600 hover:text-gray-900 transition-colors <?php echo $navPage['slug'] === $slug ? 'font-semibold text-gray-900' : ''; ?>"
                        >
                            <?php echo htmlspecialchars($navPage['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <article class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-6">
                <?php echo htmlspecialchars($page['title']); ?>
            </h1>
            
            <div class="prose max-w-none">
                <?php echo $page['content']; ?>
            </div>
        </article>
    </main>

    <footer class="bg-gray-800 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Hajime CMS. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <style>
        .prose {
            color: #374151;
            line-height: 1.75;
        }
        .prose h2 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        .prose h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #1f2937;
        }
        .prose p {
            margin-bottom: 1.25rem;
        }
        .prose ul, .prose ol {
            margin-bottom: 1.25rem;
            padding-left: 1.5rem;
        }
        .prose li {
            margin-bottom: 0.5rem;
        }
        .prose a {
            color: #2563eb;
            text-decoration: underline;
        }
        .prose a:hover {
            color: #1d4ed8;
        }
        .prose img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
        }
        .prose blockquote {
            border-left: 4px solid #e5e7eb;
            padding-left: 1rem;
            margin: 1.5rem 0;
            color: #6b7280;
            font-style: italic;
        }
    </style>
</body>
</html>
