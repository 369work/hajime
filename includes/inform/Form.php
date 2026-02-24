<?php
/**
 * Form クラス
 * 
 * フォームの作成、取得、更新、削除を管理します。
 * 要件: 1.1, 1.2, 1.3, 1.4, 1.5, 9.3
 */

require_once __DIR__ . '/Logger.php';

class Form {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * フォームを作成
     * 
     * @param string $name フォーム名
     * @param string $description フォームの説明
     * @param array $settings フォーム設定（JSON形式）
     * @return int 作成されたフォームのID
     * @throws Exception データベースエラー時
     */
    public function create($name, $description, $settings = []) {
        // 入力バリデーション
        if (empty($name)) {
            throw new InvalidArgumentException('フォーム名は必須です');
        }

        // 設定をJSON形式に変換
        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE);
        if ($settingsJson === false) {
            throw new InvalidArgumentException('設定のJSON変換に失敗しました');
        }

        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "INSERT INTO inform_forms (name, description, settings, created_at, updated_at) 
                VALUES (:name, :description, :settings, NOW(), NOW())";
        
        try {
            $stmt = $this->db->query($sql, [
                ':name' => $name,
                ':description' => $description,
                ':settings' => $settingsJson
            ]);
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            Logger::logDatabaseError('Form::create', 'Failed to create form', $e);
            throw new Exception('フォームの作成に失敗しました');
        }
    }

    /**
     * IDでフォームを取得
     * 
     * @param int $id フォームID
     * @return array|null フォームデータ、存在しない場合はnull
     * @throws Exception データベースエラー時
     */
    public function getById($id) {
        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "SELECT id, name, description, settings, created_at, updated_at 
                FROM inform_forms 
                WHERE id = :id";
        
        try {
            $stmt = $this->db->query($sql, [':id' => $id]);
            $form = $stmt->fetch();
            
            if ($form === false) {
                return null;
            }
            
            // JSON設定をデコード
            $form['settings'] = json_decode($form['settings'], true);
            if ($form['settings'] === null) {
                $form['settings'] = [];
            }
            
            return $form;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Form::getById', 'Failed to get form by ID: ' . $id, $e);
            throw new Exception('フォームの取得に失敗しました');
        }
    }

    /**
     * すべてのフォームを取得
     * 
     * @return array フォームの配列
     * @throws Exception データベースエラー時
     */
    public function getAll() {
        $sql = "SELECT id, name, description, settings, created_at, updated_at 
                FROM inform_forms 
                ORDER BY created_at DESC";
        
        try {
            $stmt = $this->db->query($sql);
            $forms = $stmt->fetchAll();
            
            // 各フォームのJSON設定をデコード
            foreach ($forms as &$form) {
                $form['settings'] = json_decode($form['settings'], true);
                if ($form['settings'] === null) {
                    $form['settings'] = [];
                }
            }
            
            return $forms;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Form::getAll', 'Failed to get all forms', $e);
            throw new Exception('フォーム一覧の取得に失敗しました');
        }
    }

    /**
     * フォームを更新
     * 
     * @param int $id フォームID
     * @param string $name フォーム名
     * @param string $description フォームの説明
     * @param array $settings フォーム設定（JSON形式）
     * @return bool 成功した場合true
     * @throws Exception データベースエラー時
     */
    public function update($id, $name, $description, $settings = []) {
        // 入力バリデーション
        if (empty($name)) {
            throw new InvalidArgumentException('フォーム名は必須です');
        }

        // 設定をJSON形式に変換
        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE);
        if ($settingsJson === false) {
            throw new InvalidArgumentException('設定のJSON変換に失敗しました');
        }

        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "UPDATE inform_forms 
                SET name = :name, 
                    description = :description, 
                    settings = :settings, 
                    updated_at = NOW() 
                WHERE id = :id";
        
        try {
            $stmt = $this->db->query($sql, [
                ':id' => $id,
                ':name' => $name,
                ':description' => $description,
                ':settings' => $settingsJson
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Form::update', 'Failed to update form ID: ' . $id, $e);
            throw new Exception('フォームの更新に失敗しました');
        }
    }

    /**
     * フォームを削除
     * 
     * 外部キー制約により、関連するフィールドと送信データも自動的に削除されます（CASCADE）
     * 
     * @param int $id フォームID
     * @return bool 成功した場合true
     * @throws Exception データベースエラー時
     */
    public function delete($id) {
        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "DELETE FROM inform_forms WHERE id = :id";
        
        try {
            $stmt = $this->db->query($sql, [':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Form::delete', 'Failed to delete form ID: ' . $id, $e);
            throw new Exception('フォームの削除に失敗しました');
        }
    }

    /**
     * フォームのフィールドを取得
     * 
     * @param int $formId フォームID
     * @return array フィールドの配列（sort_order順）
     * @throws Exception データベースエラー時
     */
    public function getFields($formId) {
        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "SELECT id, form_id, type, label, name, config, sort_order, created_at 
                FROM inform_fields 
                WHERE form_id = :form_id 
                ORDER BY sort_order ASC";
        
        try {
            $stmt = $this->db->query($sql, [':form_id' => $formId]);
            $fields = $stmt->fetchAll();
            
            // 各フィールドのJSON設定をデコード
            foreach ($fields as &$field) {
                $field['config'] = json_decode($field['config'], true);
                if ($field['config'] === null) {
                    $field['config'] = [];
                }
            }
            
            return $fields;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Form::getFields', 'Failed to get fields for form ID: ' . $formId, $e);
            throw new Exception('フィールドの取得に失敗しました');
        }
    }

    /**
     * フォームのフィールドを保存
     * 
     * 既存のフィールドを削除して、新しいフィールドを保存します。
     * 
     * @param int $formId フォームID
     * @param array $fields フィールドの配列
     * @return bool 成功した場合true
     * @throws Exception データベースエラー時
     */
    public function saveFields($formId, $fields) {
        try {
            // トランザクション開始
            $conn = $this->db->getConnection();
            $conn->beginTransaction();

            // 既存のフィールドを削除
            $deleteSql = "DELETE FROM inform_fields WHERE form_id = :form_id";
            $this->db->query($deleteSql, [':form_id' => $formId]);

            // 新しいフィールドを挿入
            $insertSql = "INSERT INTO inform_fields 
                         (form_id, type, label, name, config, sort_order, created_at) 
                         VALUES (:form_id, :type, :label, :name, :config, :sort_order, NOW())";

            foreach ($fields as $index => $field) {
                // フィールドバリデーション
                if (empty($field['type'])) {
                    throw new InvalidArgumentException('フィールドタイプは必須です');
                }
                if (empty($field['label'])) {
                    throw new InvalidArgumentException('フィールドラベルは必須です');
                }
                if (empty($field['name'])) {
                    throw new InvalidArgumentException('フィールド名は必須です');
                }

                // 設定をJSON形式に変換
                $config = isset($field['config']) ? $field['config'] : [];
                $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);
                if ($configJson === false) {
                    throw new InvalidArgumentException('フィールド設定のJSON変換に失敗しました');
                }

                $this->db->query($insertSql, [
                    ':form_id' => $formId,
                    ':type' => $field['type'],
                    ':label' => $field['label'],
                    ':name' => $field['name'],
                    ':config' => $configJson,
                    ':sort_order' => $index
                ]);
            }

            // トランザクションコミット
            $conn->commit();
            return true;

        } catch (Exception $e) {
            // エラー時はロールバック
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            Logger::logDatabaseError('Form::saveFields', 'Failed to save fields for form ID: ' . $formId, $e);
            throw new Exception('フィールドの保存に失敗しました: ' . $e->getMessage());
        }
    }
}
