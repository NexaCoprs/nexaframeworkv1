<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - Nexa Framework par Jean Setone</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&family=JetBrains+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --accent-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --dark-gradient: linear-gradient(135deg, #0c0c0c 0%, #1a1a1a 100%);
            --innovation-gradient: linear-gradient(45deg, #1a1a2e, #16213e, #0f3460, #533483, #7209b7, #2d1b69);
        }
        
        * { 
            font-family: 'Inter', sans-serif; 
            scroll-behavior: smooth;
        }
        
        .code-font { font-family: 'JetBrains Mono', monospace; }
        
        .innovation-bg {
            background: var(--innovation-gradient);
        }
        
        .glass-ultra {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(40px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .innovation-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.02));
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .innovation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .innovation-card:hover::before {
            left: 100%;
        }
        
        .innovation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .glow-text {
            background: linear-gradient(45deg, #fff, #f0f9ff, #e0f2fe, #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(255,255,255,0.5);
        }
        
        .innovation-pulse {
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.4), 0 0 40px rgba(118, 75, 162, 0.3);
        }
        
        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            margin: 4rem 0;
        }
        
        .floating-element {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .timeline-item {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 2px;
            height: 100%;
            background: linear-gradient(to bottom, #3B82F6, #8B5CF6);
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -6px;
            top: 1rem;
            width: 14px;
            height: 14px;
            background: #3B82F6;
            border-radius: 50%;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
        
        .tech-stack-item {
            transition: all 0.3s ease;
        }
        
        .tech-stack-item:hover {
            transform: scale(1.1) rotate(5deg);
        }
        
        .parallax-bg {
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
    </style>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="innovation-bg text-white min-h-screen">
    <!-- Scroll Indicator -->
    <div id="scrollIndicator" class="fixed top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-purple-500 z-50 transform scale-x-0 origin-left transition-transform duration-300"></div>
    
    <!-- Fixed Glass Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-40 innovation-card backdrop-blur-md">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-xl font-bold glow-text">NexaFramework</a>
                    <div class="hidden md:flex space-x-6">
                        <a href="/" class="text-white/80 hover:text-white transition-colors">Accueil</a>
                        <a href="/about" class="text-white hover:text-white transition-colors border-b border-white/30">À propos</a>
                        <a href="/documentation" class="text-white/80 hover:text-white transition-colors">Documentation</a>
                        <a href="/contact" class="text-white/80 hover:text-white transition-colors">Contact</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="https://github.com/nexaframework" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="min-h-screen flex items-center justify-center relative overflow-hidden pt-16">
        <!-- Floating Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="floating-element absolute top-20 left-10 w-32 h-32 bg-blue-500/10 rounded-full blur-xl"></div>
            <div class="floating-element absolute top-40 right-20 w-48 h-48 bg-purple-500/10 rounded-full blur-xl" style="animation-delay: -2s;"></div>
            <div class="floating-element absolute bottom-20 left-1/4 w-24 h-24 bg-pink-500/10 rounded-full blur-xl" style="animation-delay: -4s;"></div>
        </div>
        
        <div class="container mx-auto px-6 text-center relative z-10">
            <div class="fade-in">
                <h1 class="text-7xl md:text-8xl font-bold mb-6 glow-text">
                    L'Histoire de
                    <span class="block bg-gradient-to-r from-blue-400 via-purple-500 to-pink-500 bg-clip-text text-transparent">
                        Nexa Framework
                    </span>
                </h1>
                <p class="text-xl md:text-2xl text-white/80 max-w-3xl mx-auto mb-8 leading-relaxed">
                    Découvrez l'aventure extraordinaire qui a donné naissance au framework PHP le plus innovant de sa génération
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#story" class="px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full text-white font-semibold hover:shadow-lg hover:shadow-blue-500/25 transition-all duration-300 transform hover:scale-105">
                        🚀 Découvrir l'Histoire
                    </a>
                    <a href="#team" class="px-8 py-4 border-2 border-white/30 rounded-full text-white font-semibold hover:bg-white/10 transition-all duration-300">
                        👥 Rencontrer l'Équipe
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
    </section>
    <!-- Story Section -->
    <section id="story" class="py-20 px-6">
        <div class="container mx-auto max-w-6xl">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-5xl font-bold mb-6 glow-text">🌟 L'Histoire de Nexa</h2>
                <p class="text-xl text-white/80 max-w-3xl mx-auto">
                    Une aventure technologique née de la passion et de l'innovation
                </p>
            </div>
            
            <!-- Timeline -->
            <div class="relative max-w-4xl mx-auto">
                <div class="timeline-item fade-in mb-12">
                    <div class="innovation-card p-8 rounded-xl">
                        <div class="flex items-center mb-4">
                            <span class="text-3xl mr-4">💡</span>
                            <h3 class="text-2xl font-bold text-white">Début 2025 - L'Idée</h3>
                        </div>
                        <p class="text-white/90 leading-relaxed">
                            Jean Setone, développeur passionné, constate les limitations des frameworks existants. 
                            L'idée de Nexa naît : créer un framework PHP moderne, performant et accessible à tous.
                        </p>
                    </div>
                </div>
                
                <div class="timeline-item fade-in mb-12">
                    <div class="innovation-card p-8 rounded-xl">
                        <div class="flex items-center mb-4">
                            <span class="text-3xl mr-4">🚀</span>
                            <h3 class="text-2xl font-bold text-white">Mars-Mai 2025 - Le Développement</h3>
                        </div>
                        <p class="text-white/90 leading-relaxed">
                            Développement intensif de l'architecture core, intégration de GraphQL, WebSockets, 
                            et création d'un système de modules révolutionnaire. Plus de 90 tests validés.
                        </p>
                    </div>
                </div>
                
                <div class="timeline-item fade-in mb-12">
                    <div class="innovation-card p-8 rounded-xl">
                        <div class="flex items-center mb-4">
                            <span class="text-3xl mr-4">🌟</span>
                            <h3 class="text-2xl font-bold text-white">Juin 2025 - Le Lancement</h3>
                        </div>
                        <p class="text-white/90 leading-relaxed">
                            Lancement officiel de Nexa Framework v1.0. Une révolution dans le développement PHP 
                            avec des fonctionnalités avancées et une simplicité d'utilisation inégalée.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Team Section -->
    <section id="team" class="py-20 px-6 bg-black/20">
        <div class="container mx-auto max-w-6xl">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-5xl font-bold mb-6 glow-text">👥 L'Équipe Nexa</h2>
                <p class="text-xl text-white/80 max-w-3xl mx-auto">
                    Des visionnaires passionnés qui façonnent l'avenir du développement web
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Founder -->
                <div class="innovation-card p-8 rounded-xl text-center fade-in">
                    <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full mx-auto mb-6 flex items-center justify-center text-4xl font-bold text-white">
                        JS
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Jean Setone</h3>
                    <p class="text-blue-400 mb-4">Fondateur & Architecte Principal</p>
                    <p class="text-white/80 text-sm leading-relaxed">
                        Développeur full-stack avec 8+ années d'expérience. Spécialiste en PHP, JavaScript, 
                        et architectures modernes. Visionnaire derrière Nexa Framework.
                    </p>
                    <div class="flex justify-center space-x-4 mt-6">
                        <a href="#" class="text-white/60 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.29 18.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0020 3.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.073 4.073 0 01.8 7.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84"></path>
                            </svg>
                        </a>
                        <a href="#" class="text-white/60 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <!-- Community Members -->
                <div class="innovation-card p-8 rounded-xl text-center fade-in">
                    <div class="w-32 h-32 bg-gradient-to-br from-green-500 to-teal-600 rounded-full mx-auto mb-6 flex items-center justify-center text-4xl">
                        🌍
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Communauté</h3>
                    <p class="text-green-400 mb-4">Contributeurs Actifs</p>
                    <p class="text-white/80 text-sm leading-relaxed">
                        Une communauté grandissante de développeurs passionnés qui contribuent 
                        quotidiennement à l'amélioration de Nexa Framework.
                    </p>
                    <div class="mt-6">
                        <span class="text-2xl font-bold text-green-400">500+</span>
                        <p class="text-white/60 text-sm">Membres actifs</p>
                    </div>
                </div>
                
                <!-- Future Team -->
                <div class="innovation-card p-8 rounded-xl text-center fade-in">
                    <div class="w-32 h-32 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full mx-auto mb-6 flex items-center justify-center text-4xl">
                        🚀
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2">Rejoignez-nous</h3>
                    <p class="text-purple-400 mb-4">Talents Recherchés</p>
                    <p class="text-white/80 text-sm leading-relaxed">
                        Nous recherchons des développeurs talentueux pour rejoindre l'aventure Nexa 
                        et façonner l'avenir du développement web.
                    </p>
                    <a href="/contact" class="inline-block mt-6 px-6 py-2 bg-purple-500 hover:bg-purple-600 rounded-lg text-white font-semibold transition-colors">
                        Postuler
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Technology Stack -->
    <section class="py-20 px-6">
        <div class="container mx-auto max-w-6xl">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-5xl font-bold mb-6 glow-text">⚡ Technologies</h2>
                <p class="text-xl text-white/80 max-w-3xl mx-auto">
                    Les technologies de pointe qui alimentent Nexa Framework
                </p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-8">
                <div class="tech-stack-item innovation-card p-6 rounded-xl text-center fade-in">
                    <div class="text-4xl mb-4">🐘</div>
                    <h4 class="text-white font-semibold">PHP 8.3+</h4>
                </div>
                <div class="tech-stack-item innovation-card p-6 rounded-xl text-center fade-in">
                    <div class="text-4xl mb-4">🔗</div>
                    <h4 class="text-white font-semibold">GraphQL</h4>
                </div>
                <div class="tech-stack-item innovation-card p-6 rounded-xl text-center fade-in">
                    <div class="text-4xl mb-4">⚡</div>
                    <h4 class="text-white font-semibold">WebSockets</h4>
                </div>
                <div class="tech-stack-item innovation-card p-6 rounded-xl text-center fade-in">
                    <div class="text-4xl mb-4">🗄️</div>
                    <h4 class="text-white font-semibold">MySQL</h4>
                </div>
                <div class="tech-stack-item innovation-card p-6 rounded-xl text-center fade-in">
                    <div class="text-4xl mb-4">📦</div>
                    <h4 class="text-white font-semibold">Composer</h4>
                </div>
                <div class="tech-stack-item innovation-card p-6 rounded-xl text-center fade-in">
                    <div class="text-4xl mb-4">🔧</div>
                    <h4 class="text-white font-semibold">Modules</h4>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-20 px-6 bg-black/20">
        <div class="container mx-auto max-w-6xl">
            <div class="text-center mb-16 fade-in">
                <h2 class="text-5xl font-bold mb-6 glow-text">📊 Nexa en Chiffres</h2>
            </div>
            
            <div class="grid md:grid-cols-4 gap-8">
                <div class="innovation-card p-8 rounded-xl text-center fade-in">
                    <div class="text-5xl font-bold text-blue-400 mb-4">90+</div>
                    <h4 class="text-xl font-semibold text-white mb-2">Tests Validés</h4>
                    <p class="text-white/60 text-sm">Qualité garantie</p>
                </div>
                <div class="innovation-card p-8 rounded-xl text-center fade-in">
                    <div class="text-5xl font-bold text-green-400 mb-4">15+</div>
                    <h4 class="text-xl font-semibold text-white mb-2">Fonctionnalités</h4>
                    <p class="text-white/60 text-sm">Écosystème complet</p>
                </div>
                <div class="innovation-card p-8 rounded-xl text-center fade-in">
                    <div class="text-5xl font-bold text-purple-400 mb-4">3</div>
                    <h4 class="text-xl font-semibold text-white mb-2">Phases</h4>
                    <p class="text-white/60 text-sm">Développement structuré</p>
                </div>
                <div class="innovation-card p-8 rounded-xl text-center fade-in">
                    <div class="text-5xl font-bold text-orange-400 mb-4">100%</div>
                    <h4 class="text-xl font-semibold text-white mb-2">Production Ready</h4>
                    <p class="text-white/60 text-sm">Prêt pour vos projets</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="py-20 px-6">
        <div class="container mx-auto max-w-4xl text-center">
            <div class="innovation-card p-12 rounded-2xl fade-in">
                <h2 class="text-4xl font-bold text-white mb-6 glow-text">🚀 Rejoignez la Révolution</h2>
                <p class="text-xl text-white/80 mb-8 leading-relaxed">
                    Découvrez pourquoi des milliers de développeurs choisissent Nexa Framework 
                    pour leurs projets les plus ambitieux.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/documentation" class="px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full text-white font-semibold hover:shadow-lg hover:shadow-blue-500/25 transition-all duration-300 transform hover:scale-105">
                        📚 Documentation
                    </a>
                    <a href="https://github.com/nexaframework" class="px-8 py-4 border-2 border-white/30 rounded-full text-white font-semibold hover:bg-white/10 transition-all duration-300">
                        🐙 GitHub
                    </a>
                    <a href="/contact" class="px-8 py-4 bg-gradient-to-r from-green-500 to-teal-600 rounded-full text-white font-semibold hover:shadow-lg hover:shadow-green-500/25 transition-all duration-300 transform hover:scale-105">
                        💬 Contact
                    </a>
                </div>
            </div>
        </div>
    </section>
    <script>
        // Scroll indicator
        window.addEventListener('scroll', () => {
            const scrolled = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            document.getElementById('scrollIndicator').style.transform = `scaleX(${scrolled / 100})`;
        });
        
        // Fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);
        
        // Observer tous les éléments fade-in
        document.addEventListener('DOMContentLoaded', () => {
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>