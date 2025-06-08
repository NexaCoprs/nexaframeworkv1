<?php

namespace Workspace\Handlers;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Core\Logger;

class TestControllerController extends Controller
{
    /**
     * Affiche la liste des éléments
     */
    public function index()
    {
        Logger::info('TestControllerController index accessed');
        
        $data = [
            'title' => 'Liste des éléments',
            'items' => [
                ['id' => 1, 'name' => 'Élément 1'],
                ['id' => 2, 'name' => 'Élément 2'],
                ['id' => 3, 'name' => 'Élément 3']
            ]
        ];
        
        return $this->view('test.index', $data);
    }
    
    /**
     * Affiche un élément spécifique
     */
    public function show($id)
    {
        Logger::info('TestControllerController show accessed', ['id' => $id]);
        
        return $this->json([
            'id' => $id,
            'name' => 'Élément ' . $id,
            'description' => 'Description de l\'élément ' . $id
        ]);
    }
    
    /**
     * Crée un nouvel élément
     */
    public function store(Request $request)
    {
        Logger::info('TestControllerController store accessed', $request->all());
        
        // Simulation de la création
        $newId = rand(100, 999);
        
        return $this->json([
            'success' => true,
            'message' => 'Élément créé avec succès',
            'id' => $newId,
            'data' => $request->all()
        ], 201);
    }
    
    /**
     * Met à jour un élément
     */
    public function update(Request $request, $id)
    {
        Logger::info('TestControllerController update accessed', ['id' => $id, 'data' => $request->all()]);
        
        return $this->json([
            'success' => true,
            'message' => 'Élément mis à jour avec succès',
            'id' => $id,
            'data' => $request->all()
        ]);
    }
    
    /**
     * Supprime un élément
     */
    public function destroy($id)
    {
        Logger::info('TestControllerController destroy accessed', ['id' => $id]);
        
        return $this->json([
            'success' => true,
            'message' => 'Élément supprimé avec succès',
            'id' => $id
        ]);
    }
}