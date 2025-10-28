<?php
require_once __DIR__ . '/Database.php';

class Page {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * すべてのページを取得
     */
    public function getAllPages($orderBy = 'created_at DESC') {
        $sql = "SELECT * FROM pages ORDER BY {$orderBy}";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * 公開中のページを取得
     */
    public function getPublishedPages() {
        $sql = "SELECT * FROM pages WHERE status = 'published' ORDER BY created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * IDでページを取得
     */
    public function getPageById($id) {
        $sql = "SELECT * FROM pages WHERE id = ?";
        return $this->db->query($sql, [$id])->fetch();
    }

    /**
     * スラッグでページを取得
     */
    public function getPageBySlug($slug) {
        $sql = "SELECT * FROM pages WHERE slug = ? AND status = 'published'";
        return $this->db->query($sql, [$slug])->fetch();
    }

    /**
     * ページを作成
     */
    public function createPage($data) {
        $sql = "INSERT INTO pages (title, slug, content, template, status, meta_title, meta_description, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $this->db->query($sql, [
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['template'] ?? 'default',
            $data['status'] ?? 'draft',
            $data['meta_title'] ?? '',
            $data['meta_description'] ?? ''
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * ページを更新
     */
    public function updatePage($id, $data) {
        $sql = "UPDATE pages SET 
                title = ?, 
                slug = ?, 
                content = ?, 
                template = ?, 
                status = ?, 
                meta_title = ?, 
                meta_description = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        return $this->db->query($sql, [
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['template'] ?? 'default',
            $data['status'] ?? 'draft',
            $data['meta_title'] ?? '',
            $data['meta_description'] ?? '',
            $id
        ]);
    }

    /**
     * ページを削除
     */
    public function deletePage($id) {
        $sql = "DELETE FROM pages WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    /**
     * スラッグの重複チェック
     */
    public function isSlugExists($slug, $excludeId = null) {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM pages WHERE slug = ? AND id != ?";
            $result = $this->db->query($sql, [$slug, $excludeId])->fetch();
        } else {
            $sql = "SELECT COUNT(*) as count FROM pages WHERE slug = ?";
            $result = $this->db->query($sql, [$slug])->fetch();
        }
        
        return $result['count'] > 0;
    }

    /**
     * ユニークなスラッグを生成
     */
    public function generateUniqueSlug($title, $excludeId = null) {
        $slug = $this->createSlug($title);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->isSlugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * タイトルからスラッグを作成
     */
    private function createSlug($string) {
        // 日本語をローマ字に変換（簡易版）
        $string = mb_convert_kana($string, 'as', 'UTF-8');
        
        // 小文字に変換
        $string = strtolower($string);
        
        // 英数字とハイフン以外を削除
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        
        // 連続するハイフンを1つに
        $string = preg_replace('/-+/', '-', $string);
        
        // 前後のハイフンを削除
        $string = trim($string, '-');
        
        return $string;
    }
}
