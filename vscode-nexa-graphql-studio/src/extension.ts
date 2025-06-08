import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';
import { GraphQLStudio } from './graphqlStudio';
import { ResolverGenerator } from './resolverGenerator';
import { SchemaValidator } from './schemaValidator';
import { QueryTester } from './queryTester';
import { DocumentationGenerator } from './documentationGenerator';

export function activate(context: vscode.ExtensionContext) {
    console.log('Extension Nexa GraphQL Studio activée');

    // Vérifier si c'est un projet Nexa
    const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
    if (workspaceFolder) {
        const nexaPath = path.join(workspaceFolder.uri.fsPath, 'nexa');
        vscode.commands.executeCommand('setContext', 'workspaceHasNexaProject', true);
    }

    // Initialiser les composants
    const studio = new GraphQLStudio(context);
    const resolverGenerator = new ResolverGenerator(context);
    const schemaValidator = new SchemaValidator(context);
    const queryTester = new QueryTester(context);
    const docGenerator = new DocumentationGenerator(context);

    // Commande pour ouvrir GraphQL Studio
    const openStudio = vscode.commands.registerCommand('nexa.graphql.openStudio', async () => {
        await studio.openStudio();
    });

    // Commande pour générer un resolver
    const generateResolver = vscode.commands.registerCommand('nexa.graphql.generateResolver', async (uri?: vscode.Uri) => {
        if (uri) {
            await resolverGenerator.generateFromSchema(uri.fsPath);
        } else {
            const typeName = await vscode.window.showInputBox({
                prompt: 'Nom du type GraphQL',
                placeHolder: 'User'
            });
            
            if (typeName) {
                await resolverGenerator.generateResolver(typeName);
            }
        }
    });

    // Commande pour valider le schéma
    const validateSchema = vscode.commands.registerCommand('nexa.graphql.validateSchema', async (uri?: vscode.Uri) => {
        if (uri) {
            await schemaValidator.validateFile(uri.fsPath);
        } else {
            await schemaValidator.validateWorkspace();
        }
    });

    // Commande pour tester une requête
    const testQuery = vscode.commands.registerCommand('nexa.graphql.testQuery', async () => {
        await queryTester.openQueryTester();
    });

    // Commande pour générer la documentation
    const generateDocs = vscode.commands.registerCommand('nexa.graphql.generateDocs', async () => {
        await docGenerator.generateDocumentation();
    });

    // Provider d'autocomplétion pour GraphQL
    const completionProvider = vscode.languages.registerCompletionItemProvider(
        { scheme: 'file', language: 'graphql' },
        {
            provideCompletionItems(document: vscode.TextDocument, position: vscode.Position) {
                const linePrefix = document.lineAt(position).text.substr(0, position.character);
                
                const completions: vscode.CompletionItem[] = [];
                
                // Types de base GraphQL
                const basicTypes = ['String', 'Int', 'Float', 'Boolean', 'ID'];
                basicTypes.forEach(type => {
                    const completion = new vscode.CompletionItem(type, vscode.CompletionItemKind.TypeParameter);
                    completion.detail = `Type GraphQL de base`;
                    completions.push(completion);
                });
                
                // Directives GraphQL
                const directives = ['@deprecated', '@include', '@skip'];
                directives.forEach(directive => {
                    const completion = new vscode.CompletionItem(directive, vscode.CompletionItemKind.Keyword);
                    completion.detail = 'Directive GraphQL';
                    completions.push(completion);
                });
                
                // Mots-clés GraphQL
                const keywords = ['type', 'interface', 'union', 'enum', 'input', 'scalar', 'query', 'mutation', 'subscription'];
                keywords.forEach(keyword => {
                    const completion = new vscode.CompletionItem(keyword, vscode.CompletionItemKind.Keyword);
                    completion.detail = 'Mot-clé GraphQL';
                    completions.push(completion);
                });
                
                return completions;
            }
        },
        ' ', ':', '@'
    );

    // Provider de diagnostic pour la validation
    const diagnosticCollection = vscode.languages.createDiagnosticCollection('graphql');
    
    const validateDocument = async (document: vscode.TextDocument) => {
        if (document.languageId === 'graphql') {
            const diagnostics = await schemaValidator.validateDocument(document);
            diagnosticCollection.set(document.uri, diagnostics);
        }
    };

    // Validation à l'ouverture et à la sauvegarde
    vscode.workspace.onDidOpenTextDocument(validateDocument);
    vscode.workspace.onDidSaveTextDocument(validateDocument);
    
    // Validation des documents déjà ouverts
    vscode.workspace.textDocuments.forEach(validateDocument);

    // Provider de définition pour aller aux resolvers
    const definitionProvider = vscode.languages.registerDefinitionProvider(
        { scheme: 'file', language: 'graphql' },
        {
            async provideDefinition(document: vscode.TextDocument, position: vscode.Position) {
                const wordRange = document.getWordRangeAtPosition(position);
                if (!wordRange) return;
                
                const word = document.getText(wordRange);
                const resolverPath = await findResolverForType(word);
                
                if (resolverPath) {
                    return new vscode.Location(
                        vscode.Uri.file(resolverPath),
                        new vscode.Position(0, 0)
                    );
                }
            }
        }
    );

    // Provider de hover pour afficher des informations
    const hoverProvider = vscode.languages.registerHoverProvider(
        { scheme: 'file', language: 'graphql' },
        {
            provideHover(document: vscode.TextDocument, position: vscode.Position) {
                const wordRange = document.getWordRangeAtPosition(position);
                if (!wordRange) return;
                
                const word = document.getText(wordRange);
                
                // Informations sur les types de base
                const typeInfo: { [key: string]: string } = {
                    'String': 'Type de chaîne de caractères UTF-8',
                    'Int': 'Entier signé 32-bit',
                    'Float': 'Nombre à virgule flottante double précision',
                    'Boolean': 'Valeur booléenne true ou false',
                    'ID': 'Identifiant unique sérialisé comme String'
                };
                
                if (typeInfo[word]) {
                    return new vscode.Hover([
                        `**${word}**`,
                        typeInfo[word]
                    ]);
                }
            }
        }
    );

    context.subscriptions.push(
        openStudio,
        generateResolver,
        validateSchema,
        testQuery,
        generateDocs,
        completionProvider,
        diagnosticCollection,
        definitionProvider,
        hoverProvider
    );

    // Watcher pour les fichiers GraphQL
    const graphqlWatcher = vscode.workspace.createFileSystemWatcher('**/*.{graphql,gql}');
    graphqlWatcher.onDidChange(async (uri) => {
        const document = await vscode.workspace.openTextDocument(uri);
        await validateDocument(document);
    });
    
    context.subscriptions.push(graphqlWatcher);
}

async function findResolverForType(typeName: string): Promise<string | undefined> {
    const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
    if (!workspaceFolder) return;

    const config = vscode.workspace.getConfiguration('nexa.graphql');
    const resolverPath = config.get<string>('resolverPath', 'workspace/handlers/graphql');
    
    const resolverDir = path.join(workspaceFolder.uri.fsPath, resolverPath);
    const possibleFiles = [
        path.join(resolverDir, `${typeName}Resolver.php`),
        path.join(resolverDir, `${typeName.toLowerCase()}Resolver.php`),
        path.join(resolverDir, `${typeName}.php`)
    ];
    
    for (const file of possibleFiles) {
        try {
            await fs.promises.access(file);
            return file;
        } catch {
            continue;
        }
    }
    
    return undefined;
}

export function deactivate() {}