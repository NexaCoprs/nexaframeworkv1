# Guide de Contribution - Nexa Framework

[![Contributors](https://img.shields.io/github/contributors/nexa-framework/nexa.svg)](https://github.com/nexa-framework/nexa/graphs/contributors)
[![Issues](https://img.shields.io/github/issues/nexa-framework/nexa.svg)](https://github.com/nexa-framework/nexa/issues)
[![Pull Requests](https://img.shields.io/github/issues-pr/nexa-framework/nexa.svg)](https://github.com/nexa-framework/nexa/pulls)
[![Discord](https://img.shields.io/discord/123456789.svg?label=Discord&logo=discord)](https://discord.gg/nexa)

Merci de votre intérêt pour contribuer au Framework Nexa ! Ce guide vous explique comment participer au développement et améliorer le framework ensemble.

## 📋 Table des Matières

1. [🤝 Code de Conduite](#-code-de-conduite)
2. [🚀 Comment Contribuer](#-comment-contribuer)
3. [⚙️ Configuration de l'Environnement](#️-configuration-de-lenvironnement)
4. [📏 Standards de Code](#-standards-de-code)
5. [🔄 Processus de Pull Request](#-processus-de-pull-request)
6. [🐛 Rapporter des Bugs](#-rapporter-des-bugs)
7. [✨ Proposer des Fonctionnalités](#-proposer-des-fonctionnalités)
8. [📚 Documentation](#-documentation)
9. [🧪 Tests](#-tests)
10. [👥 Communauté](#-communauté)
11. [🛠️ Extensions VSCode](#️-extensions-vscode)
12. [🏆 Reconnaissance](#-reconnaissance)

---

## Code de Conduite

En participant à ce projet, vous acceptez de respecter notre [Code de Conduite](CODE_OF_CONDUCT.md). Nous nous engageons à maintenir un environnement accueillant et inclusif pour tous.

### Nos Valeurs

- **Respect** : Traitez tous les contributeurs avec respect et courtoisie
- **Collaboration** : Travaillons ensemble pour améliorer le framework
- **Qualité** : Privilégions la qualité du code et de la documentation
- **Innovation** : Encourageons les idées nouvelles et créatives
- **Apprentissage** : Aidons-nous mutuellement à apprendre et grandir

---

## Comment Contribuer

Il existe plusieurs façons de contribuer au Framework Nexa :

### 🐛 Rapporter des Bugs
- Signalez les problèmes que vous rencontrez
- Fournissez des informations détaillées pour reproduire le bug
- Proposez des solutions si vous en avez

### ✨ Proposer des Fonctionnalités
- Suggérez de nouvelles fonctionnalités
- Discutez de l'implémentation avec la communauté
- Créez des RFC (Request for Comments) pour les changements majeurs

### 💻 Contribuer au Code
- Corrigez des bugs
- Implémentez de nouvelles fonctionnalités
- Améliorez les performances
- Refactorisez le code existant

### 📚 Améliorer la Documentation
- Corrigez les erreurs dans la documentation
- Ajoutez des exemples et tutoriels
- Traduisez la documentation
- Créez des guides d'utilisation

### 🧪 Écrire des Tests
- Ajoutez des tests pour les fonctionnalités existantes
- Améliorez la couverture de tests
- Créez des tests d'intégration

### 🎨 Améliorer l'UX/UI
- Améliorez l'interface des outils de développement
- Créez des templates et exemples
- Optimisez l'expérience développeur

---

## Configuration de l'Environnement

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- Git
- Node.js (pour les outils de build)
- Base de données (MySQL, PostgreSQL, SQLite)

### Installation

```bash
# 1. Forker le repository sur GitHub
# 2. Cloner votre fork
git clone https://github.com/VOTRE-USERNAME/nexa-framework.git
cd nexa-framework

# 3. Ajouter le repository original comme remote
git remote add upstream https://github.com/nexa-framework/nexa-framework.git

# 4. Installer les dépendances
composer install
npm install

# 5. Copier le fichier de configuration
cp .env.example .env.testing

# 6. Configurer la base de données de test
# Éditez .env.testing avec vos paramètres de test

# 7. Exécuter les tests pour vérifier l'installation
php vendor/bin/phpunit
```

### Structure du Projet

```
nexa-framework/
├── src/Nexa/              # Code source du framework
│   ├── Auth/              # Système d'authentification
│   ├── Database/          # ORM et migrations
│   ├── Http/              # Requêtes, réponses, routing
│   ├── Validation/        # Système de validation
│   └── ...
├── tests/                 # Tests unitaires et d'intégration
├── docs/                  # Documentation
├── examples/              # Exemples d'utilisation
├── tools/                 # Outils de développement
└── public/                # Point d'entrée web
```

---

## Standards de Code

### Style de Code

Nous suivons les standards PSR-12 avec quelques adaptations :

```php
<?php

namespace Nexa\Http;

use Nexa\Contracts\RequestInterface;
use Nexa\Validation\Validator;

/**
 * Classe de gestion des requêtes HTTP.
 */
class Request implements RequestInterface
{
    /**
     * Données de la requête.
     */
    private array $data = [];
    
    /**
     * Constructeur.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
    
    /**
     * Obtient une valeur de la requête.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Valide les données de la requête.
     */
    public function validate(array $rules): array
    {
        $validator = new Validator($this->data, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
        
        return $validator->validated();
    }
}
```

### Conventions de Nommage

- **Classes** : PascalCase (`UserController`, `PaymentService`)
- **Méthodes** : camelCase (`getUserById`, `processPayment`)
- **Variables** : camelCase (`$userId`, `$paymentData`)
- **Constantes** : SCREAMING_SNAKE_CASE (`MAX_RETRY_ATTEMPTS`)
- **Fichiers** : snake_case (`user_controller.php`, `payment_service.php`)

### Documentation du Code

```php
/**
 * Service de gestion des paiements.
 * 
 * Ce service gère le traitement des paiements via différents
 * processeurs (Stripe, PayPal, etc.).
 * 
 * @package Nexa\Payment
 * @author Votre Nom <email@example.com>
 * @since 1.0.0
 */
class PaymentService
{
    /**
     * Traite un paiement.
     * 
     * @param Payment $payment Le paiement à traiter
     * @param string $processor Le processeur à utiliser
     * 
     * @return PaymentResult Le résultat du traitement
     * 
     * @throws PaymentException Si le traitement échoue
     * @throws InvalidProcessorException Si le processeur est invalide
     */
    public function process(Payment $payment, string $processor): PaymentResult
    {
        // Implémentation...
    }
}
```

### Outils de Qualité

```bash
# Vérification du style de code
composer run cs-check

# Correction automatique du style
composer run cs-fix

# Analyse statique avec PHPStan
composer run analyze

# Exécution de tous les tests
composer run test

# Tests avec couverture
composer run test-coverage
```

---

## Processus de Pull Request

### 1. Préparation

```bash
# Synchroniser avec le repository principal
git checkout main
git pull upstream main

# Créer une nouvelle branche
git checkout -b feature/nom-de-la-fonctionnalite
# ou
git checkout -b fix/description-du-bug
```

### 2. Développement

- Écrivez du code propre et bien documenté
- Ajoutez des tests pour vos modifications
- Respectez les standards de code
- Commitez régulièrement avec des messages clairs

```bash
# Messages de commit conventionnels
git commit -m "feat: ajouter support pour les webhooks Stripe"
git commit -m "fix: corriger la validation des emails"
git commit -m "docs: mettre à jour le guide d'installation"
git commit -m "test: ajouter tests pour UserService"
```

### 3. Tests et Vérifications

```bash
# Exécuter tous les tests
composer run test

# Vérifier le style de code
composer run cs-check

# Analyse statique
composer run analyze

# Tests de performance (si applicable)
composer run benchmark
```

### 4. Soumission

```bash
# Pousser la branche
git push origin feature/nom-de-la-fonctionnalite

# Créer une Pull Request sur GitHub
```

### Template de Pull Request

```markdown
## Description

Brève description des changements apportés.

## Type de Changement

- [ ] Bug fix (changement non-breaking qui corrige un problème)
- [ ] Nouvelle fonctionnalité (changement non-breaking qui ajoute une fonctionnalité)
- [ ] Breaking change (correction ou fonctionnalité qui casserait la compatibilité)
- [ ] Documentation (changements dans la documentation uniquement)

## Tests

- [ ] J'ai ajouté des tests qui prouvent que ma correction est efficace ou que ma fonctionnalité fonctionne
- [ ] Les tests nouveaux et existants passent localement avec mes changements
- [ ] J'ai vérifié que ma modification n'introduit pas de régression

## Checklist

- [ ] Mon code suit les standards de style du projet
- [ ] J'ai effectué une auto-review de mon code
- [ ] J'ai commenté mon code, particulièrement dans les zones difficiles à comprendre
- [ ] J'ai apporté les changements correspondants à la documentation
- [ ] Mes changements ne génèrent aucun nouveau warning
- [ ] J'ai ajouté des tests qui prouvent que ma correction est efficace ou que ma fonctionnalité fonctionne

## Screenshots (si applicable)

## Notes Supplémentaires

Toute information supplémentaire utile pour les reviewers.
```

---

## Rapporter des Bugs

### Avant de Rapporter

1. **Vérifiez** si le bug n'a pas déjà été rapporté
2. **Testez** avec la dernière version du framework
3. **Reproduisez** le bug de manière consistante

### Template de Bug Report

```markdown
**Description du Bug**
Description claire et concise du problème.

**Étapes pour Reproduire**
1. Aller à '...'
2. Cliquer sur '....'
3. Faire défiler jusqu'à '....'
4. Voir l'erreur

**Comportement Attendu**
Description claire de ce qui devrait se passer.

**Comportement Actuel**
Description claire de ce qui se passe actuellement.

**Screenshots**
Si applicable, ajoutez des screenshots pour expliquer le problème.

**Environnement**
- OS: [ex. Windows 11, macOS 12, Ubuntu 20.04]
- PHP Version: [ex. 8.1.0]
- Framework Version: [ex. 1.2.3]
- Serveur Web: [ex. Apache 2.4, Nginx 1.18]

**Code d'Exemple**
```php
// Code minimal pour reproduire le bug
```

**Logs d'Erreur**
```
// Coller les logs d'erreur ici
```

**Contexte Supplémentaire**
Toute autre information utile sur le problème.
```

---

## Proposer des Fonctionnalités

### RFC (Request for Comments)

Pour les changements majeurs, créez un RFC :

```markdown
# RFC: Nom de la Fonctionnalité

## Résumé

Brève description de la fonctionnalité proposée.

## Motivation

Pourquoi cette fonctionnalité est-elle nécessaire ?
Quels problèmes résout-elle ?

## Guide Détaillé

### API Proposée

```php
// Exemples d'utilisation de la nouvelle API
```

### Implémentation

Comment cette fonctionnalité sera-t-elle implémentée ?

### Migration

Comment les utilisateurs existants migreront-ils ?

## Inconvénients

Quels sont les inconvénients potentiels ?

## Alternatives

Quelles alternatives ont été considérées ?

## Questions Non Résolues

Quelles questions restent ouvertes ?
```

---

## Documentation

### Types de Documentation

1. **API Documentation** : Documentation technique des classes et méthodes
2. **Guides d'Utilisation** : Tutoriels et exemples pratiques
3. **Architecture** : Documentation de l'architecture interne
4. **Migration** : Guides de migration entre versions

### Standards de Documentation

```markdown
# Titre Principal

## Introduction

Brève introduction au sujet.

## Installation

```bash
# Commandes d'installation
```

## Utilisation Basique

```php
// Exemple de code simple
```

## Utilisation Avancée

### Sous-section

Explication détaillée avec exemples.

```php
// Exemple de code avancé
```

## API Reference

### Classe `ExampleClass`

#### Méthodes

##### `method()`

**Description** : Description de la méthode.

**Paramètres** :
- `$param1` (string) : Description du paramètre
- `$param2` (int, optionnel) : Description du paramètre optionnel

**Retour** : Type de retour et description

**Exemple** :
```php
$result = $instance->method('value', 42);
```

## Voir Aussi

- [Lien vers documentation connexe](./autre-doc.md)
- [Lien externe](https://example.com)
```

---

## Tests

### Types de Tests

1. **Tests Unitaires** : Testent des unités isolées de code
2. **Tests d'Intégration** : Testent l'interaction entre composants
3. **Tests Fonctionnels** : Testent des fonctionnalités complètes
4. **Tests de Performance** : Mesurent les performances

### Structure des Tests

```php
<?php

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Nexa\Http\Request;
use Nexa\Validation\ValidationException;

class RequestTest extends TestCase
{
    private Request $request;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request(['name' => 'John', 'email' => 'john@example.com']);
    }
    
    /** @test */
    public function it_can_get_input_value(): void
    {
        // Arrange
        $key = 'name';
        $expected = 'John';
        
        // Act
        $result = $this->request->input($key);
        
        // Assert
        $this->assertEquals($expected, $result);
    }
    
    /** @test */
    public function it_returns_default_value_for_missing_input(): void
    {
        // Arrange
        $key = 'missing';
        $default = 'default_value';
        
        // Act
        $result = $this->request->input($key, $default);
        
        // Assert
        $this->assertEquals($default, $result);
    }
    
    /** @test */
    public function it_validates_input_successfully(): void
    {
        // Arrange
        $rules = ['name' => 'required|string', 'email' => 'required|email'];
        
        // Act
        $result = $this->request->validate($rules);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('John', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }
    
    /** @test */
    public function it_throws_exception_for_invalid_input(): void
    {
        // Arrange
        $request = new Request(['email' => 'invalid-email']);
        $rules = ['email' => 'required|email'];
        
        // Act & Assert
        $this->expectException(ValidationException::class);
        $request->validate($rules);
    }
}
```

### Commandes de Test

```bash
# Tous les tests
php vendor/bin/phpunit

# Tests spécifiques
php vendor/bin/phpunit tests/Unit/Http/RequestTest.php

# Tests avec couverture
php vendor/bin/phpunit --coverage-html coverage

# Tests de performance
php vendor/bin/phpunit tests/Performance/

# Tests d'intégration
php vendor/bin/phpunit tests/Integration/
```

---

## Communauté

### 💬 Discord
- **Serveur principal** : [discord.gg/nexa](https://discord.gg/nexa)
- **Canaux disponibles** :
  - `#general` : Discussions générales
  - `#help` : Aide et support
  - `#development` : Développement du framework
  - `#showcase` : Montrez vos projets
  - `#feedback` : Retours et suggestions
  - `#extensions` : Développement d'extensions VSCode
  - `#templates-nx` : Discussion sur les templates .nx

### 🌐 Forums et Plateformes
- **Forum officiel** : [forum.nexa-framework.com](https://forum.nexa-framework.com)
- **Reddit** : [r/NexaFramework](https://reddit.com/r/NexaFramework)
- **Stack Overflow** : Tag `nexa-framework`
- **GitHub Discussions** : [Discussions](https://github.com/nexa-framework/nexa/discussions)

### 📱 Réseaux Sociaux
- **Twitter** : [@NexaFramework](https://twitter.com/NexaFramework)
- **LinkedIn** : [Nexa Framework](https://linkedin.com/company/nexa-framework)
- **YouTube** : [Chaîne officielle](https://youtube.com/NexaFramework)
- **Dev.to** : [Nexa Framework](https://dev.to/nexa-framework)

### 🎉 Événements
- **Meetups mensuels** : Rencontres en ligne
- **Conférences** : Présentations aux événements PHP
- **Hackathons** : Événements de développement
- **Webinaires** : Sessions de formation
- **Workshops** : Ateliers pratiques sur les extensions VSCode

---

## 🛠️ Extensions VSCode

### Contribuer aux Extensions

Nous encourageons les contributions aux extensions VSCode de Nexa :

#### Structure des Extensions
```
vscode-nexa-[nom-extension]/
├── src/
│   ├── extension.ts          # Point d'entrée principal
│   ├── [modules].ts          # Modules spécifiques
│   └── ...
├── package.json              # Configuration de l'extension
├── tsconfig.json            # Configuration TypeScript
└── README.md                # Documentation
```

#### Guidelines pour les Extensions

1. **Nommage** :
   - Préfixe : `vscode-nexa-`
   - Nom descriptif : `vscode-nexa-code-snippets-pro`

2. **Structure du Code** :
   - TypeScript obligatoire
   - Architecture modulaire
   - Tests unitaires
   - Documentation complète

3. **Fonctionnalités Communes** :
   - Support des fichiers `.nx`
   - Intégration avec l'écosystème Nexa
   - Interface utilisateur cohérente
   - Performance optimisée

#### Processus de Développement

1. **Fork** le repository de l'extension
2. **Créer** une branche feature
3. **Développer** en suivant les guidelines
4. **Tester** avec des projets Nexa réels
5. **Documenter** les nouvelles fonctionnalités
6. **Soumettre** une pull request

#### Extensions Prioritaires

Nous recherchons des contributeurs pour :
- **Nexa Debugger** : Débogueur intégré
- **Nexa Profiler** : Profileur de performance
- **Nexa Deployment** : Outils de déploiement
- **Nexa Assistant** : Assistant pour le développement

### Tests des Extensions

```bash
# Installation des dépendances
npm install

# Compilation
npm run compile

# Tests
npm test

# Package de l'extension
vsce package
```

---

## 🏆 Reconnaissance

### 🌟 Système de Badges

Nous reconnaissons les contributions avec un système de badges :

- 🥇 **Gold Contributor** : 50+ contributions significatives
- 🥈 **Silver Contributor** : 20+ contributions
- 🥉 **Bronze Contributor** : 5+ contributions
- 🐛 **Bug Hunter** : Rapports de bugs de qualité
- 📚 **Documentation Master** : Contributions à la documentation
- 🧪 **Test Champion** : Contributions aux tests
- 🎨 **UI/UX Expert** : Améliorations d'interface
- 🔧 **Extension Developer** : Développement d'extensions VSCode
- 🚀 **Performance Optimizer** : Optimisations de performance
- 🔒 **Security Expert** : Contributions sécuritaires

### 🎖️ Hall of Fame

#### Core Team
- **Lead Developer** : Architecture et vision
- **Core Maintainers** : Maintenance quotidienne
- **Extension Leads** : Responsables des extensions VSCode
- **Community Managers** : Animation de la communauté

#### Top Contributors
- **Monthly MVP** : Contributeur du mois
- **Yearly Champion** : Contributeur de l'année
- **Innovation Award** : Idées innovantes
- **Mentorship Award** : Aide aux nouveaux contributeurs

### 💝 Avantages pour les Contributeurs

- **Accès anticipé** aux nouvelles fonctionnalités
- **Merchandise exclusif** Nexa Framework
- **Invitations** aux événements privés
- **Mentions** dans les release notes
- **Certificats** de contribution
- **Recommandations LinkedIn** de l'équipe core

### 🎁 Sponsors et Partenaires

Merci à nos sponsors qui soutiennent le développement :

#### 🏢 Sponsors Entreprise
- **Platinum** : [Sponsor 1](https://sponsor1.com)
- **Gold** : [Sponsor 2](https://sponsor2.com)
- **Silver** : [Sponsor 3](https://sponsor3.com)

#### 👤 Sponsors Individuels
- **GitHub Sponsors** : [Soutenez-nous](https://github.com/sponsors/nexa-framework)
- **Open Collective** : [Contributions](https://opencollective.com/nexa-framework)
- **Patreon** : [Abonnements mensuels](https://patreon.com/nexa-framework)

---

## 📞 Contact

### 📧 Équipe Core
- **General** : [hello@nexa-framework.com](mailto:hello@nexa-framework.com)
- **Security** : [security@nexa-framework.com](mailto:security@nexa-framework.com)
- **Partnerships** : [partnerships@nexa-framework.com](mailto:partnerships@nexa-framework.com)
- **Press** : [press@nexa-framework.com](mailto:press@nexa-framework.com)

### 🆘 Support
- **Community Support** : Discord, Forum, GitHub Issues
- **Priority Support** : Pour les sponsors et contributeurs actifs
- **Enterprise Support** : [Contactez-nous](mailto:enterprise@nexa-framework.com)

---

## Ressources Utiles

### Documentation

- [Guide de Démarrage Rapide](./docs/QUICK_START.md)
- [Documentation API](./docs/API_DOCUMENTATION.md)
- [Meilleures Pratiques](./docs/BEST_PRACTICES.md)
- [Tutoriels](./docs/TUTORIALS.md)

### Outils

- [PHPStan](https://phpstan.org/) : Analyse statique
- [PHP CS Fixer](https://cs.symfony.com/) : Formatage du code
- [PHPUnit](https://phpunit.de/) : Tests unitaires
- [Composer](https://getcomposer.org/) : Gestionnaire de dépendances

### Liens Externes

- [PSR-12](https://www.php-fig.org/psr/psr-12/) : Standard de style de code
- [Semantic Versioning](https://semver.org/) : Versioning sémantique
- [Conventional Commits](https://www.conventionalcommits.org/) : Format des commits

---

## Questions Fréquentes

### Comment puis-je commencer à contribuer ?

1. Lisez ce guide de contribution
2. Configurez votre environnement de développement
3. Regardez les issues étiquetées "good first issue"
4. Rejoignez notre Discord pour poser des questions

### Combien de temps faut-il pour qu'une PR soit reviewée ?

Nous nous efforçons de reviewer les PRs dans les 48-72 heures. Les PRs complexes peuvent prendre plus de temps.

### Puis-je proposer des changements breaking ?

Oui, mais ils doivent être discutés via un RFC et planifiés pour une version majeure.

### Comment puis-je devenir mainteneur ?

Les mainteneurs sont choisis parmi les contributeurs actifs qui ont démontré leur expertise et leur engagement envers le projet.

---

## Remerciements

Merci à tous les contributeurs qui rendent ce projet possible ! Votre temps, vos idées et votre passion font de Nexa un framework meilleur chaque jour.

**Ensemble, construisons l'avenir du développement PHP !** 🚀

*"La meilleure façon de prédire l'avenir est de le créer."* - Équipe Nexa Framework