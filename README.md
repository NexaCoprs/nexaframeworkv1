# Nexa Framework - PHP ORM and Database Toolkit

Nexa Framework is a powerful, lightweight PHP ORM and database toolkit that provides an elegant and intuitive way to interact with databases. It features a fluent query builder, schema management, migrations, seeders, and a full-featured ORM with relationships, scopes, and advanced querying capabilities.

## ✨ Pourquoi Nexa ?

- 🚀 **Démarrage instantané** : Zero-config, auto-discovery intelligent
- 💎 **Syntaxe élégante** : API fluide et expressive inspirée des meilleurs frameworks
- 🔥 **Hot-reload** : Rechargement automatique en développement
- 🛠️ **Outils modernes** : CLI puissant, validation fluide, cache intelligent
- 📦 **Tout inclus** : ORM, routage, validation, cache, événements, et plus
- 🎯 **Performance** : Optimisé pour la vitesse et l'efficacité
- 🔒 **Sécurisé** : Protection intégrée contre les vulnérabilités courantes

## 🚀 Démarrage rapide

```bash
# Installation
composer create-project nexa/framework mon-projet
cd mon-projet

# Démarrage du serveur de développement
php nexa serve
```

## 🎯 Fonctionnalités principales

### 🏗️ Architecture moderne
- **Auto-discovery** : Détection automatique des contrôleurs, modèles et middleware
- **Zero-config** : Fonctionne immédiatement sans configuration
- **Hot-reload** : Rechargement automatique des routes en développement
- **API fluide** : Syntaxe chainable et expressive

### 🛣️ Routage avancé
- **Routes expressives** : Syntaxe claire et intuitive
- **Groupes de routes** : Organisation et middleware partagés
- **Routes de ressources** : CRUD automatique
- **Contraintes de paramètres** : Validation au niveau des routes
- **Routes nommées** : Navigation et génération d'URLs simplifiées

### 🗄️ ORM moderne
- **Query Builder fluide** : Requêtes expressives et chainables
- **Relations éloquentes** : Gestion intuitive des relations
- **Scopes et mutateurs** : Logique métier encapsulée
- **Timestamps automatiques** : Gestion transparente des dates
- **Casting d'attributs** : Conversion automatique des types

### ✅ Validation puissante
- **API fluide** : Validation chainable et expressive
- **Règles extensibles** : Ajout facile de règles personnalisées
- **Messages personnalisés** : Contrôle total des messages d'erreur
- **Validation de tableaux** : Support des structures complexes

### 🚀 Cache intelligent
- **Stores multiples** : File, Array, et extensible
- **API unifiée** : Interface cohérente pour tous les stores
- **Remember patterns** : Cache automatique avec callbacks
- **Nettoyage automatique** : Gestion transparente de l'expiration

### 🎪 Système d'événements
- **Listeners flexibles** : Gestion d'événements découplée
- **Wildcards** : Écoute de patterns d'événements
- **Priorités** : Contrôle de l'ordre d'exécution
- **Subscribers** : Organisation des listeners

### 🛠️ CLI moderne
- **Commandes make** : Génération rapide de code
- **Interface colorée** : Sortie claire et attrayante
- **Validation interactive** : Prompts intelligents
- **Progress bars** : Feedback visuel pour les tâches longues

## 📚 Exemples de code

### Routage simple et élégant

```php
// Routes basiques
Route::get('/', function() {
    return view('welcome');
});

Route::post('/users', [UserController::class, 'store']);

// Groupes de routes avec middleware
Route::group(['prefix' => 'api', 'middleware' => 'auth'], function() {
    Route::resource('posts', PostController::class);
    Route::get('/profile', [UserController::class, 'profile']);
});
```

### ORM expressif et puissant

