<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Administrador, recepcionista y cliente comparten la misma tabla de acceso.
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('ci', 20)->nullable()->unique();
            $table->string('password');
            $table->string('rol', 20);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('planes', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->unsignedSmallInteger('duracion_dias');
            $table->decimal('precio', 10, 2);
            $table->unsignedInteger('puntos');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('niveles', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->unsignedSmallInteger('meses_minimos')->unique();
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->unsignedInteger('bono_puntos')->default(0);
            $table->string('beneficios')->nullable();
            $table->timestamps();
        });

        Schema::create('grupos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('tipo', 20); // pareja | familiar
            $table->timestamps();
        });

        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('referido_por_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('ci', 20)->unique();
            $table->string('email')->unique();
            $table->string('telefono', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->integer('puntos')->default(0);
            $table->timestamps();
        });

        Schema::create('promociones', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('tipo', 20); // fecha_especial | cumpleanos | pareja | familiar
            $table->decimal('porcentaje', 5, 2);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->unsignedTinyInteger('min_personas')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('planes')->nullOnDelete();
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('suscripciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('planes');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promocion_id')->nullable()->constrained('promociones')->nullOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('precio_base', 10, 2);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->string('descuento_motivo')->nullable();
            $table->decimal('total', 10, 2);
            $table->string('metodo_pago', 20);
            $table->unsignedInteger('puntos_ganados')->default(0);
            $table->timestamps();
        });

        Schema::create('premios', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->unsignedInteger('puntos');
            $table->unsignedInteger('stock')->nullable(); // null = sin límite
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('canjes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('premio_id')->constrained('premios');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('puntos');
            $table->timestamps();
        });

        Schema::create('movimientos_puntos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->string('tipo', 20); // suscripcion | referido | antiguedad | canje | ajuste
            $table->integer('puntos');
            $table->string('descripcion');
            $table->foreignId('suscripcion_id')->nullable()->constrained('suscripciones')->nullOnDelete();
            $table->foreignId('canje_id')->nullable()->constrained('canjes')->nullOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('configuraciones', function (Blueprint $table): void {
            $table->string('clave')->primary();
            $table->string('valor');
        });
    }

    public function down(): void
    {
        foreach (['configuraciones', 'movimientos_puntos', 'canjes', 'premios', 'suscripciones', 'promociones', 'clientes', 'grupos', 'niveles', 'planes', 'users'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
