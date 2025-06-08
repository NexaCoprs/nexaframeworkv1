import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export class ResolverGenerator {
    private context: vscode.ExtensionContext;

    constructor(context: vscode.ExtensionContext) {
        this.context = context;
    }

    async generateResolver(typeName: string) {
        const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
        if (!workspaceFolder) {
            vscode.window.showErrorMessage('Aucun workspace ouvert');
            return;
        }

        // Demander le type de resolver
        const resolverType = await vscode.window.showQuickPick([
            { label: 'Query Resolver', description: 'Resolver pour les requêtes de lecture' },
            { label: 'Mutation Resolver', description: 'Resolver pour les mutations' },
            { label: 'Subscription Resolver', description: 'Resolver pour les souscriptions' },
            { label: 'Type Resolver', description: 'Resolver pour un type personnalisé' }
        ], {
            placeHolder: 'Sélectionnez le type de resolver'
        });

        if (!resolverType) return;

        const config = vscode.workspace.getConfiguration('nexa.graphql');
        const resolverPath = config.get<string>('resolverPath', 'workspace/handlers/graphql');
        
        const resolverDir = path.join(workspaceFolder.uri.fsPath, resolverPath);
        await fs.promises.mkdir(resolverDir, { recursive: true });
        
        let resolverContent: string;
        let fileName: string;

        switch (resolverType.label) {
            case 'Query Resolver':
                fileName = `${typeName}QueryResolver.php`;
                resolverContent = this.generateQueryResolver(typeName);
                break;
            case 'Mutation Resolver':
                fileName = `${typeName}MutationResolver.php`;
                resolverContent = this.generateMutationResolver(typeName);
                break;
            case 'Subscription Resolver':
                fileName = `${typeName}SubscriptionResolver.php`;
                resolverContent = this.generateSubscriptionResolver(typeName);
                break;
            default:
                fileName = `${typeName}Resolver.php`;
                resolverContent = this.generateTypeResolver(typeName);
        }

        const resolverFile = path.join(resolverDir, fileName);
        await fs.promises.writeFile(resolverFile, resolverContent, 'utf8');
        
        vscode.window.showInformationMessage(`Resolver généré: ${fileName}`);
        
        // Ouvrir le fichier généré
        const document = await vscode.workspace.openTextDocument(resolverFile);
        await vscode.window.showTextDocument(document);
    }

    async generateFromSchema(schemaPath: string) {
        try {
            const schemaContent = await fs.promises.readFile(schemaPath, 'utf8');
            const types = this.extractTypesFromSchema(schemaContent);
            
            if (types.length === 0) {
                vscode.window.showWarningMessage('Aucun type trouvé dans le schéma');
                return;
            }

            // Permettre à l'utilisateur de sélectionner les types
            const selectedTypes = await vscode.window.showQuickPick(
                types.map(type => ({
                    label: type.name,
                    description: `${type.fields.length} champs`,
                    picked: true,
                    type: type
                })),
                {
                    canPickMany: true,
                    placeHolder: 'Sélectionnez les types pour lesquels générer des resolvers'
                }
            );

            if (!selectedTypes || selectedTypes.length === 0) return;

            const workspaceFolder = vscode.workspace.workspaceFolders?.[0];
            if (!workspaceFolder) return;

            const config = vscode.workspace.getConfiguration('nexa.graphql');
            const resolverPath = config.get<string>('resolverPath', 'workspace/handlers/graphql');
            const resolverDir = path.join(workspaceFolder.uri.fsPath, resolverPath);
            await fs.promises.mkdir(resolverDir, { recursive: true });

            // Générer les resolvers pour chaque type sélectionné
            for (const selectedType of selectedTypes) {
                const type = selectedType.type;
                const resolverContent = this.generateCompleteResolver(type.name, type.fields);
                const fileName = `${type.name}Resolver.php`;
                const resolverFile = path.join(resolverDir, fileName);
                
                await fs.promises.writeFile(resolverFile, resolverContent, 'utf8');
            }

            vscode.window.showInformationMessage(`${selectedTypes.length} resolver(s) généré(s)`);
        } catch (error) {
            vscode.window.showErrorMessage(`Erreur lors de la génération: ${error}`);
        }
    }

    private extractTypesFromSchema(schema: string): any[] {
        const types: any[] = [];
        const typeMatches = schema.match(/type\s+(\w+)\s*\{([^}]+)\}/g) || [];
        
        typeMatches.forEach(typeMatch => {
            const nameMatch = typeMatch.match(/type\s+(\w+)/);
            const fieldsMatch = typeMatch.match(/\{([^}]+)\}/);
            
            if (nameMatch && fieldsMatch) {
                const typeName = nameMatch[1];
                const fieldsText = fieldsMatch[1];
                const fields = fieldsText.split('\n')
                    .map(line => line.trim())
                    .filter(line => line && line.includes(':'))
                    .map(line => {
                        const fieldMatch = line.match(/(\w+)\s*:\s*(.+)/);
                        return fieldMatch ? {
                            name: fieldMatch[1],
                            type: fieldMatch[2].replace(/[!\[\]]/g, '').trim()
                        } : null;
                    })
                    .filter(field => field !== null);
                
                types.push({
                    name: typeName,
                    fields
                });
            }
        });
        
        return types;
    }

    private generateQueryResolver(typeName: string): string {
        const className = `${typeName}QueryResolver`;
        const modelName = typeName;
        const instanceName = typeName.toLowerCase();
        
        return `<?php

namespace App\\GraphQL\\Resolvers;

use Nexa\\GraphQL\\Resolver;
use App\\Models\\${modelName};
use GraphQL\\Type\\Definition\\ResolveInfo;

class ${className} extends Resolver
{
    /**
     * Récupérer tous les ${instanceName}s
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return array
     */
    public function all($root, array $args, $context, ResolveInfo $info): array
    {
        $query = ${modelName}::query();
        
        // Filtres optionnels
        if (isset($args['filter'])) {
            $this->applyFilters($query, $args['filter']);
        }
        
        // Pagination
        if (isset($args['limit'])) {
            $query->limit($args['limit']);
        }
        
        if (isset($args['offset'])) {
            $query->offset($args['offset']);
        }
        
        // Tri
        if (isset($args['orderBy'])) {
            $query->orderBy($args['orderBy']['field'], $args['orderBy']['direction'] ?? 'ASC');
        }
        
        return $query->get()->toArray();
    }

    /**
     * Récupérer un ${instanceName} par ID
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return array|null
     */
    public function find($root, array $args, $context, ResolveInfo $info): ?array
    {
        $${instanceName} = ${modelName}::find($args['id']);
        return $${instanceName} ? $${instanceName}->toArray() : null;
    }

    /**
     * Rechercher des ${instanceName}s
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return array
     */
    public function search($root, array $args, $context, ResolveInfo $info): array
    {
        $query = ${modelName}::query();
        
        if (isset($args['term'])) {
            // Recherche dans les champs texte principaux
            $query->where(function($q) use ($args) {
                $q->where('name', 'LIKE', '%' . $args['term'] . '%')
                  ->orWhere('description', 'LIKE', '%' . $args['term'] . '%');
            });
        }
        
        return $query->get()->toArray();
    }

    /**
     * Appliquer les filtres à la requête
     *
     * @param \\Illuminate\\Database\\Eloquent\\Builder $query
     * @param array $filters
     */
    private function applyFilters($query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                if (isset($value['in'])) {
                    $query->whereIn($field, $value['in']);
                } elseif (isset($value['between'])) {
                    $query->whereBetween($field, $value['between']);
                } elseif (isset($value['like'])) {
                    $query->where($field, 'LIKE', '%' . $value['like'] . '%');
                }
            } else {
                $query->where($field, $value);
            }
        }
    }
}
`;
    }

    private generateMutationResolver(typeName: string): string {
        const className = `${typeName}MutationResolver`;
        const modelName = typeName;
        const instanceName = typeName.toLowerCase();
        
        return `<?php

namespace App\\GraphQL\\Resolvers;

use Nexa\\GraphQL\\Resolver;
use App\\Models\\${modelName};
use GraphQL\\Type\\Definition\\ResolveInfo;
use Illuminate\\Validation\\ValidationException;
use Illuminate\\Support\\Facades\\Validator;

class ${className} extends Resolver
{
    /**
     * Créer un nouveau ${instanceName}
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return array
     * @throws ValidationException
     */
    public function create($root, array $args, $context, ResolveInfo $info): array
    {
        $input = $args['input'];
        
        // Validation
        $validator = Validator::make($input, $this->getValidationRules());
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        $${instanceName} = ${modelName}::create($input);
        
        return $${instanceName}->toArray();
    }

    /**
     * Mettre à jour un ${instanceName}
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return array|null
     * @throws ValidationException
     */
    public function update($root, array $args, $context, ResolveInfo $info): ?array
    {
        $${instanceName} = ${modelName}::find($args['id']);
        
        if (!$${instanceName}) {
            throw new \\Exception('${typeName} non trouvé');
        }
        
        $input = $args['input'];
        
        // Validation
        $validator = Validator::make($input, $this->getValidationRules(true));
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        $${instanceName}->update($input);
        
        return $${instanceName}->fresh()->toArray();
    }

    /**
     * Supprimer un ${instanceName}
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return bool
     */
    public function delete($root, array $args, $context, ResolveInfo $info): bool
    {
        $${instanceName} = ${modelName}::find($args['id']);
        
        if (!$${instanceName}) {
            throw new \\Exception('${typeName} non trouvé');
        }
        
        return $${instanceName}->delete();
    }

    /**
     * Supprimer plusieurs ${instanceName}s
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return int
     */
    public function deleteMany($root, array $args, $context, ResolveInfo $info): int
    {
        $ids = $args['ids'];
        return ${modelName}::whereIn('id', $ids)->delete();
    }

    /**
     * Règles de validation
     *
     * @param bool $isUpdate
     * @return array
     */
    private function getValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            // Ajoutez vos règles de validation ici
            // 'name' => 'required|string|max:255',
            // 'email' => 'required|email|unique:users,email',
        ];
        
        if ($isUpdate) {
            // Modifier les règles pour la mise à jour si nécessaire
            // $rules['email'] = 'sometimes|email|unique:users,email,' . $args['id'];
        }
        
        return $rules;
    }
}
`;
    }

    private generateSubscriptionResolver(typeName: string): string {
        const className = `${typeName}SubscriptionResolver`;
        const modelName = typeName;
        const instanceName = typeName.toLowerCase();
        
        return `<?php

namespace App\\GraphQL\\Resolvers;

use Nexa\\GraphQL\\Resolver;
use App\\Models\\${modelName};
use GraphQL\\Type\\Definition\\ResolveInfo;
use Ratchet\\ConnectionInterface;
use Ratchet\\RFC6455\\Messaging\\MessageInterface;

class ${className} extends Resolver
{
    /**
     * Souscription aux créations de ${instanceName}
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return \\Generator
     */
    public function ${instanceName}Created($root, array $args, $context, ResolveInfo $info): \\Generator
    {
        // Écouter les événements de création
        while (true) {
            $new${typeName} = $this->waitForNew${typeName}();
            
            if ($new${typeName}) {
                yield $new${typeName}->toArray();
            }
        }
    }

    /**
     * Souscription aux mises à jour de ${instanceName}
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return \\Generator
     */
    public function ${instanceName}Updated($root, array $args, $context, ResolveInfo $info): \\Generator
    {
        $${instanceName}Id = $args['id'] ?? null;
        
        while (true) {
            $updated${typeName} = $this->waitForUpdated${typeName}($${instanceName}Id);
            
            if ($updated${typeName}) {
                yield $updated${typeName}->toArray();
            }
        }
    }

    /**
     * Souscription aux suppressions de ${instanceName}
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return \\Generator
     */
    public function ${instanceName}Deleted($root, array $args, $context, ResolveInfo $info): \\Generator
    {
        while (true) {
            $deleted${typeName}Id = $this->waitForDeleted${typeName}();
            
            if ($deleted${typeName}Id) {
                yield ['id' => $deleted${typeName}Id];
            }
        }
    }

    /**
     * Attendre un nouveau ${instanceName}
     *
     * @return ${modelName}|null
     */
    private function waitForNew${typeName}(): ?${modelName}
    {
        // Implémentation de l'écoute des événements
        // Vous pouvez utiliser Redis, RabbitMQ, ou d'autres systèmes de messagerie
        
        // Exemple avec Redis
        // $redis = app('redis');
        // $message = $redis->blpop('${instanceName}_created', 1);
        // 
        // if ($message) {
        //     $data = json_decode($message[1], true);
        //     return ${modelName}::find($data['id']);
        // }
        
        return null;
    }

    /**
     * Attendre une mise à jour de ${instanceName}
     *
     * @param int|null $${instanceName}Id
     * @return ${modelName}|null
     */
    private function waitForUpdated${typeName}(?int $${instanceName}Id): ?${modelName}
    {
        // Implémentation similaire pour les mises à jour
        return null;
    }

    /**
     * Attendre une suppression de ${instanceName}
     *
     * @return int|null
     */
    private function waitForDeleted${typeName}(): ?int
    {
        // Implémentation similaire pour les suppressions
        return null;
    }
}
`;
    }

    private generateTypeResolver(typeName: string): string {
        const className = `${typeName}Resolver`;
        const modelName = typeName;
        const instanceName = typeName.toLowerCase();
        
        return `<?php

namespace App\\GraphQL\\Resolvers;

use Nexa\\GraphQL\\Resolver;
use App\\Models\\${modelName};
use GraphQL\\Type\\Definition\\ResolveInfo;

class ${className} extends Resolver
{
    /**
     * Résoudre le champ personnalisé
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function customField($root, array $args, $context, ResolveInfo $info)
    {
        // Implémentation personnalisée
        return null;
    }

    /**
     * Résoudre les relations
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function relations($root, array $args, $context, ResolveInfo $info)
    {
        // Charger les relations si nécessaire
        if ($root instanceof ${modelName}) {
            // return $root->relationName;
        }
        
        return null;
    }

    /**
     * Résoudre un champ calculé
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function computedField($root, array $args, $context, ResolveInfo $info)
    {
        if ($root instanceof ${modelName}) {
            // Calcul basé sur les données du modèle
            // return $root->someCalculation();
        }
        
        return null;
    }
}
`;
    }

    private generateCompleteResolver(typeName: string, fields: any[]): string {
        const className = `${typeName}Resolver`;
        const modelName = typeName;
        const instanceName = typeName.toLowerCase();
        
        const fieldResolvers = fields.map(field => {
            if (field.name === 'id') return '';
            
            return `
    /**
     * Résoudre le champ ${field.name}
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function ${field.name}($root, array $args, $context, ResolveInfo $info)
    {
        if ($root instanceof ${modelName}) {
            return $root->${field.name};
        }
        
        return null;
    }`;
        }).filter(resolver => resolver !== '').join('');
        
        return `<?php

namespace App\\GraphQL\\Resolvers;

use Nexa\\GraphQL\\Resolver;
use App\\Models\\${modelName};
use GraphQL\\Type\\Definition\\ResolveInfo;
use Illuminate\\Validation\\ValidationException;
use Illuminate\\Support\\Facades\\Validator;

class ${className} extends Resolver
{
    /**
     * Récupérer tous les ${instanceName}s
     */
    public function all($root, array $args, $context, ResolveInfo $info): array
    {
        return ${modelName}::all()->toArray();
    }

    /**
     * Récupérer un ${instanceName} par ID
     */
    public function find($root, array $args, $context, ResolveInfo $info): ?array
    {
        $${instanceName} = ${modelName}::find($args['id']);
        return $${instanceName} ? $${instanceName}->toArray() : null;
    }

    /**
     * Créer un nouveau ${instanceName}
     */
    public function create($root, array $args, $context, ResolveInfo $info): array
    {
        $input = $args['input'];
        
        // Validation basique
        $validator = Validator::make($input, [
            ${fields.filter(f => f.name !== 'id' && f.name !== 'createdAt' && f.name !== 'updatedAt')
                .map(field => `'${field.name}' => 'required'`).join(',\n            ')}
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        $${instanceName} = ${modelName}::create($input);
        return $${instanceName}->toArray();
    }

    /**
     * Mettre à jour un ${instanceName}
     */
    public function update($root, array $args, $context, ResolveInfo $info): ?array
    {
        $${instanceName} = ${modelName}::find($args['id']);
        
        if (!$${instanceName}) {
            throw new \\Exception('${typeName} non trouvé');
        }
        
        $${instanceName}->update($args['input']);
        return $${instanceName}->fresh()->toArray();
    }

    /**
     * Supprimer un ${instanceName}
     */
    public function delete($root, array $args, $context, ResolveInfo $info): bool
    {
        $${instanceName} = ${modelName}::find($args['id']);
        
        if (!$${instanceName}) {
            throw new \\Exception('${typeName} non trouvé');
        }
        
        return $${instanceName}->delete();
    }${fieldResolvers}
}
`;
    }
}