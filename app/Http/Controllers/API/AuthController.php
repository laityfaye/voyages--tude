<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Crée une nouvelle instance du contrôleur.
     *
     * @return void
     */
    public function __construct()
    {
        // Ne pas utiliser le middleware ici, il sera configuré dans les routes
    }

    /**
     * Authentification et génération du token JWT
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = auth('api')->attempt($credentials)) {
                return response()->json(['error' => 'Les informations d\'identification sont incorrectes.'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur interne du serveur.'], 500);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Déconnexion de l'utilisateur
     * 
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        
        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    /**
     * Rafraîchir le token JWT
     * 
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            // Utiliser JWTAuth pour rafraîchir le token
            $token = JWTAuth::parseToken()->refresh();
            return $this->respondWithToken($token);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Le token est invalide.'], 401);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
            // Si le token a expiré, on peut quand même essayer de le rafraîchir
            try {
                $token = JWTAuth::parseToken()->refresh();
                return $this->respondWithToken($token);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Le token a expiré et ne peut pas être rafraîchi.'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Le token ne peut pas être rafraîchi.'], 401);
        }
    }

    /**
     * Récupérer les informations de l'utilisateur connecté
     * 
     * @return JsonResponse
     */
    public function user(): JsonResponse
    {
        $user = auth('api')->user();
        
        // Filtrer les informations sensibles
        return response()->json([
            'id' => $user->id,
            'name' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            // Autres champs non sensibles que vous souhaitez exposer
        ]);
    }

    /**
     * Formater la réponse avec le token
     * 
     * @param string $token
     * @return JsonResponse
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        $user = auth('api')->user();
        
        // Filtrer les informations utilisateur sensibles
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            // Autres champs non sensibles que vous souhaitez exposer
        ];

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $userData
        ]);
    }
}