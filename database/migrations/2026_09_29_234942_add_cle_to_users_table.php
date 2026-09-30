<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up() : void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cle', 32)->nullable()->unique()->after('id');
        });

        //==============================================================================================================
        // Comptes existants : on leur génère une clé
        //==============================================================================================================
        DB::table('users')
          ->whereNull('cle')
          ->orderBy('id')
          ->each(fn($user) => DB::table('users')->where('id', $user->id)->update(['cle' => Str::random(32)]));
    }



    public function down() : void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['cle']);
            $table->dropColumn('cle');
        });
    }
};
