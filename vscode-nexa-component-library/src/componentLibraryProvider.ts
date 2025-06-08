import * as vscode from 'vscode';
import * as path from 'path';
import { ComponentManager } from './componentManager';

export class ComponentLibraryProvider implements vscode.TreeDataProvider<ComponentItem> {
    private _onDidChangeTreeData: vscode.EventEmitter<ComponentItem | undefined | null | void> = new vscode.EventEmitter<ComponentItem | undefined | null | void>();
    readonly onDidChangeTreeData: vscode.Event<ComponentItem | undefined | null | void> = this._onDidChangeTreeData.event;

    constructor(
        private context: vscode.ExtensionContext,
        private componentManager: ComponentManager
    ) {}

    refresh(): void {
        this._onDidChangeTreeData.fire();
    }

    getTreeItem(element: ComponentItem): vscode.TreeItem {
        return element;
    }

    async getChildren(element?: ComponentItem): Promise<ComponentItem[]> {
        if (!element) {
            // Root level - show categories
            return this.getComponentCategories();
        } else if (element.contextValue === 'category') {
            // Category level - show components in category
            return this.getComponentsInCategory(element.label as string);
        }
        return [];
    }

    private async getComponentCategories(): Promise<ComponentItem[]> {
        const categories = await this.componentManager.getComponentCategories();
        return categories.map(category => new ComponentItem(
            category,
            vscode.TreeItemCollapsibleState.Collapsed,
            'category',
            new vscode.ThemeIcon('folder')
        ));
    }

    private async getComponentsInCategory(category: string): Promise<ComponentItem[]> {
        const components = await this.componentManager.getComponentsInCategory(category);
        return components.map(component => {
            const item = new ComponentItem(
                component.name,
                vscode.TreeItemCollapsibleState.None,
                'component',
                new vscode.ThemeIcon('symbol-class')
            );
            item.description = component.description;
            item.tooltip = `${component.name}\n${component.description}`;
            item.command = {
                command: 'nexa.componentLibrary.previewComponent',
                title: 'Prévisualiser',
                arguments: [component]
            };
            return item;
        });
    }
}

export class ComponentItem extends vscode.TreeItem {
    constructor(
        public readonly label: string,
        public readonly collapsibleState: vscode.TreeItemCollapsibleState,
        public readonly contextValue: string,
        public readonly iconPath?: vscode.ThemeIcon,
        public readonly path?: string
    ) {
        super(label, collapsibleState);
        this.contextValue = contextValue;
        this.iconPath = iconPath;
    }
}