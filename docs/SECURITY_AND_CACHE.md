# Guide de Sécurité et Cache - Nexa Framework

## Vue d'ensemble

Ce guide présente les fonctionnalités de sécurité et de cache implémentées dans Nexa Framework pour protéger votre application contre les vulnérabilités courantes et améliorer les performances.

## 🔒 Fonctionnalités de Sécurité

### 1. Protection CSRF (Cross-Site Request Forgery)

La protection CSRF empêche les attaques de falsification de requête inter-sites.

#### Utilisation

```php
use Nexa\Security\CsrfProtection;

$csrf = new CsrfProtection();

// Générer un token CSRF
$token = $csrf->generateToken();

// Dans un formulaire HTML
echo $csrf->field(); // <input type="hidden" name="_token" value="...">

// Meta tag pour JavaScript
echo $csrf->metaTag(); // <meta name="csrf-token" content="...">

// Valider un token
$isValid = $csrf->validateToken($token);
```

#### Configuration

```php
// config/security.php
'csrf' => [
    'enabled' => true,
    'exclude_api' => true,
    'token_name' => '_token',
    'header_name' => 'X-CSRF-TOKEN',
],
```

### 2. Protection XSS (Cross-Site Scripting)

La protection XSS nettoie et valide les données d'entrée utilisateur.

#### Utilisation

```php
use Nexa\Security\XssProtection;

// Nettoyage basique
$clean = XssProtection::clean('<script>alert("XSS")</script>Hello');
// Résultat: &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;Hello

// Nettoyage HTML (garde les balises sûres)
$cleanHtml = XssProtection::cleanHtml('<p>Texte <strong>gras</strong></p><script>bad</script>');
// Résultat: <p>Texte <strong>gras</strong></p>

// Validation
$isValid = XssProtection::validate($input);

// Encodage pour différents contextes
$attr = XssProtection::attribute($value);     // Pour attributs HTML
$js = XssProtection::javascript($value);      // Pour JavaScript
$url = XssProtection::url($value);            // Pour URLs
$css = XssProtection::css($value);            // Pour CSS

// Détection SQL injection
$isSqlInjection = XssProtection::detectSqlInjection($input);
```

#### Configuration

```php
// config/security.php
'xss' => [
    'enabled' => true,
    'auto_clean' => true,
    'allow_html' => false,
],
```

### 3. Limitation de Taux (Rate Limiting)

La limitation de taux protège contre les attaques par déni de service et l'abus d'API.

#### Utilisation

```php
use Nexa\Security\RateLimiter;

$rateLimiter = new RateLimiter();

// Vérifier si une requête est autorisée
$key = 'user_' . $userId;
if ($rateLimiter->attempt($key, 60, 1)) { // 60 tentatives par minute
    // Traiter la requête
} else {
    // Trop de tentatives
}

// Obtenir les tentatives restantes
$remaining = $rateLimiter->remaining($key, 60, 1);

// Réinitialiser les tentatives
$rateLimiter->clear($key);
```

#### Configuration

```php
// config/security.php
'rate_limiting' => [
    'enabled' => true,
    'max_attempts' => 60,
    'decay_minutes' => 1,
    'storage' => 'file',
],
```

### 4. Headers de Sécurité

Les headers de sécurité protègent contre diverses attaques.

#### Configuration

```php
// config/security.php
'headers' => [
    'x_frame_options' => 'DENY',
    'x_content_type_options' => 'nosniff',
    'x_xss_protection' => '1; mode=block',
    'referrer_policy' => 'strict-origin-when-cross-origin',
    'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline';",
],
```

### 5. Middleware de Sécurité

Le middleware de sécurité applique toutes les protections automatiquement.

```php
use Nexa\Middleware\SecurityMiddleware;

// Dans votre application
$app->middleware(new SecurityMiddleware());
```

## 🚀 Système de Cache

### Cache de Fichiers

Le cache de fichiers offre un stockage persistant et rapide.

#### Utilisation

