<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation - Nexa Framework</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --innovation-gradient: linear-gradient(135deg, #7D4FFE 0%, #7D4FFE 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }
        
        .innovation-bg {
            background: #7D4FFE;
        }
        
        .innovation-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .glow-text {
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }
        
        .code-block {
            background: rgba(30, 41, 59, 0.8);
            color: #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            overflow-x: auto;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .scroll-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #7D4FFE, #7D4FFE);
            transform-origin: left;
            z-index: 9999;
        }
        
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="innovation-bg text-white min-h-screen">
    <!-- Scroll Indicator -->
    <div class="scroll-indicator"></div>
    
    <!-- Fixed Glass Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 innovation-card backdrop-blur-md">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-xl font-bold glow-text">NexaFramework</a>
                    <div class="hidden md:flex space-x-6">
                        <a href="/" class="text-white/80 hover:text-white transition-colors">Accueil</a>
                        <a href="/about" class="text-white/80 hover:text-white transition-colors">À propos</a>
                        <a href="/documentation" class="text-white hover:text-white transition-colors border-b border-white/30">Documentation</a>
                        <a href="/contact" class="text-white/80 hover:text-white transition-colors">Contact</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="https://github.com/nexaframework" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <header class="pt-24 pb-16">
        <div class="container mx-auto px-6">
            <div class="text-center">
                <h1 class="text-5xl font-bold mb-4 glow-text">Documentation Nexa</h1>
                <p class="text-xl text-white/80">Guide complet pour développer avec le framework Nexa</p>
            </div>
        </div>
    </header>
    
    <!-- Quick Navigation -->
    <div class="container mx-auto px-6 mb-8">
        <div class="innovation-card rounded-lg p-4">
            <div class="flex flex-wrap gap-2 justify-center">
                <a href="#installation" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Installation</a>
                <a href="#routing" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Routage</a>
                <a href="#controllers" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Contrôleurs</a>
                <a href="#views" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Vues</a>
                <a href="#database" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">ORM & Base de données</a>
                <a href="#validation" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Validation</a>
                <a href="#middleware" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Middleware</a>
                <a href="#auth" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">JWT Auth</a>
                <a href="#events" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Événements</a>
                <a href="#queues" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Files d'attente</a>
                <a href="#modules" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Modules</a>
                <a href="#plugins" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Plugins</a>
                <a href="#graphql" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">GraphQL</a>
                <a href="#websockets" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">WebSockets</a>
                <a href="#microservices" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Microservices</a>
                <a href="#testing" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Tests</a>
                <a href="#cache" class="px-3 py-1 text-sm bg-white/10 hover:bg-white/20 rounded-full transition-colors">Cache & Logging</a>
            </div>
        </div>
    </div>

    <!-- Content -->
    <main class="container mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <aside class="lg:col-span-1">
                <div class="innovation-card rounded-lg p-6 sticky top-24">
                    <h3 class="font-bold text-white mb-4 glow-text">Table des matières</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#introduction" class="text-white/80 hover:text-white transition-colors">Introduction</a></li>
                        <li><a href="#installation" class="text-white/80 hover:text-white transition-colors">Installation</a></li>
                        <li><a href="#structure" class="text-white/80 hover:text-white transition-colors">Structure du projet</a></li>
                        <li><a href="#routing" class="text-white/80 hover:text-white transition-colors">Système de routage</a></li>
                        <li><a href="#controllers" class="text-white/80 hover:text-white transition-colors">Contrôleurs</a></li>
                        <li><a href="#views" class="text-white/80 hover:text-white transition-colors">Moteur de vues</a></li>
                        <li><a href="#database" class="text-white/80 hover:text-white transition-colors">ORM & Base de données</a></li>
                        <li><a href="#relations" class="text-white/80 hover:text-white transition-colors">Relations de modèles</a></li>
                        <li><a href="#querybuilder" class="text-white/80 hover:text-white transition-colors">Query Builder</a></li>
                        <li><a href="#validation" class="text-white/80 hover:text-white transition-colors">Système de validation</a></li>
                        <li><a href="#middleware" class="text-white/80 hover:text-white transition-colors">Middleware & Sécurité</a></li>
                        <li><a href="#auth" class="text-white/80 hover:text-white transition-colors">Authentification JWT</a></li>
                        <li><a href="#events" class="text-white/80 hover:text-white transition-colors">Système d'événements</a></li>
                        <li><a href="#queues" class="text-white/80 hover:text-white transition-colors">Files d'attente</a></li>
                        <li><a href="#modules" class="text-white/80 hover:text-white transition-colors">Système de modules</a></li>
                        <li><a href="#plugins" class="text-white/80 hover:text-white transition-colors">Système de plugins</a></li>
                        <li><a href="#graphql" class="text-white/80 hover:text-white transition-colors">GraphQL API</a></li>
                        <li><a href="#websockets" class="text-white/80 hover:text-white transition-colors">WebSockets</a></li>
                        <li><a href="#microservices" class="text-white/80 hover:text-white transition-colors">Microservices</a></li>
                        <li><a href="#testing" class="text-white/80 hover:text-white transition-colors">Framework de tests</a></li>
                        <li><a href="#cli" class="text-white/80 hover:text-white transition-colors">Interface CLI</a></li>
                        <li><a href="#cache" class="text-white/80 hover:text-white transition-colors">Cache</a></li>
                        <li><a href="#logging" class="text-white/80 hover:text-white transition-colors">Logging</a></li>
                        <li><a href="#services" class="text-white/80 hover:text-white transition-colors">Services</a></li>
                    </ul>
                </div>
            </aside>

            <!-- Main content -->
            <div class="lg:col-span-3">
                <!-- Introduction -->
                <section id="introduction" class="mb-12">
                    <div class="innovation-card rounded-lg p-8">
                        <h2 class="text-3xl font-bold text-white mb-6 glow-text">Introduction à Nexa</h2>
                        <p class="text-white/80 mb-4">
                            Nexa est un framework PHP moderne et riche en fonctionnalités, conçu pour offrir une expérience de développement exceptionnelle. 
                            Il suit les principes de l'architecture MVC et propose un écosystème complet pour créer des applications web robustes et performantes.
                        </p>
                        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">
                                        <strong>🚀 Phase 3 Complète :</strong> Système de modules dynamiques, plugins extensibles, GraphQL intégré, WebSockets temps réel, architecture microservices. Framework production-ready avec 90 tests validés !
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>✅ Phase 2 :</strong> Authentification JWT, système d'événements avancé, files d'attente avec retry, framework de tests complet, interface CLI, et sécurité renforcée.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Phase 1 :</strong> ORM avancé avec relations Eloquent-style, système de validation robuste, middlewares de sécurité, cache haute performance, logging PSR-3, Query Builder fluide.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            <!-- Phase 1 Features -->
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">🗄️ ORM Avancé</h4>
                                <p class="text-sm opacity-90">Relations complètes, Query Builder, migrations</p>
                            </div>
                            <div class="bg-gradient-to-r from-green-500 to-teal-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">🛡️ Sécurité</h4>
                                <p class="text-sm opacity-90">Validation, CSRF, middlewares d'authentification</p>
                            </div>
                            <div class="bg-gradient-to-r from-orange-500 to-red-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">⚡ Performance</h4>
                                <p class="text-sm opacity-90">Cache intelligent, logging optimisé</p>
                            </div>
                            <!-- Phase 2 Features -->
                            <div class="bg-gradient-to-r from-indigo-500 to-blue-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">🔐 JWT Auth</h4>
                                <p class="text-sm opacity-90">Tokens sécurisés, refresh automatique</p>
                            </div>
                            <div class="bg-gradient-to-r from-purple-500 to-pink-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">📡 Événements</h4>
                                <p class="text-sm opacity-90">System d'événements avec listeners</p>
                            </div>
                            <div class="bg-gradient-to-r from-teal-500 to-green-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">🔄 Files d'attente</h4>
                                <p class="text-sm opacity-90">Jobs asynchrones avec retry</p>
                            </div>
                            <div class="bg-gradient-to-r from-yellow-500 to-orange-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">🧪 Tests</h4>
                                <p class="text-sm opacity-90">Framework de tests complet</p>
                            </div>
                            <div class="bg-gradient-to-r from-gray-500 to-gray-700 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">💻 CLI</h4>
                                <p class="text-sm opacity-90">Interface en ligne de commande</p>
                            </div>
                            <!-- Phase 3 Features -->
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">🧩 Modules</h4>
                                <p class="text-sm opacity-90">Système modulaire dynamique</p>
                            </div>
                            <div class="bg-gradient-to-r from-violet-500 to-purple-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">🔌 Plugins</h4>
                                <p class="text-sm opacity-90">Architecture extensible</p>
                            </div>
                            <div class="bg-gradient-to-r from-pink-500 to-rose-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">🚀 GraphQL</h4>
                                <p class="text-sm opacity-90">API moderne et flexible</p>
                            </div>
                            <div class="bg-gradient-to-r from-cyan-500 to-blue-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">🌐 WebSockets</h4>
                                <p class="text-sm opacity-90">Communication temps réel</p>
                            </div>
                            <div class="bg-gradient-to-r from-amber-500 to-orange-600 text-white p-4 rounded-lg">
                                <h4 class="font-bold mb-2">⚙️ Microservices</h4>
                                <p class="text-sm opacity-90">Architecture distribuée</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Installation -->
                <section id="installation" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Installation</h2>
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Prérequis</h3>
                        <ul class="list-disc list-inside text-gray-700 mb-6">
                            <li>PHP 8.0 ou supérieur</li>
                            <li>Composer</li>
                            <li>Serveur web (Apache, Nginx, ou serveur de développement PHP)</li>
                        </ul>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Installation via Composer</h3>
                        <div class="code-block mb-6">
                            <code>composer create-project nexa/framework mon-projet</code>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Démarrage du serveur de développement</h3>
                        <div class="code-block mb-6">
                            <code>cd mon-projet<br>php -S localhost:8000 -t public</code>
                        </div>
                    </div>
                </section>

                <!-- Structure -->
                <section id="structure" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Structure du projet</h2>
                        <div class="code-block mb-6">
                            <pre>mon-projet/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   └── Models/
├── config/
│   ├── app.php
│   └── database.php
├── public/
│   └── index.php
├── resources/
│   └── views/
├── routes/
│   └── web.php
├── src/
│   └── Nexa/
└── vendor/</pre>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded">
                                <h4 class="font-semibold text-gray-800 mb-2">app/</h4>
                                <p class="text-sm text-gray-600">Contient les contrôleurs et modèles de votre application</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded">
                                <h4 class="font-semibold text-gray-800 mb-2">config/</h4>
                                <p class="text-sm text-gray-600">Fichiers de configuration de l'application</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded">
                                <h4 class="font-semibold text-gray-800 mb-2">resources/views/</h4>
                                <p class="text-sm text-gray-600">Templates et vues de l'application</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded">
                                <h4 class="font-semibold text-gray-800 mb-2">routes/</h4>
                                <p class="text-sm text-gray-600">Définition des routes de l'application</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Routing -->
                <section id="routing" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Système de routage</h2>
                        <p class="text-gray-700 mb-6">
                            Le système de routage de Nexa permet de définir facilement les URL de votre application et de les associer à des contrôleurs ou des fonctions.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Définition des routes</h3>
                        <p class="text-gray-700 mb-4">Les routes sont définies dans le fichier <code class="bg-gray-100 px-2 py-1 rounded">routes/web.php</code> :</p>
                        <div class="code-block mb-6">
                            <pre>&lt;?php
use Nexa\Routing\Router;
use App\Http\Controllers\WelcomeController;

$router = new Router;

// Route simple
$router->get('/', [WelcomeController::class, 'index']);

// Route avec paramètre
$router->get('/user/{id}', [UserController::class, 'show']);

// Route POST
$router->post('/contact', [ContactController::class, 'store']);

return $router;</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Méthodes HTTP supportées</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-green-50 border border-green-200 p-3 rounded text-center">
                                <span class="font-semibold text-green-800">GET</span>
                            </div>
                            <div class="bg-blue-50 border border-blue-200 p-3 rounded text-center">
                                <span class="font-semibold text-blue-800">POST</span>
                            </div>
                            <div class="bg-yellow-50 border border-yellow-200 p-3 rounded text-center">
                                <span class="font-semibold text-yellow-800">PUT</span>
                            </div>
                            <div class="bg-red-50 border border-red-200 p-3 rounded text-center">
                                <span class="font-semibold text-red-800">DELETE</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Controllers -->
                <section id="controllers" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Contrôleurs</h2>
                        <p class="text-gray-700 mb-6">
                            Les contrôleurs organisent la logique de votre application et gèrent les requêtes HTTP.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Création d'un contrôleur</h3>
                        <div class="code-block mb-6">
                            <pre>&lt;?php

namespace App\Http\Controllers;

use Nexa\Http\Controller;

class WelcomeController extends Controller
{
    public function index()
    {
        return $this->view('welcome', [
            'message' => 'Bienvenue sur Nexa!'
        ]);
    }
    
    public function show($id)
    {
        return $this->view('user', [
            'user_id' => $id
        ]);
    }
}</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Méthodes disponibles</h3>
                        <div class="space-y-4">
                            <div class="border-l-4 border-blue-400 pl-4">
                                <h4 class="font-semibold text-gray-800">view($template, $data)</h4>
                                <p class="text-gray-600 text-sm">Rend une vue avec des données</p>
                            </div>
                            <div class="border-l-4 border-green-400 pl-4">
                                <h4 class="font-semibold text-gray-800">json($data)</h4>
                                <p class="text-gray-600 text-sm">Retourne une réponse JSON</p>
                            </div>
                            <div class="border-l-4 border-purple-400 pl-4">
                                <h4 class="font-semibold text-gray-800">redirect($url)</h4>
                                <p class="text-gray-600 text-sm">Effectue une redirection</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Views -->
                <section id="views" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Moteur de vues</h2>
                        <p class="text-gray-700 mb-6">
                            Nexa inclut un moteur de templates simple et puissant pour créer vos interfaces utilisateur.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Syntaxe des templates</h3>
                        <div class="code-block mb-6">
                            <pre>&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1&gt;&lt;/h1&gt;
    
    @@if($user)
        &lt;p&gt;Bonjour !&lt;/p&gt;
    @@endif
    
    @@include('partials.footer')
&lt;/body&gt;
&lt;/html&gt;</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Directives disponibles</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded">
                                <h4 class="font-semibold text-gray-800 mb-2">" }}</h4>
                                <p class="text-sm text-gray-600">Affichage de variables</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded">
                                <h4 class="font-semibold text-gray-800 mb-2">@@if / @@endif</h4>
                                <p class="text-sm text-gray-600">Conditions</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded">
                                <h4 class="font-semibold text-gray-800 mb-2">@@include</h4>
                                <p class="text-sm text-gray-600">Inclusion de templates</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded">
                                <h4 class="font-semibold text-gray-800 mb-2">@@tailwind</h4>
                                <p class="text-sm text-gray-600">Support Tailwind CSS</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Database & ORM -->
                <section id="database" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">ORM & Base de données</h2>
                        <p class="text-gray-700 mb-6">
                            Nexa propose un ORM avancé inspiré d'Eloquent avec un Query Builder fluide pour interagir facilement avec vos bases de données.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Configuration</h3>
                        <p class="text-gray-700 mb-4">Configurez votre base de données dans <code class="bg-gray-100 px-2 py-1 rounded">.env</code> :</p>
                        <div class="code-block mb-6">
                            <pre>DB_HOST=localhost
DB_DATABASE=nexa_app
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Modèles Eloquent</h3>
                        <p class="text-gray-700 mb-4">Créez des modèles pour représenter vos tables :</p>
                        <div class="code-block mb-6">
                            <pre>&lt;?php
// app/Models/User.php
use Nexa\Database\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
    
    // Relation avec les posts
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    // Relation avec le profil
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Opérations CRUD</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Créer</h4>
                                <pre>$user = new User();
$user->name = 'Jean Dupont';
$user->email = 'jean@example.com';
$user->save();

// Ou avec create()
$user = User::create([
    'name' => 'Marie Martin',
    'email' => 'marie@example.com'
]);</pre>
                            </div>
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Lire</h4>
                                <pre>// Tous les utilisateurs
$users = User::all();

// Par ID
$user = User::find(1);

// Avec conditions
$users = User::where('active', true)
    ->orderBy('created_at', 'desc')
    ->get();</pre>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Relations -->
                <section id="relations" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Relations de modèles</h2>
                        <p class="text-gray-700 mb-6">
                            Nexa supporte tous les types de relations Eloquent pour modéliser vos données complexes.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-blue-800 mb-2">Un-à-Un (hasOne)</h4>
                                <div class="code-block text-xs">
                                    <pre>public function profile()
{
    return $this->hasOne(Profile::class);
}

// Utilisation
$profile = $user->profile;</pre>
                                </div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-green-800 mb-2">Un-à-Plusieurs (hasMany)</h4>
                                <div class="code-block text-xs">
                                    <pre>public function posts()
{
    return $this->hasMany(Post::class);
}

// Utilisation
$posts = $user->posts;</pre>
                                </div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-purple-800 mb-2">Appartient-à (belongsTo)</h4>
                                <div class="code-block text-xs">
                                    <pre>public function user()
{
    return $this->belongsTo(User::class);
}

// Utilisation
$user = $post->user;</pre>
                                </div>
                            </div>
                            <div class="bg-orange-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-orange-800 mb-2">Plusieurs-à-Plusieurs</h4>
                                <div class="code-block text-xs">
                                    <pre>public function roles()
{
    return $this->belongsToMany(
        Role::class, 'user_roles'
    );
}

// Utilisation
$roles = $user->roles;</pre>
                                </div>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Requêtes avec relations</h3>
                        <div class="code-block mb-6">
                            <pre>// Charger les relations
$user = User::with('posts', 'profile')->find(1);

// Filtrer par relation
$users = User::whereHas('posts', function($query) {
    $query->where('published', true);
})->get();

// Compter les relations
$user = User::withCount('posts')->find(1);
echo $user->posts_count;</pre>
                        </div>
                    </div>
                </section>
                
                <!-- Query Builder -->
                <section id="querybuilder" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Query Builder</h2>
                        <p class="text-gray-700 mb-6">
                            Le Query Builder de Nexa offre une interface fluide pour construire des requêtes SQL complexes.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Requêtes de base</h3>
                        <div class="code-block mb-6">
                            <pre>// SELECT avec conditions
$users = User::where('active', true)
    ->where('age', '>', 18)
    ->orWhere('role', 'admin')
    ->get();

// WHERE IN
$users = User::whereIn('role', ['admin', 'editor'])->get();

// LIKE
$users = User::where('name', 'LIKE', '%jean%')->get();

// ORDER BY et LIMIT
$users = User::orderBy('created_at', 'desc')
    ->limit(10)
    ->offset(20)
    ->get();</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Agrégations</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="code-block">
                                <pre>// Compter
$count = User::count();
$activeCount = User::where('active', true)->count();

// Autres agrégations
$maxAge = User::max('age');
$avgAge = User::avg('age');
$sumSalary = User::sum('salary');</pre>
                            </div>
                            <div class="code-block">
                                <pre>// GROUP BY
$stats = User::select('role')
    ->selectRaw('COUNT(*) as count')
    ->groupBy('role')
    ->get();

// HAVING
$roles = User::groupBy('role')
    ->havingRaw('COUNT(*) > 5')
    ->get();</pre>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">JOINs</h3>
                        <div class="code-block mb-6">
                            <pre>// INNER JOIN
$users = User::join('profiles', 'users.id', '=', 'profiles.user_id')
    ->select('users.*', 'profiles.bio')
    ->get();

// LEFT JOIN
$users = User::leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->select('users.*')
    ->selectRaw('COUNT(posts.id) as posts_count')
    ->groupBy('users.id')
    ->get();</pre>
                        </div>
                    </div>
                </section>

                <!-- Validation -->
                <section id="validation" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Système de validation</h2>
                        <p class="text-gray-700 mb-6">
                            Nexa propose un système de validation robuste avec des règles prédéfinies et personnalisables.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Validation dans les contrôleurs</h3>
                        <div class="code-block mb-6">
                            <pre>&lt;?php

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'age' => 'required|integer|min:18|max:120'
        ]);
        
        User::create($validated);
        return redirect('/users')->with('success', 'Utilisateur créé!');
    }
}</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Règles disponibles</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-blue-800 mb-2">Règles de base</h4>
                                <ul class="text-sm text-blue-700 space-y-1">
                                    <li><code>required</code> - Champ obligatoire</li>
                                    <li><code>string</code> - Doit être une chaîne</li>
                                    <li><code>integer</code> - Doit être un entier</li>
                                    <li><code>email</code> - Format email valide</li>
                                    <li><code>url</code> - URL valide</li>
                                </ul>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-green-800 mb-2">Règles avancées</h4>
                                <ul class="text-sm text-green-700 space-y-1">
                                    <li><code>min:value</code> - Valeur/longueur minimale</li>
                                    <li><code>max:value</code> - Valeur/longueur maximale</li>
                                    <li><code>unique:table</code> - Valeur unique en BDD</li>
                                    <li><code>confirmed</code> - Confirmation de champ</li>
                                    <li><code>in:val1,val2</code> - Dans une liste</li>
                                </ul>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Messages d'erreur personnalisés</h3>
                        <div class="code-block mb-6">
                            <pre>$validated = $request->validate([
    'email' => 'required|email'
], [
    'email.required' => 'L\'adresse email est obligatoire.',
    'email.email' => 'Veuillez saisir une adresse email valide.'
]);</pre>
                        </div>
                    </div>
                </section>
                
                <!-- Middleware -->
                <section id="middleware" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Middleware & Sécurité</h2>
                        <p class="text-gray-700 mb-6">
                            Les middlewares permettent de filtrer les requêtes HTTP et d'ajouter des couches de sécurité.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Création d'un middleware</h3>
                        <div class="code-block mb-6">
                            <pre>&lt;?php
