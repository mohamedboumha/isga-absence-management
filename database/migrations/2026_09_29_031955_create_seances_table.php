<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('seances', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('module_id')
                  ->constrained('modules')
                  ->restrictOnDelete();
            $table->foreignId('enseignant_id')
                  ->constrained('enseignants')
                  ->restrictOnDelete();
            $table->foreignId('groupe_id')
                  ->constrained('groupes')
                  ->restrictOnDelete();

            $table->date('date');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('type', 5);                 // COURS, TD, TP
            $table->string('salle', 30)
                  ->nullable();
            $table->boolean('annulee')
                  ->default(false);

            $table->timestamps();
            $table->softDeletes();

            //==========================================================================================================
            // Index pour les recherches fréquentes : planning du jour, d'un groupe, d'un enseignant
            //==========================================================================================================
            $table->index('date');
            $table->index(['groupe_id', 'date']);
            $table->index(['enseignant_id', 'date']);
        });
    }



    public function down() : void {
        Schema::dropIfExists('seances');
    }
};
