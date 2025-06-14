import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import { ColorPalette, ColorPaletteData } from './colorPalette';

export interface ThemeColors {
    // Editor colors
    'editor.background': string;
    'editor.foreground': string;
    'editor.lineHighlightBackground': string;
    'editor.selectionBackground': string;
    'editor.selectionHighlightBackground': string;
    'editor.findMatchBackground': string;
    'editor.findMatchHighlightBackground': string;
    'editor.wordHighlightBackground': string;
    'editor.wordHighlightStrongBackground': string;
    'editor.hoverHighlightBackground': string;
    'editorLineNumber.foreground': string;
    'editorLineNumber.activeForeground': string;
    'editorCursor.foreground': string;
    'editorWhitespace.foreground': string;
    'editorIndentGuide.background': string;
    'editorIndentGuide.activeBackground': string;
    'editorRuler.foreground': string;
    'editorCodeLens.foreground': string;
    'editorBracketMatch.background': string;
    'editorBracketMatch.border': string;
    'editorOverviewRuler.border': string;
    'editorGutter.background': string;
    'editorError.foreground': string;
    'editorWarning.foreground': string;
    'editorInfo.foreground': string;
    'editorHint.foreground': string;
    
    // Syntax highlighting
    'textMateRules': TokenColor[];
    
    // Activity Bar
    'activityBar.background': string;
    'activityBar.foreground': string;
    'activityBar.inactiveForeground': string;
    'activityBar.border': string;
    'activityBarBadge.background': string;
    'activityBarBadge.foreground': string;
    
    // Side Bar
    'sideBar.background': string;
    'sideBar.foreground': string;
    'sideBar.border': string;
    'sideBarTitle.foreground': string;
    'sideBarSectionHeader.background': string;
    'sideBarSectionHeader.foreground': string;
    'sideBarSectionHeader.border': string;
    
    // Status Bar
    'statusBar.background': string;
    'statusBar.foreground': string;
    'statusBar.border': string;
    'statusBar.debuggingBackground': string;
    'statusBar.debuggingForeground': string;
    'statusBar.noFolderBackground': string;
    'statusBar.noFolderForeground': string;
    'statusBarItem.activeBackground': string;
    'statusBarItem.hoverBackground': string;
    'statusBarItem.prominentBackground': string;
    'statusBarItem.prominentForeground': string;
    'statusBarItem.prominentHoverBackground': string;
    
    // Title Bar
    'titleBar.activeBackground': string;
    'titleBar.activeForeground': string;
    'titleBar.inactiveBackground': string;
    'titleBar.inactiveForeground': string;
    'titleBar.border': string;
    
    // Menu Bar
    'menubar.selectionForeground': string;
    'menubar.selectionBackground': string;
    'menubar.selectionBorder': string;
    'menu.foreground': string;
    'menu.background': string;
    'menu.selectionForeground': string;
    'menu.selectionBackground': string;
    'menu.selectionBorder': string;
    'menu.separatorBackground': string;
    'menu.border': string;
    
    // Tabs
    'tab.activeBackground': string;
    'tab.activeForeground': string;
    'tab.activeBorder': string;
    'tab.activeBorderTop': string;
    'tab.inactiveBackground': string;
    'tab.inactiveForeground': string;
    'tab.border': string;
    'tab.hoverBackground': string;
    'tab.hoverForeground': string;
    'tab.hoverBorder': string;
    'tab.unfocusedActiveBackground': string;
    'tab.unfocusedActiveForeground': string;
    'tab.unfocusedActiveBorder': string;
    'tab.unfocusedActiveBorderTop': string;
    'tab.unfocusedInactiveBackground': string;
    'tab.unfocusedInactiveForeground': string;
    'tab.unfocusedHoverBackground': string;
    'tab.unfocusedHoverForeground': string;
    'tab.unfocusedHoverBorder': string;
    
    // Panel
    'panel.background': string;
    'panel.border': string;
    'panelTitle.activeBorder': string;
    'panelTitle.activeForeground': string;
    'panelTitle.inactiveForeground': string;
    
    // Terminal
    'terminal.background': string;
    'terminal.foreground': string;
    'terminal.ansiBlack': string;
    'terminal.ansiRed': string;
    'terminal.ansiGreen': string;
    'terminal.ansiYellow': string;
    'terminal.ansiBlue': string;
    'terminal.ansiMagenta': string;
    'terminal.ansiCyan': string;
    'terminal.ansiWhite': string;
    'terminal.ansiBrightBlack': string;
    'terminal.ansiBrightRed': string;
    'terminal.ansiBrightGreen': string;
    'terminal.ansiBrightYellow': string;
    'terminal.ansiBrightBlue': string;
    'terminal.ansiBrightMagenta': string;
    'terminal.ansiBrightCyan': string;
    'terminal.ansiBrightWhite': string;
    
    // Lists
    'list.activeSelectionBackground': string;
    'list.activeSelectionForeground': string;
    'list.inactiveSelectionBackground': string;
    'list.inactiveSelectionForeground': string;
    'list.hoverBackground': string;
    'list.hoverForeground': string;
    'list.focusBackground': string;
    'list.focusForeground': string;
    'list.highlightForeground': string;
    'list.dropBackground': string;
    'list.errorForeground': string;
    'list.warningForeground': string;
    
    // Buttons
    'button.background': string;
    'button.foreground': string;
    'button.hoverBackground': string;
    'button.secondaryBackground': string;
    'button.secondaryForeground': string;
    'button.secondaryHoverBackground': string;
    
