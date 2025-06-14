<?php

namespace Nexa\Attributes;

use Attribute;

/**
 * Attribut pour la génération automatique de documentation Swagger/OpenAPI
 * Permet de définir les métadonnées API directement sur les classes et méthodes
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class SwaggerAPI
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $version = '1.0.0',
        public ?string $summary = null,
        public ?string $operationId = null,
        public array $tags = [],
        public array $parameters = [],
        public ?array $requestBody = null,
        public array $responses = [],
        public array $security = [],
        public bool $deprecated = false,
        public ?string $externalDocs = null,
        public array $servers = [],
        public ?array $contact = null,
        public ?array $license = null,
        public array $examples = [],
        public array $schemas = [],
        public bool $generateExamples = true,
        public bool $includeValidation = true,
        public string $format = 'json'
    ) {}

    /**
     * Génère la documentation OpenAPI pour une classe
     */
    public function generateClassDocumentation(): array
    {
        $doc = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => $this->title ?? 'API Documentation',
                'description' => $this->description ?? 'API générée automatiquement par Nexa Framework',
                'version' => $this->version
            ]
        ];

        if ($this->contact) {
            $doc['info']['contact'] = $this->contact;
        }

        if ($this->license) {
            $doc['info']['license'] = $this->license;
        }

        if (!empty($this->servers)) {
            $doc['servers'] = array_map(function($server) {
                return is_string($server) ? ['url' => $server] : $server;
            }, $this->servers);
        }

        return $doc;
    }

    /**
     * Génère la documentation OpenAPI pour une méthode
     */
    public function generateMethodDocumentation(): array
    {
        $operation = [];

        if ($this->summary) {
            $operation['summary'] = $this->summary;
        }

        if ($this->description) {
            $operation['description'] = $this->description;
        }

        if ($this->operationId) {
            $operation['operationId'] = $this->operationId;
        }

        if (!empty($this->tags)) {
            $operation['tags'] = $this->tags;
        }

        if (!empty($this->parameters)) {
            $operation['parameters'] = $this->parameters;
        }

        if ($this->requestBody) {
            $operation['requestBody'] = $this->requestBody;
        }

        if (!empty($this->responses)) {
            $operation['responses'] = $this->responses;
        } else {
            // Réponses par défaut
            $operation['responses'] = [
                '200' => [
                    'description' => 'Succès',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'success' => ['type' => 'boolean'],
                                    'data' => ['type' => 'object']
                                ]
                            ]
                        ]
                    ]
                ],
                '400' => [
                    'description' => 'Erreur de validation',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'success' => ['type' => 'boolean', 'example' => false],
                                    'message' => ['type' => 'string'],
                                    'errors' => ['type' => 'object']
                                ]
                            ]
                        ]
                    ]
                ],
                '500' => [
                    'description' => 'Erreur serveur',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'success' => ['type' => 'boolean', 'example' => false],
                                    'message' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        if (!empty($this->security)) {
            $operation['security'] = $this->security;
        }

        if ($this->deprecated) {
            $operation['deprecated'] = true;
        }

        if ($this->externalDocs) {
            $operation['externalDocs'] = ['url' => $this->externalDocs];
        }

        return $operation;
    }

    /**
     * Génère des exemples automatiquement basés sur les types
     */
    public function generateAutoExamples(array $schema): array
    {
        if (!$this->generateExamples) {
            return [];
        }

        $examples = [];
        
        if (isset($schema['properties'])) {
            $example = [];
            foreach ($schema['properties'] as $property => $definition) {
                $example[$property] = $this->generateExampleValue($definition);
            }
            $examples['example'] = ['value' => $example];
        }

        return $examples;
    }

    /**
     * Génère une valeur d'exemple basée sur le type
     */
    private function generateExampleValue(array $definition)
    {
        $type = $definition['type'] ?? 'string';
        
        switch ($type) {
            case 'string':
                if (isset($definition['format'])) {
                    switch ($definition['format']) {
                        case 'email':
                            return 'user@example.com';
                        case 'date':
                            return date('Y-m-d');
                        case 'date-time':
                            return date('c');
                        case 'password':
                            return 'password123';
                        case 'uuid':
                            return '123e4567-e89b-12d3-a456-426614174000';
                        default:
                            return 'string';
                    }
                }
                return isset($definition['example']) ? $definition['example'] : 'example string';
                
            case 'integer':
                return isset($definition['example']) ? $definition['example'] : 42;
                
            case 'number':
                return isset($definition['example']) ? $definition['example'] : 3.14;
                
            case 'boolean':
                return isset($definition['example']) ? $definition['example'] : true;
                
            case 'array':
                $itemExample = isset($definition['items']) 
                    ? $this->generateExampleValue($definition['items']) 
                    : 'item';
                return [$itemExample];
                
            case 'object':
                if (isset($definition['properties'])) {
                    $example = [];
                    foreach ($definition['properties'] as $prop => $propDef) {
                        $example[$prop] = $this->generateExampleValue($propDef);
                    }
                    return $example;
                }
                return new \stdClass();
                
            default:
                return null;
        }
    }

    /**
     * Convertit les règles de validation Nexa en schéma OpenAPI
     */
    public function convertValidationToSchema(array $rules): array
    {
        if (!$this->includeValidation) {
            return [];
        }

        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ];

        foreach ($rules as $field => $rule) {
            $fieldSchema = $this->parseValidationRule($rule);
            $schema['properties'][$field] = $fieldSchema;
            
            if ($this->isRequired($rule)) {
                $schema['required'][] = $field;
            }
        }

        return $schema;
    }

    /**
     * Parse une règle de validation en schéma OpenAPI
     */
    private function parseValidationRule(string $rule): array
    {
        $rules = explode('|', $rule);
        $schema = ['type' => 'string'];
        
        foreach ($rules as $r) {
            if (strpos($r, ':') !== false) {
                [$ruleName, $ruleValue] = explode(':', $r, 2);
            } else {
                $ruleName = $r;
                $ruleValue = null;
            }
            
            switch ($ruleName) {
                case 'integer':
                    $schema['type'] = 'integer';
                    break;
                case 'numeric':
                    $schema['type'] = 'number';
                    break;
                case 'boolean':
                    $schema['type'] = 'boolean';
                    break;
                case 'email':
                    $schema['format'] = 'email';
                    break;
                case 'url':
                    $schema['format'] = 'uri';
                    break;
                case 'date':
                    $schema['format'] = 'date';
                    break;
                case 'min':
                    if ($schema['type'] === 'string') {
                        $schema['minLength'] = (int)$ruleValue;
                    } else {
                        $schema['minimum'] = (int)$ruleValue;
                    }
                    break;
                case 'max':
                    if ($schema['type'] === 'string') {
                        $schema['maxLength'] = (int)$ruleValue;
                    } else {
                        $schema['maximum'] = (int)$ruleValue;
                    }
                    break;
                case 'in':
                    $schema['enum'] = explode(',', $ruleValue);
                    break;
            }
        }
        
        return $schema;
    }

    /**
     * Vérifie si un champ est requis
     */
    private function isRequired(string $rule): bool
    {
        return strpos($rule, 'required') !== false;
    }

    /**
     * Génère les composants de sécurité OpenAPI
     */
    public function generateSecuritySchemes(): array
    {
        return [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT'
            ],
            'apiKey' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Key'
            ],
            'oauth2' => [
                'type' => 'oauth2',
                'flows' => [
                    'authorizationCode' => [
                        'authorizationUrl' => '/oauth/authorize',
                        'tokenUrl' => '/oauth/token',
                        'scopes' => [
                            'read' => 'Lecture des données',
                            'write' => 'Écriture des données',
                            'admin' => 'Administration'
                        ]
                    ]
                ]
            ]
        ];
    }
}