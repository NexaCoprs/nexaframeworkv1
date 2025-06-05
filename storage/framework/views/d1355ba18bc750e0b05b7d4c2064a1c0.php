<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Nexa Framework</title>
    <script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <h1 class="text-5xl font-bold text-gray-800 mb-4">Contactez-nous</h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Une question sur Nexa Framework ? Notre équipe est là pour vous aider à réussir vos projets.
                </p>
                <div class="mt-4 inline-flex items-center px-4 py-2 bg-green-100 border border-green-300 rounded-full">
                    <span class="text-green-800 font-medium">🚀 Phase 3 Production-Ready - Support Premium Disponible</span>
                </div>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Contact Form -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                
                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <strong>Erreurs de validation :</strong>
                        <ul class="mt-2 list-disc list-inside">
                            <?php foreach ($errors as $field => $fieldErrors): ?>
                                <?php foreach ($fieldErrors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="/contact" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom complet *
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Adresse email *
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                            Message *
                        </label>
                        <textarea 
                            id="message" 
                            name="message" 
                            rows="6"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            required
                        ><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
                    </div>
                    
                    <div>
                        <button 
                            type="submit" 
                            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200"
                        >
                            Envoyer le message
                        </button>
                    </div>
                </form>
                
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="text-center">
                        <a href="/" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                            ← Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information & Support -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Informations de Contact & Support</h2>
                
                <!-- Contact Methods -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">📞 Nous Contacter</h3>
                        <div class="space-y-3 text-gray-600">
                            <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                                <svg class="w-5 h-5 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span class="font-medium">contact@nexa-framework.com</span>
                            </div>
                            <div class="flex items-center p-3 bg-green-50 rounded-lg">
                                <svg class="w-5 h-5 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <span class="font-medium">+33 1 23 45 67 89</span>
                            </div>
                            <div class="flex items-center p-3 bg-purple-50 rounded-lg">
                                <svg class="w-5 h-5 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="font-medium">123 Rue de la Technologie, 75001 Paris</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">🛠️ Types de Support</h3>
                        <div class="space-y-3">
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="font-medium text-yellow-800">Support Technique</div>
                                <div class="text-sm text-yellow-700">Aide pour l'implémentation et le débogage</div>
                            </div>
                            <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
                                <div class="font-medium text-indigo-800">Consulting</div>
                                <div class="text-sm text-indigo-700">Architecture et optimisation de projets</div>
                            </div>
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="font-medium text-green-800">Formation</div>
                                <div class="text-sm text-green-700">Sessions de formation personnalisées</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">🔗 Liens Utiles</h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <a href="/documentation" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-200">
                            <span class="text-2xl mr-3">📚</span>
                            <div>
                                <div class="font-medium text-blue-800">Documentation</div>
                                <div class="text-sm text-blue-600">Guide complet du framework</div>
                            </div>
                        </a>
                        <a href="/about" class="flex items-center p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition duration-200">
                            <span class="text-2xl mr-3">ℹ️</span>
                            <div>
                                <div class="font-medium text-purple-800">À Propos</div>
                                <div class="text-sm text-purple-600">Découvrir Nexa Framework</div>
                            </div>
                        </a>
                        <a href="/" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-lg transition duration-200">
                            <span class="text-2xl mr-3">🏠</span>
                            <div>
                                <div class="font-medium text-green-800">Accueil</div>
                                <div class="text-sm text-green-600">Retour à la page principale</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            </div>
            
            <!-- Footer -->
            <div class="text-center mt-8 text-gray-600">
                <p class="text-sm">
                    Créé avec ❤️ par l'équipe Nexa Framework - Phase 3 Production Ready
                </p>
            </div>
        </div>
    </div>
</body>
</html>