<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNocShiftAssignmentsTable extends Migration
{
    public function up()
    {
        Schema::create('noc_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('shift', ['pagi','siang','malam']);
            // ← add ->nullable() here
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['date','shift']);
        });
    }


    public function down()
    {
        Schema::dropIfExists('noc_shift_assignments');
    }
}