    // Input
    'input.background': string;
    'input.foreground': string;
    'input.border': string;
    'input.placeholderForeground': string;
    'inputOption.activeBackground': string;
    'inputOption.activeBorder': string;
    'inputOption.activeForeground': string;
    'inputValidation.errorBackground': string;
    'inputValidation.errorForeground': string;
    'inputValidation.errorBorder': string;
    'inputValidation.infoBackground': string;
    'inputValidation.infoForeground': string;
    'inputValidation.infoBorder': string;
    'inputValidation.warningBackground': string;
    'inputValidation.warningForeground': string;
    'inputValidation.warningBorder': string;
    
    // Dropdown
    'dropdown.background': string;
    'dropdown.foreground': string;
    'dropdown.border': string;
    'dropdown.listBackground': string;
    
    // Badge
    'badge.background': string;
    'badge.foreground': string;
    
    // Progress Bar
    'progressBar.background': string;
    
    // Scrollbar
    'scrollbar.shadow': string;
    'scrollbarSlider.background': string;
    'scrollbarSlider.hoverBackground': string;
    'scrollbarSlider.activeBackground': string;
    
    // Notifications
    'notificationCenter.border': string;
    'notificationCenterHeader.foreground': string;
    'notificationCenterHeader.background': string;
    'notificationToast.border': string;
    'notifications.foreground': string;
    'notifications.background': string;
    'notifications.border': string;
    'notificationLink.foreground': string;
    'notificationsErrorIcon.foreground': string;
    'notificationsWarningIcon.foreground': string;
    'notificationsInfoIcon.foreground': string;
}

export interface TokenColor {
    name?: string;
    scope: string | string[];
    settings: {
        foreground?: string;
        background?: string;
        fontStyle?: string;
    };
}

export interface ThemeDefinition {
    name: string;
    type: 'dark' | 'light' | 'hc-black' | 'hc-light';
    colors: Partial<ThemeColors>;
    tokenColors: TokenColor[];
    semanticHighlighting?: boolean;
    semanticTokenColors?: { [key: string]: string };
}

export interface ThemeTemplate {
    id: string;
    name: string;
    description: string;
    type: 'dark' | 'light';
    baseColors: {
        primary: string;
        secondary: string;
        accent: string;
        background: string;
        surface: string;
        text: string;
        textSecondary: string;
        border: string;
        error: string;
        warning: string;
        info: string;
        success: string;
    };
}

export class ThemeGenerator {
    private outputChannel: vscode.OutputChannel;
    private colorPalette: ColorPalette;

    constructor(colorPalette: ColorPalette) {
        this.outputChannel = vscode.window.createOutputChannel('Nexa Theme Generator');
        this.colorPalette = colorPalette;
    }

    public generateTheme(template: ThemeTemplate, customizations?: Partial<ThemeColors>): ThemeDefinition {
        const baseColors = this.generateBaseColors(template);
        const tokenColors = this.generateTokenColors(template);
        const semanticTokenColors = this.generateSemanticTokenColors(template);

        const theme: ThemeDefinition = {
            name: template.name,
            type: template.type,
            colors: {
                ...baseColors,
                ...customizations
            },
            tokenColors,
            semanticHighlighting: true,
            semanticTokenColors
        };

        return theme;
    }

    public generateFromColorPalette(paletteData: ColorPaletteData, themeName: string, themeType: 'dark' | 'light'): ThemeDefinition {
        // Utiliser les couleurs de la palette pour définir les couleurs de base
        const colors = paletteData.colors;
        const primary = colors[0] || '#007acc';
        const secondary = colors[1] || '#6c757d';
        const accent = colors[2] || '#28a745';
        
        const template: ThemeTemplate = {
            id: `custom-${Date.now()}`,
            name: themeName,
            description: `Thème généré à partir d'une palette personnalisée`,
            type: themeType,
            baseColors: {
                primary: primary,
                secondary: secondary,
                accent: accent,
                background: themeType === 'dark' ? '#1e1e1e' : '#ffffff',
                surface: themeType === 'dark' ? '#252526' : '#f3f3f3',
                text: themeType === 'dark' ? '#cccccc' : '#333333',
                textSecondary: themeType === 'dark' ? '#969696' : '#666666',
                border: themeType === 'dark' ? '#3e3e42' : '#e1e1e1',
                error: '#f14c4c',
                warning: '#ffcc02',
                info: '#0e639c',
                success: '#89d185'
            }
        };

        return this.generateTheme(template);
    }

    public async saveTheme(theme: ThemeDefinition, outputPath?: string): Promise<string> {
        const themeJson = JSON.stringify(theme, null, 2);
        
        if (!outputPath) {
            const workspaceFolders = vscode.workspace.workspaceFolders;
            if (!workspaceFolders) {
                throw new Error('Aucun workspace ouvert pour sauvegarder le thème');
            }
            
            const themesDir = path.join(workspaceFolders[0].uri.fsPath, '.vscode', 'themes');
            if (!fs.existsSync(themesDir)) {
                await fs.promises.mkdir(themesDir, { recursive: true });
            }
            
            outputPath = path.join(themesDir, `${theme.name.toLowerCase().replace(/\s+/g, '-')}-color-theme.json`);
        }
        
        await fs.promises.writeFile(outputPath, themeJson, 'utf8');
        
        this.outputChannel.appendLine(`Thème sauvegardé: ${outputPath}`);
        return outputPath;
    }