```php
// Modèle simple
class User extends Model
{
    protected $fillable = ['name', 'email'];
    protected $casts = ['email_verified_at' => 'datetime'];
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

// Requêtes fluides
$users = User::where('active', true)
    ->whereNotNull('email_verified_at')
    ->with('posts')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Création et mise à jour
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

$user = User::firstOrCreate(
    ['email' => 'jane@example.com'],
    ['name' => 'Jane Doe']
);
```

### Validation fluide et expressive

```php
// Dans un contrôleur
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|min:3|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
        'age' => 'integer|min:18'
    ]);
    
    return User::create($validated);
}

// Validation avec middleware
Route::post('/users', [UserController::class, 'store'])
    ->middleware(ValidationMiddleware::make([
        'name' => 'required|string',
        'email' => 'required|email'
    ]));
```

### Cache intelligent

```php
// Cache simple
Cache::put('key', 'value', 3600); // 1 heure
$value = Cache::get('key', 'default');

// Remember pattern
$users = Cache::remember('active_users', 3600, function() {
    return User::where('active', true)->get();
});

// Cache permanent
Cache::forever('settings', $settings);
```

### Système d'événements

```php
// Déclencher un événement
Event::dispatch('user.created', $user);

// Écouter un événement
Event::listen('user.created', function($user) {
    // Envoyer un email de bienvenue
    Mail::send('welcome', $user);
});

// Wildcards
Event::listen('user.*', function($event, $data) {
    Log::info("Événement utilisateur: {$event}");
});
```

## 🛠️ Installation

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- Extensions PHP : PDO, mbstring, openssl

### Installation via Composer

```bash
# Nouveau projet
composer create-project nexa/framework mon-projet
cd mon-projet

# Configuration
cp .env.example .env
php nexa key:generate

# Base de données (optionnel)
php nexa migrate

# Démarrage
php nexa serve
```

### Installation manuelle

```bash
git clone https://github.com/nexa/framework.git
cd framework
composer install
cp .env.example .env
php nexa key:generate
```

## 🚀 Utilisation

### Structure du projet

```
mon-projet/
├── app/
│   ├── Controllers/     # Contrôleurs
│   ├── Models/         # Modèles
│   └── Middleware/     # Middleware personnalisés
├── config/             # Configuration
├── public/             # Point d'entrée web
├── resources/
│   ├── views/          # Templates
│   └── assets/         # Assets (CSS, JS)
├── routes/             # Définition des routes
├── storage/            # Fichiers générés
└── vendor/             # Dépendances
```

### Commandes CLI

```bash
# Génération de code
php nexa make:controller UserController
php nexa make:model Post --migration
php nexa make:middleware AuthMiddleware

# Base de données
php nexa migrate
php nexa migrate:rollback
php nexa db:seed

# Serveur de développement
php nexa serve
php nexa serve --port=8080

# Cache
php nexa cache:clear
php nexa config:cache
```

### ✨ Fonctionnalités Principales

#### Phase 1 - Fondations ✅
- 🗄️ **ORM avancé** avec relations, migrations et seeding
- 🛣️ **Routage intuitif** avec support pour les groupes et middlewares
- 🔄 **Contrôleurs** avec injection de dépendances
- 🖥️ **Moteur de templates** rapide et flexible
- 🔍 **Query Builder** fluide et expressif
- ✅ **Validation** des données robuste
- 🔒 **Middleware** pour la sécurité et plus
- 📦 **Cache** haute performance
- 📝 **Logging** compatible PSR-3

#### Phase 2 - Fonctionnalités Avancées ✅ NOUVEAU!
- 🔐 **Authentification JWT** complète avec refresh tokens
- 📡 **Système d'événements** avec listeners et priorités
- 🔄 **Files d'attente (Queue)** pour le traitement asynchrone
- 🧪 **Framework de tests** automatisés avec assertions
- 💻 **Interface CLI** pour la gestion et génération de code
- 🛡️ **Sécurité avancée** (CORS, CSRF, Rate Limiting)
- 📈 **Monitoring et performance** intégrés

