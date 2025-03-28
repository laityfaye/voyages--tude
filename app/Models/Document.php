<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'demande_voyage_id', 'titre', 'type', 'fichierUrl', 'format', 'taille'
    ];

    public function demandeVoyage()
    {
        return $this->belongsTo(DemandeVoyage::class);
    }
}