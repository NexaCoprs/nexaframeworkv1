<?php

namespace Nexa\Middleware;

use Nexa\Http\Request;
use Nexa\Http\Response;

class AuthMiddleware
{
    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next)
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated($request)) {
            return Response::json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], 401);
        }

        return $next($request);
    }

    /**
     * Check if the request is authenticated
     *
     * @param Request $request
     * @return bool
     */
    protected function isAuthenticated(Request $request): bool
    {
        // Check for session-based authentication
        if (session_status() === PHP_SESSION_ACTIVE) {
            return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        }

        // Check for token-based authentication
        $token = $request->header('Authorization');
        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
            return $this->validateToken($token);
        }

        return false;
    }

    /**
     * Validate authentication token
     *
     * @param string $token
     * @return bool
     */
    protected function validateToken(string $token): bool
    {
        // Simple token validation - in production, use proper JWT validation
        return !empty($token) && strlen($token) > 10;
    }

    /**
     * Get the authenticated user
     *
     * @return array|null
     */
    public static function user(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'] ?? 'User',
                'email' => $_SESSION['user_email'] ?? 'user@example.com',
                'role' => $_SESSION['user_role'] ?? 'user'
            ];
        }

        return null;
    }

    /**
     * Login a user
     *
     * @param array $userData
     * @return bool
     */
    public static function login(array $userData): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_name'] = $userData['name'] ?? 'User';
        $_SESSION['user_email'] = $userData['email'] ?? '';
        $_SESSION['user_role'] = $userData['role'] ?? 'user';

        return true;
    }

    /**
     * Logout the current user
     *
     * @return bool
     */
    public static function logout(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_role']);

        return true;
    }

    /**
     * Hash password using secure algorithm
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Verify password against hash
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate secure token
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}