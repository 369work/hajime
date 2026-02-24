<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * メニュー管理クラス
 *
 * サイトナビゲーションのメニュー項目を管理する。
 */
class Menu
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 全メニュー項目を取得（管理画面用）
     *
     * @return array
     */
    public function getAll(): array
    {
        $sql = "SELECT m.*,
                    CASE
                        WHEN m.type = 'page' THEN p.title
                        WHEN m.type = 'category' THEN c.name
                        ELSE NULL
                    END AS reference_name
                FROM menus m
                LEFT JOIN pages p ON m.type = 'page' AND m.reference_id = p.id
                LEFT JOIN categories c ON m.type = 'category' AND m.reference_id = c.id
                ORDER BY m.sort_order ASC, m.id ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * 有効なメニュー項目のみ取得（フロントエンド用）
     * URLを自動生成して返す
     *
     * @return array
     */
    public function getActiveMenus(): array
    {
        $sql = "SELECT m.*,
                    CASE
                        WHEN m.type = 'page' THEN p.slug
                        ELSE NULL
                    END AS page_slug,
                    CASE
                        WHEN m.type = 'category' THEN c.slug
                        ELSE NULL
                    END AS category_slug
                FROM menus m
                LEFT JOIN pages p ON m.type = 'page' AND m.reference_id = p.id
                LEFT JOIN categories c ON m.type = 'category' AND m.reference_id = c.id
                WHERE m.is_active = 1
                ORDER BY m.sort_order ASC, m.id ASC";
        $items = $this->db->query($sql)->fetchAll();

        // URLを生成
        foreach ($items as &$item) {
            switch ($item['type']) {
                case 'page':
                    $item['href'] = '?page=' . ($item['page_slug'] ?? '');
                    break;
                case 'category':
                    $item['href'] = '?category=' . ($item['category_slug'] ?? '');
                    break;
                case 'custom':
                    $item['href'] = $item['url'] ?? '#';
                    break;
                default:
                    $item['href'] = '#';
            }
        }
        unset($item);

        return $items;
    }

    /**
     * IDでメニュー項目を取得
     *
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        $sql = "SELECT * FROM menus WHERE id = ?";
        return $this->db->query($sql, [$id])->fetch();
    }

    /**
     * メニュー項目を作成
     *
     * @param array $data
     * @return int 新規ID
     */
    public function create(array $data): int
    {
        // sort_orderを自動設定（末尾に追加）
        $maxOrder = $this->db->query("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM menus")->fetch();
        $sortOrder = ($maxOrder['max_order'] ?? 0) + 1;

        $sql = "INSERT INTO menus (label, css_id, type, reference_id, url, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['label'],
            !empty($data['css_id']) ? $data['css_id'] : null,
            $data['type'],
            ($data['type'] !== 'custom' && !empty($data['reference_id'])) ? (int) $data['reference_id'] : null,
            ($data['type'] === 'custom') ? ($data['url'] ?? '') : null,
            $sortOrder,
            (int) ($data['is_active'] ?? 1)
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * メニュー項目を更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE menus SET label = ?, css_id = ?, type = ?, reference_id = ?, url = ?, is_active = ? WHERE id = ?";
        $this->db->query($sql, [
            $data['label'],
            !empty($data['css_id']) ? $data['css_id'] : null,
            $data['type'],
            ($data['type'] !== 'custom' && !empty($data['reference_id'])) ? (int) $data['reference_id'] : null,
            ($data['type'] === 'custom') ? ($data['url'] ?? '') : null,
            (int) ($data['is_active'] ?? 1),
            $id
        ]);
        return true;
    }

    /**
     * メニュー項目を削除
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM menus WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * 並び順を一括更新
     *
     * @param array $orderedIds IDの配列（順序通り）
     * @return bool
     */
    public function updateOrder(array $orderedIds): bool
    {
        $sql = "UPDATE menus SET sort_order = ? WHERE id = ?";
        foreach ($orderedIds as $index => $id) {
            $this->db->query($sql, [$index + 1, (int) $id]);
        }
        return true;
    }

    /**
     * 有効/無効を切り替え
     *
     * @param int $id
     * @return bool
     */
    public function toggleActive(int $id): bool
    {
        $sql = "UPDATE menus SET is_active = NOT is_active WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }
}
