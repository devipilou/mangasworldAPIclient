<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Support\Facades\Auth;
use Session;
use GuzzleHttp\Client;

class GenreController extends Controller {

    /**
     * Récupère la liste des genres 
     * @return Collection de Genre
     */
    public function getGenres() {
        $erreur = Session::get('erreur');
        Session::forget('erreur');
        $client = new Client();
        $uri = 'http://localhost/mangasworldAPI/public/api/genre';
        $response = $client->request('GET', $uri);
        $genres = json_decode($response->getBody()->getContents());
        return $genres;
    }
    /**
     * Récupère la collection des genres 
     * @return View FormGenre
     */
    public function getFormGenre(){
        $erreur = Session::get('erreur');
        Session::forget('erreur');
        $user = Auth::guard()->user();
        $genres = $this->getGenres();
        return view ('formGenre', compact('genres', 'erreur', 'user'));
    }



}
