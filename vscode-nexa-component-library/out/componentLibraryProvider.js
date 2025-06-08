"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ComponentItem = exports.ComponentLibraryProvider = void 0;
const vscode = require("vscode");
class ComponentLibraryProvider {
    constructor(context, componentManager) {
        this.context = context;
        this.componentManager = componentManager;
        this._onDidChangeTreeData = new vscode.EventEmitter();
        this.onDidChangeTreeData = this._onDidChangeTreeData.event;
    }
    refresh() {
        this._onDidChangeTreeData.fire();
    }
    getTreeItem(element) {
        return element;
    }
    async getChildren(element) {
        if (!element) {
            // Root level - show categories
            return this.getComponentCategories();
        }
        else if (element.contextValue === 'category') {
            // Category level - show components in category
            return this.getComponentsInCategory(element.label);
        }
        return [];
    }
    async getComponentCategories() {
        const categories = await this.componentManager.getComponentCategories();
        return categories.map(category => new ComponentItem(category, vscode.TreeItemCollapsibleState.Collapsed, 'category', new vscode.ThemeIcon('folder')));
    }
    async getComponentsInCategory(category) {
        const components = await this.componentManager.getComponentsInCategory(category);
        return components.map(component => {
            const item = new ComponentItem(component.name, vscode.TreeItemCollapsibleState.None, 'component', new vscode.ThemeIcon('symbol-class'));
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
exports.ComponentLibraryProvider = ComponentLibraryProvider;
class ComponentItem extends vscode.TreeItem {
    constructor(label, collapsibleState, contextValue, iconPath, path) {
        super(label, collapsibleState);
        this.label = label;
        this.collapsibleState = collapsibleState;
        this.contextValue = contextValue;
        this.iconPath = iconPath;
        this.path = path;
        this.contextValue = contextValue;
        this.iconPath = iconPath;
    }
}
exports.ComponentItem = ComponentItem;
//# sourceMappingURL=componentLibraryProvider.js.map