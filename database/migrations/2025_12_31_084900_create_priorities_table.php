<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique()
                ->comment('Baja, Media, Alta, Crítica');

            $table->unsignedTinyInteger('level')
                ->comment('Nivel numérico para orden y SLA');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
