<?php

namespace Workspace\Handlers;

use Nexa\Attributes\Route;
use Nexa\Attributes\API;
use Nexa\Attributes\Performance;
use Nexa\Attributes\SmartCache;
use Nexa\Attributes\AutoCRUD;
use Nexa\Attributes\AutoTest;
use Nexa\Attributes\Validation;
use Nexa\Attributes\Secure;
use Nexa\Http\Request;
use Nexa\Http\Response;

#[AutoCRUD(
    fillable: ['name', 'description', 'price', 'category_id'],
    hidden: ['created_at', 'updated_at'],
    validation_rules: [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id'
    ],
    route_prefix: 'api/products',
    middleware: ['auth:api'],
    pagination: true,
    per_page: 20
)]
#[AutoTest(
    unit: true,
    integration: true,
    feature: true,
    test_cases: [
        'can_create_product',
        'can_update_product',
        'can_delete_product',
        'validates_required_fields'
    ],
    performance_test: true,
    performance_threshold: 500
)]
class SmartProductHandler
{
    #[Route(method: 'GET', path: '/products')]
    #[API(
        version: 'v1',
        summary: 'Liste tous les produits',
        description: 'Récupère une liste paginée de tous les produits disponibles',
        tags: ['products'],
        responses: [
            200 => 'Liste des produits récupérée avec succès',
            401 => 'Non autorisé'
        ]
    )]
    #[Performance(
        monitor: true,
        threshold: 800,
        log_slow: true,
        metric_name: 'products_index'
    )]
    #[SmartCache(
        strategy: 'adaptive',
        base_ttl: 1800,
        usage_multiplier: 2.0,
        max_ttl: 7200,
        tags: ['products', 'catalog'],
        auto_refresh: true,
        invalidate_on: ['product.created', 'product.updated', 'product.deleted']
    )]
    #[Secure(permissions: ['products.read'])]
    public function index(Request $request): Response
    {
        // La logique sera générée automatiquement par AutoCRUD
        // Mais peut être personnalisée ici
        
        $filters = $request->only(['category_id', 'price_min', 'price_max', 'search']);
        
        // Le cache intelligent s'adaptera automatiquement
        // Le monitoring des performances est automatique
        // La validation des permissions est automatique
        
        return response()->json([
            'message' => 'Products retrieved successfully',
            'data' => [], // Sera rempli par le système AutoCRUD
            'meta' => [
                'filters_applied' => $filters,
                'cache_strategy' => 'adaptive',
                'performance_monitored' => true
            ]
        ]);
    }
    
    #[Route(method: 'POST', path: '/products')]
    #[API(
        version: 'v1',
        summary: 'Crée un nouveau produit',
        description: 'Crée un nouveau produit avec validation automatique',
        tags: ['products']
    )]
    #[Performance(
        monitor: true,
        threshold: 1000,
        metric_name: 'products_create'
    )]
    #[Validation(rules: [
        'name' => 'required|string|max:255|unique:products',
        'description' => 'nullable|string|max:1000',
        'price' => 'required|numeric|min:0|max:999999.99',
        'category_id' => 'required|exists:categories,id',
        'tags' => 'nullable|array',
        'tags.*' => 'string|max:50'
    ])]
    #[Secure(permissions: ['products.create'])]
    #[AutoTest(
        test_cases: [
            'validates_required_name',
            'validates_unique_name',
            'validates_price_format',
            'validates_category_exists'
        ]
    )]
    public function store(Request $request): Response
    {
        // Validation automatique via l'attribut Validation
        // Permissions vérifiées via l'attribut Secure
        // Performance monitorée via l'attribut Performance
        // Tests générés automatiquement via AutoTest
        
        $data = $request->validated();
        
        // Logique métier personnalisée
        if (isset($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }
        
        return response()->json([
            'message' => 'Product created successfully',
            'data' => $data // Sera traité par AutoCRUD
        ], 201);
    }
    
    #[Route(method: 'GET', path: '/products/{id}')]
    #[API(
        version: 'v1',
        summary: 'Récupère un produit spécifique',
        description: 'Récupère les détails d\'un produit par son ID',
        parameters: [
            'id' => 'ID du produit à récupérer'
        ]
    )]
    #[Performance(
        monitor: true,
        threshold: 300,
        metric_name: 'products_show'
    )]
    #[SmartCache(
        strategy: 'usage_based',
        base_ttl: 3600,
        usage_multiplier: 1.8,
        tags: ['product_{id}'],
        compress: true
    )]
    #[Secure(permissions: ['products.read'])]
    public function show(Request $request, int $id): Response
    {
        // Cache intelligent basé sur l'utilisation
        // Compression automatique pour optimiser la mémoire
        // Monitoring des performances avec seuil bas (300ms)
        
        return response()->json([
            'message' => 'Product retrieved successfully',
            'data' => ['id' => $id], // Sera rempli par AutoCRUD
            'meta' => [
                'cache_hit' => true, // Sera déterminé automatiquement
                'performance_threshold' => '300ms'
            ]
        ]);
    }
    
    #[Route(method: 'PUT', path: '/products/{id}')]
    #[API(
        version: 'v1',
        summary: 'Met à jour un produit',
        description: 'Met à jour les informations d\'un produit existant'
    )]
    #[Performance(
        monitor: true,
        threshold: 1200,
        log_slow: true,
        alerts: ['email:admin@example.com'],
        metric_name: 'products_update'
    )]
    #[Validation(rules: [
        'name' => 'sometimes|string|max:255|unique:products,name,{id}',
        'description' => 'sometimes|string|max:1000',
        'price' => 'sometimes|numeric|min:0|max:999999.99',
        'category_id' => 'sometimes|exists:categories,id'
    ])]
    #[Secure(permissions: ['products.update'])]
    #[SmartCache(
        strategy: 'time_based',
        invalidate_on: ['product.updated'],
        tags: ['product_{id}', 'products']
    )]
    public function update(Request $request, int $id): Response
    {
        // Validation conditionnelle (sometimes)
        // Invalidation automatique du cache
        // Alertes par email si performance dégradée
        // Règle de validation unique avec exclusion de l'ID actuel
        
        $data = $request->validated();
        
        return response()->json([
            'message' => 'Product updated successfully',
            'data' => array_merge(['id' => $id], $data)
        ]);
    }
    
    #[Route(method: 'DELETE', path: '/products/{id}')]
    #[API(
        version: 'v1',
        summary: 'Supprime un produit',
        description: 'Supprime définitivement un produit'
    )]
    #[Performance(
        monitor: true,
        threshold: 500,
        metric_name: 'products_delete'
    )]
    #[Secure(permissions: ['products.delete'])]
    #[SmartCache(
        invalidate_on: ['product.deleted'],
        tags: ['product_{id}', 'products', 'catalog']
    )]
    public function destroy(Request $request, int $id): Response
    {
        // Invalidation en cascade du cache
        // Vérification des permissions de suppression
        // Monitoring de la performance de suppression
        
        return response()->json([
            'message' => 'Product deleted successfully',
            'data' => ['id' => $id]
        ]);
    }
    
    #[Route(method: 'GET', path: '/products/analytics')]
    #[API(
        version: 'v1',
        summary: 'Analytics des produits',
        description: 'Récupère les statistiques et analytics des produits'
    )]
    #[Performance(
        monitor: true,
        threshold: 2000,
        cache_metrics: true,
        metric_name: 'products_analytics'
    )]
    #[SmartCache(
        strategy: 'adaptive',
        base_ttl: 7200,
        max_ttl: 86400,
        tags: ['analytics', 'products'],
        auto_refresh: true
    )]
    #[Secure(permissions: ['products.analytics'])]
    public function analytics(Request $request): Response
    {
        // Cache longue durée pour les analytics
        // Seuil de performance élevé (2s) car calculs complexes
        // Mise en cache des métriques de performance
        // Rafraîchissement automatique du cache
        
        return response()->json([
            'message' => 'Analytics retrieved successfully',
            'data' => [
                'total_products' => 0,
                'average_price' => 0,
                'top_categories' => [],
                'performance_metrics' => [
                    'cache_strategy' => 'adaptive',
                    'auto_refresh' => true,
                    'ttl_range' => '2h-24h'
                ]
            ]
        ]);
    }
}