    public async installTheme(theme: ThemeDefinition): Promise<void> {
        const extensionsPath = this.getExtensionsPath();
        const themeExtensionPath = path.join(extensionsPath, `nexa-theme-${theme.name.toLowerCase().replace(/\s+/g, '-')}`);
        
        // Create extension directory
        if (!fs.existsSync(themeExtensionPath)) {
            await fs.promises.mkdir(themeExtensionPath, { recursive: true });
        }
        
        // Create package.json
        const packageJson = {
            name: `nexa-theme-${theme.name.toLowerCase().replace(/\s+/g, '-')}`,
            displayName: `Nexa Theme: ${theme.name}`,
            description: `Thème ${theme.name} généré par Nexa Theme Designer`,
            version: '1.0.0',
            publisher: 'nexa',
            engines: {
                vscode: '^1.74.0'
            },
            categories: ['Themes'],
            contributes: {
                themes: [
                    {
                        label: theme.name,
                        uiTheme: this.getUITheme(theme.type),
                        path: './themes/theme.json'
                    }
                ]
            }
        };
        
        await fs.promises.writeFile(
            path.join(themeExtensionPath, 'package.json'),
            JSON.stringify(packageJson, null, 2),
            'utf8'
        );
        
        // Create themes directory
        const themesDir = path.join(themeExtensionPath, 'themes');
        if (!fs.existsSync(themesDir)) {
            await fs.promises.mkdir(themesDir, { recursive: true });
        }
        
        // Save theme file
        await fs.promises.writeFile(
            path.join(themesDir, 'theme.json'),
            JSON.stringify(theme, null, 2),
            'utf8'
        );
        
        this.outputChannel.appendLine(`Thème installé: ${themeExtensionPath}`);
        
        // Suggest reloading VS Code
        const reload = await vscode.window.showInformationMessage(
            'Thème installé avec succès! Voulez-vous recharger VS Code pour l\'activer?',
            'Recharger',
            'Plus tard'
        );
        
        if (reload === 'Recharger') {
            vscode.commands.executeCommand('workbench.action.reloadWindow');
        }
    }

    public getBuiltInTemplates(): ThemeTemplate[] {
        return [
            {
                id: 'nexa-dark',
                name: 'Nexa Dark',
                description: 'Thème sombre moderne avec des accents bleus',
                type: 'dark',
                baseColors: {
                    primary: '#007acc',
                    secondary: '#1e1e1e',
                    accent: '#0e639c',
                    background: '#1e1e1e',
                    surface: '#252526',
                    text: '#cccccc',
                    textSecondary: '#969696',
                    border: '#3e3e42',
                    error: '#f14c4c',
                    warning: '#ffcc02',
                    info: '#0e639c',
                    success: '#89d185'
                }
            },
            {
                id: 'nexa-light',
                name: 'Nexa Light',
                description: 'Thème clair moderne avec des accents bleus',
                type: 'light',
                baseColors: {
                    primary: '#0066cc',
                    secondary: '#ffffff',
                    accent: '#005a9e',
                    background: '#ffffff',
                    surface: '#f3f3f3',
                    text: '#333333',
                    textSecondary: '#666666',
                    border: '#e1e1e1',
                    error: '#d73a49',
                    warning: '#f66a0a',
                    info: '#0366d6',
                    success: '#28a745'
                }
            },
            {
                id: 'nexa-purple',
                name: 'Nexa Purple',
                description: 'Thème sombre avec des accents violets',
                type: 'dark',
                baseColors: {
                    primary: '#8b5cf6',
                    secondary: '#1a1a2e',
                    accent: '#a855f7',
                    background: '#1a1a2e',
                    surface: '#16213e',
                    text: '#e2e8f0',
                    textSecondary: '#94a3b8',
                    border: '#334155',
                    error: '#ef4444',
                    warning: '#f59e0b',
                    info: '#3b82f6',
                    success: '#10b981'
                }
            },
            {
                id: 'nexa-green',
                name: 'Nexa Green',
                description: 'Thème sombre avec des accents verts',
                type: 'dark',
                baseColors: {
                    primary: '#10b981',
                    secondary: '#0f172a',
                    accent: '#059669',
                    background: '#0f172a',
                    surface: '#1e293b',
                    text: '#f1f5f9',
                    textSecondary: '#94a3b8',
                    border: '#334155',
                    error: '#ef4444',
                    warning: '#f59e0b',
                    info: '#3b82f6',
                    success: '#10b981'
                }
            },
            {
                id: 'nexa-ocean',
                name: 'Nexa Ocean',
                description: 'Thème sombre inspiré de l\'océan',
                type: 'dark',
                baseColors: {
                    primary: '#06b6d4',
                    secondary: '#0c1821',
                    accent: '#0891b2',
                    background: '#0c1821',
                    surface: '#1e2a3a',
                    text: '#e2e8f0',
                    textSecondary: '#94a3b8',
                    border: '#334155',
                    error: '#ef4444',
                    warning: '#f59e0b',
                    info: '#3b82f6',
                    success: '#10b981'
                }
            },
            {
                id: 'nexa-sunset',
                name: 'Nexa Sunset',
                description: 'Thème sombre avec des couleurs chaudes',
                type: 'dark',
                baseColors: {
                    primary: '#f97316',
                    secondary: '#1c1917',
                    accent: '#ea580c',
                    background: '#1c1917',
                    surface: '#292524',
                    text: '#fafaf9',
                    textSecondary: '#a8a29e',
                    border: '#44403c',
                    error: '#ef4444',
                    warning: '#f59e0b',
                    info: '#3b82f6',
                    success: '#10b981'
                }
            }
        ];
    }

