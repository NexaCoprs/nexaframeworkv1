import * as vscode from 'vscode';
import * as path from 'path';
import { SecurityScanner, ScanResult } from './securityScanner';
import { VulnerabilityDetector } from './vulnerabilityDetector';
import { ComplianceChecker } from './complianceChecker';
import { ReportGenerator } from './reportGenerator';
import { DependencyScanner } from './dependencyScanner';
import { AuditReporter } from './auditReporter';

export function activate(context: vscode.ExtensionContext) {
    const securityScanner = new SecurityScanner();
    const vulnerabilityDetector = new VulnerabilityDetector();
    const complianceChecker = new ComplianceChecker();
    const reportGenerator = new ReportGenerator();
    const dependencyScanner = new DependencyScanner();
    const auditReporter = new AuditReporter();

    // Register commands
    const scanFile = vscode.commands.registerCommand('nexa.security.scanFile', async () => {
        const activeEditor = vscode.window.activeTextEditor;
        if (!activeEditor) {
            vscode.window.showErrorMessage('Aucun fichier ouvert pour le scan');
            return;
        }

        const document = activeEditor.document;
        await securityScanner.scanCurrentFile();
    });

    const scanProject = vscode.commands.registerCommand('nexa.security.scanProject', async () => {
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (!workspaceFolders) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const result = await securityScanner.scanEntireProject();
        
        if (result && result.issues.length > 0) {
            vscode.window.showWarningMessage(`${result.issues.length} problème(s) de sécurité détecté(s)`);
        } else {
            vscode.window.showInformationMessage('Aucun problème de sécurité détecté');
        }
    });

    const showReport = vscode.commands.registerCommand('nexa.security.showReport', async () => {
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (!workspaceFolders) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        // Scan project first
        const scanResult = await securityScanner.scanEntireProject();
        if (!scanResult) {
            vscode.window.showErrorMessage('Échec du scan du projet');
            return;
        }
        
        // Generate and show report
        const report = await reportGenerator.generateSecurityReport([scanResult], [], path.basename(workspaceFolders[0].uri.fsPath));
        await reportGenerator.showReport(report);
    });

    const fixVulnerability = vscode.commands.registerCommand('nexa.security.fixVulnerability', async (vulnerability) => {
        await securityScanner.fixVulnerability(vulnerability);
    });

    const checkCompliance = vscode.commands.registerCommand('nexa.security.checkCompliance', async () => {
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (!workspaceFolders) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const standards = ['OWASP', 'PCI-DSS', 'GDPR'];
        const reports = [];
        
        for (const standard of standards) {
            const report = await complianceChecker.generateComplianceReport(workspaceFolders[0].uri.fsPath, standard);
            reports.push(report);
        }
        
        // Show compliance results
        const panel = vscode.window.createWebviewPanel(
            'complianceReport',
            'Rapport de Conformité',
            vscode.ViewColumn.One,
            { enableScripts: true }
        );
        
        const htmlContent = reports.map(report => `
            <div style="margin: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px;">
                <h3>${report.standard}</h3>
                <p><strong>Score:</strong> ${report.score}%</p>
                <p><strong>Règles respectées:</strong> ${report.passedRules}/${report.totalRules}</p>
                <p><strong>Problèmes détectés:</strong> ${report.failedRules}</p>
            </div>
        `).join('');
        panel.webview.html = `<!DOCTYPE html>
<html><head><title>Conformité</title><style>body{font-family:Arial,sans-serif;}</style></head><body><h1>Rapport de Conformité</h1>${htmlContent}</body></html>`;
    });

    const generateAudit = vscode.commands.registerCommand('nexa.security.generateAudit', async () => {
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (!workspaceFolders) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        try {
            vscode.window.showInformationMessage('Génération du rapport d\'audit en cours...');
            
            // Perform comprehensive security scan
            const securityResult = await securityScanner.scanEntireProject();
            if (!securityResult) {
                vscode.window.showErrorMessage('Erreur lors du scan de sécurité');
                return;
            }
            
            // Check compliance
            const complianceReports = [];
            const standards = ['OWASP', 'PCI-DSS', 'GDPR'];
            for (const standard of standards) {
                const report = await complianceChecker.generateComplianceReport(workspaceFolders[0].uri.fsPath, standard);
                complianceReports.push(report);
            }
            
            // Scan dependencies
            const dependencyResult = await dependencyScanner.scanDependencies(workspaceFolders[0].uri.fsPath);
            
            // Generate comprehensive audit report
            const auditReport = await auditReporter.generateAuditReport(
                workspaceFolders[0].uri.fsPath,
                securityResult.issues,
                complianceReports,
                dependencyResult
            );
            
            // Show the audit report
            await auditReporter.showReport(auditReport);
            
            vscode.window.showInformationMessage('Rapport d\'audit généré avec succès!');
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la génération du rapport d'audit: ${error}`);
        }
    });

    const configureRules = vscode.commands.registerCommand('nexa.security.configureRules', async () => {
        await securityScanner.configureSecurityRules();
    });

    const scanDependencies = vscode.commands.registerCommand('nexa.security.scanDependencies', async () => {
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (!workspaceFolders) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        try {
            vscode.window.showInformationMessage('Scan des dépendances en cours...');
            
            const result = await dependencyScanner.scanDependencies(workspaceFolders[0].uri.fsPath);
            
            if (result.totalVulnerabilities > 0) {
                vscode.window.showWarningMessage(
                    `${result.totalVulnerabilities} vulnérabilité(s) détectée(s) dans ${result.vulnerableDependencies} dépendance(s)`
                );
                
                // Generate security issues from dependency scan
                const securityIssues = dependencyScanner.generateSecurityIssues(result);
                
                // Show results
                const scanResult = {
                    issues: securityIssues,
                    summary: {
                        total: securityIssues.length,
                        critical: securityIssues.filter(i => i.severity === 'critical').length,
                        high: securityIssues.filter(i => i.severity === 'high').length,
                        medium: securityIssues.filter(i => i.severity === 'medium').length,
                        low: securityIssues.filter(i => i.severity === 'low').length,
                        info: securityIssues.filter(i => i.severity === 'info').length
                    },
                    scanTime: result.scanTime,
                    timestamp: result.timestamp
                };
                
                const { summary } = scanResult;
                const message = `Scan des dépendances terminé: ${summary.total} problème(s) de sécurité trouvé(s)`;
                
                if (summary.critical > 0 || summary.high > 0) {
                    vscode.window.showErrorMessage(message);
                } else if (summary.medium > 0) {
                    vscode.window.showWarningMessage(message);
                } else {
                    vscode.window.showInformationMessage(message);
                }
            } else {
                vscode.window.showInformationMessage('Aucune vulnérabilité détectée dans les dépendances');
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors du scan des dépendances: ${error}`);
        }
    });

    const checkPermissions = vscode.commands.registerCommand('nexa.security.checkPermissions', async () => {
        await securityScanner.checkFilePermissions();
    });

    const validateInput = vscode.commands.registerCommand('nexa.security.validateInput', async () => {
        vscode.window.showInformationMessage('Utilisez le scan complet pour valider la sécurité des entrées');
    });

    const checkSQLInjection = vscode.commands.registerCommand('nexa.security.checkSQLInjection', async () => {
        vscode.window.showInformationMessage('Utilisez le scan complet pour détecter les injections SQL');
    });

    const checkXSS = vscode.commands.registerCommand('nexa.security.checkXSS', async () => {
        vscode.window.showInformationMessage('Utilisez le scan complet pour détecter les vulnérabilités XSS');
    });

    const checkCSRF = vscode.commands.registerCommand('nexa.security.checkCSRF', async () => {
        vscode.window.showInformationMessage('Utilisez le scan complet pour détecter les vulnérabilités CSRF');
    });

    const exportReport = vscode.commands.registerCommand('nexa.security.exportReport', async () => {
        const workspaceFolders = vscode.workspace.workspaceFolders;
        if (!workspaceFolders) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        // Scan project first
        const scanResult = await securityScanner.scanEntireProject();
        if (!scanResult) {
            vscode.window.showErrorMessage('Échec du scan du projet');
            return;
        }
        
        // Generate and show report
        const report = await reportGenerator.generateSecurityReport([scanResult], [], path.basename(workspaceFolders[0].uri.fsPath));
        await reportGenerator.exportReport(report, {
            format: 'html',
            includeDetails: true,
            includeCompliance: true,
            includeTrends: true,
            includeRecommendations: true
        });
    });

    // New command for vulnerability detection
    const detectVulnerabilities = vscode.commands.registerCommand('nexa.security.detectVulnerabilities', async () => {
        const activeEditor = vscode.window.activeTextEditor;
        if (!activeEditor) {
            vscode.window.showErrorMessage('Aucun fichier ouvert pour la détection');
            return;
        }

        const document = activeEditor.document;
        const content = document.getText();
        const language = document.languageId;
        
        const vulnerabilities = await vulnerabilityDetector.detectVulnerabilities(content, document.fileName);
        
        if (vulnerabilities.length > 0) {
            vscode.window.showWarningMessage(`${vulnerabilities.length} vulnérabilité(s) détectée(s)`);
            
            // Convert to security issues and show
            const securityIssues = vulnerabilities.map((vuln: any) => ({
                id: `vuln_${Date.now()}_${Math.random()}`,
                type: 'vulnerability' as const,
                severity: vuln.severity,
                title: vuln.title,
                description: vuln.description,
                file: document.fileName,
                line: vuln.line,
                rule: vuln.type,
                fix: vuln.recommendation,
                references: vuln.references
            }));
            
            const result: ScanResult = {
                issues: securityIssues,
                summary: {
                    total: securityIssues.length,
                    critical: securityIssues.filter((i: any) => i.severity === 'critical').length,
                    high: securityIssues.filter((i: any) => i.severity === 'high').length,
                    medium: securityIssues.filter((i: any) => i.severity === 'medium').length,
                    low: securityIssues.filter((i: any) => i.severity === 'low').length,
                    info: securityIssues.filter((i: any) => i.severity === 'info').length
                },
                scanTime: 0,
                timestamp: new Date()
            };
            
            const { summary } = result;
            const message = `Scan terminé: ${summary.total} problème(s) de sécurité trouvé(s) (${summary.critical} critique(s), ${summary.high} élevé(s), ${summary.medium} moyen(s))`;
            
            if (summary.critical > 0 || summary.high > 0) {
                vscode.window.showErrorMessage(message);
            } else if (summary.medium > 0) {
                vscode.window.showWarningMessage(message);
            } else {
                vscode.window.showInformationMessage(message);
            }
        } else {
            vscode.window.showInformationMessage('Aucune vulnérabilité détectée');
        }
    });

    // Enregistrement des commandes
    context.subscriptions.push(
        scanFile,
        scanProject,
        showReport,
        fixVulnerability,
        checkCompliance,
        generateAudit,
        configureRules,
        scanDependencies,
        checkPermissions,
        validateInput,
        checkSQLInjection,
        checkXSS,
        checkCSRF,
        exportReport,
        detectVulnerabilities
    );

    // Diagnostic collection pour les problèmes de sécurité
    const securityDiagnostics = vscode.languages.createDiagnosticCollection('nexa-security');
    context.subscriptions.push(securityDiagnostics);

    // Scanner automatique lors de l'ouverture/modification de fichiers
    const documentChangeListener = vscode.workspace.onDidChangeTextDocument(async (event) => {
        const document = event.document;
        if (document.languageId === 'php' || document.fileName.endsWith('.nx')) {
            await securityScanner.scanCurrentFile();
        }
    });

    const documentOpenListener = vscode.workspace.onDidOpenTextDocument(async (document) => {
        if (document.languageId === 'php' || document.fileName.endsWith('.nx')) {
            await securityScanner.scanCurrentFile();
        }
    });

    context.subscriptions.push(documentChangeListener, documentOpenListener);

    // Code Actions pour les corrections automatiques
    const codeActionProvider = vscode.languages.registerCodeActionsProvider(
        ['php', 'nx'],
        new SecurityCodeActionProvider(securityScanner),
        {
            providedCodeActionKinds: [vscode.CodeActionKind.QuickFix]
        }
    );

    context.subscriptions.push(codeActionProvider);

    // Status bar item
    const statusBarItem = vscode.window.createStatusBarItem(vscode.StatusBarAlignment.Right, 100);
    statusBarItem.command = 'nexa.security.showReport';
    statusBarItem.text = '$(shield) Nexa Security';
    statusBarItem.tooltip = 'Cliquer pour voir le rapport de sécurité';
    statusBarItem.show();
    context.subscriptions.push(statusBarItem);

    vscode.window.showInformationMessage('Nexa Security Scanner est prêt!');
}

export function deactivate() {
    console.log('Extension Nexa Security Scanner désactivée');
}

class SecurityCodeActionProvider implements vscode.CodeActionProvider {
    constructor(private securityScanner: SecurityScanner) {}

    provideCodeActions(
        document: vscode.TextDocument,
        range: vscode.Range | vscode.Selection,
        context: vscode.CodeActionContext,
        token: vscode.CancellationToken
    ): vscode.ProviderResult<(vscode.Command | vscode.CodeAction)[]> {
        const actions: vscode.CodeAction[] = [];

        // Rechercher les diagnostics de sécurité dans la plage
        const securityDiagnostics = context.diagnostics.filter(
            diagnostic => diagnostic.source === 'nexa-security'
        );

        for (const diagnostic of securityDiagnostics) {
            const action = new vscode.CodeAction(
                `Corriger: ${diagnostic.message}`,
                vscode.CodeActionKind.QuickFix
            );
            
            action.command = {
                title: 'Corriger la vulnérabilité',
                command: 'nexa.security.fixVulnerability',
                arguments: [diagnostic]
            };
            
            action.diagnostics = [diagnostic];
            actions.push(action);
        }

        return actions;
    }
}