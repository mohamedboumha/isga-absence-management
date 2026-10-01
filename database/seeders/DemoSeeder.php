<?php

namespace Database\Seeders;

use App\Features\Absence\Absence;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use App\Features\Appel\AppelService;
use App\Features\Enseignant\Enseignant;
use App\Features\Etudiant\Etudiant;
use App\Features\Groupe\Groupe;
use App\Features\Inscription\InscriptionService;
use App\Features\Journal\JournalService;
use App\Features\Justificatif\Justificatif;
use App\Features\Justificatif\JustificatifService;
use App\Features\Module\Module;
use App\Features\NiveauEtude\NiveauEtude;
use App\Features\PassageAnnee\PassageAnneeService;
use App\Features\Seance\Seance;
use App\Features\Seance\SeanceService;
use App\Features\User\UserService;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder {
    const int    etudiants_par_groupe = 20;   // année passée
    const int    effectif_cible       = 22;   // année en cours, après les nouveaux entrants
    const int    nb_enseignants       = 12;
    const string mot_de_passe_demo    = 'password';

    //==================================================================================================================
    // 6 modules par niveau (3 au semestre 1, 3 au semestre 2), selon la famille du niveau
    //==================================================================================================================
    const array modules_par_famille = [
        'prepa'      => ["Analyse mathématique", "Algèbre linéaire", "Algorithmique", "Physique générale", "Anglais", "Techniques de communication"],
        'cycle'      => ["Programmation orientée objet", "Bases de données", "Systèmes d'exploitation", "Réseaux informatiques", "Génie logiciel", "Développement web"],
        'specialite' => ["Architecture logicielle", "Intelligence artificielle", "Cloud computing", "Sécurité des systèmes", "Gestion de projet", "Projet de fin d'études"],
        'master'     => ["Comptabilité approfondie", "Finance d'entreprise", "Contrôle de gestion", "Fiscalité", "Audit", "Marchés financiers"],
        'licence'    => ["Management des entreprises", "Comptabilité générale", "Marketing digital", "Systèmes d'information", "Droit des affaires", "Communication professionnelle"],
    ];

    //==================================================================================================================
    // Probabilité d'absence (%) de chaque étudiant : quelques absentéistes, les autres rarement absents
    //==================================================================================================================
    protected array $probabilites = [];



    public function run() : void {
        JournalService::$actif = false;

        $this->call(DatabaseSeeder::class); // super-administrateur + structure ISGA

        [$annee_passee, $annee_courante] = $this->creer_annees();

        $niveaux = NiveauEtude::query()
                              ->with(['cycle', 'suivants'])
                              ->orderBy('code')
                              ->get()
                              ->keyBy('code');

        $this->creer_modules($niveaux);
        $enseignants = $this->creer_enseignants();
        $admin       = $this->creer_admin();

        //==============================================================================================================
        // Année passée : groupes, étudiants, puis passage vers l'année en cours
        //==============================================================================================================
        $groupes_passes = $this->creer_groupes_et_etudiants($annee_passee, $niveaux);
        $this->faire_le_passage($groupes_passes, $annee_courante);
        $this->completer_annee_courante($annee_courante, $niveaux);

        //==============================================================================================================
        // Séances et appels des deux années
        //==============================================================================================================
        $this->planifier_seances($annee_passee, $this->get_semaines_annee_passee($annee_passee));
        $this->planifier_seances($annee_courante, $this->get_semaines_annee_courante($annee_courante));
        $this->faire_les_appels();
        $this->creer_justificatifs($admin);
        $this->creer_seance_du_jour_pour_la_demo($enseignants->first());

        JournalService::$actif = true;

        $this->afficher_resume($annee_passee, $annee_courante);
    }



    //==================================================================================================================
    // L'année en cours (septembre → juillet) et la précédente, déjà terminée
    //==================================================================================================================
    protected function creer_annees() : array {
        $debut = today()->month >= 9 ? today()->year : today()->year - 1;

        $passee = AnneeUniversitaire::create([
                                                 'libelle'    => ($debut - 1) . '-' . $debut,
                                                 'date_debut' => ($debut - 1) . '-09-01',
                                                 'date_fin'   => "$debut-07-31",
                                                 'active'     => false,
                                             ]);

        $courante = AnneeUniversitaire::create([
                                                   'libelle'    => $debut . '-' . ($debut + 1),
                                                   'date_debut' => "$debut-09-01",
                                                   'date_fin'   => ($debut + 1) . '-07-31',
                                                   'active'     => true,
                                               ]);

        return [$passee, $courante];
    }



    protected function get_famille(NiveauEtude $niveau) : string {
        return match (true) {
            $niveau->cycle->code === 'MST' => 'master',
            $niveau->cycle->code === 'LIC' => 'licence',
            $niveau->annee_cycle <= 2      => 'prepa',
            $niveau->annee_cycle <= 4      => 'cycle',
            default                        => 'specialite',
        };
    }



    protected function creer_modules(Collection $niveaux) : void {
        foreach ($niveaux as $niveau) {
            foreach (self::modules_par_famille[$this->get_famille($niveau)] as $index => $intitule) {
                Module::create(Module::faker([
                                                 'niveau'   => $niveau,
                                                 'intitule' => $intitule,
                                                 'semestre' => $niveau->nb_semestres >= 2 ? ($index < 3 ? 1 : 2) : 1,
                                             ]));
            }
        }
    }



    //==================================================================================================================
    // Enseignants avec compte ; les modules sont répartis à tour de rôle
    //==================================================================================================================
    protected function creer_enseignants() : Collection {
        $enseignants = collect(range(1, self::nb_enseignants))->map(function (int $numero) {
            $faker  = fake('fr_FR');
            $prenom = $numero === 1 ? 'Karim' : $faker->firstName();
            $nom    = $numero === 1 ? 'Bennani' : $faker->lastName();
            $email  = $numero === 1 ? 'enseignant@isga.ma' : Str::slug("$prenom.$nom", '.') . ".$numero@isga.ma";

            $user = new User();
            $user->forceFill([
                                 'name'              => $nom,
                                 'prenom'            => $prenom,
                                 'email'             => $email,
                                 'password'          => self::mot_de_passe_demo,
                                 'role'              => UserService::role_enseignant,
                                 'actif'             => true,
                                 'email_verified_at' => now(),
                             ])
                 ->save();

            return Enseignant::create([
                                          'user_id'   => $user->id,
                                          'nom'       => $nom,
                                          'prenom'    => $prenom,
                                          'email'     => $email,
                                          'telephone' => '06' . $faker->numerify('########'),
                                      ]);
        });

        Module::query()
              ->orderBy('id')
              ->get()
              ->each(function (Module $module, int $index) use ($enseignants) {
                  $enseignants[$index % $enseignants->count()]->modules()
                                                              ->attach($module->id);
              });

        return $enseignants;
    }



    protected function creer_admin() : User {
        $admin = new User();

        $admin->forceFill([
                              'name'              => 'Scolarité',
                              'prenom'            => 'Agent',
                              'email'             => 'scolarite@isga.ma',
                              'password'          => self::mot_de_passe_demo,
                              'role'              => UserService::role_admin,
                              'actif'             => true,
                              'email_verified_at' => now(),
                          ])
              ->save();

        return $admin;
    }



    //==================================================================================================================
    // Un étudiant de plus : 12 % d'absentéistes (25 % d'absences), les autres à 4 %
    //==================================================================================================================
    protected function nouvel_etudiant(Groupe $groupe) : Etudiant {
        $etudiant = Etudiant::create(Etudiant::faker());

        $this->probabilites[$etudiant->id] = fake()->boolean(12) ? 25 : 4;

        InscriptionService::inscrire($etudiant, $groupe);

        return $etudiant;
    }



    //==================================================================================================================
    // Année passée : un groupe par niveau (deux en 1AP), 20 étudiants chacun
    //==================================================================================================================
    protected function creer_groupes_et_etudiants(AnneeUniversitaire $annee, Collection $niveaux) : Collection {
        $groupes = collect();

        foreach ($niveaux as $niveau) {
            foreach (range(1, $niveau->code === '1AP' ? 2 : 1) as $i) {
                $groupe = Groupe::create(Groupe::faker(['annee' => $annee, 'niveau' => $niveau]));

                foreach (range(1, self::etudiants_par_groupe) as $j) {
                    $this->nouvel_etudiant($groupe);
                }

                $groupes->push($groupe);
            }
        }

        return $groupes;
    }



    //==================================================================================================================
    // Passage d'année réel (PassageAnneeService) pour chaque groupe de l'année passée
    //==================================================================================================================
    protected function faire_le_passage(Collection $groupes, AnneeUniversitaire $cible) : void {
        foreach ($groupes as $groupe) {
            $niveau = $groupe->niveau_etude()
                             ->with(['cycle', 'suivants'])
                             ->firstOrFail();

            //==========================================================================================================
            // Groupe d'accueil : celui qui existe déjà dans l'année cible pour ce niveau, sinon créé automatiquement
            //==========================================================================================================
            $destinations = collect([...$niveau->suivants->all(), $niveau])
                ->unique('id')
                ->map(fn(NiveauEtude $destination) => [
                    'niveau_id' => $destination->id,
                    'groupe_id' => Groupe::query()
                                         ->where('annee_universitaire_id', $cible->id)
                                         ->where('niveau_etude_id', $destination->id)
                                         ->value('id'),
                ])
                ->values()
                ->all();

            //==========================================================================================================
            // Décisions : ~85 % admis (ou ~88 % diplômés en dernière année), le reste redoublant ou sortant
            //==========================================================================================================
            $lignes = $groupe->inscriptions()
                             ->get()
                             ->map(function ($inscription) use ($niveau) {
                                 $tirage = fake()->numberBetween(1, 100);

                                 if ($niveau->est_derniere_annee) {
                                     $decision = $tirage <= 88 ? InscriptionService::decision_diplome
                                         : ($tirage <= 95 ? InscriptionService::decision_redoublant : InscriptionService::decision_sortant);
                                 } else {
                                     $decision = $tirage <= 85 ? InscriptionService::decision_admis
                                         : ($tirage <= 93 ? InscriptionService::decision_redoublant : InscriptionService::decision_sortant);
                                 }

                                 return [
                                     'inscription_id' => $inscription->id,
                                     'decision'       => $decision,
                                     'niveau_id'      => $decision === InscriptionService::decision_admis ? $niveau->suivants->random()->id : null,
                                 ];
                             })
                             ->all();

            PassageAnneeService::executer($groupe, $cible, $destinations, $lignes);
        }
    }



    //==================================================================================================================
    // Année en cours : chaque niveau a un groupe, complété par de nouveaux entrants (Bac, Bac+2, Bac+3...)
    //==================================================================================================================
    protected function completer_annee_courante(AnneeUniversitaire $annee, Collection $niveaux) : void {
        foreach ($niveaux as $niveau) {
            $groupes = Groupe::query()
                             ->where('annee_universitaire_id', $annee->id)
                             ->where('niveau_etude_id', $niveau->id)
                             ->get();

            if ($groupes->isEmpty()) {
                $groupes->push(Groupe::create(Groupe::faker(['annee' => $annee, 'niveau' => $niveau])));
            }

            foreach ($groupes as $groupe) {
                $manquants = self::effectif_cible - $groupe->etudiants()
                                                           ->count();

                foreach (range(1, max($manquants, 0)) as $i) {
                    if ($manquants > 0) {
                        $this->nouvel_etudiant($groupe);
                    }
                }
            }
        }
    }



    //==================================================================================================================
    // Semaines planifiées : 4 en octobre et 4 en mars de l'année passée ; 4 semaines passées + 1 à venir cette année
    //==================================================================================================================
    protected function get_semaines_annee_passee(AnneeUniversitaire $annee) : array {
        $debut    = $annee->date_debut;
        $semaines = [];

        foreach ([$debut->copy()
                        ->addWeeks(5), $debut->copy()
                                             ->addMonths(6)
                                             ->addWeeks(1)] as $depart) {
            foreach (range(0, 3) as $i) {
                $semaines[] = $depart->copy()
                                     ->addWeeks($i)
                                     ->startOfWeek();
            }
        }

        return $semaines;
    }



    protected function get_semaines_annee_courante(AnneeUniversitaire $annee) : array {
        $semaines = [];
        $depart   = today()
            ->subWeeks(4)
            ->max($annee->date_debut)
            ->startOfWeek();
        $fin      = today()
            ->addWeek()
            ->startOfWeek();

        for ($semaine = $depart; $semaine->lte($fin); $semaine = $semaine->addWeek()) {
            $semaines[] = $semaine;
        }

        return $semaines;
    }



    protected function get_semestre(CarbonInterface $date) : int {
        return ($date->month >= 9 || $date->month === 1) ? 1 : 2;
    }



    //==================================================================================================================
    // Emploi du temps : chaque semaine, une séance par module du semestre en cours (cours, TD, TP en alternance)
    //==================================================================================================================
    protected function planifier_seances(AnneeUniversitaire $annee, array $semaines) : void {
        $groupes            = Groupe::query()
                                    ->where('annee_universitaire_id', $annee->id)
                                    ->with('niveau_etude')
                                    ->get();
        $modules_par_niveau = Module::query()
                                    ->with('enseignants')
                                    ->get()
                                    ->groupBy('niveau_etude_id');

        foreach ($semaines as $semaine) {
            foreach ($groupes as $index_groupe => $groupe) {
                $semestre = $groupe->niveau_etude->nb_semestres >= 2 ? $this->get_semestre($semaine) : 1;

                $modules = ($modules_par_niveau[$groupe->niveau_etude_id] ?? collect())
                    ->where('semestre', $semestre)
                    ->values();

                foreach ($modules as $index_module => $module) {
                    $type = ['COURS', 'TD', 'TP'][$index_module % 3];

                    $this->planifier($annee, $groupe, $module, $type, $semaine, $index_groupe + $index_module * 3);
                }
            }
        }
    }



    //==================================================================================================================
    // Premier créneau libre (groupe et enseignant) à partir d'une position dans la semaine
    //==================================================================================================================
    protected function planifier(AnneeUniversitaire $annee, Groupe $groupe, Module $module, string $type, CarbonInterface $semaine, int $position) : void {
        $enseignant = $module->enseignants->first();

        if (!$enseignant) {
            return;
        }

        foreach (range(0, 19) as $decalage) {
            $case = ($position + $decalage) % 20;          // 5 jours × 4 créneaux
            $date = $semaine->copy()
                            ->addDays(intdiv($case, 4));

            [$debut, $fin] = SeanceService::creneaux[$case % 4];

            if ($date->lt($annee->date_debut) || $date->gt($annee->date_fin)) {
                continue;
            }

            $conflit = SeanceService::get_seance_en_conflit('groupe_id', $groupe->id, $date->format('Y-m-d'), $debut, $fin, null)
                ?? SeanceService::get_seance_en_conflit('enseignant_id', $enseignant->id, $date->format('Y-m-d'), $debut, $fin, null);

            if ($conflit) {
                continue;
            }

            Seance::create([
                               'module_id'     => $module->id,
                               'enseignant_id' => $enseignant->id,
                               'groupe_id'     => $groupe->id,
                               'date'          => $date->format('Y-m-d'),
                               'heure_debut'   => $debut,
                               'heure_fin'     => $fin,
                               'type'          => $type,
                               'salle'         => fake()->randomElement(['A', 'B', 'C']) . fake()->numberBetween(101, 320),
                               'annulee'       => fake()->boolean(3),
                           ]);

            return;
        }
    }



    //==================================================================================================================
    // Appels des séances passées : ~95 % faits (AppelService), à l'heure de la séance
    //==================================================================================================================
    protected function faire_les_appels() : void {
        Seance
            ::query()
            ->non_annulees()
            ->whereDate('date', '<', today())
            ->with(['groupe', 'enseignant.user'])
            ->get()
            ->each(function (Seance $seance) {
                if (fake()->boolean(5)) {
                    return; // appel oublié : visible dans "Appels non faits"
                }

                $absences = $seance->groupe->etudiants
                    ->filter(fn(Etudiant $etudiant) => fake()->boolean($this->probabilites[$etudiant->id] ?? 4))
                    ->map(fn(Etudiant $etudiant) => [
                        'etudiant_id' => $etudiant->id,
                        'remarque'    => fake()->boolean(15) ? fake()->randomElement(["Absence signalée par un camarade", "Parti avant la fin", "Malade selon un camarade"]) : null,
                    ])
                    ->values()
                    ->all();

                AppelService::enregistrer_appel($seance, $absences, $seance->enseignant->user);

                $seance->forceFill([
                                       'appel_fait_le' => $seance->date?->copy()
                                                                       ->setTimeFromTimeString($seance->heure_debut ?? '08:30')
                                                                       ->addMinutes(10),
                                   ])
                       ->save();
            });
    }



    //==================================================================================================================
    // Justificatifs pour ~15 % des absences : 60 % validés, 15 % refusés, le reste en attente
    //==================================================================================================================
    protected function creer_justificatifs(User $admin) : void {
        $chemin = 'justificatifs/demo-certificat.pdf';

        Storage::disk(JustificatifService::disque)
               ->put(
                   $chemin,
                   Pdf::loadHTML('<h2>Certificat médical</h2><p>Document de démonstration.</p>')
                      ->output()
               );

        Absence
            ::query()
            ->with('seance')
            ->inRandomOrder()
            ->get()
            ->take((int) (Absence::count() * 0.15))
            ->each(function (Absence $absence) use ($admin, $chemin) {
                $date = $absence->seance->date;

                $justificatif = new Justificatif();
                $justificatif->forceFill([
                                             'etudiant_id'    => $absence->etudiant_id,
                                             'type'           => fake()->randomElement(array_keys(JustificatifService::types)),
                                             'date_debut'     => $date?->format('Y-m-d'),
                                             'date_fin'       => $date?->format('Y-m-d'),
                                             'motif'          => fake()->randomElement(["Consultation médicale", "Raison familiale", "Rendez-vous administratif", null]),
                                             'date_depot'     => min($date?->copy()
                                                                          ->addDays(fake()->numberBetween(0, 6)), today())?->format('Y-m-d'),
                                             'fichier_chemin' => $chemin,
                                             'fichier_nom'    => 'certificat.pdf',
                                             'statut'         => JustificatifService::statut_en_attente,
                                         ])
                             ->save();

                $tirage = fake()->numberBetween(1, 100);

                if ($tirage <= 60) {
                    JustificatifService::valider($justificatif, $admin);
                } elseif ($tirage <= 75) {
                    JustificatifService::refuser($justificatif, "Document illisible ou non conforme.", $admin);
                }
            });
    }



    //==================================================================================================================
    // L'enseignant de démonstration a toujours une séance aujourd'hui (appel à faire devant le jury)
    //==================================================================================================================
    protected function creer_seance_du_jour_pour_la_demo(Enseignant $enseignant) : void {
        if (Seance::query()
                  ->where('enseignant_id', $enseignant->id)
                  ->whereDate('date', today())
                  ->exists()) {
            return;
        }

        $annee = AnneeUniversitaire::query()
                                   ->where('active', true)
                                   ->firstOrFail();

        foreach ($enseignant->modules()
                            ->get() as $module) {
            $groupe = Groupe::query()
                            ->where('annee_universitaire_id', $annee->id)
                            ->where('niveau_etude_id', $module->niveau_etude_id)
                            ->first();

            if (!$groupe) {
                continue;
            }

            foreach (array_reverse(SeanceService::creneaux) as [$debut, $fin]) {
                $date = today()->format('Y-m-d');

                $conflit = SeanceService::get_seance_en_conflit('groupe_id', $groupe->id, $date, $debut, $fin, null)
                    ?? SeanceService::get_seance_en_conflit('enseignant_id', $enseignant->id, $date, $debut, $fin, null);

                if (!$conflit) {
                    Seance::create([
                                       'module_id'     => $module->id,
                                       'enseignant_id' => $enseignant->id,
                                       'groupe_id'     => $groupe->id,
                                       'date'          => $date,
                                       'heure_debut'   => $debut,
                                       'heure_fin'     => $fin,
                                       'type'          => 'COURS',
                                       'salle'         => 'B204',
                                       'annulee'       => false,
                                   ]);

                    return;
                }
            }
        }
    }



    protected function afficher_resume(AnneeUniversitaire $passee, AnneeUniversitaire $courante) : void {
        $this->command->newLine();
        $this->command->info('Données de démonstration créées :');

        $this->command->table(['Élément', 'Nombre'], [
            ['Niveaux d\'études', NiveauEtude::count()],
            ['Modules', Module::count()],
            ['Enseignants', Enseignant::count()],
            ['Étudiants', Etudiant::count()],
            ["Groupes {$passee->libelle} (terminée)", Groupe::query()
                                                            ->where('annee_universitaire_id', $passee->id)
                                                            ->count()],
            ["Groupes {$courante->libelle} (en cours)", Groupe::query()
                                                              ->where('annee_universitaire_id', $courante->id)
                                                              ->count()],
            ['Séances', Seance::count()],
            ['Absences', Absence::count()],
            ['Justificatifs', Justificatif::count()],
        ]);

        $this->command->table(['Profil', 'E-mail', 'Mot de passe'], [
            ['Super-administrateur', 'admin@isga.ma', self::mot_de_passe_demo],
            ['Administrateur', 'scolarite@isga.ma', self::mot_de_passe_demo],
            ['Enseignant (séance aujourd\'hui)', 'enseignant@isga.ma', self::mot_de_passe_demo],
        ]);
    }
}
