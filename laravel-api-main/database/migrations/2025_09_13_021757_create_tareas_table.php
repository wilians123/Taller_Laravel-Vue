<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tareas')) {
            Schema::create('tareas', function (Blueprint $table) {
                $table->id();
                $table->string('titulo', 200);
                $table->text('descripcion')->nullable();
                $table->enum('estado', ['pendiente', 'en_progreso', 'completada'])->default('pendiente');
                $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');
                $table->date('fecha_vencimiento');
                $table->unsignedBigInteger('usuario_id');
                $table->timestamps();

                // Relación con la tabla usuarios
                $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');

                // Índices para mejorar rendimiento
                $table->index('estado');
                $table->index('fecha_vencimiento');
                $table->index('usuario_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
