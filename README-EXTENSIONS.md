# Extensions VS Code pour Nexa Framework

Ce projet contient une suite complète de 12 extensions VS Code spécialement conçues pour améliorer l'expérience de développement avec le framework Nexa.

## 📦 Extensions Disponibles

### 1. **vscode-nexa-cli-tools**
- **Description** : Outils en ligne de commande intégrés pour Nexa
- **Fonctionnalités** :
  - Gestion des commandes Nexa depuis VS Code
  - Terminal intégré avec auto-complétion
  - Scaffolding de projets avec templates
  - Gestion des migrations et seeds
  - Serveur de développement intégré

### 2. **vscode-nexa-theme-designer**
- **Description** : Concepteur de thèmes visuels pour applications Nexa
- **Fonctionnalités** :
  - Création de thèmes personnalisés
  - Générateur basé sur templates et palettes
  - Prévisualisation en temps réel
  - Export vers différents formats
  - Gestion des couleurs et typographies

### 3. **vscode-nexa-test-runner**
- **Description** : Exécuteur de tests intégré avec couverture
- **Fonctionnalités** :
  - Exécution de tests unitaires et d'intégration
  - Analyse de couverture de code
  - Génération automatique de tests
  - Rapports détaillés
  - Génération de mocks et fixtures

### 4. **vscode-nexa-code-snippets-pro**
- **Description** : Snippets de code intelligents et contextuels
- **Fonctionnalités** :
  - Snippets pour Handlers, Entities, Middleware
  - Auto-complétion intelligente
  - Génération contextuelle de code
  - Support WebSocket et GraphQL
  - Templates de tests et validation

### 5. **vscode-nexa-security-scanner**
- **Description** : Scanner de sécurité pour code Nexa
- **Fonctionnalités** :
  - Analyse de vulnérabilités
  - Scan de fichiers et projets
  - Rapports de sécurité détaillés
  - Suggestions de corrections
  - Intégration continue

### 6. **vscode-nexa-project-generator**
- **Description** : Générateur de projets et composants
- **Fonctionnalités** :
  - Création de projets complets
  - Scaffolding de modules
  - Configuration Docker et CI/CD
  - Génération d'APIs et CRUD
  - Support microservices

### 7. **vscode-nexa-database-manager**
- **Description** : Gestionnaire de base de données intégré
- **Fonctionnalités** :
  - Gestion des migrations
  - Création d'entités
  - Interface de requêtes
  - Visualisation des schémas
  - Synchronisation des modèles

### 8. **vscode-nexa-component-library**
- **Description** : Bibliothèque de composants réutilisables
- **Fonctionnalités** :
  - Catalogue de composants
  - Création de composants personnalisés
  - Documentation intégrée
  - Prévisualisation en direct
  - Gestion des versions

### 9. **vscode-nexa-performance-monitor**
- **Description** : Moniteur de performance et optimisation
- **Fonctionnalités** :
  - Analyse des performances
  - Détection de goulots d'étranglement
  - Suggestions d'optimisation
  - Métriques en temps réel
  - Rapports de performance

### 10. **vscode-nexa-graphql-studio**
- **Description** : Studio GraphQL intégré
- **Fonctionnalités** :
  - Éditeur de schémas GraphQL
  - Playground intégré
  - Génération de resolvers
  - Documentation automatique
  - Tests de requêtes

### 11. **vscode-nx-extension**
- **Description** : Extension pour intégration Nx avec Nexa
- **Fonctionnalités** :
  - Gestion des workspaces Nx
  - Génération de composants
  - Intégration avec Nexa CLI
  - Support monorepo
  - Outils de build optimisés

### 12. **vscode-nexa-microservices-manager**
- **Description** : Gestionnaire de microservices
- **Fonctionnalités** :
  - Orchestration de microservices
  - Communication inter-services
  - Monitoring distribué
  - Configuration centralisée
  - Déploiement automatisé

