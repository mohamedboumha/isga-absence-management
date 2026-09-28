<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('semestres', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('annee_universitaire_id')
                  ->constrained('annees_universitaires')
                  ->restrictOnDelete();

            $table->string('libelle', 3);
            $table->date('date_debut');
            $table->date('date_fin');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['annee_universitaire_id', 'libelle']);
        });
    }



    public function down() : void {
        Schema::dropIfExists('semestres');
    }
};
