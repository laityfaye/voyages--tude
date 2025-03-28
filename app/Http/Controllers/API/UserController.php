<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Seuls les administrateurs peuvent voir tous les utilisateurs
        if ($user->role->nom !== 'Administrateur') {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        $utilisateurs = User::with('role')->get();
        
        return response()->json($utilisateurs);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        // Seuls les administrateurs peuvent créer des utilisateurs
        if ($user->role->nom !== 'Administrateur') {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'matricule' => 'required|string|max:255|unique:users',
            'dateRecrutement' => 'required|date',
            'etablissement' => 'required|string|max:255',
            'departement' => 'required|string|max:255',
            'laboratoire' => 'required|string|max:255',
            'responsableLaboratoire' => 'required|string|max:255',
            'diplome' => 'required|string|max:255',
            'dateObtentionDiplome' => 'required|date',
            'telephoneProfessionnel' => 'required|string|max:255',
            'adresseProfessionnelle' => 'required|string',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $utilisateur = new User([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'matricule' => $request->matricule,
            'dateRecrutement' => $request->dateRecrutement,
            'etablissement' => $request->etablissement,
            'departement' => $request->departement,
            'laboratoire' => $request->laboratoire,
            'responsableLaboratoire' => $request->responsableLaboratoire,
            'themesRecherche' => $request->themesRecherche,
            'partenariatsReseauxRecherche' => $request->partenariatsReseauxRecherche,
            'diplome' => $request->diplome,
            'dateObtentionDiplome' => $request->dateObtentionDiplome,
            'fonctionsOccupees' => $request->fonctionsOccupees,
            'publicationsRecentes' => $request->publicationsRecentes,
            'telephoneProfessionnel' => $request->telephoneProfessionnel,
            'telephonePersonnel' => $request->telephonePersonnel,
            'adresseProfessionnelle' => $request->adresseProfessionnelle,
            'adressePersonnelle' => $request->adressePersonnelle,
            'estActif' => true,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id
        ]);
        
        $utilisateur->save();
        
        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'utilisateur' => $utilisateur->load('role')
        ], 201);
    }

    public function show($id)
    {
        $user = auth::user();
        
        // Si ce n'est pas un admin, on ne permet d'accéder qu'à son propre profil
        if ($user->role->nom !== 'Administrateur' && $user->id != $id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        $utilisateur = User::with('role')->findOrFail($id);
        
        return response()->json($utilisateur);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        // Vérification des droits d'accès
        if ($user->role->nom !== 'Administrateur' && $user->id != $id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        $utilisateur = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'matricule' => 'sometimes|required|string|max:255|unique:users,matricule,' . $id,
            'telephoneProfessionnel' => 'sometimes|required|string|max:255',
            'telephonePersonnel' => 'sometimes|string|max:255|nullable',
            'adresseProfessionnelle' => 'sometimes|required|string',
            'adressePersonnelle' => 'sometimes|string|nullable',
            'password' => 'sometimes|required|string|min:8',
            'role_id' => 'sometimes|required|exists:roles,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Mise à jour des champs
        if ($request->has('nom')) $utilisateur->nom = $request->nom;
        if ($request->has('prenom')) $utilisateur->prenom = $request->prenom;
        if ($request->has('email')) $utilisateur->email = $request->email;
        if ($request->has('matricule')) $utilisateur->matricule = $request->matricule;
        if ($request->has('telephoneProfessionnel')) $utilisateur->telephoneProfessionnel = $request->telephoneProfessionnel;
        if ($request->has('telephonePersonnel')) $utilisateur->telephonePersonnel = $request->telephonePersonnel;
        if ($request->has('adresseProfessionnelle')) $utilisateur->adresseProfessionnelle = $request->adresseProfessionnelle;
        if ($request->has('adressePersonnelle')) $utilisateur->adressePersonnelle = $request->adressePersonnelle;
        
        // Seul l'administrateur peut modifier le rôle
        if ($user->role->nom === 'Administrateur' && $request->has('role_id')) {
            $utilisateur->role_id = $request->role_id;
        }
        
        // Mise à jour du mot de passe si fourni
        if ($request->has('password')) {
            $utilisateur->password = Hash::make($request->password);
        }
        
        $utilisateur->save();
        
        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'utilisateur' => $utilisateur->load('role')
        ]);
    }

    public function destroy($id)
    {
        $user = auth::user();
        
        // Seul l'administrateur peut supprimer des utilisateurs
        if ($user->role->nom !== 'Administrateur') {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }
        
        $utilisateur = User::findOrFail($id);
        
        // Vérifier si l'utilisateur a des demandes de voyage
        if ($utilisateur->demandesVoyage()->count() > 0) {
            // Désactiver l'utilisateur au lieu de le supprimer
            $utilisateur->estActif = false;
            $utilisateur->save();
            
            return response()->json([
                'message' => 'L\'utilisateur a des demandes de voyage associées. Il a été désactivé.'
            ]);
        }
        
        $utilisateur->delete();
        
        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }

    public function getProfile(Request $request)
    {
        return response()->json($request->user()->load('role'));
    }
}