<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('justificatifs', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('etudiant_id')
                  ->constrained('etudiants')
                  ->restrictOnDelete();

            $table->string('type', 15);                     // MEDICAL, FAMILIAL, ADMINISTRATIF, AUTRE
            $table->date('date_debut');
            $table->date('date_fin');
            $table->text('motif')
                  ->nullable();
            $table->date('date_depot');

            $table->string('fichier_chemin')
                  ->nullable();   // chemin sur le disque privé
            $table->string('fichier_nom')
                  ->nullable();      // nom d'origine, pour le téléchargement

            $table->string('statut', 12)
                  ->default('EN_ATTENTE'); // EN_ATTENTE, VALIDE, REFUSE
            $table->string('motif_refus', 500)
                  ->nullable();
            $table->foreignId('traite_par')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('traite_le')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['etudiant_id', 'statut']);
        });

        //==============================================================================================================
        // Chaque absence justifiée pointe vers son justificatif
        //==============================================================================================================
        Schema::table('absences', function (Blueprint $table) {
            $table->foreignId('justificatif_id')
                  ->nullable()
                  ->after('justifiee')
                  ->constrained('justificatifs')
                  ->nullOnDelete();
        });
    }



    public function down() : void {
        Schema::table('absences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('justificatif_id');
        });

        Schema::dropIfExists('justificatifs');
    }
};
