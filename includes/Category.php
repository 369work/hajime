<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * カテゴリー管理クラス
 *
 * 階層構造を持つカテゴリーのCRUD操作を提供する。
 */
class Category
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 全カテゴリーを取得（ツリー構造）
     *
     * @return array 階層構造のカテゴリー配列
     */
    public function getTree(): array
    {
        $sql = "SELECT * FROM categories ORDER BY sort_order ASC, name ASC";
        $categories = $this->db->query($sql)->fetchAll();

        return $this->buildTree($categories);
    }

    /**
     * 全カテゴリーをフラット配列で取得（ナビゲーション向け）
     *
     * @return array
     */
    public function getAll(): array
    {
        $sql = "SELECT * FROM categories ORDER BY sort_order ASC, name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * フラットな配列からツリー構造を構築
     *
     * @param array $categories フラットなカテゴリー配列
     * @param int|null $parentId 親カテゴリーID
     * @return array ツリー構造の配列
     */
    private function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, (int) $category['id']);
                $category['children'] = $children;
                $tree[] = $category;
            }
        }
        return $tree;
    }

    /**
     * 全カテゴリーをフラットリストで取得（セレクトボックス向け、インデント付き）
     *
     * @return array ['id' => int, 'name' => string, 'depth' => int, ...]
     */
    public function getFlatList(): array
    {
        $tree = $this->getTree();
        $flat = [];
        $this->flattenTree($tree, $flat, 0);
        return $flat;
    }

    /**
     * ツリーをフラットリストに変換（再帰）
     */
    private function flattenTree(array $tree, array &$flat, int $depth): void
    {
        foreach ($tree as $category) {
            $category['depth'] = $depth;
            $category['indented_name'] = str_repeat('　', $depth) . $category['name'];
            $children = $category['children'];
            unset($category['children']);
            $flat[] = $category;
            $this->flattenTree($children, $flat, $depth + 1);
        }
    }

    /**
     * IDでカテゴリーを取得
     *
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        $sql = "SELECT * FROM categories WHERE id = ?";
        return $this->db->query($sql, [$id])->fetch();
    }

    /**
     * スラッグでカテゴリーを取得
     *
     * @param string $slug
     * @return array|false
     */
    public function getBySlug(string $slug)
    {
        $sql = "SELECT * FROM categories WHERE slug = ?";
        return $this->db->query($sql, [$slug])->fetch();
    }

    /**
     * カテゴリーを作成
     *
     * @param array $data ['name', 'slug', 'description', 'parent_id', 'sort_order']
     * @return int 新規ID
     */
    public function create(array $data): int
    {
        $slug = $data['slug'] ?: $this->generateSlug($data['name']);
        $slug = $this->ensureUniqueSlug($slug);

        $sql = "INSERT INTO categories (name, slug, description, parent_id, sort_order)
                VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['name'],
            $slug,
            $data['description'] ?? '',
            $data['parent_id'] ?: null,
            (int) ($data['sort_order'] ?? 0),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * カテゴリーを更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $slug = $data['slug'] ?: $this->generateSlug($data['name']);
        $slug = $this->ensureUniqueSlug($slug, $id);

        // 自分自身を親にしない
        $parentId = $data['parent_id'] ?: null;
        if ($parentId !== null && (int) $parentId === $id) {
            $parentId = null;
        }

        $sql = "UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, sort_order = ? WHERE id = ?";
        $this->db->query($sql, [
            $data['name'],
            $slug,
            $data['description'] ?? '',
            $parentId,
            (int) ($data['sort_order'] ?? 0),
            $id,
        ]);

        return true;
    }

    /**
     * カテゴリーを削除
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        // 子カテゴリーの親をNULLに設定（ON DELETE SET NULLで対応済み）
        $sql = "DELETE FROM categories WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * カテゴリーに属するページ数を取得
     *
     * @param int $id
     * @return int
     */
    public function getPageCount(int $id): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM pages WHERE category_id = ?";
        return (int) $this->db->query($sql, [$id])->fetch()['cnt'];
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
            $slug = 'category-' . time();
        }

        return $slug;
    }

    /**
     * ユニークなスラッグを確保
     */
    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM categories WHERE slug = ?";
            $params = [$slug];

            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $existing = $this->db->query($sql, $params)->fetch();
            if (!$existing) {
                break;
            }

            $slug = $originalSlug . '-' . (++$counter);
        }

        return $slug;
    }
}
