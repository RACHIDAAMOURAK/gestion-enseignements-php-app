<?php
class SessionManager {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public function getUserRole() {
        return $_SESSION['role'] ?? null;
    }

    public function setUserSession($userId, $role, $username) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;
        $_SESSION['username'] = $username;
    }

    public function clearSession() {
        session_unset();
        session_destroy();
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header("Location: ../login.php");
            exit;
        }
    }

    public function requireAdmin() {
        if (!$this->isAdmin()) {
            header("Location: ../unauthorized.php");
            exit;
        }
    }

    public function getUsername() {
        return $_SESSION['username'] ?? null;
    }

    public function regenerateSession() {
        session_regenerate_id(true);
    }
} 