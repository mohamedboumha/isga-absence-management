<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::create('filieres', function (Blueprint $table) {
            $table->id();
            $table->string('cle', 32)
                  ->unique();
            $table->foreignId('cycle_id')
                  ->constrained('cycles')
                  ->restrictOnDelete();
            $table->string('code', 10)
                  ->unique();
            $table->string('nom', 150)
                  ->unique();
            $table->text('description')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }



    public function down() : void {
        Schema::dropIfExists('filieres');
    }
};
