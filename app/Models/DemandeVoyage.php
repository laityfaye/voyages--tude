<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DemandeVoyage extends Model
{
    use HasFactory;

    protected $table = 'demande_voyages';

    protected $fillable = [
        'user_id', 
        'date_demande', 
        'destination', 
        'ville_pays_final', 
        'itineraire', 
        'etablissement_accueil', 
        'responsable_accueil', 
        'statut', 
        'mention_lu_et_approuve',
        'nom_chef_etablissement',
        'commentaire',
        'date_validation',
        'annee_dernier_voyage',
        'date_engagement',
        'date_visa_etablissement'
    ];
    protected $dates = [
        'date_validation'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}