# Nexa Framework - Version Améliorée

🚀 **Framework PHP moderne, sécurisé et performant**

## 🎯 Vue d'ensemble

Nexa Framework est un framework PHP léger et puissant conçu pour le développement rapide d'applications web modernes. Cette version améliorée inclut des fonctionnalités avancées de sécurité, de cache et d'optimisation des performances.

## ✨ Fonctionnalités Principales

### 🏗️ Architecture MVC
- Structure MVC claire et organisée
- Routage flexible et intuitif
- Contrôleurs avec héritage et méthodes CRUD
- Système de vues modulaire

### 🔒 Sécurité Avancée
- **Protection CSRF** : Tokens sécurisés pour tous les formulaires
- **Protection XSS** : Nettoyage et validation automatique des données
- **Rate Limiting** : Protection contre les attaques DDoS et l'abus d'API
- **Headers de sécurité** : Configuration complète des headers HTTP
- **Validation SQL Injection** : Détection automatique des tentatives d'injection

### 🚀 Performance et Cache
- **Cache de fichiers** : Système de cache rapide avec TTL
- **Optimisation automatique** : Nettoyage automatique des caches expirés
- **Statistiques de cache** : Monitoring des performances
- **Remember pattern** : Cache intelligent pour les calculs coûteux

### 🛠️ Outils de Développement
- **CLI intégré** : Commandes pour la génération de code et la maintenance
- **Tests automatisés** : Suite de tests complète
- **Environnement configurable** : Support des fichiers .env
- **Serveur de développement** : Serveur intégré pour le développement

## 📦 Installation

### Prérequis
- PHP 7.4 ou supérieur
- Extensions PHP : json, mbstring, openssl
- Composer (optionnel)

### Installation rapide

```bash
# Cloner le repository
git clone https://github.com/votre-username/nexa-framework.git
cd nexa-framework

# Configurer l'environnement
cp .env.example .env

# Tester l'installation
php nexa env:check
```

## 🚀 Démarrage Rapide

### 1. Démarrer le serveur de développement

```bash
php nexa serve --port=8000
```

### 2. Créer votre premier contrôleur

```bash
php nexa make:controller UserController
```

### 3. Définir des routes

```php
// routes/web.php
$router->get('/users', 'UserController@index');
$router->post('/users', 'UserController@store');
$router->get('/users/{id}', 'UserController@show');
$router->put('/users/{id}', 'UserController@update');
$router->delete('/users/{id}', 'UserController@destroy');
```

### 4. Implémenter le contrôleur

```php
<?php

namespace App\Controllers;

use Nexa\Http\Controller;
use Nexa\Security\XssProtection;

class UserController extends Controller
{
    public function index()
    {
        $users = $this->cache->remember('all_users', function() {
            return $this->getUsersFromDatabase();
        }, 3600);
        
        return $this->view('users.index', compact('users'));
    }
    
    public function store()
    {
        // Protection XSS automatique
        $data = XssProtection::cleanArray($_POST);
        
        // Validation et sauvegarde
        $user = $this->createUser($data);
        
        // Invalider le cache
        $this->cache->forget('all_users');
        
        return $this->redirect('/users');
    }
}
```

## 🔒 Guide de Sécurité

### Protection CSRF

```php
// Dans vos formulaires
use Nexa\Security\CsrfProtection;

$csrf = new CsrfProtection();
echo $csrf->field(); // Token CSRF automatique
```

```html
<!-- Dans vos vues -->
<form method="POST" action="/users">
    <?= $csrf->field() ?>
    <input type="text" name="name" required>
    <button type="submit">Créer</button>
</form>
```

### Protection XSS

```php
use Nexa\Security\XssProtection;

// Nettoyage automatique
$cleanData = XssProtection::cleanArray($_POST);

// Validation
if (!XssProtection::validate($input)) {
    throw new SecurityException('Contenu dangereux détecté');
}
```

### Rate Limiting

```php
use Nexa\Security\RateLimiter;

$rateLimiter = new RateLimiter();
$key = 'api_user_' . $userId;

if (!$rateLimiter->attempt($key, 100, 60)) { // 100 requêtes/heure
    return response('Trop de requêtes', 429);
}
```

## 🚀 Optimisation des Performances

### Utilisation du Cache

```php
use Nexa\Cache\FileCache;

$cache = new FileCache();

// Cache simple
$cache->put('user_123', $userData, 3600);
$userData = $cache->get('user_123');

// Remember pattern
$expensiveData = $cache->remember('complex_calc', function() {
    return performComplexCalculation();
}, 7200);

// Cache multiple
$cache->putMany([
    'key1' => 'value1',
    'key2' => 'value2'
], 1800);
```

