<?php

namespace Nexa\Security;

/**
 * Protection XSS pour Nexa Framework
 * 
 * Cette classe fournit une protection contre les attaques Cross-Site Scripting (XSS)
 * en nettoyant et validant les données d'entrée utilisateur.
 */
class XssProtection
{
    /**
     * Nettoyer une chaîne contre les attaques XSS
     */
    public static function clean(string $input, bool $allowHtml = false): string
    {
        if ($allowHtml) {
            return self::cleanHtml($input);
        }
        
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Nettoyer un tableau de données
     */
    public static function cleanArray(array $data, bool $allowHtml = false): array
    {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = self::cleanArray($value, $allowHtml);
            } elseif (is_string($value)) {
                $cleaned[$key] = self::clean($value, $allowHtml);
            } else {
                $cleaned[$key] = $value;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Nettoyer le HTML en gardant les balises sûres
     */
    public static function cleanHtml(string $input): string
    {
        // Balises autorisées
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
        
        // Supprimer les balises dangereuses
        $input = strip_tags($input, $allowedTags);
        
        // Supprimer les attributs dangereux (amélioration)
        $dangerousAttributes = [
            'on\w+',           // tous les événements (onclick, onload, onerror, etc.)
            'javascript:',     // protocole javascript
            'vbscript:',      // protocole vbscript
            'data:',          // protocole data (peut contenir du javascript)
            'formaction',     // redirection de formulaire
            'action',         // action de formulaire
            'href\s*=\s*["\']?javascript:', // liens javascript
            'src\s*=\s*["\']?javascript:',  // sources javascript
            'style\s*=.*expression\s*\(',   // CSS expressions
            'style\s*=.*javascript:',      // CSS javascript
            'background\s*=.*javascript:',  // background javascript
        ];
        
        foreach ($dangerousAttributes as $attr) {
            $input = preg_replace('/(<[^>]+)\s+' . $attr . '[^>]*>/i', '$1>', $input);
        }
        
        // Supprimer complètement les balises avec attributs dangereux restants
        $input = preg_replace('/<[^>]*\s+(on\w+|javascript:|vbscript:)[^>]*>/i', '', $input);
        
        // Supprimer les balises script et style
        $input = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', '', $input);
        
        return $input;
    }
    
    /**
     * Valider si une chaîne contient du contenu potentiellement dangereux
     */
    public static function validate(string $input): bool
    {
        // Patterns dangereux
        $dangerousPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>/i',
            '/<form[^>]*>.*?<\/form>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i',
            '/<meta[^>]*http-equiv[^>]*refresh/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Encoder pour utilisation dans les attributs HTML
     */
    public static function attribute(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Encoder pour utilisation dans JavaScript
     */
    public static function javascript(string $input): string
    {
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
    
    /**
     * Encoder pour utilisation dans les URLs
     */
    public static function url(string $input): string
    {
        return urlencode($input);
    }
    
    /**
     * Encoder pour utilisation dans les CSS
     */
    public static function css(string $input): string
    {
        // Supprimer les caractères dangereux pour CSS
        return preg_replace('/[^\w\-\s#.,]/', '', $input);
    }
    
    /**
     * Middleware pour nettoyer automatiquement les données d'entrée
     */
    public static function middleware($request, callable $next)
    {
        // Nettoyer les données POST
        if (!empty($_POST)) {
            $_POST = self::cleanArray($_POST);
        }
        
        // Nettoyer les données GET
        if (!empty($_GET)) {
            $_GET = self::cleanArray($_GET);
        }
        
        // Nettoyer les cookies
        if (!empty($_COOKIE)) {
            $_COOKIE = self::cleanArray($_COOKIE);
        }
        
        return $next($request);
    }
    
    /**
     * Détecter les tentatives d'injection SQL dans les chaînes
     */
    public static function detectSqlInjection(string $input): bool
    {
        $sqlPatterns = [
            '/\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
            '/[\'";]/',
            '/--/',
            '/\/\*.*?\*\//s',
            '/\bor\s+1\s*=\s*1/i',
            '/\band\s+1\s*=\s*1/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Générer un nonce pour Content Security Policy
     */
    public static function generateNonce(): string
    {
        return base64_encode(random_bytes(16));
    }
}