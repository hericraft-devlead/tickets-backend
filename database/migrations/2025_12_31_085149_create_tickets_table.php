<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('title')
                ->comment('Título o descripción breve del problema');

            $table->text('description')
                ->comment('Descripción detallada del problema');


            $table->string('contact_name')
                ->comment('Nombre de quien crea el ticket');

            $table->string('contact_email')
                ->comment('Correo de contacto');


            $table->foreignId('moodle_user_id')
                ->nullable()
                ->constrained('moodle_users')
                ->nullOnDelete();


            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();


            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();


            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->foreignId('priority_id')
                ->nullable()
                ->constrained('priorities')
                ->nullOnDelete();

            $table->foreignId('status_id')
                ->constrained('ticket_statuses');


            $table->timestamp('closed_at')
                ->nullable()
                ->comment('Fecha en la que el ticket fue cerrado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
