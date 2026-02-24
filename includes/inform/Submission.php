<?php
/**
 * Submission クラス
 * 
 * フォーム送信データの保存と管理を行います。
 * 要件: 5.5, 5.6, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 9.5
 */

require_once __DIR__ . '/Logger.php';

class Submission {
    private $db;

    // ステータス定数
    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';

    // レート制限設定
    const RATE_LIMIT_MAX_SUBMISSIONS = 5;  // 最大送信回数
    const RATE_LIMIT_WINDOW_MINUTES = 60;  // 時間枠（分）

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * 送信を作成
     * 
     * @param int $formId フォームID
     * @param array $data 送信データ（フィールド名 => 値の配列）
     * @param string $ipAddress IPアドレス
     * @param string $userAgent ユーザーエージェント
     * @return int 作成された送信のID
     * @throws Exception データベースエラー時
     */
    public function create($formId, $data, $ipAddress, $userAgent = '') {
        // 入力バリデーション
        if (empty($formId)) {
            throw new InvalidArgumentException('フォームIDは必須です');
        }

        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException('送信データは必須です');
        }

        // データをJSON形式に変換
        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($dataJson === false) {
            throw new InvalidArgumentException('データのJSON変換に失敗しました');
        }

        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "INSERT INTO inform_submissions 
                (form_id, data, ip_address, user_agent, status, created_at, updated_at) 
                VALUES (:form_id, :data, :ip_address, :user_agent, :status, NOW(), NOW())";
        
