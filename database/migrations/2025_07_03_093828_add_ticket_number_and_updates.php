<?php
// database/migrations/2025_07_03_200000_add_ticket_number_and_updates.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTicketNumberAndUpdates extends Migration
{
    public function up()
    {
        // 1) tickets: kolom unik ticket_number
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('ticket_number', 30)->unique()->after('id');
        });

        // 2) ticket_updates: simpan setiap update chronologically
        Schema::create('ticket_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('detail');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_updates');
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('ticket_number');
        });
    }
}
