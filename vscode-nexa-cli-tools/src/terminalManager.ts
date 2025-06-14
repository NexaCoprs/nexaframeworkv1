import * as vscode from 'vscode';
import * as path from 'path';

export interface TerminalSession {
    id: string;
    terminal: vscode.Terminal;
    type: 'nexa' | 'serve' | 'test' | 'general';
    isActive: boolean;
    createdAt: Date;
}

export class TerminalManager {
    private terminals: Map<string, TerminalSession> = new Map();
    private activeTerminal: TerminalSession | null = null;
    private workspaceFolder: vscode.WorkspaceFolder | undefined;

    constructor() {
        this.workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        this.setupTerminalListeners();
    }

    private setupTerminalListeners(): void {
        // Écouter la fermeture des terminaux
        vscode.window.onDidCloseTerminal((terminal) => {
            for (const [id, session] of this.terminals) {
                if (session.terminal === terminal) {
                    this.terminals.delete(id);
                    if (this.activeTerminal?.id === id) {
                        this.activeTerminal = null;
                    }
                    break;
                }
            }
        });

        // Écouter le changement de terminal actif
        vscode.window.onDidChangeActiveTerminal((terminal) => {
            if (terminal) {
                for (const session of this.terminals.values()) {
                    if (session.terminal === terminal) {
                        this.activeTerminal = session;
                        break;
                    }
                }
            }
        });
    }

    public createNexaTerminal(type: 'nexa' | 'serve' | 'test' | 'general' = 'nexa'): TerminalSession {
        const id = `nexa-${type}-${Date.now()}`;
        const name = this.getTerminalName(type);
        
        const terminal = vscode.window.createTerminal({
            name: name,
            cwd: this.workspaceFolder?.uri.fsPath,
            iconPath: new vscode.ThemeIcon('terminal'),
            color: this.getTerminalColor(type)
        });

        const session: TerminalSession = {
            id,
            terminal,
            type,
            isActive: true,
            createdAt: new Date()
        };

        this.terminals.set(id, session);
        this.activeTerminal = session;

        // Afficher le terminal
        terminal.show();

        // Envoyer une commande d'initialisation si nécessaire
        this.initializeTerminal(session);

        return session;
    }

    private getTerminalName(type: string): string {
        switch (type) {
            case 'serve':
                return 'Nexa Server';
            case 'test':
                return 'Nexa Tests';
            case 'nexa':
                return 'Nexa CLI';
            default:
                return 'Nexa Terminal';
        }
    }

    private getTerminalColor(type: string): vscode.ThemeColor {
        switch (type) {
            case 'serve':
                return new vscode.ThemeColor('terminal.ansiGreen');
            case 'test':
                return new vscode.ThemeColor('terminal.ansiYellow');
            case 'nexa':
                return new vscode.ThemeColor('terminal.ansiBlue');
            default:
                return new vscode.ThemeColor('terminal.ansiWhite');
        }
    }

    private initializeTerminal(session: TerminalSession): void {
        switch (session.type) {
            case 'nexa':
                session.terminal.sendText('echo "Terminal Nexa CLI prêt"');
                session.terminal.sendText('./nexa --help');
                break;
            case 'serve':
                session.terminal.sendText('echo "Démarrage du serveur Nexa..."');
                break;
            case 'test':
                session.terminal.sendText('echo "Terminal de tests Nexa prêt"');
                break;
        }
    }

    public getOrCreateTerminal(type: 'nexa' | 'serve' | 'test' | 'general' = 'nexa'): TerminalSession {
        // Chercher un terminal existant du même type
        for (const session of this.terminals.values()) {
            if (session.type === type && session.isActive) {
                session.terminal.show();
                this.activeTerminal = session;
                return session;
            }
        }

        // Créer un nouveau terminal si aucun n'existe
        return this.createNexaTerminal(type);
    }

    public executeCommand(command: string, type: 'nexa' | 'serve' | 'test' | 'general' = 'nexa'): void {
        const session = this.getOrCreateTerminal(type);
        session.terminal.sendText(command);
    }