## 🚀 Installation

### Installation Globale
```bash
# Installer toutes les extensions
npm install -g @nexa/vscode-extensions

# Ou installer individuellement
code --install-extension nexa.cli-tools
code --install-extension nexa.theme-designer
# ... etc
```

### Installation depuis le Code Source
```bash
# Cloner le repository
git clone <repository-url>
cd nexaframeworkv1

# Installer les dépendances pour chaque extension
for dir in vscode-nexa-*/; do
  cd "$dir"
  npm install
  npm run compile
  cd ..
done
```

## 🛠️ Développement

### Prérequis
- Node.js 16+
- VS Code 1.60+
- TypeScript 4.5+

### Structure du Projet
```
nexaframeworkv1/
├── vscode-nexa-cli-tools/
│   ├── src/
│   ├── package.json
│   └── tsconfig.json
├── vscode-nexa-theme-designer/
├── vscode-nexa-test-runner/
├── vscode-nexa-code-snippets-pro/
├── vscode-nexa-security-scanner/
├── vscode-nexa-project-generator/
├── vscode-nexa-database-manager/
├── vscode-nexa-component-library/
├── vscode-nexa-performance-monitor/
├── vscode-nexa-graphql-studio/
├── vscode-nx-extension/
└── vscode-nexa-microservices-manager/
```

### Compilation
```bash
# Compiler une extension spécifique
cd vscode-nexa-cli-tools
npm run compile

# Compiler toutes les extensions
npm run compile:all
```

### Tests
```bash
# Tester une extension
cd vscode-nexa-test-runner
npm test

# Tester toutes les extensions
npm run test:all
```

## 📋 Fonctionnalités Principales

### 🎨 Interface Utilisateur
- Interface intuitive et moderne
- Thèmes personnalisables
- Raccourcis clavier optimisés
- Panneaux contextuels

### ⚡ Performance
- Chargement rapide des extensions
- Optimisation mémoire
- Cache intelligent
- Traitement asynchrone

### 🔧 Intégration
- Intégration native avec Nexa Framework
- Support des workspaces multi-projets
- Synchronisation avec Git
- CI/CD intégré

### 🛡️ Sécurité
- Analyse de vulnérabilités
- Validation de code
- Chiffrement des données sensibles
- Audit de sécurité

## 🎯 Utilisation

### Démarrage Rapide
1. Installer les extensions
2. Ouvrir un projet Nexa
3. Utiliser `Ctrl+Shift+P` pour accéder aux commandes
4. Commencer à développer avec les outils intégrés

### Commandes Principales
- `Nexa: Create New Project` - Créer un nouveau projet
- `Nexa: Generate Handler` - Générer un handler
- `Nexa: Run Tests` - Exécuter les tests
- `Nexa: Start Dev Server` - Démarrer le serveur de développement
- `Nexa: Security Scan` - Scanner la sécurité

## 🤝 Contribution

### Guidelines
1. Fork le repository
2. Créer une branche feature
3. Implémenter les changements
4. Ajouter des tests
5. Soumettre une Pull Request

### Standards de Code
- TypeScript strict mode
- ESLint + Prettier
- Tests unitaires obligatoires
- Documentation JSDoc

## 📄 Licence

MIT License - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🆘 Support

- **Documentation** : [docs.nexa-framework.com](https://docs.nexa-framework.com)
- **Issues** : [GitHub Issues](https://github.com/nexa-framework/vscode-extensions/issues)
- **Discord** : [Communauté Nexa](https://discord.gg/nexa)
- **Email** : support@nexa-framework.com

## 🔄 Changelog

### v1.0.0 (2024-01-15)
- ✨ Version initiale avec 12 extensions
- 🎨 Interface utilisateur moderne
- ⚡ Performance optimisée
- 🛡️ Sécurité renforcée
- 📚 Documentation complète

---

**Développé avec ❤️ pour la communauté Nexa Framework**