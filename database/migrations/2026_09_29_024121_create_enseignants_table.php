<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('enseignants', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('user_id')
                  ->nullable()
                  ->unique()                 // un compte = une seule fiche enseignant
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('email')
                  ->unique();
            $table->string('telephone', 20)
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        //==============================================================================================================
        // Modules enseignés (plusieurs-à-plusieurs)
        //==============================================================================================================
        Schema::create('enseignant_module', function (Blueprint $table) {
            $table->foreignId('enseignant_id')
                  ->constrained('enseignants')
                  ->cascadeOnDelete();
            $table->foreignId('module_id')
                  ->constrained('modules')
                  ->cascadeOnDelete();

            $table->primary(['enseignant_id', 'module_id']);
        });
    }



    public function down() : void {
        Schema::dropIfExists('enseignant_module');
        Schema::dropIfExists('enseignants');
    }
};
