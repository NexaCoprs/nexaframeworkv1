import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class SchemaValidator {
    private context: vscode.ExtensionContext;
    private diagnosticCollection: vscode.DiagnosticCollection;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
        this.diagnosticCollection = vscode.languages.createDiagnosticCollection('graphql');
    }

    async validateFile(filePath: string) {
        try {
            const content = await fs.promises.readFile(filePath, 'utf8');
            const document = await vscode.workspace.openTextDocument(filePath);
            const diagnostics = await this.validateDocument(document);
            
            this.diagnosticCollection.set(document.uri, diagnostics);
            
            if (diagnostics.length === 0) {
                vscode.window.showInformationMessage(`✅ Schéma valide: ${path.basename(filePath)}`);
            } else {
                vscode.window.showWarningMessage(`⚠️ ${diagnostics.length} erreur(s) trouvée(s) dans ${path.basename(filePath)}`);
            }
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la validation: ${error}`);
        }
    }

    async validateWorkspace() {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        const schemaFiles = await vscode.workspace.findFiles('**/*.{graphql,gql}');
        
        if (schemaFiles.length === 0) {
            vscode.window.showInformationMessage('Aucun fichier GraphQL trouvé');
            return;
        }

        let totalErrors = 0;
        let validFiles = 0;

        for (const file of schemaFiles) {
            const document = await vscode.workspace.openTextDocument(file);
            const diagnostics = await this.validateDocument(document);
            
            this.diagnosticCollection.set(file, diagnostics);
            
            if (diagnostics.length === 0) {
                validFiles++;
            } else {
                totalErrors += diagnostics.length;
            }
        }

        const message = `Validation terminée: ${validFiles}/${schemaFiles.length} fichiers valides, ${totalErrors} erreur(s) au total`;
        
        if (totalErrors === 0) {
            vscode.window.showInformationMessage(`✅ ${message}`);
        } else {
            vscode.window.showWarningMessage(`⚠️ ${message}`);
        }
    }

    async validateDocument(document: vscode.TextDocument): Promise<vscode.Diagnostic[]> {
        const diagnostics: vscode.Diagnostic[] = [];
        const content = document.getText();
        const lines = content.split('\n');

        // Validation de la syntaxe de base
        this.validateBasicSyntax(content, lines, diagnostics);
        
        // Validation des types
        this.validateTypes(content, lines, diagnostics);
        
        // Validation des champs
        this.validateFields(content, lines, diagnostics);
        
        // Validation des directives
        this.validateDirectives(content, lines, diagnostics);
        
        // Validation des références
        this.validateReferences(content, lines, diagnostics);
        
        // Validation des conventions de nommage
        this.validateNamingConventions(content, lines, diagnostics);

        return diagnostics;
    }

    private validateBasicSyntax(content: string, lines: string[], diagnostics: vscode.Diagnostic[]) {
        // Vérifier les accolades équilibrées
        const openBraces = (content.match(/\{/g) || []).length;
        const closeBraces = (content.match(/\}/g) || []).length;
        
        if (openBraces !== closeBraces) {
            const diagnostic = new vscode.Diagnostic(
                new vscode.Range(0, 0, 0, 0),
                `Accolades non équilibrées: ${openBraces} ouvertes, ${closeBraces} fermées`,
                vscode.DiagnosticSeverity.Error
            );
            diagnostics.push(diagnostic);
        }

        // Vérifier les parenthèses équilibrées
        const openParens = (content.match(/\(/g) || []).length;
        const closeParens = (content.match(/\)/g) || []).length;
        
        if (openParens !== closeParens) {
            const diagnostic = new vscode.Diagnostic(
                new vscode.Range(0, 0, 0, 0),
                `Parenthèses non équilibrées: ${openParens} ouvertes, ${closeParens} fermées`,
                vscode.DiagnosticSeverity.Error
            );
            diagnostics.push(diagnostic);
        }

        // Vérifier les crochets équilibrés
        const openBrackets = (content.match(/\[/g) || []).length;
        const closeBrackets = (content.match(/\]/g) || []).length;
        
        if (openBrackets !== closeBrackets) {
            const diagnostic = new vscode.Diagnostic(
                new vscode.Range(0, 0, 0, 0),
                `Crochets non équilibrés: ${openBrackets} ouverts, ${closeBrackets} fermés`,
                vscode.DiagnosticSeverity.Error
            );
            diagnostics.push(diagnostic);
        }

        // Vérifier les caractères invalides
        lines.forEach((line, lineIndex) => {
            const invalidChars = line.match(/[^\w\s{}()\[\]:!@#$%^&*()_+\-=\[\]{}|;':",./<>?`~]/g);
            if (invalidChars) {
                invalidChars.forEach(char => {
                    const charIndex = line.indexOf(char);
                    const diagnostic = new vscode.Diagnostic(
                        new vscode.Range(lineIndex, charIndex, lineIndex, charIndex + 1),
                        `Caractère invalide: '${char}'`,
                        vscode.DiagnosticSeverity.Warning
                    );
                    diagnostics.push(diagnostic);
                });
            }
        });
    }

    private validateTypes(content: string, lines: string[], diagnostics: vscode.Diagnostic[]) {
        const typePattern = /^\s*(type|interface|union|enum|input|scalar)\s+(\w+)/gm;
        const definedTypes = new Set<string>();
        const typeDefinitions = new Map<string, number>();
        
        let match;
        while ((match = typePattern.exec(content)) !== null) {
            const typeName = match[2];
            const lineIndex = content.substring(0, match.index).split('\n').length - 1;
            
            // Vérifier les doublons
            if (definedTypes.has(typeName)) {
                const diagnostic = new vscode.Diagnostic(
                    new vscode.Range(lineIndex, match[1].length + 1, lineIndex, match[0].length),
                    `Type '${typeName}' déjà défini`,
                    vscode.DiagnosticSeverity.Error
                );
                diagnostics.push(diagnostic);
            } else {
                definedTypes.add(typeName);
                typeDefinitions.set(typeName, lineIndex);
            }
            
            // Vérifier les conventions de nommage des types
            if (!/^[A-Z][a-zA-Z0-9]*$/.test(typeName)) {
                const diagnostic = new vscode.Diagnostic(
                    new vscode.Range(lineIndex, match[1].length + 1, lineIndex, match[0].length),
                    `Le nom du type '${typeName}' devrait commencer par une majuscule et utiliser PascalCase`,
                    vscode.DiagnosticSeverity.Warning
                );
                diagnostics.push(diagnostic);
            }
        }

        // Vérifier la présence de types obligatoires
        if (!definedTypes.has('Query') && content.includes('type ')) {
            const diagnostic = new vscode.Diagnostic(
                new vscode.Range(0, 0, 0, 0),
                'Un schéma GraphQL doit contenir au moins un type Query',
                vscode.DiagnosticSeverity.Warning
            );
            diagnostics.push(diagnostic);
        }
    }

    private validateFields(content: string, lines: string[], diagnostics: vscode.Diagnostic[]) {
        lines.forEach((line, lineIndex) => {
            const trimmed = line.trim();
            
            // Vérifier les champs dans les types
            const fieldMatch = trimmed.match(/^(\w+)\s*:\s*(.+)$/);
            if (fieldMatch && !trimmed.startsWith('type ') && !trimmed.startsWith('interface ')) {
                const fieldName = fieldMatch[1];
                const fieldType = fieldMatch[2];
                
                // Vérifier les conventions de nommage des champs
                if (!/^[a-z][a-zA-Z0-9]*$/.test(fieldName)) {
                    const diagnostic = new vscode.Diagnostic(
                        new vscode.Range(lineIndex, 0, lineIndex, fieldName.length),
                        `Le nom du champ '${fieldName}' devrait commencer par une minuscule et utiliser camelCase`,
                        vscode.DiagnosticSeverity.Warning
                    );
                    diagnostics.push(diagnostic);
                }
                
                // Vérifier les types de champs
                const typePattern = /([A-Za-z][A-Za-z0-9]*)/g;
                let typeMatch;
                while ((typeMatch = typePattern.exec(fieldType)) !== null) {
                    const typeName = typeMatch[1];
                    
                    // Vérifier si c'est un type de base GraphQL
                    const basicTypes = ['String', 'Int', 'Float', 'Boolean', 'ID'];
                    if (!basicTypes.includes(typeName) && !/^[A-Z]/.test(typeName)) {
                        const diagnostic = new vscode.Diagnostic(
                            new vscode.Range(lineIndex, line.indexOf(typeName), lineIndex, line.indexOf(typeName) + typeName.length),
                            `Type '${typeName}' non reconnu. Les types personnalisés doivent commencer par une majuscule`,
                            vscode.DiagnosticSeverity.Warning
                        );
                        diagnostics.push(diagnostic);
                    }
                }
                
                // Vérifier la syntaxe des arguments
                const argsMatch = fieldType.match(/\(([^)]+)\)/);
                if (argsMatch) {
                    const argsString = argsMatch[1];
                    const args = argsString.split(',').map(arg => arg.trim());
                    
                    args.forEach(arg => {
                        if (!arg.match(/^\w+\s*:\s*.+$/)) {
                            const diagnostic = new vscode.Diagnostic(
                                new vscode.Range(lineIndex, line.indexOf(arg), lineIndex, line.indexOf(arg) + arg.length),
                                `Syntaxe d'argument invalide: '${arg}'`,
                                vscode.DiagnosticSeverity.Error
                            );
                            diagnostics.push(diagnostic);
                        }
                    });
                }
            }
        });
    }

    private validateDirectives(content: string, lines: string[], diagnostics: vscode.Diagnostic[]) {
        const knownDirectives = ['@deprecated', '@include', '@skip', '@auth', '@validate', '@cache'];
        
        lines.forEach((line, lineIndex) => {
            const directiveMatches = line.matchAll(/@(\w+)/g);
            
            for (const match of directiveMatches) {
                const directiveName = `@${match[1]}`;
                
                if (!knownDirectives.includes(directiveName)) {
                    const diagnostic = new vscode.Diagnostic(
                        new vscode.Range(lineIndex, match.index!, lineIndex, match.index! + directiveName.length),
                        `Directive inconnue: '${directiveName}'`,
                        vscode.DiagnosticSeverity.Warning
                    );
                    diagnostics.push(diagnostic);
                }
            }
        });
    }

    private validateReferences(content: string, lines: string[], diagnostics: vscode.Diagnostic[]) {
        // Extraire tous les types définis
        const definedTypes = new Set<string>();
        const typePattern = /^\s*(type|interface|union|enum|input|scalar)\s+(\w+)/gm;
        
        let match;
        while ((match = typePattern.exec(content)) !== null) {
            definedTypes.add(match[2]);
        }
        
        // Ajouter les types de base GraphQL
        ['String', 'Int', 'Float', 'Boolean', 'ID'].forEach(type => definedTypes.add(type));
        
        // Vérifier les références de types
        lines.forEach((line, lineIndex) => {
            const fieldMatch = line.trim().match(/^\w+\s*:\s*(.+)$/);
            if (fieldMatch) {
                const fieldType = fieldMatch[1];
                
                // Extraire les types référencés
                const referencedTypes = fieldType.match(/[A-Z][a-zA-Z0-9]*/g) || [];
                
                referencedTypes.forEach(refType => {
                    if (!definedTypes.has(refType)) {
                        const typeIndex = line.indexOf(refType);
                        const diagnostic = new vscode.Diagnostic(
                            new vscode.Range(lineIndex, typeIndex, lineIndex, typeIndex + refType.length),
                            `Type '${refType}' non défini`,
                            vscode.DiagnosticSeverity.Error
                        );
                        diagnostics.push(diagnostic);
                    }
                });
            }
        });
    }

    private validateNamingConventions(content: string, lines: string[], diagnostics: vscode.Diagnostic[]) {
        lines.forEach((line, lineIndex) => {
            const trimmed = line.trim();
            
            // Vérifier les noms de types
            const typeMatch = trimmed.match(/^(type|interface|union|enum|input|scalar)\s+(\w+)/);
            if (typeMatch) {
                const typeName = typeMatch[2];
                
                // Les types doivent être en PascalCase
                if (!/^[A-Z][a-zA-Z0-9]*$/.test(typeName)) {
                    const diagnostic = new vscode.Diagnostic(
                        new vscode.Range(lineIndex, typeMatch[1].length + 1, lineIndex, typeMatch[0].length),
                        `Le type '${typeName}' devrait utiliser PascalCase`,
                        vscode.DiagnosticSeverity.Information
                    );
                    diagnostics.push(diagnostic);
                }
                
                // Vérifier les suffixes appropriés
                if (typeMatch[1] === 'input' && !typeName.endsWith('Input')) {
                    const diagnostic = new vscode.Diagnostic(
                        new vscode.Range(lineIndex, typeMatch[1].length + 1, lineIndex, typeMatch[0].length),
                        `Les types input devraient se terminer par 'Input'`,
                        vscode.DiagnosticSeverity.Information
                    );
                    diagnostics.push(diagnostic);
                }
                
                if (typeMatch[1] === 'enum' && !typeName.endsWith('Enum') && !typeName.endsWith('Type')) {
                    const diagnostic = new vscode.Diagnostic(
                        new vscode.Range(lineIndex, typeMatch[1].length + 1, lineIndex, typeMatch[0].length),
                        `Les enums devraient se terminer par 'Enum' ou 'Type'`,
                        vscode.DiagnosticSeverity.Information
                    );
                    diagnostics.push(diagnostic);
                }
            }
            
            // Vérifier les noms de champs
            const fieldMatch = trimmed.match(/^(\w+)\s*:/);
            if (fieldMatch && !trimmed.startsWith('type ')) {
                const fieldName = fieldMatch[1];
                
                // Les champs doivent être en camelCase
                if (!/^[a-z][a-zA-Z0-9]*$/.test(fieldName)) {
                    const diagnostic = new vscode.Diagnostic(
                        new vscode.Range(lineIndex, 0, lineIndex, fieldName.length),
                        `Le champ '${fieldName}' devrait utiliser camelCase`,
                        vscode.DiagnosticSeverity.Information
                    );
                    diagnostics.push(diagnostic);
                }
            }
        });
    }

    dispose() {
        this.diagnosticCollection.dispose();
    }
}