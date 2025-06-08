import * as vscode from 'vscode';

export interface ApiTheme {
    name: string;
    displayName: string;
    description: string;
    colors: {
        primary: string;
        secondary: string;
        success: string;
        warning: string;
        error: string;
        info: string;
        background: string;
        surface: string;
        text: string;
        textSecondary: string;
        border: string;
        hover: string;
        active: string;
        disabled: string;
    };
    syntax: {
        keyword: string;
        string: string;
        number: string;
        boolean: string;
        null: string;
        comment: string;
        operator: string;
        bracket: string;
        property: string;
        value: string;
    };
    ui: {
        buttonRadius: string;
        inputRadius: string;
        cardRadius: string;
        shadow: string;
        fontSize: string;
        fontFamily: string;
        lineHeight: string;
        spacing: {
            xs: string;
            sm: string;
            md: string;
            lg: string;
            xl: string;
        };
    };
}

export class ThemeManager {
    private context: vscode.ExtensionContext;
    private currentTheme: ApiTheme;
    private themes: Map<string, ApiTheme> = new Map();
    private onThemeChangedEmitter = new vscode.EventEmitter<ApiTheme>();
    public readonly onThemeChanged = this.onThemeChangedEmitter.event;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
        this.initializeDefaultThemes();
        this.currentTheme = this.getDefaultTheme();
        this.loadSavedTheme();
    }

    private initializeDefaultThemes(): void {
        // Thème sombre par défaut
        this.themes.set('dark', {
            name: 'dark',
            displayName: 'Sombre',
            description: 'Thème sombre moderne pour l\'API Tester',
            colors: {
                primary: '#007ACC',
                secondary: '#6C757D',
                success: '#28A745',
                warning: '#FFC107',
                error: '#DC3545',
                info: '#17A2B8',
                background: '#1E1E1E',
                surface: '#252526',
                text: '#CCCCCC',
                textSecondary: '#969696',
                border: '#3C3C3C',
                hover: '#2A2D2E',
                active: '#094771',
                disabled: '#656565'
            },
            syntax: {
                keyword: '#569CD6',
                string: '#CE9178',
                number: '#B5CEA8',
                boolean: '#569CD6',
                null: '#569CD6',
                comment: '#6A9955',
                operator: '#D4D4D4',
                bracket: '#FFD700',
                property: '#9CDCFE',
                value: '#CE9178'
            },
            ui: {
                buttonRadius: '4px',
                inputRadius: '4px',
                cardRadius: '6px',
                shadow: '0 2px 8px rgba(0, 0, 0, 0.3)',
                fontSize: '13px',
                fontFamily: 'Consolas, "Courier New", monospace',
                lineHeight: '1.4',
                spacing: {
                    xs: '4px',
                    sm: '8px',
                    md: '16px',
                    lg: '24px',
                    xl: '32px'
                }
            }
        });

        // Thème clair
        this.themes.set('light', {
            name: 'light',
            displayName: 'Clair',
            description: 'Thème clair moderne pour l\'API Tester',
            colors: {
                primary: '#0078D4',
                secondary: '#6C757D',
                success: '#107C10',
                warning: '#FF8C00',
                error: '#D13438',
                info: '#0078D4',
                background: '#FFFFFF',
                surface: '#F8F9FA',
                text: '#323130',
                textSecondary: '#605E5C',
                border: '#EDEBE9',
                hover: '#F3F2F1',
                active: '#DEECF9',
                disabled: '#A19F9D'
            },
            syntax: {
                keyword: '#0000FF',
                string: '#A31515',
                number: '#098658',
                boolean: '#0000FF',
                null: '#0000FF',
                comment: '#008000',
                operator: '#000000',
                bracket: '#0431FA',
                property: '#001080',
                value: '#A31515'
            },
            ui: {
                buttonRadius: '4px',
                inputRadius: '4px',
                cardRadius: '6px',
                shadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
                fontSize: '13px',
                fontFamily: 'Consolas, "Courier New", monospace',
                lineHeight: '1.4',
                spacing: {
                    xs: '4px',
                    sm: '8px',
                    md: '16px',
                    lg: '24px',
                    xl: '32px'
                }
            }
        });

        // Thème bleu océan
        this.themes.set('ocean', {
            name: 'ocean',
            displayName: 'Océan',
            description: 'Thème inspiré des profondeurs océaniques',
            colors: {
                primary: '#00BCD4',
                secondary: '#607D8B',
                success: '#4CAF50',
                warning: '#FF9800',
                error: '#F44336',
                info: '#2196F3',
                background: '#0D1117',
                surface: '#161B22',
                text: '#C9D1D9',
                textSecondary: '#8B949E',
                border: '#30363D',
                hover: '#21262D',
                active: '#1F6FEB',
                disabled: '#484F58'
            },
            syntax: {
                keyword: '#FF7B72',
                string: '#A5D6FF',
                number: '#79C0FF',
                boolean: '#FF7B72',
                null: '#FF7B72',
                comment: '#8B949E',
                operator: '#C9D1D9',
                bracket: '#FFA657',
                property: '#7EE787',
                value: '#A5D6FF'
            },
            ui: {
                buttonRadius: '6px',
                inputRadius: '6px',
                cardRadius: '8px',
                shadow: '0 4px 12px rgba(0, 188, 212, 0.2)',
                fontSize: '13px',
                fontFamily: 'Consolas, "Courier New", monospace',
                lineHeight: '1.4',
                spacing: {
                    xs: '4px',
                    sm: '8px',
                    md: '16px',
                    lg: '24px',
                    xl: '32px'
                }
            }
        });

        // Thème violet moderne
        this.themes.set('purple', {
            name: 'purple',
            displayName: 'Violet Moderne',
            description: 'Thème violet élégant et moderne',
            colors: {
                primary: '#8B5CF6',
                secondary: '#6B7280',
                success: '#10B981',
                warning: '#F59E0B',
                error: '#EF4444',
                info: '#3B82F6',
                background: '#0F0F23',
                surface: '#1A1A2E',
                text: '#E5E7EB',
                textSecondary: '#9CA3AF',
                border: '#374151',
                hover: '#252545',
                active: '#7C3AED',
                disabled: '#4B5563'
            },
            syntax: {
                keyword: '#C084FC',
                string: '#34D399',
                number: '#FBBF24',
                boolean: '#C084FC',
                null: '#C084FC',
                comment: '#6B7280',
                operator: '#E5E7EB',
                bracket: '#F472B6',
                property: '#60A5FA',
                value: '#34D399'
            },
            ui: {
                buttonRadius: '8px',
                inputRadius: '6px',
                cardRadius: '10px',
                shadow: '0 4px 16px rgba(139, 92, 246, 0.3)',
                fontSize: '13px',
                fontFamily: 'Consolas, "Courier New", monospace',
                lineHeight: '1.4',
                spacing: {
                    xs: '4px',
                    sm: '8px',
                    md: '16px',
                    lg: '24px',
                    xl: '32px'
                }
            }
        });
    }

    private getDefaultTheme(): ApiTheme {
        const vscodeTheme = vscode.window.activeColorTheme;
        
        // Détecter le thème VS Code et choisir un thème approprié
        if (vscodeTheme.kind === vscode.ColorThemeKind.Light) {
            return this.themes.get('light')!;
        } else {
            return this.themes.get('dark')!;
        }
    }

    private loadSavedTheme(): void {
        const savedThemeName = this.context.globalState.get<string>('nexa.apiTester.theme');
        if (savedThemeName && this.themes.has(savedThemeName)) {
            this.currentTheme = this.themes.get(savedThemeName)!;
        }
    }

    public getCurrentTheme(): ApiTheme {
        return this.currentTheme;
    }

    public getAvailableThemes(): ApiTheme[] {
        return Array.from(this.themes.values());
    }

    public async setTheme(themeName: string): Promise<void> {
        if (!this.themes.has(themeName)) {
            throw new Error(`Thème '${themeName}' non trouvé`);
        }

        this.currentTheme = this.themes.get(themeName)!;
        await this.context.globalState.update('nexa.apiTester.theme', themeName);
        this.onThemeChangedEmitter.fire(this.currentTheme);
    }

    public generateCSS(): string {
        const theme = this.currentTheme;
        
        return `
            :root {
                /* Couleurs principales */
                --nexa-primary: ${theme.colors.primary};
                --nexa-secondary: ${theme.colors.secondary};
                --nexa-success: ${theme.colors.success};
                --nexa-warning: ${theme.colors.warning};
                --nexa-error: ${theme.colors.error};
                --nexa-info: ${theme.colors.info};
                
                /* Couleurs de fond */
                --nexa-bg: ${theme.colors.background};
                --nexa-surface: ${theme.colors.surface};
                
                /* Couleurs de texte */
                --nexa-text: ${theme.colors.text};
                --nexa-text-secondary: ${theme.colors.textSecondary};
                
                /* Couleurs d'interface */
                --nexa-border: ${theme.colors.border};
                --nexa-hover: ${theme.colors.hover};
                --nexa-active: ${theme.colors.active};
                --nexa-disabled: ${theme.colors.disabled};
                
                /* Couleurs de syntaxe */
                --nexa-syntax-keyword: ${theme.syntax.keyword};
                --nexa-syntax-string: ${theme.syntax.string};
                --nexa-syntax-number: ${theme.syntax.number};
                --nexa-syntax-boolean: ${theme.syntax.boolean};
                --nexa-syntax-null: ${theme.syntax.null};
                --nexa-syntax-comment: ${theme.syntax.comment};
                --nexa-syntax-operator: ${theme.syntax.operator};
                --nexa-syntax-bracket: ${theme.syntax.bracket};
                --nexa-syntax-property: ${theme.syntax.property};
                --nexa-syntax-value: ${theme.syntax.value};
                
                /* Propriétés UI */
                --nexa-button-radius: ${theme.ui.buttonRadius};
                --nexa-input-radius: ${theme.ui.inputRadius};
                --nexa-card-radius: ${theme.ui.cardRadius};
                --nexa-shadow: ${theme.ui.shadow};
                --nexa-font-size: ${theme.ui.fontSize};
                --nexa-font-family: ${theme.ui.fontFamily};
                --nexa-line-height: ${theme.ui.lineHeight};
                
                /* Espacement */
                --nexa-spacing-xs: ${theme.ui.spacing.xs};
                --nexa-spacing-sm: ${theme.ui.spacing.sm};
                --nexa-spacing-md: ${theme.ui.spacing.md};
                --nexa-spacing-lg: ${theme.ui.spacing.lg};
                --nexa-spacing-xl: ${theme.ui.spacing.xl};
            }
            
            /* Styles de base */
            body {
                background-color: var(--nexa-bg);
                color: var(--nexa-text);
                font-family: var(--nexa-font-family);
                font-size: var(--nexa-font-size);
                line-height: var(--nexa-line-height);
                margin: 0;
                padding: 0;
            }
            
            /* Boutons */
            .nexa-button {
                background-color: var(--nexa-primary);
                color: white;
                border: none;
                border-radius: var(--nexa-button-radius);
                padding: var(--nexa-spacing-sm) var(--nexa-spacing-md);
                font-family: var(--nexa-font-family);
                font-size: var(--nexa-font-size);
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: var(--nexa-shadow);
            }
            
            .nexa-button:hover {
                background-color: var(--nexa-active);
                transform: translateY(-1px);
            }
            
            .nexa-button:disabled {
                background-color: var(--nexa-disabled);
                cursor: not-allowed;
                transform: none;
            }
            
            .nexa-button.secondary {
                background-color: var(--nexa-secondary);
            }
            
            .nexa-button.success {
                background-color: var(--nexa-success);
            }
            
            .nexa-button.warning {
                background-color: var(--nexa-warning);
            }
            
            .nexa-button.error {
                background-color: var(--nexa-error);
            }
            
            /* Champs de saisie */
            .nexa-input {
                background-color: var(--nexa-surface);
                color: var(--nexa-text);
                border: 1px solid var(--nexa-border);
                border-radius: var(--nexa-input-radius);
                padding: var(--nexa-spacing-sm);
                font-family: var(--nexa-font-family);
                font-size: var(--nexa-font-size);
                transition: border-color 0.2s ease;
            }
            
            .nexa-input:focus {
                outline: none;
                border-color: var(--nexa-primary);
                box-shadow: 0 0 0 2px rgba(0, 120, 204, 0.2);
            }
            
            /* Cartes */
            .nexa-card {
                background-color: var(--nexa-surface);
                border: 1px solid var(--nexa-border);
                border-radius: var(--nexa-card-radius);
                padding: var(--nexa-spacing-md);
                box-shadow: var(--nexa-shadow);
                transition: all 0.2s ease;
            }
            
            .nexa-card:hover {
                background-color: var(--nexa-hover);
                transform: translateY(-2px);
            }
            
            /* Code et syntaxe */
            .nexa-code {
                font-family: var(--nexa-font-family);
                background-color: var(--nexa-surface);
                border: 1px solid var(--nexa-border);
                border-radius: var(--nexa-input-radius);
                padding: var(--nexa-spacing-md);
                overflow-x: auto;
            }
            
            .nexa-syntax-keyword { color: var(--nexa-syntax-keyword); }
            .nexa-syntax-string { color: var(--nexa-syntax-string); }
            .nexa-syntax-number { color: var(--nexa-syntax-number); }
            .nexa-syntax-boolean { color: var(--nexa-syntax-boolean); }
            .nexa-syntax-null { color: var(--nexa-syntax-null); }
            .nexa-syntax-comment { color: var(--nexa-syntax-comment); }
            .nexa-syntax-operator { color: var(--nexa-syntax-operator); }
            .nexa-syntax-bracket { color: var(--nexa-syntax-bracket); }
            .nexa-syntax-property { color: var(--nexa-syntax-property); }
            .nexa-syntax-value { color: var(--nexa-syntax-value); }
            
            /* Messages de statut */
            .nexa-status {
                padding: var(--nexa-spacing-sm) var(--nexa-spacing-md);
                border-radius: var(--nexa-input-radius);
                margin: var(--nexa-spacing-sm) 0;
                font-weight: 500;
            }
            
            .nexa-status.success {
                background-color: rgba(40, 167, 69, 0.1);
                color: var(--nexa-success);
                border: 1px solid var(--nexa-success);
            }
            
            .nexa-status.warning {
                background-color: rgba(255, 193, 7, 0.1);
                color: var(--nexa-warning);
                border: 1px solid var(--nexa-warning);
            }
            
            .nexa-status.error {
                background-color: rgba(220, 53, 69, 0.1);
                color: var(--nexa-error);
                border: 1px solid var(--nexa-error);
            }
            
            .nexa-status.info {
                background-color: rgba(23, 162, 184, 0.1);
                color: var(--nexa-info);
                border: 1px solid var(--nexa-info);
            }
            
            /* Animations */
            @keyframes nexa-pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
            
            .nexa-loading {
                animation: nexa-pulse 1.5s ease-in-out infinite;
            }
            
            @keyframes nexa-slide-in {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .nexa-slide-in {
                animation: nexa-slide-in 0.3s ease-out;
            }
        `;
    }

    public async createCustomTheme(baseTheme: string, customizations: Partial<ApiTheme>): Promise<string> {
        if (!this.themes.has(baseTheme)) {
            throw new Error(`Thème de base '${baseTheme}' non trouvé`);
        }

        const base = this.themes.get(baseTheme)!;
        const customTheme: ApiTheme = {
            ...base,
            ...customizations,
            colors: { ...base.colors, ...customizations.colors },
            syntax: { ...base.syntax, ...customizations.syntax },
            ui: { 
                ...base.ui, 
                ...customizations.ui,
                spacing: { ...base.ui.spacing, ...customizations.ui?.spacing }
            }
        };

        const themeName = customTheme.name || `custom-${Date.now()}`;
        this.themes.set(themeName, customTheme);

        // Sauvegarder le thème personnalisé
        const customThemes = this.context.globalState.get<Record<string, ApiTheme>>('nexa.apiTester.customThemes', {});
        customThemes[themeName] = customTheme;
        await this.context.globalState.update('nexa.apiTester.customThemes', customThemes);

        return themeName;
    }

    public async deleteCustomTheme(themeName: string): Promise<void> {
        // Ne pas supprimer les thèmes par défaut
        const defaultThemes = ['dark', 'light', 'ocean', 'purple'];
        if (defaultThemes.includes(themeName)) {
            throw new Error('Impossible de supprimer un thème par défaut');
        }

        this.themes.delete(themeName);

        // Supprimer de la sauvegarde
        const customThemes = this.context.globalState.get<Record<string, ApiTheme>>('nexa.apiTester.customThemes', {});
        delete customThemes[themeName];
        await this.context.globalState.update('nexa.apiTester.customThemes', customThemes);

        // Si c'était le thème actuel, revenir au thème par défaut
        if (this.currentTheme.name === themeName) {
            await this.setTheme('dark');
        }
    }

    public async loadCustomThemes(): Promise<void> {
        const customThemes = this.context.globalState.get<Record<string, ApiTheme>>('nexa.apiTester.customThemes', {});
        
        for (const [name, theme] of Object.entries(customThemes)) {
            this.themes.set(name, theme);
        }
    }

    public exportTheme(themeName: string): string {
        const theme = this.themes.get(themeName);
        if (!theme) {
            throw new Error(`Thème '${themeName}' non trouvé`);
        }

        return JSON.stringify(theme, null, 2);
    }

    public async importTheme(themeJson: string): Promise<string> {
        try {
            const theme: ApiTheme = JSON.parse(themeJson);
            
            // Validation basique
            if (!theme.name || !theme.colors || !theme.syntax || !theme.ui) {
                throw new Error('Format de thème invalide');
            }

            const themeName = theme.name;
            this.themes.set(themeName, theme);

            // Sauvegarder comme thème personnalisé
            const customThemes = this.context.globalState.get<Record<string, ApiTheme>>('nexa.apiTester.customThemes', {});
            customThemes[themeName] = theme;
            await this.context.globalState.update('nexa.apiTester.customThemes', customThemes);

            return themeName;
        } catch (error) {
            throw new Error(`Erreur lors de l'importation du thème: ${error}`);
        }
    }

    public dispose(): void {
        this.onThemeChangedEmitter.dispose();
    }
}