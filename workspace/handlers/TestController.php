<?php

namespace Workspace\Handlers;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;

class TestController extends Controller
{
    public function index()
    {
        return $this->view('welcome', [
            'title' => 'Test Controller',
            'message' => 'Ceci est un contrôleur de test'
        ]);
    }
    
    public function show($id)
    {
        return $this->json([
            'id' => $id,
            'message' => 'Affichage de l\'élément ' . $id
        ]);
    }
    
    public function create(Request $request)
    {
        if ($request->isPost()) {
            // Traitement des données POST
            return $this->json([
                'success' => true,
                'message' => 'Élément créé avec succès',
                'data' => $request->all()
            ]);
        }
        
        return $this->view('test.create');
    }
}