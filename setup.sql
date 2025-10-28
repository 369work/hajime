-- Simple CMS データベース

-- データベースの作成
CREATE DATABASE IF NOT EXISTS simple_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simple_cms;

-- ユーザーテーブル
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'editor') DEFAULT 'editor',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ページテーブル
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content TEXT,
    template VARCHAR(50) DEFAULT 'default',
    status ENUM('draft', 'published') DEFAULT 'draft',
    meta_title VARCHAR(200),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- デフォルトの管理者ユーザーを作成（パスワード: admin）
INSERT INTO users (username, password, email, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin')
ON DUPLICATE KEY UPDATE username=username;

-- サンプルページを作成
INSERT INTO pages (title, slug, content, template, status, meta_title, meta_description)
VALUES 
(
    'ホーム',
    'home',
    '<h2>Simple CMSへようこそ</h2>
<p>これはシンプルで使いやすいコンテンツ管理システムです。</p>
<h3>特徴</h3>
<ul>
<li>シンプルで直感的な管理画面</li>
<li>カスタマイズ可能なテンプレート</li>
<li>SEO対応のメタタグ設定</li>
<li>レスポンシブデザイン</li>
</ul>
<p>管理画面にアクセスするには、<a href="/simple-cms/admin/">こちら</a>をクリックしてください。</p>',
    'default',
    'published',
    'ホーム - Simple CMS',
    'シンプルで使いやすいコンテンツ管理システム'
),
(
    '私たちについて',
    'about',
    '<h2>Simple CMSについて</h2>
<p>Simple CMSは、PHPとMySQLで構築された軽量なコンテンツ管理システムです。</p>
<p>シンプルさと使いやすさを重視しており、小規模なウェブサイトに最適です。</p>
<h3>主な機能</h3>
<ul>
<li>ページ管理</li>
<li>テンプレートシステム</li>
<li>ユーザー認証</li>
<li>SEO設定</li>
</ul>',
    'modern',
    'published',
    '私たちについて - Simple CMS',
    'Simple CMSの紹介と主な機能について'
)
ON DUPLICATE KEY UPDATE title=title;
