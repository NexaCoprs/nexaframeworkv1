# Nexa CLI Tools

Extension VS Code pour l'intégration des commandes Nexa directement dans l'éditeur.

## Fonctionnalités

### 🚀 Génération rapide
- **Handlers** : Générez rapidement des handlers avec `Ctrl+Shift+P` → "Nexa: Générer un Handler"
- **Entités** : Créez des entités de base de données
- **Middlewares** : Générez des middlewares personnalisés

### 🗄️ Gestion de base de données
- **Migrations** : Exécutez, annulez ou réinitialisez les migrations
- **Seeds** : Lancez les seeds pour peupler la base de données

### 💻 Terminal intégré
- Terminal Nexa dédié avec autocomplétion
- Commandes contextuelles dans l'explorateur
- Configuration personnalisable du chemin CLI

## Installation

1. Ouvrez VS Code
2. Allez dans Extensions (Ctrl+Shift+X)
3. Recherchez "Nexa CLI Tools"
4. Cliquez sur Installer

## Configuration

```json
{
  "nexa.cliPath": "./nexa",
  "nexa.autoComplete": true
}
```

## Utilisation

### Commandes disponibles

- `Nexa: Générer un Handler` - Crée un nouveau handler
- `Nexa: Générer une Entité` - Crée une nouvelle entité
- `Nexa: Générer un Middleware` - Crée un nouveau middleware
- `Nexa: Exécuter les Migrations` - Gère les migrations de base de données
- `Nexa: Exécuter les Seeds` - Lance les seeds
- `Nexa: Ouvrir Terminal Nexa` - Ouvre un terminal dédié

### Menu contextuel

Clic droit sur un dossier dans l'explorateur pour accéder aux options de génération.

### Autocomplétion

L'extension fournit une autocomplétion intelligente pour les commandes Nexa dans le terminal.

## Prérequis

- VS Code 1.74.0 ou plus récent
- Projet Nexa Framework
- CLI Nexa installé

## Support

Pour signaler des bugs ou demander des fonctionnalités :
- [GitHub Issues](https://github.com/nexacorps/nexa-cli-tools/issues)

## Licence

MIT