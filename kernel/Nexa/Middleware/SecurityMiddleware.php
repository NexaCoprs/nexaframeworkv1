<?php

namespace Nexa\Middleware;

use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Security\CsrfProtection;
use Nexa\Security\XssProtection;
use Nexa\Security\RateLimiter;
use Nexa\Core\Config;

/**
 * Middleware de sécurité global pour Nexa Framework
 * 
 * Ce middleware applique toutes les protections de sécurité configurées
 * incluant CSRF, XSS, limitation de taux et headers de sécurité.
 */
class SecurityMiddleware
{
    private $csrf;
    private $rateLimiter;
    
    public function __construct()
    {
        $this->csrf = new CsrfProtection();
        $this->rateLimiter = new RateLimiter();
    }
    
    /**
     * Traiter la requête
     */
    public function handle(Request $request, callable $next)
    {
        // 1. Vérification de la limitation de taux
        if (Config::get('security.rate_limiting.enabled', true)) {
            $maxAttempts = Config::get('security.rate_limiting.max_attempts', 60);
            $decayMinutes = Config::get('security.rate_limiting.decay_minutes', 1);
            
            $rateLimitResponse = $this->rateLimiter->middleware($request, function($req) use ($next) {
                return $next($req);
            }, $maxAttempts, $decayMinutes);
            
            if ($rateLimitResponse instanceof Response && $rateLimitResponse->getStatusCode() === 429) {
                return $rateLimitResponse;
            }
        }
        
        // 2. Protection XSS automatique
        if (Config::get('security.xss.enabled', true) && Config::get('security.xss.auto_clean', true)) {
            XssProtection::middleware($request, function($req) { return $req; });
        }
        
        // 3. Validation CSRF
        if (Config::get('security.csrf.enabled', true)) {
            $csrfResponse = $this->csrf->middleware($request, function($req) use ($next) {
                return $next($req);
            });
            
            if ($csrfResponse instanceof Response && $csrfResponse->getStatusCode() === 419) {
                return $csrfResponse;
            }
        }
        
        // 4. Traitement normal de la requête
        $response = $next($request);
        
        // 5. Ajout des headers de sécurité (seulement si c'est une Response)
        if ($response instanceof Response) {
            $this->addSecurityHeaders($response);
        }
        
        return $response;
    }
    
    /**
     * Ajouter les headers de sécurité à la réponse
     */
    private function addSecurityHeaders(Response $response): void
    {
        $headers = Config::get('security.headers', []);
        
        // X-Frame-Options
        if (isset($headers['x_frame_options'])) {
            $response->header('X-Frame-Options', $headers['x_frame_options']);
        }
        
        // X-Content-Type-Options
        if (isset($headers['x_content_type_options'])) {
            $response->header('X-Content-Type-Options', $headers['x_content_type_options']);
        }
        
        // X-XSS-Protection
        if (isset($headers['x_xss_protection'])) {
            $response->header('X-XSS-Protection', $headers['x_xss_protection']);
        }
        
        // Referrer-Policy
        if (isset($headers['referrer_policy'])) {
            $response->header('Referrer-Policy', $headers['referrer_policy']);
        }
        
        // Content-Security-Policy
        if (isset($headers['content_security_policy'])) {
            $response->header('Content-Security-Policy', $headers['content_security_policy']);
        }
        
        // Strict-Transport-Security (HTTPS uniquement)
        if ($this->isHttps() && isset($headers['strict_transport_security'])) {
            $response->header('Strict-Transport-Security', $headers['strict_transport_security']);
        }
        
        // Permissions-Policy
        if (isset($headers['permissions_policy'])) {
            $response->header('Permissions-Policy', $headers['permissions_policy']);
        }
    }
    
    /**
     * Vérifier si la connexion est HTTPS
     */
    private function isHttps(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        );
    }

    /**
     * Generate CSRF token
     */
    public function generateCSRFToken(): string
    {
        return $this->csrf->generateToken();
    }

    /**
     * Sanitize input to prevent XSS
     */
    public function sanitizeInput(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Check rate limit for IP and endpoint
     */
    public function checkRateLimit(string $clientIP, int $maxAttempts = 60, int $decayMinutes = 1): bool
    {
        return $this->rateLimiter->attempt($clientIP, $maxAttempts, $decayMinutes);
    }

    /**
     * Sanitize SQL input
     */
    public function sanitizeSqlInput(string $input): string
    {
        return addslashes(trim($input));
    }

    /**
     * Escape HTML content
     */
    public function escapeHtml(string $content): string
    {
        return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }
}