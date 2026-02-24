<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * タグ管理クラス
 *
 * タグのCRUD操作とページへの紐付けを提供する。
 */
class Tag
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 全タグを取得（使用数付き）
     *
     * @return array
     */
    public function getAll(): array
    {
        $sql = "SELECT t.*, COUNT(pt.page_id) as page_count
                FROM tags t
                LEFT JOIN page_tags pt ON t.id = pt.tag_id
                GROUP BY t.id
                ORDER BY t.name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * IDでタグを取得
     *
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        $sql = "SELECT * FROM tags WHERE id = ?";
        return $this->db->query($sql, [$id])->fetch();
    }

    /**
     * スラッグでタグを取得
     *
     * @param string $slug
     * @return array|false
     */
    public function getBySlug(string $slug)
    {
        $sql = "SELECT * FROM tags WHERE slug = ?";
        return $this->db->query($sql, [$slug])->fetch();
    }

    /**
     * タグを作成
     *
     * @param string $name タグ名
     * @return int 新規ID
     */
    public function create(string $name): int
    {
        $slug = $this->generateSlug($name);
        $slug = $this->ensureUniqueSlug($slug);

        $sql = "INSERT INTO tags (name, slug) VALUES (?, ?)";
        $this->db->query($sql, [$name, $slug]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * タグを削除
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // page_tags は ON DELETE CASCADE で自動削除
        $sql = "DELETE FROM tags WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * ページにタグを紐付け（既存の紐付けをリセット）
     *
     * @param int $pageId
     * @param array $tagIds タグIDの配列
     */
    public function syncPageTags(int $pageId, array $tagIds): void
    {
        // 既存の紐付けを削除
        $sql = "DELETE FROM page_tags WHERE page_id = ?";
        $this->db->query($sql, [$pageId]);

        // 新しく紐付け
        if (!empty($tagIds)) {
            $sql = "INSERT INTO page_tags (page_id, tag_id) VALUES (?, ?)";
            foreach ($tagIds as $tagId) {
                $tagId = (int) $tagId;
                if ($tagId > 0) {
                    $this->db->query($sql, [$pageId, $tagId]);
                }
            }
        }
    }

    /**
     * ページに紐付くタグ一覧を取得
     *
     * @param int $pageId
     * @return array
     */
    public function getTagsByPageId(int $pageId): array
    {
        $sql = "SELECT t.* FROM tags t
                INNER JOIN page_tags pt ON t.id = pt.tag_id
                WHERE pt.page_id = ?
                ORDER BY t.name ASC";
        return $this->db->query($sql, [$pageId])->fetchAll();
    }

    /**
     * 名前からスラッグを生成
     */
    private function generateSlug(string $name): string
    {
        $slug = mb_strtolower($name, 'UTF-8');
        $slug = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'tag-' . time();
        }

        return $slug;
    }

    /**
     * ユニークなスラッグを確保
     */
    private function ensureUniqueSlug(string $slug): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $existing = $this->db->query("SELECT id FROM tags WHERE slug = ?", [$slug])->fetch();
            if (!$existing) {
                break;
            }
            $slug = $originalSlug . '-' . (++$counter);
        }

        return $slug;
    }
}
