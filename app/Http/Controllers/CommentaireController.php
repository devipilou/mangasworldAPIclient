<?php

namespace App\Http\Controllers;

use Request;
use Exception;
use Session;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\Commentaire;
use App\Models\Manga;
use App\Models\Lecteur;
use GuzzleHttp\Client;


class CommentaireController extends Controller {

    /**
     * Affiche la liste de tous les Commentaires
     * Si la Session contient un message d'erreur, 
     * on le récupère et on le supprime de la Session
     * @return Vue listerCommentaires
     */
    public function getCommentaires($idManga) {
        $erreur = Session::get('erreur');
        Session::forget('erreur');
        // On récupère la liste de tous les commentaires
        $client = new Client();
        $uri = 'http://localhost/mangasworldAPI/public/api/commentaire/manga/' . $idManga;
        $response = $client->request('GET', $uri);
        $manga = json_decode($response->getBody()->getContents());
        $commentaires = $manga->commentaires;
        // On affiche la liste de ces mangas        
        return view('listeCommentaires', compact('commentaires', 'manga', 'erreur'));
        // return $commentaires;
    }

    /**
     * Lit le Commentaire à modifier et récupère le Manga
     * auquel il est rattaché. Vérifie que l'utilisateur
     * connecté a bien le droit de le modifier et 
     * initialise le formulaire en mode Modification si
     * c'est le cas sinon l'initialise en mode Consultation
     * @param int $id Id du Commentaire à modifier
     * @param string $erreur message d'erreur (paramètre optionnel)
     * @return Vue formCommentaire
     */
    public function updateCommentaire($idCommentaire) {
        $readonly = null;
        $erreur = Session::get('erreur');
        Session::forget('erreur');
        $user = Auth::guard()->user();
        $client = new Client();
        $uri = 'http://localhost/mangasworldAPI/public/api/commentaire/' . $idCommentaire;
        $response = $client->request('GET', $uri, ['headers' => ['Authorization' => 'Bearer ' . $user->api_token]]);
        $commentaire = json_decode($response->getBody()->getContents());
        $manga = Manga::find($commentaire->id_manga);
        $titreVue = "Modification d'un Commentaire";
        $user = Auth::user();
        if ($user != null) {
            if (!($user->role == 'comment' && $user->id == $commentaire->id_lecteur)) {
                $erreur = 'Vous ne pouvez que consulter ce commentaire, mais pas le modifier';
                $readonly = 'readonly';
            }
        }
        // Affiche le formulaire en lui fournissant les données à afficher
        // return $commentaire;
        return view('formCommentaire', compact('manga', 'commentaire', 'titreVue', 'readonly', 'erreur'));
    }

    /**
     * Lit le Commentaire à consulter et récupère le Manga
     * auquel il est rattaché. Vérifie que l'utilisateur
     * connecté a bien le droit de le consulter et 
     * initialise le formulaire en mode Modification si
     * c'est le cas sinon l'initialise en mode Consultation
     * @param int $id Id du Commentaire à consulter
     * @param string $erreur message d'erreur (paramètre optionnel)
     * @return Vue formCommentaire
     */
    public function showCommentaire($idCommentaire) {
        $readonly = null;
        $erreur = Session::get('erreur');
        Session::forget('erreur');
        $user = Auth::guard()->user();
        $client = new Client();
        $uri = 'http://localhost/mangasworldAPI/public/api/commentaire/' . $idCommentaire;
        $response = $client->request('GET', $uri);
        $commentaire = json_decode($response->getBody()->getContents());
        $manga = Manga::find($commentaire->id_manga);
        $titreVue = "Consultation d'un Commentaire";
        if ($user != null) {
            if (!$user->role == 'comment' && $user->id == $commentaire->id_lecteur) {
                $erreur = 'Vous ne pouvez que consulter ce commentaire, mais pas le modifier';
                $readonly = 'readonly';
            }
        } else{
            $readonly = 'readonly';
        }
        // Affiche le formulaire en lui fournissant les données à afficher
        return view('formCommentaire', compact('manga', 'commentaire', 'titreVue', 'readonly', 'erreur'));
    }