    private generateBaseColors(template: ThemeTemplate): Partial<ThemeColors> {
        const { baseColors } = template;
        const isDark = template.type === 'dark';
        
        return {
            // Editor
            'editor.background': baseColors.background,
            'editor.foreground': baseColors.text,
            'editor.lineHighlightBackground': this.adjustOpacity(baseColors.surface, 0.5),
            'editor.selectionBackground': this.adjustOpacity(baseColors.primary, 0.3),
            'editor.selectionHighlightBackground': this.adjustOpacity(baseColors.primary, 0.2),
            'editor.findMatchBackground': this.adjustOpacity(baseColors.accent, 0.4),
            'editor.findMatchHighlightBackground': this.adjustOpacity(baseColors.accent, 0.2),
            'editor.wordHighlightBackground': this.adjustOpacity(baseColors.primary, 0.2),
            'editor.wordHighlightStrongBackground': this.adjustOpacity(baseColors.primary, 0.3),
            'editor.hoverHighlightBackground': this.adjustOpacity(baseColors.surface, 0.8),
            'editorLineNumber.foreground': baseColors.textSecondary,
            'editorLineNumber.activeForeground': baseColors.text,
            'editorCursor.foreground': baseColors.primary,
            'editorWhitespace.foreground': this.adjustOpacity(baseColors.textSecondary, 0.3),
            'editorIndentGuide.background': this.adjustOpacity(baseColors.border, 0.5),
            'editorIndentGuide.activeBackground': baseColors.border,
            'editorRuler.foreground': baseColors.border,
            'editorCodeLens.foreground': baseColors.textSecondary,
            'editorBracketMatch.background': this.adjustOpacity(baseColors.primary, 0.2),
            'editorBracketMatch.border': baseColors.primary,
            'editorOverviewRuler.border': baseColors.border,
            'editorGutter.background': baseColors.background,
            'editorError.foreground': baseColors.error,
            'editorWarning.foreground': baseColors.warning,
            'editorInfo.foreground': baseColors.info,
            'editorHint.foreground': baseColors.info,
            
            // Activity Bar
            'activityBar.background': baseColors.surface,
            'activityBar.foreground': baseColors.text,
            'activityBar.inactiveForeground': baseColors.textSecondary,
            'activityBar.border': baseColors.border,
            'activityBarBadge.background': baseColors.primary,
            'activityBarBadge.foreground': isDark ? '#ffffff' : '#000000',
            
            // Side Bar
            'sideBar.background': baseColors.surface,
            'sideBar.foreground': baseColors.text,
            'sideBar.border': baseColors.border,
            'sideBarTitle.foreground': baseColors.text,
            'sideBarSectionHeader.background': this.adjustOpacity(baseColors.primary, 0.1),
            'sideBarSectionHeader.foreground': baseColors.text,
            'sideBarSectionHeader.border': baseColors.border,
            
            // Status Bar
            'statusBar.background': baseColors.primary,
            'statusBar.foreground': isDark ? '#ffffff' : '#000000',
            'statusBar.border': baseColors.border,
            'statusBar.debuggingBackground': baseColors.warning,
            'statusBar.debuggingForeground': '#000000',
            'statusBar.noFolderBackground': baseColors.surface,
            'statusBar.noFolderForeground': baseColors.text,
            'statusBarItem.activeBackground': this.adjustOpacity('#ffffff', 0.2),
            'statusBarItem.hoverBackground': this.adjustOpacity('#ffffff', 0.1),
            'statusBarItem.prominentBackground': baseColors.accent,
            'statusBarItem.prominentForeground': isDark ? '#ffffff' : '#000000',
            'statusBarItem.prominentHoverBackground': this.lighten(baseColors.accent, 10),
            
            // Title Bar
            'titleBar.activeBackground': baseColors.surface,
            'titleBar.activeForeground': baseColors.text,
            'titleBar.inactiveBackground': baseColors.surface,
            'titleBar.inactiveForeground': baseColors.textSecondary,
            'titleBar.border': baseColors.border,
            
            // Menu Bar
            'menubar.selectionForeground': baseColors.text,
            'menubar.selectionBackground': this.adjustOpacity(baseColors.primary, 0.2),
            'menubar.selectionBorder': baseColors.primary,
            'menu.foreground': baseColors.text,
            'menu.background': baseColors.surface,
            'menu.selectionForeground': baseColors.text,
            'menu.selectionBackground': this.adjustOpacity(baseColors.primary, 0.2),
            'menu.selectionBorder': baseColors.primary,
            'menu.separatorBackground': baseColors.border,
            'menu.border': baseColors.border,
            
            // Tabs
            'tab.activeBackground': baseColors.background,
            'tab.activeForeground': baseColors.text,
            'tab.activeBorder': 'transparent',
            'tab.activeBorderTop': baseColors.primary,
            'tab.inactiveBackground': baseColors.surface,
            'tab.inactiveForeground': baseColors.textSecondary,
            'tab.border': baseColors.border,
            'tab.hoverBackground': this.adjustOpacity(baseColors.background, 0.8),
            'tab.hoverForeground': baseColors.text,
            'tab.hoverBorder': 'transparent',
            'tab.unfocusedActiveBackground': baseColors.background,
            'tab.unfocusedActiveForeground': baseColors.textSecondary,
            'tab.unfocusedActiveBorder': 'transparent',
            'tab.unfocusedActiveBorderTop': baseColors.textSecondary,
            'tab.unfocusedInactiveBackground': baseColors.surface,
            'tab.unfocusedInactiveForeground': baseColors.textSecondary,
            'tab.unfocusedHoverBackground': this.adjustOpacity(baseColors.background, 0.8),
            'tab.unfocusedHoverForeground': baseColors.textSecondary,
            'tab.unfocusedHoverBorder': 'transparent',
            
            // Panel
            'panel.background': baseColors.background,
            'panel.border': baseColors.border,
            'panelTitle.activeBorder': baseColors.primary,
            'panelTitle.activeForeground': baseColors.text,
            'panelTitle.inactiveForeground': baseColors.textSecondary,
            
            // Terminal
            'terminal.background': baseColors.background,
            'terminal.foreground': baseColors.text,
            'terminal.ansiBlack': isDark ? '#000000' : '#000000',
            'terminal.ansiRed': baseColors.error,
            'terminal.ansiGreen': baseColors.success,
            'terminal.ansiYellow': baseColors.warning,
            'terminal.ansiBlue': baseColors.info,
            'terminal.ansiMagenta': baseColors.accent,
            'terminal.ansiCyan': baseColors.primary,
            'terminal.ansiWhite': isDark ? '#ffffff' : '#ffffff',
            'terminal.ansiBrightBlack': baseColors.textSecondary,
            'terminal.ansiBrightRed': this.lighten(baseColors.error, 20),
            'terminal.ansiBrightGreen': this.lighten(baseColors.success, 20),
            'terminal.ansiBrightYellow': this.lighten(baseColors.warning, 20),
            'terminal.ansiBrightBlue': this.lighten(baseColors.info, 20),
            'terminal.ansiBrightMagenta': this.lighten(baseColors.accent, 20),
            'terminal.ansiBrightCyan': this.lighten(baseColors.primary, 20),
            'terminal.ansiBrightWhite': baseColors.text,
            
            // Lists
            'list.activeSelectionBackground': this.adjustOpacity(baseColors.primary, 0.3),
            'list.activeSelectionForeground': baseColors.text,
            'list.inactiveSelectionBackground': this.adjustOpacity(baseColors.primary, 0.2),
            'list.inactiveSelectionForeground': baseColors.text,
            'list.hoverBackground': this.adjustOpacity(baseColors.primary, 0.1),
            'list.hoverForeground': baseColors.text,
            'list.focusBackground': this.adjustOpacity(baseColors.primary, 0.2),
            'list.focusForeground': baseColors.text,
            'list.highlightForeground': baseColors.primary,
            'list.dropBackground': this.adjustOpacity(baseColors.primary, 0.4),
            'list.errorForeground': baseColors.error,
            'list.warningForeground': baseColors.warning,
            
            // Buttons
            'button.background': baseColors.primary,
            'button.foreground': isDark ? '#ffffff' : '#000000',
            'button.hoverBackground': this.lighten(baseColors.primary, 10),
            'button.secondaryBackground': baseColors.surface,
            'button.secondaryForeground': baseColors.text,
            'button.secondaryHoverBackground': this.lighten(baseColors.surface, 10),
            
            // Input
            'input.background': baseColors.surface,
            'input.foreground': baseColors.text,
            'input.border': baseColors.border,
            'input.placeholderForeground': baseColors.textSecondary,
            'inputOption.activeBackground': baseColors.primary,
            'inputOption.activeBorder': baseColors.primary,
            'inputOption.activeForeground': isDark ? '#ffffff' : '#000000',
            'inputValidation.errorBackground': this.adjustOpacity(baseColors.error, 0.1),
            'inputValidation.errorForeground': baseColors.error,
            'inputValidation.errorBorder': baseColors.error,
            'inputValidation.infoBackground': this.adjustOpacity(baseColors.info, 0.1),
            'inputValidation.infoForeground': baseColors.info,
            'inputValidation.infoBorder': baseColors.info,
            'inputValidation.warningBackground': this.adjustOpacity(baseColors.warning, 0.1),
            'inputValidation.warningForeground': baseColors.warning,
            'inputValidation.warningBorder': baseColors.warning,
            
            // Dropdown
            'dropdown.background': baseColors.surface,
            'dropdown.foreground': baseColors.text,
            'dropdown.border': baseColors.border,
            'dropdown.listBackground': baseColors.background,
            
            // Badge
            'badge.background': baseColors.primary,
            'badge.foreground': isDark ? '#ffffff' : '#000000',
            
            // Progress Bar
            'progressBar.background': baseColors.primary,
            
            // Scrollbar
            'scrollbar.shadow': this.adjustOpacity('#000000', 0.6),
            'scrollbarSlider.background': this.adjustOpacity(baseColors.textSecondary, 0.4),
            'scrollbarSlider.hoverBackground': this.adjustOpacity(baseColors.textSecondary, 0.7),
            'scrollbarSlider.activeBackground': this.adjustOpacity(baseColors.primary, 0.8),
            
            // Notifications
            'notificationCenter.border': baseColors.border,
            'notificationCenterHeader.foreground': baseColors.text,
            'notificationCenterHeader.background': baseColors.surface,
            'notificationToast.border': baseColors.border,
            'notifications.foreground': baseColors.text,
            'notifications.background': baseColors.surface,
            'notifications.border': baseColors.border,
            'notificationLink.foreground': baseColors.primary,
            'notificationsErrorIcon.foreground': baseColors.error,
            'notificationsWarningIcon.foreground': baseColors.warning,
            'notificationsInfoIcon.foreground': baseColors.info
        };
    }