> **🎉 Phase 2 Complète!** Toutes les fonctionnalités avancées sont maintenant disponibles et testées.

#### Phase 3 - Écosystème Complet 🚧 EN COURS
- 🔌 **Architecture modulaire** avec système de plugins
- 📊 **Support GraphQL** avec génération automatique de schémas
- 🔄 **Websockets** pour communication en temps réel
- 🌐 **Architecture microservices** avec service discovery
- 🛠️ **Outils de développement avancés** (debugging, profiling)

> **🚀 Phase 3 Démarrée!** Nous commençons le développement de l'écosystème complet.

## 🏃‍♂️ Démarrage Rapide

### Installation

1. Clonez le repository :
```bash
git clone https://github.com/votre-username/nexa-framework.git
cd nexa-framework
```

2. Installez les dépendances :
```bash
composer install
```

3. Configurez votre environnement :
```bash
cp .env.example .env
# Éditez le fichier .env avec vos paramètres
```

4. Nettoyez et organisez le projet :
```bash
php scripts/cleanup.php
```

5. Lancez le serveur de développement :
```bash
php -S localhost:8000 -t public
```

## Documentation

- 📁 [Structure du Projet](PROJECT_STRUCTURE.md) - Organisation des fichiers
- 🚀 [Guide de Déploiement](DEPLOYMENT.md) - Instructions pour OVH
- 🔒 [Guide de Sécurité](SECURITY.md) - Configuration sécurisée
- 📚 [Documentation API](docs/API_DOCUMENTATION.md) - Référence API
- ⚡ [Démarrage Rapide](docs/QUICK_START.md) - Guide de démarrage

### Exemple de Routage

```php
// routes/web.php
use Nexa\Routing\Router;

Router::get('/', function() {
    return view('welcome');
});

Router::get('/users/{id}', 'UserController@show');

Router::group(['prefix' => 'admin', 'middleware' => 'auth'], function() {
    Router::get('/dashboard', 'AdminController@dashboard');
});
```

### Exemple d'Authentification JWT

```php
// Génération d'un token JWT
$token = \Nexa\Auth\JWT::generate([
    'user_id' => 1,
    'role' => 'admin'
]);

// Vérification d'un token
$payload = \Nexa\Auth\JWT::verify($token);

// Utilisation du middleware JWT
Router::group(['middleware' => 'jwt'], function() {
    Router::get('/profile', 'UserController@profile');
});
```

### Exemple d'Événements

```php
// Utilisation des événements prédéfinis
use Nexa\Events\UserRegistered;
use Nexa\Events\UserLoggedIn;
use Nexa\Events\ModelCreated;

// Instancier un événement avec des données
$event = new UserRegistered($user);

// Accéder aux données de l'événement
$userId = $event->user->id;
$email = $event->user->email;

// Événement de connexion
$loginEvent = new UserLoggedIn($user, $request->ip());

// Événement de création de modèle
$modelEvent = new ModelCreated($post, 'Post');
$modelName = $modelEvent->modelType; // 'Post'
```

### Exemple de Queue

```php
// Création d'un job
$job = new \Nexa\Queue\Job('App\Jobs\SendEmail', [
    'user_id' => 123,
    'subject' => 'Bienvenue!',
    'content' => 'Merci de votre inscription.'
]);

// Ajout à la queue pour exécution immédiate
\Nexa\Queue\Queue::push($job);

// Ajout à la queue pour exécution différée (60 secondes)
\Nexa\Queue\Queue::later($job, 60);
```

## ✅ Tests et Validation Phase 2

La Phase 2 a été validée avec succès via le script `test_phase2.php` qui vérifie toutes les nouvelles fonctionnalités :

```
✅ Test JWT Authentication: PASSED
✅ Test Event System: PASSED
✅ Test Queue System: PASSED
✅ Test CLI Commands: PASSED
✅ Test Advanced Security: PASSED

All Phase 2 tests passed successfully!
```

