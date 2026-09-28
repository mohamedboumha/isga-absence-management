<?php

namespace App\_Core\Navigation;

use App\Features\User\UserService;
use App\_Core\Builders\SideBar\SideBarBuilder;
use App\_Core\Builders\SideBar\SideBarGroupe;
use App\_Core\Builders\SideBar\SideBarItem;
use App\Models\User;

class SideBarService {

    public static function get_sidebar(?User $user) : array {
        return SideBarBuilder
            ::new()
            //==========================================================================================================
            // Accueil (tous les rôles)
            //==========================================================================================================
            ->add_groupe(
                SideBarGroupe
                    ::new()
                    ->add_item(SideBarItem::new()
                                          ->label("Tableau de bord")
                                          ->icon('LayoutGrid')
                                          ->route('dashboard'))
            )
            //==========================================================================================================
            // Enseignant
            //==========================================================================================================
            ->add_groupe(
                SideBarGroupe
                    ::new()
                    ->label("Mon espace")
                    ->roles([UserService::role_enseignant])
                    ->add_item(SideBarItem::new()
                                          ->label("Mes séances")
                                          ->icon('CalendarCheck')
                                          ->route('mes-seances.list'))
                    ->add_item(SideBarItem::new()
                                          ->label("Absences de mes groupes")
                                          ->icon('UserX')
                                          ->route('mes-absences.list'))
            )
            //==========================================================================================================
            // Référentiel (administration)
            //==========================================================================================================
            ->add_groupe(
                SideBarGroupe
                    ::new()
                    ->label("Référentiel")
                    ->roles(UserService::roles_administration)
                    ->add_item(SideBarItem::new()
                                          ->label("Années universitaires")
                                          ->icon('CalendarRange')
                                          ->route('annees-universitaires.list'))
                    ->add_item(SideBarItem::new()
                                          ->label("Semestres")
                                          ->icon('CalendarDays')
                                          ->route('semestres.list'))
                    ->add_item(SideBarItem::new()
                                          ->label("Filières")
                                          ->icon('GraduationCap')
                                          ->route('filieres.list'))
                    ->add_item(SideBarItem::new()
                                          ->label("Groupes")
                                          ->icon('Users')
                                          ->route('groupes.list'))
                    ->add_item(SideBarItem::new()
                                          ->label("Modules")
                                          ->icon('BookOpen')
                                          ->route('modules.list'))
                    ->add_item(SideBarItem::new()
                                          ->label("Enseignants")
                                          ->icon('Presentation')
                                          ->route('enseignants.list'))
                    ->add_item(SideBarItem::new()
                                          ->label("Étudiants")
                                          ->icon('UserRound')
                                          ->route('etudiants.list'))
            )
            //==========================================================================================================
            // Présences (administration)
            //==========================================================================================================
            ->add_groupe(
                SideBarGroupe
                    ::new()
                    ->label("Présences")
                    ->roles(UserService::roles_administration)
                    ->add_item(SideBarItem::new()
                                          ->label("Séances")
                                          ->icon('CalendarCheck')
                                          ->route('seances.list'))
                    ->add_item(SideBarItem::new()
                                          ->label("Absences")
                                          ->icon('UserX')
                                          ->route('absences.list'))
                    ->add_item(SideBarItem::new()
                                          ->label("Justificatifs")
                                          ->icon('FileCheck')
                                          ->route('justificatifs.list'))
            )
            //==========================================================================================================
            // Statistiques (administration)
            //==========================================================================================================
            ->add_groupe(
                SideBarGroupe
                    ::new()
                    ->label("Statistiques")
                    ->roles(UserService::roles_administration)
                    ->add_item(SideBarItem::new()
                                          ->label("Statistiques")
                                          ->icon('ChartColumn')
                                          ->route('statistiques.index'))
            )
            //==========================================================================================================
            // Comptes (super-administrateur uniquement)
            //==========================================================================================================
            ->add_groupe(
                SideBarGroupe
                    ::new()
                    ->label("Comptes")
                    ->roles([UserService::role_super_admin])
                    ->add_item(SideBarItem::new()
                                          ->label("Utilisateurs")
                                          ->icon('ShieldUser')
                                          ->route('utilisateurs.list'))
            )
            ->get($user);
    }
}
