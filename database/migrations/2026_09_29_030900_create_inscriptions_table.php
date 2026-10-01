<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('etudiant_id')
                  ->constrained('etudiants')
                  ->restrictOnDelete();
            $table->foreignId('groupe_id')
                  ->constrained('groupes')
                  ->restrictOnDelete();
            $table->foreignId('annee_universitaire_id')
                  ->constrained('annees_universitaires')
                  ->restrictOnDelete();

            $table->string('decision', 12)
                  ->nullable();   // fin d'année : ADMIS, REDOUBLANT, DIPLOME, SORTANT

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['etudiant_id', 'annee_universitaire_id']); // RG-01 : une inscription par étudiant et par année
            $table->index('groupe_id');
        });
    }



    public function down() : void {
        Schema::dropIfExists('inscriptions');
    }
};
