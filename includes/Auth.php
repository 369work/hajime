<?php
require_once __DIR__ . '/Database.php';

class Auth
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * ログイン処理
     */
    public function login($username, $password)
    {
        $sql = "SELECT * FROM users WHERE username = ?";
        $user = $this->db->query($sql, [$username])->fetch();

        error_log("Auth::login - Username: " . $username);
        error_log("Auth::login - User found: " . ($user ? 'yes' : 'no'));

        if ($user) {
            error_log("Auth::login - DB password hash: " . substr($user['password'], 0, 20) . "...");
            error_log("Auth::login - Input password: " . $password);
            $verified = password_verify($password, $user['password']);
            error_log("Auth::login - Password verified: " . ($verified ? 'yes' : 'no'));
        }

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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
    public function logout()
    {
        session_unset();
        session_destroy();
    }

    /**
     * ログインチェック
     */
    public function isLoggedIn()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // セッションタイムアウトチェック
        if (
            isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)
        ) {
            $this->logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * ログインを要求
     */
    public function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            header('Location: ' . ADMIN_URL . '/login.php');
            exit;
        }
    }

    /**
     * 現在のユーザー情報を取得
     */
    public function getCurrentUser()
    {
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
    public function isAdmin()
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /**
     * CSRFトークンを生成
     */
    public function generateCSRFToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * CSRFトークンを検証
     */
    public function verifyCSRFToken($token)
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
