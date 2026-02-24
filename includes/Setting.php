<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * サイト設定管理クラス
 *
 * サイト全体の基本情報やデザインテーマ設定を管理する。
 */
class Setting
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 指定されたキーの設定値を取得
     *
     * @param string $key 設定キー
     * @param string $default デフォルト値
     * @return string
     */
    public function get(string $key, string $default = ''): string
    {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
        $result = $this->db->query($sql, [$key])->fetch();

        return $result ? ($result['setting_value'] ?? $default) : $default;
    }

    /**
     * 全設定を取得
     *
     * @return array [key => value] の連想配列
     */
    public function getAll(): array
    {
        $sql = "SELECT setting_key, setting_value FROM settings";
        $results = $this->db->query($sql)->fetchAll();

        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    /**
     * 設定値を保存（存在すれば更新、なければ作成）
     *
     * @param string $key 設定キー
     * @param string $value 設定値
     * @return bool
     */
    public function set(string $key, ?string $value): bool
    {
        $value = (string) $value;
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = ?";
        $this->db->query($sql, [$key, $value, $value]);

        return true;
    }

    /**
     * 複数の設定を一括更新
     *
     * @param array $settings [key => value] の連想配列
     * @return bool
     */
    public function updateAll(array $settings): bool
    {
        try {
            $this->db->beginTransaction();
            foreach ($settings as $key => $value) {
                $this->set($key, $value);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
