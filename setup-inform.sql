-- Inform お問い合わせフォームシステム データベーススキーマ
-- Hajime CMSに統合される埋め込み可能なお問い合わせフォームシステム

USE hajime_db;

-- フォームテーブル
-- フォームの基本情報と設定を保存
CREATE TABLE IF NOT EXISTS inform_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL COMMENT 'フォーム名',
    description TEXT COMMENT 'フォームの説明',
    settings JSON COMMENT 'メール通知設定など（email_notifications, notification_emails, success_message, redirect_url）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='お問い合わせフォームの定義';

-- フォームフィールドテーブル
-- 各フォームのフィールド定義を保存
CREATE TABLE IF NOT EXISTS inform_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL COMMENT '所属するフォームのID',
    type ENUM('text', 'email', 'textarea', 'select', 'checkbox', 'radio') NOT NULL COMMENT 'フィールドタイプ',
    label VARCHAR(200) NOT NULL COMMENT 'フィールドのラベル',
    name VARCHAR(100) NOT NULL COMMENT 'フィールドの内部名',
    config JSON COMMENT 'フィールド設定（placeholder, required, max_length, options, validation_pattern）',
    sort_order INT DEFAULT 0 COMMENT '表示順序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (form_id) REFERENCES inform_forms(id) ON DELETE CASCADE,
    INDEX idx_form_sort (form_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='フォームのフィールド定義';

-- 送信データテーブル
-- フォームから送信されたデータを保存
CREATE TABLE IF NOT EXISTS inform_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL COMMENT '送信元フォームのID',
    data JSON NOT NULL COMMENT '送信されたフィールドデータ（フィールド名: 値のペア）',
    ip_address VARCHAR(45) COMMENT '送信元IPアドレス（IPv4/IPv6対応）',
    user_agent TEXT COMMENT '送信元ブラウザのUser-Agent',
    status ENUM('new', 'in_progress', 'resolved') DEFAULT 'new' COMMENT '対応ステータス',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '送信日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (form_id) REFERENCES inform_forms(id) ON DELETE CASCADE,
    INDEX idx_form_status (form_id, status),
    INDEX idx_created (created_at),
    INDEX idx_ip (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='フォーム送信データ';

-- スパム対策チャレンジテーブル
-- 画像ベースの日本語質問チャレンジを管理
CREATE TABLE IF NOT EXISTS inform_challenges (
    id VARCHAR(64) PRIMARY KEY COMMENT 'チャレンジの一意識別子（ハッシュ）',
    question_index INT NOT NULL COMMENT '質問セットのインデックス',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    expires_at TIMESTAMP NOT NULL COMMENT '有効期限',
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='スパム対策チャレンジ';

-- レート制限テーブル
-- IPアドレスごとの送信回数を追跡してスパムを防止
CREATE TABLE IF NOT EXISTS inform_rate_limits (
    ip_address VARCHAR(45) PRIMARY KEY COMMENT 'IPアドレス（IPv4/IPv6対応）',
    submission_count INT DEFAULT 1 COMMENT '送信回数',
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'レート制限ウィンドウの開始時刻',
    INDEX idx_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='レート制限管理';
