import * as vscode from 'vscode';
import { PerformanceMonitor } from './performanceMonitor';
import { PerformanceAnalyzer } from './performanceAnalyzer';
import { PerformanceReportPanel } from './performanceReportPanel';
import { PerformanceTreeProvider } from './performanceTreeProvider';

let performanceMonitor: PerformanceMonitor;
let performanceAnalyzer: PerformanceAnalyzer;
let performanceTreeProvider: PerformanceTreeProvider;

export function activate(context: vscode.ExtensionContext) {
    console.log('Nexa Performance Monitor extension is now active!');

    // Initialize components
    performanceMonitor = new PerformanceMonitor(context);
    performanceAnalyzer = new PerformanceAnalyzer(context);
    performanceTreeProvider = new PerformanceTreeProvider(context, performanceMonitor);

    // Register tree data provider
    vscode.window.registerTreeDataProvider('nexaPerformanceMonitor', performanceTreeProvider);

    // Register commands
    const commands = [
        vscode.commands.registerCommand('nexa.performance.startMonitoring', async () => {
            await performanceMonitor.startMonitoring();
            performanceTreeProvider.refresh();
            vscode.window.showInformationMessage('Monitoring de performance démarré');
        }),

        vscode.commands.registerCommand('nexa.performance.stopMonitoring', async () => {
            await performanceMonitor.stopMonitoring();
            performanceTreeProvider.refresh();
            vscode.window.showInformationMessage('Monitoring de performance arrêté');
        }),

        vscode.commands.registerCommand('nexa.performance.analyzeFile', async () => {
            const editor = vscode.window.activeTextEditor;
            if (!editor) {
                vscode.window.showErrorMessage('Aucun fichier ouvert');
                return;
            }
            
            const analysis = await performanceAnalyzer.analyzeFile(editor.document.uri.fsPath);
            if (analysis) {
                vscode.window.showInformationMessage(`Analyse terminée: ${analysis.issues.length} problèmes détectés`);
                performanceTreeProvider.refresh();
            }
        }),

        vscode.commands.registerCommand('nexa.performance.analyzeProject', async () => {
            if (!vscode.workspace.workspaceFolders) {
                vscode.window.showErrorMessage('Aucun workspace ouvert');
                return;
            }

            vscode.window.withProgress({
                location: vscode.ProgressLocation.Notification,
                title: 'Analyse du projet en cours...',
                cancellable: true
            }, async (progress, token) => {
                const analysis = await performanceAnalyzer.analyzeProject(
                    vscode.workspace.workspaceFolders![0].uri.fsPath,
                    progress,
                    token
                );
                
                if (analysis && !token.isCancellationRequested) {
                    vscode.window.showInformationMessage(
                        `Analyse du projet terminée: ${analysis.totalIssues} problèmes détectés`
                    );
                    performanceTreeProvider.refresh();
                }
            });
        }),

        vscode.commands.registerCommand('nexa.performance.showReport', () => {
            PerformanceReportPanel.createOrShow(context.extensionUri, performanceMonitor, performanceAnalyzer);
        }),

        vscode.commands.registerCommand('nexa.performance.optimizationSuggestions', async () => {
            const suggestions = await performanceAnalyzer.getOptimizationSuggestions();
            if (suggestions.length === 0) {
                vscode.window.showInformationMessage('Aucune suggestion d\'optimisation disponible');
                return;
            }

            const selected = await vscode.window.showQuickPick(
                suggestions.map(s => ({
                    label: s.title,
                    description: s.description,
                    detail: s.impact,
                    suggestion: s
                })),
                {
                    placeHolder: 'Sélectionnez une suggestion d\'optimisation'
                }
            );

            if (selected) {
                vscode.window.showInformationMessage(selected.suggestion.details);
            }
        }),

        vscode.commands.registerCommand('nexa.performance.profileFunction', async () => {
            const editor = vscode.window.activeTextEditor;
            if (!editor) {
                vscode.window.showErrorMessage('Aucun éditeur actif');
                return;
            }

            const selection = editor.selection;
            const selectedText = editor.document.getText(selection);
            
            if (!selectedText) {
                vscode.window.showErrorMessage('Sélectionnez une fonction à profiler');
                return;
            }

            const profile = await performanceAnalyzer.profileFunction(selectedText, editor.document.uri.fsPath);
            if (profile) {
                vscode.window.showInformationMessage(
                    `Profiling terminé: ${profile.executionTime}ms, ${profile.memoryUsage}MB`
                );
            }
        }),

        vscode.commands.registerCommand('nexa.performance.memoryUsage', async () => {
            const memoryReport = await performanceMonitor.getMemoryUsage();
            vscode.window.showInformationMessage(
                `Utilisation mémoire: ${memoryReport.used}MB / ${memoryReport.total}MB (${memoryReport.percentage}%)`
            );
        })
    ];

    commands.forEach(command => context.subscriptions.push(command));

    // Set context for when extension is active
    vscode.commands.executeCommand('setContext', 'nexaProject', true);

    // Auto-start monitoring if enabled
    const config = vscode.workspace.getConfiguration('nexa.performance');
    if (config.get<boolean>('autoMonitoring', false)) {
        performanceMonitor.startMonitoring();
    }

    // Watch for configuration changes
    vscode.workspace.onDidChangeConfiguration(event => {
        if (event.affectsConfiguration('nexa.performance')) {
            performanceMonitor.updateConfiguration();
        }
    });
}

export function deactivate() {
    if (performanceMonitor) {
        performanceMonitor.stopMonitoring();
    }
    console.log('Nexa Performance Monitor extension is now deactivated');
}