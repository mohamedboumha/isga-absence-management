<?php

namespace Database\Seeders;

use App\Features\Absence\Absence;
use App\Features\AnneeUniversitaire\AnneeUniversitaire;
use App\Features\Appel\AppelService;
use App\Features\Enseignant\Enseignant;
use App\Features\Etudiant\Etudiant;
use App\Features\Filiere\Filiere;
use App\Features\Filiere\FiliereService;
use App\Features\Groupe\Groupe;
use App\Features\Journal\JournalService;
use App\Features\Justificatif\Justificatif;
use App\Features\Justificatif\JustificatifService;
use App\Features\Module\Module;
use App\Features\Seance\Seance;
use App\Features\Seance\SeanceService;
use App\Features\Semestre\Semestre;
use App\Features\User\UserService;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder {
    //==================================================================================================================
    // Cursus : 2 modules par semestre (S1 à S6) pour chaque filière
    //==================================================================================================================
    const array filieres = [
        'GI' => [
            'nom'     => "Génie Informatique",
            'modules' => [
                'S1' => ["Algorithmique", "Architecture des ordinateurs"],
                'S2' => ["Programmation en C", "Introduction au web"],
                'S3' => ["Programmation orientée objet", "Bases de données"],
                'S4' => ["Structures de données", "Réseaux informatiques"],
                'S5' => ["Développement web avancé", "Génie logiciel"],
                'S6' => ["Sécurité informatique", "Projet de fin d'études"],
            ],
        ],
        'GC' => [
            'nom'     => "Génie Civil",
            'modules' => [
                'S1' => ["Mathématiques", "Dessin technique"],
                'S2' => ["Mécanique générale", "Matériaux de construction"],
                'S3' => ["Résistance des matériaux", "Topographie"],
                'S4' => ["Béton armé", "Hydraulique"],
                'S5' => ["Calcul des structures", "Géotechnique"],
                'S6' => ["Gestion de chantier", "Projet de fin d'études"],
            ],
        ],
        'FC' => [
            'nom'     => "Finance et Comptabilité",
            'modules' => [
                'S1' => ["Comptabilité générale", "Économie générale"],
                'S2' => ["Mathématiques financières", "Droit des affaires"],
                'S3' => ["Comptabilité analytique", "Statistiques"],
                'S4' => ["Fiscalité", "Gestion financière"],
                'S5' => ["Audit", "Contrôle de gestion"],
                'S6' => ["Marchés financiers", "Projet de fin d'études"],
            ],
        ],
    ];

    const array groupes = [
        ['filiere' => 'GI', 'niveau' => 'L1', 'lettre' => 'A'],
        ['filiere' => 'GI', 'niveau' => 'L1', 'lettre' => 'B'],
        ['filiere' => 'GI', 'niveau' => 'L2', 'lettre' => 'A'],
        ['filiere' => 'GI', 'niveau' => 'L3', 'lettre' => 'A'],
        ['filiere' => 'GC', 'niveau' => 'L1', 'lettre' => 'A'],
        ['filiere' => 'GC', 'niveau' => 'L2', 'lettre' => 'A'],
        ['filiere' => 'GC', 'niveau' => 'L3', 'lettre' => 'A'],
        ['filiere' => 'FC', 'niveau' => 'L1', 'lettre' => 'A'],
        ['filiere' => 'FC', 'niveau' => 'L2', 'lettre' => 'A'],
        ['filiere' => 'FC', 'niveau' => 'L3', 'lettre' => 'A'],
    ];

    const int    nb_etudiants_par_groupe = 25;
    const int    nb_enseignants          = 8;
    const string mot_de_passe_demo       = 'password';



    public function run() : void {
        JournalService::$actif = false;

        $this->call(DatabaseSeeder::class); // super-administrateur

        $annee       = $this->creer_annee();
        $filieres    = $this->creer_filieres_et_modules();
        $groupes     = $this->creer_groupes_et_etudiants($annee, $filieres);
        $enseignants = $this->creer_enseignants($filieres);
        $admin       = $this->creer_admin();

        $this->creer_emploi_du_temps($annee, $groupes);
        $this->faire_les_appels();
        $this->creer_justificatifs($admin);
        $this->creer_seance_du_jour_pour_la_demo($enseignants->first());

        JournalService::$actif = true;

        $this->afficher_resume();
    }



    //==================================================================================================================
    // Année en cours (septembre → juillet) et ses semestres
    //==================================================================================================================
    protected function creer_annee() : AnneeUniversitaire {
        $debut = today()->month >= 9 ? today()->year : today()->year - 1;

        $annee = AnneeUniversitaire::create([
                                                'libelle'    => $debut . '-' . ($debut + 1),
                                                'date_debut' => "$debut-09-01",
                                                'date_fin'   => ($debut + 1) . '-07-31',
                                                'active'     => true,
                                            ]);

        foreach (range(1, 6) as $numero) {
            $impair = $numero % 2 === 1;

            Semestre::create([
                                 'annee_universitaire_id' => $annee->id,
                                 'libelle'                => "S$numero",
                                 'date_debut'             => $impair ? "$debut-09-01" : ($debut + 1) . '-02-01',
                                 'date_fin'               => $impair ? ($debut + 1) . '-01-31' : ($debut + 1) . '-07-31',
                             ]);
        }

        return $annee;
    }



    protected function creer_filieres_et_modules() : Collection {
        return collect(self::filieres)->map(function (array $definition, string $code) {
            $filiere = Filiere::create([
                                           'code'        => $code,
                                           'nom'         => $definition['nom'],
                                           'description' => "Filière {$definition['nom']} (données de démonstration).",
                                       ]);

            foreach ($definition['modules'] as $semestre => $intitules) {
                foreach ($intitules as $index => $intitule) {
                    Module::create([
                                       'filiere_id'     => $filiere->id,
                                       'code'           => "$code-$semestre-" . ($index + 1),
                                       'intitule'       => $intitule,
                                       'semestre'       => $semestre,
                                       'volume_horaire' => fake()->randomElement([30, 36, 42, 48]),
                                   ]);
                }
            }

            return $filiere;
        });
    }



    protected function creer_groupes_et_etudiants(AnneeUniversitaire $annee, Collection $filieres) : Collection {
        return collect(self::groupes)->map(function (array $definition) use ($annee, $filieres) {
            $filiere = $filieres[$definition['filiere']];

            $groupe = Groupe::create([
                                         'annee_universitaire_id' => $annee->id,
                                         'filiere_id'             => $filiere->id,
                                         'niveau'                 => $definition['niveau'],
                                         'nom'                    => "{$filiere->code}-{$definition['niveau']}-{$definition['lettre']}",
                                     ]);

            foreach (range(1, self::nb_etudiants_par_groupe) as $i) {
                Etudiant::create(Etudiant::faker(['groupe' => $groupe]));
            }

            return $groupe;
        });
    }



    //==================================================================================================================
    // Enseignants avec compte ; les modules sont répartis à tour de rôle
    //==================================================================================================================
    protected function creer_enseignants(Collection $filieres) : Collection {
        $enseignants = collect(range(1, self::nb_enseignants))->map(function (int $numero) {
            $faker  = fake('fr_FR');
            $prenom = $numero === 1 ? 'Karim' : $faker->firstName();
            $nom    = $numero === 1 ? 'Bennani' : $faker->lastName();
            $email  = $numero === 1 ? 'enseignant@isga.ma' : Str::slug("$prenom.$nom", '.') . "@isga.ma";

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
    // Emploi du temps : chaque semaine, pour chaque module du semestre en cours, un COURS et un TD
    //==================================================================================================================
    protected function creer_emploi_du_temps(AnneeUniversitaire $annee, Collection $groupes) : void {
        $semestre_impair = today()->month >= 9 || today()->month === 1;

        $premiere_semaine = today()
            ->subWeeks(4)
            ->startOfWeek();
        $derniere_semaine = today()
            ->addWeek()
            ->startOfWeek();

        for ($semaine = $premiere_semaine; $semaine->lte($derniere_semaine); $semaine = $semaine->addWeek()) {
            foreach ($groupes as $index_groupe => $groupe) {
                $modules = Module
                    ::query()
                    ->where('filiere_id', $groupe->filiere_id)
                    ->with('enseignants')
                    ->get()
                    ->filter(function (Module $module) use ($groupe, $semestre_impair) {
                        $numero = (int) substr($module->semestre, 1);

                        return FiliereService::get_niveau_by_semestre($module->semestre) === $groupe->niveau
                            && ($numero % 2 === 1) === $semestre_impair;
                    })
                    ->values();

                foreach ($modules as $index_module => $module) {
                    foreach (['COURS', 'TD'] as $index_type => $type) {
                        $this->planifier($annee, $groupe, $module, $type, $semaine, $index_groupe + $index_module * 2 + $index_type * 3);
                    }
                }
            }
        }
    }



    //==================================================================================================================
    // Cherche le premier créneau libre (groupe et enseignant) à partir d'une position dans la semaine
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
                               'annulee'       => fake()->boolean(3), // ~3 % de séances annulées
                           ]);

            return;
        }
    }



    //==================================================================================================================
    // Appels des séances passées : ~95 % faits, avec quelques étudiants souvent absents
    //==================================================================================================================
    protected function faire_les_appels() : void {
        //==============================================================================================================
        // Probabilité d'absence de chaque étudiant : 3 "absentéistes" par groupe, les autres rarement absents
        //==============================================================================================================
        $probabilites = [];

        Etudiant::all()
                ->groupBy('groupe_id')
                ->each(function (Collection $etudiants) use (&$probabilites) {
                    $absenteistes = $etudiants->random(3)
                                              ->pluck('id')
                                              ->all();

                    foreach ($etudiants as $etudiant) {
                        $probabilites[$etudiant->id] = in_array($etudiant->id, $absenteistes, true) ? 25 : 4;
                    }
                });

        Seance
            ::query()
            ->non_annulees()
            ->whereDate('date', '<', today())
            ->with(['groupe.etudiants', 'enseignant.user'])
            ->get()
            ->each(function (Seance $seance) use ($probabilites) {
                if (fake()->boolean(5)) {
                    return; // appel oublié : visible dans "Appels non faits"
                }

                $absences = $seance->groupe->etudiants
                    ->filter(fn(Etudiant $etudiant) => fake()->boolean($probabilites[$etudiant->id] ?? 4))
                    ->map(fn(Etudiant $etudiant) => [
                        'etudiant_id' => $etudiant->id,
                        'remarque'    => fake()->boolean(15) ? fake()->randomElement(["Absence signalée par un camarade", "Retard de plus de 30 minutes", "Parti avant la fin"]) : null,
                    ])
                    ->values()
                    ->all();

                AppelService::enregistrer_appel($seance, $absences, $seance->enseignant->user);

                //======================================================================================================
                // L'appel a été fait à l'heure de la séance, pas au moment du seeding
                //======================================================================================================
                $seance->forceFill(['appel_fait_le' => $seance->date?->copy()
                                                                    ->setTimeFromTimeString($seance->heure_debut ?? '08:30')
                                                                    ->addMinutes(10)])
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
        $deja = Seance::query()
                      ->where('enseignant_id', $enseignant->id)
                      ->whereDate('date', today())
                      ->exists();

        if ($deja) {
            return;
        }

        $module = $enseignant->modules()
                             ->first();
        $groupe = Groupe::query()
                        ->where('filiere_id', $module->filiere_id)
                        ->where('niveau', FiliereService::get_niveau_by_semestre($module->semestre))
                        ->first();

        if (!$groupe) {
            return;
        }

        foreach (array_reverse(SeanceService::creneaux) as [$debut, $fin]) {
            $conflit = SeanceService::get_seance_en_conflit('groupe_id', $groupe->id, today()->format('Y-m-d'), $debut, $fin, null)
                ?? SeanceService::get_seance_en_conflit('enseignant_id', $enseignant->id, today()->format('Y-m-d'), $debut, $fin, null);

            if (!$conflit) {
                Seance::create([
                                   'module_id'     => $module->id,
                                   'enseignant_id' => $enseignant->id,
                                   'groupe_id'     => $groupe->id,
                                   'date'          => today()->format('Y-m-d'),
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



    protected function afficher_resume() : void {
        $this->command->newLine();
        $this->command->info('Données de démonstration créées :');

        $this->command->table(['Élément', 'Nombre'], [
            ['Filières', Filiere::count()],
            ['Modules', Module::count()],
            ['Groupes', Groupe::count()],
            ['Étudiants', Etudiant::count()],
            ['Enseignants', Enseignant::count()],
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