    private generateTokenColors(template: ThemeTemplate): TokenColor[] {
        const { baseColors } = template;
        
        return [
            {
                name: 'Comment',
                scope: ['comment', 'punctuation.definition.comment'],
                settings: {
                    foreground: baseColors.textSecondary,
                    fontStyle: 'italic'
                }
            },
            {
                name: 'Variables',
                scope: ['variable', 'string constant.other.placeholder'],
                settings: {
                    foreground: baseColors.text
                }
            },
            {
                name: 'Colors',
                scope: ['constant.other.color'],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'Invalid',
                scope: ['invalid', 'invalid.illegal'],
                settings: {
                    foreground: baseColors.error
                }
            },
            {
                name: 'Keyword, Storage',
                scope: ['keyword', 'storage.type', 'storage.modifier'],
                settings: {
                    foreground: baseColors.primary
                }
            },
            {
                name: 'Operator, Misc',
                scope: [
                    'keyword.control',
                    'constant.other.color',
                    'punctuation',
                    'meta.tag',
                    'punctuation.definition.tag',
                    'punctuation.separator.inheritance.php',
                    'punctuation.definition.tag.html',
                    'punctuation.definition.tag.begin.html',
                    'punctuation.definition.tag.end.html',
                    'punctuation.section.embedded',
                    'keyword.other.template',
                    'keyword.other.substitution'
                ],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'Tag',
                scope: [
                    'entity.name.tag',
                    'meta.tag.sgml',
                    'markup.deleted.git_gutter'
                ],
                settings: {
                    foreground: baseColors.error
                }
            },
            {
                name: 'Function, Special Method',
                scope: [
                    'entity.name.function',
                    'meta.function-call',
                    'variable.function',
                    'support.function',
                    'keyword.other.special-method'
                ],
                settings: {
                    foreground: baseColors.warning
                }
            },
            {
                name: 'Block Level Variables',
                scope: ['meta.block variable.other'],
                settings: {
                    foreground: baseColors.text
                }
            },
            {
                name: 'Other Variable, String Link',
                scope: ['support.other.variable', 'string.other.link'],
                settings: {
                    foreground: baseColors.error
                }
            },
            {
                name: 'Number, Constant, Function Argument, Tag Attribute, Embedded',
                scope: [
                    'constant.numeric',
                    'constant.language',
                    'support.constant',
                    'constant.character',
                    'constant.escape',
                    'variable.parameter',
                    'keyword.other.unit',
                    'keyword.other'
                ],
                settings: {
                    foreground: baseColors.info
                }
            },
            {
                name: 'String, Symbols, Inherited Class, Markup Heading',
                scope: [
                    'string',
                    'constant.other.symbol',
                    'constant.other.key',
                    'entity.other.inherited-class',
                    'markup.heading',
                    'markup.inserted.git_gutter',
                    'meta.group.braces.curly constant.other.object.key.js string.unquoted.label.js'
                ],
                settings: {
                    foreground: baseColors.success
                }
            },
            {
                name: 'Class, Support',
                scope: [
                    'entity.name',
                    'support.type',
                    'support.class',
                    'support.other.namespace.use.php',
                    'meta.use.php',
                    'support.other.namespace.php',
                    'markup.changed.git_gutter',
                    'support.type.sys-types'
                ],
                settings: {
                    foreground: baseColors.warning
                }
            },
            {
                name: 'Entity Types',
                scope: ['support.type'],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'CSS Class and Support',
                scope: [
                    'source.css support.type.property-name',
                    'source.sass support.type.property-name',
                    'source.scss support.type.property-name',
                    'source.less support.type.property-name',
                    'source.stylus support.type.property-name',
                    'source.postcss support.type.property-name'
                ],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'Sub-methods',
                scope: [
                    'entity.name.module.js',
                    'variable.import.parameter.js',
                    'variable.other.class.js'
                ],
                settings: {
                    foreground: baseColors.error
                }
            },
            {
                name: 'Language methods',
                scope: ['variable.language'],
                settings: {
                    foreground: baseColors.error,
                    fontStyle: 'italic'
                }
            },
            {
                name: 'entity.name.method.js',
                scope: ['entity.name.method.js'],
                settings: {
                    foreground: baseColors.warning
                }
            },
            {
                name: 'meta.method.js',
                scope: [
                    'meta.class-method.js entity.name.function.js',
                    'variable.function.constructor'
                ],
                settings: {
                    foreground: baseColors.warning
                }
            },
            {
                name: 'Attributes',
                scope: ['entity.other.attribute-name'],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'HTML Attributes',
                scope: [
                    'text.html.basic entity.other.attribute-name.html',
                    'text.html.basic entity.other.attribute-name'
                ],
                settings: {
                    foreground: baseColors.warning,
                    fontStyle: 'italic'
                }
            },
            {
                name: 'CSS Classes',
                scope: ['entity.other.attribute-name.class'],
                settings: {
                    foreground: baseColors.warning
                }
            },
            {
                name: 'CSS ID\'s',
                scope: ['source.sass keyword.control'],
                settings: {
                    foreground: baseColors.warning
                }
            },
            {
                name: 'Inserted',
                scope: ['markup.inserted'],
                settings: {
                    foreground: baseColors.success
                }
            },
            {
                name: 'Deleted',
                scope: ['markup.deleted'],
                settings: {
                    foreground: baseColors.error
                }
            },
            {
                name: 'Changed',
                scope: ['markup.changed'],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'Regular Expressions',
                scope: ['string.regexp'],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'Escape Characters',
                scope: ['constant.character.escape'],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'URL',
                scope: ['*url*', '*link*', '*uri*'],
                settings: {
                    fontStyle: 'underline'
                }
            },
            {
                name: 'Decorators',
                scope: [
                    'tag.decorator.js entity.name.tag.js',
                    'tag.decorator.js punctuation.definition.tag.js'
                ],
                settings: {
                    foreground: baseColors.warning,
                    fontStyle: 'italic'
                }
            },
            {
                name: 'ES7 Bind Operator',
                scope: [
                    'source.js constant.other.object.key.js string.unquoted.label.js'
                ],
                settings: {
                    foreground: baseColors.error,
                    fontStyle: 'italic'
                }
            },
            {
                name: 'JSON Key - Level 0',
                scope: [
                    'source.json meta.structure.dictionary.json support.type.property-name.json'
                ],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'JSON Key - Level 1',
                scope: [
                    'source.json meta.structure.dictionary.json meta.structure.dictionary.value.json meta.structure.dictionary.json support.type.property-name.json'
                ],
                settings: {
                    foreground: baseColors.warning
                }
            },
            {
                name: 'JSON Key - Level 2',
                scope: [
                    'source.json meta.structure.dictionary.json meta.structure.dictionary.value.json meta.structure.dictionary.json meta.structure.dictionary.value.json meta.structure.dictionary.json support.type.property-name.json'
                ],
                settings: {
                    foreground: baseColors.info
                }
            },
            {
                name: 'JSON Key - Level 3',
                scope: [
                    'source.json meta.structure.dictionary.json meta.structure.dictionary.value.json meta.structure.dictionary.json meta.structure.dictionary.value.json meta.structure.dictionary.json meta.structure.dictionary.value.json meta.structure.dictionary.json support.type.property-name.json'
                ],
                settings: {
                    foreground: baseColors.error
                }
            },
            {
                name: 'Markdown - Plain',
                scope: [
                    'text.html.markdown',
                    'punctuation.definition.list_item.markdown'
                ],
                settings: {
                    foreground: baseColors.text
                }
            },
            {
                name: 'Markdown - Markup Raw Inline',
                scope: ['text.html.markdown markup.inline.raw.markdown'],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'Markdown - Markup Raw Inline Punctuation',
                scope: [
                    'text.html.markdown markup.inline.raw.markdown punctuation.definition.raw.markdown'
                ],
                settings: {
                    foreground: baseColors.textSecondary
                }
            },
            {
                name: 'Markdown - Heading',
                scope: [
                    'markdown.heading',
                    'markup.heading | markup.heading entity.name',
                    'markup.heading.markdown punctuation.definition.heading.markdown'
                ],
                settings: {
                    foreground: baseColors.success
                }
            },
            {
                name: 'Markup - Italic',
                scope: ['markup.italic'],
                settings: {
                    fontStyle: 'italic',
                    foreground: baseColors.warning
                }
            },
            {
                name: 'Markup - Bold',
                scope: ['markup.bold', 'markup.bold string'],
                settings: {
                    fontStyle: 'bold',
                    foreground: baseColors.warning
                }
            },
            {
                name: 'Markup - Bold-Italic',
                scope: [
                    'markup.bold markup.italic',
                    'markup.italic markup.bold',
                    'markup.quote markup.bold',
                    'markup.bold markup.italic string',
                    'markup.italic markup.bold string',
                    'markup.quote markup.bold string'
                ],
                settings: {
                    fontStyle: 'bold',
                    foreground: baseColors.warning
                }
            },
            {
                name: 'Markup - Underline',
                scope: ['markup.underline'],
                settings: {
                    fontStyle: 'underline',
                    foreground: baseColors.info
                }
            },
            {
                name: 'Markdown - Blockquote',
                scope: ['markup.quote punctuation.definition.blockquote.markdown'],
                settings: {
                    foreground: baseColors.textSecondary
                }
            },
            {
                name: 'Markup - Quote',
                scope: ['markup.quote'],
                settings: {
                    fontStyle: 'italic'
                }
            },
            {
                name: 'Markdown - Link',
                scope: ['string.other.link.title.markdown'],
                settings: {
                    foreground: baseColors.warning
                }
            },
            {
                name: 'Markdown - Link Description',
                scope: ['string.other.link.description.title.markdown'],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'Markdown - Link Anchor',
                scope: ['constant.other.reference.link.markdown'],
                settings: {
                    foreground: baseColors.info
                }
            },
            {
                name: 'Markup - Raw Block',
                scope: ['markup.raw.block'],
                settings: {
                    foreground: baseColors.accent
                }
            },
            {
                name: 'Markdown - Raw Block Fenced',
                scope: ['markup.raw.block.fenced.markdown'],
                settings: {
                    foreground: baseColors.text
                }
            },
            {
                name: 'Markdown - Fenced Bode Block',
                scope: ['punctuation.definition.fenced.markdown'],
                settings: {
                    foreground: baseColors.textSecondary
                }
            },
            {
                name: 'Markdown - Fenced Bode Block Variable',
                scope: [
                    'markup.raw.block.fenced.markdown',
                    'variable.language.fenced.markdown',
                    'punctuation.section.class.end'
                ],
                settings: {
                    foreground: baseColors.text
                }
            },
            {
                name: 'Markdown - Fenced Language',
                scope: ['variable.language.fenced.markdown'],
                settings: {
                    foreground: baseColors.textSecondary
                }
            },
            {
                name: 'Markdown - Separator',
                scope: ['meta.separator'],
                settings: {
                    fontStyle: 'bold',
                    foreground: baseColors.textSecondary
                }
            },
            {
                name: 'Markup - Table',
                scope: ['markup.table'],
                settings: {
                    foreground: baseColors.text
                }
            }
        ];
    }

