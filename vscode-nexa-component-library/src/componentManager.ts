import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';

export interface Component {
    name: string;
    description: string;
    category: string;
    path: string;
    content: string;
    preview?: string;
}

export class ComponentManager {
    private components: Component[] = [];
    private libraryPath: string;

    constructor(private context: vscode.ExtensionContext) {
        this.libraryPath = this.getLibraryPath();
        this.loadComponents();
    }

    private getLibraryPath(): string {
        const config = vscode.workspace.getConfiguration('nexa.componentLibrary');
        const relativePath = config.get<string>('libraryPath', 'workspace/interface/components');
        
        if (vscode.workspace.workspaceFolders && vscode.workspace.workspaceFolders.length > 0) {
            return path.join(vscode.workspace.workspaceFolders[0].uri.fsPath, relativePath);
        }
        
        return relativePath;
    }

    async loadComponents(): Promise<void> {
        this.components = [];
        
        if (!fs.existsSync(this.libraryPath)) {
            // Create default components directory structure
            await this.createDefaultStructure();
            return;
        }

        await this.scanDirectory(this.libraryPath);
    }

    private async scanDirectory(dirPath: string): Promise<void> {
        try {
            const items = fs.readdirSync(dirPath);
            
            for (const item of items) {
                const itemPath = path.join(dirPath, item);
                const stat = fs.statSync(itemPath);
                
                if (stat.isDirectory()) {
                    await this.scanDirectory(itemPath);
                } else if (item.endsWith('.nx')) {
                    await this.loadComponent(itemPath);
                }
            }
        } catch (error) {
            console.error('Error scanning directory:', error);
        }
    }

    private async loadComponent(filePath: string): Promise<void> {
        try {
            const content = fs.readFileSync(filePath, 'utf8');
            const relativePath = path.relative(this.libraryPath, filePath);
            const category = path.dirname(relativePath) === '.' ? 'General' : path.dirname(relativePath);
            const name = path.basename(filePath, '.nx');
            
            // Extract description from component comments
            const descriptionMatch = content.match(/\/\*\*\s*([^*]+)\s*\*\//m);
            const description = descriptionMatch ? descriptionMatch[1].trim() : 'Composant Nexa';
            
            this.components.push({
                name,
                description,
                category,
                path: filePath,
                content
            });
        } catch (error) {
            console.error(`Error loading component ${filePath}:`, error);
        }
    }

    async getComponentCategories(): Promise<string[]> {
        const categories = new Set(this.components.map(c => c.category));
        return Array.from(categories).sort();
    }

    async getComponentsInCategory(category: string): Promise<Component[]> {
        return this.components.filter(c => c.category === category);
    }

    async getComponentCode(componentPath: string): Promise<string | null> {
        const component = this.components.find(c => c.path === componentPath);
        return component ? component.content : null;
    }

    async createComponent(name: string, type: string): Promise<void> {
        const categoryPath = path.join(this.libraryPath, type.toLowerCase());
        
        // Ensure category directory exists
        if (!fs.existsSync(categoryPath)) {
            fs.mkdirSync(categoryPath, { recursive: true });
        }
        
        const componentPath = path.join(categoryPath, `${name}.nx`);
        const template = this.getComponentTemplate(name, type);
        
        fs.writeFileSync(componentPath, template, 'utf8');
        await this.loadComponents(); // Reload components
    }

    private getComponentTemplate(name: string, type: string): string {
        switch (type.toLowerCase()) {
            case 'button':
                return `/**
 * ${name} - Composant bouton personnalisé
 */
<button class="nexa-btn nexa-btn-primary" onclick="{action}">
    {label || 'Cliquer ici'}
</button>

<style>
.nexa-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

.nexa-btn-primary {
    background-color: #007acc;
    color: white;
}

.nexa-btn-primary:hover {
    background-color: #005a9e;
}
</style>`;
            
            case 'card':
                return `/**
 * ${name} - Composant carte
 */
<div class="nexa-card">
    <div class="nexa-card-header" if="{title}">
        <h3>{title}</h3>
    </div>
    <div class="nexa-card-body">
        {content}
    </div>
    <div class="nexa-card-footer" if="{footer}">
        {footer}
    </div>
</div>

<style>
.nexa-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.nexa-card-header {
    padding: 16px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
}

.nexa-card-body {
    padding: 16px;
}

.nexa-card-footer {
    padding: 16px;
    background-color: #f9f9f9;
    border-top: 1px solid #e0e0e0;
}
</style>`;
            
            case 'form':
                return `/**
 * ${name} - Composant formulaire
 */
<form class="nexa-form" onsubmit="{onSubmit}">
    <div class="nexa-form-group">
        <label for="{fieldId}">{label}</label>
        <input type="{inputType || 'text'}" id="{fieldId}" name="{fieldName}" 
               placeholder="{placeholder}" required="{required}" />
    </div>
    <div class="nexa-form-actions">
        <button type="submit" class="nexa-btn nexa-btn-primary">
            {submitLabel || 'Envoyer'}
        </button>
    </div>
</form>

<style>
.nexa-form {
    max-width: 400px;
}

.nexa-form-group {
    margin-bottom: 16px;
}

.nexa-form-group label {
    display: block;
    margin-bottom: 4px;
    font-weight: bold;
}

.nexa-form-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.nexa-form-actions {
    text-align: right;
}
</style>`;
            
            default:
                return `/**
 * ${name} - Composant personnalisé
 */
<div class="nexa-${name.toLowerCase()}">
    <!-- Contenu du composant -->
    {content}
</div>

<style>
.nexa-${name.toLowerCase()} {
    /* Styles du composant */
}
</style>`;
        }
    }

    private async createDefaultStructure(): Promise<void> {
        const categories = ['button', 'card', 'form', 'layout', 'navigation'];
        
        for (const category of categories) {
            const categoryPath = path.join(this.libraryPath, category);
            if (!fs.existsSync(categoryPath)) {
                fs.mkdirSync(categoryPath, { recursive: true });
            }
        }
        
        // Create some default components
        await this.createComponent('PrimaryButton', 'button');
        await this.createComponent('InfoCard', 'card');
        await this.createComponent('ContactForm', 'form');
    }
}