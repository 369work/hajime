<?php
/**
 * FormField クラス
 * 
 * フォームフィールドの定義と検証を管理します。
 * 要件: 2.1, 2.2, 2.3, 2.4, 2.5
 */
class FormField {
    private $db;

    // フィールドタイプ定数
    const TYPE_TEXT = 'text';
    const TYPE_EMAIL = 'email';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_SELECT = 'select';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RADIO = 'radio';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * フィールドを作成
     * 
     * @param int $formId フォームID
     * @param string $type フィールドタイプ
     * @param string $label フィールドラベル
     * @param array $config フィールド設定（placeholder, required, max_length, options, validation_pattern）
     * @return int 作成されたフィールドのID
     * @throws Exception データベースエラー時
     */
    public function create($formId, $type, $label, $config = []) {
        // 入力バリデーション
        if (empty($label)) {
            throw new InvalidArgumentException('フィールドラベルは必須です');
        }

        if (!$this->isValidType($type)) {
            throw new InvalidArgumentException('無効なフィールドタイプです');
        }

        // フィールド名を自動生成（ラベルから）
        $name = $this->generateFieldName($label);

        // 設定をJSON形式に変換
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);
        if ($configJson === false) {
            throw new InvalidArgumentException('設定のJSON変換に失敗しました');
        }

        // sort_orderを取得（最後に追加）
        $sortOrder = $this->getNextSortOrder($formId);

        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "INSERT INTO inform_fields (form_id, type, label, name, config, sort_order, created_at) 
                VALUES (:form_id, :type, :label, :name, :config, :sort_order, NOW())";
        
