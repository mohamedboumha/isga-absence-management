<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('groupes', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('annee_universitaire_id')
                  ->constrained('annees_universitaires')
                  ->restrictOnDelete();

            $table->foreignId('niveau_etude_id')
                  ->constrained('niveaux_etudes')
                  ->restrictOnDelete();

            $table->string('nom', 30);     // ex. GI-L3-A

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['annee_universitaire_id', 'nom']); // un nom de groupe unique par année
        });
    }



    public function down() : void {
        Schema::dropIfExists('groupes');
    }
};
