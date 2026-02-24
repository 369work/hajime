-- Hajime CMS 画像アップロード テーブル

USE hajime_db;

-- アップロード画像テーブル
CREATE TABLE IF NOT EXISTS uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL COMMENT '保存ファイル名（ユニーク生成）',
    original_name VARCHAR(255) NOT NULL COMMENT '元のファイル名',
    mime_type VARCHAR(100) NOT NULL COMMENT 'MIMEタイプ',
    file_size INT NOT NULL COMMENT 'ファイルサイズ（バイト）',
    width INT DEFAULT NULL COMMENT '画像の幅（px）',
    height INT DEFAULT NULL COMMENT '画像の高さ（px）',
    alt_text VARCHAR(500) DEFAULT '' COMMENT 'alt属性テキスト',
    uploaded_by INT NOT NULL COMMENT 'アップロードしたユーザーID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'アップロード日時',
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_created (created_at),
    INDEX idx_mime (mime_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='アップロード画像管理';