    public executeNexaCommand(command: string): void {
        const fullCommand = `./nexa ${command}`;
        this.executeCommand(fullCommand, 'nexa');
    }

    public startServer(port?: number, host?: string): void {
        const session = this.getOrCreateTerminal('serve');
        let command = './nexa serve';
        
        if (port) {
            command += ` --port=${port}`;
        }
        if (host) {
            command += ` --host=${host}`;
        }
        
        session.terminal.sendText(command);
    }

    public runTests(testType?: 'unit' | 'feature' | 'all'): void {
        const session = this.getOrCreateTerminal('test');
        let command = './nexa test';
        
        if (testType && testType !== 'all') {
            command += `:${testType}`;
        }
        
        session.terminal.sendText(command);
    }

    public stopServer(): void {
        const serverSession = Array.from(this.terminals.values())
            .find(session => session.type === 'serve');
        
        if (serverSession) {
            serverSession.terminal.sendText('\u0003'); // Ctrl+C
        }
    }

    public killAllTerminals(): void {
        for (const session of this.terminals.values()) {
            session.terminal.dispose();
        }
        this.terminals.clear();
        this.activeTerminal = null;
    }

    public killTerminalsByType(type: 'nexa' | 'serve' | 'test' | 'general'): void {
        const toDelete: string[] = [];
        
        for (const [id, session] of this.terminals) {
            if (session.type === type) {
                session.terminal.dispose();
                toDelete.push(id);
            }
        }
        
        toDelete.forEach(id => this.terminals.delete(id));
        
        if (this.activeTerminal && toDelete.includes(this.activeTerminal.id)) {
            this.activeTerminal = null;
        }
    }

    public getActiveTerminals(): TerminalSession[] {
        return Array.from(this.terminals.values())
            .filter(session => session.isActive);
    }

    public getTerminalsByType(type: 'nexa' | 'serve' | 'test' | 'general'): TerminalSession[] {
        return Array.from(this.terminals.values())
            .filter(session => session.type === type);
    }

    public showTerminalSelector(): void {
        const terminals = this.getActiveTerminals();
        
        if (terminals.length === 0) {
            vscode.window.showInformationMessage('Aucun terminal Nexa actif');
            return;
        }

        const items = terminals.map(session => ({
            label: `$(terminal) ${session.terminal.name}`,
            description: `Type: ${session.type}`,
            detail: `Créé le ${session.createdAt.toLocaleString()}`,
            session
        }));

        vscode.window.showQuickPick(items, {
            placeHolder: 'Sélectionnez un terminal'
        }).then(selected => {
            if (selected) {
                selected.session.terminal.show();
                this.activeTerminal = selected.session;
            }
        });
    }

    public async sendCustomCommand(): Promise<void> {
        const command = await vscode.window.showInputBox({
            prompt: 'Entrez une commande Nexa',
            placeHolder: 'Ex: generate:handler UserHandler'
        });

        if (command) {
            this.executeNexaCommand(command);
        }
    }

    public async openTerminalWithCommand(): Promise<void> {
        const commands = [
            { label: 'Terminal CLI général', command: '' },
            { label: 'Démarrer le serveur', command: 'serve' },
            { label: 'Exécuter les tests', command: 'test' },
            { label: 'Voir l\'aide', command: '--help' },
            { label: 'Lister les routes', command: 'route:list' },
            { label: 'Vider le cache', command: 'cache:clear' }
        ];

        const selected = await vscode.window.showQuickPick(commands, {
            placeHolder: 'Sélectionnez une action'
        });

        if (selected) {
            if (selected.command === '') {
                this.createNexaTerminal('general');
            } else if (selected.command === 'serve') {
                this.startServer();
            } else if (selected.command === 'test') {
                this.runTests();
            } else {
                this.executeNexaCommand(selected.command);
            }
        }
    }

    public getTerminalStatus(): { [type: string]: number } {
        const status: { [type: string]: number } = {
            nexa: 0,
            serve: 0,
            test: 0,
            general: 0
        };

        for (const session of this.terminals.values()) {
            if (session.isActive) {
                status[session.type]++;
            }
        }

        return status;
    }

    public dispose(): void {
        this.killAllTerminals();
    }
}