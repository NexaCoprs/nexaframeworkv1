import * as vscode from 'vscode';
import { TestRunner } from './testRunner';
import { CoverageAnalyzer } from './coverageAnalyzer';
import { TestGenerator } from './testGenerator';
import { TestReporter } from './testReporter';

export function activate(context: vscode.ExtensionContext) {
    console.log('Extension Nexa Test Runner activée');

    const testRunner = new TestRunner();
    const coverageAnalyzer = new CoverageAnalyzer(context);
    const testGenerator = new TestGenerator();
    const testReporter = new TestReporter(context);

    // Commandes principales
    const runTests = vscode.commands.registerCommand('nexa.test.runTests', async () => {
        await testRunner.runAllTests();
    });

    const runCurrentTest = vscode.commands.registerCommand('nexa.test.runCurrentTest', async () => {
        await testRunner.runCurrentTest();
    });

    const runTestFile = vscode.commands.registerCommand('nexa.test.runTestFile', async () => {
        await testRunner.runTestFile();
    });

    const debugTest = vscode.commands.registerCommand('nexa.test.debugTest', async () => {
        await testRunner.debugCurrentTest();
    });

    const generateTest = vscode.commands.registerCommand('nexa.test.generateTest', async () => {
        await testGenerator.generateTestForCurrentFile();
    });

    const showCoverage = vscode.commands.registerCommand('nexa.test.showCoverage', async () => {
        await coverageAnalyzer.showCoverageReport();
    });

    const runWithCoverage = vscode.commands.registerCommand('nexa.test.runWithCoverage', async () => {
        await testRunner.runTestsWithCoverage();
    });

    const generateMocks = vscode.commands.registerCommand('nexa.test.generateMocks', async () => {
        await testGenerator.generateMocks();
    });

    const generateFixtures = vscode.commands.registerCommand('nexa.test.generateFixtures', async () => {
        await testGenerator.generateFixtures();
    });

    const runFailedTests = vscode.commands.registerCommand('nexa.test.runFailedTests', async () => {
        await testRunner.runFailedTests();
    });

    const watchTests = vscode.commands.registerCommand('nexa.test.watchTests', async () => {
        await testRunner.watchTests();
    });

    const stopWatching = vscode.commands.registerCommand('nexa.test.stopWatching', async () => {
        await testRunner.stopWatching();
    });

    const showTestReport = vscode.commands.registerCommand('nexa.test.showReport', async () => {
        await testReporter.showTestReport();
    });

    const exportReport = vscode.commands.registerCommand('nexa.test.exportReport', async () => {
        await testReporter.exportReport();
    });

    const configureTests = vscode.commands.registerCommand('nexa.test.configure', async () => {
        await testRunner.configureTestSettings();
    });

    // Enregistrement des commandes
    context.subscriptions.push(
        runTests,
        runCurrentTest,
        runTestFile,
        debugTest,
        generateTest,
        showCoverage,
        runWithCoverage,
        generateMocks,
        generateFixtures,
        runFailedTests,
        watchTests,
        stopWatching,
        showTestReport,
        exportReport,
        configureTests
    );

    // Test Explorer
    const testExplorer = new TestExplorerProvider();
    vscode.window.createTreeView('nexaTestExplorer', {
        treeDataProvider: testExplorer,
        showCollapseAll: true
    });

    // Décorateurs pour la couverture de code
    const coverageDecorator = vscode.window.createTextEditorDecorationType({
        backgroundColor: new vscode.ThemeColor('testing.coveredBackground'),
        isWholeLine: true
    });

    const uncoveredDecorator = vscode.window.createTextEditorDecorationType({
        backgroundColor: new vscode.ThemeColor('testing.uncoveredBackground'),
        isWholeLine: true
    });

    context.subscriptions.push(coverageDecorator, uncoveredDecorator);

    // Listener pour les changements de fichiers
    const documentChangeListener = vscode.workspace.onDidSaveTextDocument(async (document) => {
        if (document.languageId === 'php' || document.fileName.endsWith('.nx')) {
            await testRunner.runRelatedTests(document);
        }
    });

    context.subscriptions.push(documentChangeListener);

    // Status bar pour les tests
    const testStatusBar = vscode.window.createStatusBarItem(vscode.StatusBarAlignment.Left, 100);
    testStatusBar.command = 'nexa.test.runTests';
    testStatusBar.text = '$(beaker) Tests';
    testStatusBar.tooltip = 'Cliquer pour exécuter tous les tests';
    testStatusBar.show();
    context.subscriptions.push(testStatusBar);

    // Code Lens pour les tests
    const testCodeLensProvider = vscode.languages.registerCodeLensProvider(
        ['php', 'nx'],
        new TestCodeLensProvider(testRunner)
    );

    context.subscriptions.push(testCodeLensProvider);

    vscode.window.showInformationMessage('Nexa Test Runner est prêt!');
}

export function deactivate() {
    console.log('Extension Nexa Test Runner désactivée');
}

class TestExplorerProvider implements vscode.TreeDataProvider<TestItem> {
    private _onDidChangeTreeData: vscode.EventEmitter<TestItem | undefined | null | void> = new vscode.EventEmitter<TestItem | undefined | null | void>();
    readonly onDidChangeTreeData: vscode.Event<TestItem | undefined | null | void> = this._onDidChangeTreeData.event;

    refresh(): void {
        this._onDidChangeTreeData.fire();
    }

    getTreeItem(element: TestItem): vscode.TreeItem {
        return element;
    }

    getChildren(element?: TestItem): Thenable<TestItem[]> {
        if (!element) {
            return Promise.resolve([
                new TestItem('Unit Tests', vscode.TreeItemCollapsibleState.Collapsed, 'unit'),
                new TestItem('Integration Tests', vscode.TreeItemCollapsibleState.Collapsed, 'integration'),
                new TestItem('Feature Tests', vscode.TreeItemCollapsibleState.Collapsed, 'feature')
            ]);
        }
        return Promise.resolve([]);
    }
}

class TestItem extends vscode.TreeItem {
    constructor(
        public readonly label: string,
        public readonly collapsibleState: vscode.TreeItemCollapsibleState,
        public readonly type: string
    ) {
        super(label, collapsibleState);
        this.tooltip = `${this.label} - ${this.type}`;
        this.description = this.type;
        this.contextValue = 'testItem';
    }

    iconPath = new vscode.ThemeIcon('beaker');
}

class TestCodeLensProvider implements vscode.CodeLensProvider {
    constructor(private testRunner: TestRunner) {}

    provideCodeLenses(
        document: vscode.TextDocument,
        token: vscode.CancellationToken
    ): vscode.ProviderResult<vscode.CodeLens[]> {
        const codeLenses: vscode.CodeLens[] = [];
        const text = document.getText();
        
        // Rechercher les méthodes de test
        const testMethodRegex = /public\s+function\s+(test\w+)\s*\(/g;
        let match;
        
        while ((match = testMethodRegex.exec(text)) !== null) {
            const line = document.positionAt(match.index).line;
            const range = new vscode.Range(line, 0, line, 0);
            
            // Code lens pour exécuter le test
            const runTestLens = new vscode.CodeLens(range, {
                title: '▶ Exécuter',
                command: 'nexa.test.runCurrentTest',
                arguments: [match[1]]
            });
            
            // Code lens pour déboguer le test
            const debugTestLens = new vscode.CodeLens(range, {
                title: '🐛 Déboguer',
                command: 'nexa.test.debugTest',
                arguments: [match[1]]
            });
            
            codeLenses.push(runTestLens, debugTestLens);
        }
        
        return codeLenses;
    }
}