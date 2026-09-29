<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('groupe_id')
                  ->constrained('groupes')
                  ->restrictOnDelete();

            $table->string('cne', 10)
                  ->unique();     // Code Massar, ex. R130245678
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email')
                  ->nullable()
                  ->unique();
            $table->string('telephone', 20)
                  ->nullable();
            $table->date('date_naissance')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['nom', 'prenom']);       // recherche et tri par nom plus rapides
        });
    }



    public function down() : void {
        Schema::dropIfExists('etudiants');
    }
};