    /**
     * Enregistre une mise à jour d'un Commentaire 
     * après avoir vérifié que l'utilisateur est bien
     * habilité à le faire
     * Si la modification d'un Commentaire
     * provoque une erreur fatale, on la place
     * dans la Session et on réaffiche le formulaire
     * Sinon réaffiche la liste des mangas
     * @return Redirection listerCommentaires
     */
    public function validateCommentaire() {
        // Récupération des valeurs saisies
        $idManga = Request::input('id_manga'); // id dans le champs caché
        $idCommentaire = Request::input('id_commentaire'); // id dans le champs caché
        //validation de la présence des données
        $regles = array(
            'lib_commentaire'=>'required',
        );
        $messages = array(
            'lib_commentaire.required' => 'Il faut saisir un commentaire',
        );
        $validator = Validator::make(Request::all(),$regles, $messages);
        // On retourne au formulaire s'il y a un problème
        if($validator->fails()){
            if($idCommentaire>0){
                return redirect ('modifierCommentaire/' .$idCommentaire)
                ->withErrors($validator)
                ->withInput();
            }else{
                return redirect ('ajouterCommentaire/' .$idManga)
                ->withErrors($validator)
                ->withInput();
            }
        }
        $libCommentaire = Request::input('lib_commentaire');

        $user = Auth::guard()->user();
        $client = new Client();
        $uri = 'http://localhost/mangasworldAPI/public/api/commentaire';
        $commentaire = new Commentaire();
        $commentaire->lib_commentaire = $libCommentaire;
        $commentaire->id_lecteur = Auth::user()->id;
        $commentaire->id_manga = $idManga;

        try {
            if ($idCommentaire > 0) {
                $commentaire->id_commentaire = $idCommentaire;
                $data = $commentaire->toArray();
                $response = $client->request('PUT', $uri, ['headers' => ['Authorization' => 'Bearer ' . $user->api_token], 'query' => $data]);
                $commentaire = json_decode($response->getBody()->getContents());
            } else {
                $data = $commentaire->toArray();
                $response = $client->request('POST', $uri, ['headers' => ['Authorization' => 'Bearer ' . $user->api_token], 'query' => $data]);
                $commentaire = json_decode($response->getBody()->getContents());
            }
        } catch (Exception $ex) {
            $erreur = $ex->getMessage();
            Session::put('erreur', $erreur);
        }
        // On réaffiche la liste des mangas
        return redirect('/listerCommentaires/' . $idManga);
    }

    /**
     * Initialise le formulaire d'ajout d'un commentaire
     * sous réserve que l'utilisateur en ait bien le droit
     * @return Vue formCommentaire
     */
    public function addCommentaire($idManga) {
        $readonly = null;
        $erreur = Session::get('erreur');
        Session::forget('erreur');
        $user = Auth::user();
        if (!$user->role == 'comment') {
            $erreur = 'Vous ne disposez pas des droits pour ajouter des commentaires !';
            Session::put('erreur', $erreur);
            return $this->getCommentaires($idManga);
        }
        $commentaire = new Commentaire();
        $manga = Manga::find($idManga);
        $titreVue = "Ajout d'un Commentaire";
        // Affiche le formulaire en lui fournissant les données à afficher
        return view('formCommentaire', compact('manga', 'commentaire', 'titreVue', 'readonly', 'erreur'));
    }

    /**
     * Supression d'un Commentaire
     * Si la suppression provoque une erreur fatale
     * on la place dans la Session
     * Dans tous les cas on réaffiche la liste des mangas
     * @param int $idCommentaire : Id du Commentaire à supprimer
     * @param int $idManga : Id du Manga du Commentaire à supprimer
     * @return Redirection listerCommentaires
     */
    public function deleteCommentaire($idCommentaire, $idManga) {
        $erreur = "";
        $commentaire = Commentaire::find($idCommentaire);
        try {
            $user = Auth::user();
            if (!($user->role == 'comment' && $user->id == $commentaire->id_lecteur)) {
                $erreur = 'Vous ne pouvez supprimer que vos propres commentaires !';
                Session::put('erreur', $erreur);
                return $this->getCommentaires($commentaire->id_manga);
            }
            $client = new Client();
            $uri = 'http://localhost/mangasworldAPI/public/api/commentaire/' . $idCommentaire;
            $response = $client->request('DELETE', $uri, ['headers' => ['Authorization' => 'Bearer ' . $user->api_token]]);
            // On réaffiche la liste des commentaires
            return redirect('/listerCommentaires/' . $idManga);
        } catch (Exception $ex) {
            Session::put('erreur', $ex->getMessage());
            return $this->getCommentaires($commentaire->id_manga);
        }
    }

}
