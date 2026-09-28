<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('annees_universitaires', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();

            $table->string('libelle', 9)
                  ->unique();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('active')
                  ->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }



    public function down() : void {
        Schema::dropIfExists('annees_universitaires');
    }
};