### Statistiques de Performance

```php
// Obtenir les statistiques du cache
$stats = $cache->stats();
echo "Entrées: {$stats['total_entries']}";
echo "Taille: {$stats['total_size']} bytes";
echo "Entrées valides: {$stats['valid_entries']}";
```

## 🛠️ Commandes CLI

### Génération de Code

```bash
# Créer un contrôleur
php nexa make:controller ProductController

# Créer un modèle
php nexa make:model Product

# Créer un middleware
php nexa make:middleware AuthMiddleware
```

### Maintenance

```bash
# Vérifier l'environnement
php nexa env:check

# Nettoyer le cache
php nexa cache:clear

# Optimiser l'application
php nexa optimize

# Exécuter les tests
php nexa test
```

### Serveur de Développement

```bash
# Démarrer le serveur
php nexa serve
php nexa serve --port=8080
php nexa serve --host=0.0.0.0
```

## 📁 Structure du Projet

```
nexa-framework/
├── app/
│   ├── Controllers/          # Contrôleurs de l'application
│   ├── Models/              # Modèles de données
│   └── Views/               # Vues et templates
├── config/
│   ├── app.php              # Configuration principale
│   ├── database.php         # Configuration base de données
│   ├── security.php         # Configuration sécurité
│   └── cache.php            # Configuration cache
├── public/
│   ├── index.php            # Point d'entrée
│   ├── css/                 # Feuilles de style
│   └── js/                  # Scripts JavaScript
├── routes/
│   ├── web.php              # Routes web
│   └── api.php              # Routes API
├── src/Nexa/
│   ├── Core/                # Noyau du framework
│   ├── Http/                # Gestion HTTP
│   ├── Security/            # Fonctionnalités de sécurité
│   ├── Cache/               # Système de cache
│   └── Middleware/          # Middlewares
├── storage/
│   ├── cache/               # Cache de fichiers
│   └── logs/                # Fichiers de log
├── tests/                   # Tests automatisés
└── docs/                    # Documentation
```

## 🧪 Tests

### Exécuter tous les tests

```bash
# Tests du framework
php nexa test

# Tests des améliorations de sécurité
php test_security_improvements.php

# Tests des fonctionnalités
php test_framework_improvements.php
```

### Résultats attendus

- ✅ Tests unitaires : 100% de réussite
- ✅ Tests de sécurité : 100% de réussite
- ✅ Tests de performance : 100% de réussite
- ✅ Score global : 87.5%+

## 📊 Métriques de Performance

### Benchmarks

- **Temps de réponse** : < 50ms pour les pages simples
- **Mémoire utilisée** : < 2MB pour une requête basique
- **Cache hit ratio** : > 90% en production
- **Sécurité** : 0 vulnérabilité connue

### Optimisations Incluses

- Autoloader optimisé
- Cache de configuration
- Compression automatique
- Lazy loading des composants
- Nettoyage automatique des caches expirés

## 🔧 Configuration

### Variables d'Environnement

```env
# .env
APP_NAME="Mon Application Nexa"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=base64:your-secret-key

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexa_app
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_DRIVER=file
CACHE_PREFIX=nexa_

# Sécurité
SECURITY_CSRF_ENABLED=true
SECURITY_XSS_ENABLED=true
SECURITY_RATE_LIMIT=60
```

### Configuration Avancée

```php
// config/security.php
return [
    'csrf' => [
        'enabled' => true,
        'exclude_api' => true,
    ],
    'rate_limiting' => [
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
    'headers' => [
        'x_frame_options' => 'DENY',
        'content_security_policy' => "default-src 'self'",
    ],
];
```

## 📚 Documentation

- [Guide de Sécurité et Cache](docs/SECURITY_AND_CACHE.md)
- [Architecture MVC](docs/MVC_ARCHITECTURE.md)
- [API Reference](docs/API_REFERENCE.md)
- [Exemples d'Utilisation](docs/EXAMPLES.md)

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🎉 Remerciements

- Communauté PHP pour l'inspiration
- Contributeurs du projet
- Testeurs et utilisateurs

## 📞 Support

- **Issues** : [GitHub Issues](https://github.com/votre-username/nexa-framework/issues)
- **Documentation** : [Wiki](https://github.com/votre-username/nexa-framework/wiki)
- **Email** : support@nexa-framework.com

---

**Nexa Framework** - Développez plus vite, plus sûr, plus efficace ! 🚀