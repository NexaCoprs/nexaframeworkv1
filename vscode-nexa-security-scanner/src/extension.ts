import * as vscode from 'vscode';
import { SecurityScanner } from './securityScanner';
import { VulnerabilityDetector } from './vulnerabilityDetector';
import { ComplianceChecker } from './complianceChecker';
import { SecurityReporter } from './securityReporter';

export function activate(context: vscode.ExtensionContext) {
    console.log('Extension Nexa Security Scanner activée');

    const securityScanner = new SecurityScanner();
    const vulnerabilityDetector = new VulnerabilityDetector();
    const complianceChecker = new ComplianceChecker();
    const securityReporter = new SecurityReporter(context);

    // Commandes principales
    const scanFile = vscode.commands.registerCommand('nexa.security.scanFile', async () => {
        await securityScanner.scanCurrentFile();
    });

    const scanProject = vscode.commands.registerCommand('nexa.security.scanProject', async () => {
        await securityScanner.scanEntireProject();
    });

    const showReport = vscode.commands.registerCommand('nexa.security.showReport', async () => {
        await securityReporter.showSecurityReport();
    });

    const fixVulnerability = vscode.commands.registerCommand('nexa.security.fixVulnerability', async (vulnerability) => {
        await securityScanner.fixVulnerability(vulnerability);
    });

    const checkCompliance = vscode.commands.registerCommand('nexa.security.checkCompliance', async () => {
        await complianceChecker.checkCompliance();
    });

    const generateAudit = vscode.commands.registerCommand('nexa.security.generateAudit', async () => {
        await securityReporter.generateAuditReport();
    });

    const configureRules = vscode.commands.registerCommand('nexa.security.configureRules', async () => {
        await securityScanner.configureSecurityRules();
    });

    const scanDependencies = vscode.commands.registerCommand('nexa.security.scanDependencies', async () => {
        await vulnerabilityDetector.scanDependencies();
    });

    const checkPermissions = vscode.commands.registerCommand('nexa.security.checkPermissions', async () => {
        await securityScanner.checkFilePermissions();
    });

    const validateInput = vscode.commands.registerCommand('nexa.security.validateInput', async () => {
        await vulnerabilityDetector.validateInputSecurity();
    });

    const checkSQLInjection = vscode.commands.registerCommand('nexa.security.checkSQLInjection', async () => {
        await vulnerabilityDetector.checkSQLInjection();
    });

    const checkXSS = vscode.commands.registerCommand('nexa.security.checkXSS', async () => {
        await vulnerabilityDetector.checkXSSVulnerabilities();
    });

    const checkCSRF = vscode.commands.registerCommand('nexa.security.checkCSRF', async () => {
        await vulnerabilityDetector.checkCSRFProtection();
    });

    const exportReport = vscode.commands.registerCommand('nexa.security.exportReport', async () => {
        await securityReporter.exportReport();
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
        exportReport
    );

    // Diagnostic collection pour les problèmes de sécurité
    const securityDiagnostics = vscode.languages.createDiagnosticCollection('nexa-security');
    context.subscriptions.push(securityDiagnostics);

    // Scanner automatique lors de l'ouverture/modification de fichiers
    const documentChangeListener = vscode.workspace.onDidChangeTextDocument(async (event) => {
        if (event.document.languageId === 'php' || event.document.fileName.endsWith('.nx')) {
            await securityScanner.scanDocumentForIssues(event.document, securityDiagnostics);
        }
    });

    const documentOpenListener = vscode.workspace.onDidOpenTextDocument(async (document) => {
        if (document.languageId === 'php' || document.fileName.endsWith('.nx')) {
            await securityScanner.scanDocumentForIssues(document, securityDiagnostics);
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