### Composants validés :

- ✓ Authentification JWT avec refresh tokens
- ✓ Système d'événements avec listeners prioritaires
- ✓ Queue system avec drivers Database et Sync
- ✓ Interface CLI avec commandes de génération
- ✓ Sécurité avancée (CORS, Rate Limiting)

### Corrections Récentes :

- ✓ Correction du namespace des événements prédéfinis
- ✓ Amélioration de la gestion des erreurs dans les queues
- ✓ Optimisation des performances du dispatcher d'événements
- ✓ Correction des tests automatisés pour PHP 8.1+

## 📁 Structure du Projet

```
├── app/                     # Code de l'application
│   ├── Controllers/         # Contrôleurs
│   ├── Models/              # Modèles
│   ├── Middleware/          # Middlewares personnalisés
│   ├── Events/              # Événements personnalisés
│   └── Jobs/                # Jobs pour les queues
├── config/                  # Configuration
│   ├── app.php
│   ├── database.php
│   ├── auth.php
│   └── queue.php
├── database/                # Migrations et seeds
│   ├── migrations/
│   └── seeds/
├── public/                  # Point d'entrée public
│   └── index.php
├── resources/               # Assets et vues
│   ├── views/
│   ├── css/
│   └── js/
├── routes/                  # Définition des routes
│   ├── web.php
│   └── api.php
├── src/                     # Code source du framework
│   └── Nexa/
│       ├── Core/
│       ├── Database/
│       ├── Routing/
│       ├── Auth/
│       ├── Events/
│       └── Queue/
├── storage/                  # Stockage (logs, cache, uploads)
│   ├── logs/
│   ├── cache/
│   └── uploads/
├── examples/                 # Exemples d'utilisation
│   └── complete_app.php
├── docs/                     # Documentation
│   └── PHASE2.md
├── nexa                      # CLI exécutable
├── NexaCLI.php              # Classe CLI principale
└── README.md                # Ce fichier
```

## 🔧 Configuration Avancée

### Configuration de la Base de Données

```php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'nexa',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../database/database.sqlite',
        ],
    ],
];
```

### Configuration des Événements

```php
// config/events.php
return [
    'listeners' => [
        'Nexa\Events\UserRegistered' => [
            'App\Listeners\SendWelcomeEmail',
            'App\Listeners\CreateUserProfile',
        ],
        'Nexa\Events\UserLoggedIn' => [
            'App\Listeners\LogUserActivity',
        ],
    ],
];
```

### Configuration des Queues

```php
// config/queue.php
return [
    'default' => 'database',
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'retry_after' => 90,
        ],
    ],
];
```

## 📊 Performance

Nexa Framework est conçu pour être rapide et efficace :

- **Temps de réponse** : ~5ms pour les routes simples
- **Empreinte mémoire** : ~2MB sans ORM, ~10MB avec ORM complet
- **Requêtes par seconde** : ~1000 req/s sur un serveur modeste

## 📚 Documentation

### Guides Essentiels
- [🚀 Guide de Démarrage Rapide](docs/QUICK_START.md) - Commencez en 5 minutes
- [📖 Documentation API Complète](docs/API_DOCUMENTATION.md) - Référence technique
- [✨ Meilleures Pratiques](docs/BEST_PRACTICES.md) - Patterns et anti-patterns
- [🎓 Tutoriels Détaillés](docs/TUTORIALS.md) - Apprenez par l'exemple

### Développement
- [🤝 Guide de Contribution](CONTRIBUTING.md) - Comment contribuer
- [📝 Changelog](CHANGELOG.md) - Historique des versions
- [🗺️ Phase 1 - Améliorations](PHASE1_IMPROVEMENTS.md)
- [🗺️ Phase 2 - Roadmap](PHASE2_ROADMAP.md)
- [🗺️ Phase 3 - Roadmap](PHASE3_ROADMAP.md)