// app/Middleware/AuthMiddleware.php
use Nexa\Http\Middleware\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle($request, $next)
    {
        if (!$request->session()->has('user_id')) {
            return redirect('/login');
        }
        
        return $next($request);
    }
}</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Application des middlewares</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Sur les routes</h4>
                                <pre>// routes/web.php
Route::get('/dashboard', 'DashboardController@index')
    ->middleware('auth');

// Groupe de routes
Route::group(['middleware' => 'auth'], function() {
    Route::get('/profile', 'ProfileController@show');
    Route::post('/profile', 'ProfileController@update');
});</pre>
                            </div>
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Dans les contrôleurs</h4>
                                <pre>class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin')
            ->only(['destroy', 'edit']);
    }
}</pre>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Middlewares intégrés</h3>
                        <div class="bg-yellow-50 p-4 rounded-lg mb-6">
                            <ul class="text-sm text-yellow-800 space-y-2">
                                <li><strong>CSRF Protection:</strong> Protection contre les attaques CSRF</li>
                                <li><strong>Rate Limiting:</strong> Limitation du nombre de requêtes</li>
                                <li><strong>CORS:</strong> Gestion des requêtes cross-origin</li>
                                <li><strong>Security Headers:</strong> En-têtes de sécurité automatiques</li>
                            </ul>
                        </div>
                    </div>
                </section>
                
                <!-- Cache -->
                <section id="cache" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Cache</h2>
                        <p class="text-gray-700 mb-6">
                            Le système de cache de Nexa améliore les performances en stockant temporairement les données fréquemment utilisées.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Configuration</h3>
                        <div class="code-block mb-6">
                            <pre>// .env
