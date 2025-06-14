"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.deactivate = exports.activate = void 0;
const vscode = require("vscode");
const projectGenerator_1 = require("./projectGenerator");
const templateManager_1 = require("./templateManager");
const dockerManager_1 = require("./dockerManager");
const cicdManager_1 = require("./cicdManager");
function activate(context) {
    console.log('Extension Nexa Project Generator activée');
    const projectGenerator = new projectGenerator_1.ProjectGenerator(context);
    const templateManager = new templateManager_1.TemplateManager();
    const dockerManager = new dockerManager_1.DockerManager();
    const cicdManager = new cicdManager_1.CICDManager();
    // Commandes principales
    const createProject = vscode.commands.registerCommand('nexa.projectGenerator.create', async () => {
        await projectGenerator.createNewProject();
    });
    const scaffoldProject = vscode.commands.registerCommand('nexa.projectGenerator.scaffold', async () => {
        await projectGenerator.scaffoldCurrentProject();
    });
    const addModule = vscode.commands.registerCommand('nexa.projectGenerator.addModule', async () => {
        await projectGenerator.addModule();
    });
    const setupDocker = vscode.commands.registerCommand('nexa.projectGenerator.setupDocker', async () => {
        await dockerManager.setupDocker();
    });
    const setupCICD = vscode.commands.registerCommand('nexa.projectGenerator.setupCICD', async () => {
        await cicdManager.setupCICD();
    });
    const generateAPI = vscode.commands.registerCommand('nexa.projectGenerator.generateAPI', async () => {
        await projectGenerator.generateAPI();
    });
    const generateCRUD = vscode.commands.registerCommand('nexa.projectGenerator.generateCRUD', async () => {
        await projectGenerator.generateCRUD();
    });
    const generateMicroservice = vscode.commands.registerCommand('nexa.projectGenerator.generateMicroservice', async () => {
        await projectGenerator.generateMicroservice();
    });
    const generateWebSocket = vscode.commands.registerCommand('nexa.projectGenerator.generateWebSocket', async () => {
        await projectGenerator.generateWebSocket();
    });
    const generateGraphQL = vscode.commands.registerCommand('nexa.projectGenerator.generateGraphQL', async () => {
        await projectGenerator.generateGraphQL();
    });
    const generateTests = vscode.commands.registerCommand('nexa.projectGenerator.generateTests', async () => {
        await projectGenerator.generateTests();
    });
    const generateDocs = vscode.commands.registerCommand('nexa.projectGenerator.generateDocs', async () => {
        await projectGenerator.generateDocumentation();
    });
    const listTemplates = vscode.commands.registerCommand('nexa.projectGenerator.listTemplates', async () => {
        const templates = templateManager.getAvailableTemplates();
        const templateNames = templates.map(t => `${t.name} - ${t.description}`);
        await vscode.window.showQuickPick(templateNames, {
            placeHolder: 'Templates disponibles'
        });
    });
    const applyTemplate = vscode.commands.registerCommand('nexa.projectGenerator.applyTemplate', async () => {
        const templates = templateManager.getAvailableTemplates();
        const selected = await vscode.window.showQuickPick(templates.map(t => t.name), {
            placeHolder: 'Choisissez un template à appliquer'
        });
        if (selected) {
            const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
            if (workspaceFolder) {
                await templateManager.applyTemplate(selected, workspaceFolder.uri.fsPath);
                vscode.window.showInformationMessage(`Template ${selected} appliqué avec succès!`);
            }
        }
    });
    // Enregistrement des commandes
    context.subscriptions.push(createProject, scaffoldProject, addModule, setupDocker, setupCICD, generateAPI, generateCRUD, generateMicroservice, generateWebSocket, generateGraphQL, generateTests, generateDocs, listTemplates, applyTemplate);
    // Enregistrement du provider de vues
    const projectTreeProvider = new ProjectTreeProvider();
    vscode.window.createTreeView('nexaProjectExplorer', {
        treeDataProvider: projectTreeProvider,
        showCollapseAll: true
    });
    vscode.window.showInformationMessage('Nexa Project Generator est prêt!');
}
exports.activate = activate;
function deactivate() {
    console.log('Extension Nexa Project Generator désactivée');
}
exports.deactivate = deactivate;
class ProjectTreeProvider {
    constructor() {
        this._onDidChangeTreeData = new vscode.EventEmitter();
        this.onDidChangeTreeData = this._onDidChangeTreeData.event;
    }
    refresh() {
        this._onDidChangeTreeData.fire();
    }
    getTreeItem(element) {
        return element;
    }
    getChildren(element) {
        if (!element) {
            return Promise.resolve([
                new ProjectItem('Handlers', vscode.TreeItemCollapsibleState.Collapsed, 'handlers'),
                new ProjectItem('Entities', vscode.TreeItemCollapsibleState.Collapsed, 'entities'),
                new ProjectItem('Middleware', vscode.TreeItemCollapsibleState.Collapsed, 'middleware'),
                new ProjectItem('WebSockets', vscode.TreeItemCollapsibleState.Collapsed, 'websockets'),
                new ProjectItem('GraphQL', vscode.TreeItemCollapsibleState.Collapsed, 'graphql'),
                new ProjectItem('Tests', vscode.TreeItemCollapsibleState.Collapsed, 'tests')
            ]);
        }
        return Promise.resolve([]);
    }
}
class ProjectItem extends vscode.TreeItem {
    constructor(label, collapsibleState, type) {
        super(label, collapsibleState);
        this.label = label;
        this.collapsibleState = collapsibleState;
        this.type = type;
        this.iconPath = {
            light: vscode.Uri.joinPath(vscode.Uri.file(__dirname), '..', 'resources', 'light', 'folder.svg'),
            dark: vscode.Uri.joinPath(vscode.Uri.file(__dirname), '..', 'resources', 'dark', 'folder.svg')
        };
        this.tooltip = `${this.label} - ${this.type}`;
        this.description = this.type;
    }
}
//# sourceMappingURL=extension.js.map