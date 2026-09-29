<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void {
        Schema::table('seances', function (Blueprint $table) {
            $table->timestamp('appel_fait_le')
                  ->nullable()
                  ->after('annulee');
            $table->foreignId('appel_fait_par')
                  ->nullable()
                  ->after('appel_fait_le')
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }



    public function down() : void {
        Schema::table('seances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appel_fait_par');
            $table->dropColumn('appel_fait_le');
        });
    }
};
