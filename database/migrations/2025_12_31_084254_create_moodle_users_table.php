<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('moodle_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('moodle_user_id')->unique(); 
            $table->string('username')->unique(); 
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();  
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moodle_users');
    }
};
