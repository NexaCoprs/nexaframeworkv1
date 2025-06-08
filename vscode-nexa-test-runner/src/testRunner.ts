import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class TestRunner {
    private outputChannel: vscode.OutputChannel;
    private isWatching: boolean = false;
    private watcher?: vscode.FileSystemWatcher;

    constructor() {
        this.outputChannel = vscode.window.createOutputChannel('Nexa Test Runner');
    }

    async runAllTests(): Promise<void> {
        this.outputChannel.show();
        this.outputChannel.appendLine('🚀 Exécution de tous les tests...');
        
        try {
            const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
            if (!workspaceFolder) {
                vscode.window.showErrorMessage('Aucun workspace ouvert');
                return;
            }

            const testCommand = this.getTestCommand();
            const terminal = vscode.window.createTerminal('Nexa Tests');
            terminal.sendText(testCommand);
            terminal.show();

            this.outputChannel.appendLine('✅ Tests lancés dans le terminal');
        } catch (error) {
            this.outputChannel.appendLine(`❌ Erreur: ${error}`);
            vscode.window.showErrorMessage(`Erreur lors de l'exécution des tests: ${error}`);
        }
    }

    async runCurrentTest(): Promise<void> {
        const editor = vscode.window.activeTextEditor;
        if (!editor) {
            vscode.window.showWarningMessage('Aucun fichier ouvert');
            return;
        }

        const testMethod = this.getCurrentTestMethod(editor);
        if (!testMethod) {
            vscode.window.showWarningMessage('Aucune méthode de test trouvée à la position du curseur');
            return;
        }

        this.outputChannel.show();
        this.outputChannel.appendLine(`🎯 Exécution du test: ${testMethod}`);

        const testCommand = this.getTestCommand(`--filter ${testMethod}`);
        const terminal = vscode.window.createTerminal('Nexa Test');
        terminal.sendText(testCommand);
        terminal.show();
    }

    async runTestFile(): Promise<void> {
        const editor = vscode.window.activeTextEditor;
        if (!editor) {
            vscode.window.showWarningMessage('Aucun fichier ouvert');
            return;
        }

        const filePath = editor.document.fileName;
        if (!this.isTestFile(filePath)) {
            vscode.window.showWarningMessage('Ce fichier ne semble pas être un fichier de test');
            return;
        }

        this.outputChannel.show();
        this.outputChannel.appendLine(`📄 Exécution des tests du fichier: ${path.basename(filePath)}`);

        const testCommand = this.getTestCommand(filePath);
        const terminal = vscode.window.createTerminal('Nexa Test File');
        terminal.sendText(testCommand);
        terminal.show();
    }

    async debugCurrentTest(): Promise<void> {
        const editor = vscode.window.activeTextEditor;
        if (!editor) {
            vscode.window.showWarningMessage('Aucun fichier ouvert');
            return;
        }

        const testMethod = this.getCurrentTestMethod(editor);
        if (!testMethod) {
            vscode.window.showWarningMessage('Aucune méthode de test trouvée');
            return;
        }

        this.outputChannel.appendLine(`🐛 Débogage du test: ${testMethod}`);

        // Configuration de débogage pour PHPUnit
        const debugConfig = {
            name: 'Debug Nexa Test',
            type: 'php',
            request: 'launch',
            program: '${workspaceFolder}/vendor/bin/phpunit',
            args: [`--filter`, testMethod],
            cwd: '${workspaceFolder}',
            runtimeArgs: ['-dxdebug.start_with_request=yes'],
            env: {
                XDEBUG_MODE: 'debug,develop'
            }
        };

        await vscode.debug.startDebugging(vscode.workspace.workspaceFolders?.[0], debugConfig);
    }

    async runTestsWithCoverage(): Promise<void> {
        this.outputChannel.show();
        this.outputChannel.appendLine('📊 Exécution des tests avec couverture de code...');

        const testCommand = this.getTestCommand('--coverage-html coverage');
        const terminal = vscode.window.createTerminal('Nexa Coverage');
        terminal.sendText(testCommand);
        terminal.show();

        vscode.window.showInformationMessage('Tests avec couverture lancés. Rapport disponible dans le dossier coverage/');
    }

    async runFailedTests(): Promise<void> {
        this.outputChannel.show();
        this.outputChannel.appendLine('🔄 Ré-exécution des tests échoués...');

        const testCommand = this.getTestCommand('--cache-result --order-by=defects --stop-on-defect');
        const terminal = vscode.window.createTerminal('Nexa Failed Tests');
        terminal.sendText(testCommand);
        terminal.show();
    }

    async watchTests(): Promise<void> {
        if (this.isWatching) {
            vscode.window.showWarningMessage('Le mode watch est déjà actif');
            return;
        }

        this.isWatching = true;
        this.outputChannel.show();
        this.outputChannel.appendLine('👀 Mode watch activé - Les tests seront exécutés automatiquement');

        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            return;
        }

        this.watcher = vscode.workspace.createFileSystemWatcher(
            new vscode.RelativePattern(workspaceFolder, '**/*.{php,nx}')
        );

        this.watcher.onDidChange(async (uri) => {
            if (this.isTestFile(uri.fsPath)) {
                this.outputChannel.appendLine(`📝 Fichier modifié: ${path.basename(uri.fsPath)}`);
                await this.runTestFile();
            }
        });

        vscode.window.showInformationMessage('Mode watch activé pour les tests Nexa');
    }

    async stopWatching(): Promise<void> {
        if (!this.isWatching) {
            vscode.window.showWarningMessage('Le mode watch n\'est pas actif');
            return;
        }

        this.isWatching = false;
        this.watcher?.dispose();
        this.outputChannel.appendLine('⏹️ Mode watch désactivé');
        vscode.window.showInformationMessage('Mode watch désactivé');
    }

    async runRelatedTests(document: vscode.TextDocument): Promise<void> {
        if (!this.isWatching) {
            return;
        }

        const fileName = path.basename(document.fileName);
        this.outputChannel.appendLine(`🔗 Exécution des tests liés à: ${fileName}`);

        // Logique pour trouver et exécuter les tests liés
        const testFile = this.findRelatedTestFile(document.fileName);
        if (testFile && fs.existsSync(testFile)) {
            const testCommand = this.getTestCommand(testFile);
            const terminal = vscode.window.createTerminal('Nexa Related Tests');
            terminal.sendText(testCommand);
        }
    }

    async configureTestSettings(): Promise<void> {
        const options = [
            'Configurer PHPUnit',
            'Configurer Pest',
            'Configurer les chemins de test',
            'Configurer la couverture de code'
        ];

        const selected = await vscode.window.showQuickPick(options, {
            placeHolder: 'Que souhaitez-vous configurer ?'
        });

        switch (selected) {
            case 'Configurer PHPUnit':
                await this.configurePHPUnit();
                break;
            case 'Configurer Pest':
                await this.configurePest();
                break;
            case 'Configurer les chemins de test':
                await this.configureTestPaths();
                break;
            case 'Configurer la couverture de code':
                await this.configureCoverage();
                break;
        }
    }

    private getTestCommand(args: string = ''): string {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            return '';
        }

        // Vérifier si Pest est disponible
        const pestPath = path.join(workspaceFolder.uri.fsPath, 'vendor', 'bin', 'pest');
        if (fs.existsSync(pestPath)) {
            return `./vendor/bin/pest ${args}`;
        }

        // Utiliser PHPUnit par défaut
        return `./vendor/bin/phpunit ${args}`;
    }

    private getCurrentTestMethod(editor: vscode.TextEditor): string | null {
        const document = editor.document;
        const position = editor.selection.active;
        const text = document.getText();
        
        // Rechercher la méthode de test la plus proche
        const lines = text.split('\n');
        for (let i = position.line; i >= 0; i--) {
            const line = lines[i];
            const match = line.match(/public\s+function\s+(test\w+)\s*\(/);
            if (match) {
                return match[1];
            }
        }
        
        return null;
    }

    private isTestFile(filePath: string): boolean {
        const fileName = path.basename(filePath);
        return fileName.includes('Test.php') || 
               fileName.includes('test.php') || 
               filePath.includes('/tests/') ||
               filePath.includes('\\tests\\');
    }

    private findRelatedTestFile(sourceFile: string): string | null {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            return null;
        }

        const baseName = path.basename(sourceFile, '.php');
        const testFileName = `${baseName}Test.php`;
        
        // Chercher dans le dossier tests
        const testPath = path.join(workspaceFolder.uri.fsPath, 'tests', testFileName);
        if (fs.existsSync(testPath)) {
            return testPath;
        }

        return null;
    }

    private async configurePHPUnit(): Promise<void> {
        const config = `<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </coverage>
</phpunit>`;

        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (workspaceFolder) {
            const configPath = path.join(workspaceFolder.uri.fsPath, 'phpunit.xml');
            fs.writeFileSync(configPath, config);
            vscode.window.showInformationMessage('Configuration PHPUnit créée');
        }
    }

    private async configurePest(): Promise<void> {
        const config = `<?php

uses(Tests\\TestCase::class)->in('Feature');
uses(Tests\\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function something()
{
    // ..
}`;

        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (workspaceFolder) {
            const configPath = path.join(workspaceFolder.uri.fsPath, 'tests', 'Pest.php');
            fs.writeFileSync(configPath, config);
            vscode.window.showInformationMessage('Configuration Pest créée');
        }
    }

    private async configureTestPaths(): Promise<void> {
        const config = vscode.workspace.getConfiguration('nexa.test');
        const currentPaths = config.get('paths', ['tests']);
        
        const newPath = await vscode.window.showInputBox({
            prompt: 'Entrez un nouveau chemin de test',
            value: 'tests'
        });

        if (newPath) {
            const updatedPaths = [...currentPaths, newPath];
            await config.update('paths', updatedPaths, vscode.ConfigurationTarget.Workspace);
            vscode.window.showInformationMessage(`Chemin de test ajouté: ${newPath}`);
        }
    }

    private async configureCoverage(): Promise<void> {
        const options = [
            'HTML',
            'XML',
            'Text',
            'Clover'
        ];

        const format = await vscode.window.showQuickPick(options, {
            placeHolder: 'Choisissez le format de rapport de couverture'
        });

        if (format) {
            const config = vscode.workspace.getConfiguration('nexa.test');
            await config.update('coverage.format', format.toLowerCase(), vscode.ConfigurationTarget.Workspace);
            vscode.window.showInformationMessage(`Format de couverture configuré: ${format}`);
        }
    }
}