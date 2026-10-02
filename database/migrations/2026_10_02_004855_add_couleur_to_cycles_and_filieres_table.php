<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    //==================================================================================================================
    // Couleurs de la structure ISGA (les autres cycles prennent l'ardoise ; les autres filières héritent de leur cycle)
    //==================================================================================================================
    const array couleurs_cycles = [
        'ING' => '#0369A1',
        'MST' => '#7C3AED',
        'LIC' => '#B45309',
    ];

    const array couleurs_filieres = [
        'ISI'   => '#0E7490',
        'ISII'  => '#4F46E5',
        'IDWM'  => '#15803D',
        'SIICQ' => '#4D7C0F',
        'IABD'  => '#BE185D',
        'IRSS'  => '#475569',
        'CF'    => '#86198F',
        'MDEC'  => '#C2410C',
        'CCA'   => '#1E3A8A',
        'IF'    => '#0F766E',
    ];



    public function up() : void {
        Schema::table('cycles', function (Blueprint $table) {
            $table->string('couleur', 7)
                  ->default('#475569')
                  ->after('nb_annees');
        });

        Schema::table('filieres', function (Blueprint $table) {
            $table->string('couleur', 7)
                  ->nullable()
                  ->after('nom'); // null = couleur du cycle
        });

        foreach (self::couleurs_cycles as $code => $couleur) {
            DB::table('cycles')
              ->where('code', $code)
              ->update(['couleur' => $couleur]);
        }

        foreach (self::couleurs_filieres as $code => $couleur) {
            DB::table('filieres')
              ->where('code', $code)
              ->update(['couleur' => $couleur]);
        }
    }



    public function down() : void {
        Schema::table('filieres', function (Blueprint $table) {
            $table->dropColumn('couleur');
        });

        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('couleur');
        });
    }
};
