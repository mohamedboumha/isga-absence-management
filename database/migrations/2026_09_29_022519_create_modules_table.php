<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('niveau_etude_id')
                  ->constrained('niveaux_etudes')
                  ->restrictOnDelete();

            $table->string('code', 15)
                  ->unique();          // ex. GI-BDD
            $table->string('intitule', 150);               // ex. Bases de données
            $table->unsignedTinyInteger('semestre');       // 1 à nb_semestres du niveau d'études
            $table->unsignedSmallInteger('volume_horaire'); // en heures

            $table->timestamps();
            $table->softDeletes();
        });
    }



    public function down() : void {
        Schema::dropIfExists('modules');
    }
};