CACHE_DRIVER=file
CACHE_PREFIX=nexa_
CACHE_TTL=3600</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Utilisation basique</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Stocker et récupérer</h4>
                                <pre>use Nexa\Cache\Cache;

// Stocker
Cache::put('user.1', $user, 3600);

// Récupérer
$user = Cache::get('user.1');

// Avec valeur par défaut
$user = Cache::get('user.1', function() {
    return User::find(1);
});</pre>
                            </div>
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Opérations avancées</h4>
                                <pre>// Vérifier l'existence
if (Cache::has('user.1')) {
    // ...
}

// Supprimer
Cache::forget('user.1');

// Vider tout le cache
Cache::flush();

// Remember (récupère ou stocke)
$users = Cache::remember('users.all', 3600, function() {
    return User::all();
});</pre>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Cache de requêtes</h3>
                        <div class="code-block mb-6">
                            <pre>// Cache automatique des requêtes
$users = User::cache(3600)->where('active', true)->get();

// Cache avec tag
$posts = Post::cacheWithTags(['posts', 'content'], 3600)
    ->where('published', true)
    ->get();

// Invalider par tag
Cache::tags(['posts'])->flush();</pre>
                        </div>
                    </div>
                </section>
                
                <!-- Logging -->
                <section id="logging" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Logging</h2>
                        <p class="text-gray-700 mb-6">
                            Le système de logging de Nexa vous aide à surveiller et déboguer votre application.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Configuration</h3>
                        <div class="code-block mb-6">
                            <pre>// .env
LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_PATH=storage/logs</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Niveaux de log</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Utilisation basique</h4>
                                <pre>use Nexa\Log\Log;

// Différents niveaux
Log::emergency('Système en panne!');
Log::alert('Action immédiate requise');
Log::critical('Erreur critique');
Log::error('Erreur standard');
Log::warning('Avertissement');
Log::notice('Information notable');
Log::info('Information générale');
Log::debug('Information de débogage');</pre>
                            </div>
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Avec contexte</h4>
                                <pre>// Ajouter du contexte
Log::info('Utilisateur connecté', [
    'user_id' => $user->id,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent()
]);

// Log d'exception
try {
    // Code risqué
} catch (Exception $e) {
    Log::error('Erreur lors du traitement', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}</pre>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Canaux personnalisés</h3>
                        <div class="code-block mb-6">
                            <pre>// Créer un canal spécifique
Log::channel('security')->warning('Tentative de connexion suspecte', [
    'ip' => $request->ip(),
    'attempts' => $attempts
]);

// Log conditionnel
Log::when($app->isProduction(), function($log) {
    $log->info('Application en production');
});</pre>
                        </div>
                    </div>
                </section>
                
                <!-- JWT Authentication -->
                <section id="auth" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">🔐 Authentification JWT</h2>
                        <p class="text-gray-700 mb-6">
                            Le système d'authentification JWT de Nexa offre une sécurité robuste avec gestion des tokens et refresh automatique.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Configuration</h3>
                        <div class="code-block mb-6">
                            <pre>// config/phase2.php
'jwt' => [
    'secret' => env('JWT_SECRET', 'your-secret-key'),
    'algorithm' => 'HS256',
    'expiration' => 3600, // 1 heure
    'refresh_expiration' => 604800, // 7 jours
]</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Utilisation</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Génération de token</h4>
                                <pre>use Nexa\Auth\JWTAuth;

$auth = new JWTAuth();

// Générer un token
$token = $auth->generateToken([
    'user_id' => $user->id,
    'email' => $user->email
]);

// Générer un refresh token
$refreshToken = $auth->generateRefreshToken($user->id);</pre>
                            </div>
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Validation</h4>
                                <pre>// Valider un token
try {
    $payload = $auth->validateToken($token);
    $userId = $payload['user_id'];
} catch (Exception $e) {
    // Token invalide
}

// Refresh d'un token
$newToken = $auth->refreshToken($refreshToken);</pre>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Middleware d'authentification</h3>
                        <div class="code-block mb-6">
                            <pre>// Dans vos routes
$router->group(['middleware' => 'auth.jwt'], function($router) {
    $router->get('/profile', [UserController::class, 'profile']);
    $router->post('/logout', [AuthController::class, 'logout']);
});</pre>
                        </div>
                    </div>
                </section>
                
                <!-- Event System -->
                <section id="events" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">📡 Système d'Événements</h2>
                        <p class="text-gray-700 mb-6">
                            Le système d'événements permet de découpler votre application en utilisant le pattern Observer.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Événements prédéfinis</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">UserRegistered</h4>
                                <pre>use Nexa\Events\UserRegistered;

$userData = [
    'id' => 1,
    'email' => 'user@example.com',
    'name' => 'John Doe'
];

$event = new UserRegistered($userData);
echo $event->getUserId();    // 1
echo $event->getUserEmail(); // user@example.com
echo $event->getUserName();  // John Doe</pre>
                            </div>
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">UserLoggedIn</h4>
                                <pre>use Nexa\Events\UserLoggedIn;

$event = new UserLoggedIn(
    $userData,
    '192.168.1.1',
    'Mozilla/5.0'
);

echo $event->getUserId();    // 1
echo $event->getIpAddress(); // 192.168.1.1
echo $event->getUserAgent(); // Mozilla/5.0</pre>
                            </div>
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">ModelCreated</h4>
                                <pre>use Nexa\Events\ModelCreated;

$modelData = [
    'id' => 1,
    'title' => 'Post',
    'content' => 'Content'
];

$event = new ModelCreated('Post', $modelData);
echo $event->getModelName(); // Post
echo $event->getModelId();   // 1</pre>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Listeners et dispatch</h3>
                        <div class="code-block mb-6">
                            <pre>// Enregistrer un listener
$eventDispatcher->listen('UserRegistered', function($event) {
    // Envoyer un email de bienvenue
    $job = new SendEmailJob([
        'to' => $event->getUserEmail(),
        'template' => 'welcome'
    ]);
    $queueManager->push($job);
});

// Déclencher l'événement
$eventDispatcher->dispatch($event);</pre>
                        </div>
                    </div>
                </section>
                
                <!-- Queue System -->
                <section id="queues" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">🔄 Files d'Attente</h2>
                        <p class="text-gray-700 mb-6">
                            Le système de files d'attente permet d'exécuter des tâches en arrière-plan avec gestion des échecs et retry automatique.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Création d'un Job</h3>
                        <div class="code-block mb-6">
                            <pre>use Nexa\Queue\Job;

class SendEmailJob extends Job {
    private $emailData;
    
    public function __construct($emailData) {
        $this->emailData = $emailData;
    }
    
    public function handle() {
        // Logique d'envoi d'email
        $mailer = new Mailer();
        $mailer->send($this->emailData);
    }
    
    public function shouldRetry($exception) {
        return $exception instanceof TemporaryException;
    }
    
    public function retry() {
        // Logique de retry personnalisée
        sleep(5); // Attendre 5 secondes
    }
}</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Utilisation</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Ajouter à la queue</h4>
                                <pre>use Nexa\Queue\QueueManager;

$queueManager = new QueueManager();

// Ajouter un job
$job = new SendEmailJob([
    'to' => 'user@example.com',
    'subject' => 'Bienvenue',
    'template' => 'welcome'
]);

$queueManager->push($job);</pre>
                            </div>
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Traitement</h4>
                                <pre>// Traiter la queue
$queueManager->work();

// Traiter un nombre limité de jobs
$queueManager->work(10);

// Vérifier la taille de la queue
$size = $queueManager->size();
echo "Jobs en attente: $size";</pre>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Testing Framework -->
                <section id="testing" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">🧪 Framework de Tests</h2>
                        <p class="text-gray-700 mb-6">
                            Nexa inclut un framework de tests complet pour valider votre application.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Écriture de tests</h3>
                        <div class="code-block mb-6">
                            <pre>use Nexa\Testing\TestCase;

class UserTest extends TestCase {
    public function testUserCreation() {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }
    
    public function testUserValidation() {
        $this->expectException(ValidationException::class);
        
        new User([
            'name' => '',
            'email' => 'invalid-email'
        ]);
    }
}</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Exécution des tests</h3>
                        <div class="code-block mb-6">
                            <pre>// Exécuter tous les tests
php vendor/bin/phpunit

// Exécuter un test spécifique
php vendor/bin/phpunit tests/UserTest.php

// Avec couverture de code
php vendor/bin/phpunit --coverage-html coverage/</pre>
                        </div>
                        
                        <div class="bg-green-50 border-l-4 border-green-400 p-4">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">
                                        <strong>🚀 Tests Phase 3 :</strong> Framework production-ready ! Tous les 90 tests passent avec succès, incluant modules, plugins, GraphQL, WebSockets et microservices.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Modules System -->
                <section id="modules" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">🧩 Système de Modules</h2>
                        <p class="text-gray-700 mb-6">
                            Le système de modules permet d'organiser votre application en composants réutilisables et indépendants.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Création d'un module</h3>
                        <div class="code-block mb-6">
                            <pre>use Nexa\Modules\Module;

class BlogModule extends Module {
    protected $name = "Blog Module";
    protected $version = "1.0.0";
    protected $description = "Module de gestion de blog";
    
    public function register(): void {
        // Enregistrer les services du module
    }
    
    public function boot(): void {
        // Initialiser le module
        $this->loadRoutes();
        $this->loadViews();
        $this->loadMigrations();
    }
}</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Gestion des modules</h3>
                        <div class="code-block mb-6">
                            <pre>// Découvrir et charger les modules
$moduleManager = app('modules');
$modules = $moduleManager->discoverModules();

// Activer un module
$moduleManager->activate('BlogModule');

// Désactiver un module
$moduleManager->deactivate('BlogModule');

// Obtenir un module
$blogModule = $moduleManager->getModule('BlogModule');</pre>
                        </div>
                    </div>
                </section>

                <!-- Plugins System -->
                <section id="plugins" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">🔌 Système de Plugins</h2>
                        <p class="text-gray-700 mb-6">
                            Les plugins permettent d'étendre les fonctionnalités du framework de manière modulaire.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Création d'un plugin</h3>
                        <div class="code-block mb-6">
                            <pre>use Nexa\Plugins\Plugin;

class SEOPlugin extends Plugin {
    protected $name = "SEO Plugin";
    protected $version = "1.0.0";
    
    public function activate(): void {
        // Logique d'activation
        $this->addHooks();
    }
    
    public function deactivate(): void {
        // Logique de désactivation
        $this->removeHooks();
    }
    
    private function addHooks(): void {
        add_action('head', [$this, 'addMetaTags']);
    }
}</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Gestion des plugins</h3>
                        <div class="code-block mb-6">
                            <pre>// Charger les plugins
$pluginManager = app('plugins');
$plugins = $pluginManager->loadPlugins();

// Activer un plugin
$pluginManager->activate('SEOPlugin');

// Désactiver un plugin
$pluginManager->deactivate('SEOPlugin');

// Lister les plugins actifs
$activePlugins = $pluginManager->getActivePlugins();</pre>
                        </div>
                    </div>
                </section>

                <!-- GraphQL -->
                <section id="graphql" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">🚀 GraphQL API</h2>
                        <p class="text-gray-700 mb-6">
                            GraphQL offre une API flexible et efficace pour interroger vos données.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Définition des types</h3>
                        <div class="code-block mb-6">
                            <pre>use Nexa\GraphQL\Type;

class UserType extends Type {
    public function fields(): array {
        return [
            'id' => ['type' => 'ID'],
            'name' => ['type' => 'String'],
            'email' => ['type' => 'String'],
            'posts' => [
                'type' => '[Post]',
                'resolve' => function($user) {
                    return $user->posts();
                }
            ]
        ];
    }
}</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Requêtes et mutations</h3>
                        <div class="code-block mb-6">
                            <pre>// Exemple de requête GraphQL
query {
    users {
        id
        name
        email
        posts {
            title
            content
        }
    }
}

// Exemple de mutation
mutation {
    createUser(input: {
        name: "John Doe"
        email: "john@example.com"
    }) {
        id
        name
    }
}</pre>
                        </div>
                    </div>
                </section>

                <!-- WebSockets -->
                <section id="websockets" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">🌐 WebSockets</h2>
                        <p class="text-gray-700 mb-6">
                            Les WebSockets permettent une communication bidirectionnelle en temps réel.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Serveur WebSocket</h3>
                        <div class="code-block mb-6">
                            <pre>use Nexa\WebSockets\WebSocketServer;

$server = new WebSocketServer('localhost', 8080);

$server->onConnection(function($connection) {
    echo "Nouvelle connexion: {$connection->getId()}\n";
});

$server->onMessage(function($connection, $message) {
    // Diffuser le message à tous les clients
    $server->broadcast($message);
});

$server->onDisconnection(function($connection) {
    echo "Déconnexion: {$connection->getId()}\n";
});

$server->start();</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Client WebSocket</h3>
                        <div class="code-block mb-6">
                            <pre>// JavaScript côté client
const socket = new WebSocket('ws://localhost:8080');

socket.onopen = function(event) {
    console.log('Connexion établie');
    socket.send('Hello Server!');
};

socket.onmessage = function(event) {
    console.log('Message reçu:', event.data);
};

socket.onclose = function(event) {
    console.log('Connexion fermée');
};</pre>
                        </div>
                    </div>
                </section>

                <!-- Microservices -->
                <section id="microservices" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">⚙️ Architecture Microservices</h2>
                        <p class="text-gray-700 mb-6">
                            Le framework supporte une architecture microservices pour des applications distribuées.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Registre de services</h3>
                        <div class="code-block mb-6">
                            <pre>use Nexa\Microservices\ServiceRegistry;

$registry = new ServiceRegistry();

// Enregistrer un service
$registry->register('user-service', [
    'host' => 'localhost',
    'port' => 3001,
    'health_check' => '/health'
]);

// Découvrir un service
$service = $registry->discover('user-service');

// Vérifier la santé des services
$healthStatus = $registry->healthCheck();</pre>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Client de service</h3>
                        <div class="code-block mb-6">
                            <pre>use Nexa\Microservices\ServiceClient;

$client = new ServiceClient();

// Appel à un microservice
$response = $client->call('user-service', 'GET', '/users/1');

// Avec gestion d'erreur et retry
$response = $client->callWithRetry('user-service', 'POST', '/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
], 3); // 3 tentatives</pre>
                        </div>
                    </div>
                </section>
                
                <!-- CLI Interface -->
                <section id="cli" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">💻 Interface CLI</h2>
                        <p class="text-gray-700 mb-6">
                            L'interface en ligne de commande facilite la gestion et le développement de votre application.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Commandes disponibles</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Gestion des queues</h4>
                                <pre># Démarrer le worker de queue
