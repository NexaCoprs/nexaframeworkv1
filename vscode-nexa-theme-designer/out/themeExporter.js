"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ThemeExporter = void 0;
const vscode = require("vscode");
const fs = require("fs");
const path = require("path");
class ThemeExporter {
    constructor() {
        this.outputChannel = vscode.window.createOutputChannel('Nexa Theme Exporter');
    }
    async exportTheme(theme, options) {
        switch (options.format) {
            case 'json':
                return this.exportAsJSON(theme, options);
            case 'vsix':
                return this.exportAsVSIX(theme, options);
            case 'css':
            case 'scss':
            case 'less':
                return this.exportAsCSS(theme, {
                    includeVariables: true,
                    prefix: 'nexa',
                    format: options.format,
                    includeComments: true
                }, options.outputPath);
            default:
                throw new Error(`Format d'export non supporté: ${options.format}`);
        }
    }
    async exportAsJSON(theme, options) {
        const outputData = options.includeMetadata ? {
            metadata: {
                name: theme.name,
                type: theme.type,
                version: '1.0.0',
                author: 'Nexa Theme Designer',
                created: new Date().toISOString(),
                description: `Thème ${theme.name} généré par Nexa Theme Designer`
            },
            theme
        } : theme;
        const jsonContent = options.minify
            ? JSON.stringify(outputData)
            : JSON.stringify(outputData, null, 2);
        const outputPath = options.outputPath || await this.getDefaultOutputPath(theme.name, 'json');
        await fs.promises.writeFile(outputPath, jsonContent, 'utf8');
        this.outputChannel.appendLine(`Thème exporté en JSON: ${outputPath}`);
        return outputPath;
    }
    async exportAsVSIX(theme, options, vsixOptions) {
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (!workspaceFolders) {
            throw new Error('Aucun workspace ouvert pour créer le package VSIX');
        }
        const tempDir = path.join(workspaceFolders[0].uri.fsPath, '.temp-theme-export');
        const packageName = `nexa-theme-${theme.name.toLowerCase().replace(/\s+/g, '-')}`;
        const packageDir = path.join(tempDir, packageName);
        try {
            // Create temporary directory structure
            await this.ensureDirectoryExists(packageDir);
            await this.ensureDirectoryExists(path.join(packageDir, 'themes'));
            // Create package.json
            const packageJson = {
                name: packageName,
                displayName: `Nexa Theme: ${theme.name}`,
                description: vsixOptions?.description || `Thème ${theme.name} généré par Nexa Theme Designer`,
                version: vsixOptions?.version || '1.0.0',
                publisher: vsixOptions?.publisher || 'nexa',
                engines: {
                    vscode: '^1.74.0'
                },
                categories: ['Themes'],
                keywords: vsixOptions?.keywords || ['theme', 'color-theme', 'nexa'],
                contributes: {
                    themes: [
                        {
                            label: theme.name,
                            uiTheme: this.getUITheme(theme.type),
                            path: './themes/theme.json'
                        }
                    ]
                },
                repository: vsixOptions?.repository ? {
                    type: 'git',
                    url: vsixOptions.repository
                } : undefined,
                license: vsixOptions?.license || 'MIT',
                icon: vsixOptions?.icon
            };
            await fs.promises.writeFile(path.join(packageDir, 'package.json'), JSON.stringify(packageJson, null, 2), 'utf8');
            // Save theme file
            await fs.promises.writeFile(path.join(packageDir, 'themes', 'theme.json'), JSON.stringify(theme, null, 2), 'utf8');
            // Create README.md
            await this.createReadme(packageDir, theme, packageJson);
            // Create CHANGELOG.md
            await this.createChangelog(packageDir, theme, packageJson.version);
            // Try to package with vsce if available
            const outputPath = options.outputPath || await this.getDefaultOutputPath(theme.name, 'vsix');
            try {
                await this.packageWithVSCE(packageDir, outputPath);
            }
            catch (error) {
                // If vsce is not available, just copy the directory
                const fallbackPath = path.join(path.dirname(outputPath), packageName);
                await this.copyDirectory(packageDir, fallbackPath);
                this.outputChannel.appendLine(`VSCE non disponible. Package créé dans: ${fallbackPath}`);
                this.outputChannel.appendLine('Pour créer un fichier .vsix, installez vsce: npm install -g vsce');
                return fallbackPath;
            }
            this.outputChannel.appendLine(`Package VSIX créé: ${outputPath}`);
            return outputPath;
        }
        finally {
            // Clean up temporary directory
            try {
                await this.removeDirectory(tempDir);
            }
            catch (error) {
                this.outputChannel.appendLine(`Erreur lors du nettoyage: ${error}`);
            }
        }
    }
    async exportAsCSS(theme, cssOptions, outputPath) {
        const colors = theme.colors;
        const format = cssOptions.format;
        const prefix = cssOptions.prefix;
        let content = '';
        if (cssOptions.includeComments) {
            content += `/*\n * ${theme.name} Theme\n * Généré par Nexa Theme Designer\n * Type: ${theme.type}\n */\n\n`;
        }
        if (cssOptions.includeVariables) {
            content += this.generateCSSVariables(colors, format, prefix, cssOptions.includeComments);
        }
        content += this.generateCSSClasses(colors, format, prefix, cssOptions.includeComments);
        const extension = format === 'scss' ? 'scss' : format === 'less' ? 'less' : 'css';
        const finalOutputPath = outputPath || await this.getDefaultOutputPath(theme.name, extension);
        await fs.promises.writeFile(finalOutputPath, content, 'utf8');
        this.outputChannel.appendLine(`Thème exporté en ${format.toUpperCase()}: ${finalOutputPath}`);
        return finalOutputPath;
    }
    async exportMultipleFormats(theme, formats, baseOutputPath) {
        const results = [];
        for (const options of formats) {
            try {
                if (baseOutputPath) {
                    const extension = this.getExtensionForFormat(options.format);
                    options.outputPath = path.join(baseOutputPath, `${theme.name.toLowerCase().replace(/\s+/g, '-')}.${extension}`);
                }
                const result = await this.exportTheme(theme, options);
                results.push(result);
            }
            catch (error) {
                this.outputChannel.appendLine(`Erreur lors de l'export en ${options.format}: ${error}`);
            }
        }
        return results;
    }
    async createThemeBundle(themes, bundleName, outputPath) {
        const bundle = {
            name: bundleName,
            version: '1.0.0',
            description: `Bundle de thèmes ${bundleName} créé par Nexa Theme Designer`,
            created: new Date().toISOString(),
            themes: themes.map(theme => ({
                id: theme.name.toLowerCase().replace(/\s+/g, '-'),
                name: theme.name,
                type: theme.type,
                theme
            }))
        };
        const bundleContent = JSON.stringify(bundle, null, 2);
        const finalOutputPath = outputPath || await this.getDefaultOutputPath(bundleName, 'json');
        await fs.promises.writeFile(finalOutputPath, bundleContent, 'utf8');
        this.outputChannel.appendLine(`Bundle de thèmes créé: ${finalOutputPath}`);
        return finalOutputPath;
    }
    generateCSSVariables(colors, format, prefix, includeComments) {
        let content = '';
        const variablePrefix = format === 'less' ? '@' : format === 'scss' ? '$' : '--';
        const rootSelector = format === 'css' ? ':root {\n' : '';
        if (includeComments) {
            content += `/* Variables de couleurs */\n`;
        }
        if (format === 'css') {
            content += rootSelector;
        }
        for (const [key, value] of Object.entries(colors)) {
            if (typeof value === 'string') {
                const variableName = `${variablePrefix}${prefix}-${key.replace(/\./g, '-')}`;
                const declaration = format === 'css'
                    ? `  ${variableName}: ${value};\n`
                    : `${variableName}: ${value};\n`;
                content += declaration;
            }
        }
        if (format === 'css') {
            content += '}\n\n';
        }
        else {
            content += '\n';
        }
        return content;
    }
    generateCSSClasses(colors, format, prefix, includeComments) {
        let content = '';
        if (includeComments) {
            content += `/* Classes utilitaires */\n`;
        }
        for (const [key, value] of Object.entries(colors)) {
            if (typeof value === 'string') {
                const className = `.${prefix}-${key.replace(/\./g, '-')}`;
                content += `${className} {\n`;
                content += `  color: ${value};\n`;
                content += `}\n\n`;
                const bgClassName = `.${prefix}-bg-${key.replace(/\./g, '-')}`;
                content += `${bgClassName} {\n`;
                content += `  background-color: ${value};\n`;
                content += `}\n\n`;
            }
        }
        return content;
    }
    async packageWithVSCE(packageDir, outputPath) {
        const { exec } = require('child_process');
        const util = require('util');
        const execAsync = util.promisify(exec);
        try {
            // Check if vsce is available
            await execAsync('vsce --version');
            // Package the extension
            const command = `vsce package --out "${outputPath}"`;
            await execAsync(command, { cwd: packageDir });
        }
        catch (error) {
            throw new Error(`VSCE non disponible ou erreur lors du packaging: ${error}`);
        }
    }
    async createReadme(packageDir, theme, packageJson) {
        const readme = `# ${theme.name} Theme

${theme.name} est un thème ${theme.type === 'dark' ? 'sombre' : 'clair'} pour Visual Studio Code, généré par Nexa Theme Designer.

## Aperçu

![Aperçu du thème](./preview.png)

## Installation

### Via VS Code Marketplace

1. Ouvrez VS Code
2. Allez dans Extensions (Ctrl+Shift+X)
3. Recherchez "${packageJson.name}"
4. Installez le thème
5. Allez dans File > Preferences > Color Theme
6. Sélectionnez "${theme.name}"

### Installation manuelle

1. Téléchargez le fichier .vsix
2. Ouvrez VS Code
3. Appuyez sur Ctrl+Shift+P
4. Tapez "Extensions: Install from VSIX"
5. Sélectionnez le fichier téléchargé

## Caractéristiques

- Thème ${theme.type === 'dark' ? 'sombre' : 'clair'} optimisé pour la lecture
- Coloration syntaxique complète
- Support des tokens sémantiques
- Compatible avec tous les langages supportés par VS Code
- Couleurs soigneusement choisies pour réduire la fatigue oculaire

## Langages supportés

- JavaScript/TypeScript
- Python
- PHP
- HTML/CSS
- JSON
- Markdown
- Et bien d'autres...

## Développement

Ce thème a été généré avec [Nexa Theme Designer](https://github.com/nexa/vscode-extensions), un outil puissant pour créer des thèmes VS Code personnalisés.

## Contribution

Les contributions sont les bienvenues! N'hésitez pas à ouvrir une issue ou une pull request.

## Licence

${packageJson.license || 'MIT'}

## Crédits

- Créé avec [Nexa Theme Designer](https://github.com/nexa/vscode-extensions)
- Basé sur l'API de thèmes de [Visual Studio Code](https://code.visualstudio.com/api/extension-guides/color-theme)
`;
        await fs.promises.writeFile(path.join(packageDir, 'README.md'), readme, 'utf8');
    }
    async createChangelog(packageDir, theme, version) {
        const changelog = `# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

## [${version}] - ${new Date().toISOString().split('T')[0]}

### Ajouté
- Version initiale du thème ${theme.name}
- Support complet des couleurs VS Code
- Coloration syntaxique optimisée pour tous les langages
- Support des tokens sémantiques
- Thème ${theme.type === 'dark' ? 'sombre' : 'clair'} avec palette de couleurs cohérente

### Caractéristiques
- ${Object.keys(theme.colors || {}).length} couleurs d'interface personnalisées
- ${theme.tokenColors?.length || 0} règles de coloration syntaxique
- Support des tokens sémantiques: ${theme.semanticHighlighting ? 'Oui' : 'Non'}

---

## Format

Ce changelog suit le format de [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
`;
        await fs.promises.writeFile(path.join(packageDir, 'CHANGELOG.md'), changelog, 'utf8');
    }
    getUITheme(type) {
        switch (type) {
            case 'light':
                return 'vs';
            case 'hc-black':
                return 'hc-black';
            case 'hc-light':
                return 'hc-light';
            default:
                return 'vs-dark';
        }
    }
    getExtensionForFormat(format) {
        switch (format) {
            case 'json':
                return 'json';
            case 'vsix':
                return 'vsix';
            case 'css':
                return 'css';
            case 'scss':
                return 'scss';
            case 'less':
                return 'less';
            default:
                return 'json';
        }
    }
    async getDefaultOutputPath(themeName, extension) {
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (!workspaceFolders) {
            throw new Error('Aucun workspace ouvert pour déterminer le chemin de sortie');
        }
        const outputDir = path.join(workspaceFolders[0].uri.fsPath, 'themes-export');
        await this.ensureDirectoryExists(outputDir);
        const fileName = `${themeName.toLowerCase().replace(/\s+/g, '-')}.${extension}`;
        return path.join(outputDir, fileName);
    }
    async ensureDirectoryExists(dirPath) {
        try {
            await fs.promises.access(dirPath);
        }
        catch {
            await fs.promises.mkdir(dirPath, { recursive: true });
        }
    }
    async copyDirectory(src, dest) {
        await this.ensureDirectoryExists(dest);
        const entries = await fs.promises.readdir(src, { withFileTypes: true });
        for (const entry of entries) {
            const srcPath = path.join(src, entry.name);
            const destPath = path.join(dest, entry.name);
            if (entry.isDirectory()) {
                await this.copyDirectory(srcPath, destPath);
            }
            else {
                await fs.promises.copyFile(srcPath, destPath);
            }
        }
    }
    async removeDirectory(dirPath) {
        try {
            const entries = await fs.promises.readdir(dirPath, { withFileTypes: true });
            for (const entry of entries) {
                const fullPath = path.join(dirPath, entry.name);
                if (entry.isDirectory()) {
                    await this.removeDirectory(fullPath);
                }
                else {
                    await fs.promises.unlink(fullPath);
                }
            }
            await fs.promises.rmdir(dirPath);
        }
        catch (error) {
            // Ignore errors when cleaning up
        }
    }
    async showExportDialog(theme) {
        const formatItems = [
            {
                label: 'JSON',
                description: 'Fichier JSON standard',
                detail: 'Format natif de VS Code, compatible avec tous les outils',
                format: 'json'
            },
            {
                label: 'VSIX Package',
                description: 'Package d\'extension VS Code',
                detail: 'Prêt à installer ou publier sur le marketplace',
                format: 'vsix'
            },
            {
                label: 'CSS',
                description: 'Feuille de style CSS',
                detail: 'Variables et classes CSS pour utilisation web',
                format: 'css'
            },
            {
                label: 'SCSS',
                description: 'Feuille de style Sass',
                detail: 'Variables Sass pour projets utilisant SCSS',
                format: 'scss'
            },
            {
                label: 'LESS',
                description: 'Feuille de style Less',
                detail: 'Variables Less pour projets utilisant Less',
                format: 'less'
            }
        ];
        const selectedFormat = await vscode.window.showQuickPick(formatItems, {
            placeHolder: 'Sélectionnez le format d\'export',
            title: `Exporter le thème "${theme.name}"`
        });
        if (!selectedFormat) {
            return undefined;
        }
        const options = {
            format: selectedFormat.format,
            includeMetadata: true,
            minify: false
        };
        // Additional options based on format
        if (selectedFormat.format === 'json') {
            const metadataChoice = await vscode.window.showQuickPick([
                { label: 'Avec métadonnées', value: true },
                { label: 'Thème seulement', value: false }
            ], { placeHolder: 'Inclure les métadonnées?' });
            if (metadataChoice) {
                options.includeMetadata = metadataChoice.value;
            }
            const minifyChoice = await vscode.window.showQuickPick([
                { label: 'Formaté (lisible)', value: false },
                { label: 'Minifié (compact)', value: true }
            ], { placeHolder: 'Format du JSON?' });
            if (minifyChoice) {
                options.minify = minifyChoice.value;
            }
        }
        // Ask for output location
        const outputUri = await vscode.window.showSaveDialog({
            defaultUri: vscode.Uri.file(`${theme.name.toLowerCase().replace(/\s+/g, '-')}.${this.getExtensionForFormat(selectedFormat.format)}`),
            filters: {
                [selectedFormat.label]: [this.getExtensionForFormat(selectedFormat.format)]
            },
            title: `Sauvegarder le thème ${theme.name}`
        });
        if (outputUri) {
            options.outputPath = outputUri.fsPath;
        }
        return options;
    }
    dispose() {
        this.outputChannel.dispose();
    }
}
exports.ThemeExporter = ThemeExporter;
//# sourceMappingURL=themeExporter.js.map