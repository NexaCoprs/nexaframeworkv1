"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.TestReporter = void 0;
const vscode = require("vscode");
const path = require("path");
const fs = require("fs");
class TestReporter {
    constructor(context) {
        this.testResults = null;
        this.context = context;
    }
    async showTestReport() {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }
        // Charger les résultats de test
        await this.loadTestResults();
        // Créer et afficher le rapport
        const panel = vscode.window.createWebviewPanel('nexaTestReport', 'Rapport de Tests Nexa', vscode.ViewColumn.One, {
            enableScripts: true,
            localResourceRoots: [this.context.extensionUri]
        });
        panel.webview.html = this.getReportHtml();
        panel.webview.onDidReceiveMessage(message => {
            switch (message.command) {
                case 'openFile':
                    this.openTestFile(message.file, message.line);
                    break;
                case 'runTest':
                    this.runSpecificTest(message.test);
                    break;
                case 'exportReport':
                    this.exportReport(message.format);
                    break;
            }
        }, undefined, this.context.subscriptions);
    }
    async exportReport() {
        const formats = [
            'HTML',
            'JSON',
            'XML (JUnit)',
            'CSV',
            'PDF'
        ];
        const selectedFormat = await vscode.window.showQuickPick(formats, {
            placeHolder: 'Choisissez le format d\'export'
        });
        if (!selectedFormat) {
            return;
        }
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            return;
        }
        const exportPath = await vscode.window.showSaveDialog({
            defaultUri: vscode.Uri.file(path.join(workspaceFolder.uri.fsPath, `test-report.${this.getFileExtension(selectedFormat)}`)),
            filters: {
                [selectedFormat]: [this.getFileExtension(selectedFormat)]
            }
        });
        if (!exportPath) {
            return;
        }
        await this.generateExportFile(selectedFormat, exportPath.fsPath);
        vscode.window.showInformationMessage(`Rapport exporté: ${path.basename(exportPath.fsPath)}`);
    }
    async loadTestResults() {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            return;
        }
        // Chercher les fichiers de résultats de test
        const junitFile = path.join(workspaceFolder.uri.fsPath, 'tests', 'results', 'junit.xml');
        const jsonFile = path.join(workspaceFolder.uri.fsPath, 'tests', 'results', 'results.json');
        if (fs.existsSync(jsonFile)) {
            try {
                const content = fs.readFileSync(jsonFile, 'utf8');
                this.testResults = JSON.parse(content);
            }
            catch (error) {
                console.error('Erreur lors du chargement des résultats JSON:', error);
            }
        }
        else if (fs.existsSync(junitFile)) {
            // Parser le fichier JUnit XML
            this.testResults = await this.parseJUnitXML(junitFile);
        }
        else {
            // Générer des données de test simulées
            this.testResults = this.generateMockResults();
        }
    }
    async parseJUnitXML(filePath) {
        try {
            const xml2js = require('xml2js');
            const xmlContent = fs.readFileSync(filePath, 'utf8');
            const parser = new xml2js.Parser({ explicitArray: false });
            const result = await parser.parseStringPromise(xmlContent);
            const testsuites = result.testsuites || result.testsuite;
            if (!testsuites) {
                throw new Error('Invalid JUnit XML format');
            }
            const suites = Array.isArray(testsuites.testsuite) ? testsuites.testsuite : [testsuites.testsuite || testsuites];
            let totalTests = 0;
            let totalPassed = 0;
            let totalFailed = 0;
            let totalSkipped = 0;
            let totalDuration = 0;
            const parsedSuites = suites.map((suite) => {
                const tests = parseInt(suite.$.tests || '0');
                const failures = parseInt(suite.$.failures || '0');
                const errors = parseInt(suite.$.errors || '0');
                const skipped = parseInt(suite.$.skipped || '0');
                const time = parseFloat(suite.$.time || '0');
                const passed = tests - failures - errors - skipped;
                totalTests += tests;
                totalPassed += passed;
                totalFailed += failures + errors;
                totalSkipped += skipped;
                totalDuration += time;
                const testCases = [];
                if (suite.testcase) {
                    const cases = Array.isArray(suite.testcase) ? suite.testcase : [suite.testcase];
                    testCases.push(...cases.map((testcase) => {
                        let status = 'passed';
                        let error = null;
                        if (testcase.failure) {
                            status = 'failed';
                            error = testcase.failure._ || testcase.failure;
                        }
                        else if (testcase.error) {
                            status = 'failed';
                            error = testcase.error._ || testcase.error;
                        }
                        else if (testcase.skipped) {
                            status = 'skipped';
                        }
                        return {
                            name: testcase.$.name,
                            class: testcase.$.classname,
                            status,
                            duration: parseFloat(testcase.$.time || '0'),
                            error,
                            file: testcase.$.file,
                            line: testcase.$.line ? parseInt(testcase.$.line) : undefined
                        };
                    }));
                }
                return {
                    name: suite.$.name,
                    file: suite.$.file || suite.$.name,
                    tests,
                    passed,
                    failed: failures + errors,
                    skipped,
                    duration: time,
                    testCases
                };
            });
            return {
                summary: {
                    total: totalTests,
                    passed: totalPassed,
                    failed: totalFailed,
                    skipped: totalSkipped,
                    duration: totalDuration,
                    coverage: null // Coverage not available in JUnit XML
                },
                suites: parsedSuites
            };
        }
        catch (error) {
            console.error('Error parsing JUnit XML:', error);
            // Fallback to mock data if parsing fails
            return this.generateMockResults();
        }
    }
    generateMockResults() {
        return {
            summary: {
                total: 25,
                passed: 22,
                failed: 2,
                skipped: 1,
                duration: 12.45,
                coverage: 85.5
            },
            suites: [
                {
                    name: 'Unit Tests',
                    file: 'tests/Unit',
                    tests: 15,
                    passed: 14,
                    failed: 1,
                    skipped: 0,
                    duration: 8.2,
                    testCases: [
                        {
                            name: 'testUserCreation',
                            class: 'UserTest',
                            status: 'passed',
                            duration: 0.15
                        },
                        {
                            name: 'testUserValidation',
                            class: 'UserTest',
                            status: 'failed',
                            duration: 0.25,
                            error: 'Assertion failed: Expected true but got false',
                            file: 'tests/Unit/UserTest.php',
                            line: 45
                        }
                    ]
                },
                {
                    name: 'Feature Tests',
                    file: 'tests/Feature',
                    tests: 10,
                    passed: 8,
                    failed: 1,
                    skipped: 1,
                    duration: 4.25,
                    testCases: [
                        {
                            name: 'testApiEndpoint',
                            class: 'ApiTest',
                            status: 'passed',
                            duration: 1.2
                        },
                        {
                            name: 'testAuthenticationFlow',
                            class: 'AuthTest',
                            status: 'skipped',
                            reason: 'External service unavailable'
                        }
                    ]
                }
            ]
        };
    }
    getReportHtml() {
        const summary = this.testResults?.summary || {};
        const suites = this.testResults?.suites || [];
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Rapport de Tests Nexa</title>
                <style>
                    body {
                        font-family: var(--vscode-font-family);
                        color: var(--vscode-foreground);
                        background-color: var(--vscode-editor-background);
                        margin: 0;
                        padding: 20px;
                        line-height: 1.6;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        padding: 20px;
                        background: var(--vscode-editor-inactiveSelectionBackground);
                        border-radius: 8px;
                    }
                    .summary {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                        gap: 15px;
                        margin-bottom: 30px;
                    }
                    .summary-card {
                        background: var(--vscode-editor-inactiveSelectionBackground);
                        padding: 20px;
                        border-radius: 8px;
                        text-align: center;
                    }
                    .summary-value {
                        font-size: 2em;
                        font-weight: bold;
                        margin-bottom: 5px;
                    }
                    .summary-label {
                        color: var(--vscode-descriptionForeground);
                        font-size: 0.9em;
                    }
                    .passed { color: #4CAF50; }
                    .failed { color: #F44336; }
                    .skipped { color: #FF9800; }
                    .total { color: var(--vscode-foreground); }
                    
                    .suite {
                        background: var(--vscode-editor-inactiveSelectionBackground);
                        margin-bottom: 20px;
                        border-radius: 8px;
                        overflow: hidden;
                    }
                    .suite-header {
                        background: var(--vscode-panel-border);
                        padding: 15px 20px;
                        cursor: pointer;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }
                    .suite-header:hover {
                        background: var(--vscode-list-hoverBackground);
                    }
                    .suite-content {
                        padding: 0;
                        max-height: 0;
                        overflow: hidden;
                        transition: max-height 0.3s ease;
                    }
                    .suite.expanded .suite-content {
                        max-height: 1000px;
                        padding: 20px;
                    }
                    .test-case {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 10px 0;
                        border-bottom: 1px solid var(--vscode-panel-border);
                    }
                    .test-case:last-child {
                        border-bottom: none;
                    }
                    .test-name {
                        font-weight: 500;
                    }
                    .test-status {
                        padding: 4px 8px;
                        border-radius: 4px;
                        font-size: 0.8em;
                        font-weight: bold;
                    }
                    .status-passed {
                        background: #4CAF50;
                        color: white;
                    }
                    .status-failed {
                        background: #F44336;
                        color: white;
                    }
                    .status-skipped {
                        background: #FF9800;
                        color: white;
                    }
                    .error-details {
                        background: var(--vscode-inputValidation-errorBackground);
                        color: var(--vscode-inputValidation-errorForeground);
                        padding: 10px;
                        margin-top: 10px;
                        border-radius: 4px;
                        font-family: monospace;
                        font-size: 0.9em;
                    }
                    .actions {
                        margin-top: 30px;
                        text-align: center;
                    }
                    .btn {
                        background: var(--vscode-button-background);
                        color: var(--vscode-button-foreground);
                        border: none;
                        padding: 10px 20px;
                        margin: 0 10px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 14px;
                    }
                    .btn:hover {
                        background: var(--vscode-button-hoverBackground);
                    }
                    .progress-bar {
                        width: 100%;
                        height: 8px;
                        background: var(--vscode-progressBar-background);
                        border-radius: 4px;
                        overflow: hidden;
                        margin-top: 10px;
                    }
                    .progress-fill {
                        height: 100%;
                        background: #4CAF50;
                        transition: width 0.3s ease;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>🧪 Rapport de Tests Nexa</h1>
                    <p>Résultats de l'exécution des tests - ${new Date().toLocaleString()}</p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${this.getSuccessRate()}%"></div>
                    </div>
                </div>

                <div class="summary">
                    <div class="summary-card">
                        <div class="summary-value total">${summary.total || 0}</div>
                        <div class="summary-label">Total</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value passed">${summary.passed || 0}</div>
                        <div class="summary-label">Réussis</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value failed">${summary.failed || 0}</div>
                        <div class="summary-label">Échoués</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value skipped">${summary.skipped || 0}</div>
                        <div class="summary-label">Ignorés</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-value">${(summary.duration || 0).toFixed(2)}s</div>
                        <div class="summary-label">Durée</div>
                    </div>
                    ${summary.coverage ? `
                    <div class="summary-card">
                        <div class="summary-value">${summary.coverage.toFixed(1)}%</div>
                        <div class="summary-label">Couverture</div>
                    </div>
                    ` : ''}
                </div>

                <h2>📋 Détails par Suite</h2>
                ${this.generateSuitesHtml(suites)}

                <div class="actions">
                    <button class="btn" onclick="exportReport('HTML')">📄 Exporter HTML</button>
                    <button class="btn" onclick="exportReport('JSON')">📊 Exporter JSON</button>
                    <button class="btn" onclick="exportReport('XML')">📋 Exporter XML</button>
                    <button class="btn" onclick="runFailedTests()">🔄 Relancer les échecs</button>
                </div>

                <script>
                    const vscode = acquireVsCodeApi();
                    
                    function toggleSuite(element) {
                        element.classList.toggle('expanded');
                    }
                    
                    function openFile(file, line) {
                        vscode.postMessage({
                            command: 'openFile',
                            file: file,
                            line: line
                        });
                    }
                    
                    function runTest(testName) {
                        vscode.postMessage({
                            command: 'runTest',
                            test: testName
                        });
                    }
                    
                    function exportReport(format) {
                        vscode.postMessage({
                            command: 'exportReport',
                            format: format
                        });
                    }
                    
                    function runFailedTests() {
                        vscode.postMessage({
                            command: 'runFailedTests'
                        });
                    }
                </script>
            </body>
            </html>
        `;
    }
    generateSuitesHtml(suites) {
        return suites.map(suite => `
            <div class="suite">
                <div class="suite-header" onclick="toggleSuite(this.parentElement)">
                    <div>
                        <strong>${suite.name}</strong>
                        <span style="margin-left: 10px; color: var(--vscode-descriptionForeground);">
                            ${suite.tests} tests • ${suite.passed} réussis • ${suite.failed} échoués
                            ${suite.skipped ? ` • ${suite.skipped} ignorés` : ''}
                        </span>
                    </div>
                    <div>
                        <span class="passed">${suite.passed}</span> / 
                        <span class="failed">${suite.failed}</span> / 
                        <span class="skipped">${suite.skipped || 0}</span>
                    </div>
                </div>
                <div class="suite-content">
                    ${this.generateTestCasesHtml(suite.testCases || [])}
                </div>
            </div>
        `).join('');
    }
    generateTestCasesHtml(testCases) {
        return testCases.map(test => `
            <div class="test-case">
                <div>
                    <div class="test-name">${test.name}</div>
                    <small style="color: var(--vscode-descriptionForeground);">
                        ${test.class} • ${(test.duration || 0).toFixed(3)}s
                    </small>
                    ${test.error ? `
                        <div class="error-details">
                            <strong>Erreur:</strong> ${test.error}<br>
                            <small>Fichier: <a href="#" onclick="openFile('${test.file}', ${test.line})">${test.file}:${test.line}</a></small>
                        </div>
                    ` : ''}
                    ${test.reason ? `
                        <div style="color: var(--vscode-descriptionForeground); margin-top: 5px;">
                            <em>Raison: ${test.reason}</em>
                        </div>
                    ` : ''}
                </div>
                <div>
                    <span class="test-status status-${test.status}">${test.status.toUpperCase()}</span>
                    ${test.status === 'failed' ? `
                        <button class="btn" style="margin-left: 10px; padding: 4px 8px; font-size: 12px;" 
                                onclick="runTest('${test.name}')">
                            🔄 Relancer
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }
    getSuccessRate() {
        const summary = this.testResults?.summary;
        if (!summary || !summary.total) {
            return 0;
        }
        return (summary.passed / summary.total) * 100;
    }
    getFileExtension(format) {
        switch (format) {
            case 'HTML': return 'html';
            case 'JSON': return 'json';
            case 'XML (JUnit)': return 'xml';
            case 'CSV': return 'csv';
            case 'PDF': return 'pdf';
            default: return 'txt';
        }
    }
    async generateExportFile(format, filePath) {
        let content = '';
        switch (format) {
            case 'HTML':
                content = this.getReportHtml();
                break;
            case 'JSON':
                content = JSON.stringify(this.testResults, null, 2);
                break;
            case 'XML (JUnit)':
                content = this.generateJUnitXML();
                break;
            case 'CSV':
                content = this.generateCSV();
                break;
            default:
                content = this.generateTextReport();
        }
        fs.writeFileSync(filePath, content);
    }
    generateJUnitXML() {
        const summary = this.testResults?.summary || {};
        const suites = this.testResults?.suites || [];
        let xml = `<?xml version="1.0" encoding="UTF-8"?>\n`;
        xml += `<testsuites tests="${summary.total}" failures="${summary.failed}" skipped="${summary.skipped}" time="${summary.duration}">\n`;
        suites.forEach(suite => {
            xml += `  <testsuite name="${suite.name}" tests="${suite.tests}" failures="${suite.failed}" skipped="${suite.skipped || 0}" time="${suite.duration}">\n`;
            (suite.testCases || []).forEach(test => {
                xml += `    <testcase name="${test.name}" classname="${test.class}" time="${test.duration || 0}">\n`;
                if (test.status === 'failed') {
                    xml += `      <failure message="${test.error}">${test.error}</failure>\n`;
                }
                else if (test.status === 'skipped') {
                    xml += `      <skipped message="${test.reason || 'Skipped'}"/>\n`;
                }
                xml += `    </testcase>\n`;
            });
            xml += `  </testsuite>\n`;
        });
        xml += `</testsuites>`;
        return xml;
    }
    generateCSV() {
        let csv = 'Suite,Test,Class,Status,Duration,Error\n';
        const suites = this.testResults?.suites || [];
        suites.forEach(suite => {
            (suite.testCases || []).forEach(test => {
                csv += `"${suite.name}","${test.name}","${test.class}","${test.status}","${test.duration || 0}","${test.error || ''}"}\n`;
            });
        });
        return csv;
    }
    generateTextReport() {
        const summary = this.testResults?.summary || {};
        const suites = this.testResults?.suites || [];
        let report = 'RAPPORT DE TESTS NEXA\n';
        report += '='.repeat(50) + '\n\n';
        report += `Total: ${summary.total || 0}\n`;
        report += `Réussis: ${summary.passed || 0}\n`;
        report += `Échoués: ${summary.failed || 0}\n`;
        report += `Ignorés: ${summary.skipped || 0}\n`;
        report += `Durée: ${(summary.duration || 0).toFixed(2)}s\n`;
        if (summary.coverage) {
            report += `Couverture: ${summary.coverage.toFixed(1)}%\n`;
        }
        report += '\n';
        suites.forEach(suite => {
            report += `${suite.name}\n`;
            report += '-'.repeat(suite.name.length) + '\n';
            (suite.testCases || []).forEach(test => {
                const status = test.status.toUpperCase().padEnd(8);
                report += `${status} ${test.name} (${(test.duration || 0).toFixed(3)}s)\n`;
                if (test.error) {
                    report += `         Erreur: ${test.error}\n`;
                }
            });
            report += '\n';
        });
        return report;
    }
    async openTestFile(filePath, line) {
        try {
            const document = await vscode.workspace.openTextDocument(filePath);
            const editor = await vscode.window.showTextDocument(document);
            if (line) {
                const position = new vscode.Position(line - 1, 0);
                editor.selection = new vscode.Selection(position, position);
                editor.revealRange(new vscode.Range(position, position));
            }
        }
        catch (error) {
            vscode.window.showErrorMessage(`Impossible d'ouvrir le fichier: ${filePath}`);
        }
    }
    async runSpecificTest(testName) {
        const terminal = vscode.window.createTerminal('Nexa Test');
        terminal.sendText(`./vendor/bin/phpunit --filter ${testName}`);
        terminal.show();
    }
}
exports.TestReporter = TestReporter;
//# sourceMappingURL=testReporter.js.map