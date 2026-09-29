<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('seance_id')
                  ->constrained('seances')
                  ->restrictOnDelete();
            $table->foreignId('etudiant_id')
                  ->constrained('etudiants')
                  ->restrictOnDelete();
            $table->foreignId('saisie_par')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->boolean('justifiee')
                  ->default(false);    // passera à true avec les justificatifs
            $table->string('remarque', 255)
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['seance_id', 'etudiant_id']);   // RG-03 : une seule absence par étudiant et par séance
            $table->index('etudiant_id');                   // historique d'un étudiant (BF-17)
        });
    }



    public function down() : void {
        Schema::dropIfExists('absences');
    }
};
