<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inegi_states', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('cvegeo', 2)->index();
            $tabla->string('cve_ent', 2)->unique();
            $tabla->string('nomgeo', 80);
            $tabla->string('nom_abrev', 20)->nullable();
            $tabla->unsignedBigInteger('pob_total')->nullable();
            $tabla->unsignedBigInteger('pob_femenina')->nullable();
            $tabla->unsignedBigInteger('pob_masculina')->nullable();
            $tabla->unsignedBigInteger('total_viviendas_habitadas')->nullable();
            $tabla->json('raw')->nullable();
            $tabla->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('inegi_states');
    }
};
