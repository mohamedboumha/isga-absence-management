<?php

namespace App\Features\Etudiant;

use App\Features\Absence\Absence;
use App\Features\AnneeUniversitaire\AnneeUniversitaireService;
use App\Features\Groupe\Groupe;
use App\Features\Inscription\InscriptionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EtudiantImportService {
    const int nb_max_lignes = 1000;

    const string statut_nouveau  = 'nouveau';
    const string statut_modifie  = 'modifie';
    const string statut_inchange = 'inchange';
    const string statut_erreur   = 'erreur';


    //==================================================================================================================
    // En-têtes acceptés (après conversion par Laravel Excel : minuscules, sans accents, espaces => "_")
    //==================================================================================================================
    const array alias_colonnes = [
        'cne'            => ['cne', 'code_national', 'code_national_etudiant'],
        'nom'            => ['nom', 'nom_de_famille'],
        'prenom'         => ['prenom'],
        'email'          => ['e_mail', 'email', 'mail', 'adresse_e_mail', 'adresse_email'],
        'telephone'      => ['telephone', 'tel', 'gsm', 'portable'],
        'date_naissance' => ['date_de_naissance', 'date_naissance', 'naissance'],
        'groupe'         => ['groupe', 'groupe_2026_2027', 'classe'],
    ];

    const array colonnes_obligatoires = ['cne', 'nom', 'prenom'];


    //==================================================================================================================
    // ANALYSE : lit le fichier, vérifie chaque ligne, ne modifie rien en base
    //==================================================================================================================
    public static function analyser(UploadedFile $fichier) : array {
        $lignes_brutes = Excel::toArray(new EtudiantImportLecture(), $fichier)[0] ?? [];

        $lignes_brutes = array_filter($lignes_brutes, fn(array $ligne) => self::a_du_contenu($ligne));

        if (!$lignes_brutes) {
            throw ValidationException::withMessages(['fichier' => "Le fichier ne contient aucun étudiant (la première ligne doit contenir les en-têtes)."]);
        }

        if (count($lignes_brutes) > self::nb_max_lignes) {
            throw ValidationException::withMessages(['fichier' => "Le fichier contient " . count($lignes_brutes) . " lignes : " . self::nb_max_lignes . " au maximum par import."]);
        }


        //==============================================================================================================
        // Colonnes : on retrouve chaque champ à partir des en-têtes du fichier
        //==============================================================================================================
        $correspondances = self::get_correspondances(array_keys(reset($lignes_brutes)));

        $manquantes = array_diff(self::colonnes_obligatoires, array_keys($correspondances));

        if ($manquantes) {
            $noms = ['cne' => 'CNE', 'nom' => 'Nom', 'prenom' => 'Prénom'];

            throw ValidationException::withMessages([
                                                        'fichier' => "Colonne(s) manquante(s) : " . implode(', ', array_map(fn(string $cle) => $noms[$cle], $manquantes)) . ". Utilisez le modèle.",
                                                    ]);
        }


        //==============================================================================================================
        // Lignes normalisées (le numéro de ligne Excel = index + 2, la ligne 1 étant les en-têtes)
        //==============================================================================================================
        $lignes = [];

        foreach ($lignes_brutes as $index => $brute) {
            $lignes[] = self::normaliser($brute, $correspondances, $index + 2);
        }


        //==============================================================================================================
        // Données existantes, chargées en une fois
        //==============================================================================================================
        $annee     = AnneeUniversitaireService::get_active();
        $cnes      = array_filter(array_column($lignes, 'cne'));
        $emails    = array_filter(array_column($lignes, 'email'));
        $existants = Etudiant::query()
                             ->whereIn('cne', $cnes)
                             ->with('inscription_active.groupe')
                             ->get()
                             ->keyBy('cne');
        $supprimes = Etudiant::onlyTrashed()
                             ->whereIn('cne', $cnes)
                             ->pluck('cne')
                             ->all();
        $par_email = Etudiant::query()
                             ->whereIn('email', $emails)
                             ->get(['id', 'cne', 'email'])
                             ->keyBy(fn(Etudiant $etudiant) => mb_strtolower($etudiant->email));
        $groupes   = $annee
            ? Groupe::query()
                    ->where('annee_universitaire_id', $annee->id)
                    ->get()
                    ->keyBy(fn(Groupe $groupe) => mb_strtoupper($groupe->nom))
            : collect();

        $doublons_cne   = self::get_doublons(array_column($lignes, 'cne'));
        $doublons_email = self::get_doublons(array_map(fn(?string $email) => $email ? mb_strtolower($email) : null, array_column($lignes, 'email')));


        //==============================================================================================================
        // Chaque ligne : erreurs, puis nouveau / mis à jour / inchangé
        //==============================================================================================================
        $apercu     = [];
        $a_importer = [];

        foreach ($lignes as $ligne) {
            $erreurs = $ligne['erreurs'];

            /** @var Etudiant|null $existant */
            $existant = $ligne['cne'] ? $existants->get($ligne['cne']) : null;
            $groupe   = $ligne['groupe'] ? $groupes->get(mb_strtoupper($ligne['groupe'])) : null;

            if ($ligne['cne'] && in_array($ligne['cne'], $doublons_cne, true)) {
                $erreurs[] = "Ce CNE apparaît plusieurs fois dans le fichier.";
            }

            if ($ligne['cne'] && in_array($ligne['cne'], $supprimes, true)) {
                $erreurs[] = "Un étudiant supprimé porte déjà ce CNE.";
            }

            if ($ligne['email']) {
                $email = mb_strtolower($ligne['email']);

                if (in_array($email, $doublons_email, true)) {
                    $erreurs[] = "Cet e-mail apparaît plusieurs fois dans le fichier.";
                } elseif ($par_email->has($email) && $par_email->get($email)->cne !== $ligne['cne']) {
                    $erreurs[] = "Cet e-mail est déjà utilisé par l'étudiant {$par_email->get($email)->cne}.";
                }
            }

            if ($ligne['groupe'] && !$annee) {
                $erreurs[] = "Aucune année universitaire active : impossible d'inscrire dans un groupe.";
            } elseif ($ligne['groupe'] && !$groupe) {
                $erreurs[] = "Groupe « {$ligne['groupe']} » introuvable cette année (voir la feuille « Groupes » du modèle).";
            }


            //==========================================================================================================
            // Changement de groupe refusé si l'étudiant a des absences dans son groupe actuel
            //==========================================================================================================
            $groupe_actuel = $existant?->inscription_active?->groupe;

            if (!$erreurs && $existant && $groupe && $groupe_actuel && $groupe_actuel->id !== $groupe->id) {
                $a_des_absences = Absence
                    ::query()
                    ->where('etudiant_id', $existant->id)
                    ->whereHas('seance', fn($query) => $query->where('groupe_id', $groupe_actuel->id))
                    ->exists();

                if ($a_des_absences) {
                    $erreurs[] = "Changement de groupe impossible ({$groupe_actuel->nom} → {$groupe->nom}) : l'étudiant a déjà des absences dans {$groupe_actuel->nom}.";
                }
            }


            //==========================================================================================================
            // Statut de la ligne
            //==========================================================================================================
            $changements = $existant && !$erreurs ? self::get_changements($existant, $ligne, $groupe) : [];

            $statut = match (true) {
                (bool) $erreurs     => self::statut_erreur,
                !$existant          => self::statut_nouveau,
                (bool) $changements => self::statut_modifie,
                default             => self::statut_inchange,
            };

            $apercu[] = [
                'ligne'       => $ligne['numero'],
                'cne'         => $ligne['cne'],
                'nom_complet' => trim("{$ligne['prenom']} " . mb_strtoupper((string) $ligne['nom'])),
                'groupe'      => $groupe?->nom ?? $ligne['groupe'],
                'statut'      => $statut,
                'erreurs'     => $erreurs,
                'changements' => $changements,
            ];

            if (in_array($statut, [self::statut_nouveau, self::statut_modifie], true)) {
                $a_importer[] = [
                    'etudiant_id' => $existant?->id,
                    'groupe_id'   => $groupe?->id,
                    'attributs'   => [
                        'cne'            => $ligne['cne'],
                        'nom'            => $ligne['nom'],
                        'prenom'         => $ligne['prenom'],
                        'email'          => $ligne['email'],
                        'telephone'      => $ligne['telephone'],
                        'date_naissance' => $ligne['date_naissance'],
                    ],
                ];
            }
        }

        $compteurs = collect($apercu)
            ->countBy('statut')
            ->all();

        return [
            'jeton'      => Str::random(32),
            'fichier'    => $fichier->getClientOriginalName(),
            'annee'      => $annee?->libelle,
            'compteurs'  => [
                self::statut_nouveau  => $compteurs[self::statut_nouveau] ?? 0,
                self::statut_modifie  => $compteurs[self::statut_modifie] ?? 0,
                self::statut_inchange => $compteurs[self::statut_inchange] ?? 0,
                self::statut_erreur   => $compteurs[self::statut_erreur] ?? 0,
            ],
            'apercu'     => $apercu,
            'a_importer' => $a_importer,
        ];
    }



    //==================================================================================================================
    // IMPORT : les lignes valides, dans une seule transaction (tout ou rien)
    //==================================================================================================================
    public static function importer(array $a_importer) : array {
        return DB::transaction(function () use ($a_importer) {
            $bilan = ['crees' => 0, 'modifies' => 0];

            foreach ($a_importer as $ligne) {
                $existant = $ligne['etudiant_id'] ? Etudiant::query()
                                                            ->find($ligne['etudiant_id']) : null;

                $etudiant = Etudiant::update_by_cle_or_create($existant?->cle, $ligne['attributs']);

                $existant ? $bilan['modifies']++ : $bilan['crees']++;

                //======================================================================================================
                // Inscription dans le groupe de l'année active (création, ou changement si le groupe diffère)
                //======================================================================================================
                if ($ligne['groupe_id'] && $etudiant->inscription_active?->groupe_id !== $ligne['groupe_id']) {
                    InscriptionService::inscrire($etudiant, Groupe::query()
                                                                  ->findOrFail($ligne['groupe_id']));
                }
            }

            return $bilan;
        });
    }



    //==================================================================================================================
    // Une ligne du fichier => valeurs propres + erreurs de format
    //==================================================================================================================
    protected static function normaliser(array $brute, array $correspondances, int $numero) : array {
        $valeur = fn(string $champ) => isset($correspondances[$champ]) ? self::texte($brute[$correspondances[$champ]] ?? null) : null;

        $cne       = $valeur('cne') ? mb_strtoupper(str_replace(' ', '', $valeur('cne'))) : null;
        $nom       = $valeur('nom') ? mb_strtoupper($valeur('nom')) : null;
        $prenom    = $valeur('prenom') ? Str::title(mb_strtolower($valeur('prenom'))) : null;
        $email     = $valeur('email') ? mb_strtolower($valeur('email')) : null;
        $telephone = self::normaliser_telephone($valeur('telephone'));
        $groupe    = $valeur('groupe');

        $erreurs = [];

        if (!$cne) {
            $erreurs[] = "Le CNE est obligatoire.";
        } elseif (mb_strlen($cne) > 20) {
            $erreurs[] = "Le CNE dépasse 20 caractères.";
        }

        if (!$nom) {
            $erreurs[] = "Le nom est obligatoire.";
        }
        if (!$prenom) {
            $erreurs[] = "Le prénom est obligatoire.";
        }

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "L'e-mail « {$email} » n'est pas valide.";
        }

        $date_naissance = null;

        if (isset($correspondances['date_naissance']) && self::texte($brute[$correspondances['date_naissance']] ?? null)) {
            $date_naissance = self::normaliser_date($brute[$correspondances['date_naissance']]);

            if (!$date_naissance) {
                $erreurs[] = "Date de naissance non reconnue (format attendu : 15/03/2007).";
            }
        }

        return compact('numero', 'cne', 'nom', 'prenom', 'email', 'telephone', 'date_naissance', 'groupe', 'erreurs');
    }



    //==================================================================================================================
    // Différences entre l'étudiant existant et la ligne : ["e-mail", "groupe : 1AP-A → 1AP-B"]
    //==================================================================================================================
    protected static function get_changements(Etudiant $existant, array $ligne, ?Groupe $groupe) : array {
        $changements = [];

        $libelles = ['nom' => 'nom', 'prenom' => 'prénom', 'email' => 'e-mail', 'telephone' => 'téléphone'];

        foreach ($libelles as $champ => $libelle) {
            if ((string) $existant->{$champ} !== (string) $ligne[$champ]) {
                $changements[] = $libelle;
            }
        }

        if ($existant->date_naissance?->format('Y-m-d') !== $ligne['date_naissance']) {
            $changements[] = 'date de naissance';
        }

        $groupe_actuel = $existant->inscription_active?->groupe;

        if ($groupe && $groupe_actuel?->id !== $groupe->id) {
            $changements[] = $groupe_actuel ? "groupe : {$groupe_actuel->nom} → {$groupe->nom}" : "inscription en {$groupe->nom}";
        }

        return $changements;
    }



    //==================================================================================================================
    // En-têtes du fichier => champ => clé de colonne
    //==================================================================================================================
    protected static function get_correspondances(array $entetes) : array {
        $correspondances = [];

        foreach (self::alias_colonnes as $champ => $alias) {
            foreach ($entetes as $entete) {
                $cle = Str::slug((string) $entete, '_');

                if (in_array($cle, $alias, true) || ($champ === 'groupe' && str_starts_with($cle, 'groupe'))) {
                    $correspondances[$champ] = $entete;
                    break;
                }
            }
        }

        return $correspondances;
    }



    protected static function get_doublons(array $valeurs) : array {
        return collect($valeurs)
            ->filter()
            ->countBy()
            ->filter(fn(int $nombre) => $nombre > 1)
            ->keys()
            ->all();
    }



    protected static function a_du_contenu(array $ligne) : bool {
        return collect($ligne)->contains(fn($valeur) => self::texte($valeur) !== null);
    }



    protected static function texte(mixed $valeur) : ?string {
        if ($valeur === null) {
            return null;
        }

        $texte = trim((string) $valeur);

        return $texte === '' ? null : $texte;
    }



    //==================================================================================================================
    // 612345678 (Excel a retiré le 0) => 0612345678 ; espaces et points retirés
    //==================================================================================================================
    protected static function normaliser_telephone(?string $telephone) : ?string {
        if (!$telephone) {
            return null;
        }

        $telephone = preg_replace('/[\s.\-]/', '', $telephone);

        return preg_match('/^[5-7]\d{8}$/', $telephone) ? "0{$telephone}" : $telephone;
    }



    //==================================================================================================================
    // Date Excel (nombre), "15/03/2007", "15-03-2007" ou "2007-03-15" => "2007-03-15" ; null si non reconnue
    //==================================================================================================================
    protected static function normaliser_date(mixed $valeur) : ?string {
        if (is_numeric($valeur)) {
            return ExcelDate::excelToDateTimeObject((float) $valeur)
                            ->format('Y-m-d');
        }

        $texte = trim((string) $valeur);

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $format) {
            $date = Carbon::createFromFormat("!{$format}", $texte);

            if ($date && $date->format($format) === $texte) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
