<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHandoverLogsTable extends Migration
{
    public function up()
    {
        Schema::create('handover_logs', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('shift',['pagi','siang','malam']);
            $table->foreignId('from_user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('to_user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->text('issues');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('handover_logs');
    }
}