    private generateSemanticTokenColors(template: ThemeTemplate): { [key: string]: string } {
        const { baseColors } = template;
        
        return {
            'variable': baseColors.text,
            'variable.readonly': baseColors.accent,
            'parameter': baseColors.info,
            'function': baseColors.warning,
            'method': baseColors.warning,
            'class': baseColors.warning,
            'interface': baseColors.accent,
            'enum': baseColors.accent,
            'enumMember': baseColors.info,
            'type': baseColors.accent,
            'typeParameter': baseColors.accent,
            'namespace': baseColors.accent,
            'property': baseColors.text,
            'property.readonly': baseColors.accent,
            'macro': baseColors.primary,
            'keyword': baseColors.primary,
            'comment': baseColors.textSecondary,
            'string': baseColors.success,
            'number': baseColors.info,
            'regexp': baseColors.accent,
            'operator': baseColors.accent
        };
    }

    private getUITheme(type: 'dark' | 'light' | 'hc-black' | 'hc-light'): string {
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

    private getExtensionsPath(): string {
        const os = require('os');
        const platform = os.platform();
        const homeDir = os.homedir();
        
        switch (platform) {
            case 'win32':
                return path.join(homeDir, '.vscode', 'extensions');
            case 'darwin':
                return path.join(homeDir, '.vscode', 'extensions');
            default:
                return path.join(homeDir, '.vscode', 'extensions');
        }
    }

    private adjustOpacity(color: string, opacity: number): string {
        // Convert hex to rgba
        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        
        return `rgba(${r}, ${g}, ${b}, ${opacity})`;
    }

    private lighten(color: string, percent: number): string {
        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        
        const newR = Math.min(255, Math.floor(r + (255 - r) * (percent / 100)));
        const newG = Math.min(255, Math.floor(g + (255 - g) * (percent / 100)));
        const newB = Math.min(255, Math.floor(b + (255 - b) * (percent / 100)));
        
        return `#${newR.toString(16).padStart(2, '0')}${newG.toString(16).padStart(2, '0')}${newB.toString(16).padStart(2, '0')}`;
    }

    private darken(color: string, percent: number): string {
        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        
        const newR = Math.max(0, Math.floor(r * (1 - percent / 100)));
        const newG = Math.max(0, Math.floor(g * (1 - percent / 100)));
        const newB = Math.max(0, Math.floor(b * (1 - percent / 100)));
        
        return `#${newR.toString(16).padStart(2, '0')}${newG.toString(16).padStart(2, '0')}${newB.toString(16).padStart(2, '0')}`;
    }

    public exportThemePackage(theme: ThemeDefinition, outputDir: string): Promise<string> {
        return new Promise(async (resolve, reject) => {
            try {
                const packageName = `nexa-theme-${theme.name.toLowerCase().replace(/\s+/g, '-')}`;
                const packageDir = path.join(outputDir, packageName);
                
                // Create package directory
                if (!fs.existsSync(packageDir)) {
                    await fs.promises.mkdir(packageDir, { recursive: true });
                }
                
                // Create package.json
                const packageJson = {
                    name: packageName,
                    displayName: `Nexa Theme: ${theme.name}`,
                    description: `Thème ${theme.name} généré par Nexa Theme Designer`,
                    version: '1.0.0',
                    publisher: 'nexa',
                    engines: {
                        vscode: '^1.74.0'
                    },
                    categories: ['Themes'],
                    keywords: ['theme', 'color-theme', 'nexa'],
                    contributes: {
                        themes: [
                            {
                                label: theme.name,
                                uiTheme: this.getUITheme(theme.type),
                                path: './themes/theme.json'
                            }
                        ]
                    },
                    repository: {
                        type: 'git',
                        url: 'https://github.com/nexa/themes'
                    },
                    bugs: {
                        url: 'https://github.com/nexa/themes/issues'
                    },
                    homepage: 'https://github.com/nexa/themes#readme'
                };
                
                await fs.promises.writeFile(
                    path.join(packageDir, 'package.json'),
                    JSON.stringify(packageJson, null, 2),
                    'utf8'
                );
                
                // Create themes directory
                const themesDir = path.join(packageDir, 'themes');
                if (!fs.existsSync(themesDir)) {
                    await fs.promises.mkdir(themesDir, { recursive: true });
                }
                
                // Save theme file
                await fs.promises.writeFile(
                    path.join(themesDir, 'theme.json'),
                    JSON.stringify(theme, null, 2),
                    'utf8'
                );
                
                // Create README.md
                const readme = `# ${theme.name} Theme

${theme.name} est un thème ${theme.type === 'dark' ? 'sombre' : 'clair'} généré par Nexa Theme Designer.

## Installation

1. Ouvrez VS Code
2. Allez dans Extensions (Ctrl+Shift+X)
3. Recherchez "${packageName}"
4. Installez le thème
5. Allez dans File > Preferences > Color Theme
6. Sélectionnez "${theme.name}"

## Développé avec

- [Nexa Theme Designer](https://github.com/nexa/vscode-extensions)
- [VS Code Theme API](https://code.visualstudio.com/api/extension-guides/color-theme)

## Licence

MIT
`;
                
                await fs.promises.writeFile(
                    path.join(packageDir, 'README.md'),
                    readme,
                    'utf8'
                );
                
                // Create CHANGELOG.md
                const changelog = `# Change Log

## [1.0.0] - ${new Date().toISOString().split('T')[0]}

### Added
- Version initiale du thème ${theme.name}
- Support complet des couleurs VS Code
- Coloration syntaxique optimisée
`;
                
                await fs.promises.writeFile(
                    path.join(packageDir, 'CHANGELOG.md'),
                    changelog,
                    'utf8'
                );
                
                this.outputChannel.appendLine(`Package de thème créé: ${packageDir}`);
                resolve(packageDir);
            } catch (error) {
                reject(error);
            }
        });
    }
}