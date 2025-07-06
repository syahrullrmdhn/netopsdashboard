<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('escalation_levels', function (Blueprint $table) {
            $table->integer('level')->primary();
            $table->string('label');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->timestamps();
        });

        // Seed default levels
        $levels = [
            0 => 'Initial Alert',
            1 => 'Minor Escalation',
            2 => 'Moderate Escalation',
            3 => 'Major Escalation',
            4 => 'Critical Escalation',
            5 => 'Emergency Escalation',
        ];
        foreach ($levels as $lvl => $label) {
            \DB::table('escalation_levels')->insert([
                'level'    => $lvl,
                'label'    => $label,
                'name'     => '',
                'phone'    => '',
                'email'    => '',
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('escalation_levels');
    }
};
