<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - Nexa Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="gradient-bg min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-4xl w-full">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-6xl font-bold text-white mb-4 tracking-tight">
                    À propos de Nexa
                </h1>
                <p class="text-xl text-indigo-100 max-w-2xl mx-auto">
                    Découvrez l'histoire et la philosophie derrière Nexa Framework
                </p>
            </div>

            <!-- Main Card -->
            <div class="bg-white rounded-2xl card-shadow p-8 mb-8">
                <?php if (isset($message)): ?>
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-blue-700 font-medium"><?= htmlspecialchars($message) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- About Content -->
                <div class="prose prose-lg max-w-none">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-800 mb-4">Notre Mission</h2>
                        <p class="text-gray-600 leading-relaxed mb-6">
                            Nexa Framework révolutionne le développement web en PHP en offrant une plateforme complète, 
                            moderne et extensible. De la simple application web aux architectures microservices complexes, 
                            Nexa s'adapte à tous vos besoins avec élégance et performance.
                        </p>
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-xl border border-blue-200">
                            <h3 class="text-lg font-semibold text-blue-800 mb-2">🚀 Phase 3 - Production Ready</h3>
                            <p class="text-blue-700">Avec 90 tests validés et des fonctionnalités enterprise-grade, Nexa Framework est maintenant prêt pour vos projets les plus ambitieux.</p>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6 mb-8">
                        <!-- Philosophy -->
                        <div class="bg-gradient-to-br from-indigo-50 to-blue-50 p-6 rounded-lg border border-indigo-200">
                            <h3 class="text-xl font-semibold text-gray-800 mb-3">🎯 Philosophie</h3>
                            <ul class="text-gray-600 space-y-2">
                                <li class="flex items-start">
                                    <span class="text-indigo-500 mr-2">•</span>
                                    Simplicité avant tout
                                </li>
                                <li class="flex items-start">
                                    <span class="text-indigo-500 mr-2">•</span>
                                    Performance optimisée
                                </li>
                                <li class="flex items-start">
                                    <span class="text-indigo-500 mr-2">•</span>
                                    Code maintenable
                                </li>
                                <li class="flex items-start">
                                    <span class="text-indigo-500 mr-2">•</span>
                                    Architecture modulaire
                                </li>
                            </ul>
                        </div>

                        <!-- Core Features -->
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-6 rounded-lg border border-green-200">
                            <h3 class="text-xl font-semibold text-gray-800 mb-3">⚡ Fonctionnalités Core</h3>
                            <ul class="text-gray-600 space-y-2">
                                <li class="flex items-start">
                                    <span class="text-green-500 mr-2">•</span>
                                    Routage intelligent
                                </li>
                                <li class="flex items-start">
                                    <span class="text-green-500 mr-2">•</span>
                                    ORM avancé
                                </li>
                                <li class="flex items-start">
                                    <span class="text-green-500 mr-2">•</span>
                                    Authentification JWT
                                </li>
                                <li class="flex items-start">
                                    <span class="text-green-500 mr-2">•</span>
                                    Système d'événements
                                </li>
                            </ul>
                        </div>

                        <!-- Advanced Features -->
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 p-6 rounded-lg border border-purple-200">
                            <h3 class="text-xl font-semibold text-gray-800 mb-3">🚀 Fonctionnalités Avancées</h3>
                            <ul class="text-gray-600 space-y-2">
                                <li class="flex items-start">
                                    <span class="text-purple-500 mr-2">•</span>
                                    Modules dynamiques
                                </li>
                                <li class="flex items-start">
                                    <span class="text-purple-500 mr-2">•</span>
                                    GraphQL API
                                </li>
                                <li class="flex items-start">
                                    <span class="text-purple-500 mr-2">•</span>
                                    WebSockets temps réel
                                </li>
                                <li class="flex items-start">
                                    <span class="text-purple-500 mr-2">•</span>
                                    Architecture microservices
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Statistics Section -->
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-8 rounded-xl mb-8 border border-gray-200">
                        <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">📊 Nexa en Chiffres</h3>
                        <div class="grid md:grid-cols-4 gap-6 text-center">
                            <div class="bg-white p-4 rounded-lg shadow-sm">
                                <div class="text-3xl font-bold text-blue-600 mb-2">90</div>
                                <div class="text-sm text-gray-600">Tests Validés</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-sm">
                                <div class="text-3xl font-bold text-green-600 mb-2">15+</div>
                                <div class="text-sm text-gray-600">Fonctionnalités</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-sm">
                                <div class="text-3xl font-bold text-purple-600 mb-2">3</div>
                                <div class="text-sm text-gray-600">Phases Complètes</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow-sm">
                                <div class="text-3xl font-bold text-orange-600 mb-2">100%</div>
                                <div class="text-sm text-gray-600">Production Ready</div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Section -->
                    <div class="text-center bg-gradient-to-r from-purple-50 to-pink-50 p-8 rounded-lg">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">L'Équipe</h3>
                        <p class="text-gray-600 mb-4">
                            Nexa Framework est développé par une équipe passionnée de développeurs PHP 
                            qui croient en la puissance de la simplicité.
                        </p>
                        <div class="flex justify-center space-x-4">
                            <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">N</span>
                            </div>
                            <div class="w-12 h-12 bg-pink-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">E</span>
                            </div>
                            <div class="w-12 h-12 bg-indigo-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">X</span>
                            </div>
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">A</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mt-8">
                    <a href="/" class="px-8 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 font-medium text-center">
                        Retour à l'accueil
                    </a>
                    <a href="/documentation" class="px-8 py-3 border-2 border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors duration-200 font-medium text-center">
                        Documentation
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-indigo-100">
                <p class="text-sm">
                    Créé avec ❤️ par l'équipe Nexa Framework
                </p>
            </div>
        </div>
    </div>
</body>
</html>