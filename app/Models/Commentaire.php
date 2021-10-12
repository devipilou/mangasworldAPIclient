<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commentaire extends Model
{
    protected $table = 'commentaire';
    public $timestamps = false;
    protected $primaryKey = 'id_commentaire';
    protected $fillable = ['id_commentaire','lib_commentaire', 'id_manga', 'id_lecteur'];
    
    public function manga()
    {
        return $this->belongsTo('App\Models\Manga', 'id_manga', 'id_manga');
    }
    
    public function lecteur()
    {
        return $this->belongsTo('App\Models\Lecteur', 'id_lecteur', 'id_lecteur');
    }

    /**
     * Redéfinit toArray() de Model pour former
     * un tableau des propriétés simples
     * @return type tableau de propriétés
     */
    public function toArray() {
        $data = parent::toArray();
        $data['lecteur'] = $this->lecteur;
        return $data;
    }  
      
    
}