```php
use Nexa\Cache\FileCache;

$cache = new FileCache();

// Stocker une valeur
$cache->put('user_123', $userData, 3600); // TTL de 1 heure

// Récupérer une valeur
$userData = $cache->get('user_123', $default);

// Vérifier l'existence
if ($cache->has('user_123')) {
    // La clé existe
}

// Remember pattern
$expensiveData = $cache->remember('expensive_calculation', function() {
    return performExpensiveCalculation();
}, 3600);

// Opérations numériques
$cache->increment('page_views');
$cache->decrement('stock_count', 5);

// Opérations multiples
$cache->putMany([
    'key1' => 'value1',
    'key2' => 'value2'
], 3600);

$values = $cache->many(['key1', 'key2']);

// Statistiques
$stats = $cache->stats();
echo "Entrées: {$stats['total_entries']}, Taille: {$stats['total_size']} bytes";

// Nettoyage
$cleaned = $cache->cleanup(); // Supprime les entrées expirées
$cache->flush(); // Vide tout le cache
```

#### Configuration

```php
// config/cache.php
'default' => 'file',

'stores' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('cache'),
        'default_ttl' => 3600,
    ],
],

'cleanup' => [
    'enabled' => true,
    'probability' => 2, // 2% de chance de nettoyage
    'max_age' => 86400,
],
```

## 🛡️ Bonnes Pratiques de Sécurité

### 1. Validation des Données

```php
// Toujours valider et nettoyer les données d'entrée
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$name = XssProtection::clean($_POST['name']);
```

### 2. Gestion des Sessions

```php
// Configuration sécurisée des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
```

### 3. Mots de Passe

```php
// Hachage sécurisé des mots de passe
$hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

// Vérification
if (password_verify($password, $hashedPassword)) {
    // Mot de passe correct
}
```

### 4. HTTPS

```php
// Forcer HTTPS en production
if ($_ENV['APP_ENV'] === 'production' && !isset($_SERVER['HTTPS'])) {
    $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirectURL");
    exit();
}
```

## 📊 Optimisation des Performances

### 1. Stratégies de Cache

```php
// Cache des requêtes de base de données
$users = $cache->remember('all_users', function() {
    return Database::query('SELECT * FROM users');
}, 1800);

// Cache des vues
$html = $cache->remember('homepage_html', function() {
    return renderHomepage();
}, 3600);

// Cache des calculs coûteux
$result = $cache->remember('complex_calculation_' . $params, function() use ($params) {
    return performComplexCalculation($params);
}, 7200);
```

### 2. Invalidation du Cache

```php
// Invalider le cache lors des mises à jour
function updateUser($userId, $data) {
    Database::update('users', $userId, $data);
    $cache->forget('user_' . $userId);
    $cache->forget('all_users');
}
```

## 🔧 Dépannage

### Problèmes Courants

1. **Erreur CSRF Token Mismatch**
   - Vérifiez que le token est inclus dans le formulaire
   - Assurez-vous que les sessions fonctionnent

2. **Cache Non Fonctionnel**
   - Vérifiez les permissions du répertoire de cache
   - Assurez-vous que le répertoire existe

3. **Rate Limiting Trop Strict**
   - Ajustez les paramètres dans la configuration
   - Vérifiez la détection d'IP

### Logs de Sécurité

```php
// Activer les logs de sécurité
function logSecurityEvent($event, $details) {
    $log = date('Y-m-d H:i:s') . " - $event: " . json_encode($details) . "\n";
    file_put_contents(storage_path('logs/security.log'), $log, FILE_APPEND);
}
```

## 📝 Tests

Pour tester les fonctionnalités de sécurité et de cache :

```bash
php test_security_improvements.php
```

Ce script teste :
- Protection CSRF
- Protection XSS
- Limitation de taux
- Cache de fichiers
- Configurations

## 🎯 Conclusion

Le framework Nexa offre maintenant une sécurité robuste et des performances optimisées grâce à :

- ✅ Protection CSRF complète
- ✅ Protection XSS avancée
- ✅ Limitation de taux configurable
- ✅ Système de cache efficace
- ✅ Headers de sécurité
- ✅ Middleware de sécurité intégré

Ces fonctionnalités permettent de développer des applications web sécurisées et performantes avec Nexa Framework.