<?php

namespace Nexa\Security;

use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Core\Config;

/**
 * Protection CSRF pour Nexa Framework
 * 
 * Cette classe fournit une protection contre les attaques Cross-Site Request Forgery
 * en générant et validant des tokens CSRF pour les formulaires et requêtes sensibles.
 */
class CsrfProtection
{
    private $tokenName = '_token';
    private $sessionKey = 'csrf_tokens';
    private $tokenLength = 32;
    
    /**
     * Générer un nouveau token CSRF
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes($this->tokenLength));
        
        // Stocker le token en session
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
        
        $_SESSION[$this->sessionKey][] = $token;
        
        // Limiter le nombre de tokens stockés (max 10)
        if (count($_SESSION[$this->sessionKey]) > 10) {
            array_shift($_SESSION[$this->sessionKey]);
        }
        
        return $token;
    }
    
    /**
     * Valider un token CSRF
     */
    public function validateToken(string $token): bool
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION[$this->sessionKey]) || !is_array($_SESSION[$this->sessionKey])) {
            return false;
        }
        
        $isValid = in_array($token, $_SESSION[$this->sessionKey], true);
        
        if ($isValid) {
            // Supprimer le token utilisé pour éviter la réutilisation
            $key = array_search($token, $_SESSION[$this->sessionKey], true);
            if ($key !== false) {
                unset($_SESSION[$this->sessionKey][$key]);
            }
        }
        
        return $isValid;
    }
    
    /**
     * Middleware pour vérifier les tokens CSRF
     */
    public function middleware(Request $request, callable $next)
    {
        // Vérifier seulement pour les méthodes POST, PUT, PATCH, DELETE
        $method = strtoupper($request->method());
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }
        
        // Exclure les routes API si configuré
        if (Config::get('csrf.exclude_api', true) && $this->isApiRoute($request)) {
            return $next($request);
        }
        
        $token = $this->getTokenFromRequest($request);
        
        if (!$token || !$this->validateToken($token)) {
            return new Response('CSRF token mismatch', 419, [
                'Content-Type' => 'application/json'
            ]);
        }
        
        return $next($request);
    }
    
    /**
     * Obtenir le token depuis la requête
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        // Vérifier dans les données POST
        $token = $request->input($this->tokenName);
        
        if (!$token) {
            // Vérifier dans les headers
            $token = $request->header('X-CSRF-TOKEN');
        }
        
        if (!$token) {
            // Vérifier dans les headers alternatifs
            $token = $request->header('X-XSRF-TOKEN');
        }
        
        return $token;
    }
    
    /**
     * Vérifier si c'est une route API
     */
    private function isApiRoute(Request $request): bool
    {
        $uri = $request->uri();
        return strpos($uri, '/api/') === 0;
    }
    
    /**
     * Générer un champ de formulaire caché avec le token CSRF
     */
    public function field(): string
    {
        $token = $this->generateToken();
        return '<input type="hidden" name="' . $this->tokenName . '" value="' . $token . '">';
    }
    
    /**
     * Obtenir le token actuel pour JavaScript
     */
    public function token(): string
    {
        return $this->generateToken();
    }
    
    /**
     * Générer une meta tag pour JavaScript
     */
    public function metaTag(): string
    {
        $token = $this->generateToken();
        return '<meta name="csrf-token" content="' . $token . '">';
    }
}