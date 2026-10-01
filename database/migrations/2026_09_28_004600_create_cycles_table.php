<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)->unique();

            $table->string('code', 10)->unique();          // ex. ING
            $table->string('nom', 100)->unique();          // ex. Cycle ingénieur
            $table->unsignedTinyInteger('nb_annees');      // Licence 1, Master 2, Cycle ingénieur 5

            $table->timestamps();
            $table->softDeletes();
        });
    }



    public function down() : void {
        Schema::dropIfExists('cycles');
    }
};