### Ressources
- [🧪 Tests](tests/) - Suite de tests complète
- [💡 Exemples](examples/) - Projets d'exemple
- [🔧 Outils](tools/) - Utilitaires de développement

## 🤝 Contribution

Nous accueillons chaleureusement les contributions ! Voici comment vous pouvez aider :

### Signaler des bugs

1. Vérifiez que le bug n'a pas déjà été signalé
2. Créez une issue détaillée avec :
   - Description du problème
   - Étapes pour reproduire
   - Environnement (PHP, OS, etc.)
   - Code d'exemple si possible

### Proposer des fonctionnalités

1. Ouvrez une issue pour discuter de votre idée
2. Attendez les retours de la communauté
3. Implémentez la fonctionnalité
4. Soumettez une pull request

### Développement

```bash
# Fork et clone
git clone https://github.com/votre-username/nexa-framework.git
cd nexa-framework

# Installation des dépendances
composer install

# Tests
php vendor/bin/phpunit

# Standards de code
php vendor/bin/php-cs-fixer fix
```

### Guidelines

- **Code style** : PSR-12
- **Tests** : Couverture minimale de 80%
- **Documentation** : Commentaires PHPDoc
- **Commits** : Messages clairs et descriptifs
- **Branches** : `feature/nom-fonctionnalite` ou `fix/nom-bug`

## 📈 Roadmap

### Version 3.1 (Q2 2024)
- [ ] Support des WebSockets
- [ ] Queue system avancé
- [ ] API GraphQL intégrée
- [ ] Hot-reload pour les assets
- [ ] Amélioration des performances

### Version 3.2 (Q3 2024)
- [ ] Support multi-tenant
- [ ] Système de plugins avancé
- [ ] Interface d'administration
- [ ] Monitoring intégré
- [ ] Support Docker officiel

### Version 4.0 (Q4 2024)
- [ ] Architecture microservices
- [ ] Support PHP 8.3+
- [ ] Refactoring complet du core
- [ ] Nouvelle CLI interactive
- [ ] Performance x2

## 🏆 Communauté

- **Discord** : [Rejoindre le serveur](https://discord.gg/nexa)
- **Forum** : [forum.nexa-framework.com](https://forum.nexa-framework.com)
- **Twitter** : [@NexaFramework](https://twitter.com/NexaFramework)
- **Blog** : [blog.nexa-framework.com](https://blog.nexa-framework.com)

## 📚 Ressources

- **Documentation complète** : [docs.nexa-framework.com](https://docs.nexa-framework.com)
- **Tutoriels vidéo** : [YouTube](https://youtube.com/NexaFramework)
- **Exemples de projets** : [github.com/nexa/examples](https://github.com/nexa/examples)
- **Packages officiels** : [packagist.org/packages/nexa](https://packagist.org/packages/nexa/)

## 🎯 Sponsors

Nexa Framework est rendu possible grâce au soutien de nos sponsors :

- **🥇 Sponsors Or** : [Votre entreprise ici](mailto:sponsors@nexa-framework.com)
- **🥈 Sponsors Argent** : [Votre entreprise ici](mailto:sponsors@nexa-framework.com)
- **🥉 Sponsors Bronze** : [Votre entreprise ici](mailto:sponsors@nexa-framework.com)

[Devenir sponsor](https://github.com/sponsors/nexa-framework)

## 📄 Licence

Nexa Framework est un logiciel open source sous licence [MIT](LICENSE).

```
MIT License

Copyright (c) 2024 Nexa Framework

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

<div align="center">

**Fait avec ❤️ par l'équipe Nexa Framework**

[Site web](https://nexa-framework.com) • [Documentation](https://docs.nexa-framework.com) • [GitHub](https://github.com/nexa/framework) • [Discord](https://discord.gg/nexa)

⭐ **N'oubliez pas de donner une étoile si Nexa vous plaît !** ⭐

</div>