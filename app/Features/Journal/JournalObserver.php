<?php

namespace App\Features\Journal;

use Illuminate\Database\Eloquent\Model;

class JournalObserver {
    public function created(Model $model) : void {
        JournalService::enregistrer(
            $model,
            JournalService::action_creation,
            null,
            JournalService::filtrer($model->getAttributes())
        );
    }



    public function updated(Model $model) : void {
        //==============================================================================================================
        // Seuls les champs réellement modifiés, avec leur ancienne valeur
        //==============================================================================================================
        $nouvelles = JournalService::filtrer($model->getChanges());

        if (!$nouvelles) {
            return; // ex. seul updated_at ou deleted_at a changé (restauration)
        }

        $anciennes = array_intersect_key($model->getOriginal(), $nouvelles);

        JournalService::enregistrer($model, JournalService::action_modification, $anciennes, $nouvelles);
    }



    public function deleted(Model $model) : void {
        JournalService::enregistrer(
            $model,
            JournalService::action_suppression,
            JournalService::filtrer($model->getAttributes()),
            null
        );
    }



    public function restored(Model $model) : void {
        JournalService::enregistrer($model, JournalService::action_restauration, null, null);
    }
}