php nexa queue:work

# Traiter un nombre limité de jobs
php nexa queue:work --limit=10

# Vider la queue
php nexa queue:clear</pre>
                            </div>
                            <div class="code-block">
                                <h4 class="text-white font-semibold mb-2">Gestion du cache</h4>
                                <pre># Vider le cache
php nexa cache:clear

# Voir les statistiques du cache
php nexa cache:stats

# Précharger le cache
php nexa cache:warm</pre>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Commandes personnalisées</h3>
                        <div class="code-block mb-6">
                            <pre>use Nexa\Console\Command;

class CustomCommand extends Command {
    protected $signature = 'app:custom {argument} {--option=}';
    protected $description = 'Description de la commande';
    
    public function handle() {
        $argument = $this->argument('argument');
        $option = $this->option('option');
        
        $this->info('Commande exécutée avec succès!');
    }
}</pre>
                        </div>
                    </div>
                </section>

                <!-- Services -->
                <section id="services" class="mb-12">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Services et injection de dépendances</h2>
                        <p class="text-gray-700 mb-6">
                            Nexa utilise un conteneur d'injection de dépendances pour gérer les services de votre application.
                        </p>
                        
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Service Providers</h3>
                        <p class="text-gray-700 mb-4">Les service providers sont enregistrés dans <code class="bg-gray-100 px-2 py-1 rounded">config/app.php</code> :</p>
                        <div class="code-block mb-6">
                            <pre>'providers' => [
    Nexa\Database\DatabaseServiceProvider::class,
    Nexa\View\ViewServiceProvider::class,
    Nexa\Routing\RoutingServiceProvider::class,
]</pre>
                        </div>
                        
                        <div class="bg-green-50 border-l-4 border-green-400 p-4">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm text-green-700">
                                        <strong>Conseil :</strong> Créez vos propres service providers pour organiser l'initialisation de vos services personnalisés.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">Nexa Framework</h3>
                    <p class="text-gray-300">Un framework PHP moderne et léger pour créer des applications web robustes.</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Liens utiles</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-300 hover:text-white">GitHub</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white">Communauté</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white">Support</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact</h4>
                    <p class="text-gray-300">Des questions ? Contactez-nous sur notre Discord ou GitHub.</p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-400">&copy; 2025 Nexa Framework. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>