<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DemandeVoyage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DemandeVoyageController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Vérification de l'authentification
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Utilisateur non authentifié'], 401);
            }

            // Débogage des relations
            if (!$user->relationLoaded('role')) {
                Log::info('Relation role non chargée', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles ?? 'Aucun rôle'
                ]);
            }

            // Vérification sécurisée du rôle
            $isAdmin = optional($user->role)->nom === 'Administrateur';

            // Récupération des demandes avec gestion des erreurs
            if ($isAdmin) {
                $demandes = DemandeVoyage::with(['user', 'documents'])
                    ->latest()
                    ->get();
            } else {
                // Vérification de l'existence de la relation demandesVoyage
                if (!method_exists($user, 'demandesVoyage')) {
                    Log::error('Méthode demandesVoyage non définie', [
                        'user_id' => $user->id,
                        'user_class' => get_class($user)
                    ]);
                    throw new \Exception('Relation demandesVoyage non configurée');
                }

                $demandes = $user->demandesVoyage()->with('documents')->latest()->get();
            }

            // Retour avec informations supplémentaires
            return response()->json([
                'demandes' => $demandes,
                'total' => $demandes->count(),
                'is_admin' => $isAdmin
            ]);

        } catch (\Exception $e) {
            // Log détaillé de l'erreur
            Log::error('Erreur lors de la récupération des demandes', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des demandes',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'destination' => 'required|string|max:255',
            'villePaysFinal' => 'required|string|max:255',
            'itineraire' => 'required|string',
            'etablissementAccueil' => 'required|string|max:255',
            'responsableAccueil' => 'required|string|max:255',
            'mentionLuEtApprouve' => 'required|boolean',
            'nomChefEtablissement' => 'required|string|max:255' // Ajout de cette validation
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // Vérification d'éligibilité
        if (!$user->verifierEligibilite()) {
            return response()->json([
                'message' => "Vous n'êtes pas éligible pour un voyage. Soit vous n'avez pas l'ancienneté requise, soit vous avez déjà effectué deux voyages."
            ], 403);
        }
        
        $demande = new DemandeVoyage([
            'user_id' => $user->id,
            'date_demande' => now()->toDateString(),
            'destination' => $request->destination,
            'ville_pays_final' => $request->villePaysFinal,
            'itineraire' => $request->itineraire,
            'etablissement_accueil' => $request->etablissementAccueil,
            'responsable_accueil' => $request->responsableAccueil,
            'statut' => 'en_attente',
            'mention_lu_et_approuve' => $request->mentionLuEtApprouve,
            'nom_chef_etablissement' => $request->nomChefEtablissement // Ajout de cette ligne
        ]);

        $demande->save();

        return response()->json([
            'message' => 'Demande de voyage soumise avec succès',
            'demande' => $demande
        ], 201);
    }
    public function show($id)
    {
        $user = auth::user();
        $demande = DemandeVoyage::with(['user', 'documents'])->findOrFail($id);
        
        // Vérification des droits d'accès
        if ($user->role->nom !== 'Administrateur' && $demande->user_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        return response()->json($demande);
    }

    public function update(Request $request, $id)
    {
        try {
            // Récupération de la demande
            $demande = DemandeVoyage::findOrFail($id);
            $user = $request->user();

            // Vérification de l'authentification
            if (!$user) {
                return response()->json(['message' => 'Utilisateur non authentifié'], 401);
            }

            // Vérification du rôle de l'utilisateur
            $isAdmin = optional($user->role)->nom === 'Administrateur';

            // Validation par l'administrateur
            if ($isAdmin && $request->has('statut')) {
                $validator = Validator::make($request->all(), [
                    'statut' => 'required|in:en_attente,valide,rejete',
                    'commentaire' => 'nullable|string|max:500'
                ]);
                
                if ($validator->fails()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }

                // Mise à jour du statut
                $demande->statut = $request->statut;
                $demande->commentaire = $request->input('commentaire');
                $demande->date_validation = now();
                
                if (!$demande->save()) {
                    throw new \Exception('Erreur lors de la sauvegarde de la demande');
                }

                return response()->json([
                    'message' => 'Statut de la demande mis à jour avec succès',
                    'demande' => $demande
                ]);
            }

            // Modification par l'enseignant-chercheur
            if ($user->id === $demande->user_id) {
                // Vérifier le statut avant de permettre la modification
                if ($demande->statut !== 'en_attente') {
                    return response()->json([
                        'message' => 'Impossible de modifier une demande qui a déjà été traitée'
                    ], 403);
                }

                // Validation des champs modifiables
                $validator = Validator::make($request->all(), [
                    'destination' => 'sometimes|required|string|max:255',
                    'ville_pays_final' => 'sometimes|required|string|max:255',
                    'itineraire' => 'sometimes|required|string|max:500',
                    'etablissement_accueil' => 'sometimes|required|string|max:255',
                    'responsable_accueil' => 'sometimes|required|string|max:255',
                    
                    // Ajout d'une validation pour interdire la modification du statut
                    'statut' => 'prohibited'
                ], [
                    'statut.prohibited' => 'Vous ne pouvez pas modifier le statut de votre demande.'
                ]);
                
                if ($validator->fails()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }

                // Mise à jour selective des champs
                $demande->fill($request->only([
                    'destination', 'ville_pays_final', 'itineraire', 
                    'etablissement_accueil', 'responsable_accueil'
                ]));
                
                if (!$demande->save()) {
                    throw new \Exception('Erreur lors de la mise à jour de la demande');
                }

                return response()->json([
                    'message' => 'Demande mise à jour avec succès',
                    'demande' => $demande
                ]);
            }

            return response()->json(['message' => 'Action non autorisée'], 403);

        } catch (\Exception $e) {
            // Log de l'erreur
            Log::error('Erreur lors de la mise à jour de la demande : ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue lors du traitement de votre demande',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne'
            ], 500);
        }
    }
    
    // Méthode de création de notification (à adapter à votre modèle)
    private function createNotification($demande)
    {
        try {
            // Vérifiez que votre modèle Notification existe et a les bons attributs
            Notification::create([
                'user_id' => $demande->user_id,
                'message' => "Votre demande de voyage a été " . 
                             ($demande->statut == 'valide' ? 'validée' : 'rejetée'),
                'estLue' => false,
                'type' => $demande->statut == 'valide' ? 'success' : 'danger',
                'dateCreation' => now()
            ]);
        } catch (\Exception $e) {
            Log::warning('Erreur lors de la création de la notification : ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $user = auth::user();
        $demande = DemandeVoyage::findOrFail($id);
        
        // Vérification des droits
        if ($user->role->nom !== 'Administrateur' && $demande->user_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        // Vérifier si la demande peut être supprimée (seulement si en attente)
        if ($demande->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Seules les demandes en attente peuvent être supprimées'
            ], 400);
        }
        
        // Supprimer les documents associés
        foreach ($demande->documents as $document) {
            // Supprimer le fichier physique
            if (file_exists(storage_path('app/public/' . $document->fichierUrl))) {
                unlink(storage_path('app/public/' . $document->fichierUrl));
            }
            $document->delete();
        }
        
        $demande->delete();
        
        return response()->json(['message' => 'Demande supprimée avec succès']);
    }
}