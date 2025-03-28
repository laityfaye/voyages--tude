<?php

// app/Http/Controllers/API/DocumentController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DemandeVoyage;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'demande_voyage_id' => 'required|exists:demandes_voyage,id',
            'titre' => 'required|string',
            'type' => 'required|string',
            'fichier' => 'required|file|mimes:pdf,doc,docx|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $demande = DemandeVoyage::findOrFail($request->demande_voyage_id);
        
        // Vérifier si l'utilisateur a le droit d'ajouter un document à cette demande
        if ($user->role->nom !== 'Administrateur' && $demande->utilisateur_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        // Vérifier si la demande est encore modifiable (en attente)
        if ($demande->statut !== 'en_attente' && $user->role->nom !== 'Administrateur') {
            return response()->json([
                'message' => 'Vous ne pouvez pas ajouter de documents à une demande déjà traitée'
            ], 403);
        }

        $file = $request->file('fichier');
        $path = $file->store('documents', 'public');
        
        $document = new Document([
            'demande_voyage_id' => $request->demande_voyage_id,
            'titre' => $request->titre,
            'type' => $request->type,
            'fichierUrl' => $path,
            'format' => $file->getClientOriginalExtension(),
            'taille' => $file->getSize()
        ]);
        
        $document->save();
        
        return response()->json([
            'message' => 'Document ajouté avec succès',
            'document' => $document
        ], 201);
    }

    public function show($id)
    {
        $document = Document::findOrFail($id);
        $user = auth::user();
        $demande = $document->demandeVoyage;
        
        // Vérifier les droits d'accès
        if ($user->role->nom !== 'Administrateur' && $demande->utilisateur_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        return response()->json($document);
    }

    public function download($id)
    {
        $document = Document::findOrFail($id);
        $user = auth::user();
        $demande = $document->demandeVoyage;
        
        // Vérifier les droits d'accès
        if ($user->role->nom !== 'Administrateur' && $demande->utilisateur_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        if (!Storage::disk('public')->exists($document->fichierUrl)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }
        
        //return Storage::disk('public')->download($document->fichierUrl, $document->titre . '.' . $document->format);
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        $user = auth::user();
        $demande = $document->demandeVoyage;
        
        // Vérifier les droits d'accès
        if ($user->role->nom !== 'Administrateur' && $demande->utilisateur_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        // Vérifier si la demande est encore modifiable
        if ($demande->statut !== 'en_attente' && $user->role->nom !== 'Administrateur') {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer des documents d\'une demande déjà traitée'
            ], 403);
        }
        
        // Supprimer le fichier physique
        if (Storage::disk('public')->exists($document->fichierUrl)) {
            Storage::disk('public')->delete($document->fichierUrl);
        }
        
        $document->delete();
        
        return response()->json(['message' => 'Document supprimé avec succès']);
    }
}