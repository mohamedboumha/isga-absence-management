<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('journal_actions', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('action', 15);                   // CREATION, MODIFICATION, SUPPRESSION, RESTAURATION
            $table->string('entite', 50);                   // ex. Justificatif
            $table->unsignedBigInteger('entite_id')
                  ->nullable();
            $table->string('entite_cle', 32)
                  ->nullable();
            $table->string('entite_label')
                  ->nullable();     // ex. "Salma EL IDRISSI"

            $table->json('anciennes_valeurs')
                  ->nullable();
            $table->json('nouvelles_valeurs')
                  ->nullable();
            $table->string('ip', 45)
                  ->nullable();           // 45 caractères : assez pour une adresse IPv6

            $table->timestamp('created_at')
                  ->useCurrent();

            $table->index(['entite', 'entite_id']);        // historique d'un élément
            $table->index('created_at');
        });
    }



    public function down() : void {
        Schema::dropIfExists('journal_actions');
    }
};
