<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('niveaux_etudes', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('cycle_id')
                  ->constrained('cycles')
                  ->restrictOnDelete();
            $table->foreignId('filiere_id')
                  ->nullable()
                  ->constrained('filieres')
                  ->restrictOnDelete(); // null = tronc commun (1AP, 2AP)

            $table->unsignedTinyInteger('annee_cycle');     // 1 à nb_annees du cycle
            $table->string('code', 20)
                  ->unique();           // ex. 3CI-IABD
            $table->string('libelle', 150);                 // ex. 3ème année cycle ingénieur — IABD
            $table->unsignedTinyInteger('nb_semestres')
                  ->default(2);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['cycle_id', 'annee_cycle']);
        });

        //==============================================================================================================
        // Parcours : niveaux possibles l'année suivante (plusieurs-à-plusieurs, d'un niveau vers un niveau)
        //==============================================================================================================
        Schema::create('parcours', function (Blueprint $table) {
            $table->foreignId('niveau_id')
                  ->constrained('niveaux_etudes')
                  ->cascadeOnDelete();
            $table->foreignId('niveau_suivant_id')
                  ->constrained('niveaux_etudes')
                  ->cascadeOnDelete();

            $table->primary(['niveau_id', 'niveau_suivant_id']);
        });
    }



    public function down() : void {
        Schema::dropIfExists('parcours');
        Schema::dropIfExists('niveaux_etudes');
    }
};
