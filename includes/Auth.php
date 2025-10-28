<?php
require_once __DIR__ . '/Database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * ログイン処理
     */
    public function login($username, $password) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $user = $this->db->query($sql, [$username])->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // 最終ログイン時刻を更新
            $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $this->db->query($updateSql, [$user['id']]);
            
            return true;
        }

        return false;
    }

    /**
     * ログアウト処理
     */
    public function logout() {
        session_unset();
        session_destroy();
    }

    /**
     * ログインチェック
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // セッションタイムアウトチェック
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            $this->logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * ログインを要求
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . ADMIN_URL . '/login.php');
            exit;
        }
    }

    /**
     * 現在のユーザー情報を取得
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['user_role']
        ];
    }

    /**
     * 管理者かチェック
     */
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
