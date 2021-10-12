<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use Validator;
use App\Models\Lecteur;

class ProfilController extends Controller {

    // Collection des rôles disponibles avec leur libellé
    private $roles = array("admin" => "Administrateur", "comment" => "Commentateur", "contrib" => "Contributeur");
    
    /**
     * Initialise le formulaire de saisie d'un profil
     * @return Vue profil
     */
    public function getProfil() {   
        $erreur = "";
        $user = Auth::user();
        $id_lecteur = $user->id;
        $lecteur = Lecteur::find($id_lecteur);    
        return view('formProfil', compact('lecteur','user','erreur'));
    }

    /**
     * Enregistre le profil
     * @return Vue home
     */
    public function setProfil() {
        // Messages d'erreur personnalisés
        $messages = array( 
            'nom.required' => 'Il faut saisir un nom.',
            'prenom.required' => 'Il faut sélectionner un prénom.',
            'cp.required' => 'Il faut saisir un Code postal.',
            'cp.numeric' => 'Le Code postal doit être une valeur numérique.'
            );
        // Liste des champs à vérifier
        $regles = array(
            'nom' => 'required',
            'prenom' => 'required',
            'cp' => 'required | numeric'
            );
        $validator = Validator::make(Request::all(), $regles, $messages);
        // On retourne au formulaire s'il y a un problème
        if ($validator->fails()) {
            return redirect('formProfil')
                            ->withErrors($validator)
                            ->withInput();
        }
        // On récupère les données et on enregistre
        $user = Auth::user();
        $id_lecteur = $user->id;
        $lecteur = new Lecteur();
        $lecteur->id_lecteur = $id_lecteur;
        $lecteur->nom = Request::input('nom');
        $lecteur->prenom = Request::input('prenom');
        $lecteur->rue = Request::input('rue');
        $lecteur->cp = Request::input('cp');
        $lecteur->ville = Request::input('ville');
        $lecteur->save();
        return redirect('/home');
    }

}
