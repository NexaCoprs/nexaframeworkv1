<?php

namespace Nexa\Security;

use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Core\Config;

/**
 * Limiteur de taux pour Nexa Framework
 * 
 * Cette classe implémente un système de limitation de taux pour protéger
 * contre les attaques par déni de service et l'abus d'API.
 */
class RateLimiter
{
    private $storage = [];
    private $storageFile;
    
    public function __construct()
    {
        $this->storageFile = storage_path('rate_limits.json');
        $this->loadStorage();
    }
    
    /**
     * Vérifier si une requête est autorisée
     */
    public function attempt(string $key, int $maxAttempts = 60, int $decayMinutes = 1): bool
    {
        $this->cleanExpired();
        
        $now = time();
        $windowStart = $now - ($decayMinutes * 60);
        
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = [];
        }
        
        // Supprimer les tentatives expirées
        $this->storage[$key] = array_filter($this->storage[$key], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        // Vérifier si la limite est atteinte
        if (count($this->storage[$key]) >= $maxAttempts) {
            $this->saveStorage();
            return false;
        }
        
        // Ajouter la nouvelle tentative
        $this->storage[$key][] = $now;
        $this->saveStorage();
        
        return true;
    }
    
    /**
     * Obtenir le nombre de tentatives restantes
     */
    public function remaining(string $key, int $maxAttempts = 60, int $decayMinutes = 1): int
    {
        $this->cleanExpired();
        
        $now = time();
        $windowStart = $now - ($decayMinutes * 60);
        
        if (!isset($this->storage[$key])) {
            return $maxAttempts;
        }
        
        // Compter les tentatives dans la fenêtre actuelle
        $attempts = array_filter($this->storage[$key], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return max(0, $maxAttempts - count($attempts));
    }
    
    /**
     * Obtenir le temps de réinitialisation
     */
    public function resetTime(string $key, int $decayMinutes = 1): int
    {
        if (!isset($this->storage[$key]) || empty($this->storage[$key])) {
            return time();
        }
        
        $oldestAttempt = min($this->storage[$key]);
        return $oldestAttempt + ($decayMinutes * 60);
    }
    
    /**
     * Réinitialiser les tentatives pour une clé
     */
    public function clear(string $key): void
    {
        unset($this->storage[$key]);
        $this->saveStorage();
    }
    
    /**
     * Middleware pour la limitation de taux
     */
    public function middleware(Request $request, callable $next, int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        
        if (!$this->attempt($key, $maxAttempts, $decayMinutes)) {
            return $this->buildResponse($key, $maxAttempts, $decayMinutes);
        }
        
        $response = $next($request);
        
        // Ajouter les headers de limitation
        $response->header('X-RateLimit-Limit', $maxAttempts);
        $response->header('X-RateLimit-Remaining', $this->remaining($key, $maxAttempts, $decayMinutes));
        $response->header('X-RateLimit-Reset', $this->resetTime($key, $decayMinutes));
        
        return $response;
    }
    
    /**
     * Résoudre la signature de la requête
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $ip = $this->getClientIp($request);
        $route = $request->uri();
        
        return sha1($ip . '|' . $route);
    }
    
    /**
     * Obtenir l'IP du client
     */
    protected function getClientIp(Request $request): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Construire la réponse de limitation
     */
    protected function buildResponse(string $key, int $maxAttempts, int $decayMinutes): Response
    {
        $retryAfter = $this->resetTime($key, $decayMinutes) - time();
        
        return new Response(
            json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Try again in ' . $retryAfter . ' seconds.',
                'retry_after' => $retryAfter
            ]),
            429,
            [
                'Content-Type' => 'application/json',
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => $this->resetTime($key, $decayMinutes)
            ]
        );
    }
    
    /**
     * Charger le stockage depuis le fichier
     */
    private function loadStorage(): void
    {
        if (file_exists($this->storageFile)) {
            $data = file_get_contents($this->storageFile);
            $this->storage = json_decode($data, true) ?: [];
        }
    }
    
    /**
     * Sauvegarder le stockage dans le fichier
     */
    private function saveStorage(): void
    {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->storageFile, json_encode($this->storage));
    }
    
    /**
     * Nettoyer les entrées expirées
     */
    private function cleanExpired(): void
    {
        $now = time();
        $maxAge = 3600; // 1 heure
        
        foreach ($this->storage as $key => $attempts) {
            $this->storage[$key] = array_filter($attempts, function($timestamp) use ($now, $maxAge) {
                return ($now - $timestamp) < $maxAge;
            });
            
            if (empty($this->storage[$key])) {
                unset($this->storage[$key]);
            }
        }
    }
}