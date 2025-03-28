<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    // 1|H5qRIGjO7tlsff9DujiwQoYbf1dLysvhYMvWcz3C0f9bb4a3
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom', 'prenom', 'email', 'matricule', 'dateRecrutement',
        'etablissement', 'departement', 'laboratoire', 'responsableLaboratoire',
        'themesRecherche', 'partenariatsReseauxRecherche', 'diplome',
        'dateObtentionDiplome', 'fonctionsOccupees', 'publicationsRecentes',
        'telephoneProfessionnel', 'telephonePersonnel', 'adresseProfessionnelle',
        'adressePersonnelle', 'estActif', 'password', 'role_id'
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function demandesVoyage()
    {
        return $this->hasMany(DemandeVoyage::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class,'user_id');
    }

    public function verifierEligibilite()
    {
        $dateRecrutement = Carbon::parse($this->dateRecrutement);
        $deuxAnsDepuisRecrutement = $dateRecrutement->diffInYears(now()) >= 2;
        $nombreVoyages = $this->getNombreVoyagesEffectues();
        
        return $deuxAnsDepuisRecrutement && $nombreVoyages < 2;
    }

    public function getNombreVoyagesEffectues()
    {
        return $this->demandesVoyage()->where('statut', 'valide')->count();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}