        try {
            $stmt = $this->db->query($sql, [
                ':form_id' => $formId,
                ':data' => $dataJson,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent,
                ':status' => self::STATUS_NEW
            ]);
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            Logger::logDatabaseError('Submission::create', 'Failed to create submission for form ID: ' . $formId, $e);
            throw new Exception('送信の作成に失敗しました');
        }
    }

    /**
     * IDで送信を取得
     * 
     * @param int $id 送信ID
     * @return array|null 送信データ、存在しない場合はnull
     * @throws Exception データベースエラー時
     */
    public function getById($id) {
        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "SELECT id, form_id, data, ip_address, user_agent, status, created_at, updated_at 
                FROM inform_submissions 
                WHERE id = :id";
        
        try {
            $stmt = $this->db->query($sql, [':id' => $id]);
            $submission = $stmt->fetch();
            
            if ($submission === false) {
                return null;
            }
            
            // JSONデータをデコード
            $submission['data'] = json_decode($submission['data'], true);
            if ($submission['data'] === null) {
                $submission['data'] = [];
            }
            
            return $submission;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Submission::getById', 'Failed to get submission by ID: ' . $id, $e);
            throw new Exception('送信の取得に失敗しました');
        }
    }

    /**
     * フォームIDで送信を取得
     * 
     * @param int $formId フォームID
     * @param array $filters フィルタ条件（status, date_from, date_to）
     * @return array 送信の配列
     * @throws Exception データベースエラー時
     */
    public function getByForm($formId, $filters = []) {
        $sql = "SELECT id, form_id, data, ip_address, user_agent, status, created_at, updated_at 
                FROM inform_submissions 
                WHERE form_id = :form_id";
        
        $params = [':form_id' => $formId];

        // ステータスフィルタ
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        // 日付範囲フィルタ（開始日）
        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        // 日付範囲フィルタ（終了日）
        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY created_at DESC";
        
        try {
            $stmt = $this->db->query($sql, $params);
            $submissions = $stmt->fetchAll();
            
            // 各送信のJSONデータをデコード
            foreach ($submissions as &$submission) {
                $submission['data'] = json_decode($submission['data'], true);
                if ($submission['data'] === null) {
                    $submission['data'] = [];
                }
            }
            
            return $submissions;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Submission::getByForm', 'Failed to get submissions for form ID: ' . $formId, $e);
            throw new Exception('送信一覧の取得に失敗しました');
        }
    }

    /**
     * すべての送信を取得
     * 
     * @param array $filters フィルタ条件（form_id, status, date_from, date_to）
     * @return array 送信の配列
     * @throws Exception データベースエラー時
     */
    public function getAll($filters = []) {
        $sql = "SELECT s.id, s.form_id, s.data, s.ip_address, s.user_agent, s.status, 
                       s.created_at, s.updated_at, f.name as form_name
                FROM inform_submissions s
                LEFT JOIN inform_forms f ON s.form_id = f.id
                WHERE 1=1";
        
        $params = [];

        // フォームIDフィルタ
        if (!empty($filters['form_id'])) {
            $sql .= " AND s.form_id = :form_id";
            $params[':form_id'] = $filters['form_id'];
        }

        // ステータスフィルタ
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = :status";
            $params[':status'] = $filters['status'];
        }

        // 日付範囲フィルタ（開始日）
        if (!empty($filters['date_from'])) {
            $sql .= " AND s.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        // 日付範囲フィルタ（終了日）
        if (!empty($filters['date_to'])) {
            $sql .= " AND s.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY s.created_at DESC";
        
        try {
            $stmt = $this->db->query($sql, $params);
            $submissions = $stmt->fetchAll();
            
            // 各送信のJSONデータをデコード
            foreach ($submissions as &$submission) {
                $submission['data'] = json_decode($submission['data'], true);
                if ($submission['data'] === null) {
                    $submission['data'] = [];
                }
            }
            
            return $submissions;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Submission::getAll', 'Failed to get all submissions', $e);
            throw new Exception('送信一覧の取得に失敗しました');
        }
    }

    /**
     * ステータスを更新
     * 
     * @param int $id 送信ID
     * @param string $status 新しいステータス
     * @return bool 成功した場合true
     * @throws Exception データベースエラー時
     */
    public function updateStatus($id, $status) {
        // ステータスバリデーション
        $validStatuses = [self::STATUS_NEW, self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED];
        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException('無効なステータスです');
        }

        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "UPDATE inform_submissions 
                SET status = :status, updated_at = NOW() 
                WHERE id = :id";
        
        try {
            $stmt = $this->db->query($sql, [
                ':id' => $id,
                ':status' => $status
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Submission::updateStatus', 'Failed to update status for submission ID: ' . $id, $e);
            throw new Exception('ステータスの更新に失敗しました');
        }
    }

    /**
     * 送信を削除
     * 
     * @param int $id 送信ID
     * @return bool 成功した場合true
     * @throws Exception データベースエラー時
     */
    public function delete($id) {
        // プリペアドステートメントを使用してSQLインジェクションを防止
        $sql = "DELETE FROM inform_submissions WHERE id = :id";
        
        try {
            $stmt = $this->db->query($sql, [':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Submission::delete', 'Failed to delete submission ID: ' . $id, $e);
            throw new Exception('送信の削除に失敗しました');
        }
    }

    /**
     * レート制限をチェック
     * 
     * IPアドレスごとに一定時間内の送信回数を制限します。
     * 
     * @param string $ipAddress IPアドレス
     * @return bool 送信可能な場合true、制限超過の場合false
     * @throws Exception データベースエラー時
     */
    public function checkRateLimit($ipAddress) {
        if (empty($ipAddress)) {
            throw new InvalidArgumentException('IPアドレスは必須です');
        }

        try {
            // 現在のレート制限レコードを取得
            $sql = "SELECT submission_count, window_start 
                    FROM inform_rate_limits 
                    WHERE ip_address = :ip_address";
            
            $stmt = $this->db->query($sql, [':ip_address' => $ipAddress]);
            $rateLimit = $stmt->fetch();

            $now = new DateTime();
            $windowMinutes = self::RATE_LIMIT_WINDOW_MINUTES;

            if ($rateLimit === false) {
                // レコードが存在しない場合は新規作成
                $insertSql = "INSERT INTO inform_rate_limits 
                             (ip_address, submission_count, window_start) 
                             VALUES (:ip_address, 1, NOW())";
                $this->db->query($insertSql, [':ip_address' => $ipAddress]);
                return true;
            }

            // 時間枠の開始時刻を取得
            $windowStart = new DateTime($rateLimit['window_start']);
            $windowEnd = clone $windowStart;
            $windowEnd->modify("+{$windowMinutes} minutes");

            // 時間枠が経過している場合はリセット
            if ($now >= $windowEnd) {
                $updateSql = "UPDATE inform_rate_limits 
                             SET submission_count = 1, window_start = NOW() 
                             WHERE ip_address = :ip_address";
                $this->db->query($updateSql, [':ip_address' => $ipAddress]);
                return true;
            }

            // 時間枠内の場合、カウントをチェック
            $currentCount = (int) $rateLimit['submission_count'];
            
            if ($currentCount >= self::RATE_LIMIT_MAX_SUBMISSIONS) {
                // 制限超過
                return false;
            }

            // カウントを増加
            $updateSql = "UPDATE inform_rate_limits 
                         SET submission_count = submission_count + 1 
                         WHERE ip_address = :ip_address";
            $this->db->query($updateSql, [':ip_address' => $ipAddress]);
            
            return true;

        } catch (PDOException $e) {
            Logger::logDatabaseError('Submission::checkRateLimit', 'Failed to check rate limit for IP: ' . $ipAddress, $e);
            throw new Exception('レート制限のチェックに失敗しました');
        }
    }

    /**
     * レート制限をリセット（テスト用）
     * 
     * @param string $ipAddress IPアドレス
     * @return bool 成功した場合true
     */
    public function resetRateLimit($ipAddress) {
        $sql = "DELETE FROM inform_rate_limits WHERE ip_address = :ip_address";
        
        try {
            $this->db->query($sql, [':ip_address' => $ipAddress]);
            return true;
        } catch (PDOException $e) {
            Logger::logDatabaseError('Submission::resetRateLimit', 'Failed to reset rate limit for IP: ' . $ipAddress, $e);
            return false;
        }
    }
}
