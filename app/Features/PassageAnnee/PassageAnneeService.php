<?php

namespace App\Features\PassageAnnee;

use App\Features\Absence\Absence;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use App\Features\Groupe\Groupe;
use App\Features\Inscription\Inscription;
use App\Features\Inscription\InscriptionService;
use App\Features\NiveauEtude\NiveauEtude;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PassageAnneeService {
    //==================================================================================================================
    // Années pour les selects (la valeur est la clé, utilisée dans l'URL)
    //==================================================================================================================
    public static function get_annees_pour_select() : array {
        return AnneeUniversitaire
            ::query()
            ->orderByDesc('date_debut')
            ->get()
            ->map(fn(AnneeUniversitaire $annee) => ['valeur' => $annee->cle, 'label' => $annee->libelle])
            ->all();
    }



    //==================================================================================================================
    // Année cible proposée : la première qui commence après l'année source
    //==================================================================================================================
    public static function get_annee_cible_par_defaut(AnneeUniversitaire $source) : ?AnneeUniversitaire {
        return AnneeUniversitaire
            ::query()
            ->where('date_debut', '>', $source->date_debut?->format('Y-m-d'))
            ->orderBy('date_debut')
            ->first();
    }



    //==================================================================================================================
    // Décisions possibles selon le niveau (RG-16) : "Diplômé" seulement en dernière année, "Admis" sinon
    //==================================================================================================================
    public static function get_decisions_possibles(NiveauEtude $niveau) : array {
        return $niveau->est_derniere_annee
            ? [InscriptionService::decision_diplome, InscriptionService::decision_redoublant, InscriptionService::decision_sortant]
            : [InscriptionService::decision_admis, InscriptionService::decision_redoublant, InscriptionService::decision_sortant];
    }



    //==================================================================================================================
    // Groupes de l'année source, avec leur avancement (décisions déjà posées / effectif)
    //==================================================================================================================
    public static function get_groupes_source(AnneeUniversitaire $source) : array {
        return Groupe
            ::query()
            ->where('annee_universitaire_id', $source->id)
            ->with('niveau_etude')
            ->withCount([
                            'inscriptions',
                            'inscriptions as nb_decisions' => fn(Builder $query) => $query->whereNotNull('decision'),
                        ])
            ->orderBy('nom')
            ->get()
            ->map(fn(Groupe $groupe) => [
                'cle'          => $groupe->cle,
                'nom'          => $groupe->nom,
                'niveau'       => $groupe->niveau_etude->code,
                'effectif'     => (int) $groupe->inscriptions_count,
                'nb_decisions' => (int) $groupe->nb_decisions,
            ])
            ->all();
    }



    //==================================================================================================================
    // Tout ce qu'il faut pour préparer le passage d'un groupe vers l'année cible
    //==================================================================================================================
    public static function get_detail_groupe(Groupe $groupe, AnneeUniversitaire $cible) : array {
        $groupe->loadMissing(['niveau_etude.cycle', 'niveau_etude.suivants']);

        $niveau = $groupe->niveau_etude;


        //==============================================================================================================
        // Inscriptions du groupe, triées par nom d'étudiant
        //==============================================================================================================
        $inscriptions = $groupe
            ->inscriptions()
            ->with('etudiant')
            ->get()
            ->sortBy(fn(Inscription $inscription) => "{$inscription->etudiant->nom} {$inscription->etudiant->prenom}")
            ->values();

        $etudiant_ids = $inscriptions->pluck('etudiant_id')
                                     ->all();


        //==============================================================================================================
        // Inscriptions déjà créées dans l'année cible (passage refait) et nombre d'absences dans ce groupe
        //==============================================================================================================
        $inscriptions_cible = Inscription
            ::query()
            ->where('annee_universitaire_id', $cible->id)
            ->whereIn('etudiant_id', $etudiant_ids)
            ->with('groupe')
            ->get()
            ->keyBy('etudiant_id');

        $absences = Absence
            ::query()
            ->whereIn('etudiant_id', $etudiant_ids)
            ->whereHas('seance', fn(Builder $query) => $query->where('groupe_id', $groupe->id))
            ->selectRaw('etudiant_id, COUNT(*) as total')
            ->groupBy('etudiant_id')
            ->pluck('total', 'etudiant_id');


        //==============================================================================================================
        // Niveaux de destination : ceux du parcours (admis) + le niveau actuel (redoublants)
        //==============================================================================================================
        $niveaux_destination = collect([...$niveau->suivants->all(), $niveau])
            ->unique('id')
            ->values();

        $destinations = $niveaux_destination->map(function (NiveauEtude $destination) use ($cible, $niveau) {
            $groupes = Groupe
                ::query()
                ->where('annee_universitaire_id', $cible->id)
                ->where('niveau_etude_id', $destination->id)
                ->orderBy('nom')
                ->get();

            return [
                'niveau_id'        => $destination->id,
                'code'             => $destination->code,
                'libelle'          => $destination->libelle,
                'est_redoublement' => $destination->id === $niveau->id,
                'groupes'          => $groupes->map(fn(Groupe $groupe) => ['valeur' => $groupe->id, 'label' => $groupe->nom])
                                              ->all(),
                'groupe_id_defaut' => $groupes->first()?->id,
                'nom_automatique'  => Groupe::faker(['annee' => $cible, 'niveau' => $destination])['nom'],
            ];
        });

        return [
            'groupe'              => [
                'id'                 => $groupe->id,
                'cle'                => $groupe->cle,
                'nom'                => $groupe->nom,
                'niveau_code'        => $niveau->code,
                'niveau_libelle'     => $niveau->libelle,
                'est_derniere_annee' => $niveau->est_derniere_annee,
            ],
            'suivants'            => $niveau->suivants
                ->map(fn(NiveauEtude $suivant) => ['id' => $suivant->id, 'code' => $suivant->code, 'libelle' => $suivant->libelle])
                ->values()
                ->all(),
            'decisions_possibles' => self::get_decisions_possibles($niveau),
            'destinations'        => $destinations->all(),
            'etudiants'           => $inscriptions->map(fn(Inscription $inscription) => [
                'inscription_id'  => $inscription->id,
                'nom_complet'     => $inscription->etudiant->nom_complet,
                'cne'             => $inscription->etudiant->cne,
                'nb_absences'     => (int) ($absences[$inscription->etudiant_id] ?? 0),
                'decision'        => $inscription->decision,
                'niveau_cible_id' => $inscriptions_cible[$inscription->etudiant_id]?->groupe?->niveau_etude_id ?? null,
            ])
                                                  ->all(),
        ];
    }



    //==================================================================================================================
    // Exécution : décision sur l'inscription passée, inscription dans l'année cible (une seule transaction)
    //==================================================================================================================
    public static function executer(Groupe $groupe, AnneeUniversitaire $cible, array $destinations, array $lignes) : array {
        return DB::transaction(function () use ($groupe, $cible, $destinations, $lignes) {
            $niveau = $groupe->niveau_etude()
                             ->with(['cycle', 'suivants'])
                             ->firstOrFail();


            //==========================================================================================================
            // Groupe d'accueil choisi pour chaque niveau (null = à créer automatiquement)
            //==========================================================================================================
            $choix = [];

            foreach ($destinations as $destination) {
                $choix[(int) $destination['niveau_id']] = isset($destination['groupe_id']) ? (int) $destination['groupe_id'] : null;
            }

            $groupes_accueil = [];

            $obtenir_groupe = function (int $niveau_id) use (&$groupes_accueil, $choix, $cible) : Groupe {
                if (isset($groupes_accueil[$niveau_id])) {
                    return $groupes_accueil[$niveau_id];
                }

                $groupe_id = $choix[$niveau_id] ?? null;

                $groupe_accueil = $groupe_id
                    ? Groupe::findOrFail($groupe_id)
                    : Groupe::create(Groupe::faker(['annee' => $cible, 'niveau' => NiveauEtude::findOrFail($niveau_id)]));

                return $groupes_accueil[$niveau_id] = $groupe_accueil;
            };


            //==========================================================================================================
            // Une décision par étudiant
            //==========================================================================================================
            $compteurs = array_fill_keys(array_keys(InscriptionService::decisions), 0);

            foreach ($lignes as $ligne) {
                $inscription = Inscription::with('etudiant')
                                          ->findOrFail((int) $ligne['inscription_id']);
                $decision    = $ligne['decision'];

                $inscription->decision = $decision;
                $inscription->save();

                switch ($decision) {
                    case InscriptionService::decision_admis:
                        $niveau_id = (int) ($ligne['niveau_id'] ?? $niveau->suivants->first()?->id);
                        InscriptionService::inscrire($inscription->etudiant, $obtenir_groupe($niveau_id));
                        break;

                    case InscriptionService::decision_redoublant:
                        InscriptionService::inscrire($inscription->etudiant, $obtenir_groupe($niveau->id));
                        break;

                    default: // diplômé ou sortant : pas d'inscription l'année suivante
                        InscriptionService::desinscrire($inscription->etudiant, $cible);
                }

                $compteurs[$decision]++;
            }

            return $compteurs;
        });
    }
}