        try {
            $stmt = $this->db->query($sql, [
                ':form_id' => $formId,
                ':type' => $type,
                ':label' => $label,
                ':name' => $name,
                ':config' => $configJson,
                ':sort_order' => $sortOrder
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception('フィールドの作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * フィールドを取得
     * 
     * @param int $id フィールドID
     * @return array|null フィールドデータ
     */
    public function getById($id) {
        $sql = "SELECT * FROM inform_fields WHERE id = :id";
        
        try {
            $result = $this->db->query($sql, [':id' => $id]);
            $field = $result->fetch(PDO::FETCH_ASSOC);
            
            if ($field) {
                // JSON設定をデコード
                $field['config'] = json_decode($field['config'], true);
            }
            
            return $field ?: null;
        } catch (Exception $e) {
            throw new Exception('フィールドの取得に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * フォームのすべてのフィールドを取得
     * 
     * @param int $formId フォームID
     * @return array フィールドの配列
     */
    public function getByFormId($formId) {
        $sql = "SELECT * FROM inform_fields WHERE form_id = :form_id ORDER BY sort_order ASC";
        
        try {
            $result = $this->db->query($sql, [':form_id' => $formId]);
            $fields = $result->fetchAll(PDO::FETCH_ASSOC);
            
            // JSON設定をデコード
            foreach ($fields as &$field) {
                $field['config'] = json_decode($field['config'], true);
            }
            
            return $fields;
        } catch (Exception $e) {
            throw new Exception('フィールドの取得に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * フィールドを更新
     * 
     * @param int $id フィールドID
     * @param string $type フィールドタイプ
     * @param string $label フィールドラベル
     * @param array $config フィールド設定
     * @return bool 成功したかどうか
     */
    public function update($id, $type, $label, $config = []) {
        // 入力バリデーション
        if (empty($label)) {
            throw new InvalidArgumentException('フィールドラベルは必須です');
        }

        if (!$this->isValidType($type)) {
            throw new InvalidArgumentException('無効なフィールドタイプです');
        }

        // フィールド名を自動生成
        $name = $this->generateFieldName($label);

        // 設定をJSON形式に変換
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);
        if ($configJson === false) {
            throw new InvalidArgumentException('設定のJSON変換に失敗しました');
        }

        $sql = "UPDATE inform_fields 
                SET type = :type, label = :label, name = :name, config = :config 
                WHERE id = :id";
        
        try {
            $stmt = $this->db->query($sql, [
                ':id' => $id,
                ':type' => $type,
                ':label' => $label,
                ':name' => $name,
                ':config' => $configJson
            ]);
            
            return true;
        } catch (Exception $e) {
            throw new Exception('フィールドの更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * フィールドを削除
     * 
     * @param int $id フィールドID
     * @return bool 成功したかどうか
     */
    public function delete($id) {
        $sql = "DELETE FROM inform_fields WHERE id = :id";
        
        try {
            $stmt = $this->db->query($sql, [':id' => $id]);
            return true;
        } catch (Exception $e) {
            throw new Exception('フィールドの削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * フィールド値をバリデーション
     * 
     * @param mixed $value 検証する値
     * @param array $fieldConfig フィールド設定
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validate($value, $fieldConfig) {
        $config = $fieldConfig['config'] ?? [];
        $type = $fieldConfig['type'] ?? self::TYPE_TEXT;
        $label = $fieldConfig['label'] ?? 'フィールド';

        // 必須チェック
        if (!empty($config['required']) && empty($value)) {
            return [
                'valid' => false,
                'error' => "{$label}は必須です"
            ];
        }

        // 値が空の場合、必須でなければOK
        if (empty($value)) {
            return ['valid' => true, 'error' => null];
        }

        // タイプ別のバリデーション
        switch ($type) {
            case self::TYPE_EMAIL:
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return [
                        'valid' => false,
                        'error' => '有効なメールアドレスを入力してください'
                    ];
                }
                break;

            case self::TYPE_TEXT:
            case self::TYPE_TEXTAREA:
                // 最大長チェック
                if (!empty($config['max_length'])) {
                    $maxLength = (int)$config['max_length'];
                    if (mb_strlen($value) > $maxLength) {
                        return [
                            'valid' => false,
                            'error' => "{$label}は{$maxLength}文字以内で入力してください"
                        ];
                    }
                }
                break;

            case self::TYPE_SELECT:
            case self::TYPE_RADIO:
                // オプションに含まれているかチェック
                if (!empty($config['options'])) {
                    if (!in_array($value, $config['options'], true)) {
                        return [
                            'valid' => false,
                            'error' => '無効な選択肢です'
                        ];
                    }
                }
                break;

            case self::TYPE_CHECKBOX:
                // チェックボックスは配列の場合がある
                if (is_array($value) && !empty($config['options'])) {
                    foreach ($value as $item) {
                        if (!in_array($item, $config['options'], true)) {
                            return [
                                'valid' => false,
                                'error' => '無効な選択肢が含まれています'
                            ];
                        }
                    }
                }
                break;
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * フィールドをHTMLとしてレンダリング
     * 
     * @param array $fieldConfig フィールド設定
     * @param mixed $value 現在の値（デフォルト値または送信エラー時の値）
     * @return string HTML文字列
     */
    public function render($fieldConfig, $value = '') {
        $config = $fieldConfig['config'] ?? [];
        $type = $fieldConfig['type'] ?? self::TYPE_TEXT;
        $label = htmlspecialchars($fieldConfig['label'] ?? '', ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($fieldConfig['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars($config['placeholder'] ?? '', ENT_QUOTES, 'UTF-8');
        $required = !empty($config['required']) ? 'required' : '';
        $requiredMark = !empty($config['required']) ? '<span class="text-red-500">*</span>' : '';

        $html = '<div class="mb-4">';
        $html .= "<label class=\"block text-gray-700 font-bold mb-2\">{$label} {$requiredMark}</label>";

        switch ($type) {
            case self::TYPE_TEXT:
            case self::TYPE_EMAIL:
                $inputType = $type === self::TYPE_EMAIL ? 'email' : 'text';
                $maxLength = !empty($config['max_length']) ? 'maxlength="' . (int)$config['max_length'] . '"' : '';
                $valueAttr = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $html .= "<input type=\"{$inputType}\" name=\"{$name}\" value=\"{$valueAttr}\" placeholder=\"{$placeholder}\" {$required} {$maxLength} class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500\">";
                break;

            case self::TYPE_TEXTAREA:
                $maxLength = !empty($config['max_length']) ? 'maxlength="' . (int)$config['max_length'] . '"' : '';
                $valueText = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $html .= "<textarea name=\"{$name}\" placeholder=\"{$placeholder}\" {$required} {$maxLength} rows=\"5\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500\">{$valueText}</textarea>";
                break;

            case self::TYPE_SELECT:
                $html .= "<select name=\"{$name}\" {$required} class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500\">";
                $html .= "<option value=\"\">選択してください</option>";
                if (!empty($config['options'])) {
                    foreach ($config['options'] as $option) {
                        $optionValue = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
                        $selected = ($value === $option) ? 'selected' : '';
                        $html .= "<option value=\"{$optionValue}\" {$selected}>{$optionValue}</option>";
                    }
                }
                $html .= "</select>";
                break;

            case self::TYPE_RADIO:
                if (!empty($config['options'])) {
                    foreach ($config['options'] as $option) {
                        $optionValue = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
                        $checked = ($value === $option) ? 'checked' : '';
                        $html .= "<div class=\"flex items-center mb-2\">";
                        $html .= "<input type=\"radio\" name=\"{$name}\" value=\"{$optionValue}\" {$checked} {$required} class=\"mr-2\">";
                        $html .= "<span>{$optionValue}</span>";
                        $html .= "</div>";
                    }
                }
                break;

            case self::TYPE_CHECKBOX:
                if (!empty($config['options'])) {
                    $valueArray = is_array($value) ? $value : [];
                    foreach ($config['options'] as $option) {
                        $optionValue = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
                        $checked = in_array($option, $valueArray) ? 'checked' : '';
                        $html .= "<div class=\"flex items-center mb-2\">";
                        $html .= "<input type=\"checkbox\" name=\"{$name}[]\" value=\"{$optionValue}\" {$checked} class=\"mr-2\">";
                        $html .= "<span>{$optionValue}</span>";
                        $html .= "</div>";
                    }
                }
                break;
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * フィールドタイプが有効かチェック
     * 
     * @param string $type フィールドタイプ
     * @return bool
     */
    private function isValidType($type) {
        $validTypes = [
            self::TYPE_TEXT,
            self::TYPE_EMAIL,
            self::TYPE_TEXTAREA,
            self::TYPE_SELECT,
            self::TYPE_CHECKBOX,
            self::TYPE_RADIO
        ];
        return in_array($type, $validTypes, true);
    }

    /**
     * ラベルからフィールド名を生成
     * 
     * @param string $label フィールドラベル
     * @return string フィールド名
     */
    private function generateFieldName($label) {
        // 日本語を含む場合はローマ字化せず、シンプルな名前を生成
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $label);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        
        // 空の場合はタイムスタンプを使用
        if (empty($name)) {
            $name = 'field_' . time();
        }
        
        return strtolower($name);
    }

    /**
     * 次のsort_orderを取得
     * 
     * @param int $formId フォームID
     * @return int 次のsort_order
     */
    private function getNextSortOrder($formId) {
        $sql = "SELECT MAX(sort_order) as max_order FROM inform_fields WHERE form_id = :form_id";
        
        try {
            $result = $this->db->query($sql, [':form_id' => $formId]);
            $row = $result->fetch(PDO::FETCH_ASSOC);
            return ($row['max_order'] ?? 0) + 1;
        } catch (Exception $e) {
            return 1;
        }
    }
}
