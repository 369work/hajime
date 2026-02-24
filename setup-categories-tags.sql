-- Hajime CMS カテゴリー・タグ テーブル

USE hajime_db;

-- カテゴリーテーブル（階層構造対応）
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'カテゴリー名',
    slug VARCHAR(100) UNIQUE NOT NULL COMMENT 'URLスラッグ',
    description TEXT DEFAULT NULL COMMENT '説明文',
    parent_id INT DEFAULT NULL COMMENT '親カテゴリーID',
    sort_order INT DEFAULT 0 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_parent (parent_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='カテゴリー管理';

-- タグテーブル
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'タグ名',
    slug VARCHAR(100) UNIQUE NOT NULL COMMENT 'URLスラッグ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='タグ管理';

-- ページとタグの中間テーブル（多対多）
CREATE TABLE IF NOT EXISTS page_tags (
    page_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (page_id, tag_id),
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ページとタグの紐付け';

-- pagesテーブルにcategory_idカラムを追加
ALTER TABLE pages ADD COLUMN category_id INT DEFAULT NULL COMMENT 'カテゴリーID' AFTER template;
ALTER TABLE pages ADD FOREIGN KEY fk_page_category (category_id) REFERENCES categories(id) ON DELETE SET NULL;
ALTER TABLE pages ADD INDEX idx_category (category_id);

-- サンプルカテゴリーを挿入
INSERT INTO categories (name, slug, description, sort_order) VALUES
('お知らせ', 'news', 'ニュースやお知らせ', 1),
('ブログ', 'blog', 'ブログ記事', 2),
('サービス', 'services', 'サービス紹介', 3)
ON DUPLICATE KEY UPDATE name=name;

-- サンプルタグを挿入
INSERT INTO tags (name, slug) VALUES
('重要', 'important'),
('更新', 'update'),
('お知らせ', 'announcement')
ON DUPLICATE KEY UPDATE name=name;
