<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** HU-005 · Pago grupal: las suscripciones de un mismo cobro a una pareja o familia comparten "lote". */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suscripciones', function (Blueprint $table): void {
            $table->foreignId('grupo_id')->nullable()->after('promocion_id')->constrained('grupos')->nullOnDelete();
            $table->string('lote', 36)->nullable()->after('grupo_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('suscripciones', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('grupo_id');
            $table->dropColumn('lote');
        });
    }
};
