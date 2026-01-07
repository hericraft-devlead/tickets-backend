<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {

            $table->dropForeign(['moodle_user_id']);
        });
        

        Schema::table('moodle_users', function (Blueprint $table) {

            $table->dropColumn('id');
            

            $table->bigInteger('moodle_user_id')->unsigned()->change();
        });

        Schema::table('moodle_users', function (Blueprint $table) {
            $table->primary('moodle_user_id');
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('moodle_user_id')
                ->references('moodle_user_id')
                ->on('moodle_users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['moodle_user_id']);
        });
        
        Schema::table('moodle_users', function (Blueprint $table) {
            $table->dropPrimary();
            $table->id()->first(); 
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('moodle_user_id')
                  ->references('id')
                  ->on('moodle_users')
                  ->onDelete('cascade');
        });
    }
};